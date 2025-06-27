<?php
session_start();
$host = 'localhost';
$db = 'sigatru';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8";

$error = '';
$role = $_GET['role'] ?? 'user';

try {
    $pdo = new PDO($dsn, $user, $pass);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Login tamu via icon
if (isset($_GET['guest'])) {
    $_SESSION['username'] = "Tamu";
    $_SESSION['role'] = "guest";
    header("Location: projectWarga.php");
    exit();
}

// Proses login dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if ($role === 'admin') {
        // Admin login - bisa dibuat validasi hardcode atau DB
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'admin';
            header("Location: admin.php");
            exit();
        } else {
            $error = "Login admin gagal!";
        }
    } else {
        // User login dari database
        $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :username AND password = :password");
        $stmt->execute(['username' => $username, 'password' => $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['username'] = $user['nama'];
            $_SESSION['role'] = 'user';
            header("Location: projectWarga.php");
            exit();
        } else {
            $error = "Login gagal! Email atau password salah.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login | SIGATRU</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1d, #2c3e50);
            margin: 0;
            padding: 0;
            color: #e0e0e0;
        }

        .welcome-title {
            text-align: center;
            font-size: 2.3em;
            font-weight: bold;
            padding-top: 50px;
            animation: fadeInUp 1s ease-out;
        }

        .welcome-title span {
            color: #f1c40f;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes glow {
            from { text-shadow: 0 0 5px #f1c40f; }
            to { text-shadow: 0 0 15px #f39c12, 0 0 25px #e67e22; }
        }

        .login-container {
            width: 380px;
            margin: 40px auto;
            background: #2c3e50;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .tab-menu {
            display: flex;
            justify-content: space-around;
            margin-bottom: 25px;
            border-bottom: 2px solid #444;
        }

        .tab-menu a {
            text-decoration: none;
            color: #aaa;
            font-weight: bold;
            font-size: 1em;
            padding-bottom: 8px;
            transition: 0.3s;
            position: relative;
        }

        .tab-menu a.active {
            color: #f1c40f;
        }

        .tab-menu a.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #f1c40f;
            border-radius: 2px;
        }

        h2 {
            text-align: center;
            color: #f1f1f1;
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            background: #1f2a35;
            border: 1px solid #444;
            border-radius: 8px;
            color: #eee;
            transition: 0.3s;
        }

        input:focus {
            border-color: #f1c40f;
            outline: none;
            box-shadow: 0 0 6px rgba(241, 196, 15, 0.3);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #f1c40f;
            color: #222;
            border: none;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #d4ac0d;
        }

        .register-link {
            text-align: center;
            margin-top: 15px;
        }

        .register-link a {
            text-decoration: none;
            color: #f1c40f;
            font-size: 0.95em;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .error {
            color: #ff4d4d;
            text-align: center;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .guest-btn {
            text-align: center;
            margin-top: 25px;
        }

        .guest-btn a {
            font-size: 2.2em;
            display: inline-block;
            padding: 10px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }

        .guest-btn a:hover {
            background-color: #2980b9;
        }

        .guest-btn label {
            display: block;
            margin-top: 8px;
            font-size: 0.85em;
            color: #ccc;
        }

        /* Loading Spinner */
        #loading-spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            border: 6px solid #f3f3f3;
            border-top: 6px solid #f1c40f;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="welcome-title">
        Welcome to <span>SIGATRU</span>
    </div>

    <div class="login-container">
        <div class="tab-menu">
            <a href="?role=user" class="<?= ($role == 'user') ? 'active' : '' ?>">Login User</a>
            <a href="?role=admin" class="<?= ($role == 'admin') ? 'active' : '' ?>">Login Admin</a>
        </div>

        <h2>Login <?= ucfirst($role) ?></h2>

        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <input type="hidden" name="role" value="<?= $role ?>">
            <input type="text" name="username" placeholder="Email / Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <?php if ($role == 'user'): ?>
            <div class="register-link">
                Belum punya akun? <a href="regis.php">Daftar di sini</a>
            </div>
        <?php endif; ?>

        <!-- Icon Guest Login 👤 -->
        <div class="guest-btn">
            <a href="?guest=1" title="Masuk sebagai Tamu">👤</a>
            <label>Masuk sebagai tamu</label>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-spinner">
        <div class="spinner"></div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function () {
            document.getElementById('loading-spinner').style.display = 'flex';
            // tunggu 5 detik, lalu kirim form
    setTimeout(() => {
        e.target.submit();
    }, 500);
        });
    </script>
</body>
</html>
