<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sigatru";
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Koneksi gagal: " . mysqli_connect_error());

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "Akses ditolak. Halaman ini hanya untuk Admin.";
    exit();
}

$cekLaporan = mysqli_query($conn, "SELECT COUNT(*) as total FROM laporan_warga");
$jumlahLaporan = 0;
if ($cekLaporan && $row = mysqli_fetch_assoc($cekLaporan)) {
    $jumlahLaporan = $row['total'];
}

$pesan = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_warga'])) {
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];
    $nik = $_POST['nik'];
    $tgl = $_POST['tanggal_lahir'];

    $stmt = mysqli_prepare($conn, "INSERT INTO data_warga (nama, alamat, nik, tanggal_lahir) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $nama, $alamat, $nik, $tgl);
    mysqli_stmt_execute($stmt);
    $pesan = "Data warga berhasil ditambahkan!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin - SIGATRU</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; background: #f4f6f8; }

    header {
      background: #2c3e50; color: white; padding: 20px;
      display: flex; justify-content: space-between; align-items: center;
    }

    .menu-toggle {
      display: none;
      font-size: 24px; cursor: pointer;
    }

    .container {
      display: flex;
      height: 100vh;
    }

    .sidebar {
      width: 250px;
      background: linear-gradient(to bottom, #2c3e50, #34495e);
      color: white;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 15px;
      transition: transform 0.3s ease-in-out;
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
      color: white;
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
      background: #2ecc71; color: white; padding: 10px;
      border-radius: 5px; margin-bottom: 15px;
    }

    form {
      background: white; padding: 20px; border-radius: 8px;
      border: 1px solid #ccc; max-width: 500px;
    }

    form input {
      width: 100%; padding: 10px; margin: 10px 0;
    }

    button {
      padding: 10px 15px; background: #2980b9;
      color: white; border: none; cursor: pointer;
      border-radius: 5px;
    }

    .btn-email {
      background: #27ae60; margin-top: 20px;
    }

    @media (max-width: 768px) {
      .sidebar {
        position: absolute;
        transform: translateX(-100%);
        z-index: 1000;
        top: 0; left: 0; height: 100%;
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .menu-toggle {
        display: block;
        color: white;
      }

      .container {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>

<header>
  <h1>Dashboard Admin - SIGATRU</h1>
  <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
</header>

<div class="container">
  <div class="sidebar" id="sidebar">
    <a href="#" onclick="showPage('beranda', this)" class="active">📊 Beranda</a>
    <a href="#" onclick="showPage('laporan', this)">
      📨 Laporan
      <?php if ($jumlahLaporan > 0): ?>
        <span class="badge">!</span>
      <?php endif; ?>
    </a>
    <a href="#" onclick="showPage('tambah', this)">👥 Tambah Warga</a>
    <a href="login.php">🚪 Logout</a>
  </div>

  <div class="content">
    <div id="beranda">
      <h2>📊 Selamat Datang di Dashboard Admin</h2>
      <p>Halo Admin! Berikut beberapa informasi yang berkaitan dengan peran Anda dalam sistem SIGATRU:</p>

      <h3 style="margin-top:20px;">📌 Peran Anda:</h3>
      <ul style="margin-left: 20px; margin-top: 10px;">
        <li>🔍 Meninjau dan memverifikasi laporan yang dikirim oleh warga.</li>
        <li>👥 Menambahkan dan memperbarui data warga ke dalam sistem.</li>
        <li>📩 Menindaklanjuti laporan melalui email atau kunjungan lapangan.</li>
        <li>🧾 Memastikan data dalam sistem selalu akurat dan terkini.</li>
      </ul>

      <h3 style="margin-top:20px;">📈 Statistik Singkat:</h3>
      <p>Jumlah laporan yang masuk saat ini: <strong><?= $jumlahLaporan ?></strong></p>

      <h3 style="margin-top:20px;">🚀 Akses Cepat:</h3>
      <ul style="margin-left: 20px; margin-top: 10px;">
        <li><strong>Laporan:</strong> Klik menu <em>Laporan</em> untuk melihat notifikasi dan cek email Anda.</li>
        <li><strong>Tambah Warga:</strong> Gunakan menu <em>Tambah Warga</em> untuk input data baru dengan cepat.</li>
        <li><strong>Logout:</strong> Selesai bekerja? Klik menu <em>Logout</em> untuk keluar dengan aman.</li>
      </ul>

      <p style="margin-top: 30px;">Terima kasih atas kerja keras Anda menjaga ketertiban dan keakuratan data warga melalui SIGATRU! 🙌</p>
    </div>

    <div id="laporan" class="hidden">
      <h2>📨 Notifikasi Laporan</h2>
      <p>Ada warga yang mengirimkan laporan. Silakan cek email Anda untuk melihat detailnya.</p>
      <a href="https://mail.google.com" target="_blank"><button class="btn-email">📧 Cek Email</button></a>
    </div>

    <div id="tambah" class="hidden">
      <h2>👥 Tambah Data Warga</h2>
      <?php if (!empty($pesan)): ?><div class="alert">✅ <?= $pesan ?></div><?php endif; ?>
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

  // highlight menu aktif
  const links = document.querySelectorAll('.sidebar a');
  links.forEach(link => link.classList.remove('active'));
  el.classList.add('active');

  // close sidebar on mobile
  if (window.innerWidth < 768) toggleSidebar();
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('active');
}
</script>

</body>
</html>
