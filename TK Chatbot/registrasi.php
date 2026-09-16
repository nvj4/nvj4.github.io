
<?php
require_once 'api\config.php';

// Jika sudah login, langsung redirect
if (isLoggedIn()) {
    header('Location: dashboard_guru.php');
    exit();
}

 $error = '';
 $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $secret_key = trim($_POST['secret_key'] ?? 'HANYAUNTUKGURU');//GANTI SECRECTKEY NYA AGAR TIDAK SEMBARANG ORANG REGISTRASI MENJADI GURU

    // 1. Validasi Kode Rahasia (Paling Awal)
    if ($secret_key !== REG_SECRET_KEY) {
        $error = 'Kode Registrasi salah! Hanya staf sekolah yang boleh mendaftar.';
    } 
    // 2. Validasi Field Kosong
    elseif (empty($nama_lengkap) || empty($username) || empty($password)) {
        $error = 'Nama lengkap, username, dan password wajib diisi!';
    } 
    // 3. Validasi Panjang Username
    elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter!';
    } 
    // 4. Validasi Panjang Password
    elseif (strlen($password) < 4) {
        $error = 'Password minimal 4 karakter!';
    } 
    // 5. Validasi Konfirmasi Password
    elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } 
    // 6. Jika semua validasi dasar lolos, cek database
    else {
        $conn = getConnection();
        
        // Cek username sudah ada atau belum
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username sudah digunakan! Silakan pilih username lain.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'guru';
            
            // Insert ke database
            $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, role, no_hp) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashed_password, $nama_lengkap, $role, $no_hp);
            
            if ($stmt->execute()) {
                $success = 'Registrasi berhasil! <a href="login.php" class="underline font-bold">Login di sini</a>';
                // Reset form
                $_POST = [];
            } else {
                $error = 'Terjadi kesalahan saat menyimpan data.';
            }
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Guru - TK Ceria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
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
    <style>body { font-family: 'Nunito', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-orange-50 via-white to-teal-50 flex items-center justify-center p-4">
    
    <div class="w-full max-w-sm relative z-10">
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-lg mb-3">
                <svg class="w-9 h-9 text-secondary" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/></svg>
            </div>
            <h1 class="font-display text-2xl font-bold text-dark">Registrasi Guru</h1>
            <p class="text-gray-400 text-sm">Khusus Staf Pengajar TK Ceria</p>
        </div>
        
        <!-- Card -->
        <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-xl p-6">
            
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-3 rounded-r-lg mb-4 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <span><?= e($error) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-400 text-green-700 p-3 rounded-r-lg mb-4 text-sm">
                <?= $success ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-3">
                
                <!-- Input Kode Rahasia -->
                <div class="bg-yellow-50 p-3 rounded-xl border border-yellow-100">
                    <label class="block text-xs font-bold text-yellow-800 mb-1.5">Kode Registrasi Rahasia *</label>
                    <input 
                        type="text" 
                        name="secret_key" 
                        class="w-full px-3 py-2 bg-white border border-yellow-200 rounded-lg focus:border-yellow-400 focus:ring-1 focus:ring-yellow-400 outline-none text-sm text-center font-mono tracking-widest placeholder-yellow-300"
                        placeholder="MASUKKAN KODE"
                        required
                        value="<?= e($_POST['secret_key'] ?? '') ?>"
                    >
                    <p class="text-xs text-yellow-600 mt-1">Dapatkan kode dari Kepala Sekolah.</p>
                </div>

                <div class="border-t border-gray-100 pt-3 mt-3">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" value="<?= e($_POST['nama_lengkap'] ?? '') ?>" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-secondary focus:ring-1 focus:ring-secondary outline-none text-sm" placeholder="Nama lengkap" required>
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Username *</label>
                    <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-secondary focus:ring-1 focus:ring-secondary outline-none text-sm" placeholder="Minimal 4 karakter" required minlength="4">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">No. HP</label>
                    <input type="tel" name="no_hp" value="<?= e($_POST['no_hp'] ?? '') ?>" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-secondary focus:ring-1 focus:ring-secondary outline-none text-sm" placeholder="08xxxxxxxxxx">
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Password *</label>
                        <input type="password" name="password" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-secondary focus:ring-1 focus:ring-secondary outline-none text-sm" placeholder="Min. 4 char" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Ulangi Pass *</label>
                        <input type="password" name="confirm_password" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-secondary focus:ring-1 focus:ring-secondary outline-none text-sm" placeholder="Sama" required>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-secondary to-teal-400 text-white font-bold py-2.5 rounded-xl hover:shadow-lg transition-all text-sm mt-2">
                    Daftar Sekarang
                </button>
            </form>
            
            <div class="mt-4 text-center text-xs text-gray-400">
                Sudah punya akun? <a href="login.php" class="text-secondary font-semibold hover:underline">Login di sini</a>
            </div>
        </div>
    </div>
</body>
</html>