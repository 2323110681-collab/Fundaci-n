<?php

require_once __DIR__ . '/../Models/Miembros.php';
require_once __DIR__ . '/../Helpers/UploadPath.php';
require_once __DIR__ . '/../Helpers/UploadCleaner.php';
require_once __DIR__ . '/../../config/database.php';

class MiembrosController
{
    private $db;
    private $miembros;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->miembros = new Miembros($this->db);
    }

    private function payload(): array
    {
        $data = $_POST;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $data['imagen'] = $this->storeImage($_FILES['imagen']);
        }
        return $data;
    }

    private function text($value, int $max): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function storeImage(array $file): ?string
    {
        if ($file['size'] > 2 * 1024 * 1024 || empty($file['tmp_name'])) {
            return null;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            return null;
        }
        $directory = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $name = uniqid('member_', true) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            return null;
        }
        return UploadPath::fileUrl($name);
    }

    /**
     * Siguiente posicion libre. El campo de orden ya no se expone en el panel:
     * cada persona se coloca detras de la ultima que haya, en el orden en que se
     * vai registrando. Antes el valor por defecto era 99, con lo que todo
     * miembro nuevo caia al final sin saber en que puesto quedaba.
     */
    private function nextOrden(): int
    {
        $max = $this->db->query('SELECT COALESCE(MAX(orden), 0) FROM miembros')->fetchColumn();

        return max(1, (int) $max + 1);
    }

    /** Mensaje de error de la ultima validación, para poder responder con un 400. */
    private $validationError = '';

    private function validate(array $data, bool $required = true): ?array
    {
        $this->validationError = '';
        $nombre = $this->text($data['nombre'] ?? '', 180);
        $cargo = $this->text($data['cargo'] ?? '', 120);
        if ($required && (!$nombre || !$cargo)) {
            return null;
        }
        // Se conserva el dato en blanco cuando el campo viene vacio a proposito,
        // para poder borrar un correo. Solo se rechaza cuando hay texto y no es
        // una direccion valida: si no, un error de dedo borraria el dato.
        $correoEnviado = array_key_exists('correo', $data);
        $correo = $this->text($data['correo'] ?? '', 180);
        if ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->validationError = 'El correo electrónico no es válido.';
            return null;
        }
        return [
            'nombre' => $nombre,
            'cargo' => $cargo,
            'correo' => $correo,
            'correoEnviado' => $correoEnviado,
            'imagen' => array_key_exists('imagen', $data) ? $data['imagen'] : null,
        ];
    }

    public function readMiembros(): void
    {
        $rows = $this->miembros->readAll();
        foreach ($rows as &$row) {
            if (isset($row['imagen']) && UploadPath::isManagedPath($row['imagen'])) {
                $row['imagen'] = UploadPath::normalize($row['imagen']);
            }
        }
        unset($row);
        echo json_encode(['data' => $rows]);
    }

    public function readMiembro(int $id): void
    {
        $row = $this->miembros->readOne($id);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['message' => 'Miembro no encontrado.']);
            return;
        }
        if (isset($row['imagen']) && UploadPath::isManagedPath($row['imagen'])) {
            $row['imagen'] = UploadPath::normalize($row['imagen']);
        }
        echo json_encode(['data' => $row]);
    }

    public function createMiembro(): void
    {
        $validated = $this->validate($this->payload());
        if (!$validated || ($validated['imagen'] === null && isset($_FILES['imagen']))) {
            http_response_code(400);
            echo json_encode(['message' => $this->validationError ?: 'Nombre, cargo y una imagen válida son requeridos.']);
            return;
        }
        $this->miembros->nombre = $validated['nombre'];
        $this->miembros->cargo = $validated['cargo'];
        $this->miembros->correo = $validated['correo'];
        $this->miembros->orden = $this->nextOrden();
        $this->miembros->imagen = $validated['imagen'];
        $this->miembros->create();
        http_response_code(201);
        echo json_encode(['message' => 'Miembro creado correctamente.']);
    }

    public function updateMiembro(int $id): void
    {
        $existing = $this->miembros->readOne($id);
        $validated = $this->validate($this->payload(), false);
        if (!$existing || !$validated) {
            http_response_code(400);
            echo json_encode(['message' => $this->validationError ?: 'Datos de miembro inválidos.']);
            return;
        }
        $this->miembros->id = $id;
        $this->miembros->nombre = $validated['nombre'] ?? $existing['nombre'];
        $this->miembros->cargo = $validated['cargo'] ?? $existing['cargo'];
        // Un correo vacio se guarda como vacio (asi se puede quitar), y si el
        // campo no vino en el envio se conserva el que ya tenia.
        $this->miembros->correo = $validated['correoEnviado'] ? $validated['correo'] : $existing['correo'];
        // El orden ya no se edita: se conserva la posicion que ya tenía.
        $this->miembros->orden = (int) $existing['orden'];
        $this->miembros->imagen = $validated['imagen'] ?: $existing['imagen'];
        $this->miembros->update();
        if (($existing['imagen'] ?? null) !== $this->miembros->imagen) {
            UploadCleaner::deleteIfUnused($this->db, $existing['imagen'] ?? null);
        }
        echo json_encode(['message' => 'Miembro actualizado correctamente.']);
    }

    public function deleteMiembro(int $id): void
    {
        $existing = $this->miembros->readOne($id);
        $this->miembros->id = $id;
        $this->miembros->delete();
        if ($existing) {
            UploadCleaner::deleteIfUnused($this->db, $existing['imagen'] ?? null);
        }
        echo json_encode(['message' => 'Miembro eliminado correctamente.']);
    }
}
