<?php

require_once __DIR__ . '/../Models/Programas.php';
require_once __DIR__ . '/../Helpers/HtmlSanitizer.php';
require_once __DIR__ . '/../Helpers/UploadPath.php';
require_once __DIR__ . '/../Helpers/UploadCleaner.php';
require_once __DIR__ . '/../../config/database.php';

class ProgramasController
{
    private $db;
    private $programas;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->programas = new Programas($this->db);
    }

    private function sanitizeText($value, int $max = 255)
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        return mb_substr($value, 0, $max);
    }

    private function validateDate($value)
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function validateUrl($value)
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }

    private function validateList($value, array $fields, array $requiredFields = [])
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value) || ($value !== [] && array_keys($value) !== range(0, count($value) - 1))) {
            return false;
        }

        $items = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                return false;
            }

            $validatedItem = [];
            foreach ($fields as $field => $maxLength) {
                $fieldValue = $item[$field] ?? '';
                $cleanValue = $this->sanitizeText($fieldValue, $maxLength);
                if (in_array($field, $requiredFields, true) && $cleanValue === null) {
                    return false;
                }
                if ($field === 'correo' && $cleanValue !== null && !filter_var($cleanValue, FILTER_VALIDATE_EMAIL)) {
                    return false;
                }
                if ($field === 'linkedin' && $cleanValue !== null && !$this->validateUrl($cleanValue)) {
                    return false;
                }
                if ($field === 'foto' && $cleanValue !== null && !$this->validateUrl($cleanValue) && !preg_match('#^/(?!/)#', $cleanValue)) {
                    return false;
                }
                $validatedItem[$field] = $cleanValue;
            }
            $items[] = $validatedItem;
        }

        return json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function validateProgramaPayload(array $data, bool $requireTitleCategory = true)
    {
        if (!empty($data['_invalid_docente_photo'])) {
            return null;
        }

        $result = [];
        $titulo = $this->sanitizeText($data['titulo'] ?? '', 200);
        $categoria = $this->sanitizeText($data['categoria'] ?? '', 100);

        if ($titulo !== null) {
            $result['titulo'] = $titulo;
        }
        if ($categoria !== null) {
            $result['categoria'] = $categoria;
        }
        if ($requireTitleCategory && ($titulo === null || $categoria === null)) {
            return null;
        }

        $autor = $this->sanitizeText($data['autor'] ?? '', 150);
        if ($autor !== null) {
            $result['autor'] = $autor;
        }

        $result['descripcion'] = HtmlSanitizer::clean($data['descripcion'] ?? null, 20000);
        $result['link'] = $this->validateUrl($data['link'] ?? '');
        $result['fecha_inicio'] = $this->validateDate($data['fecha_inicio'] ?? '');
        $result['fecha_fin'] = $this->validateDate($data['fecha_fin'] ?? '');
        $result['lugar'] = $this->sanitizeText($data['lugar'] ?? '', 200);

        $textFields = [
            'duracion' => 120,
            'horario' => 180,
            'frecuencia' => 120,
            'dirigido_a' => 1000,
            'objetivos' => 3000,
            'requisitos' => 2000,
            'certificacion' => 1500,
            'inversion' => 1000,
            'descuentos' => 1000,
            'contacto_telefono' => 60,
            'contacto_whatsapp' => 60,
        ];
        foreach ($textFields as $field => $maxLength) {
            if (array_key_exists($field, $data)) {
                $result[$field] = $this->sanitizeText($data[$field], $maxLength);
            }
        }

        if (array_key_exists('modalidad', $data)) {
            $modalidad = $this->sanitizeText($data['modalidad'], 20);
            $result['modalidad'] = in_array($modalidad, ['presencial', 'virtual', 'hibrida'], true) ? $modalidad : null;
        }
        if (array_key_exists('contacto_correo', $data)) {
            $correo = $this->sanitizeText($data['contacto_correo'], 254);
            $result['contacto_correo'] = $correo !== null && filter_var($correo, FILTER_VALIDATE_EMAIL) ? $correo : null;
        }
        if (array_key_exists('vacantes', $data)) {
            $vacantes = $data['vacantes'];
            $result['vacantes'] = $vacantes === '' || $vacantes === null
                ? null
                : (filter_var($vacantes, FILTER_VALIDATE_INT) !== false && (int) $vacantes >= 0 ? (int) $vacantes : null);
        }
        if (array_key_exists('temario', $data)) {
            $result['temario'] = $this->validateList($data['temario'], ['titulo' => 200, 'descripcion' => 2000], ['titulo']);
            if ($result['temario'] === false) return null;
        }
        if (array_key_exists('docentes', $data)) {
            $result['docentes'] = $this->validateList($data['docentes'], [
                'foto' => 500,
                'nombre' => 180,
                'cargo' => 180,
                'descripcion' => 1500,
                'correo' => 254,
                'linkedin' => 500,
            ], ['nombre']);
            if ($result['docentes'] === false) return null;
        }

        if (isset($data['imagen'])) {
            $result['imagen'] = $data['imagen'];
        }

        return $result;
    }

    private function getPayload()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        $payload = [];
        if (!empty($_POST)) {
            $payload = $_POST;
        }

        // Si se envió un archivo pero no es válido, se avisa en lugar de ignorarlo (o de borrar la imagen actual).
        if (isset($_FILES['imagen']) && ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $imagePath = $_FILES['imagen']['error'] === UPLOAD_ERR_OK
                ? $this->storeUploadedImage($_FILES['imagen'])
                : null;
            if ($imagePath === null) {
                $payload['_imagen_error'] = true;
            } else {
                $payload['imagen'] = $imagePath;
            }
        }

        $teacherPhotos = [];
        foreach ($_FILES as $fieldName => $file) {
            if (!preg_match('/^docente_foto_(\d+)$/', $fieldName, $matches)) {
                continue;
            }
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $photoPath = $this->storeUploadedImage($file);
            if ($photoPath === null) {
                $payload['_invalid_docente_photo'] = true;
                continue;
            }
            $teacherPhotos[(int) $matches[1]] = $photoPath;
        }

        if ($teacherPhotos) {
            $teachers = $payload['docentes'] ?? [];
            if (is_string($teachers)) {
                $teachers = json_decode($teachers, true);
            }
            if (!is_array($teachers)) {
                $payload['_invalid_docente_photo'] = true;
            } else {
                foreach ($teacherPhotos as $index => $photoPath) {
                    if (!isset($teachers[$index]) || !is_array($teachers[$index])) {
                        $payload['_invalid_docente_photo'] = true;
                        continue;
                    }
                    $teachers[$index]['foto'] = $photoPath;
                }
                $payload['docentes'] = $teachers;
            }
        }

        return $payload;
    }

    private function storeUploadedImage($file)
    {
        if (!is_array($file) || $file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name']) || empty($file['name'])) {
            return null;
        }

        if (!is_int($file['size']) || $file['size'] > 2 * 1024 * 1024) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!is_string($mime) || strpos($mime, 'image/') !== 0) {
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            return null;
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid('img_', true) . '.' . $extension;
        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        return UploadPath::fileUrl($fileName);
    }

    public function createPrograma()
    {
        $data = $this->getPayload();
        if (!empty($data['_imagen_error'])) {
            http_response_code(400);
            echo json_encode(['message' => 'La imagen debe ser JPG, PNG, GIF o WEBP y pesar máximo 2 MB.']);
            return;
        }
        $validated = $this->validateProgramaPayload($data, true);

        if ($validated === null) {
            http_response_code(400);
            echo json_encode(['message' => 'Datos inválidos o incompletos.']);
            return;
        }

        $this->programas->titulo = $validated['titulo'];
        $this->programas->categoria = $validated['categoria'];
        $this->programas->imagen = $validated['imagen'] ?? null;
        $this->programas->autor = $validated['autor'] ?? null;
        $this->programas->descripcion = $validated['descripcion'] ?? null;
        $this->programas->link = $validated['link'] ?? null;
        $this->programas->fecha_inicio = $validated['fecha_inicio'] ?? null;
        $this->programas->fecha_fin = $validated['fecha_fin'] ?? null;
        $this->programas->lugar = $validated['lugar'] ?? null;
        $this->assignExtendedFields($validated);

        if ($this->programas->create()) {
            http_response_code(201);
            echo json_encode(['message' => 'Programa creado exitosamente.']);
            return;
        }

        http_response_code(503);
        echo json_encode(['message' => 'No se pudo crear el programa.']);
    }

    public function readProgramas()
    {
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : null;
        $query = 'SELECT * FROM programas ORDER BY id DESC';

        if ($limit !== null && $limit > 0) {
            $query .= ' LIMIT :limit';
        }

        $stmt = $this->db->prepare($query);

        if ($limit !== null && $limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();

        $programas_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $programas_arr[] = $this->formatPrograma($row);
        }

        http_response_code(200);
        echo json_encode(['data' => $programas_arr]);
    }

    public function readPrograma(int $id)
    {
        $query = 'SELECT * FROM programas WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            http_response_code(200);
            echo json_encode(['data' => $this->formatPrograma($row)]);
            return;
        }

        http_response_code(404);
        echo json_encode(['message' => 'Programa no encontrado.']);
    }

    public function updatePrograma(int $id)
    {
        $data = $this->getPayload();
        if (!empty($data['_imagen_error'])) {
            http_response_code(400);
            echo json_encode(['message' => 'La imagen debe ser JPG, PNG, GIF o WEBP y pesar máximo 2 MB.']);
            return;
        }
        $validated = $this->validateProgramaPayload($data, false);

        if (empty($id) || $validated === null || count($validated) === 0) {
            http_response_code(400);
            echo json_encode(['message' => 'ID o datos no proporcionados.']);
            return;
        }

        $existing = $this->getProgramaById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['message' => 'Programa no encontrado.']);
            return;
        }

        $this->programas->id = $id;
        $this->programas->titulo = $validated['titulo'] ?? $existing['titulo'];
        $this->programas->categoria = $validated['categoria'] ?? $existing['categoria'];
        $newImage = $validated['imagen'] ?? null;
        $this->programas->imagen = ($newImage !== null && $newImage !== '') ? $newImage : $existing['imagen'];
        $this->programas->autor = $validated['autor'] ?? $existing['autor'];
        $this->programas->descripcion = $validated['descripcion'] ?? $existing['descripcion'];
        $this->programas->link = $validated['link'] ?? $existing['link'];
        $this->programas->fecha_inicio = $validated['fecha_inicio'] ?? $existing['fecha_inicio'];
        $this->programas->fecha_fin = $validated['fecha_fin'] ?? $existing['fecha_fin'];
        $this->programas->lugar = $validated['lugar'] ?? $existing['lugar'];
        $this->assignExtendedFields($validated, $existing);

        if ($this->programas->update()) {
            $oldFiles = array_merge([$existing['imagen'] ?? null], UploadCleaner::teacherPhotos($existing['docentes'] ?? null));
            $keptFiles = array_merge([$this->programas->imagen], UploadCleaner::teacherPhotos($this->programas->docentes));
            UploadCleaner::deleteManyIfUnused($this->db, array_diff(array_filter($oldFiles, 'is_string'), array_filter($keptFiles, 'is_string')));
            http_response_code(200);
            echo json_encode(['message' => 'Programa actualizado exitosamente.']);
            return;
        }

        http_response_code(503);
        echo json_encode(['message' => 'No se pudo actualizar el programa.']);
    }

    private function assignExtendedFields(array $validated, array $existing = [])
    {
        foreach ([
            'duracion', 'modalidad', 'horario', 'frecuencia', 'dirigido_a', 'objetivos',
            'temario', 'requisitos', 'certificacion', 'docentes', 'inversion', 'descuentos',
            'vacantes', 'contacto_telefono', 'contacto_whatsapp', 'contacto_correo'
        ] as $field) {
            $this->programas->$field = array_key_exists($field, $validated)
                ? $validated[$field]
                : ($existing[$field] ?? null);
        }
    }

    private function formatPrograma(array $row)
    {
        $imagen = $row['imagen'] ?? null;
        if (UploadPath::isManagedPath($imagen)) {
            $imagen = UploadPath::normalize($imagen);
        }

        $programa = [
            'id' => $row['id'] ?? null,
            'titulo' => $row['titulo'] ?? null,
            'categoria' => $row['categoria'] ?? null,
            'imagen' => $imagen,
            'autor' => $row['autor'] ?? null,
            'descripcion' => $row['descripcion'] ?? null,
            'link' => $row['link'] ?? null,
            'fecha_inicio' => $row['fecha_inicio'] ?? null,
            'fecha_fin' => $row['fecha_fin'] ?? null,
            'lugar' => $row['lugar'] ?? null,
            'duracion' => $row['duracion'] ?? null,
            'modalidad' => $row['modalidad'] ?? null,
            'horario' => $row['horario'] ?? null,
            'frecuencia' => $row['frecuencia'] ?? null,
            'dirigido_a' => $row['dirigido_a'] ?? null,
            'objetivos' => $row['objetivos'] ?? null,
            'temario' => $row['temario'] ?? null,
            'requisitos' => $row['requisitos'] ?? null,
            'certificacion' => $row['certificacion'] ?? null,
            'docentes' => $row['docentes'] ?? null,
            'inversion' => $row['inversion'] ?? null,
            'descuentos' => $row['descuentos'] ?? null,
            'vacantes' => $row['vacantes'] ?? null,
            'contacto_telefono' => $row['contacto_telefono'] ?? null,
            'contacto_whatsapp' => $row['contacto_whatsapp'] ?? null,
            'contacto_correo' => $row['contacto_correo'] ?? null,
        ];

        foreach (['temario', 'docentes'] as $field) {
            $decoded = isset($programa[$field]) ? json_decode($programa[$field], true) : null;
            $programa[$field] = is_array($decoded) ? $decoded : [];
        }
        foreach ($programa['docentes'] as &$docente) {
            if (is_array($docente) && isset($docente['foto']) && UploadPath::isManagedPath($docente['foto'])) {
                $docente['foto'] = UploadPath::normalize($docente['foto']);
            }
        }
        unset($docente);

        return $programa;
    }

    private function getProgramaById(int $id)
    {
        $query = 'SELECT * FROM programas WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deletePrograma(int $id)
    {
        $existing = $this->getProgramaById($id);
        $this->programas->id = $id;

        if ($this->programas->delete()) {
            if ($existing) {
                UploadCleaner::deleteManyIfUnused($this->db, array_merge([$existing['imagen'] ?? null], UploadCleaner::teacherPhotos($existing['docentes'] ?? null)));
            }
            http_response_code(200);
            echo json_encode(['message' => 'Programa eliminado exitosamente.']);
            return;
        }

        http_response_code(503);
        echo json_encode(['message' => 'No se pudo eliminar el programa.']);
    }
}
