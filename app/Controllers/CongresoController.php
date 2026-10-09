<?php

require_once __DIR__ . '/../Models/Congreso.php';
require_once __DIR__ . '/../Helpers/HtmlSanitizer.php';
require_once __DIR__ . '/../Helpers/UploadPath.php';
require_once __DIR__ . '/../../config/database.php';

class CongresoController
{
    private Congreso $congreso;

    public function __construct()
    {
        $database = new Database();
        $connection = $database->getConnection();
        if (!$connection instanceof PDO) {
            throw new RuntimeException('No se pudo conectar con la base de datos.');
        }
        $this->congreso = new Congreso($connection);
    }

    public function read(): void
    {
        $id = $this->requestedId();
        $role = $_SESSION['user']['role'] ?? '';
        $content = $this->congreso->read($id, in_array($role, array('admin', 'editor'), true));
        if ($content === null) {
            http_response_code(404);
            echo json_encode(array('error' => 'No se encontró el congreso solicitado.'));
            return;
        }
        echo json_encode(array('data' => array($content)), JSON_UNESCAPED_UNICODE);
    }

    public function readSummary(): void
    {
        $summaries = array();
        foreach ($this->congreso->readAll(true) as $content) {
            $description = trim((string) ($content['summary'] ?? ''));
            if ($description === '') {
                $description = trim((string) ($content['intro'] ?? ''));
            }
            if ($description === '') {
                $description = 'Conoce a nuestros ponentes, tarifas y opciones de inscripción.';
            }
            $summaries[] = array(
                'id' => $content['id'],
                'title' => $content['title'],
                'titulo' => $content['title'],
                'categoria' => 'Congreso',
                'subtitulo' => $content['subtitle'],
                'summary' => $description,
                'descripcion' => $content['intro'],
                'intro' => $content['intro'],
                'date' => $content['date'],
                'time' => $content['time'],
                'logo' => $content['logo'],
                'imagen' => $content['logo'],
                'publicado' => !empty($content['published']) ? 1 : 0,
                'link' => 'congreso?id=' . rawurlencode((string) $content['id']),
            );
        }
        echo json_encode(array('data' => $summaries), JSON_UNESCAPED_UNICODE);
    }

    public function createPublished(): void
    {
        $request = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($request) || json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(array('error' => 'El contenido enviado no es JSON válido.'));
            return;
        }

        $content = $this->validateContent($request);
        if ($content === null) {
            http_response_code(422);
            echo json_encode(array('error' => 'Revisa los campos del congreso, tarifas y ponentes.'));
            return;
        }

        $id = $this->congreso->createPublished($content);
        http_response_code(201);
        echo json_encode(array('data' => array('id' => $id), 'message' => 'Congreso creado y publicado.'));
    }

    public function delete(): void
    {
        $id = $this->requestedId();
        if ($id < 1) {
            http_response_code(400);
            echo json_encode(array('error' => 'El identificador del congreso no es válido.'));
            return;
        }
        if (!$this->congreso->delete($id)) {
            http_response_code(404);
            echo json_encode(array('error' => 'No se encontró el congreso solicitado.'));
            return;
        }
        echo json_encode(array('message' => 'El congreso se eliminó correctamente.'));
    }

    public function save(): void
    {
        $id = $this->requestedId();
        if ($id < 1) {
            http_response_code(400);
            echo json_encode(array('error' => 'El identificador del congreso no es válido.'));
            return;
        }
        $currentContent = $this->congreso->read($id, true);
        if ($currentContent === null) {
            http_response_code(404);
            echo json_encode(array('error' => 'No se encontró el congreso solicitado.'));
            return;
        }
        $request = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($request) || json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(array('error' => 'El contenido enviado no es JSON válido.'));
            return;
        }

        if (!array_key_exists('countries', $request)) {
            $request['countries'] = $currentContent['countries'] ?? array();
        }

        $content = $this->validateContent($request);
        if ($content === null) {
            http_response_code(422);
            echo json_encode(array('error' => 'Revisa los campos del congreso, tarifas y ponentes.'));
            return;
        }

        $this->congreso->save($id, $content);
        echo json_encode(array('message' => 'Información del congreso guardada.'));
    }

    public function saveSummary(): void
    {
        $input = $_POST;
        if (!$input) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $input = is_array($decoded) ? $decoded : array();
        }

        $id = $this->requestedId();
        if ($id < 1) {
            http_response_code(400);
            echo json_encode(array('error' => 'El identificador del congreso no es válido.'));
            return;
        }
        $content = $this->congreso->read($id);
        if ($content === null) {
            http_response_code(404);
            echo json_encode(array('error' => 'No se encontró el congreso solicitado.'));
            return;
        }
        $fields = array(
            'titulo' => array('title', 180),
            'subtitulo' => array('subtitle', 255),
            'descripcion' => array('intro', 1000),
            'summary' => array('summary', 1000),
            'time' => array('time', 5),
            'logo' => array('logo', 500),
        );

        foreach ($fields as $requestField => $definition) {
            if (!array_key_exists($requestField, $input)) {
                continue;
            }
            $value = $input[$requestField];
            if (!is_string($value) || mb_strlen(trim($value), 'UTF-8') > $definition[1]) {
                http_response_code(422);
                echo json_encode(array('error' => 'Revisa el título, presentación y logo del congreso.'));
                return;
            }
            $content[$definition[0]] = trim($value);
        }

        if ($content['time'] !== '' && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $content['time']) !== 1) {
            http_response_code(422);
            echo json_encode(array('error' => 'La hora del congreso no tiene un formato válido.'));
            return;
        }

        if ($content['title'] === '' || ($content['logo'] !== '' && !preg_match('#^(?:https?://|/|assets/)[^\s]+$#i', $content['logo']))) {
            http_response_code(422);
            echo json_encode(array('error' => 'El título o la ruta del logo no son válidos.'));
            return;
        }

        $this->congreso->save($id, $content);
        echo json_encode(array('message' => 'Datos de presentación del congreso guardados.'));
    }

    private function requestedId(): int
    {
        $rawId = $_GET['id'] ?? '1';
        if (!is_string($rawId) && !is_int($rawId)) {
            return 0;
        }
        $id = filter_var($rawId, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        return $id === false ? 0 : (int) $id;
    }

    public function uploadImage(): void
    {
        $maxFileSize = 5 * 1024 * 1024;
        $file = $_FILES['image'] ?? null;
        if (!is_array($file)) {
            http_response_code(400);
            echo json_encode(array('error' => 'No se recibió una imagen para subir.'));
            return;
        }
        $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            http_response_code(400);
            $message = in_array($uploadError, array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true)
                ? 'El servidor rechazó la imagen por superar su límite de carga. El máximo configurado para este sitio es 5 MB.'
                : 'No se pudo recibir la imagen. Vuelve a seleccionarla e inténtalo de nuevo.';
            echo json_encode(array('error' => $message));
            return;
        }
        if (!isset($file['tmp_name'], $file['name'], $file['size'])
            || !is_uploaded_file($file['tmp_name'])
            || !is_int($file['size'])
            || $file['size'] <= 0
            || $file['size'] > $maxFileSize
        ) {
            http_response_code(400);
            echo json_encode(array('error' => 'Selecciona una imagen JPG, PNG, GIF o WEBP de hasta 5 MB.'));
            return;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        );
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $imageInfo = getimagesize($file['tmp_name']);
        if (!isset($allowedTypes[$extension]) || $mime !== $allowedTypes[$extension]
            || !is_array($imageInfo) || ($imageInfo['mime'] ?? '') !== $mime
        ) {
            http_response_code(400);
            echo json_encode(array('error' => 'El archivo no es una imagen JPG, PNG, GIF o WEBP válida.'));
            return;
        }

        $uploadDirectory = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . DIRECTORY_SEPARATOR . $fileName)) {
            throw new RuntimeException('No se pudo guardar la imagen subida.');
        }

        echo json_encode(array('url' => UploadPath::fileUrl($fileName)));
    }

    private function validateContent(array $input): ?array
    {
        $stringFields = array(
            'title' => 180,
            'subtitle' => 255,
            'intro' => 1000,
            'summary' => 1000,
            'logo' => 500,
            'date' => 120,
            'time' => 5,
            'modality' => 120,
            'venue' => 255,
            'registration_url' => 1000,
            'registration_qr' => 500,
            'contact_email' => 254,
        );
        $content = array();
        foreach ($stringFields as $field => $maxLength) {
            $value = $input[$field] ?? '';
            if (!is_string($value) || mb_strlen(trim($value), 'UTF-8') > $maxLength) {
                return null;
            }
            $content[$field] = trim($value);
        }
        if ($content['title'] === '' || !filter_var($content['contact_email'], FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        if ($content['time'] !== '' && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $content['time']) !== 1) {
            return null;
        }
        if (!$this->isOptionalHttpUrl($content['registration_url'])) {
            return null;
        }
        if ($content['logo'] !== '' && !preg_match('#^(?:https?://|/|assets/)[^\s]+$#i', $content['logo'])) {
            return null;
        }
        if ($content['registration_qr'] !== '' && !preg_match('#^(?:https?://|/|assets/)[^\s]+$#i', $content['registration_qr'])) {
            return null;
        }

        $paymentInput = $input['payment'] ?? null;
        if (!is_array($paymentInput)) {
            return null;
        }
        $content['payment'] = array();
        foreach (array('bank' => 180, 'holder' => 180, 'account' => 80, 'cci' => 80, 'wallet_name' => 60, 'yape' => 80, 'yape_holder' => 180, 'note' => 1000) as $field => $maxLength) {
            $value = $paymentInput[$field] ?? '';
            if (!is_string($value) || mb_strlen(trim($value), 'UTF-8') > $maxLength) {
                return null;
            }
            $content['payment'][$field] = trim($value);
        }
        $yapeEnabled = $paymentInput['yape_enabled'] ?? true;
        if (!is_bool($yapeEnabled)) {
            return null;
        }
        $content['payment']['yape_enabled'] = $yapeEnabled;

        $certifications = $input['certifications'] ?? null;
        if (!is_array($certifications) || count($certifications) > 30) {
            return null;
        }
        $content['certifications'] = array();
        foreach ($certifications as $certification) {
            if (!is_string($certification) || mb_strlen(trim($certification), 'UTF-8') > 12000) {
                return null;
            }
            $cleanCertification = HtmlSanitizer::clean($certification, 12000);
            if ($cleanCertification !== null) {
                $content['certifications'][] = $cleanCertification;
            } elseif (!$this->isEmptyRichText($certification)) {
                return null;
            }
        }

        $sponsors = $input['sponsors'] ?? null;
        if (!is_array($sponsors) || count($sponsors) > 30) {
            return null;
        }
        $content['sponsors'] = array();
        foreach ($sponsors as $sponsor) {
            if (is_string($sponsor)) {
                $sponsor = array('name' => trim($sponsor), 'logo' => '', 'description' => '');
            }
            if (!is_array($sponsor)) {
                return null;
            }
            $name = $sponsor['name'] ?? '';
            $logo = $sponsor['logo'] ?? '';
            $description = $sponsor['description'] ?? '';
            if (!is_string($name) || mb_strlen(trim($name), 'UTF-8') > 180
                || !is_string($logo) || mb_strlen(trim($logo), 'UTF-8') > 500
                || !is_string($description) || mb_strlen(trim($description), 'UTF-8') > 12000
            ) {
                return null;
            }
            $name = trim($name);
            $logo = trim($logo);
            if ($logo !== '' && !preg_match('#^(?:https?://|/|assets/)[^\s]+$#i', $logo)) {
                return null;
            }
            $cleanDescription = HtmlSanitizer::clean($description, 12000);
            if ($cleanDescription === null && !$this->isEmptyRichText($description)) {
                return null;
            }
            if ($name !== '' || $logo !== '' || $cleanDescription !== null) {
                $content['sponsors'][] = array(
                    'name' => $name,
                    'logo' => $logo,
                    'description' => $cleanDescription ?? '',
                );
            }
        }

        $countries = $input['countries'] ?? array();
        if (!is_array($countries) || count($countries) > 100) {
            return null;
        }
        $content['countries'] = array();
        $countryKeys = array();
        foreach ($countries as $country) {
            if (!is_string($country) || mb_strlen(trim($country), 'UTF-8') > 120) {
                return null;
            }
            $country = trim($country);
            if ($country === '') {
                continue;
            }
            $countryKey = mb_strtolower($country, 'UTF-8');
            if (isset($countryKeys[$countryKey])) {
                continue;
            }
            $countryKeys[$countryKey] = true;
            $content['countries'][] = $country;
        }

        $fees = $input['fees'] ?? null;
        if (!is_array($fees) || count($fees) > 30) {
            return null;
        }
        $content['fees'] = array();
        foreach ($fees as $fee) {
            if (!is_array($fee) || !is_string($fee['audience'] ?? null) || !is_string($fee['amount'] ?? null)) {
                return null;
            }
            $audience = trim($fee['audience']);
            $amount = trim($fee['amount']);
            if ($audience === '' || mb_strlen($audience, 'UTF-8') > 180 || mb_strlen($amount, 'UTF-8') > 80) {
                return null;
            }
            $content['fees'][] = array('audience' => $audience, 'amount' => $amount);
        }

        $speakers = $input['speakers'] ?? null;
        if (!is_array($speakers) || count($speakers) > 100) {
            return null;
        }
        $content['speakers'] = array();
        foreach ($speakers as $speaker) {
            if (!is_array($speaker)) {
                return null;
            }
            foreach (array('name' => 180, 'country' => 120, 'flag' => 32, 'institution' => 255, 'photo' => 500, 'biography' => 30000) as $field => $maxLength) {
                $value = $speaker[$field] ?? '';
                if (!is_string($value) || mb_strlen(trim($value), 'UTF-8') > $maxLength) {
                    return null;
                }
                $speaker[$field] = trim($value);
            }
            $speaker['biography'] = HtmlSanitizer::clean($speaker['biography'], 30000, true) ?? '';
            if ($speaker['name'] === '' || $speaker['country'] === '') {
                return null;
            }
            if ($speaker['flag'] !== '' && !preg_match('/^[A-Z]{2}$/', $speaker['flag'])) {
                return null;
            }
            if ($speaker['photo'] !== '') {
                if (preg_match('/^[A-Za-z0-9_.-]+\.(?:jpe?g|png|gif|webp)$/i', $speaker['photo'])) {
                    $speaker['photo'] = 'assets/images/congreso/' . $speaker['photo'];
                } elseif (!preg_match('#^(?:https?://|/|assets/)[^\s]+$#i', $speaker['photo'])) {
                    return null;
                }
            }
            $links = $speaker['links'] ?? null;
            if (!is_array($links) || count($links) > 20) {
                return null;
            }
            $speaker['links'] = array();
            foreach ($links as $link) {
                if (!is_string($link) || !$this->isOptionalHttpUrl(trim($link))) {
                    return null;
                }
                if (trim($link) !== '') {
                    $speaker['links'][] = trim($link);
                }
            }
            $content['speakers'][] = $speaker;
        }

        return $content;
    }

    private function isEmptyRichText(string $html): bool
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);
        return trim($text) === '';
    }

    private function isOptionalHttpUrl(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        return filter_var($value, FILTER_VALIDATE_URL) && in_array($scheme, array('http', 'https'), true);
    }
}
