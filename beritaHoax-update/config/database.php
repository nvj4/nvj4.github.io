<?php
class Database {
    private $host = "localhost";
    private $db_name = "faktaku_db";
    private $username = "root"; // Sesuaikan dengan username MySQL Anda
    private $password = ""; // Sesuaikan dengan password MySQL Anda
    private $charset = "utf8mb4";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            echo json_encode([
                'success' => false,
                'message' => 'Database connection error: ' . $exception->getMessage()
            ]);
            exit();
        }
        
        return $this->conn;
    }
}
?>
