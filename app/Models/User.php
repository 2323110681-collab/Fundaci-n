<?php
require_once __DIR__ . '/../../config/database.php';

class User
{
    private const MAX_FAILED_ATTEMPTS = 3;
    private const DAILY_LOCKOUT_THRESHOLD = 5;
    private const INITIAL_LOCK_SECONDS = 900;
    private const FINAL_LOCK_SECONDS = 86400;

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
            failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            login_lockouts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            locked_until DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $columns = array_column($this->conn->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        $securityColumns = array(
            'failed_login_attempts' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
            'login_lockouts' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
            'locked_until' => 'DATETIME DEFAULT NULL',
        );
        foreach ($securityColumns as $column => $definition) {
            if (!in_array($column, $columns, true)) {
                try {
                    $this->conn->exec('ALTER TABLE users ADD COLUMN ' . $column . ' ' . $definition);
                } catch (PDOException $exception) {
                    $columns = array_column(
                        $this->conn->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC),
                        'Field'
                    );
                    if (!in_array($column, $columns, true)) {
                        throw $exception;
                    }
                }
            }
        }

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

    public function attemptLogin(string $username, string $password): array
    {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare(
                'SELECT id, username, password, role, failed_login_attempts, login_lockouts, locked_until,
                        GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), locked_until)) AS lock_seconds
                 FROM users WHERE username = :username LIMIT 1 FOR UPDATE'
            );
            $stmt->execute(array(':username' => $username));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->conn->commit();
                return array('status' => 'invalid');
            }

            $lockSeconds = (int) $user['lock_seconds'];
            $lockouts = (int) $user['login_lockouts'];
            if ($lockSeconds > 0) {
                $this->conn->commit();
                return array('status' => 'locked', 'retry_after' => $lockSeconds);
            }

            if ($lockouts >= self::DAILY_LOCKOUT_THRESHOLD && $user['locked_until'] !== null) {
                $reset = $this->conn->prepare(
                    'UPDATE users SET failed_login_attempts = 0, login_lockouts = 0, locked_until = NULL WHERE id = :id'
                );
                $reset->execute(array(':id' => $user['id']));
                $lockouts = 0;
            }

            if (password_verify($password, $user['password'])) {
                $reset = $this->conn->prepare(
                    'UPDATE users SET failed_login_attempts = 0, login_lockouts = 0, locked_until = NULL WHERE id = :id'
                );
                $reset->execute(array(':id' => $user['id']));
                $this->conn->commit();
                return array(
                    'status' => 'authenticated',
                    'user' => array(
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role'] ?? 'editor',
                    ),
                );
            }

            $failedAttempts = (int) $user['failed_login_attempts'] + 1;
            if ($failedAttempts < self::MAX_FAILED_ATTEMPTS) {
                $update = $this->conn->prepare('UPDATE users SET failed_login_attempts = :attempts WHERE id = :id');
                $update->execute(array(':attempts' => $failedAttempts, ':id' => $user['id']));
                $this->conn->commit();
                return array('status' => 'invalid');
            }

            $lockouts++;
            $lockSeconds = $lockouts >= self::DAILY_LOCKOUT_THRESHOLD
                ? self::FINAL_LOCK_SECONDS
                : self::INITIAL_LOCK_SECONDS * (2 ** ($lockouts - 1));
            $update = $this->conn->prepare(
                'UPDATE users
                 SET failed_login_attempts = 0, login_lockouts = :lockouts,
                     locked_until = DATE_ADD(NOW(), INTERVAL :lock_seconds SECOND)
                 WHERE id = :id'
            );
            $update->execute(array(
                ':lockouts' => $lockouts,
                ':lock_seconds' => $lockSeconds,
                ':id' => $user['id'],
            ));
            $this->conn->commit();
            return array('status' => 'locked', 'retry_after' => $lockSeconds);
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
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
                $stmt = $this->conn->prepare(
                    'UPDATE users
                     SET password = :password, failed_login_attempts = 0, login_lockouts = 0, locked_until = NULL
                     WHERE id = :id'
                );
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
