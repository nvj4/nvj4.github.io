<?php
include 'koneksi.php';

// ambil data dari form (AMANKAN)
$nama      = $_POST['nama'] ?? '';
$no_hp     = $_POST['no_hp'] ?? '';
$email     = $_POST['email'] ?? '';
$id_dokter = $_POST['id_dokter'] ?? '';
$tanggal   = $_POST['tanggal'] ?? '';
$jam       = $_POST['jam'] ?? '';
$keluhan   = $_POST['keluhan'] ?? '';

// 🔥 FIX FORMAT JAM (biar cocok DB)
if (strlen($jam) == 5) {
    $jam = $jam . ":00";
}

// ===========================
// CEK JADWAL SUDAH ADA / BELUM
// ===========================
$cek = mysqli_query($koneksi, "SELECT * FROM booking 
WHERE tanggal='$tanggal' 
AND jam='$jam' 
AND id_dokter='$id_dokter'");

if (mysqli_num_rows($cek) > 0) {

    echo "<h3>Jadwal yang dipilih sudah penuh!</h3>";

    $semua_jam = ["08:00:00", "10:00:00", "13:00:00", "15:00:00"];

    $data = mysqli_query($koneksi, "SELECT jam FROM booking 
    WHERE tanggal='$tanggal' 
    AND id_dokter='$id_dokter'");

    $jam_terisi = [];
    while ($row = mysqli_fetch_assoc($data)) {
        $jam_terisi[] = $row['jam'];
    }

    $jam_kosong = array_diff($semua_jam, $jam_terisi);

    if (!empty($jam_kosong)) {
        echo "Coba pilih jam berikut:<br>";
        foreach ($jam_kosong as $j) {
            echo "- " . substr($j, 0, 5) . "<br>";
        }
    } else {
        echo "Semua jadwal hari ini sudah penuh.";
    }

    exit;
}

// ===========================
// SIMPAN KE DATABASE
// ===========================
$query = mysqli_query($koneksi, "INSERT INTO booking 
(nama_pasien, no_hp, email, id_dokter, tanggal, jam, keluhan_catatan, status)
VALUES 
('$nama','$no_hp','$email','$id_dokter','$tanggal','$jam','$keluhan','pending')");

if (!$query) {
    echo "<p style='color:red;'>DB ERROR: " . mysqli_error($koneksi) . "</p>";
    exit;
}

echo "<h3>Booking berhasil!</h3>";

// ===========================
// KIRIM KE WEBHOOK N8N
// ===========================

// 🔥 PAKAI 127.0.0.1 ATAU NGROK
$webhook_url = "http://127.0.0.1:5678/webhook/booking-baru";

// 🔥 DATA LENGKAP
$data_webhook = [
    "nama_pasien"     => $nama,
    "no_hp"           => $no_hp,
    "email"           => $email,
    "id_dokter"       => $id_dokter,
    "tanggal"         => $tanggal,
    "jam"             => $jam,
    "keluhan_catatan" => $keluhan
];

$payload = json_encode($data_webhook);

// ===========================
// CURL REQUEST
// ===========================
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $webhook_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($curl);

// ===========================
// DEBUG CURL
// ===========================
if (curl_errno($curl)) {
    echo "<p style='color:red;'>CURL ERROR: " . curl_error($curl) . "</p>";
} else {
    echo "<p style='color:green;'>Webhook Response: " . htmlspecialchars($response) . "</p>";
}

curl_close($curl);

// ===========================
// OUTPUT AKHIR
// ===========================
echo "<p>Pesan sedang diproses oleh sistem WhatsApp...</p>";
echo "<a href='index.html'>Kembali</a>";
?>