<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
Autoloader::register();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(array('error' => 'Método no permitido.'));
    exit;
}

try {
    $controller = new PopupController();
    echo json_encode(array('data' => $controller->readPublic()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    error_log('No se pudo consultar la configuración del aviso emergente: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(array('error' => 'No se pudo cargar el aviso emergente.'));
}
