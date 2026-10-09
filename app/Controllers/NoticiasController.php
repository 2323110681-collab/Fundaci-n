<?php

require_once __DIR__ . '/../Models/Noticias.php';
require_once __DIR__ . '/../Helpers/HtmlSanitizer.php';
require_once __DIR__ . '/../Helpers/UploadPath.php';
require_once __DIR__ . '/../Helpers/UploadCleaner.php';
require_once __DIR__ . '/../../config/database.php';

class NoticiasController
{
    private $db;
    private $noticias;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->noticias = new Noticias($this->db);
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

    private function validateNoticiaPayload(array $data, bool $requireTitleCategory = true)
    {
        $result = [];
        $titulo = $this->sanitizeText($data['titulo'] ?? '', 200);
        $categoria = $this->sanitizeText($data['categoria'] ?? '', 100);
        if ($requireTitleCategory && $categoria === null) {
            $categoria = 'General';
        }

        if ($titulo !== null) {
            $result['titulo'] = $titulo;
        }
        if ($categoria !== null) {
            $result['categoria'] = $categoria;
        }
        $result['autor'] = $this->sanitizeText($data['autor'] ?? '', 180);
        if ($requireTitleCategory && ($titulo === null || $categoria === null)) {
            return null;
        }

        $result['imagen'] = $data['imagen'] ?? null;
        $result['fecha_evento'] = $this->validateDate($data['fecha_evento'] ?? '');
        $result['descripcion_corta'] = $this->sanitizeText($data['descripcion_corta'] ?? '', 255);
        $result['contenido'] = HtmlSanitizer::clean($data['contenido'] ?? null, 30000);
        $result['link'] = $this->validateUrl($data['link'] ?? '');
        if (array_key_exists('es_destacada', $data)) {
            $result['es_destacada'] = in_array($data['es_destacada'], [1, '1', true, 'true', 'on'], true) ? 1 : 0;
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

    public function createNoticia()
    {
        $data = $this->getPayload();
        if (!empty($data['_imagen_error'])) {
            http_response_code(400);
            echo json_encode(['message' => 'La imagen debe ser JPG, PNG, GIF o WEBP y pesar máximo 2 MB.']);
            return;
        }
        $validated = $this->validateNoticiaPayload($data, true);

        if ($validated === null) {
            http_response_code(400);
            echo json_encode(['message' => 'Datos inválidos o incompletos.']);
            return;
        }

        $this->noticias->titulo = $validated['titulo'];
        $this->noticias->categoria = $validated['categoria'];
        $this->noticias->autor = $validated['autor'];
        $this->noticias->imagen = $validated['imagen'] ?? null;
        $this->noticias->fecha_evento = $validated['fecha_evento'] ?? null;
        $this->noticias->descripcion_corta = $validated['descripcion_corta'] ?? null;
        $this->noticias->contenido = $validated['contenido'] ?? null;
        $this->noticias->link = $validated['link'] ?? null;
        $this->noticias->es_destacada = $validated['es_destacada'] ?? 0;
        $this->noticias->updated_at = date('Y-m-d H:i:s');

        if ($this->noticias->create()) {
            http_response_code(201);
            echo json_encode(['message' => 'Noticia creada exitosamente.']);
            return;
        }

        http_response_code(503);
        echo json_encode(['message' => 'No se pudo crear la noticia.']);
    }

    public function readNoticias()
    {
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : null;
        $query = 'SELECT * FROM noticias ORDER BY id DESC';

        if ($limit !== null && $limit > 0) {
            $query .= ' LIMIT :limit';
        }

        $stmt = $this->db->prepare($query);

        if ($limit !== null && $limit > 0) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();

        $noticias_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $imagen = $row['imagen'] ?? null;
            if (UploadPath::isManagedPath($imagen)) {
                $imagen = UploadPath::normalize($imagen);
            }
            $noticias_arr[] = [
                'id' => $row['id'],
                'titulo' => $row['titulo'],
                'categoria' => $row['categoria'],
                'autor' => $row['autor'] ?? null,
                'imagen' => $imagen,
                'fecha_evento' => $row['fecha_evento'],
                'descripcion_corta' => $row['descripcion_corta'],
                'contenido' => $row['contenido'],
                'link' => $row['link'],
                'es_destacada' => (int) ($row['es_destacada'] ?? 0),
                'updated_at' => $row['updated_at'],
            ];
        }

        http_response_code(200);
        echo json_encode(['data' => $noticias_arr]);
    }

    public function readNoticia(int $id)
    {
        $query = 'SELECT * FROM noticias WHERE id = :id';
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
                'categoria' => $row['categoria'],
                'autor' => $row['autor'] ?? null,
                'imagen' => $imagen,
                'fecha_evento' => $row['fecha_evento'],
                'descripcion_corta' => $row['descripcion_corta'],
                'contenido' => $row['contenido'],
                'link' => $row['link'],
                'es_destacada' => (int) ($row['es_destacada'] ?? 0),
                'updated_at' => $row['updated_at'],
            ]]);
            return;
        }

        http_response_code(404);
        echo json_encode(['message' => 'Noticia no encontrada.']);
    }

    public function updateNoticia(int $id)
    {
        $data = $this->getPayload();
        if (!empty($data['_imagen_error'])) {
            http_response_code(400);
            echo json_encode(['message' => 'La imagen debe ser JPG, PNG, GIF o WEBP y pesar máximo 2 MB.']);
            return;
        }
        $validated = $this->validateNoticiaPayload($data, false);

        if (empty($id) || $validated === null || count(array_filter($validated, fn($v) => $v !== null || $v === 0)) === 0) {
            http_response_code(400);
            echo json_encode(['message' => 'ID o datos no proporcionados.']);
            return;
        }

        $existing = $this->getNoticiaById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['message' => 'Noticia no encontrada.']);
            return;
        }

        $this->noticias->id = $id;
        $this->noticias->titulo = $validated['titulo'] ?? $existing['titulo'];
        $this->noticias->categoria = $validated['categoria'] ?? $existing['categoria'];
        $this->noticias->autor = $validated['autor'] ?? ($existing['autor'] ?? null);
        $newImage = $validated['imagen'] ?? null;
        $this->noticias->imagen = ($newImage !== null && $newImage !== '') ? $newImage : $existing['imagen'];
        $this->noticias->fecha_evento = $validated['fecha_evento'] ?? $existing['fecha_evento'];
        $this->noticias->descripcion_corta = $validated['descripcion_corta'] ?? $existing['descripcion_corta'];
        $this->noticias->contenido = $validated['contenido'] ?? $existing['contenido'];
        $this->noticias->link = $validated['link'] ?? $existing['link'];
        $this->noticias->es_destacada = array_key_exists('es_destacada', $validated) ? $validated['es_destacada'] : $existing['es_destacada'];
        $this->noticias->updated_at = date('Y-m-d H:i:s');

        if ($this->noticias->update()) {
            if (($existing['imagen'] ?? null) !== $this->noticias->imagen) {
                UploadCleaner::deleteIfUnused($this->db, $existing['imagen'] ?? null);
            }
            http_response_code(200);
            echo json_encode(['message' => 'Noticia actualizada exitosamente.']);
            return;
        }

        http_response_code(503);
        echo json_encode(['message' => 'No se pudo actualizar la noticia.']);
    }

    private function getNoticiaById(int $id)
    {
        $query = 'SELECT * FROM noticias WHERE id = :id';
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteNoticia(int $id)
    {
        $existing = $this->getNoticiaById($id);
        $this->noticias->id = $id;

        if ($this->noticias->delete()) {
            if ($existing) {
                UploadCleaner::deleteIfUnused($this->db, $existing['imagen'] ?? null);
            }
            http_response_code(200);
            echo json_encode(['message' => 'Noticia eliminada exitosamente.']);
            return;
        }

        http_response_code(503);
        echo json_encode(['message' => 'No se pudo eliminar la noticia.']);
    }
}
