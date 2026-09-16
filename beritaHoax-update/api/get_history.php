<?php
/**
 * FaktaKu - History API (GABUNGAN)
 * Satu file untuk: get, delete, clear
 * Terhubung ke tabel: detection_history (database: hoax_detector_db)
 *
 * Kolom tabel (sesuai phpMyAdmin):
 *   id, user_id, content, detection_result, user_email, date
 *
 * Cara panggil dari front-end:
 *   GET semua riwayat   -> api/get_history.php
 *                        atau api/get_history.php?action=get
 *   DELETE satu riwayat -> api/get_history.php?action=delete   (POST, body: {id: 123})
 *   HAPUS semua riwayat -> api/get_history.php?action=clear    (POST, body: {})
 */

// ── DEBUG MODE: aktifkan sementara untuk lihat error asli.
//    Hapus/comment 2 baris ini kalau sudah production. ──
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/session_init.php';   // ganti session default XAMPP dengan folder custom
require_once __DIR__ . '/koneksi.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ── Cek login ──
if (!isset($_SESSION['user']) || empty($_SESSION['user']['email'])) {
    echo json_encode([
        "success" => false,
        "message" => "Harus login terlebih dahulu",
        "debug_session" => $_SESSION // hapus baris ini setelah debug selesai
    ]);
    exit;
}

$email = $_SESSION['user']['email'];

// Baca body JSON (untuk delete & clear)
$rawBody = file_get_contents("php://input");
$body    = json_decode($rawBody, true) ?? [];

// Tentukan action: dari ?action=... di URL, atau dari body JSON, default 'get'
$action = $_GET['action'] ?? ($body['action'] ?? 'get');

try {

    // ════════════════════════════════════════
    // ACTION: GET — ambil semua riwayat milik user
    // ════════════════════════════════════════
    if ($action === 'get') {

        $stmt = $pdo->prepare(
            "SELECT id, content, detection_result, date
             FROM detection_history
             WHERE user_email = ?
             ORDER BY date DESC, id DESC"
        );
        $stmt->execute([$email]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "data"    => $rows,
            "count"   => count($rows),
            "debug_email" => $email // hapus baris ini setelah debug selesai
        ]);
        exit;
    }

    // ════════════════════════════════════════
    // ACTION: DELETE — hapus satu riwayat berdasarkan id
    // ════════════════════════════════════════
    if ($action === 'delete') {

        $id = isset($body['id']) ? (int) $body['id'] : 0;

        if ($id <= 0) {
            echo json_encode(["success" => false, "message" => "ID riwayat tidak valid"]);
            exit;
        }

        $stmt = $pdo->prepare(
            "DELETE FROM detection_history WHERE id = ? AND user_email = ?"
        );
        $stmt->execute([$id, $email]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(["success" => false, "message" => "Data tidak ditemukan atau bukan milik Anda"]);
            exit;
        }

        echo json_encode(["success" => true, "message" => "Riwayat berhasil dihapus"]);
        exit;
    }

    // ════════════════════════════════════════
    // ACTION: CLEAR — hapus semua riwayat milik user
    // ════════════════════════════════════════
    if ($action === 'clear') {

        $stmt = $pdo->prepare("DELETE FROM detection_history WHERE user_email = ?");
        $stmt->execute([$email]);

        echo json_encode([
            "success" => true,
            "message" => "Semua riwayat berhasil dihapus",
            "deleted" => $stmt->rowCount()
        ]);
        exit;
    }

    // Action tidak dikenali
    echo json_encode(["success" => false, "message" => "Action tidak dikenali: $action"]);

} catch (Exception $e) {
    // Selama masih debug, tampilkan pesan error asli supaya kelihatan
    // kolom/tabel mana yang bermasalah. Nanti ganti jadi pesan generik.
    echo json_encode([
        "success" => false,
        "message" => "Terjadi kesalahan pada server",
        "debug_error" => $e->getMessage() // hapus baris ini setelah debug selesai
    ]);
}