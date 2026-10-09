<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
Autoloader::register();
require_once __DIR__ . '/../app/Helpers/OriginGuard.php';

$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params(array(
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secureCookie,
));
session_start();

if (!isset($_SESSION['user'])) {
    session_write_close();
    header('Location: login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    session_write_close();
    header('Location: admin?section=popup', true, 303);
    exit;
}

$notice = array('type' => 'success', 'message' => 'La configuración del aviso emergente se guardó correctamente.');
$origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? null);
$submittedToken = $_POST['csrf_token'] ?? '';
if (!OriginGuard::isSameOrigin($origin, $_SERVER['HTTP_HOST'] ?? '', $secureCookie)) {
    http_response_code(403);
    $notice = array('type' => 'error', 'message' => 'La solicitud fue bloqueada por seguridad. Recarga la página e inténtalo de nuevo.');
} elseif (!is_string($submittedToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    http_response_code(403);
    $notice = array('type' => 'error', 'message' => 'La página quedó desactualizada. Recárgala e inténtalo de nuevo.');
} else {
    try {
        $controller = new PopupController();
        $controller->saveAdmin($_POST, $_FILES['image'] ?? null);
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $notice = array('type' => 'error', 'message' => $exception->getMessage());
    } catch (Throwable $exception) {
        error_log('No se pudo guardar la configuración del aviso emergente: ' . $exception->getMessage());
        http_response_code(500);
        $notice = array('type' => 'error', 'message' => 'No se pudo guardar la configuración. Revisa la conexión con la base de datos e inténtalo de nuevo.');
    }
}

$_SESSION['popup_notice'] = $notice;
session_write_close();
header('Location: admin?section=popup', true, 303);
