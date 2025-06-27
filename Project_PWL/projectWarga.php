<?php
session_start();

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sigatru";
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Koneksi gagal: " . mysqli_connect_error());

// Simulasi login otomatis
if (!isset($_SESSION['username'])) {
  $_SESSION['username'] = "wargaRT";
}

// Logout
if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SIGATRU - Sistem Kependudukan RT 01</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #1a1a1d, #2c3e50);
      color: #e0e0e0;
      margin: 0;
      padding: 0 20px;
    }
    header {
      background: #1f2a35;
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 3px 8px rgba(0,0,0,0.3);
    }
    .logo-area h1 {
      margin: 0;
      font-size: 1.8em;
      color: #f1c40f;
    }
    .logo-area h2 {
      margin: 0;
      font-size: 1em;
      color: #ccc;
      font-weight: normal;
    }
    nav ul {
      display: flex;
      gap: 15px;
      list-style: none;
      margin: 0;
      padding: 0;
      align-items: center;
    }
    nav a {
      color: #f1c40f;
      text-decoration: none;
      font-weight: bold;
      padding: 6px 12px;
      border-radius: 5px;
      transition: background 0.3s;
    }
    nav a:hover {
      background: #34495e;
    }
    nav a.logout-link {
      background-color: #e74c3c;
      color: white;
      padding: 6px 14px;
      font-weight: bold;
      border-radius: 6px;
    }
    nav a.logout-link:hover {
      background-color: #c0392b;
    }
    section {
      display: none;
      background: #2c3e50;
      padding: 20px;
      margin-top: 15px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    section.active {
      display: block;
    }
    input, textarea, select, button {
      width: 100%;
      padding: 10px;
      margin-bottom: 12px;
      background: #1f2a35;
      color: #eee;
      border: 1px solid #444;
      border-radius: 6px;
      box-sizing: border-box;
    }
    input:focus, textarea:focus, select:focus {
      border-color: #f1c40f;
      outline: none;
      box-shadow: 0 0 6px rgba(241, 196, 15, 0.3);
    }
    button {
      background: #f1c40f;
      color: #222;
      border: none;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s;
    }
    button:hover {
      background: #d4ac0d;
    }
    footer {
      margin-top: 30px;
      padding: 20px;
      text-align: center;
      background: #1f2a35;
      color: #aaa;
      font-size: 0.9em;
      border-top: 1px solid #444;
    }
    h2, h3 {
      color: #f1f1f1;
    }
    .card-laporan {
      background: #34495e;
      border-radius: 10px;
      padding: 15px;
      flex: 1;
      min-width: 200px;
      cursor: pointer;
      transition: transform 0.3s, background 0.3s;
      border: 2px solid transparent;
      text-align: center;
    }
    .card-laporan:hover {
      background: #3d566e;
      transform: translateY(-3px);
      border-color: #f1c40f;
    }
    .btn-link {
      padding:10px 15px;
      border-radius: 5px;
      text-decoration: none;
      margin-right: 10px;
      display: inline-block;
    }
    .btn-yellow { background: #f1c40f; color: #000; }
    .btn-blue { background: #3498db; color: #fff; }
    @media screen and (max-width: 768px) {
      header { flex-direction: column; align-items: flex-start; }
      nav ul { flex-direction: column; align-items: flex-start; margin-top: 10px; }
    }
  </style>
</head>
<body>

<header>
  <div class="logo-area">
    <h1>SIGATRU</h1>
    <h2>Sistem Kependudukan RT 01</h2>
  </div>
  <nav>
    <ul>
      <li><a href="#" onclick="tampilkan('beranda')">Beranda</a></li>
      <li><a href="#" onclick="tampilkan('dataTamu')">Data Tamu</a></li>
      <li><a href="#" onclick="tampilkan('laporan')">Laporan</a></li>
      <?php if (isset($_SESSION['username'])): ?>
        <li><a href="?logout" class="logout-link">Logout</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>

<!-- Beranda -->
<section id="beranda" class="active">
  <h2>Selamat Datang di Website RT 01</h2>
  <p>Lingkungan RT 01 adalah tempat tinggal yang nyaman dan aman dengan komunitas yang aktif dan peduli.</p>

  <!-- Card statistik -->
  <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
    <div class="card-laporan">
      <h3>124</h3><p>Total Warga</p>
    </div>
    <div class="card-laporan">
      <h3>42</h3><p>Kartu Keluarga</p>
    </div>
    <div class="card-laporan">
      <h3>65</h3><p>Laki-laki</p>
    </div>
    <div class="card-laporan">
      <h3>59</h3><p>Perempuan</p>
    </div>
  </div>

  <!-- Agenda -->
  <div style="margin-top: 30px;">
    <h3>Agenda Terdekat</h3>
    <ul>
      <li>📢 Rapat RT: 30 Juni 2025, pukul 19.00 WIB di Balai Warga</li>
      <li>🧹 Kerja Bakti: 6 Juli 2025, pukul 07.00 WIB</li>
    </ul>
  </div>

  <!-- Tombol Akses Cepat -->
  <div style="margin-top: 30px;">
    <a href="#" onclick="tampilkan('laporan')" class="btn-link btn-yellow">Laporkan Sesuatu</a>
    <a href="#" onclick="tampilkan('dataTamu')" class="btn-link btn-blue">Isi Buku Tamu</a>
  </div>
</section>

<!-- Data Tamu -->
<section id="dataTamu">
  <h2>Form Tamu (Data Dikirim ke Email)</h2>
  <form action="https://formsubmit.co/pippervajo06@gmail.com" method="POST" target="_blank">
    <input type="hidden" name="_subject" value="Data Kunjungan Tamu RT01">
    <input type="hidden" name="_captcha" value="false">
    <input type="hidden" name="_template" value="table">

    <label>Nama Tamu</label>
    <input type="text" name="Nama Tamu" required>
    <label>Tujuan</label>
    <input type="text" name="Tujuan" required>
    <label>Waktu Kunjungan</label>
    <input type="datetime-local" name="Waktu Kunjungan" required>
    <button type="submit">Simpan & Kirim</button>
  </form>
</section>

<!-- Laporan -->
<section id="laporan">
  <h2>Form Laporan Warga</h2>
  <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
    <div class="card-laporan" onclick="isiTemplate('kehilangan')">
      <h3>Kehilangan</h3><p>Lapor kehilangan KTP, KK, dll.</p>
    </div>
    <div class="card-laporan" onclick="isiTemplate('usaha')">
      <h3>Izin Usaha</h3><p>Ajukan izin membuka usaha rumahan.</p>
    </div>
    <div class="card-laporan" onclick="isiTemplate('kegiatan')">
      <h3>Kegiatan</h3><p>Laporan kegiatan masyarakat di lingkungan RT.</p>
    </div>
  </div>

  <form action="https://formsubmit.co/pippernavajo06@gmail.com" method="POST" target="_blank">
    <input type="hidden" name="_subject" value="Laporan dari Warga RT01">
    <input type="hidden" name="_captcha" value="false">
    <input type="hidden" name="_template" value="table">

    <label>Nama</label>
    <input type="text" name="Nama" required>
    <label>Alamat</label>
    <input type="text" name="Alamat" required>
    <label>Isi Laporan</label>
    <textarea name="Isi Laporan" rows="5" required></textarea>
    <button type="submit">Kirim Laporan</button>
  </form>
</section>

<footer>
  <p>&copy; 2025 RT 01 - Sistem Kependudukan</p>
</footer>

<script>
function tampilkan(id) {
  document.querySelectorAll("section").forEach(s => s.classList.remove("active"));
  document.getElementById(id).classList.add("active");
}
function isiTemplate(jenis) {
  let textarea = document.querySelector('#laporan textarea[name="Isi Laporan"]');
  if (jenis === 'kehilangan') {
    textarea.value = `Saya ingin melaporkan kehilangan dokumen:\nJenis Dokumen: \nTanggal Hilang: \nLokasi Terakhir Dilihat: \nKeterangan Tambahan:`;
  } else if (jenis === 'usaha') {
    textarea.value = `Saya ingin mengajukan izin membuka usaha dengan data berikut:\nNama Usaha: \nJenis Usaha: \nAlamat Usaha: \nJam Operasional: \nKeterangan Tambahan:`;
  } else if (jenis === 'kegiatan') {
    textarea.value = `Laporan kegiatan warga:\nNama Kegiatan: \nTanggal Pelaksanaan: \nTempat: \nJumlah Peserta: \nKeterangan Tambahan:`;
  }
}
</script>

</body>
</html>
