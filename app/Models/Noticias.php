<?php

class Noticias
{
    private $conn;
    private $table_name = 'noticias';

    public $id;
    public $titulo;
    public $categoria;
    public $autor;
    public $imagen;
    public $fecha_evento;
    public $descripcion_corta;
    public $contenido;
    public $link;
    public $es_destacada;
    public $updated_at;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $columns = $this->conn->query('SHOW COLUMNS FROM ' . $this->table_name)->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!in_array('autor', $columns, true)) {
            $this->conn->exec('ALTER TABLE ' . $this->table_name . ' ADD COLUMN autor VARCHAR(180) DEFAULT NULL AFTER categoria');
        }
    }

    public function create()
    {
        $query = 'INSERT INTO ' . $this->table_name . ' (titulo, categoria, autor, imagen, fecha_evento, descripcion_corta, contenido, link, es_destacada, updated_at) VALUES (:titulo, :categoria, :autor, :imagen, :fecha_evento, :descripcion_corta, :contenido, :link, :es_destacada, :updated_at)';
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':autor', $this->autor);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':fecha_evento', $this->fecha_evento);
        $stmt->bindParam(':descripcion_corta', $this->descripcion_corta);
        $stmt->bindParam(':contenido', $this->contenido);
        $stmt->bindParam(':link', $this->link);
        $stmt->bindParam(':es_destacada', $this->es_destacada, PDO::PARAM_INT);
        $stmt->bindParam(':updated_at', $this->updated_at);

        return $stmt->execute();
    }

    public function update()
    {
        $query = 'UPDATE ' . $this->table_name . ' SET titulo = :titulo, categoria = :categoria, autor = :autor, imagen = :imagen, fecha_evento = :fecha_evento, descripcion_corta = :descripcion_corta, contenido = :contenido, link = :link, es_destacada = :es_destacada, updated_at = :updated_at WHERE id = :id';
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':autor', $this->autor);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':fecha_evento', $this->fecha_evento);
        $stmt->bindParam(':descripcion_corta', $this->descripcion_corta);
        $stmt->bindParam(':contenido', $this->contenido);
        $stmt->bindParam(':link', $this->link);
        $stmt->bindParam(':es_destacada', $this->es_destacada, PDO::PARAM_INT);
        $stmt->bindParam(':updated_at', $this->updated_at);
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
