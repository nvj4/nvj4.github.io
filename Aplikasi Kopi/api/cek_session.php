<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header("Content-Type: application/json; charset=utf-8");

try {
    if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
        echo json_encode([
            "success" => true,
            "data" => [
                "id"       => $_SESSION['id'] ?? '',
                "nama"     => $_SESSION['nama'] ?? '',
                "username" => $_SESSION['username'] ?? ''
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Belum login."
        ]);
    }
} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}