<?php
require_once 'api\config.php';
requireGuru();

 $conn = getConnection();
 $guru_id = $_SESSION['user_id'];

// Ambil kelas guru
 $stmt = $conn->prepare("SELECT * FROM kelas WHERE guru_id = ?");
 $stmt->bind_param("i", $guru_id);
 $stmt->execute();
 $kelas_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
 $stmt->close();

// Ambil semua murid dari kelas guru
 $murid_list = [];
if (!empty($kelas_list)) {
    $kelas_ids = array_column($kelas_list, 'id');
    $placeholders = implode(',', array_fill(0, count($kelas_ids), '?'));
    
    $stmt = $conn->prepare("SELECT m.*, k.nama_kelas FROM murid m JOIN kelas k ON m.kelas_id = k.id WHERE m.kelas_id IN ($placeholders) ORDER BY m.nama_lengkap");
    $types = str_repeat('i', count($kelas_ids));
    $stmt->bind_param($types, ...$kelas_ids);
    $stmt->execute();
    $murid_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Inisialisasi variabel
 $action = $_GET['action'] ?? 'list';
 $murid_id = $_GET['id'] ?? 0;
 $error = '';
 $success = '';
 $murid = null;

// Jika edit, ambil data murid
if ($action === 'edit' && $murid_id) {
    $stmt = $conn->prepare("SELECT m.*, k.guru_id FROM murid m JOIN kelas k ON m.kelas_id = k.id WHERE m.id = ?");
    $stmt->bind_param("i", $murid_id);
    $stmt->execute();
    $murid = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$murid || $murid['guru_id'] != $guru_id) {
        header('Location: crud.php');
        exit();
    }
}

// Proses Hapus
if ($action === 'delete' && $murid_id) {
    $stmt = $conn->prepare("SELECT m.id FROM murid m JOIN kelas k ON m.kelas_id = k.id WHERE m.id = ? AND k.guru_id = ?");
    $stmt->bind_param("ii", $murid_id, $guru_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM murid WHERE id = ?");
        $stmt->bind_param("i", $murid_id);
        $stmt->execute();
        $success = 'Murid berhasil dihapus!';
    }
    $stmt->close();
    header('Location: crud.php?success=' . urlencode($success));
    exit();
}

// Proses Tambah/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $nama_ortu = trim($_POST['nama_ortu'] ?? '');
    $no_hp_ortu = trim($_POST['no_hp_ortu'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $kelas_id = $_POST['kelas_id'] ?? '';
    
    if (empty($nama_lengkap) || empty($tanggal_lahir) || empty($jenis_kelamin) || empty($nama_ortu) || empty($kelas_id)) {
        $error = 'Semua field wajib harus diisi!';
        $action = $post_action;
        if ($post_action === 'edit') {
            $murid = $_POST;
        }
    } else {
        if ($post_action === 'add') {
            // Generate kode unik
            $kode_unik = generateKodeUnik(8);
            $stmt = $conn->prepare("SELECT id FROM murid WHERE kode_unik = ?");
            $stmt->bind_param("s", $kode_unik);
            $stmt->execute();
            while ($stmt->get_result()->num_rows > 0) {
                $kode_unik = generateKodeUnik(8);
                $stmt->bind_param("s", $kode_unik);
                $stmt->execute();
            }
            $stmt->close();
            
            $status = 'aktif';
            $stmt = $conn->prepare("INSERT INTO murid (nama_lengkap, tanggal_lahir, jenis_kelamin, nama_ortu, no_hp_ortu, alamat, kelas_id, kode_unik, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiss", $nama_lengkap, $tanggal_lahir, $jenis_kelamin, $nama_ortu, $no_hp_ortu, $alamat, $kelas_id, $kode_unik, $status);
            
            if ($stmt->execute()) {
                header('Location: crud.php?success=' . urlencode("Murid berhasil ditambahkan! Kode Unik: $kode_unik"));
                exit();
            } else {
                $error = 'Gagal menambahkan murid.';
                $action = 'add';
            }
        } else if ($post_action === 'edit') {
            $edit_id = $_POST['edit_id'] ?? 0;
            $kode_unik = trim($_POST['kode_unik'] ?? '');
            $status = $_POST['status'] ?? 'aktif';
            
            if (empty($kode_unik)) {
                $error = 'Kode unik harus diisi!';
                $action = 'edit';
                $murid = $_POST;
                $murid['id'] = $edit_id;
            } else {
                // Cek kode unik
                $stmt = $conn->prepare("SELECT id FROM murid WHERE kode_unik = ? AND id != ?");
                $stmt->bind_param("si", $kode_unik, $edit_id);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Kode unik sudah digunakan murid lain!';
                    $action = 'edit';
                    $murid = $_POST;
                    $murid['id'] = $edit_id;
                } else {
                    $stmt = $conn->prepare("UPDATE murid SET nama_lengkap=?, tanggal_lahir=?, jenis_kelamin=?, nama_ortu=?, no_hp_ortu=?, alamat=?, kelas_id=?, kode_unik=?, status=? WHERE id=?");
                    $stmt->bind_param("ssssssissi", $nama_lengkap, $tanggal_lahir, $jenis_kelamin, $nama_ortu, $no_hp_ortu, $alamat, $kelas_id, $kode_unik, $status, $edit_id);
                    
                    if ($stmt->execute()) {
                        header('Location: crud.php?success=' . urlencode('Data murid berhasil diperbarui!'));
                        exit();
                    } else {
                        $error = 'Gagal memperbarui data murid.';
                        $action = 'edit';
                        $murid = $_POST;
                        $murid['id'] = $edit_id;
                    }
                }
            }
        }
    }
}

// Cek success dari redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

 $conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Murid - TK Ceria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#FF6B35', secondary: '#4ECDC4', accent: '#FFE66D', soft: '#FFF5EE', dark: '#2C3E50' } } }
        }
    </script>
    <style>
        body { font-family: 'Nunito', sans-serif; }
        .fade-in { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-soft via-white to-blue-50">
    <div class="lg:ml-64 min-h-screen">
        <!-- Sidebar -->
        <aside class="fixed left-0 top-0 h-full w-64 bg-white shadow-2xl z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300" id="sidebar">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-orange-400 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/></svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-dark">TK Ceria</h1>
                        <p class="text-xs text-gray-400">Dashboard Guru</p>
                    </div>
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

                <a href="crud.php" class="flex items-center gap-3 px-4 py-3 bg-primary/10 text-primary rounded-xl font-semibold">
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
        
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-lg sticky top-0 z-40 px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <button class="lg:hidden p-2 hover:bg-gray-100 rounded-xl" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="font-bold text-xl text-dark">
                    <?php if ($action === 'add'): ?>
                        Tambah Murid Baru
                    <?php elseif ($action === 'edit'): ?>
                        Edit Data Murid
                    <?php else: ?>
                        Kelola Data Murid
                    <?php endif; ?>
                </h2>
                <div class="flex items-center gap-3">
                    <?php if ($action !== 'list'): ?>
                        <a href="crud.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl transition-colors font-medium">
                            Kembali
                        </a>
                    <?php else: ?>
                        <a href="crud.php?action=add" class="px-4 py-2 bg-gradient-to-r from-primary to-orange-400 text-white rounded-xl font-semibold hover:shadow-lg transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Murid
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        
        <!-- Content -->
        <div class="p-6 lg:p-8">
            <!-- LIST VIEW -->
            <?php if ($action === 'list'): ?>
            <div class="fade-in">
                <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-r-xl mb-6"><?= e($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-r-xl mb-6"><?= e($success) ?></div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-primary to-orange-400 rounded-2xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-dark"><?= count($murid_list) ?></p>
                                <p class="text-gray-500 font-medium">Total Murid</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-dark"><?= count($kelas_list) ?></p>
                                <p class="text-gray-500 font-medium">Kelas Diampu</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-dark"><?= count(array_filter($murid_list, fn($m) => $m['status'] === 'aktif')) ?></p>
                                <p class="text-gray-500 font-medium">Murid Aktif</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Murid Table -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <?php if (empty($murid_list)): ?>
                    <div class="p-12 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <p class="text-gray-500 font-medium mb-4">Belum ada murid terdaftar</p>
                        <a href="crud.php?action=add" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-xl font-semibold hover:bg-orange-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Murid Pertama
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Murid</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kelas</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Kelamin</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Orang Tua</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Unik</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($murid_list as $m): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-secondary to-teal-400 rounded-xl flex items-center justify-center text-white font-bold">
                                                <?= strtoupper(substr($m['nama_lengkap'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-dark"><?= e($m['nama_lengkap']) ?></p>
                                                <p class="text-xs text-gray-400"><?= formatTanggal($m['tanggal_lahir']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium"><?= e($m['nama_kelas']) ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 <?= $m['jenis_kelamin'] === 'L' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700' ?> rounded-full text-sm font-medium">
                                            <?= $m['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-dark"><?= e($m['nama_ortu']) ?></p>
                                        <p class="text-xs text-gray-400"><?= e($m['no_hp_ortu']) ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <code class="px-3 py-1 bg-gray-100 rounded-lg text-sm font-mono font-semibold text-gray-700"><?= e($m['kode_unik']) ?></code>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 <?= $m['status'] === 'aktif' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?> rounded-full text-sm font-medium">
                                            <?= ucfirst($m['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="crud.php?action=edit&id=<?= $m['id'] ?>" class="p-2 hover:bg-blue-100 rounded-lg text-blue-600 transition-colors" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                            <a href="kegiatan.php?murid_id=<?= $m['id'] ?>" class="p-2 hover:bg-green-100 rounded-lg text-green-600 transition-colors" title="Input Kegiatan">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                            </a>
                                            <a href="raport.php?murid_id=<?= $m['id'] ?>" class="p-2 hover:bg-purple-100 rounded-lg text-purple-600 transition-colors" title="Input Raport">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </a>
                                            <a href="crud.php?action=delete&id=<?= $m['id'] ?>" class="p-2 hover:bg-red-100 rounded-lg text-red-600 transition-colors" title="Hapus" onclick="return confirm('Yakin ingin menghapus murid <?= e($m['nama_lengkap']) ?>?')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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
            
            <!-- ADD/EDIT FORM -->
            <?php else: ?>
            <div class="max-w-2xl mx-auto fade-in">
                <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-r-xl mb-6"><?= e($error) ?></div>
                <?php endif; ?>
                
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100">
                        <div class="w-14 h-14 bg-gradient-to-br from-primary to-orange-400 rounded-2xl flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php if ($action === 'add'): ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                <?php else: ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                <?php endif; ?>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-xl text-dark"><?= $action === 'add' ? 'Tambah Murid Baru' : 'Edit Data Murid' ?></h3>
                            <p class="text-gray-500 text-sm">Lengkapi data di bawah ini</p>
                        </div>
                    </div>
                    
                    <form method="POST" action="" class="space-y-6">
                        <input type="hidden" name="action" value="<?= e($action) ?>">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="edit_id" value="<?= e($murid['id']) ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap Murid *</label>
                            <input type="text" name="nama_lengkap" value="<?= e($murid['nama_lengkap'] ?? '') ?>" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors" placeholder="Masukkan nama lengkap murid">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Lahir *</label>
                                <input type="date" name="tanggal_lahir" value="<?= $murid['tanggal_lahir'] ?? '' ?>" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Kelamin *</label>
                                <select name="jenis_kelamin" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors">
                                    <option value="">Pilih jenis kelamin</option>
                                    <option value="L" <?= ($murid['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="P" <?= ($murid['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kelas *</label>
                            <select name="kelas_id" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors">
                                <option value="">Pilih kelas</option>
                                <?php foreach ($kelas_list as $kelas): ?>
                                <option value="<?= $kelas['id'] ?>" <?= ($murid['kelas_id'] ?? '') == $kelas['id'] ? 'selected' : '' ?>><?= e($kelas['nama_kelas']) ?> - <?= e($kelas['tahun_ajaran']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Orang Tua/Wali *</label>
                            <input type="text" name="nama_ortu" value="<?= e($murid['nama_ortu'] ?? '') ?>" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors" placeholder="Masukkan nama orang tua/wali">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">No. HP Orang Tua</label>
                            <input type="tel" name="no_hp_ortu" value="<?= e($murid['no_hp_ortu'] ?? '') ?>" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors" placeholder="Contoh: 08123456789">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Alamat</label>
                            <textarea name="alamat" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors resize-none" placeholder="Masukkan alamat lengkap"><?= e($murid['alamat'] ?? '') ?></textarea>
                        </div>
                        
                        <?php if ($action === 'add'): ?>
                        <div class="bg-blue-50 rounded-xl p-4 flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm text-blue-700"><strong>Catatan:</strong> Kode unik akan dibuat otomatis untuk akses orang tua melihat perkembangan anak.</p>
                        </div>
                        <?php else: ?>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Kode Unik Akses Orang Tua *</label>
                            <div class="flex gap-3">
                                <input type="text" name="kode_unik" value="<?= e($murid['kode_unik'] ?? '') ?>" required maxlength="8" class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors font-mono uppercase tracking-wider">
                                <button type="button" onclick="generateCode()" class="px-4 py-3 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition-colors font-medium whitespace-nowrap">
                                    Generate Baru
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Kode ini digunakan orang tua untuk mengakses perkembangan anak.</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="status" value="aktif" <?= ($murid['status'] ?? 'aktif') === 'aktif' ? 'checked' : '' ?> class="w-4 h-4 text-secondary focus:ring-secondary">
                                    <span class="font-medium text-gray-700">Aktif</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="status" value="nonaktif" <?= ($murid['status'] ?? '') === 'nonaktif' ? 'checked' : '' ?> class="w-4 h-4 text-secondary focus:ring-secondary">
                                    <span class="font-medium text-gray-700">Non-Aktif</span>
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex gap-4 pt-4">
                            <a href="crud.php" class="flex-1 px-6 py-3 border-2 border-gray-200 text-gray-600 rounded-xl font-semibold text-center hover:bg-gray-50 transition-colors">
                                Batal
                            </a>
                            <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-primary to-orange-400 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-primary/30 transition-all">
                                <?= $action === 'add' ? 'Simpan Murid' : 'Simpan Perubahan' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Mobile Overlay -->
    <div class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" id="overlay" onclick="toggleSidebar()"></div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('overlay').classList.toggle('hidden');
        }
        
        function generateCode() {
            const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let code = '';
            for (let i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.querySelector('input[name="kode_unik"]').value = code;
        }
    </script>
</body>
</html>