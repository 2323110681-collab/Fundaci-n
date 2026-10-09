<?php

class Reclamaciones
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $this->conn->exec("CREATE TABLE IF NOT EXISTS reclamaciones (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            folio VARCHAR(24) NOT NULL UNIQUE,
            nombre VARCHAR(180) NOT NULL,
            tipo_documento VARCHAR(20) NOT NULL,
            numero_documento VARCHAR(20) DEFAULT NULL,
            domicilio VARCHAR(250) DEFAULT NULL,
            telefono VARCHAR(30) DEFAULT NULL,
            correo VARCHAR(180) NOT NULL,
            tipo_bien ENUM('producto', 'servicio') NOT NULL,
            fecha_compra DATE DEFAULT NULL,
            bien_contratado VARCHAR(250) NOT NULL,
            monto_reclamado DECIMAL(12,2) DEFAULT NULL,
            tipo ENUM('reclamo', 'queja') NOT NULL,
            detalle TEXT NOT NULL,
            pedido TEXT NOT NULL,
            estado ENUM('pendiente', 'respondida') NOT NULL DEFAULT 'pendiente',
            respuesta TEXT DEFAULT NULL,
            acepta_datos TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            responded_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function create(array $data): string
    {
        $folio = 'LR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $this->conn->prepare('INSERT INTO reclamaciones (folio, nombre, tipo_documento, numero_documento, domicilio, telefono, correo, tipo_bien, fecha_compra, bien_contratado, monto_reclamado, tipo, detalle, pedido, acepta_datos) VALUES (:folio, :nombre, :tipo_documento, :numero_documento, :domicilio, :telefono, :correo, :tipo_bien, :fecha_compra, :bien_contratado, :monto_reclamado, :tipo, :detalle, :pedido, :acepta_datos)');
        $stmt->execute(array(
            ':folio' => $folio,
            ':nombre' => $data['nombre'],
            ':tipo_documento' => $data['tipo_documento'],
            ':numero_documento' => $data['numero_documento'] !== '' ? $data['numero_documento'] : null,
            ':domicilio' => $data['domicilio'] !== '' ? $data['domicilio'] : null,
            ':telefono' => $data['telefono'] !== '' ? $data['telefono'] : null,
            ':correo' => $data['correo'],
            ':tipo_bien' => $data['tipo_bien'],
            ':fecha_compra' => $data['fecha_compra'] !== '' ? $data['fecha_compra'] : null,
            ':bien_contratado' => $data['bien_contratado'],
            ':monto_reclamado' => $data['monto_reclamado'] !== '' ? $data['monto_reclamado'] : null,
            ':tipo' => $data['tipo'],
            ':detalle' => $data['detalle'],
            ':pedido' => $data['pedido'],
            ':acepta_datos' => $data['acepta_datos'],
        ));

        return $folio;
    }
}