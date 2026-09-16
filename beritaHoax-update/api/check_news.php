<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/koneksi.php';
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// Ambil input JSON
$req = json_decode(file_get_contents("php://input"), true);
$text = $req['content'] ?? '';
$source = $req['source'] ?? '';
$finalText = $text ?: $source;

if (empty($finalText)) {
    echo json_encode([
        "success" => false,
        "message" => "Konten berita kosong"
    ]);
    exit;
}

try {

    // 1️⃣ Simpan berita ke tabel news_articles
    $stmt = $pdo->prepare("INSERT INTO news_articles (content) VALUES (?)");
    $stmt->execute([$finalText]);
    $lastId = $pdo->lastInsertId();

    // 2️⃣ Path Python & Script
    $pythonPath = "C:\\Users\\dell\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";

    // naik 1 folder dari api ke root
    $scriptPath = dirname(__DIR__) . "\\python\\predict.py";

    // Validasi file
    if (!file_exists($pythonPath)) {
        throw new Exception("Python tidak ditemukan di: " . $pythonPath);
    }

    if (!file_exists($scriptPath)) {
        throw new Exception("predict.py tidak ditemukan di: " . $scriptPath);
    }

    // 3️⃣ Kirim parameter ke Python
    $escapedId = escapeshellarg($lastId);

    // sementara pakai user_id statis (bisa diganti dengan session nanti)
    $userId = 1;

    $cmd = "\"$pythonPath\" \"$scriptPath\" $escapedId $userId 2>&1";

    $pythonOutput = shell_exec($cmd);

    if ($pythonOutput === null) {
        throw new Exception("Python tidak mengembalikan output.");
    }

    // 4️⃣ Ambil hasil terakhir (hoax / fakta)
    $outputLines = explode("\n", trim($pythonOutput));
    $result = strtolower(trim(end($outputLines)));

    if ($result === 'hoax' || $result === 'fakta') {

        echo json_encode([
            "success" => true,
            "data" => [
                "result" => $result,
                "message" => ($result === 'hoax')
                    ? "Berita terindikasi HOAX"
                    : "Berita AMAN (FAKTA)"
            ]
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Python Execution Error",  
            "debug" => $pythonOutput
        ]);
    }

} catch (Throwable $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}