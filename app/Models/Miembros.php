<?php

class Miembros
{
    private $conn;
    private $tableName = 'miembros';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $this->conn->exec("CREATE TABLE IF NOT EXISTS miembros (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(180) NOT NULL,
            cargo VARCHAR(120) NOT NULL,
            correo VARCHAR(180) DEFAULT NULL,
            orden INT NOT NULL DEFAULT 99,
            imagen VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // La tabla ya existia sin la columna de correo, y CREATE TABLE IF NOT
        // EXISTS no la agrega en tablas previas.
        $columns = $this->conn->query('SHOW COLUMNS FROM ' . $this->tableName)->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!in_array('correo', $columns, true)) {
            $this->conn->exec('ALTER TABLE ' . $this->tableName . ' ADD COLUMN `correo` VARCHAR(180) DEFAULT NULL AFTER cargo');
        }

        $count = (int) $this->conn->query('SELECT COUNT(*) FROM miembros')->fetchColumn();
        if ($count === 0) {
            $stmt = $this->conn->prepare('INSERT INTO miembros (nombre, cargo, orden) VALUES (:nombre, :cargo, :orden)');
            $defaults = [
                ['José Yudberto Vilca Ccolque', 'Vicepresidente', 2],
                ['Melissa Fatima Muñante Toledo', 'Secretaria', 3],
                ['Manuel Abelardo Alcántara Ramírez', 'Tesorero', 4],
                ['Edwin Augusto Vigo Sánchez', 'Vocal', 5],
                ['José Carlos Goicochea Ponce', 'Gerente general', 6],
            ];
            foreach ($defaults as [$nombre, $cargo, $orden]) {
                $stmt->execute([':nombre' => $nombre, ':cargo' => $cargo, ':orden' => $orden]);
            }
        }
    }

    public function create(): bool
    {
        $stmt = $this->conn->prepare('INSERT INTO miembros (nombre, cargo, correo, orden, imagen) VALUES (:nombre, :cargo, :correo, :orden, :imagen)');
        return $stmt->execute([
            ':nombre' => $this->nombre,
            ':cargo' => $this->cargo,
            ':correo' => $this->correo,
            ':orden' => $this->orden,
            ':imagen' => $this->imagen,
        ]);
    }

    public function update(): bool
    {
        $stmt = $this->conn->prepare('UPDATE miembros SET nombre = :nombre, cargo = :cargo, correo = :correo, orden = :orden, imagen = :imagen WHERE id = :id');
        return $stmt->execute([
            ':id' => $this->id,
            ':nombre' => $this->nombre,
            ':cargo' => $this->cargo,
            ':correo' => $this->correo,
            ':orden' => $this->orden,
            ':imagen' => $this->imagen,
        ]);
    }

    public function delete(): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM miembros WHERE id = :id');
        return $stmt->execute([':id' => $this->id]);
    }

    public function readAll(): array
    {
        $stmt = $this->conn->query('SELECT id, nombre, cargo, correo, orden, imagen FROM miembros ORDER BY orden ASC, id ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT id, nombre, cargo, correo, orden, imagen FROM miembros WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public $id;
    public $nombre;
    public $cargo;
    public $correo;
    public $orden;
    public $imagen;
}
