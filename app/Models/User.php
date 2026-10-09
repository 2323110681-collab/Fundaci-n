<?php
require_once __DIR__ . '/../../config/database.php';

class User
{
    private $conn;
    public $id;
    public $username;
    public $password_hash;
    public $role;

    public function __construct($db = null)
    {
        if ($db) {
            $this->conn = $db;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }

        $this->ensureSchema();
    }

    private function ensureSchema()
    {
        $this->conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'editor',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $count = (int) $this->conn->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            $stmt = $this->conn->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
            $stmt->execute([
                ':username' => 'admin',
                ':password' => password_hash('admin123', PASSWORD_DEFAULT),
                ':role' => 'admin',
            ]);
        }
    }

    public function findByUsername($username)
    {
        $stmt = $this->conn->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById(int $id)
    {
        $stmt = $this->conn->prepare('SELECT id, username, role, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllUsers()
    {
        $stmt = $this->conn->prepare('SELECT id, username, role, created_at FROM users ORDER BY id DESC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($username, $password, $role = 'admin')
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':role', $role);
        return $stmt->execute();
    }

    /**
     * Cambia la contraseña y/o el rol de un usuario.
     * Devuelve null si todo salió bien o un mensaje de error si no se puede aplicar el cambio.
     * Se bloquean las filas de administradores para que dos cambios simultáneos no dejen al sistema sin admin.
     */
    public function updateUser(int $id, ?string $password, ?string $role): ?string
    {
        $this->conn->beginTransaction();
        try {
            $admins = $this->conn->query("SELECT id FROM users WHERE role = 'admin' FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN, 0);
            $stmt = $this->conn->prepare('SELECT id, role FROM users WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $this->conn->rollBack();
                return 'not found';
            }
            if ($role !== null && $role !== $user['role'] && $user['role'] === 'admin' && count($admins) <= 1) {
                $this->conn->rollBack();
                return 'last admin';
            }
            if ($role !== null) {
                $stmt = $this->conn->prepare('UPDATE users SET role = :role WHERE id = :id');
                $stmt->execute([':role' => $role, ':id' => $id]);
            }
            if ($password !== null) {
                $stmt = $this->conn->prepare('UPDATE users SET password = :password WHERE id = :id');
                $stmt->execute([':password' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]);
            }
            $this->conn->commit();
            return null;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return 'error';
        }
    }

    /** Elimina un usuario, salvo que sea el último administrador. */
    public function deleteUser(int $id): ?string
    {
        $this->conn->beginTransaction();
        try {
            $admins = $this->conn->query("SELECT id FROM users WHERE role = 'admin' FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN, 0);
            $stmt = $this->conn->prepare('SELECT id, role FROM users WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $this->conn->rollBack();
                return 'not found';
            }
            if ($user['role'] === 'admin' && count($admins) <= 1) {
                $this->conn->rollBack();
                return 'last admin';
            }
            $stmt = $this->conn->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $this->conn->commit();
            return null;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return 'error';
        }
    }
}
