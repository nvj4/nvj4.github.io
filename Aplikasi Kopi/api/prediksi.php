<?php
require_once "koneksi.php";

header("Content-Type: application/json");

 $action = $_GET['action'] ?? '';

if ($action == "create") {

    $data = json_decode(file_get_contents("php://input"), true);

    /* Ambil dari JSON body, fallback ke POST, fallback ke hari ini */
    $tanggal     = $data['tanggal_prediksi'] ?? $data['tanggal'] ?? $_POST['tanggal_prediksi'] ?? $_POST['tanggal'] ?? date('Y-m-d');
    $cuaca       = $data['cuaca'] ?? $_POST['cuaca'] ?? '';
    $hari_gajian = $data['hari_gajian'] ?? $_POST['hari_gajian'] ?? '';
    $promosi     = $data['promosi'] ?? $_POST['promosi'] ?? '';
    $daring      = $data['persen_daring'] ?? $data['daring'] ?? $_POST['persen_daring'] ?? $_POST['daring'] ?? '';

    if ($tanggal == "" || $cuaca === "" || $hari_gajian === "" || $promosi === "" || $daring === "") {
        echo json_encode(["success" => false, "message" => "Semua data wajib diisi."]);
        exit;
    }

    /* Konversi ke angka: handle format teks dari forecast & format angka dari dashboard */
    $x1 = ($cuaca === "Cerah" || $cuaca === 1 || $cuaca === "1") ? 1 : 0;
    $x2 = ($hari_gajian === "Ya" || $hari_gajian == 1 || $hari_gajian === "1") ? 1 : 0;
    $x3 = ($promosi === "Ya" || $promosi == 1 || $promosi === "1") ? 1 : 0;

    /* Daring: dashboard kirim 0.35, forecast kirim 35, sama-sama jadikan 0-1 */
    $x4 = ($daring > 1) ? ($daring / 100) : (float)$daring;

    /* Persamaan Regresi */
    $hasil = 8.5405 + (3.9936 * $x1) + (3.7708 * $x2) + (14.8803 * $x3) + (16.9990 * $x4);
    $hasil = round($hasil, 2);

    try {

        $stmt = $pdo->prepare("
            INSERT INTO prediksi
                (tanggal_prediksi, cuaca, hari_gajian, promosi, persen_daring, hasil_prediksi)
            VALUES
                (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([$tanggal, $x1, $x2, $x3, $x4, $hasil]);

        echo json_encode([
            "success" => true,
            "message" => "Prediksi berhasil disimpan.",
            "data" => [
                "id" => $pdo->lastInsertId(),
                "tanggal" => $tanggal,
                "cuaca" => $x1,
                "hari_gajian" => $x2,
                "promosi" => $x3,
                "daring" => $x4,
                "hasil_prediksi" => $hasil
            ]
        ]);

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

}

elseif ($action == "read") {

    $stmt = $pdo->query("
        SELECT
            id_prediksi AS id,
            tanggal_prediksi AS tanggal,
            cuaca,
            hari_gajian,
            promosi,
            persen_daring AS daring,
            hasil_prediksi
        FROM prediksi
        ORDER BY tanggal_prediksi DESC
    ");

    echo json_encode([
        "success" => true,
        "data" => $stmt->fetchAll()
    ]);

}

elseif ($action == "delete") {

    $id = $_GET['id'] ?? 0;

    $stmt = $pdo->prepare("DELETE FROM prediksi WHERE id_prediksi = ?");
    $stmt->execute([$id]);

    echo json_encode([
        "success" => true,
        "message" => "Data berhasil dihapus."
    ]);

}

else {

    echo json_encode([
        "success" => false,
        "message" => "Action tidak ditemukan."
    ]);

}