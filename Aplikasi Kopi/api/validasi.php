<?php
require_once "koneksi.php";

header("Content-Type: application/json");

 $action = $_GET['action'] ?? '';

switch ($action) {

  case "create":

    $data = json_decode(file_get_contents("php://input"), true);

    $r2   = $data['r2']   ?? $_POST['r2']   ?? null;   // ★ tidak wajib lagi
    $mae  = $data['mae']  ?? $_POST['mae']  ?? '';
    $mape = $data['mape'] ?? $_POST['mape'] ?? '';

    // ★ hanya mae & mape yang wajib, r2 opsional
    if ($mae === '' || $mape === '') {
        echo json_encode(["success" => false, "message" => "MAE dan MAPE wajib diisi."]);
        exit;
    }

    // ★ kalau r2 kosong/tidak dikirim, simpan sebagai NULL
    $r2Value = ($r2 === null || $r2 === '') ? null : $r2;

    try {

        $stmt = $pdo->prepare("
            INSERT INTO validasi_model (r2, mae, mape)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([$r2Value, $mae, $mape]);

        echo json_encode([
            "success" => true,
            "message" => "Data validasi berhasil disimpan."
        ]);

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

break;

    case "read":

        $stmt = $pdo->query("
            SELECT
                id_validasi AS id,
                r2,
                mae,
                mape,
                created_at
            FROM validasi_model
            ORDER BY created_at DESC
        ");

        echo json_encode([
            "success" => true,
            "data" => $stmt->fetchAll()
        ]);

    break;

    case "detail":

        $id = $_GET['id'] ?? 0;

        $stmt = $pdo->prepare("
            SELECT
                id_validasi AS id,
                r2, mae, mape, created_at
            FROM validasi_model
            WHERE id_validasi = ?
        ");

        $stmt->execute([$id]);

        echo json_encode([
            "success" => true,
            "data" => $stmt->fetch()
        ]);

    break;

    case "update":

        $data = json_decode(file_get_contents("php://input"), true);

        $id   = $data['id'] ?? $_POST['id'] ?? '';
        $r2   = $data['r2'] ?? $_POST['r2'] ?? '';
        $mae  = $data['mae'] ?? $_POST['mae'] ?? '';
        $mape = $data['mape'] ?? $_POST['mape'] ?? '';

        try {

            $stmt = $pdo->prepare("
                UPDATE validasi_model
                SET r2 = ?, mae = ?, mape = ?
                WHERE id_validasi = ?
            ");

            $stmt->execute([$r2, $mae, $mape, $id]);

            echo json_encode([
                "success" => true,
                "message" => "Data berhasil diperbarui."
            ]);

        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }

    break;

    case "delete":

        $id = $_GET['id'] ?? 0;

        try {

            $stmt = $pdo->prepare("DELETE FROM validasi_model WHERE id_validasi = ?");
            $stmt->execute([$id]);

            echo json_encode([
                "success" => true,
                "message" => "Data berhasil dihapus."
            ]);

        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }

    break;

    default:

        echo json_encode([
            "success" => false,
            "message" => "Action tidak ditemukan."
        ]);

    break;

}