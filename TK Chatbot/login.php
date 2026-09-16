<?php
require_once 'api\config.php';

// Jika sudah login, redirect sesuai role
if (isLoggedIn()) {
    if (isGuru()) {
        header('Location: dashboard_guru.php');
    } else {
        header('Location: dashboard_ortu.php');
    }
    exit();
}

 $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
                $_SESSION['role'] = $row['role'];
                
                if ($row['role'] === 'guru') {
                    header('Location: dashboard_guru.php');
                } else {
                    header('Location: dashboard_ortu.php');
                }
                exit();
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
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
    <title>Login - TK Ceria</title>
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
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.5;
            animation: float 8s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        .card-enter {
            animation: cardEnter 0.6s ease-out forwards;
        }
        @keyframes cardEnter {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-soft via-white to-blue-50 flex items-center justify-center p-4 overflow-hidden relative">
    
    <!-- Decorative Blobs -->
    <div class="blob w-72 h-72 bg-secondary top-0 -left-36" style="animation-delay: 0s;"></div>
    <div class="blob w-64 h-64 bg-primary bottom-0 -right-32" style="animation-delay: 2s;"></div>
    
    <!-- Main Container -->
    <div class="w-full max-w-sm relative z-10">
        
        <!-- Logo & Title -->
        <div class="text-center mb-6 card-enter">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-lg mb-3">
                <svg class="w-9 h-9 text-primary" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/>
                </svg>
            </div>
            <h1 class="font-display text-2xl font-bold text-dark">TK Ceria</h1>
            <p class="text-gray-400 text-sm">Sistem Informasi Sekolah</p>
        </div>
        
        <!-- Login Card -->
        <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-xl p-6 card-enter" style="animation-delay: 0.1s;">
            <h2 class="font-display text-xl font-bold text-dark mb-5 text-center">Selamat Datang</h2>
            
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-3 rounded-r-lg mb-4 flex items-center gap-2 text-sm">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium"><?= e($error) ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Form Login Guru -->
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Username</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        <input 
                            type="text" 
                            name="username" 
                            class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-secondary focus:bg-white focus:ring-1 focus:ring-secondary transition-all outline-none text-sm"
                            placeholder="Masukkan username"
                            required
                        >
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Password</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="w-full pl-9 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-secondary focus:bg-white focus:ring-1 focus:ring-secondary transition-all outline-none text-sm"
                            placeholder="Masukkan password"
                            required
                        >
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" id="eyeIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-primary to-orange-500 text-white font-bold py-2.5 rounded-xl hover:shadow-lg hover:shadow-primary/20 transform hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2 text-sm"
                >
                    <span>Login Guru</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>
            
            <!-- Divider -->
            <div class="flex items-center gap-3 my-4">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-gray-300 text-xs">atau</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>
            
            <!-- Tombol Orang Tua -->
            <a 
                href="dashboard_ortu.php" 
                class="w-full bg-gradient-to-r from-secondary to-teal-400 text-white font-bold py-2.5 rounded-xl hover:shadow-lg hover:shadow-secondary/20 transform hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2 text-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Masuk sebagai Orang Tua</span>
            </a>
            
            <!-- Demo Account & Register -->
            <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400 mb-2">Demo Guru: <code class="bg-gray-50 px-1 rounded">guru1 / password</code></p>
                <p class="text-xs text-gray-500">
                    Datar Akun Sebagai Guru 
                    <a href="registrasi.php" class="text-secondary font-semibold hover:underline">Daftar disini</a>
                </p>
            </div>
        </div> <!-- End Card -->
        
        <p class="text-center text-gray-300 text-xs mt-6">
            &copy; 2024 TK Ceria.
        </p>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;
            } else {
                password.type = 'password';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
            }
        }
    </script>
</body>
</html>