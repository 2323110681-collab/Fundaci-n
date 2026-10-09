<?php

require_once __DIR__ . '/../Models/Popup.php';
require_once __DIR__ . '/../Helpers/UploadPath.php';
require_once __DIR__ . '/../../config/database.php';

class PopupController
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    private Popup $popup;

    public function __construct()
    {
        $database = new Database();
        $connection = $database->getConnection();
        if (!$connection instanceof PDO) {
            throw new RuntimeException('No se pudo conectar con la base de datos.');
        }
        $this->popup = new Popup($connection);
    }

    public function readPublic(): ?array
    {
        $configuration = $this->popup->read();
        if ($configuration === null
            || (int) $configuration['active'] !== 1
            || $configuration['starts_on'] > date('Y-m-d')
            || $configuration['ends_on'] < date('Y-m-d')
            || trim($configuration['image_path']) === ''
        ) {
            return null;
        }

        return array(
            'image' => UploadPath::normalize($configuration['image_path']),
            'link' => $configuration['link_url'],
            'displaySeconds' => (int) $configuration['display_seconds'],
            'endsOn' => $configuration['ends_on'],
        );
    }

    public function readAdmin(): array
    {
        $configuration = $this->popup->read();
        return $configuration ?? array(
            'image_path' => '',
            'link_url' => '',
            'starts_on' => date('Y-m-d'),
            'ends_on' => date('Y-m-d', strtotime('+7 days')),
            'display_seconds' => 0,
            'active' => 0,
        );
    }

    public function saveAdmin(array $input, ?array $file): void
    {
        $current = $this->readAdmin();
        $imagePath = (string) $current['image_path'];
        $newImagePath = null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $imagePath = $this->uploadImage($file);
            $newImagePath = $imagePath;
        }

        $active = isset($input['active']) && $input['active'] === '1' ? 1 : 0;
        $linkValue = $input['link_url'] ?? '';
        $startsValue = $input['starts_on'] ?? '';
        $endsValue = $input['ends_on'] ?? '';
        $secondsValue = $input['display_seconds'] ?? '';
        if (!is_string($linkValue) || !is_string($startsValue) || !is_string($endsValue)
            || (!is_string($secondsValue) && !is_int($secondsValue))
        ) {
            $this->discardNewImage($newImagePath);
            throw new InvalidArgumentException('Revisa los datos del aviso e inténtalo de nuevo.');
        }
        $link = trim($linkValue);
        $startsOn = trim($startsValue);
        $endsOn = trim($endsValue);
        $seconds = filter_var($secondsValue, FILTER_VALIDATE_INT);

        if ($active === 1 && $imagePath === '') {
            $this->discardNewImage($newImagePath);
            throw new InvalidArgumentException('Sube una imagen antes de activar el aviso.');
        }
        if ($link !== '' && (!$this->isHttpUrl($link) || strlen($link) > 1000)) {
            $this->discardNewImage($newImagePath);
            throw new InvalidArgumentException('El enlace debe ser una dirección web válida (https:// o http://).');
        }
        if (!$this->isDate($startsOn) || !$this->isDate($endsOn) || $startsOn > $endsOn) {
            $this->discardNewImage($newImagePath);
            throw new InvalidArgumentException('Indica un período válido: la fecha de inicio debe ser anterior o igual a la fecha de fin.');
        }
        if ($seconds === false || $seconds < 0 || $seconds > 120) {
            $this->discardNewImage($newImagePath);
            throw new InvalidArgumentException('El cierre automático debe estar entre 0 y 120 segundos.');
        }

        try {
            $this->popup->save(array(
                'image_path' => $imagePath,
                'link_url' => $link,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'display_seconds' => $seconds,
                'active' => $active,
            ));
        } catch (Throwable $exception) {
            $this->discardNewImage($newImagePath);
            throw $exception;
        }

        if ($newImagePath !== null && $current['image_path'] !== $newImagePath) {
            $oldFile = UploadPath::diskPath((string) $current['image_path']);
            if ($oldFile !== null && !unlink($oldFile)) {
                error_log('No se pudo eliminar la imagen anterior del aviso emergente: ' . $oldFile);
            }
        }
    }

    private function uploadImage(array $file): string
    {
        $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(
                in_array($uploadError, array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true)
                    ? 'La imagen supera el límite de carga del servidor (máximo 5 MB).'
                    : 'No se pudo recibir la imagen. Vuelve a seleccionarla e inténtalo de nuevo.'
            );
        }
        if (!isset($file['tmp_name'], $file['name'], $file['size'])
            || !is_uploaded_file($file['tmp_name'])
            || !is_int($file['size'])
            || $file['size'] <= 0
            || $file['size'] > self::MAX_FILE_SIZE
        ) {
            throw new InvalidArgumentException('Selecciona una imagen JPG, PNG, GIF o WEBP de hasta 5 MB.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        );
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $imageInfo = getimagesize($file['tmp_name']);
        if (!isset($allowedTypes[$extension]) || $mime !== $allowedTypes[$extension]
            || !is_array($imageInfo) || ($imageInfo['mime'] ?? '') !== $mime
        ) {
            throw new InvalidArgumentException('El archivo no es una imagen JPG, PNG, GIF o WEBP válida.');
        }

        $uploadDirectory = dirname(__DIR__, 2) . '/public/uploads';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
        }
        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . DIRECTORY_SEPARATOR . $fileName)) {
            throw new RuntimeException('No se pudo guardar la imagen subida.');
        }

        return UploadPath::fileUrl($fileName);
    }

    private function discardNewImage(?string $imagePath): void
    {
        if ($imagePath === null) {
            return;
        }
        $file = UploadPath::diskPath($imagePath);
        if ($file !== null && !unlink($file)) {
            error_log('No se pudo limpiar la imagen nueva del aviso emergente: ' . $file);
        }
    }

    private function isDate(string $value): bool
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        $errors = DateTime::getLastErrors();
        return $date !== false
            && $date->format('Y-m-d') === $value
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    private function isHttpUrl(string $value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        return in_array($scheme, array('http', 'https'), true);
    }
}
