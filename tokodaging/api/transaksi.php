<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'koneksi.php';

 $action = isset($_POST['action']) ? $_POST['action'] : '';

// ================= INSERT =================
if ($action == "insert") {

    $tanggal     = $_POST['tanggal'] ?? '';
    $meat_id     = $_POST['meat_type_id'] ?? '';
    $kualitas    = $_POST['kualitas_daging'] ?? 'standar';
    $status_hari = $_POST['status_hari'] ?? 'hari kerja';
    $jumlah      = $_POST['jumlah_kg'] ?? 0;
    $harga       = $_POST['harga'] ?? 0;
    $promo       = $_POST['promo'] ?? 'tidak';
    $metode      = $_POST['metode_penjualan'] ?? 'offline';

    $sql = "INSERT INTO transactions 
            (tanggal, meat_type_id, kualitas_daging, status_hari, jumlah_kg, harga, promo, metode_penjualan) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo "Error prepare: " . $conn->error;
        exit();
    }

    $stmt->bind_param("sisssdss", $tanggal, $meat_id, $kualitas, $status_hari, $jumlah, $harga, $promo, $metode);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error execute: " . $stmt->error;
    }
    $stmt->close();
}

// ================= GET =================
else if ($action == "get") {

    header('Content-Type: application/json');

    $sql = "SELECT t.id, t.tanggal, m.nama_daging, t.kualitas_daging, t.status_hari, 
                   t.jumlah_kg, t.harga, t.promo, t.metode_penjualan
            FROM transactions t
            JOIN meat_types m ON t.meat_type_id = m.id
            ORDER BY t.tanggal DESC";

    $result = $conn->query($sql);
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
}

// ================= DELETE =================
else if ($action == "delete") {

    $id = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "deleted";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// ================= UPDATE =================
else if ($action == "update") {

    $id          = $_POST['id'] ?? 0;
    $tanggal     = $_POST['tanggal'] ?? '';
    $meat_id     = $_POST['meat_type_id'] ?? '';
    $kualitas    = $_POST['kualitas_daging'] ?? 'standar';
    $status_hari = $_POST['status_hari'] ?? 'hari kerja';
    $jumlah      = $_POST['jumlah_kg'] ?? 0;
    $harga       = $_POST['harga'] ?? 0;
    $promo       = $_POST['promo'] ?? 'tidak';
    $metode      = $_POST['metode_penjualan'] ?? 'offline';

    $sql = "UPDATE transactions 
            SET tanggal=?, meat_type_id=?, kualitas_daging=?, status_hari=?, 
                jumlah_kg=?, harga=?, promo=?, metode_penjualan=?
            WHERE id=?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo "Error prepare: " . $conn->error;
        exit();
    }

    $stmt->bind_param("sisssdssi", $tanggal, $meat_id, $kualitas, $status_hari, $jumlah, $harga, $promo, $metode, $id);

    if ($stmt->execute()) {
        echo "updated";
    } else {
        echo "Error execute: " . $stmt->error;
    }
    $stmt->close();
}

// ================= ELSE =================
else {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "No action provided"]);
}

 $conn->close();
?>