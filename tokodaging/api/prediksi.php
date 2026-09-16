<?php
/**
 * prediksi.php
 * Hanya bertugas MENGAMBIL data hasil prediksi (untuk ditampilkan di tabel).
 * Perhitungan prediksi sekarang dilakukan di naive_bayes.php (PHP, tanpa Python).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'koneksi.php'; // harus menghasilkan $conn (mysqli)

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// ================= AMBIL HASIL PREDIKSI =================
if ($action == "get") {

    $sql = "SELECT p.id, p.tanggal, m.nama_daging, p.prediksi_kg, p.akurasi, p.kategori
            FROM predictions p
            JOIN meat_types m ON p.meat_type_id = m.id
            ORDER BY p.tanggal ASC";

    $result = $conn->query($sql);
    $data = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    echo json_encode($data);

} else {
    echo json_encode(["status" => "error", "message" => "Gunakan action=get untuk mengambil data prediksi. Untuk menghitung prediksi baru, panggil naive_bayes.php dengan action=run."]);
}

$conn->close();