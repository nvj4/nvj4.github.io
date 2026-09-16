<?php
require_once "koneksi.php";
header("Content-Type: application/json");

// pastikan PDO melempar exception biar error kelihatan, bukan diam-diam gagal
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {

    $type = $_POST['type'] ?? '';

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            "success" => false,
            "message" => "File tidak ditemukan atau gagal diunggah"
        ]);
        exit;
    }

    $file = $_FILES['file'];

    // cek ekstensi
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        echo json_encode([
            "success" => false,
            "message" => "Format harus JPG/JPEG/PNG"
        ]);
        exit;
    }

    // tentukan lokasi berdasarkan jenis
    if ($type === "logo") {
        $folder = __DIR__ . "/../uploads/logo/";
        $urlFolder = "uploads/logo/";
        $filename = "logo." . $ext;
        $column = "logo";
    } elseif ($type === "ttd") {
        $folder = __DIR__ . "/../uploads/ttd/";
        $urlFolder = "uploads/ttd/";
        $filename = "ttd." . $ext;
        $column = "ttd";
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Jenis upload tidak valid"
        ]);
        exit;
    }

    // buat folder kalau belum ada
    if (!is_dir($folder)) {
        if (!mkdir($folder, 0777, true) && !is_dir($folder)) {
            echo json_encode([
                "success" => false,
                "message" => "Gagal membuat folder upload. Cek permission."
            ]);
            exit;
        }
    }

    // hapus file lama dengan ekstensi berbeda (misal ganti dari .png ke .jpg)
    foreach (['jpg', 'jpeg', 'png'] as $oldExt) {
        $oldFile = $folder . ($type === 'logo' ? 'logo' : 'ttd') . '.' . $oldExt;
        if ($oldExt !== $ext && file_exists($oldFile)) {
            @unlink($oldFile);
        }
    }

    $target = $folder . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode([
            "success" => false,
            "message" => "Gagal memindahkan file ke folder upload. Cek permission folder."
        ]);
        exit;
    }

    $dbPath = $urlFolder . $filename;

    // ★ PASTIKAN baris id=1 ada sebelum update
    $pdo->exec("INSERT IGNORE INTO company_config (id) VALUES (1)");

    $sql = "UPDATE company_config
            SET $column = ?, updated_at = NOW()
            WHERE id = 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dbPath]);

    echo json_encode([
        "success" => true,
        "message" => "Upload berhasil",
        "path" => $dbPath,
        "rows_affected" => $stmt->rowCount()
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "message" => "Terjadi kesalahan server: " . $e->getMessage()
    ]);
}