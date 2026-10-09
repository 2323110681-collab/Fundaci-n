<?php

declare(strict_types=1);

$localMailConfigPath = __DIR__ . '/mail.local.php';
$localMailConfig = is_file($localMailConfigPath) ? require $localMailConfigPath : array();
if (!is_array($localMailConfig)) {
    $localMailConfig = array();
}

$destinationEmail = mailSetting('CONTACT_DESTINATION_EMAIL', $localMailConfig, 'informes@fundaciondu.org');
$senderEmail = mailSetting('CONTACT_SENDER_EMAIL', $localMailConfig, 'informes@fundaciondu.org');

if (!filter_var($destinationEmail, FILTER_VALIDATE_EMAIL) || !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
    error_log('FDU correo: CONTACT_DESTINATION_EMAIL o CONTACT_SENDER_EMAIL no es una direccion valida.');
    $destinationEmail = 'informes@fundaciondu.org';
    $senderEmail = 'informes@fundaciondu.org';
}

if (!defined('CONTACT_DESTINATION_EMAIL')) {
    define('CONTACT_DESTINATION_EMAIL', $destinationEmail);
}
if (!defined('CONTACT_SENDER_EMAIL')) {
    define('CONTACT_SENDER_EMAIL', $senderEmail);
}
function mailSetting(string $name, array $localMailConfig, $default = ''): string
{
    $value = getenv($name);
    if (is_string($value) && trim($value) !== '') {
        return trim($value);
    }

    $value = $localMailConfig[$name] ?? $default;
    return is_string($value) ? trim($value) : (string) $value;
}

function sendContactMail(string $to, string $subject, string $body, string $replyTo = CONTACT_SENDER_EMAIL): bool
{
    $to = trim($to);
    $replyTo = trim($replyTo);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        error_log('FDU correo: destinatario o Reply-To invalido.');
        return false;
    }

    if (!function_exists('mail')) {
        error_log('FDU correo: la funcion mail() no esta disponible en PHP.');
        return false;
    }

    $headers = array(
        'From: ' . CONTACT_SENDER_EMAIL,
        'Reply-To: ' . $replyTo,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    );
    $sent = mail($to, $subject, $body, implode("\r\n", $headers));
    if (!$sent) {
        error_log('FDU correo: mail() no pudo aceptar la notificacion.');
        return false;
    }

    return true;
}

function sendContactNotification(string $nombre, string $correo, string $telefono, string $asunto, string $mensaje): bool
{
    $subject = 'Nueva consulta desde el formulario de contacto';
    $body = implode("\r\n", array(
        'Se recibio una nueva consulta desde la pagina web.',
        '',
        'Nombre: ' . $nombre,
        'Correo: ' . $correo,
        'Telefono: ' . ($telefono !== '' ? $telefono : 'No indicado'),
        'Asunto: ' . ($asunto !== '' ? $asunto : 'No indicado'),
        '',
        'Mensaje:',
        $mensaje,
    ));

    return sendContactMail(CONTACT_DESTINATION_EMAIL, $subject, $body, $correo);
}
