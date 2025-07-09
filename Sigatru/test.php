<?php
session_start();

// Koneksi PDO ke database hosting InfinityFree
$host = 'sql313.infinityfree.com';
$db   = 'if0_39334993_sigatru';
$user = 'if0_39334993';
$pass = 'SigatruPWL01';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8";

try {
  $pdo = new PDO($dsn, $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("❌ Koneksi database gagal: " . $e->getMessage());
}

// Proses tambah warga
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_warga'])) {
  $nik    = $_POST['nik'];
  $nama   = $_POST['nama'];
  $alamat = $_POST['alamat'];
  $rt     = $_POST['rt'];
  $rw     = $_POST['rw'];
  $tgl    = $_POST['tanggal_lahir'];

  try {
    $stmt = $pdo->prepare("INSERT INTO warga (NIK, Nama, Alamat, RT, RW, `Tanggal Lahir`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nik, $nama, $alamat, $rt, $rw, $tgl]);
    $pesan = "✅ Data warga berhasil ditambahkan!";
  } catch (PDOException $e) {
    $pesan = "❌ Gagal menambahkan data: " . $e->getMessage();
  }
}

$jumlahLaporan = 8;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Admin SIGATRU</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background:linear-gradient(135deg, #3E001F, #5EABD6);
    }

   .burger-menu {
  position: fixed;
  top: 10px;
  left: 10px;
  z-index: 2000;
  background: #3b82f6;
  color: white;
  padding: 10px 16px;
  font-size: 20px;
  cursor: pointer;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  display: none;
   }


    .container { flex: 1; display: flex; }

    .logo-container { text-align: center; padding: 10px; }
    .logo { max-width: 120px; height: auto; }

    .sidebar {
      width: 250px;
      background: linear-gradient(135deg, #3E001F, #5EABD6);
      color: white;
      padding: 20px;
      display: flex;
      flex-direction: column;
      transition: left 0.3s ease;
      overflow-y: auto;
    }

    .sidebar h2 { text-align: center; margin-bottom: 30px; }
    .sidebar a {
      padding: 12px 18px;
      margin-bottom: 12px;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: bold;
      display: block;
    }

    .sidebar a:hover, .sidebar a.active {
      background-color: #10b981;
    }

    .logout {
      margin-top: auto;
    }

    .main {
      flex: 1;
      padding: 30px;
      background: linear-gradient(to bottom right, #e0f2fe, #f9fafb);
      overflow-y: auto;
    }

    .welcome-banner {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, #3E001F, #5EABD6);
      padding: 30px;
      color: white;
      border-radius: 12px;
      margin-bottom: 30px;
    }

    .highlight-box {
      background: #fff;
      padding: 15px 20px;
      border-left: 5px solid #f59e0b;
      border-radius: 8px;
      margin-bottom: 25px;
    }

    .quick-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 20px;
    }

    .info-box {
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .info-box span {
      font-size: 22px;
      font-weight: bold;
      color: #3b82f6;
    }

    .card-grid-wrapper {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 40px;
    }

    .card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .card img {
      width: 100%;
      height: 150px;
      object-fit: cover;
      margin-bottom: 15px;
      border-radius: 8px;
    }

    .hidden { display: none; }

    form {
      display: flex;
      flex-direction: column;
      gap: 10px;
      background: #fff;
      padding: 0px;
      border-radius: 8px;
      max-width: 500px;
    }

    form input, form button {
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    form button {
      background: #3b82f6;
      color: white;
      border: none;
      font-weight: bold;
    }

    .alert {
      background: #d1fae5;
      padding: 10px;
      margin-bottom: 15px;
      border-left: 4px solid #10b981;
      border-radius: 4px;
    }

    @media (max-width: 768px) {
      .burger-menu {
        display: block;
      }

      .container {
        flex-direction: column;
      }

      .sidebar {
        position: fixed;
        top: 60px;
        left: -100%;
        width: 70%;
        height: calc(100vh - 60px);
        z-index: 1000;
        border-right: 1px solid #ccc;
        background: linear-gradient(to bottom, #3b0764, #3b82f6);
      }

      .sidebar.show {
        left: 0;
      }

      .main {
        margin-top: 60px;
        padding: 20px;
      }

      .welcome-banner {
        flex-direction: column;
        text-align: center;
      }

      .quick-info {
        grid-template-columns: 1fr 1fr;
      }

      .card-grid-wrapper {
        grid-template-columns: 1fr;
      }

      .sidebar .logout {
        margin-top: 20px;
      }

      form {
        width: 100%;
        max-width: 100%;
      }
    }
  </style>
</head>
<body>
<div class="burger-menu" onclick="toggleSidebar()">☰</div>

<div class="container">
  <div class="sidebar" id="sidebar">
    <div class="logo-container">
      <img src="biru.png" alt="SIGATRU Logo" class="logo">
    </div>
    <a href="#" class="active" onclick="showPage('beranda', this)">🏠 Beranda</a>
    <a href="#" onclick="showPage('laporan', this)">📃 Laporan</a>
    <a href="#" onclick="showPage('tambah', this)">👥 Tambah Warga</a>
    <div class="logout">
      <a href="index.php" style="background-color:#f59e0b; color:black;">🔒 Logout</a>
    </div>
  </div>

  <div class="main">
    <!-- Beranda -->
    <div id="beranda">
      <div class="welcome-banner">
        <div>
          <h1>Selamat Datang, Admin!</h1>
          <p>Kelola data warga dan laporan dengan lebih efisien dan terstruktur.</p>
        </div>
      </div>

      <div class="highlight-box">
        <p>📅 Rutin cek laporan dan data warga untuk menjaga keakuratan informasi sistem.</p>
      </div>

      <div class="quick-info">
        <div class="info-box"><h4>📝 Total Laporan</h4><span><?= $jumlahLaporan ?></span></div>
        <div class="info-box"><h4>✅ Selesai</h4><span>5</span></div>
        <div class="info-box"><h4>⏳ Diproses</h4><span>3</span></div>
        <div class="info-box"><h4>👤 Warga Terdaftar</h4><span>57</span></div>
      </div>

      <div class="card-grid-wrapper">
        <div class="card"><h3>👤 Peran Anda</h3><p>Memverifikasi laporan, menambahkan data warga, dan menjaga keakuratan sistem.</p></div>
        <div class="card"><h3>📊 Statistik</h3><p>Laporan masuk: <?= $jumlahLaporan ?><br>Selesai: 5<br>Diproses: 3</p></div>
        <div class="card"><h3>📝 Aktivitas Terakhir</h3><p>✔ Laporan 003 disetujui<br>✔ Warga baru ditambahkan<br>✔ Laporan 004 diproses</p></div>
        <div class="card"><h3>📢 Pengumuman</h3><p>Rapat RT 5 Juli 2025 pukul 19.00 WIB di Balai Warga.</p></div>
      </div>
    </div>

    <!-- Laporan -->
    <div id="laporan" class="hidden">
      <h2>📬 Notifikasi Laporan</h2>
      <p>Silakan cek email Anda untuk melihat detail laporan dari warga.</p>
      <a href="https://mail.google.com/" target="_blank"><button>📧 Cek Email</button></a>
    </div>

    <!-- Tambah Warga -->
    <div id="tambah" class="hidden">
      <h2>👥 Tambah Data Warga</h2>
      <?php if (!empty($pesan)): ?>
        <div class="alert"><?= $pesan ?></div>
      <?php endif; ?>
      <form method="POST">
        <label>NIK:</label>
        <input type="number" name="nik" required>
        <label>Nama:</label>
        <input type="text" name="nama" required>
        <label>Alamat:</label>
        <input type="text" name="alamat" required>
        <label>RT:</label>
        <input type="number" name="rt" required>
        <label>RW:</label>
        <input type="number" name="rw" required>
        <label>Tanggal Lahir:</label>
        <input type="date" name="tanggal_lahir" required>
        <button type="submit" name="tambah_warga">Tambah Warga</button>
      </form>
    </div>
  </div>
</div>

<script>
function showPage(id, el) {
  const pages = ['beranda', 'laporan', 'tambah'];
  pages.forEach(p => document.getElementById(p).classList.add('hidden'));
  document.getElementById(id).classList.remove('hidden');

  const links = document.querySelectorAll('.sidebar a');
  links.forEach(link => link.classList.remove('active'));
  el.classList.add('active');

  if (window.innerWidth <= 768) {
    document.getElementById('sidebar').classList.remove('show');
  }
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('show');
}
</script>
</body>
</html>
