<?php
session_start();
require_once "koneksi.php";

// Hanya menerima metode POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Metode request tidak diizinkan."
    ]);
    exit;
}

// Ambil data JSON atau form-data
$data = json_decode(file_get_contents("php://input"), true);

$username = $data['username'] ?? $_POST['username'] ?? '';
$password = $data['password'] ?? $_POST['password'] ?? '';

// Validasi input
if (empty($username) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "Username dan password wajib diisi."
    ]);
    exit;
}

try {

    // Cari user berdasarkan username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);

    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "Username tidak ditemukan."
        ]);
        exit;
    }

    /*
        Saat ini password di database masih plain text:
        admin123

        Jika nanti sudah menggunakan password_hash(),
        ganti bagian ini menjadi:

        password_verify($password, $user['password'])
    */

    if ($password != $user['password']) {

        echo json_encode([
            "success" => false,
            "message" => "Password salah."
        ]);
        exit;

    }

    // Simpan session login
    $_SESSION['login'] = true;
    $_SESSION['id'] = $user['id'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['username'] = $user['username'];

    echo json_encode([
        "success" => true,
        "message" => "Login berhasil.",
        "data" => [
            "id" => $user['id'],
            "nama" => $user['nama'],
            "username" => $user['username']
        ]
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}