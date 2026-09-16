<?php
/**
 * session_test2.php
 * Diagnostik lanjutan: cek apakah session.save_path bisa ditulis,
 * apakah session_start() benar-benar sukses, dan di mana file session disimpan.
 *
 * HAPUS FILE INI setelah selesai debug.
 */

header('Content-Type: application/json; charset=utf-8');

$savePath = session_save_path();
if (!$savePath) {
    $savePath = sys_get_temp_dir(); // default PHP kalau session.save_path kosong
}

$info = [
    "php_version"        => phpversion(),
    "session_save_path"   => session_save_path() ?: "(kosong, pakai default: " . sys_get_temp_dir() . ")",
    "save_path_exists"    => is_dir($savePath),
    "save_path_writable"  => is_writable($savePath),
    "session_save_handler"=> ini_get('session.save_handler'),
    "session_module_name" => session_module_name(),
];

$customSessionPath = __DIR__ . '/../sessions_data';
if (!is_dir($customSessionPath)) { mkdir($customSessionPath, 0777, true); }
ini_set('session.save_path', $customSessionPath);
$startResult = session_start();
$info["session_start_result"] = $startResult ? "SUKSES" : "GAGAL";
$info["session_status"] = session_status(); // 0=disabled, 1=none, 2=active
$info["session_id"] = session_id();

if (isset($_GET['set'])) {
    $_SESSION['test_value'] = 'halo-' . time();
    $info["action"] = "set";
    $info["value_set"] = $_SESSION['test_value'];
} else {
    $info["action"] = "read";
    $info["value_read"] = $_SESSION['test_value'] ?? null;
    $info["all_session"] = $_SESSION;
}

// Cek apakah file session-nya benar-benar ada di disk
$sessFile = rtrim($savePath, '/\\') . DIRECTORY_SEPARATOR . 'sess_' . session_id();
$info["session_file_path"] = $sessFile;
$info["session_file_exists"] = file_exists($sessFile);
$info["session_file_readable"] = file_exists($sessFile) ? is_readable($sessFile) : null;
$info["session_file_content"] = file_exists($sessFile) ? @file_get_contents($sessFile) : null;

$info["cookies_received"] = $_COOKIE;

echo json_encode($info, JSON_PRETTY_PRINT);