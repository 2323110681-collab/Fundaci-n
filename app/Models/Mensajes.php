<?php

class Mensajes
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $this->conn->exec("CREATE TABLE IF NOT EXISTS mensajes (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(180) NOT NULL,
            correo VARCHAR(180) NOT NULL,
            telefono VARCHAR(30) DEFAULT NULL,
            asunto VARCHAR(120) DEFAULT NULL,
            mensaje TEXT NOT NULL,
            estado ENUM('pendiente', 'respondido') NOT NULL DEFAULT 'pendiente',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function create(array $data): int
    {
        $stmt = $this->conn->prepare('INSERT INTO mensajes (nombre, correo, telefono, asunto, mensaje) VALUES (:nombre, :correo, :telefono, :asunto, :mensaje)');
        $stmt->execute(array(
            ':nombre' => $data['nombre'],
            ':correo' => $data['correo'],
            ':telefono' => $data['telefono'] !== '' ? $data['telefono'] : null,
            ':asunto' => $data['asunto'] !== '' ? $data['asunto'] : null,
            ':mensaje' => $data['mensaje'],
        ));

        return (int) $this->conn->lastInsertId();
    }
}
