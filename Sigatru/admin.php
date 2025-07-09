<?php
session_start();

// Koneksi PDO
$host = 'sql313.infinityfree.com';
$db   = 'if0_39334993_sigatru';
$user = 'if0_39334993';
$pass = 'SigatruPWL01';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8";

try {
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
} catch (PDOException $e) {
  die("Koneksi gagal: " . $e->getMessage());
}

// Hitung jumlah laporan
$jumlahLaporan = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM laporan_warga");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $jumlahLaporan = $row['total'];
    }
} catch (PDOException $e) {
    $jumlahLaporan = 0;
}

// Proses tambah warga
$pesan = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_warga'])) {
    $nama   = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $nik    = $_POST['nik'];
    $tgl    = $_POST['tanggal_lahir'];

    try {
        $stmt = $pdo->prepare("INSERT INTO data_warga (nama, alamat, nik, tanggal_lahir) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $alamat, $nik, $tgl]);
        $pesan = "Data warga berhasil ditambahkan!";
    } catch (PDOException $e) {
        $pesan = "Gagal menambahkan data: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin - SIGATRU</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; background: #f4f6f8; }

    header {
      background:linear-gradient(135deg,#3E001F,#5EABD6);
      color: white;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .logo img {
      height: 40px;
    }

    .menu-toggle {
      display: none;
      font-size: 26px;
      cursor: pointer;
    }

    .container {
      display: flex;
      height: calc(100vh - 70px);
    }

    .sidebar {
      width: 250px;
      background: linear-gradient(135deg,#3E001F,#5EABD6);
      color: white;
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.3s ease-in-out;
    }

    .sidebar .menu {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .sidebar a {
      color: #ecf0f1;
      text-decoration: none;
      font-size: 18px;
      padding: 12px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: background 0.3s;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background: #1abc9c;
    }

    .sidebar a .badge {
      background: red;
      color: white;
      font-size: 12px;
      border-radius: 50%;
      padding: 3px 6px;
      margin-left: auto;
    }

    .content {
      flex: 1;
      padding: 30px;
      overflow-y: auto;
    }

    .hidden { display: none; }

    .alert {
      background: #2ecc71;
      color: white;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 15px;
    }

    form {
      background: white;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #ccc;
      max-width: 500px;
    }

    form input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
    }

    button {
      padding: 10px 15px;
      background: #2980b9;
      color: white;
      border: none;
      cursor: pointer;
      border-radius: 5px;
    }

    .btn-email {
      background: #27ae60;
      margin-top: 20px;
    }

    @media (max-width: 768px) {
      .sidebar {
        position: absolute;
        transform: translateX(-100%);
        z-index: 1000;
        top: 0;
        left: 0;
        height: 100%;
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .menu-toggle {
        display: block;
      }

      .container {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>

<header>
  <div class="logo">
    <img src="biru.png" alt="SIGATRU Logo">
  </div>
  <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
</header>

<div class="container">
  <div class="sidebar" id="sidebar">
    <div class="menu">
      <a href="#" onclick="showPage('beranda', this)" class="active">📊 Beranda</a>
      <a href="#" onclick="showPage('laporan', this)">
        📨 Laporan
        <?php if ($jumlahLaporan > 0): ?>
          <span class="badge">!</span>
        <?php endif; ?>
      </a>
      <a href="#" onclick="showPage('tambah', this)">👥 Tambah Warga</a>
    </div>
    <a href="index.php">🚪 Logout</a>
  </div>

  <div class="content">
    <div id="beranda">
      <h2>📊 Selamat Datang di Dashboard Admin</h2>
      <p>Halo Admin! Berikut beberapa informasi penting:</p>

      <h3 style="margin-top:20px;">📌 Peran Anda:</h3>
      <ul style="margin-left: 20px; margin-top: 10px;">
        <li>🔍 Meninjau dan memverifikasi laporan.</li>
        <li>👥 Menambahkan data warga.</li>
        <li>📩 Menindaklanjuti laporan.</li>
        <li>🧾 Menjaga keakuratan data.</li>
      </ul>

      <h3 style="margin-top:20px;">📈 Statistik:</h3>
      <p>Jumlah laporan masuk: <strong><?= $jumlahLaporan ?></strong></p>
    </div>

    <div id="laporan" class="hidden">
      <h2>📨 Notifikasi Laporan</h2>
      <p>Silakan cek email Anda untuk melihat detail laporan dari warga.</p>
      <a href="https://mail.google.com" target="_blank"><button class="btn-email">📧 Cek Email</button></a>
    </div>

    <div id="tambah" class="hidden">
      <h2>👥 Tambah Data Warga</h2>
      <?php if (!empty($pesan)): ?>
        <div class="alert">✅ <?= $pesan ?></div>
      <?php endif; ?>
      <form method="POST">
        <label>Nama:</label>
        <input type="text" name="nama" required>
        <label>Alamat:</label>
        <input type="text" name="alamat" required>
        <label>NIK:</label>
        <input type="text" name="nik" required>
        <label>Tanggal Lahir:</label>
        <input type="date" name="tanggal_lahir" required>
        <button type="submit" name="tambah_warga">Tambah Warga</button>
      </form>
    </div>
  </div>
</div>

<script>
function showPage(id, el) {
  document.getElementById('beranda').classList.add('hidden');
  document.getElementById('laporan').classList.add('hidden');
  document.getElementById('tambah').classList.add('hidden');
  document.getElementById(id).classList.remove('hidden');

  const links = document.querySelectorAll('.sidebar a');
  links.forEach(link => link.classList.remove('active'));
  el.classList.add('active');

  if (window.innerWidth < 768) toggleSidebar();
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('active');
}
</script>

</body>
</html>