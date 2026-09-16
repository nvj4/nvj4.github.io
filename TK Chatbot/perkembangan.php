<?php
require_once 'api\config.php';

 $conn = getConnection();
 $murid_id = $_GET['id'] ?? 0;

// Ambil data murid
 $stmt = $conn->prepare("SELECT m.*, k.nama_kelas FROM murid m JOIN kelas k ON m.kelas_id = k.id WHERE m.id = ?");
 $stmt->bind_param("i", $murid_id);
 $stmt->execute();
 $murid = $stmt->get_result()->fetch_assoc();
 $stmt->close();

if (!$murid) {
    header('Location: dashboard_ortu.php');
    exit();
}

// Cek apakah sudah verifikasi kode unik di session
 $verified_key = 'verified_' . $murid_id;
 $is_verified = isset($_SESSION[$verified_key]) && $_SESSION[$verified_key] === true;

 $error = '';

// Proses verifikasi kode unik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode_unik'])) {
    $input_code = strtoupper(trim($_POST['kode_unik']));
    
    if ($input_code === $murid['kode_unik']) {
        $_SESSION[$verified_key] = true;
        $is_verified = true;
    } else {
        $error = 'Kode unik salah! Silakan coba lagi.';
    }
}

// Jika sudah verifikasi, ambil data kegiatan dan raport
 $kegiatan_list = [];
 $raport = null;

if ($is_verified) {
    // Ambil kegiatan 30 hari terakhir
    $stmt = $conn->prepare("SELECT * FROM kegiatan_harian WHERE murid_id = ? ORDER BY tanggal DESC LIMIT 30");
    $stmt->bind_param("i", $murid_id);
    $stmt->execute();
    $kegiatan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Ambil raport terbaru
    $stmt = $conn->prepare("SELECT r.*, u.nama_lengkap as nama_guru FROM raport r JOIN users u ON r.created_by = u.id WHERE r.murid_id = ? ORDER BY r.tahun_ajaran DESC, r.semester DESC LIMIT 1");
    $stmt->bind_param("i", $murid_id);
    $stmt->execute();
    $raport = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

 $conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perkembangan Anak - TK Ceria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#FF6B35', secondary: '#4ECDC4', accent: '#FFE66D', soft: '#FFF5EE', dark: '#2C3E50' } } }
        }
    </script>
    <style>
        body { font-family: 'Nunito', sans-serif; }
        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-soft via-white to-blue-50">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-lg sticky top-0 z-40 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="dashboard_ortu.php" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-secondary to-teal-400 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/></svg>
                    </div>
                    <span class="font-bold text-dark">TK Ceria</span>
                </a>
                <a href="login.php" class="text-sm text-gray-500 hover:text-primary transition-colors">Login Guru</a>
            </div>
        </div>
    </header>
    
    <div class="max-w-5xl mx-auto px-6 py-8">
        <!-- MODAL KODE UNIK -->
        <?php if (!$is_verified): ?>
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 fade-in">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-gradient-to-br from-secondary to-teal-400 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-2xl text-dark mb-1">Verifikasi Diperlukan</h3>
                    <p class="text-gray-500">Masukkan kode unik untuk melihat perkembangan</p>
                    <p class="font-semibold text-dark mt-2"><?= e($murid['nama_lengkap']) ?></p>
                </div>
                
                <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-r-xl mb-6 flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <span class="font-medium"><?= e($error) ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-6">
                        <input type="text" name="kode_unik" maxlength="8" required autofocus class="w-full px-6 py-4 text-center text-2xl font-mono tracking-widest border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors uppercase" placeholder="XXXXXXXX">
                    </div>
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-secondary to-teal-400 text-white rounded-xl font-bold hover:shadow-lg hover:shadow-secondary/30 transition-all">
                        Verifikasi
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="dashboard_ortu.php" class="text-sm text-gray-400 hover:text-gray-600 transition-colors">← Kembali ke daftar murid</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        
        <!-- KONTEN SETELAH VERIFIKASI -->
        <div class="fade-in">
            <!-- Breadcrumb -->
            <div class="mb-6">
                <a href="dashboard_ortu.php" class="text-secondary hover:underline text-sm">← Kembali ke daftar murid</a>
            </div>
            
            <!-- Info Murid -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <div class="w-20 h-20 bg-gradient-to-br from-primary to-orange-400 rounded-2xl flex items-center justify-center text-white text-3xl font-bold shadow-lg flex-shrink-0">
                        <?= strtoupper(substr($murid['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <div class="text-center md:text-left flex-1">
                        <h3 class="font-bold text-2xl text-dark mb-1"><?= e($murid['nama_lengkap']) ?></h3>
                        <p class="text-gray-500 mb-2"><?= e($murid['nama_kelas']) ?></p>
                        <div class="flex flex-wrap justify-center md:justify-start gap-3 text-sm">
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full"><?= $murid['jenis_kelamin'] === 'L' ? '👦 Laki-laki' : '👧 Perempuan' ?></span>
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full">🎂 <?= formatTanggal($murid['tanggal_lahir']) ?></span>
                        </div>
                    </div>
                    <div class="flex gap-3 flex-shrink-0">
                        <a href="print.php?id=<?= $murid['id'] ?>" target="_blank" class="px-5 py-2.5 bg-gradient-to-r from-primary to-orange-400 text-white rounded-xl font-semibold hover:shadow-lg transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Cetak Raport
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Kegiatan Harian -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h4 class="font-bold text-lg text-dark mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Kegiatan Harian Terbaru
                    </h4>
                    
                    <?php if (empty($kegiatan_list)): ?>
                    <div class="text-center py-12 text-gray-400 bg-gray-50 rounded-xl">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7v4m0 0v4m0-4h4m-4 0H9"/></svg>
                        <p>Belum ada data kegiatan</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                        <?php foreach ($kegiatan_list as $k): ?>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-dark text-sm"><?= formatTanggal($k['tanggal']) ?></span>
                                <span class="px-2 py-1 text-xs rounded-full <?= 
                                    $k['status_hadir'] === 'hadir' ? 'bg-green-100 text-green-700' : 
                                    ($k['status_hadir'] === 'sakit' ? 'bg-yellow-100 text-yellow-700' : 
                                    ($k['status_hadir'] === 'izin' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'))
                                ?>"><?= ucfirst($k['status_hadir']) ?></span>
                            </div>
                            <div class="flex gap-3 text-sm text-gray-600 mb-2">
                                <span title="Mood"><?= $k['mood'] === 'sangat_baik' ? '😊' : ($k['mood'] === 'baik' ? '🙂' : ($k['mood'] === 'cukup' ? '😐' : '😢')) ?></span>
                                <span title="Makan">🍜 <?= str_replace('_', ' ', $k['makan_siang']) ?></span>
                                <span title="Tidur">😴 <?= $k['tidur_siang'] === 'tidur' ? 'Tidur' : 'Tidak' ?></span>
                            </div>
                            <?php if ($k['kegiatan']): ?>
                            <p class="text-sm text-gray-600 line-clamp-2"><?= e($k['kegiatan']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Raport -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h4 class="font-bold text-lg text-dark mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Raport Semester
                    </h4>
                    
                    <?php if (!$raport): ?>
                    <div class="text-center py-12 text-gray-400 bg-gray-50 rounded-xl">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p>Belum ada data raport</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <div>
                                <p class="font-semibold text-dark">Semester <?= $raport['semester'] ?></p>
                                <p class="text-sm text-gray-500"><?= e($raport['tahun_ajaran']) ?></p>
                            </div>
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Tersedia</span>
                        </div>
                        
                        <div class="space-y-3 text-sm">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-gray-500 font-medium mb-1">Perkembangan Fisik</p>
                                <p class="text-dark"><?= e($raport['perkembangan_fisik']) ?: '-' ?></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-gray-500 font-medium mb-1">Perkembangan Bahasa</p>
                                <p class="text-dark"><?= e($raport['perkembangan_bahasa']) ?: '-' ?></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-gray-500 font-medium mb-1">Perkembangan Kognitif</p>
                                <p class="text-dark"><?= e($raport['perkembangan_kognitif']) ?: '-' ?></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-gray-500 font-medium mb-1">Perkembangan Sosial</p>
                                <p class="text-dark"><?= e($raport['perkembangan_sosial']) ?: '-' ?></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-gray-500 font-medium mb-1">Perkembangan Seni</p>
                                <p class="text-dark"><?= e($raport['perkembangan_seni']) ?: '-' ?></p>
                            </div>
                        </div>
                        
                        <?php if ($raport['catatan_guru']): ?>
                        <div class="bg-yellow-50 rounded-xl p-4 mt-4">
                            <p class="text-sm font-semibold text-yellow-700 mb-1">📝 Catatan Guru</p>
                            <p class="text-gray-700 text-sm"><?= e($raport['catatan_guru']) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <p class="text-xs text-gray-400 pt-2">Dibuat oleh: <?= e($raport['nama_guru']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>