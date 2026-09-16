<?php
error_reporting(0);
ini_set('display_errors', 0);

 $host = "localhost";
 $db   = "kopitro_db";
 $user = "root";
 $pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Koneksi database gagal: " . $e->getMessage()
    ]);
    exit;
}