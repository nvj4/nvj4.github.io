<?php
require_once "koneksi.php";
header("Content-Type: application/json");

 $action = $_GET['action'] ?? '';

if ($action == "create") {

    $data = json_decode(file_get_contents("php://input"), true);

    $tanggal         = $data['tanggal'] ?? $_POST['tanggal'] ?? '';
    $jumlah_penjualan = $data['jumlah_penjualan'] ?? $data['penjualan'] ?? $_POST['jumlah_penjualan'] ?? $_POST['penjualan'] ?? '';
    $cuaca           = $data['cuaca'] ?? $_POST['cuaca'] ?? '';
    $hari_gajian     = $data['hari_gajian'] ?? $_POST['hari_gajian'] ?? '';
    $promosi         = $data['promosi'] ?? $_POST['promosi'] ?? '';
    $daring          = $data['persen_daring'] ?? $data['daring'] ?? $_POST['persen_daring'] ?? $_POST['daring'] ?? '';

    if ($tanggal == "" || $jumlah_penjualan == "") {
        echo json_encode(["success" => false, "message" => "Tanggal dan jumlah penjualan wajib diisi."]);
        exit;
    }

    /* Daring: JS kirim 0.35, kalau > 1 artinya persen */
    $x4 = ($daring > 1) ? ($daring / 100) : (float)$daring;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO penjualan (tanggal, jumlah_penjualan, cuaca, hari_gajian, promosi, persen_daring)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tanggal, $jumlah_penjualan, $cuaca, $hari_gajian, $promosi, $x4]);

        echo json_encode(["success" => true, "message" => "Penjualan berhasil disimpan."]);

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

}

elseif ($action == "read") {

    $stmt = $pdo->query("
        SELECT
            id_penjualan AS id,
            tanggal,
            jumlah_penjualan AS penjualan,
            cuaca,
            hari_gajian,
            promosi,
            persen_daring AS daring
        FROM penjualan
        ORDER BY tanggal DESC
    ");

    echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);

}

else {
    echo json_encode(["success" => false, "message" => "Action tidak ditemukan."]);
}