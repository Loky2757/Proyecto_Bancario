<?php
declare(strict_types=1);
class Database {
    private string $host = "localhost";
    private string $db_name = "sistema_bancario";
    private string $username = "root";
    private string $password = ""; // Contraseña vacía por defecto en XAMPP Windows
    private ?PDO $conn = null;
    /**
    * Establece y retorna una instancia única de conexión PDO a MySQL.
    */
    public function getConnection(): PDO {
          if ($this->conn === null) {
    try {
      $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
      $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
];
$this->conn = new PDO($dsn, $this->username, $this->password, $options);
   } 
   catch (PDOException $e) {
    throw new Exception("Error crítico de conexión a MySQL: " . $e->getMessage());
    }
    }
return $this->conn;
    }
      }
  ?>