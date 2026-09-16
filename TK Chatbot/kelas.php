<?php
require_once 'api\config.php';
requireGuru();

 $conn = getConnection();
 $guru_id = $_SESSION['user_id'];

// Proses Tambah Kelas
if (isset($_POST['tambah_kelas'])) {
    $nama_kelas = trim($_POST['nama_kelas']);
    $tahun_ajaran = trim($_POST['tahun_ajaran']);
    
    if (!empty($nama_kelas) && !empty($tahun_ajaran)) {
        $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas, guru_id, tahun_ajaran) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $nama_kelas, $guru_id, $tahun_ajaran);
        $stmt->execute();
        $stmt->close();
    }
}

// Proses Hapus Kelas
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    // Pastikan kelas milik guru tersebut
    $stmt = $conn->prepare("DELETE FROM kelas WHERE id = ? AND guru_id = ?");
    $stmt->bind_param("ii", $id_hapus, $guru_id);
    $stmt->execute();
    $stmt->close();
}

// Ambil daftar kelas
 $stmt = $conn->prepare("SELECT * FROM kelas WHERE guru_id = ? ORDER BY nama_kelas");
 $stmt->bind_param("i", $guru_id);
 $stmt->execute();
 $kelas_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
 $stmt->close();
 $conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas - TK Ceria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#FF6B35', secondary: '#4ECDC4', dark: '#2C3E50' } } }
        }
    </script>
    <style>body { font-family: 'Nunito', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-white to-teal-50">
    <div class="lg:ml-64 min-h-screen">
        <!-- Sidebar -->
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
                <a href="kelas.php" class="flex items-center gap-3 px-4 py-3 bg-primary/10 text-primary rounded-xl font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Kelola Kelas</span>
                </a>
                <a href="crud.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    <span>Kelola Murid</span>
                </a>
                <a href="kegiatan.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
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
                <button class="lg:hidden p-2 hover:bg-gray-100 rounded-xl" onclick="toggleSidebar()"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h2 class="font-bold text-xl text-dark">Kelola Kelas</h2>
                <div></div>
            </div>
        </header>
        
        <div class="p-6 lg:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Tambah Kelas -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="font-bold text-lg text-dark mb-4">Tambah Kelas Baru</h3>
                        <form method="POST" action="" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Kelas</label>
                                <input type="text" name="nama_kelas" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none" placeholder="Contoh: TK A1">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Ajaran</label>
                                <input type="text" name="tahun_ajaran" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none" placeholder="Contoh: 2024/2025" value="2024/2025">
                            </div>
                            <button type="submit" name="tambah_kelas" class="w-full py-3 bg-gradient-to-r from-secondary to-teal-400 text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                                Simpan Kelas
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Daftar Kelas -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="font-bold text-lg text-dark mb-4">Daftar Kelas Anda</h3>
                        
                        <?php if (empty($kelas_list)): ?>
                        <div class="text-center py-12 text-gray-400">
                            <p>Anda belum memiliki kelas.</p>
                            <p class="text-sm">Silakan buat kelas baru terlebih dahulu.</p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($kelas_list as $k): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                <div>
                                    <h4 class="font-semibold text-dark"><?= e($k['nama_kelas']) ?></h4>
                                    <p class="text-sm text-gray-400"><?= e($k['tahun_ajaran']) ?></p>
                                </div>
                                <a href="?hapus=<?= $k['id'] ?>" onclick="return confirm('Yakin hapus kelas ini? Semua murid di kelas ini juga akan terhapus.')" class="p-2 text-red-500 hover:bg-red-100 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" id="overlay" onclick="toggleSidebar()"></div>
    <script>function toggleSidebar() { document.getElementById('sidebar').classList.toggle('-translate-x-full'); document.getElementById('overlay').classList.toggle('hidden'); }</script>
</body>
</html> 