<?php
require_once __DIR__ . '/../app/Core/Autoloader.php';
Autoloader::register();
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../config/recaptcha.php';

header('Content-Type: application/json; charset=UTF-8');

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!OriginGuard::isSameOrigin($origin, $_SERVER['HTTP_HOST'] ?? '', $isHttps)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden cross-site request']);
    exit;
}

$secureCookie = $isHttps;
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => $secureCookie,
]);
session_start();

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function jsonMessage(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['error' => $message, 'message' => $message]);
    exit;
}

function requireAdminSession(): void
{
    if (!isset($_SESSION['user'])) {
        jsonMessage(401, 'Tu sesión expiró. Inicia sesión de nuevo.');
    }
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        jsonMessage(403, 'Solo un administrador puede hacer esto.');
    }
}

function requireValidCsrf(): void
{
    if (!validateCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        jsonMessage(403, 'La página quedó desactualizada. Recárgala e inténtalo de nuevo.');
    }
}

const MIN_PASSWORD_LENGTH = 6;

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = $_GET['action'] ?? null;
    if ($action === 'create-user') {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'admin privileges required']);
            exit;
        }
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!validateCsrfToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['error' => 'invalid CSRF token']);
            exit;
        }
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;
        $role = $_POST['role'] ?? 'editor';
        $role = in_array($role, ['admin', 'editor'], true) ? $role : 'editor';
        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'username and password required', 'message' => 'Ingresa usuario y contraseña.']);
            exit;
        }
        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            jsonMessage(400, 'La contraseña debe tener al menos ' . MIN_PASSWORD_LENGTH . ' caracteres.');
        }
        $userModel = new User();
        $existing = $userModel->findByUsername($username);
        if ($existing) {
            http_response_code(409);
            echo json_encode(['error' => 'username already exists', 'message' => 'Ese nombre de usuario ya existe.']);
            exit;
        }
        $success = $userModel->create($username, $password, $role);
        if ($success) {
            echo json_encode(['ok' => true, 'message' => 'user created']);
            exit;
        }
        http_response_code(500);
        echo json_encode(['error' => 'failed to create user']);
        exit;
    }

    if ($action === 'update-user' || $action === 'delete-user') {
        requireAdminSession();
        requireValidCsrf();

        $targetId = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
        if ($targetId <= 0) {
            jsonMessage(400, 'Usuario no válido.');
        }
        $currentId = (int) ($_SESSION['user']['id'] ?? 0);
        $userModel = new User();

        if ($action === 'delete-user') {
            if ($targetId === $currentId) {
                jsonMessage(400, 'No puedes eliminar tu propia cuenta mientras tienes la sesión iniciada.');
            }
            $result = $userModel->deleteUser($targetId);
            if ($result === 'not found') jsonMessage(404, 'El usuario no existe.');
            if ($result === 'last admin') jsonMessage(409, 'No se puede eliminar al último administrador.');
            if ($result !== null) jsonMessage(500, 'No se pudo eliminar el usuario.');
            echo json_encode(['ok' => true, 'message' => 'Usuario eliminado.']);
            exit;
        }

        $rawPassword = $_POST['password'] ?? '';
        $rawRole = $_POST['role'] ?? '';
        $newPassword = is_string($rawPassword) && $rawPassword !== '' ? $rawPassword : null;
        $newRole = is_string($rawRole) && $rawRole !== '' ? $rawRole : null;
        if ($newRole !== null && !in_array($newRole, ['admin', 'editor'], true)) {
            jsonMessage(400, 'Rol no válido.');
        }
        if ($newPassword === null && $newRole === null) {
            jsonMessage(400, 'No hay cambios para guardar.');
        }
        if ($newPassword !== null && strlen($newPassword) < MIN_PASSWORD_LENGTH) {
            jsonMessage(400, 'La contraseña debe tener al menos ' . MIN_PASSWORD_LENGTH . ' caracteres.');
        }

        $result = $userModel->updateUser($targetId, $newPassword, $newRole);
        if ($result === 'not found') jsonMessage(404, 'El usuario no existe.');
        if ($result === 'last admin') jsonMessage(409, 'No se puede quitar el rol de administrador al último administrador.');
        if ($result !== null) jsonMessage(500, 'No se pudo actualizar el usuario.');
        if ($targetId === $currentId && $newRole !== null) {
            $_SESSION['user']['role'] = $newRole;
        }
        echo json_encode(['ok' => true, 'message' => 'Usuario actualizado.']);
        exit;
    }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'invalid CSRF token']);
        exit;
    }

    $captchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $remoteIp = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : null;
    if (!is_string($captchaResponse) || !verifyRecaptcha($captchaResponse, $remoteIp)) {
        jsonMessage(400, 'invalid recaptcha');
    }

    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;
    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'username and password required']);
        exit;
    }

    $userModel = new User();
    $user = $userModel->findByUsername($username);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role'] ?? 'editor'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $token = getCsrfToken();
        echo json_encode(['ok' => true, 'user' => $_SESSION['user'], 'csrf_token' => $token]);
        exit;
    }

    http_response_code(401);
    echo json_encode(['error' => 'invalid credentials']);
    exit;
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? null;
    if ($action === 'logout') {
        if (isset($_SESSION['user'])) {
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!validateCsrfToken($csrfToken)) {
                http_response_code(403);
                echo json_encode(['error' => 'invalid CSRF token']);
                exit;
            }
        }
        session_unset();
        session_destroy();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'list-users') {
        requireAdminSession();
        $userModel = new User();
        $users = $userModel->getAllUsers();
        echo json_encode(['data' => $users]);
        exit;
    }

    if (isset($_SESSION['user'])) {
        echo json_encode(['user' => $_SESSION['user'], 'csrf_token' => getCsrfToken()]);
        exit;
    }

        echo json_encode(['user' => null, 'csrf_token' => getCsrfToken()]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method not allowed']);

