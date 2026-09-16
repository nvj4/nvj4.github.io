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

// Cek raport yang sudah ada
 $existing_raport = null;
if ($selected_murid) {
    $stmt = $conn->prepare("SELECT * FROM raport WHERE murid_id = ? ORDER BY tahun_ajaran DESC, semester DESC LIMIT 1");
    $stmt->bind_param("i", $selected_murid);
    $stmt->execute();
    $existing_raport = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $murid_id = $_POST['murid_id'] ?? '';
    $semester = $_POST['semester'] ?? '1';
    $tahun_ajaran = $_POST['tahun_ajaran'] ?? '2024/2025';
    $perkembangan_fisik = trim($_POST['perkembangan_fisik'] ?? '');
    $perkembangan_bahasa = trim($_POST['perkembangan_bahasa'] ?? '');
    $perkembangan_kognitif = trim($_POST['perkembangan_kognitif'] ?? '');
    $perkembangan_sosial = trim($_POST['perkembangan_sosial'] ?? '');
    $perkembangan_seni = trim($_POST['perkembangan_seni'] ?? '');
    $catatan_guru = trim($_POST['catatan_guru'] ?? '');
    
    if (empty($murid_id)) {
        $error = 'Pilih murid terlebih dahulu!';
    } else {
        // Cek apakah raport sudah ada untuk semester & tahun ajaran ini
        $stmt = $conn->prepare("SELECT id FROM raport WHERE murid_id = ? AND semester = ? AND tahun_ajaran = ?");
        $stmt->bind_param("iss", $murid_id, $semester, $tahun_ajaran);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // Update
            $stmt = $conn->prepare("UPDATE raport SET perkembangan_fisik=?, perkembangan_bahasa=?, perkembangan_kognitif=?, perkembangan_sosial=?, perkembangan_seni=?, catatan_guru=? WHERE id=?");
            $stmt->bind_param("ssssssi", $perkembangan_fisik, $perkembangan_bahasa, $perkembangan_kognitif, $perkembangan_sosial, $perkembangan_seni, $catatan_guru, $existing['id']);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO raport (murid_id, semester, tahun_ajaran, perkembangan_fisik, perkembangan_bahasa, perkembangan_kognitif, perkembangan_sosial, perkembangan_seni, catatan_guru, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssi", $murid_id, $semester, $tahun_ajaran, $perkembangan_fisik, $perkembangan_bahasa, $perkembangan_kognitif, $perkembangan_sosial, $perkembangan_seni, $catatan_guru, $guru_id);
        }
        
        if ($stmt->execute()) {
            $success = 'Raport berhasil disimpan!';
        } else {
            $error = 'Gagal menyimpan raport.';
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
    <title>Input Raport - TK Ceria</title>
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
        <!-- Sidebar (sama seperti sebelumnya) -->
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
                    <span>Kelola Murid</span>
                </a>
                <a href="kegiatan.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span>Input Kegiatan</span>
                </a>
                <a href="raport.php" class="flex items-center gap-3 px-4 py-3 bg-primary/10 text-primary rounded-xl font-semibold">
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
                <h2 class="font-bold text-xl text-dark">Input Raport Semester</h2>
                <div></div>
            </div>
        </header>
        
        <div class="p-6 lg:p-8">
            <div class="max-w-4xl mx-auto">
                <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-r-xl mb-6"><?= e($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-r-xl mb-6"><?= e($success) ?></div>
                <?php endif; ?>
                
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <form method="POST" action="" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih Murid *</label>
                                <select name="murid_id" id="murid_id" required onchange="loadRaport()" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors">
                                    <option value="">-- Pilih Murid --</option>
                                    <?php foreach ($murid_list as $murid): ?>
                                    <option value="<?= $murid['id'] ?>" <?= $selected_murid == $murid['id'] ? 'selected' : '' ?>><?= e($murid['nama_lengkap']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Semester</label>
                                <select name="semester" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors">
                                    <option value="1">Semester 1</option>
                                    <option value="2">Semester 2</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Ajaran</label>
                                <input type="text" name="tahun_ajaran" value="2024/2025" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors">
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-100 pt-6">
                            <h4 class="font-bold text-lg text-dark mb-4">Perkembangan Anak</h4>
                            
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Perkembangan Fisik / Motorik</label>
                                    <textarea name="perkembangan_fisik" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors resize-none" placeholder="Contoh: Anak dapat berlari dengan baik, mampu menggunting kertas, dll"><?= e($existing_raport['perkembangan_fisik'] ?? '') ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Perkembangan Bahasa</label>
                                    <textarea name="perkembangan_bahasa" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors resize-none" placeholder="Contoh: Anak mampu menyebutkan nama lengkap, menceritakan pengalaman sederhana, dll"><?= e($existing_raport['perkembangan_bahasa'] ?? '') ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Perkembangan Kognitif</label>
                                    <textarea name="perkembangan_kognitif" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors resize-none" placeholder="Contoh: Anak dapat mengenal angka 1-10, mengenal warna, dll"><?= e($existing_raport['perkembangan_kognitif'] ?? '') ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Perkembangan Sosial Emosional</label>
                                    <textarea name="perkembangan_sosial" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors resize-none" placeholder="Contoh: Anak dapat bermain bersama teman, berbagi mainan, dll"><?= e($existing_raport['perkembangan_sosial'] ?? '') ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Perkembangan Seni</label>
                                    <textarea name="perkembangan_seni" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors resize-none" placeholder="Contoh: Anak suka mewarnai, dapat menyanyikan lagu sederhana, dll"><?= e($existing_raport['perkembangan_seni'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan Guru</label>
                            <textarea name="catatan_guru" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-secondary focus:outline-none transition-colors resize-none" placeholder="Catatan khusus untuk orang tua..."><?= e($existing_raport['catatan_guru'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="w-full py-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl font-bold hover:shadow-lg transition-all">
                            Simpan Raport
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

  <div id="ai-btn" onclick="toggleChat()" class="fixed bottom-6 right-6 w-16 h-16 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-full shadow-2xl text-white flex items-center justify-center hover:scale-110 cursor-pointer z-50 transition-all border-2 border-white">
    <svg class="w-9 h-9" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 2a2 2 0 012 2c0 .28-.06.53-.16.75l2.16 2.16C18.25 7.43 20 9.5 20 12v3a2 2 0 01-2 2h-1v2a3 3 0 01-3 3h-4a3 3 0 01-3-3v-2H6a2 2 0 01-2-2v-3c0-2.5 1.75-4.57 4-5.09V4a2 2 0 012-2m0 11a1.5 1.5 0 100-3 1.5 1.5 0 000 3m-4 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3m8 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
    </svg>
</div>

<div id="ai-panel" class="fixed bottom-24 right-6 w-80 md:w-96 h-[550px] bg-white rounded-2xl shadow-2xl z-50 hidden flex-col border border-gray-100 overflow-hidden transition-all duration-300">
    <div class="bg-indigo-600 p-4 text-white flex justify-between items-center shadow-lg">
        <div class="flex items-center gap-2">
            <div class="p-1.5 bg-white/20 rounded-lg">🤖</div>
            <span class="font-bold text-sm tracking-wide">Asisten AI Raport</span>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="confirmReset()" title="Hapus Riwayat" class="hover:text-red-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
            <button onclick="toggleChat()" class="hover:text-gray-300">✕</button>
        </div>
    </div>
    
    <div id="ai-messages" class="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50 flex flex-col text-sm">
        <div class="bg-indigo-50 border border-indigo-100 text-indigo-800 p-3 rounded-2xl rounded-tl-none self-start max-w-[85%] shadow-sm">
            Halo Guru! Pilih murid terlebih dahulu, lalu saya bisa membantu Anda menyusun narasi raport berdasarkan catatan kegiatan harian mereka. ✨
        </div>
    </div>

    <div class="p-4 bg-white border-t">
        <div class="flex gap-2">
            <input type="text" id="ai-input" onkeypress="handleKey(event)" class="flex-1 px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 text-sm" placeholder="Minta saran narasi raport...">
            <button onclick="sendToAI()" class="bg-indigo-600 text-white p-2 px-4 rounded-xl hover:bg-indigo-700 transition-all flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </div>
    </div>
</div>

<script>
const GURU_ID = <?= json_encode($_SESSION['user_id']); ?>;
const STORAGE_KEY = 'ceria_ai_history_raport_' + GURU_ID;

document.addEventListener('DOMContentLoaded', () => {
    const savedChat = localStorage.getItem(STORAGE_KEY);
    if (savedChat) {
        document.getElementById('ai-messages').innerHTML = savedChat;
        scrollChat();
    }
});

function toggleChat() {
    const panel = document.getElementById('ai-panel');
    panel.classList.toggle('hidden');
    panel.classList.toggle('flex');
    scrollChat();
}

function handleKey(e) {
    if(e.key === 'Enter') sendToAI();
}

function scrollChat() {
    const container = document.getElementById('ai-messages');
    setTimeout(() => {
        container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
    }, 100);
}

function confirmReset() {
    if(confirm('Hapus semua percakapan AI Anda?')) {
        localStorage.removeItem(STORAGE_KEY);
        location.reload();
    }
}

async function sendToAI() {
    const input = document.getElementById('ai-input');
    const container = document.getElementById('ai-messages');
    const msg = input.value.trim();
    
    if(!msg) return;

    // Ambil murid_id dari URL (Query String)
    const urlParams = new URLSearchParams(window.location.search);
    const selectedMuridId = urlParams.get('murid_id');

    // Tambah balon chat guru
    container.innerHTML += `<div class="bg-indigo-600 text-white p-3 rounded-2xl rounded-tr-none self-end ml-auto max-w-[85%] shadow-md mb-2">${msg}</div>`;
    input.value = '';
    scrollChat();

    // Simpan history sementara
    localStorage.setItem(STORAGE_KEY, container.innerHTML);

    // Animasi Loading
    const loadId = 'ai-' + Date.now();
    container.innerHTML += `<div id="${loadId}" class="text-gray-400 text-xs italic ml-2 animate-pulse">Asisten sedang menganalisis data...</div>`;
    scrollChat();

    try {
        const response = await fetch('http://127.0.0.1:8000/chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                pesan: msg,
                guru_id: GURU_ID,
                selected_murid_id: selectedMuridId // Mengirimkan ID murid yang sedang aktif
            })
        });
        
        if (!response.ok) throw new Error('Server AI tidak merespons');
        
        const data = await response.json();
        
        // Hapus loading, ganti jawaban AI (dengan format whitespace yang terjaga)
        const aiResponseHtml = `<div class="bg-white border border-gray-100 text-gray-800 p-3 rounded-2xl rounded-tl-none self-start max-w-[90%] shadow-sm leading-relaxed whitespace-pre-line">${data.jawaban}</div>`;
        document.getElementById(loadId).outerHTML = aiResponseHtml;
        
        localStorage.setItem(STORAGE_KEY, container.innerHTML);
    } catch (error) {
        document.getElementById(loadId).innerHTML = "<span class='text-red-500 font-semibold'>Koneksi Gagal. Pastikan Server Python (FastAPI) sudah berjalan di port 8000.</span>";
    }
    scrollChat();
}
</script>
    
    <div class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" id="overlay" onclick="toggleSidebar()"></div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('overlay').classList.toggle('hidden');
        }
    </script>
</body>
</html>