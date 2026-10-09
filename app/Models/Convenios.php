<?php

class Convenios
{
    private $conn;
    private $tableName = 'convenios';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $this->conn->exec("CREATE TABLE IF NOT EXISTS convenios (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            institucion VARCHAR(180) NOT NULL,
            pais VARCHAR(100) NOT NULL,
            ciudad VARCHAR(120) DEFAULT NULL,
            latitud DECIMAL(10,7) DEFAULT NULL,
            longitud DECIMAL(10,7) DEFAULT NULL,
            ubicacion_google VARCHAR(500) DEFAULT NULL,
            tipo VARCHAR(120) DEFAULT NULL,
            descripcion TEXT DEFAULT NULL,
            imagen VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        try { $this->conn->exec('ALTER TABLE convenios MODIFY latitud DECIMAL(10,7) DEFAULT NULL, MODIFY longitud DECIMAL(10,7) DEFAULT NULL'); } catch (Throwable $exception) { }
        try { $this->conn->exec('ALTER TABLE convenios ADD COLUMN ubicacion_google VARCHAR(500) DEFAULT NULL AFTER longitud'); } catch (Throwable $exception) { }

    }

    public function readAll(): array
    {
        $stmt = $this->conn->query('SELECT id, institucion, pais, ciudad, latitud, longitud, ubicacion_google, tipo, descripcion, imagen FROM convenios ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT id, institucion, pais, ciudad, latitud, longitud, ubicacion_google, tipo, descripcion, imagen FROM convenios WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(): bool
    {
        $stmt = $this->conn->prepare('INSERT INTO convenios (institucion, pais, ciudad, latitud, longitud, ubicacion_google, tipo, descripcion, imagen) VALUES (:institucion, :pais, :ciudad, :latitud, :longitud, :ubicacion_google, :tipo, :descripcion, :imagen)');
        return $stmt->execute($this->values());
    }

    public function update(): bool
    {
        $values = $this->values();
        $values[':id'] = $this->id;
        $stmt = $this->conn->prepare('UPDATE convenios SET institucion = :institucion, pais = :pais, ciudad = :ciudad, latitud = :latitud, longitud = :longitud, ubicacion_google = :ubicacion_google, tipo = :tipo, descripcion = :descripcion, imagen = :imagen WHERE id = :id');
        return $stmt->execute($values);
    }

    public function delete(): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM convenios WHERE id = :id');
        return $stmt->execute([':id' => $this->id]);
    }

    private function values(): array
    {
        return [
            ':institucion' => $this->institucion, ':pais' => $this->pais, ':ciudad' => $this->ciudad,
            ':latitud' => $this->latitud, ':longitud' => $this->longitud, ':ubicacion_google' => $this->ubicacion_google, ':tipo' => $this->tipo,
            ':descripcion' => $this->descripcion, ':imagen' => $this->imagen,
        ];
    }

    public $id;
    public $institucion;
    public $pais;
    public $ciudad;
    public $latitud;
    public $longitud;
    public $ubicacion_google;
    public $tipo;
    public $descripcion;
    public $imagen;
}
