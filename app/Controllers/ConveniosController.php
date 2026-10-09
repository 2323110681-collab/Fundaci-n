<?php

require_once __DIR__ . '/../Models/Convenios.php';
require_once __DIR__ . '/../Helpers/UploadPath.php';
require_once __DIR__ . '/../Helpers/UploadCleaner.php';
require_once __DIR__ . '/../../config/database.php';

class ConveniosController
{
    private $db;
    private $convenios;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->convenios = new Convenios($this->db);
    }

    private function text($value, int $max): ?string
    {
        if (!is_string($value)) return null;
        $value = trim($value);
        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function number($value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function payload(): array
    {
        $data = $_POST;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $data['imagen'] = $this->storeImage($_FILES['imagen']);
        }
        return $data;
    }

    private function coordinatesFromUrl(string $url): array
    {
        $number = '(-?\d+(?:\.\d+)?)';
        $patterns = [
            '/!3d' . $number . '!4d' . $number . '/',
            '/@' . $number . ',' . $number . '/',
            '/(?:q|query|ll|destination)=' . $number . '(?:,|%2C)\+?' . $number . '/i',
            '#/(?:place|search|dir)/' . $number . ',\+?' . $number . '(?:[/?@,]|$)#',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return [(float) $matches[1], (float) $matches[2]];
            }
        }
        return [null, null];
    }

    /** Los enlaces cortos de Google Maps no traen coordenadas: se sigue la redirección para obtener el enlace largo. */
    private function resolveShortMapsUrl(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($scheme !== 'https' || !in_array($host, ['maps.app.goo.gl', 'goo.gl'], true) || !function_exists('curl_init')) {
            return null;
        }
        $curl = curl_init($url);
        @curl_setopt_array($curl, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FundacionDU/1.0)',
        ]);
        curl_exec($curl);
        $finalUrl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        return is_string($finalUrl) && $finalUrl !== '' ? $finalUrl : null;
    }

    private function extractCoordinates(?string $location): array
    {
        if (!$location) return [null, null];
        [$latitude, $longitude] = $this->coordinatesFromUrl($location);
        if ($latitude !== null) return [$latitude, $longitude];
        $resolved = $this->resolveShortMapsUrl($location);
        return $resolved ? $this->coordinatesFromUrl($resolved) : [null, null];
    }

    private function storeImage(array $file): ?string
    {
        if ($file['size'] > 2 * 1024 * 1024 || empty($file['tmp_name'])) return null;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) return null;
        $dir = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $name = uniqid('convenio_', true) . '.' . $allowed[$mime];
        return move_uploaded_file($file['tmp_name'], $dir . '/' . $name) ? UploadPath::fileUrl($name) : null;
    }

    /** Devuelve [datos, mensaje de error]. */
    private function validate(array $data, bool $required = true): array
    {
        $location = $this->text($data['ubicacion_google'] ?? '', 500);
        if ($location !== null && !filter_var($location, FILTER_VALIDATE_URL)) {
            return [null, 'La ubicación de Google Maps debe ser un enlace válido (https://...).'];
        }
        [$latitude, $longitude] = $this->extractCoordinates($location);
        $item = [
            'institucion' => $this->text($data['institucion'] ?? '', 180),
            'pais' => $this->text($data['pais'] ?? '', 100),
            'ciudad' => $this->text($data['ciudad'] ?? '', 120),
            'latitud' => $latitude,
            'longitud' => $longitude,
            'ubicacion_google' => $location,
            'tipo' => $this->text($data['tipo'] ?? '', 120),
            'descripcion' => $this->text($data['descripcion'] ?? '', 2000),
            'imagen' => array_key_exists('imagen', $data) ? $data['imagen'] : null,
        ];
        if ($required && !$item['institucion']) return [null, 'La institución es obligatoria.'];
        if ($required && !$item['pais']) return [null, 'El país es obligatorio.'];
        if ($item['latitud'] !== null && ($item['latitud'] < -90 || $item['latitud'] > 90)) return [null, 'La latitud del enlace de Google Maps está fuera de rango.'];
        if ($item['longitud'] !== null && ($item['longitud'] < -180 || $item['longitud'] > 180)) return [null, 'La longitud del enlace de Google Maps está fuera de rango.'];
        return [$item, null];
    }

    private function coordinatesWarning(array $item): ?string
    {
        if ($item['ubicacion_google'] !== null && $item['latitud'] === null) {
            return 'El convenio se guardó, pero no se pudieron obtener las coordenadas de ese enlace. Abre el enlace en tu navegador, copia la dirección larga de la barra (la que contiene @latitud,longitud) y pégala en el campo de ubicación.';
        }
        return null;
    }

    public function readConvenios(): void
    {
        $rows = $this->convenios->readAll();
        foreach ($rows as &$row) {
            if (isset($row['imagen']) && UploadPath::isManagedPath($row['imagen'])) {
                $row['imagen'] = UploadPath::normalize($row['imagen']);
            }
        }
        unset($row);
        echo json_encode(['data' => $rows]);
    }

    public function readConvenio(int $id): void
    {
        $row = $this->convenios->readOne($id);
        if (!$row) { http_response_code(404); echo json_encode(['message' => 'Convenio no encontrado.']); return; }
        if (isset($row['imagen']) && UploadPath::isManagedPath($row['imagen'])) {
            $row['imagen'] = UploadPath::normalize($row['imagen']);
        }
        echo json_encode(['data' => $row]);
    }

    public function createConvenio(): void
    {
        [$item, $error] = $this->validate($this->payload());
        if (!$item) { http_response_code(400); echo json_encode(['message' => $error ?: 'Datos de convenio inválidos.']); return; }
        $this->assign($item);
        $this->convenios->create();
        http_response_code(201);
        $response = ['message' => 'Convenio creado correctamente.'];
        if ($warning = $this->coordinatesWarning($item)) $response['warning'] = $warning;
        echo json_encode($response);
    }

    public function updateConvenio(int $id): void
    {
        $existing = $this->convenios->readOne($id);
        [$item, $error] = $this->validate($this->payload(), false);
        if (!$existing) { http_response_code(404); echo json_encode(['message' => 'Convenio no encontrado.']); return; }
        if (!$item) { http_response_code(400); echo json_encode(['message' => $error ?: 'Datos de convenio inválidos.']); return; }
        // Si cambió el enlace de ubicación, no se conservan las coordenadas del enlace anterior.
        $locationChanged = $item['ubicacion_google'] !== null && $item['ubicacion_google'] !== $existing['ubicacion_google'];
        foreach (['institucion', 'pais', 'ciudad', 'latitud', 'longitud', 'ubicacion_google', 'tipo', 'descripcion'] as $key) {
            if ($locationChanged && in_array($key, ['latitud', 'longitud'], true)) continue;
            $item[$key] = $item[$key] ?? $existing[$key];
        }
        $item['imagen'] = $item['imagen'] ?: $existing['imagen'];
        $this->convenios->id = $id;
        $this->assign($item);
        $this->convenios->update();
        if (($existing['imagen'] ?? null) !== $item['imagen']) {
            UploadCleaner::deleteIfUnused($this->db, $existing['imagen'] ?? null);
        }
        $response = ['message' => 'Convenio actualizado correctamente.'];
        if ($locationChanged && ($warning = $this->coordinatesWarning($item))) $response['warning'] = $warning;
        echo json_encode($response);
    }

    public function deleteConvenio(int $id): void
    {
        $existing = $this->convenios->readOne($id);
        $this->convenios->id = $id;
        $this->convenios->delete();
        if ($existing) {
            UploadCleaner::deleteIfUnused($this->db, $existing['imagen'] ?? null);
        }
        echo json_encode(['message' => 'Convenio eliminado correctamente.']);
    }

    private function assign(array $item): void
    {
        foreach ($item as $key => $value) $this->convenios->{$key} = $value;
    }
}
