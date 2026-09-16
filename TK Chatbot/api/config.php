<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tk_db');

// Koneksi Database
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    return $conn;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isGuru() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'guru';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireGuru() {
    requireLogin();
    if (!isGuru()) {
        header('Location: index.php');
        exit();
    }
}

// Generate kode unik
function generateKodeUnik($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Format tanggal Indonesia
function formatTanggal($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $bulan[date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

// Escape output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}


define('REG_SECRET_KEY', 'HANYAUNTUKGURU'); //VALIDASI SECRET KEY
?>