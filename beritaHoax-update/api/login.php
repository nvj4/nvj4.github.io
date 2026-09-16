<?php
require_once __DIR__ . '/session_init.php';

if (file_exists(__DIR__ . '/koneksi.php')) {
    require_once __DIR__ . '/koneksi.php';
} else if (file_exists(__DIR__ . '/../koneksi.php')) {
    require_once __DIR__ . '/../koneksi.php';
} else {
    echo json_encode(["success" => false, "message" => "File koneksi.php tidak ditemukan"]);
    exit;
}

 $email = $_POST['email'] ?? '';
 $password = $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Email dan password wajib diisi"]);
    exit;
}

 $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
 $stmt->execute([$email]);
 $user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user'] = [  // ← TAMBAHKAN INI
        "id"    => $user['id'],
        "name"  => $user['name'],
        "email" => $user['email']
    ];
    echo json_encode([
        "success" => true,
        "user" => [
            "id"    => $user['id'],
            "name"  => $user['name'],
            "email" => $user['email']
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Email atau password salah"
    ]);
}