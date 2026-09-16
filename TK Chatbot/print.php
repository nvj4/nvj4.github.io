<?php
require_once 'api\config.php';

 $conn = getConnection();
 $murid_id = $_GET['id'] ?? 0;

// Ambil data murid
 $stmt = $conn->prepare("SELECT m.*, k.nama_kelas, k.tahun_ajaran FROM murid m JOIN kelas k ON m.kelas_id = k.id WHERE m.id = ?");
 $stmt->bind_param("i", $murid_id);
 $stmt->execute();
 $murid = $stmt->get_result()->fetch_assoc();
 $stmt->close();

if (!$murid) {
    die('Data tidak ditemukan');
}

// Cek verifikasi via session
 $verified_key = 'verified_' . $murid_id;
if (!isset($_SESSION[$verified_key]) || $_SESSION[$verified_key] !== true) {
    header('Location: lihat_perkembangan.php?id=' . $murid_id);
    exit();
}

// Ambil raport
 $stmt = $conn->prepare("SELECT r.*, u.nama_lengkap as nama_guru FROM raport r JOIN users u ON r.created_by = u.id WHERE r.murid_id = ? ORDER BY r.tahun_ajaran DESC, r.semester DESC LIMIT 1");
 $stmt->bind_param("i", $murid_id);
 $stmt->execute();
 $raport = $stmt->get_result()->fetch_assoc();
 $stmt->close();
 $conn->close();

// Hitung umur
 $birthDate = new DateTime($murid['tanggal_lahir']);
 $today = new DateTime();
 $umur = $today->diff($birthDate)->y;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport <?= e($murid['nama_lengkap']) ?> - TK Ceria</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12pt; line-height: 1.5; color: #333; background: white; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 3px double #333; padding-bottom: 15px; margin-bottom: 25px; }
        .header h1 { font-size: 22pt; margin: 0 0 5px 0; color: #2C3E50; }
        .header p { margin: 0; color: #666; font-size: 11pt; }
        .student-info { margin-bottom: 25px; padding: 12px; background: #f9f9f9; border-radius: 8px; }
        .student-info table { width: 100%; border-collapse: collapse; }
        .student-info td { padding: 4px 10px; font-size: 11pt; }
        .student-info td:nth-child(1) { width: 140px; font-weight: 600; }
        .student-info td:nth-child(3) { width: 80px; font-weight: 600; }
        .section-title { background: #FF6B35; color: white; padding: 6px 12px; margin: 15px 0 10px 0; border-radius: 4px; font-size: 11pt; font-weight: 600; }
        .section-content { padding: 0 10px; text-align: justify; font-size: 11pt; }
        .signature { margin-top: 40px; }
        .signature table { width: 100%; }
        .signature td { text-align: center; vertical-align: top; width: 50%; }
        .signature .sign-line { border-bottom: 1px solid #333; width: 180px; height: 50px; margin: 0 auto; }
        .no-print { display: none; }
        @media screen {
            .no-print { display: block; position: fixed; top: 20px; right: 20px; z-index: 1000; }
            .no-print button { padding: 12px 24px; background: #FF6B35; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14pt; font-weight: 600; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3); }
            .no-print button:hover { background: #e55a2b; }
            body { max-width: 210mm; margin: 0 auto; box-shadow: 0 0 20px rgba(0,0,0,0.1); min-height: 297mm; }
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    </div>
    
    <div class="header">
        <h1>TAMAN KANAK-KANAK CERIA</h1>
        <p>Jl. Pendidikan No. 123, Kota ABC | Telp: (021) 1234567</p>
    </div>
    
    <h2 style="text-align: center; margin-bottom: 25px; border: 2px solid #333; padding: 8px; background: #f5f5f5; font-size: 14pt;">
        LAPORAN PERKEMBANGAN ANAK DIDIK<br>
        <small>Semester <?= $raport['semester'] ?? '1' ?> Tahun Ajaran <?= e($raport['tahun_ajaran'] ?? $murid['tahun_ajaran']) ?></small>
    </h2>
    
    <div class="student-info">
        <table>
            <tr><td>Nama Lengkap</td><td>: <strong><?= e($murid['nama_lengkap']) ?></strong></td><td>Kelas</td><td>: <?= e($murid['nama_kelas']) ?></td></tr>
            <tr><td>Tanggal Lahir</td><td>: <?= formatTanggal($murid['tanggal_lahir']) ?></td><td>Umur</td><td>: <?= $umur ?> Tahun</td></tr>
            <tr><td>Jenis Kelamin</td><td>: <?= $murid['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td><td></td><td></td></tr>
            <tr><td>Nama Orang Tua</td><td>: <?= e($murid['nama_ortu']) ?></td><td></td><td></td></tr>
        </table>
    </div>
    
    <?php if ($raport): ?>
    <div class="section-title">A. PERKEMBANGAN FISIK / MOTORIK</div>
    <div class="section-content"><?= e($raport['perkembangan_fisik']) ?: 'Belum ada data.' ?></div>
    
    <div class="section-title">B. PERKEMBANGAN BAHASA</div>
    <div class="section-content"><?= e($raport['perkembangan_bahasa']) ?: 'Belum ada data.' ?></div>
    
    <div class="section-title">C. PERKEMBANGAN KOGNITIF</div>
    <div class="section-content"><?= e($raport['perkembangan_kognitif']) ?: 'Belum ada data.' ?></div>
    
    <div class="section-title">D. PERKEMBANGAN SOSIAL EMOSIONAL</div>
    <div class="section-content"><?= e($raport['perkembangan_sosial']) ?: 'Belum ada data.' ?></div>
    
    <div class="section-title">E. PERKEMBANGAN SENI</div>
    <div class="section-content"><?= e($raport['perkembangan_seni']) ?: 'Belum ada data.' ?></div>
    
    <div class="section-title">F. CATATAN GURU</div>
    <div class="section-content"><?= e($raport['catatan_guru']) ?: 'Tidak ada catatan khusus.' ?></div>
    <?php else: ?>
    <div style="text-align: center; color: #999; padding: 50px;">Belum ada data raport tersedia.</div>
    <?php endif; ?>
    
    <div class="signature">
        <table>
            <tr>
                <td style="padding-right: 30px;">
                    <p>Mengetahui,</p>
                    <p>Orang Tua / Wali</p>
                    <div class="sign-line"></div>
                    <p><strong><?= e($murid['nama_ortu']) ?></strong></p>
                </td>
                <td style="padding-left: 30px;">
                    <p>Kota ABC, <?= formatTanggal(date('Y-m-d')) ?></p>
                    <p>Guru Kelas</p>
                    <div class="sign-line"></div>
                    <p><strong><?= e($raport['nama_guru'] ?? '........................') ?></strong></p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>