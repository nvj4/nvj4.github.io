<?php
/**
 * session_init.php
 * Bootstrap session dengan folder penyimpanan KHUSUS di dalam project
 * (menghindari C:\xampp\tmp yang sering diganggu antivirus/Windows Defender
 * di lingkungan XAMPP Windows, menyebabkan file session ke-tulis kosong).
 *
 * WAJIB di-require SEBELUM memanggil session_start() di file mana pun
 * yang butuh session (login.php, get_history.php, detect_search.php, dll).
 *
 * Cara pakai di file lain:
 *   require_once __DIR__ . '/session_init.php';   // ganti session_start() dengan ini
 *   // TIDAK PERLU lagi panggil session_start() manual setelah ini
 */

$customSessionPath = __DIR__ . '/../sessions_data';

// Buat folder kalau belum ada
if (!is_dir($customSessionPath)) {
    mkdir($customSessionPath, 0777, true);
}

// Arahkan PHP untuk simpan session di folder ini, bukan di C:\xampp\tmp
ini_set('session.save_path', $customSessionPath);

// Opsional tapi disarankan: pastikan cookie session konsisten
ini_set('session.cookie_path', '/');
// ini_set('session.cookie_samesite', 'Lax'); // aktifkan kalau perlu

session_start();