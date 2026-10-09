<?php

require_once __DIR__ . '/../Models/Agenda.php';
require_once __DIR__ . '/../Helpers/HtmlSanitizer.php';
require_once __DIR__ . '/../Helpers/UploadPath.php';
require_once __DIR__ . '/../Helpers/UploadCleaner.php';
require_once __DIR__ . '/../../config/database.php';

class AgendaController
{
    private $db;
    private $agenda;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->agenda = new Agenda($this->db);
    }

    private function sanitizeText($value, int $max = 255)
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    /**
     * Convierte texto plano con formato simple a HTML.
     *
     * Reglas:
     * - Líneas que empiezan con "- " o "• " → <li> dentro de <ul>
     * - Líneas que empiezan con "1. ", "2. ", etc. → <li> dentro de <ol>
     * - Líneas que terminan en ":" → título en <strong>
     * - El resto → <p>
     * - Soporta **texto** para negritas inline
     */
    private function textToHtml(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Normalizar saltos de línea
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);

        $html = '';
        $inUl = false;
        $inOl = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Línea vacía: cerramos listas abiertas
            if ($line === '') {
                if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
                if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
                continue;
            }

            // Detectar viñetas: "- texto" o "• texto"
            if (preg_match('/^[-•]\s+(.+)$/u', $line, $m)) {
                if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
                if (!$inUl) { $html .= "<ul>\n"; $inUl = true; }
                $html .= '<li>' . $this->formatInline($m[1]) . "</li>\n";
                continue;
            }

            // Detectar listas numeradas: "1. texto"
            if (preg_match('/^\d+\.\s+(.+)$/u', $line, $m)) {
                if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
                if (!$inOl) { $html .= "<ol>\n"; $inOl = true; }
                $html .= '<li>' . $this->formatInline($m[1]) . "</li>\n";
                continue;
            }

            // Cerrar listas si veníamos de una
            if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
            if ($inOl) { $html .= "</ol>\n"; $inOl = false; }

            // Línea que termina en ":" → título en negrita
            if (substr($line, -1) === ':') {
                $html .= '<p><strong>' . $this->formatInline($line) . "</strong></p>\n";
                continue;
            }

            // Párrafo normal
            $html .= '<p>' . $this->formatInline($line) . "</p>\n";
        }

        // Cerrar listas si quedaron abiertas al final
        if ($inUl) { $html .= "</ul>\n"; }
        if ($inOl) { $html .= "</ol>\n"; }

        return trim($html);
    }

    /**
     * Aplica formato inline: **negrita** → <strong>negrita</strong>
     * y escapa el HTML para evitar inyecciones.
     */
    private function formatInline(string $text): string
    {
        // Primero escapamos todo el HTML por seguridad
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Luego convertimos **texto** a <strong>texto</strong>
        $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text);

        return $text;
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
        return $value === '' ? null : (filter_var($value, FILTER_VALIDATE_URL) ? $value : null);
    }

    private function validateImagePath($value)
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return preg_match('#/uploads/[A-Za-z0-9_.-]+\.(?:jpe?g|png|gif|webp)$#iD', $value) ? UploadPath::normalize($value) : null;
    }

    private function validateAgendaPayload(array $data, bool $requireRequiredFields = true)
    {
        $result = [];
        $result['titulo'] = $this->sanitizeText($data['titulo'] ?? '', 200);
        $result['fecha_evento'] = $this->validateDate($data['fecha_evento'] ?? '');
        $rawEndDate = $data['fecha_fin'] ?? '';
        if ($rawEndDate === null) {
            $rawEndDate = '';
        }
        $result['fecha_fin'] = $this->validateDate($rawEndDate);
        if (!is_string($rawEndDate)
            || (trim($rawEndDate) !== '' && (
                $result['fecha_fin'] === null ||
                ($result['fecha_evento'] !== null && $result['fecha_fin'] < $result['fecha_evento'])
            ))
        ) {
            return null;
        }
        $result['hora_evento'] = $this->sanitizeText($data['hora_evento'] ?? '', 20);
        $result['lugar'] = $this->sanitizeText($data['lugar'] ?? '', 200);

        $result['descripcion'] = HtmlSanitizer::clean($data['descripcion'] ?? null, 20000);

        $result['link_inscripcion'] = $this->validateUrl($data['link_inscripcion'] ?? '');
        $result['categoria'] = $this->sanitizeText($data['categoria'] ?? '', 100);
        $result['estado'] = $this->sanitizeText($data['estado'] ?? '', 50);
        $result['imagen'] = $this->validateImagePath($data['imagen'] ?? '');

        if ($requireRequiredFields && (
            $result['titulo'] === null ||
            $result['fecha_evento'] === null ||
            $result['hora_evento'] === null ||
            $result['lugar'] === null ||
            $result['link_inscripcion'] === null
        )) {
            return null;
        }

        return $result;
    }

    public function createAgenda()
    {
        $data = $this->getPayload();
        if (!empty($data['_imagen_error'])) {
            http_response_code(400);
            echo json_encode(['message' => 'La imagen debe ser JPG, PNG, GIF o WEBP y pesar máximo 2 MB.']);
            return;
        }
        $validated = $this->validateAgendaPayload($data, true);

        if ($validated === null) {
            http_response_code(400);
            echo json_encode(['message' => 'Datos inválidos o incompletos.']);
            return;
        }

        $this->agenda->titulo = $validated['titulo'];
        $this->agenda->fecha_evento = $validated['fecha_evento'];
        $this->agenda->fecha_fin = $validated['fecha_fin'];
        $this->agenda->hora_evento = $validated['hora_evento'];
        $this->agenda->lugar = $validated['lugar'];
        $this->agenda->descripcion = $validated['descripcion'] ?? null;
        $this->agenda->link_inscripcion = $validated['link_inscripcion'];
        $this->agenda->categoria = $validated['categoria'];
        $this->agenda->estado = $validated['estado'] ?? 'proximo';
        $this->agenda->imagen = $validated['imagen'];

        if ($this->agenda->create()) {
            http_response_code(201);
            echo json_encode(['message' => 'Evento de agenda creado exitosamente.']);
            return;
        }

        http_response_code(503);
        echo json_encode(['message' => 'No se pudo crear el evento de agenda.']);
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

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                $payload['_imagen_error'] = true;
            } else {
                $imagePath = $this->storeUploadedImage($_FILES['imagen']);
                if ($imagePath === null) {
                    $payload['_imagen_error'] = true;
                } else {
                    $payload['imagen'] = $imagePath;
                }
            }
        }

        return $payload;
    }

    private function storeUploadedImage(array $file)
    {
        if (empty($file['tmp_name']) || empty($file['name']) || !isset($file['size']) || $file['size'] > 2 * 1024 * 1024) {
            return null;
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fileInfo->file($file['tmp_name']);
        $allowedExtensions = array(
            'image/jpeg' => array('jpg', 'jpeg'),
            'image/png' => array('png'),
            'image/gif' => array('gif'),
            'image/webp' => array('webp'),
        );
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset($allowedExtensions[$mime]) || !in_array($extension, $allowedExtensions[$mime], true)) {
            return null;
        }

        $uploadDirectory = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
            return null;
        }

        $fileName = 'event_' . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . '/' . $fileName)) {
            return null;
        }

        return UploadPath::fileUrl($fileName);
    }

    public function readAgenda()
    {
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : null;
        $query = 'SELECT * FROM eventos ORDER BY id DESC';

        if ($limit !== null && $limit > 0) {
            $query .= ' LIMIT :limit';
        }

        $stmt = $this->db->prepare($query);

        if ($limit !== null && $limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();

        $agenda_arr = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $imagen = $row['imagen'] ?? null;
            if (UploadPath::isManagedPath($imagen)) {
                $imagen = UploadPath::normalize($imagen);
            }
            $agenda_arr[] = [
                'id' => $row['id'],
                'titulo' => $row['titulo'],
                'fecha_evento' => $row['fecha_evento'],
                'fecha_fin' => $row['fecha_fin'] ?? null,
                'hora_evento' => $row['hora_evento'],
                'lugar' => $row['lugar'],
                'descripcion' => $row['descripcion'],
                'link_inscripcion' => $row['link_inscripcion'],
                'categoria' => $row['categoria'],
                'estado' => $row['estado'],
                'imagen' => $imagen,
            ];
        }

        http_response_code(200);
        echo json_encode(['data' => $agenda_arr]);
    }

    public function readAgendaItem(int $id)
    {
        $query = 'SELECT * FROM eventos WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $imagen = $row['imagen'] ?? null;
            if (UploadPath::isManagedPath($imagen)) {
                $imagen = UploadPath::normalize($imagen);
            }
            http_response_code(200);
            echo json_encode(['data' => [
                'id' => $row['id'],
                'titulo' => $row['titulo'],
                'fecha_evento' => $row['fecha_evento'],
                'fecha_fin' => $row['fecha_fin'] ?? null,
                'hora_evento' => $row['hora_evento'],
                'lugar' => $row['lugar'],
                'descripcion' => $row['descripcion'],
                'link_inscripcion' => $row['link_inscripcion'],
                'categoria' => $row['categoria'],
                'estado' => $row['estado'],
                'imagen' => $imagen,
            ]]);
            return;
        }

        http_response_code(404);
        echo json_encode(['message' => 'Evento no encontrado.']);
    }

    public function updateAgenda(int $id)
    {
        $data = $this->getPayload();
        if (!empty($data['_imagen_error'])) {
            http_response_code(400);
            echo json_encode(['message' => 'La imagen debe ser JPG, PNG, GIF o WEBP y pesar máximo 2 MB.']);
            return;
        }
        $validated = $this->validateAgendaPayload($data, false);

        if (empty($id) || $validated === null || count(array_filter($validated, fn($v) => $v !== null || $v === 0)) === 0) {
            http_response_code(400);
            echo json_encode(['message' => 'ID o datos no proporcionados.']);
            return;
        }

        $existing = $this->getAgendaById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['message' => 'Evento no encontrado.']);
            return;
        }

        $effectiveStartDate = $validated['fecha_evento'] ?? $existing['fecha_evento'];
        $endDateWasProvided = array_key_exists('fecha_fin', $data);
        $effectiveEndDate = $endDateWasProvided ? $validated['fecha_fin'] : ($existing['fecha_fin'] ?? null);
        if ($effectiveEndDate !== null && $effectiveEndDate < $effectiveStartDate) {
            http_response_code(400);
            echo json_encode(['message' => 'La fecha de fin no puede ser anterior a la fecha de inicio.']);
            return;
        }

        $this->agenda->id = $id;
        $this->agenda->titulo = $validated['titulo'] ?? $existing['titulo'];
        $this->agenda->fecha_evento = $validated['fecha_evento'] ?? $existing['fecha_evento'];
        $this->agenda->fecha_fin = $effectiveEndDate;
        $this->agenda->hora_evento = $validated['hora_evento'] ?? $existing['hora_evento'];
        $this->agenda->lugar = $validated['lugar'] ?? $existing['lugar'];
        $this->agenda->descripcion = $validated['descripcion'] ?? $existing['descripcion'];
        $this->agenda->link_inscripcion = $validated['link_inscripcion'] ?? $existing['link_inscripcion'];
        $this->agenda->categoria = $validated['categoria'] ?? $existing['categoria'];
        $this->agenda->estado = $validated['estado'] ?? $existing['estado'];
        $this->agenda->imagen = $validated['imagen'] ?? $existing['imagen'];

        if ($this->agenda->update()) {
            if (($existing['imagen'] ?? null) !== $this->agenda->imagen) {
                UploadCleaner::deleteIfUnused($this->db, $existing['imagen'] ?? null);
            }
            http_response_code(200);
            echo json_encode(['message' => 'Evento de agenda actualizado exitosamente.']);
            return;
        }

        http_response_code(503);
        echo json_encode(['message' => 'No se pudo actualizar el evento de agenda.']);
    }

    private function getAgendaById(int $id)
    {
        $query = 'SELECT * FROM eventos WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteAgenda(int $id)
    {
        $existing = $this->getAgendaById($id);
        $this->agenda->id = $id;

        if ($this->agenda->delete()) {
            if ($existing) {
                UploadCleaner::deleteIfUnused($this->db, $existing['imagen'] ?? null);
            }
            http_response_code(200);
            echo json_encode(['message' => 'Evento de agenda eliminado exitosamente.']);
            return;
        }

        http_response_code(503);
        echo json_encode(['message' => 'No se pudo eliminar el evento de agenda.']);
    }
}