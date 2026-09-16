<?php
session_start(); // 1. TAMBAHKAN INI

require_once __DIR__ . '/koneksi.php';

 $name = $_POST['name'] ?? '';
 $email = $_POST['email'] ?? '';
 $password = $_POST['password'] ?? '';

if (!$name || !$email || !$password) {
    echo json_encode([
        "success" => false,
        "message" => "Semua field wajib diisi"
    ]);
    exit;
}

// Cek email sudah ada
 $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
 $check->execute([$email]);

if ($check->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Email sudah terdaftar"
    ]);
    exit;
}

// Hash password
 $hashed = password_hash($password, PASSWORD_DEFAULT);

// Insert user
 $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
 $insert = $stmt->execute([$name, $email, $hashed]);

if ($insert) {
    $user_id = $pdo->lastInsertId();

    // 2. TAMBAHKAN INI: Buat session langsung setelah daftar
    $_SESSION['user'] = [
        "id"    => $user_id,
        "name"  => $name,
        "email" => $email
    ];

    echo json_encode([
        "success" => true,
        "user" => [
            "id"    => $user_id,
            "name"  => $name,
            "email" => $email
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menyimpan ke database"
    ]);
}