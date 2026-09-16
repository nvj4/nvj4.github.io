<?php
require_once "koneksi.php";
header("Content-Type: application/json");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? 'read';

try {

    if ($action === 'read') {

        $stmt = $pdo->prepare("SELECT * FROM company_config WHERE id = 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(["success" => true, "data" => null]);
            exit;
        }

        echo json_encode([
            "success" => true,
            "data" => [
                "namaUsaha"   => $row['nama_usaha'],
                "alamat"      => $row['alamat'],
                "kota"        => $row['kota'],
                "telepon"     => $row['telepon'],
                "namaPemilik" => $row['nama_pemilik'],
                "hargaPerCup" => $row['harga_per_cup'],
                "logo"        => $row['logo'] ?: '',
                "ttd"         => $row['ttd']  ?: ''
            ]
        ]);
        exit;
    }

    if ($action === 'save') {

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(["success" => false, "message" => "Data tidak valid"]);
            exit;
        }

        $pdo->exec("INSERT IGNORE INTO company_config (id) VALUES (1)");

        $sql = "UPDATE company_config SET
                    nama_usaha = ?,
                    alamat = ?,
                    kota = ?,
                    telepon = ?,
                    nama_pemilik = ?,
                    harga_per_cup = ?
                WHERE id = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['namaUsaha']   ?? '',
            $input['alamat']      ?? '',
            $input['kota']        ?? '',
            $input['telepon']     ?? '',
            $input['namaPemilik'] ?? '',
            $input['hargaPerCup'] ?? 15000
        ]);

        echo json_encode(["success" => true, "message" => "Konfigurasi disimpan"]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Action tidak dikenal"]);

} catch (Throwable $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}