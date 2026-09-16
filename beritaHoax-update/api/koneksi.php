<?php
// ============================================================
// KONEKSI DATABASE
// Menyediakan:
//   1. Variabel $pdo (dipakai file lama: register, login, detect, dll)
//   2. Fungsi getDB() (dipakai file baru: detection, report, auth)
//   3. Helper functions (optionalParam, jsonResponse, dll)
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'hoax_detector_db');  // sesuaikan nama DB Anda
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --- 1. VARIABEL GLOBAL $pdo (untuk file lama) ---
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $e->getMessage()]);
    exit;
}

// --- 2. FUNGSI getDB() (untuk file baru laporan) ---
function getDB() {
    global $pdo;
    return $pdo;
}

// --- 3. HELPER FUNCTIONS ---
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function optionalParam($key, $default = '') {
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

function requireParam($key) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        return $_GET[$key];
    }
    jsonResponse(['success' => false, 'message' => 'Parameter "' . $key . '" wajib diisi'], 400);
}

function cleanInput($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}
?>