<?php
require_once 'api\config.php';
requireGuru();

 $conn = getConnection();
 $guru_id = $_SESSION['user_id'];

// Ambil kelas guru
 $stmt = $conn->prepare("SELECT id FROM kelas WHERE guru_id = ?");
 $stmt->bind_param("i", $guru_id);
 $stmt->execute();
 $kelas_result = $stmt->get_result();
 $kelas_ids = array_column($kelas_result->fetch_all(MYSQLI_ASSOC), 'id');
 $stmt->close();

// Ambil murid
 $murid_list = [];
if (!empty($kelas_ids)) {
    $placeholders = implode(',', array_fill(0, count($kelas_ids), '?'));
    $stmt = $conn->prepare("SELECT id, nama_lengkap FROM murid WHERE kelas_id IN ($placeholders) AND status = 'aktif' ORDER BY nama_lengkap");
    $types = str_repeat('i', count($kelas_ids));
    $stmt->bind_param($types, ...$kelas_ids);
    $stmt->execute();
    $murid_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

 $selected_murid = $_GET['murid_id'] ?? '';
 $error = '';
 $success = '';

// Ambil data kegiatan hari ini
 $kegiatan_hari_ini = [];
if (!empty($kelas_ids)) {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT kh.*, m.nama_lengkap 
        FROM kegiatan_harian kh 
        JOIN murid m ON kh.murid_id = m.id 
        WHERE m.kelas_id IN ($placeholders) AND kh.tanggal = ?
        ORDER BY m.nama_lengkap
    ");
    $types = $types . 's';
    $params = array_merge($kelas_ids, [$today]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $kegiatan_hari_ini = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $murid_id = $_POST['murid_id'] ?? '';
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $status_hadir = $_POST['status_hadir'] ?? 'hadir';
    $kegiatan = trim($_POST['kegiatan'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');
    $mood = $_POST['mood'] ?? 'baik';
    $makan_siang = $_POST['makan_siang'] ?? 'habis';
    $tidur_siang = $_POST['tidur_siang'] ?? 'tidur';
    $kebersihan = $_POST['kebersihan'] ?? 'bersih';
    
    if (empty($murid_id)) {
        $error = 'Pilih murid terlebih dahulu!';
    } else {
        // Cek apakah sudah ada kegiatan untuk murid ini di tanggal yang sama
        $stmt = $conn->prepare("SELECT id FROM kegiatan_harian WHERE murid_id = ? AND tanggal = ?");
        $stmt->bind_param("is", $murid_id, $tanggal);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // Update
            $stmt = $conn->prepare("UPDATE kegiatan_harian SET status_hadir=?, kegiatan=?, catatan=?, mood=?, makan_siang=?, tidur_siang=?, kebersihan=? WHERE id=?");
            $stmt->bind_param("sssssssi", $status_hadir, $kegiatan, $catatan, $mood, $makan_siang, $tidur_siang, $kebersihan, $existing['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO kegiatan_harian (murid_id, tanggal, status_hadir, kegiatan, catatan, mood, makan_siang, tidur_siang, kebersihan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssi", $murid_id, $tanggal, $status_hadir, $kegiatan, $catatan, $mood, $makan_siang, $tidur_siang, $kebersihan, $guru_id);
        }
        
        if ($stmt->execute()) {
            $success = 'Kegiatan berhasil disimpan!';
        } else {
            $error = 'Gagal menyimpan kegiatan.';
        }
        $stmt->close();
    }
}

 $conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Kegiatan - TK Ceria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#FF6B35', secondary: '#4ECDC4', accent: '#FFE66D', soft: '#FFF5EE', dark: '#2C3E50' } } }
        }
    </script>
    <style>body { font-family: 'Nunito', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-soft via-white to-blue-50">
    <div class="lg:ml-64 min-h-screen">
        <aside class="fixed left-0 top-0 h-full w-64 bg-white shadow-2xl z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300" id="sidebar">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-orange-400 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/></svg>
                    </div>
                    <div><h1 class="font-bold text-lg text-dark">TK Ceria</h1><p class="text-xs text-gray-400">Dashboard Guru</p></div>
                </div>
            </div>
            <nav class="p-4 space-y-2">
                <a href="dashboard_guru.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard</span>
                </a>
                
              
            <a href="kelas.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span>Kelola Kelas</span>
            </a>

                <a href="crud.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    <span>Tambah Murid</span>
                </a>
                <a href="kegiatan.php" class="flex items-center gap-3 px-4 py-3 bg-primary/10 text-primary rounded-xl font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span>Input Kegiatan</span>
                </a>
                <a href="raport.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Input Raport</span>
                </a>
            </nav>
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-100">
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar</span>
                </a>
            </div>
        </aside>
        
        <header class="bg-white/80 backdrop-blur-lg sticky top-0 z-40 px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <button class="lg:hidden p-2 hover:bg-gray-100 rounded-xl" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="font-bold text-xl text-dark">Input Kegiatan Harian</h2>
                <div></div>
            </div>
        </header>
        
        <div class="p-6 lg:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Form Input -->
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h3 class="font-bold text-lg text-dark mb-6">Form Kegiatan</h3>
                    
                    <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-r-xl mb-6"><?= e($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-r-xl mb-6"><?= e($success) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Murid *</label>
                            <select name="murid_id" id="murid_id" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors">
                                <option value="">-- Pilih Murid --</option>
                                <?php foreach ($murid_list as $murid): ?>
                                <option value="<?= $murid['id'] ?>" <?= $selected_murid == $murid['id'] ? 'selected' : '' ?>><?= e($murid['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal</label>
                                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Status Kehadiran</label>
                                <select name="status_hadir" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors">
                                    <option value="hadir">Hadir</option>
                                    <option value="sakit">Sakit</option>
                                    <option value="izin">Izin</option>
                                    <option value="alpha">Alpha</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mood Hari Ini</label>
                            <div class="grid grid-cols-4 gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="mood" value="sangat_baik" class="hidden peer">
                                    <div class="text-center p-3 border-2 rounded-xl peer-checked:border-green-400 peer-checked:bg-green-50 transition-all">
                                        <span class="text-2xl">😊</span>
                                        <p class="text-xs mt-1 font-medium">Sangat Baik</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="mood" value="baik" class="hidden peer" checked>
                                    <div class="text-center p-3 border-2 rounded-xl peer-checked:border-blue-400 peer-checked:bg-blue-50 transition-all">
                                        <span class="text-2xl">🙂</span>
                                        <p class="text-xs mt-1 font-medium">Baik</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="mood" value="cukup" class="hidden peer">
                                    <div class="text-center p-3 border-2 rounded-xl peer-checked:border-yellow-400 peer-checked:bg-yellow-50 transition-all">
                                        <span class="text-2xl">😐</span>
                                        <p class="text-xs mt-1 font-medium">Cukup</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="mood" value="kurang" class="hidden peer">
                                    <div class="text-center p-3 border-2 rounded-xl peer-checked:border-red-400 peer-checked:bg-red-50 transition-all">
                                        <span class="text-2xl">😢</span>
                                        <p class="text-xs mt-1 font-medium">Kurang</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Makan Siang</label>
                                <select name="makan_siang" class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors text-sm">
                                    <option value="habis">Habis</option>
                                    <option value="setengah">Setengah</option>
                                    <option value="sedikit">Sedikit</option>
                                    <option value="tidak_makan">Tidak Makan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tidur Siang</label>
                                <select name="tidur_siang" class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors text-sm">
                                    <option value="tidur">Tidur</option>
                                    <option value="tidak_tidur">Tidak Tidur</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Kebersihan</label>
                                <select name="kebersihan" class="w-full px-3 py-2 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors text-sm">
                                    <option value="bersih">Bersih</option>
                                    <option value="cukup">Cukup</option>
                                    <option value="perlu_diperhatikan">Perlu Diperhatikan</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kegiatan Hari Ini</label>
                            <textarea name="kegiatan" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors resize-none" placeholder="Tuliskan kegiatan yang dilakukan hari ini..."></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan Khusus</label>
                            <textarea name="catatan" rows="2" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors resize-none" placeholder="Catatan untuk orang tua..."></textarea>
                        </div>
                        
                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-secondary to-teal-400 text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                            Simpan Kegiatan
                        </button>
                    </form>
                </div>
                
                <!-- Kegiatan Hari Ini -->
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h3 class="font-bold text-lg text-dark mb-6">Kegiatan Hari Ini - <?= formatTanggal(date('Y-m-d')) ?></h3>
                    
                    <?php if (empty($kegiatan_hari_ini)): ?>
                    <div class="text-center py-12 text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p>Belum ada kegiatan yang dicatat hari ini</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                        <?php foreach ($kegiatan_hari_ini as $k): ?>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-dark"><?= e($k['nama_lengkap']) ?></h4>
                                <span class="px-2 py-1 text-xs rounded-full <?= 
                                    $k['status_hadir'] === 'hadir' ? 'bg-green-100 text-green-700' : 
                                    ($k['status_hadir'] === 'sakit' ? 'bg-yellow-100 text-yellow-700' : 
                                    ($k['status_hadir'] === 'izin' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'))
                                ?>"><?= ucfirst($k['status_hadir']) ?></span>
                            </div>
                            <div class="flex gap-4 text-sm text-gray-600">
                                <span><?= $k['mood'] === 'sangat_baik' ? '😊' : ($k['mood'] === 'baik' ? '🙂' : ($k['mood'] === 'cukup' ? '😐' : '😢')) ?> Mood</span>
                                <span>🍜 <?= ucfirst(str_replace('_', ' ', $k['makan_siang'])) ?></span>
                                <span>😴 <?= $k['tidur_siang'] === 'tidur' ? 'Tidur' : 'Tidak Tidur' ?></span>
                            </div>
                            <?php if ($k['kegiatan']): ?>
                            <p class="text-sm text-gray-600 mt-2"><?= e($k['kegiatan']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" id="overlay" onclick="toggleSidebar()"></div>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('overlay').classList.toggle('hidden');
        }
    </script>
</body>
</html>