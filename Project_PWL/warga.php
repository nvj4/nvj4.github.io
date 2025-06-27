<?php
// Koneksi ke database
$koneksi = new mysqli("localhost", "root", "", "sigatru"); // GANTI dengan nama database kamu

// Tambah data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $nik = $_POST['NIK'];
    $nama = $_POST['Nama'];
    $alamat = $_POST['Alamat'];
    $rt = $_POST['RT'];
    $rw = $_POST['RW'];

    $stmt = $koneksi->prepare("INSERT INTO warga (NIK, Nama, Alamat, RT, RW) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nik, $nama, $alamat, $rt, $rw);
    $stmt->execute();
    header("Location: projectWarga.php");
    exit();
}

// Hapus data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
    $nik = $_POST['nik'];
    $stmt = $koneksi->prepare("DELETE FROM warga WHERE nik = ?");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    header("Location: projectWarga.php");
    exit();
}

// Ambil data
$data = $koneksi->query("SELECT * FROM warga");
?>

<!DOCTYPE html>
<html>
<head>
  <title>SIGATRU</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <h1>Data Warga</h1>
</header>

<section class="active">
  <div class="form-box">
    <form action="" method="post">
      <label>NIK</label>
      <input type="text" name="NIK" required>
      <label>Nama</label>
      <input type="text" name="Nama" required>
      <label>Alamat</label>
      <input type="text" name="Alamat" required>
      <label>RT</label>
      <input type="text" name="RT" required>
      <label>RW</label>
      <input type="text" name="RW" required>

      <input type="hidden" name="aksi" value="tambah">
      <button type="submit">Simpan</button>
    </form>
  </div>

  <div class="box">
    <h2>Daftar Warga</h2>
    <table>
      <thead>
        <tr>
          <th>NIK</th>
          <th>Nama</th>
          <th>Alamat</th>
          <th>RT</th>
          <th>RW</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $data->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['NIK']) ?></td>
            <td><?= htmlspecialchars($row['Nama']) ?></td>
            <td><?= htmlspecialchars($row['Alamat']) ?></td>
            <td><?= htmlspecialchars($row['RT']) ?></td>
            <td><?= htmlspecialchars($row['RW']) ?></td>
            <td>
              <form method="post" style="display:inline;">
                <input type="hidden" name="nik" value="<?= $row['NIK'] ?>">
                <input type="hidden" name="aksi" value="hapus">
                <button type="submit" class="btn delete-btn" onclick="return confirm('Yakin ingin hapus?')">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</section>

<footer>
  <p>© 2025 Sistem Data Warga RT</p>
</footer>

</body>
</html>
