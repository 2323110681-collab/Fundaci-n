<?php

class Database {
    private $host = "127.0.0.1";
    private $port = 3306;
    private $db_name = "fundaciondu";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->conn->exec("set names utf8");
        } catch(\Throwable $th) {
            echo "Error de conexión: " . $th->getMessage();
        }

        return $this->conn;
    }
}