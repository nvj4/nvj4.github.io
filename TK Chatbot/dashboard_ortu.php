<?php
require_once 'api\config.php';

// ==========================================================
// REVISI: Reset semua status verifikasi saat kembali ke halaman ini
// Ini memastikan ortu harus input kode unik lagi setiap kali mau akses
// ==========================================================
foreach ($_SESSION as $key => $value) {
    // Cari key session yang diawali dengan 'verified_' lalu hapus
    if (strpos($key, 'verified_') === 0) {
        unset($_SESSION[$key]);
    }
}

 $conn = getConnection();

// Ambil SEMUA murid aktif
 $stmt = $conn->prepare("SELECT m.*, k.nama_kelas FROM murid m JOIN kelas k ON m.kelas_id = k.id WHERE m.status = 'aktif' ORDER BY m.nama_lengkap");
 $stmt->execute();
 $murid_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
 $stmt->close();

// Hitung statistik
 $total_murid = count($murid_list);
 $total_laki = count(array_filter($murid_list, fn($m) => $m['jenis_kelamin'] === 'L'));
 $total_perempuan = $total_murid - $total_laki;
 $kelas_unik = array_unique(array_column($murid_list, 'nama_kelas'));

 $conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TK Ceria - Portal Orang Tua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { 
                extend: { 
                    colors: { 
                        primary: '#FF6B35', 
                        secondary: '#4ECDC4', 
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
        
        /* Animations */
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUp 0.8s ease-out forwards;
        }
        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }
        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }
        
        /* Floating */
        .floating {
            animation: floating 6s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C69 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Glass */
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        
        /* Pattern */
        .pattern-bg {
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(78, 205, 196, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 107, 53, 0.08) 0%, transparent 50%);
        }
        
        /* View Transitions */
        .view-section {
            transition: opacity 0.4s ease-out, transform 0.4s ease-out;
        }
        .view-hidden {
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            visibility: hidden;
        }
        .view-visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            visibility: visible;
        }
        
        /* Card hover */
        .student-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .student-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.15);
        }
        
        /* Smooth scroll */
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-orange-50 pattern-bg flex flex-col">
    
    <!-- Floating Decorative Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-20 -left-20 w-72 h-72 bg-gradient-to-br from-secondary/20 to-teal-200/20 rounded-full blur-3xl floating"></div>
        <div class="absolute top-1/3 -right-32 w-96 h-96 bg-gradient-to-br from-primary/15 to-orange-200/15 rounded-full blur-3xl floating" style="animation-delay: -3s;"></div>
    </div>

    <!-- Header -->
    <header class="glass sticky top-0 z-50 border-b border-white/50">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="dashboard_ortu.php" class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-secondary to-teal-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-display font-bold text-xl text-dark">TK Ceria</h1>
                        <p class="text-xs text-gray-500 font-medium">Portal Orang Tua</p>
                    </div>
                </a>
                
                <a href="login.php" class="group flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary to-orange-400 text-white rounded-xl font-semibold hover:shadow-xl transition-all hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    <span>Login Guru</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Wrapper -->
    <main class="flex-grow relative z-10">
        
        <!-- VIEW 1: INTRO -->
        <section id="view-intro" class="view-section view-visible">
            <div class="max-w-7xl mx-auto px-6 py-16">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <!-- Left Content -->
                    <div class="fade-up">
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-secondary/10 rounded-full mb-6">
                            <span class="w-2 h-2 bg-secondary rounded-full animate-pulse"></span>
                            <span class="text-sm font-semibold text-secondary">Tahun Ajaran 2024/2025</span>
                        </div>
                        
                        <h2 class="font-display text-4xl md:text-5xl font-bold text-dark leading-tight mb-6">
                            Pantau <span class="gradient-text">Perkembangan</span> Anak Anda
                        </h2>
                        
                        <p class="text-lg text-gray-600 leading-relaxed mb-8 max-w-xl">
                            Akses kegiatan harian, kehadiran, dan raport anak Anda secara real-time. Sistem aman dengan kode unik privat.
                        </p>
                        
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10">
                            <div class="bg-white rounded-2xl p-4 shadow-md border border-gray-100 text-center hover:shadow-lg transition-shadow">
                                <p class="text-3xl font-bold text-dark"><?= $total_murid ?></p>
                                <p class="text-xs text-gray-500 font-medium">Total Siswa</p>
                            </div>
                            <div class="bg-white rounded-2xl p-4 shadow-md border border-gray-100 text-center hover:shadow-lg transition-shadow">
                                <p class="text-3xl font-bold text-dark"><?= count($kelas_unik) ?></p>
                                <p class="text-xs text-gray-500 font-medium">Kelas Aktif</p>
                            </div>
                            <div class="bg-white rounded-2xl p-4 shadow-md border border-gray-100 text-center hover:shadow-lg transition-shadow">
                                <p class="text-3xl font-bold text-blue-500"><?= $total_laki ?></p>
                                <p class="text-xs text-gray-500 font-medium">Laki-laki</p>
                            </div>
                            <div class="bg-white rounded-2xl p-4 shadow-md border border-gray-100 text-center hover:shadow-lg transition-shadow">
                                <p class="text-3xl font-bold text-pink-500"><?= $total_perempuan ?></p>
                                <p class="text-xs text-gray-500 font-medium">Perempuan</p>
                            </div>
                        </div>

                        <button id="btnShowList" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-primary to-orange-400 text-white rounded-2xl font-bold text-lg hover:shadow-2xl hover:shadow-primary/30 transition-all hover:-translate-y-1 flex items-center justify-center gap-3 group">
                            <span>Lihat Daftar Siswa</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Right Content -->
                    <div class="fade-up stagger-2">
                        <div class="bg-white rounded-3xl p-8 shadow-2xl border border-gray-100 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-primary/10 to-transparent rounded-bl-full"></div>
                            
                            <h3 class="font-display text-xl font-bold text-dark mb-8 relative">Cara Mengakses</h3>
                            
                            <div class="space-y-6">
                                <div class="flex gap-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-secondary to-teal-400 rounded-xl flex items-center justify-center flex-shrink-0 text-white font-bold text-lg">1</div>
                                    <div class="pt-1">
                                        <h4 class="font-semibold text-dark">Pilih Nama Anak</h4>
                                        <p class="text-sm text-gray-500">Cari dan klik nama anak dari daftar</p>
                                    </div>
                                </div>
                                
                                <div class="flex gap-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-orange-400 rounded-xl flex items-center justify-center flex-shrink-0 text-white font-bold text-lg">2</div>
                                    <div class="pt-1">
                                        <h4 class="font-semibold text-dark">Masukkan Kode Unik</h4>
                                        <p class="text-sm text-gray-500">Input kode 8 digit dari guru</p>
                                    </div>
                                </div>
                                
                                <div class="flex gap-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center flex-shrink-0 text-white font-bold text-lg">3</div>
                                    <div class="pt-1">
                                        <h4 class="font-semibold text-dark">Lihat Perkembangan</h4>
                                        <p class="text-sm text-gray-500">Akses raport & kegiatan harian</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-8 pt-6 border-t border-gray-100">
                                <p class="text-xs text-gray-400 text-center flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Data anak dilindungi dengan kode unik privat
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- VIEW 2: STUDENT LIST -->
        <section id="view-list" class="view-section view-hidden">
            <div class="max-w-7xl mx-auto px-6 py-10">
                
                <!-- Back Button & Title -->
                <div class="mb-8">
                    <button id="btnBack" class="flex items-center gap-2 text-gray-500 hover:text-primary font-semibold mb-4 transition-colors group">
                        <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Kembali ke Beranda</span>
                    </button>
                    <h2 class="font-display text-3xl font-bold text-dark">Daftar Siswa</h2>
                </div>

                <!-- Search Box -->
                <div class="bg-white rounded-2xl shadow-lg p-4 mb-8 border border-gray-100">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div id="resultCount" class="text-sm text-gray-500">
                            Menampilkan <strong class="text-dark"><?= $total_murid ?></strong> siswa
                        </div>
                        <div class="relative w-full md:w-80">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                id="searchInput"
                                class="w-full pl-12 pr-10 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:border-secondary focus:ring-0 focus:bg-white transition-all outline-none"
                                placeholder="Cari nama anak..."
                                autocomplete="off"
                            >
                            <button id="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 hidden">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (empty($murid_list)): ?>
                <div class="bg-white rounded-3xl p-16 text-center shadow-lg">
                    <p class="text-gray-400">Belum ada data siswa.</p>
                </div>
                <?php else: ?>
                <!-- Student Grid -->
                <div id="studentGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($murid_list as $murid): ?>
                    <a href="perkembangan.php?id=<?= $murid['id'] ?>" class="student-card bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 hover:border-primary/30 block" data-name="<?= strtolower(e($murid['nama_lengkap'])) ?>">
                        <div class="h-1.5 bg-gradient-to-r <?= $murid['jenis_kelamin'] === 'L' ? 'from-blue-400 to-indigo-500' : 'from-pink-400 to-rose-500' ?>"></div>
                        <div class="p-5">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-14 h-14 bg-gradient-to-br <?= $murid['jenis_kelamin'] === 'L' ? 'from-blue-400 to-indigo-500' : 'from-pink-400 to-rose-500' ?> rounded-xl flex items-center justify-center text-white text-xl font-bold shadow-md">
                                    <?= strtoupper(substr($murid['nama_lengkap'], 0, 1)) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-dark truncate student-name"><?= e($murid['nama_lengkap']) ?></h4>
                                    <p class="text-sm text-gray-400"><?= e($murid['nama_kelas']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-400">
                                <span><?= $murid['jenis_kelamin'] === 'L' ? '👦 Laki-laki' : '👧 Perempuan' ?></span>
                                <span class="text-secondary font-semibold">Lihat Detail →</span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- No Results -->
                <div id="noResults" class="hidden bg-white rounded-2xl p-12 text-center shadow-lg mt-6">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h4 class="font-bold text-dark mb-1">Tidak Ditemukan</h4>
                    <p class="text-gray-400 text-sm mb-4">Nama "<span id="searchQuery"></span>" tidak ada dalam daftar.</p>
                    <button onclick="clearSearchInput()" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-200">Reset Pencarian</button>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 mt-auto">
        <div class="bg-gradient-to-br from-dark via-slate-800 to-slate-900 text-white">
            <div class="relative -top-1">
                <svg class="w-full h-16 text-slate-50" preserveAspectRatio="none" viewBox="0 0 1440 54">
                    <path fill="currentColor" d="M0 22L60 16.7C120 11 240 1 360 0.3C480 0 600 9 720 16.7C840 24 960 31 1080 31.2C1200 31 1320 24 1380 20.2L1440 16V54H0V22Z"/>
                </svg>
            </div>
            
            <div class="max-w-7xl mx-auto px-6 py-12">
                <div class="grid md:grid-cols-3 gap-8 mb-8">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-secondary to-teal-400 rounded-xl flex items-center justify-center">
                                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-display font-bold text-lg">TK Ceria</h4>
                                <p class="text-sm text-gray-400">Membangun Generasi Cerdas</p>
                            </div>
                        </div>
                        <p class="text-gray-400 text-sm leading-relaxed">Memberikan pendidikan berkualitas untuk tumbuh kembang anak usia dini.</p>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold mb-4 text-white">Tautan Cepat</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><a href="#" class="hover:text-secondary transition-colors">Tentang Sekolah</a></li>
                            <li><a href="#" class="hover:text-secondary transition-colors">Program Pembelajaran</a></li>
                            <li><a href="login.php" class="hover:text-secondary transition-colors">Login Guru</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold mb-4 text-white">Hubungi Kami</h4>
                        <ul class="space-y-3 text-sm text-gray-400">
                            <li class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-secondary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <span>Jl. Pendidikan No. 123</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-secondary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span>(021) 123-4567</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="pt-8 border-t border-gray-700 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-500">
                    <p>&copy; 2024 TK Ceria. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        const viewIntro = document.getElementById('view-intro');
        const viewList = document.getElementById('view-list');
        const btnShowList = document.getElementById('btnShowList');
        const btnBack = document.getElementById('btnBack');
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearch');
        const studentGrid = document.getElementById('studentGrid');
        const noResults = document.getElementById('noResults');
        const resultCount = document.getElementById('resultCount');
        const searchQuery = document.getElementById('searchQuery');
        
        const totalStudents = <?= $total_murid ?>;

        function showListView() {
            viewIntro.classList.remove('view-visible');
            viewIntro.classList.add('view-hidden');
            viewList.classList.remove('view-hidden');
            viewList.classList.add('view-visible');
            setTimeout(() => { searchInput.focus(); window.scrollTo({ top: 0, behavior: 'smooth' }); }, 100);
        }

        function showIntroView() {
            viewList.classList.remove('view-visible');
            viewList.classList.add('view-hidden');
            viewIntro.classList.remove('view-hidden');
            viewIntro.classList.add('view-visible');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        btnShowList.addEventListener('click', showListView);
        btnBack.addEventListener('click', showIntroView);

        function performSearch() {
            const query = searchInput.value.toLowerCase().trim();
            const cards = studentGrid.querySelectorAll('.student-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (query === '' || name.includes(query)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (query === '') {
                resultCount.innerHTML = `Menampilkan <strong class="text-dark">${totalStudents}</strong> siswa`;
            } else {
                resultCount.innerHTML = `Ditemukan <strong class="text-dark">${visibleCount}</strong> siswa`;
            }

            clearBtn.classList.toggle('hidden', query === '');
            noResults.classList.toggle('hidden', visibleCount > 0 || query === '');
            if (query !== '' && visibleCount === 0) searchQuery.textContent = query;
        }

        function clearSearchInput() {
            searchInput.value = '';
            performSearch();
            searchInput.focus();
        }

        searchInput.addEventListener('input', performSearch);
        clearBtn.addEventListener('click', clearSearchInput);

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (viewIntro.classList.contains('view-visible')) showListView(); else searchInput.focus();
            }
            if (e.key === 'Escape') {
                if (searchInput.value !== '') clearSearchInput(); else if (viewList.classList.contains('view-visible')) showIntroView();
            }
        });
    </script>
</body>
</html>