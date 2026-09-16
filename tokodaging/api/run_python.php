<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $python = 'C:\\Users\\dell\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';
    $script = 'C:\\xampp\\htdocs\\tokodaging\\python\\prediksi_naivebayes.py';

    if (!file_exists($python)) throw new Exception("Python tidak ditemukan: $python");
    if (!file_exists($script)) throw new Exception("Script tidak ditemukan: $script");

    $command = escapeshellcmd("$python \"$script\"");
    $output = shell_exec($command . " 2>&1");

    if ($output === null || $output === '') {
        throw new Exception("Python tidak mengeluarkan output");
    }

    // Langsung teruskan output JSON dari Python
    echo $output;

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>