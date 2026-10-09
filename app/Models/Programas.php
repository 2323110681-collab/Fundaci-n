<?php

class Programas
{
    private $conn;
    private $table_name = 'programas';

    public $id;
    public $titulo;
    public $categoria;
    public $imagen;
    public $autor;
    public $descripcion;
    public $link;
    public $fecha_inicio;
    public $fecha_fin;
    public $lugar;
    public $duracion;
    public $modalidad;
    public $horario;
    public $frecuencia;
    public $dirigido_a;
    public $objetivos;
    public $temario;
    public $requisitos;
    public $certificacion;
    public $docentes;
    public $inversion;
    public $descuentos;
    public $vacantes;
    public $contacto_telefono;
    public $contacto_whatsapp;
    public $contacto_correo;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $columns = $this->conn->query('SHOW COLUMNS FROM ' . $this->table_name)->fetchAll(PDO::FETCH_COLUMN, 0);
        $definitions = [
            'duracion' => 'VARCHAR(120) DEFAULT NULL',
            'modalidad' => 'VARCHAR(20) DEFAULT NULL',
            'horario' => 'VARCHAR(180) DEFAULT NULL',
            'frecuencia' => 'VARCHAR(120) DEFAULT NULL',
            'dirigido_a' => 'TEXT DEFAULT NULL',
            'objetivos' => 'TEXT DEFAULT NULL',
            'temario' => 'LONGTEXT DEFAULT NULL',
            'requisitos' => 'TEXT DEFAULT NULL',
            'certificacion' => 'TEXT DEFAULT NULL',
            'docentes' => 'LONGTEXT DEFAULT NULL',
            'inversion' => 'TEXT DEFAULT NULL',
            'descuentos' => 'TEXT DEFAULT NULL',
            'vacantes' => 'INT(11) DEFAULT NULL',
            'contacto_telefono' => 'VARCHAR(60) DEFAULT NULL',
            'contacto_whatsapp' => 'VARCHAR(60) DEFAULT NULL',
            'contacto_correo' => 'VARCHAR(254) DEFAULT NULL',
        ];

        foreach ($definitions as $column => $definition) {
            if (!in_array($column, $columns, true)) {
                $this->conn->exec('ALTER TABLE ' . $this->table_name . ' ADD COLUMN `' . $column . '` ' . $definition);
            }
        }
    }

    public function create()
    {
        $query = 'INSERT INTO ' . $this->table_name . ' (titulo, categoria, imagen, autor, descripcion, link, fecha_inicio, fecha_fin, lugar, duracion, modalidad, horario, frecuencia, dirigido_a, objetivos, temario, requisitos, certificacion, docentes, inversion, descuentos, vacantes, contacto_telefono, contacto_whatsapp, contacto_correo) VALUES (:titulo, :categoria, :imagen, :autor, :descripcion, :link, :fecha_inicio, :fecha_fin, :lugar, :duracion, :modalidad, :horario, :frecuencia, :dirigido_a, :objetivos, :temario, :requisitos, :certificacion, :docentes, :inversion, :descuentos, :vacantes, :contacto_telefono, :contacto_whatsapp, :contacto_correo)';
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':autor', $this->autor);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':link', $this->link);
        $stmt->bindParam(':fecha_inicio', $this->fecha_inicio);
        $stmt->bindParam(':fecha_fin', $this->fecha_fin);
        $stmt->bindParam(':lugar', $this->lugar);
        $this->bindExtendedFields($stmt);

        return $stmt->execute();
    }

    public function update()
    {
        $query = 'UPDATE ' . $this->table_name . ' SET titulo = :titulo, categoria = :categoria, imagen = :imagen, autor = :autor, descripcion = :descripcion, link = :link, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, lugar = :lugar, duracion = :duracion, modalidad = :modalidad, horario = :horario, frecuencia = :frecuencia, dirigido_a = :dirigido_a, objetivos = :objetivos, temario = :temario, requisitos = :requisitos, certificacion = :certificacion, docentes = :docentes, inversion = :inversion, descuentos = :descuentos, vacantes = :vacantes, contacto_telefono = :contacto_telefono, contacto_whatsapp = :contacto_whatsapp, contacto_correo = :contacto_correo WHERE id = :id';
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':autor', $this->autor);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':link', $this->link);
        $stmt->bindParam(':fecha_inicio', $this->fecha_inicio);
        $stmt->bindParam(':fecha_fin', $this->fecha_fin);
        $stmt->bindParam(':lugar', $this->lugar);
        $this->bindExtendedFields($stmt);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function bindExtendedFields(PDOStatement $stmt)
    {
        foreach ([
            'duracion', 'modalidad', 'horario', 'frecuencia', 'dirigido_a', 'objetivos',
            'temario', 'requisitos', 'certificacion', 'docentes', 'inversion', 'descuentos',
            'vacantes', 'contacto_telefono', 'contacto_whatsapp', 'contacto_correo'
        ] as $field) {
            $type = $this->$field === null
                ? PDO::PARAM_NULL
                : ($field === 'vacantes' ? PDO::PARAM_INT : PDO::PARAM_STR);
            $stmt->bindValue(':' . $field, $this->$field, $type);
        }
    }

    public function delete()
    {
        $query = 'DELETE FROM ' . $this->table_name . ' WHERE id = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
