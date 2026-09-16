<?php
/**
 * historical_stats.php
 * Membaca file JSON/JSONL historis (data lama, TIDAK ada di database) dan mengembalikan
 * statistik ringkas (jumlah transaksi, total kg) supaya bisa digabung manual dengan
 * statistik dari tabel `transactions` di sisi frontend (Dashboard).
 *
 * TIDAK menyentuh database sama sekali -- murni baca file.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Path harus SAMA PERSIS dengan yang dipakai di naive_bayes.php
define('HISTORICAL_DATA_FILE_JSON', __DIR__ . '/data/historical_transactions.json');
define('HISTORICAL_DATA_FILE_JSONL', __DIR__ . '/data/historical_transactions.jsonl');

function loadHistoricalRawRecords(): array
{
    $filePath = null;
    if (file_exists(HISTORICAL_DATA_FILE_JSON)) {
        $filePath = HISTORICAL_DATA_FILE_JSON;
    } elseif (file_exists(HISTORICAL_DATA_FILE_JSONL)) {
        $filePath = HISTORICAL_DATA_FILE_JSONL;
    }

    if ($filePath === null) {
        return [];
    }

    $raw = file_get_contents($filePath);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $trimmed = trim($raw);
    $records = [];

    $decoded = json_decode($trimmed, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $records = isset($decoded[0]) ? $decoded : [$decoded];
    } else {
        $lines = preg_split('/\r\n|\r|\n/', $trimmed);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $obj = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $records[] = $obj;
            }
        }
    }

    return $records;
}

$records = loadHistoricalRawRecords();

$totalTransaksi = 0;
$totalKg = 0.0;

foreach ($records as $r) {
    if (isset($r['jumlah_kg']) && is_numeric($r['jumlah_kg'])) {
        $totalTransaksi++;
        $totalKg += (float) $r['jumlah_kg'];
    }
}

echo json_encode([
    "status" => "success",
    "sumber" => count($records) > 0 ? "file_historis" : "tidak_ada_file",
    "total_transaksi" => $totalTransaksi,
    "total_kg" => round($totalKg, 2),
]);