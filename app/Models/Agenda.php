<?php

class Agenda
{
    private $conn;
    private $table_name = 'eventos';

    public $id;
    public $titulo;
    public $fecha_evento;
    public $fecha_fin;
    public $hora_evento;
    public $lugar;
    public $descripcion;
    public $link_inscripcion;
    public $categoria;
    public $estado;
    public $imagen;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $columnExists = $this->conn->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name'
        );
        $columnExists->execute(array(':table_name' => $this->table_name, ':column_name' => 'fecha_fin'));
        if ((int) $columnExists->fetchColumn() === 0) {
            try {
                $this->conn->exec('ALTER TABLE eventos ADD COLUMN fecha_fin DATE DEFAULT NULL AFTER fecha_evento');
            } catch (Throwable $exception) {
                $columnExists->execute(array(':table_name' => $this->table_name, ':column_name' => 'fecha_fin'));
                if ((int) $columnExists->fetchColumn() === 0) {
                    throw $exception;
                }
            }
        }
    }

    public function create()
    {
        $query = 'INSERT INTO ' . $this->table_name . ' (titulo, fecha_evento, fecha_fin, hora_evento, lugar, descripcion, link_inscripcion, categoria, estado, imagen) VALUES (:titulo, :fecha_evento, :fecha_fin, :hora_evento, :lugar, :descripcion, :link_inscripcion, :categoria, :estado, :imagen)';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':fecha_evento', $this->fecha_evento);
        $stmt->bindParam(':fecha_fin', $this->fecha_fin);
        $stmt->bindParam(':hora_evento', $this->hora_evento);
        $stmt->bindParam(':lugar', $this->lugar);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':link_inscripcion', $this->link_inscripcion);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':imagen', $this->imagen);

        return $stmt->execute();
    }

    public function update()
    {
        $query = 'UPDATE ' . $this->table_name . ' SET titulo = :titulo, fecha_evento = :fecha_evento, fecha_fin = :fecha_fin, hora_evento = :hora_evento, lugar = :lugar, descripcion = :descripcion, link_inscripcion = :link_inscripcion, categoria = :categoria, estado = :estado, imagen = :imagen WHERE id = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':fecha_evento', $this->fecha_evento);
        $stmt->bindParam(':fecha_fin', $this->fecha_fin);
        $stmt->bindParam(':hora_evento', $this->hora_evento);
        $stmt->bindParam(':lugar', $this->lugar);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':link_inscripcion', $this->link_inscripcion);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete()
    {
        $query = 'DELETE FROM ' . $this->table_name . ' WHERE id = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
