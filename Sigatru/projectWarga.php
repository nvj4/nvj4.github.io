<?php
session_start();
$host = 'sql313.infinityfree.com';
$db   = 'if0_39334993_sigatru';
$user = 'if0_39334993';
$pass = 'SigatruPWL01';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8";

try {
  $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
  die("Koneksi gagal: " . $e->getMessage());
}

if (!isset($_SESSION['username'])) {
  $_SESSION['username'] = "wargaRT";
}

if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: index.php");
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
      background: linear-gradient(135deg, #5EABD, #E14434);
      color: black;
      margin: 0;
      padding: 0 20px;
    }

    header {
      background:linear-gradient(135deg, #3E001F, #5EABD6);
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 3px 8px rgba(0,0,0,0.3);
    }

    .logo-area img {
      height: 100px;
      width: auto;
      display: block;
    }

    nav {
      position: relative;
    }

    .hamburger {
      display: none;
      font-size: 28px;
      background: none;
      border: none;
      color: white;
      cursor: pointer;
      padding: 10px;
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
      color: white;
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
      background:white;
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
      background: white;
      color: black;
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
      background:linear-gradient(135deg, #3E001F, #5EABD6);
      color: #aaa;
      font-size: 0.9em;
      border-top: 1px solid #444;
    }

    h2, h3 {
      color:rgb(12, 12, 12);
    }

    .card-laporan {
      background: #FFFDF6;
      border-radius: 10px;
      padding: 15px;
      flex: 1;
      min-width: 200px;
      cursor: pointer;
      transition: transform 0.3s, background 0.3s;
      border: 2px solid transparent;
      text-align: center;
      box-shadow: 0 8px 25px black;
    }

    .card-laporan:hover {
      background: #3498db;
      transform: translateY(-3px);
      border-color: white;
    }

    .btn-link {
      padding:10px 15px;
      border-radius: 5px;
      text-decoration: none;
      margin-right: 10px;
      display: inline-block;
    }

    .btn-yellow { background: #e74c3c; color: white; }
    .btn-blue { background: #3498db; color: #fff; }

    .image-gallery {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 30px;
      margin: 40px 0;
    }

    .scroll-image {
      width: 90%;
      max-width: 500px;
      height: auto;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.6s ease-out;
    }

    .scroll-image.visible {
      opacity: 1;
      transform: translateY(0);
    }

    @media screen and (max-width: 768px) {
      header { flex-direction: column; align-items: flex-start; }
      .hamburger { display: block; }
      nav ul {
        display: none;
        flex-direction: column;
        width: 100%;
        background: #1f2a35;
        padding: 10px 0;
        margin-top: 10px;
      }
      nav ul.active {
        display: flex;
      }
    }
  </style>
</head>
<body>

<header>
  <div class="logo-area">
    <img src="biru.png" alt="Logo SIGATRU">
  </div>
  <nav>
    <button class="hamburger" onclick="toggleMenu()">☰</button>
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

<section id="beranda" class="active">
  <div style="display: flex; align-items: center; gap: 40px; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 250px;">
      <h2>Selamat Datang di Website RT 01</h2>
      <p>Lingkungan RT 01 adalah tempat tinggal yang nyaman dan aman dengan komunitas yang aktif dan peduli.</p>
    </div>
    <div class="rt" style="flex-shrink: 0;">
      <img src="pak rt.png" alt="Pak RT" style="width: 300px; border-radius: 10px;">
    </div>
  </div>

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

  <div style="margin-top: 30px;">
    <h3>Agenda Terdekat</h3>
    <ul>
      <li>📢 Rapat RT: 30 Juni 2025, pukul 19.00 WIB di Balai Warga</li>
      <li>🧹 Kerja Bakti: 6 Juli 2025, pukul 07.00 WIB</li>
    </ul>
  </div>

  <div style="margin-top: 30px;">
    <a href="#" onclick="tampilkan('laporan')" class="btn-link btn-yellow">Laporkan Sesuatu</a>
    <a href="#" onclick="tampilkan('dataTamu')" class="btn-link btn-blue">Isi Buku Tamu</a>
  </div>

  <div class="image-gallery">
    <img src="1.jpg" class="scroll-image" alt="Kegiatan 1">
    <img src="2.jpg" class="scroll-image" alt="Kegiatan 2">
    <img src="3.jpg" class="scroll-image" alt="Kegiatan 3">
    <img src="4.jpg" class="scroll-image" alt="Kegiatan 4">
  </div>
</section>


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
  &copy; <?= date("Y") ?> SIGATRU - Sistem Informasi Warga RT 01
</footer>

<script>
function tampilkan(id) {
  document.querySelectorAll("section").forEach(s => s.classList.remove("active"));
  document.getElementById(id).classList.add("active");
}

function toggleMenu() {
  document.querySelector("nav ul").classList.toggle("active");
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

const images = document.querySelectorAll('.scroll-image');
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
    }
  });
}, { threshold: 0.1 });

images.forEach(img => observer.observe(img));
</script>

</body>
</html>
