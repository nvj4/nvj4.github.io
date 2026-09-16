<?php
require_once 'api\config.php';
requireGuru();

 $conn = getConnection();
 $guru_id = $_SESSION['user_id'];

// Ambil kelas yang diampu guru
 $stmt = $conn->prepare("SELECT * FROM kelas WHERE guru_id = ?");
 $stmt->bind_param("i", $guru_id);
 $stmt->execute();
 $kelas_result = $stmt->get_result();
 $kelas_list = $kelas_result->fetch_all(MYSQLI_ASSOC);
 $stmt->close();

// Ambil semua murid dari kelas guru
 $murid_list = [];
 $total_murid = 0;
 $hadir_hari_ini = 0;

if (!empty($kelas_list)) {
    $kelas_ids = array_column($kelas_list, 'id');
    $placeholders = implode(',', array_fill(0, count($kelas_ids), '?'));
    
    $stmt = $conn->prepare("SELECT m.*, k.nama_kelas FROM murid m JOIN kelas k ON m.kelas_id = k.id WHERE m.kelas_id IN ($placeholders) AND m.status = 'aktif' ORDER BY m.nama_lengkap");
    $types = str_repeat('i', count($kelas_ids));
    $stmt->bind_param($types, ...$kelas_ids);
    $stmt->execute();
    $murid_result = $stmt->get_result();
    $murid_list = $murid_result->fetch_all(MYSQLI_ASSOC);
    $total_murid = count($murid_list);
    $stmt->close();
    
    // Hitung kehadiran hari ini
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT COUNT(*) as hadir 
        FROM kegiatan_harian kh 
        JOIN murid m ON kh.murid_id = m.id 
        WHERE m.kelas_id IN ($placeholders) AND kh.tanggal = ? AND kh.status_hadir = 'hadir'
    ");
    $types = $types . 's';
    $params = array_merge($kelas_ids, [$today]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $hadir_result = $stmt->get_result()->fetch_assoc();
    $hadir_hari_ini = $hadir_result['hadir'] ?? 0;
    $stmt->close();
}

 $conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - TK Ceria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#FF6B35',
                        secondary: '#4ECDC4',
                        accent: '#FFE66D',
                        soft: '#FFF5EE',
                        dark: '#2C3E50'
                    },
                    fontFamily: {
                        display: ['Quicksand', 'sans-serif'],
                        body: ['Nunito', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Nunito', sans-serif; }
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }
        .stagger-4 { animation-delay: 0.4s; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-soft via-white to-blue-50">
    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 h-full w-64 bg-white shadow-2xl z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300" id="sidebar">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-primary to-orange-400 rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="font-display font-bold text-lg text-dark">TK Ceria</h1>
                    <p class="text-xs text-gray-400">Dashboard Guru</p>
                </div>
            </div>
        </div>
        
        <nav class="p-4 space-y-2">
            <a href="dashboard_guru.php" class="flex items-center gap-3 px-4 py-3 bg-primary/10 text-primary rounded-xl font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard</span>
            </a>
            
            <!-- MENU KELOLA KELAS (BARU) -->
            <a href="kelas.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span>Kelola Kelas</span>
            </a>

            <a href="crud.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                <span>Kelola Murid</span>
            </a>
            <a href="kegiatan.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span>Input Kegiatan</span>
            </a>
            <a href="raport.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Input Raport</span>
            </a>
        </nav>
        
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-100">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Keluar</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-lg sticky top-0 z-40 px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <button class="lg:hidden p-2 hover:bg-gray-100 rounded-xl" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div>
                    <h2 class="font-display text-xl font-bold text-dark">Selamat Datang, <?= e($_SESSION['nama_lengkap']) ?></h2>
                    <p class="text-sm text-gray-500"><?= formatTanggal(date('Y-m-d')) ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-secondary to-teal-400 rounded-xl flex items-center justify-center text-white font-bold">
                        <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Content -->
        <div class="p-6 lg:p-8">
            
            <?php if (empty($kelas_list)): ?>
                <!-- WARNING: BELUM PUNYA KELAS -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-r-2xl mb-8 flex flex-col md:flex-row items-start gap-4 shadow-lg fade-in">
                    <div class="flex-shrink-0">
                        <svg class="w-10 h-10 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-yellow-800 text-xl mb-1">Anda Belum Memiliki Kelas</h3>
                        <p class="text-yellow-700 mb-4">Untuk dapat menambahkan murid, menginput kegiatan, atau membuat raport, Anda harus membuat kelas terlebih dahulu.</p>
                        <a href="kelas.php" class="inline-flex items-center gap-2 px-6 py-3 bg-yellow-500 text-white rounded-xl font-bold hover:bg-yellow-600 transition-colors shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Buat Kelas Sekarang
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- STATS CARDS (Hanya tampil jika sudah punya kelas) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-lg card-hover fade-in opacity-0 stagger-1">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-dark"><?= count($kelas_list) ?></p>
                        <p class="text-gray-500 font-medium">Kelas Diampu</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg card-hover fade-in opacity-0 stagger-2">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-primary to-orange-400 rounded-2xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-dark"><?= $total_murid ?></p>
                        <p class="text-gray-500 font-medium">Total Murid</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg card-hover fade-in opacity-0 stagger-3">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-dark"><?= $hadir_hari_ini ?></p>
                        <p class="text-gray-500 font-medium">Hadir Hari Ini</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg card-hover fade-in opacity-0 stagger-4">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-dark"><?= $total_murid - $hadir_hari_ini ?></p>
                        <p class="text-gray-500 font-medium">Tidak Hadir</p>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <a href="crud.php" class="bg-gradient-to-br from-primary to-orange-400 rounded-2xl p-6 text-white card-hover fade-in opacity-0 stagger-2 group">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Tambah Murid</h3>
                                <p class="text-white/80 text-sm">Daftarkan murid baru</p>
                            </div>
                        </div>
                    </a>
                    
                    <a href="input_kegiatan.php" class="bg-gradient-to-br from-secondary to-teal-400 rounded-2xl p-6 text-white card-hover fade-in opacity-0 stagger-3 group">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Input Kegiatan</h3>
                                <p class="text-white/80 text-sm">Catat kegiatan harian</p>
                            </div>
                        </div>
                    </a>
                    
                    <a href="input_raport.php" class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white card-hover fade-in opacity-0 stagger-4 group">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Input Raport</h3>
                                <p class="text-white/80 text-sm">Buat raport semester</p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Murid List -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden fade-in opacity-0 stagger-4">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-display text-xl font-bold text-dark">Daftar Murid</h3>
                    <p class="text-gray-500 text-sm">Klik pada murid untuk mengelola data</p>
                </div>
                
                <?php if (empty($murid_list)): ?>
                <div class="p-12 text-center">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <?php if (empty($kelas_list)): ?>
                        <p class="text-gray-500 font-medium">Anda belum memiliki kelas</p>
                        <p class="text-gray-400 text-sm">Buat kelas terlebih dahulu untuk menambahkan murid.</p>
                    <?php else: ?>
                        <p class="text-gray-500 font-medium">Belum ada murid terdaftar di kelas Anda</p>
                        <a href="crud.php" class="inline-flex items-center gap-2 mt-4 px-6 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-orange-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Murid Pertama
                        </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Murid</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kelas</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Kelamin</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Unik</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($murid_list as $murid): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-secondary to-teal-400 rounded-xl flex items-center justify-center text-white font-bold">
                                            <?= strtoupper(substr($murid['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-dark"><?= e($murid['nama_lengkap']) ?></p>
                                            <p class="text-xs text-gray-400"><?= e($murid['nama_ortu']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                                        <?= e($murid['nama_kelas']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 <?= $murid['jenis_kelamin'] === 'L' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700' ?> rounded-full text-sm font-medium">
                                        <?= $murid['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <code class="px-3 py-1 bg-gray-100 rounded-lg text-sm font-mono font-semibold text-gray-700">
                                        <?= e($murid['kode_unik']) ?>
                                    </code>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="crud.php?action=edit&id=<?= $murid['id'] ?>" class="p-2 hover:bg-blue-100 rounded-lg text-blue-600 transition-colors" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <a href="input_kegiatan.php?murid_id=<?= $murid['id'] ?>" class="p-2 hover:bg-green-100 rounded-lg text-green-600 transition-colors" title="Input Kegiatan">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                            </svg>
                                        </a>
                                        <a href="input_raport.php?murid_id=<?= $murid['id'] ?>" class="p-2 hover:bg-purple-100 rounded-lg text-purple-600 transition-colors" title="Input Raport">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </a>
                                        <a href="crud.php?action=delete&id=<?= $murid['id'] ?>" class="p-2 hover:bg-red-100 rounded-lg text-red-600 transition-colors" title="Hapus" onclick="return confirm('Yakin ingin menghapus murid ini?')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Mobile Overlay -->
    <div class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" id="overlay" onclick="toggleSidebar()"></div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>