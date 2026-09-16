<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (isset($_SESSION['user'])) {
    echo json_encode([
        "success" => true,
        "data" => [
            "name"  => $_SESSION['user']['name'],
            "email" => $_SESSION['user']['email']
        ]
    ]);
} else {
    echo json_encode([
        "success" => false
    ]);
}
