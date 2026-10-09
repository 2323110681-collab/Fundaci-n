<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
Autoloader::register();

header('Content-Type: application/json; charset=UTF-8');

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secureCookie,
]);
session_start();

function requireRole(array $roles)
{
    $userRole = $_SESSION['user']['role'] ?? '';
    if (!in_array($userRole, $roles, true)) {
        http_response_code(403);
        echo json_encode(['error' => 'insufficient privileges']);
        exit;
    }
}

function validateCsrfToken(string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

if (in_array($_SERVER['REQUEST_METHOD'], ['POST','PUT','DELETE'])) {
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'authentication required']);
        exit;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? null);
    if (!OriginGuard::isSameOrigin($origin, $_SERVER['HTTP_HOST'] ?? '', $isHttps)) {
        http_response_code(403);
        echo json_encode(['error' => 'forbidden cross-site request']);
        exit;
    }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'invalid CSRF token']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        requireRole(['admin']);
    }

    if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
        requireRole(['admin', 'editor']);
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// Sin un recurso explicito no hay nada que responder: antes esta API devolvia las
// noticias con HTTP 200 para cualquier ruta, lo que rompia el SEO y ocultaba
// errores de URL. Ahora un recurso desconocido es un 404 real.
$allowedResources = ['noticias', 'programas', 'agenda', 'eventos', 'evento', 'event', 'miembros', 'convenios', 'congreso'];
$requestedResource = $_GET['resource'] ?? null;
$resource = is_string($requestedResource) ? strtolower(trim($requestedResource)) : '';

if (!in_array($resource, $allowedResources, true)) {
    http_response_code(404);
    echo json_encode(['error' => 'Recurso no encontrado']);
    exit;
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null;

if ($method === 'POST') {
    $overrideMethod = $_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
    if ($overrideMethod) {
        $method = strtoupper($overrideMethod);
    }
}

$eventAliases = ['eventos', 'evento', 'event'];
if (in_array(strtolower($resource), $eventAliases, true)) {
    $resource = 'agenda';
}

$noticiascontroller = new NoticiasController();
$agendaController = new AgendaController();
$programasController = new ProgramasController();
$miembrosController = new MiembrosController();
$conveniosController = new ConveniosController();

switch ($resource) {
    case 'congreso':
        try {
            $congresoController = new CongresoController();
            $congressView = $_GET['view'] ?? '';
            if ($method === 'GET' && $congressView === 'summary') {
                $congresoController->readSummary();
            } elseif ($method === 'POST' && $congressView === 'upload-image') {
                $congresoController->uploadImage();
            } elseif ($method === 'POST' && $congressView === 'create-save') {
                $congresoController->createPublished();
            } elseif ($method === 'GET') {
                $congresoController->read();
            } elseif ($method === 'PUT' && $congressView === 'summary') {
                $congresoController->saveSummary();
            } elseif ($method === 'PUT') {
                $congresoController->save();
            } elseif ($method === 'DELETE') {
                requireRole(['admin']);
                $congresoController->delete();
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
            }
        } catch (Throwable $exception) {
            error_log('Error al consultar o guardar el contenido del congreso: ' . $exception->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo procesar el contenido del congreso.']);
        }
        break;
    case 'convenios':
        switch ($method) {
            case 'POST':
                $id !== null ? $conveniosController->updateConvenio($id) : $conveniosController->createConvenio();
                break;
            case 'GET':
                $id !== null ? $conveniosController->readConvenio($id) : $conveniosController->readConvenios();
                break;
            case 'PUT':
                if ($id !== null) $conveniosController->updateConvenio($id); else { http_response_code(400); echo json_encode(['error' => 'ID de convenio no proporcionado']); }
                break;
            case 'DELETE':
                if ($id !== null) $conveniosController->deleteConvenio($id); else { http_response_code(400); echo json_encode(['error' => 'ID de convenio no proporcionado']); }
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
                break;
        }
        break;
    case 'miembros':
        switch ($method) {
            case 'POST':
                if ($id !== null) {
                    $miembrosController->updateMiembro($id);
                } else {
                    $miembrosController->createMiembro();
                }
                break;
            case 'GET':
                if ($id !== null) {
                    $miembrosController->readMiembro($id);
                } else {
                    $miembrosController->readMiembros();
                }
                break;
            case 'PUT':
                if ($id !== null) {
                    $miembrosController->updateMiembro($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de miembro no proporcionado']);
                }
                break;
            case 'DELETE':
                if ($id !== null) {
                    $miembrosController->deleteMiembro($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de miembro no proporcionado']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
                break;
        }
        break;
    case 'agenda':
        switch ($method) {
            case 'POST':
                $agendaController->createAgenda();
                break;
            case 'GET':
                if ($id !== null) {
                    $agendaController->readAgendaItem($id);
                } else {
                    $agendaController->readAgenda();
                }
                break;
            case 'PUT':
                if ($id !== null) {
                    $agendaController->updateAgenda($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de agenda no proporcionado']);
                }
                break;
            case 'DELETE':
                if ($id !== null) {
                    $agendaController->deleteAgenda($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de agenda no proporcionado']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
                break;
        }
        break;
    case 'programas':
        switch ($method) {
            case 'POST':
                $programasController->createPrograma();
                break;
            case 'GET':
                if ($id !== null) {
                    $programasController->readPrograma($id);
                } else {
                    $programasController->readProgramas();
                }
                break;
            case 'PUT':
                if ($id !== null) {
                    $programasController->updatePrograma($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de programa no proporcionado']);
                }
                break;
            case 'DELETE':
                if ($id !== null) {
                    $programasController->deletePrograma($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de programa no proporcionado']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
                break;
        }
        break;
    case 'noticias':
        switch ($method) {
            case 'POST':
                $noticiascontroller->createNoticia();
                break;
            case 'GET':
                if ($id !== null) {
                    $noticiascontroller->readNoticia($id);
                } else { 
                    $noticiascontroller->readNoticias();
                }
                break;
            case 'PUT':
                if ($id !== null) {
                    $noticiascontroller->updateNoticia($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de noticia no proporcionado']);
                }
                break;
            case 'DELETE':
                if ($id !== null) {
                    $noticiascontroller->deleteNoticia($id);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID de noticia no proporcionado']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
                break;
        }
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Recurso no encontrado']);
        break;
}
