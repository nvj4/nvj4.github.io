<?php
// Koneksi ke database
$host = 'sql313.infinityfree.com';
$db   = 'if0_39334993_sigatru';
$user = 'if0_39334993';
$pass = 'SigatruPWL01';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8";

$success = '';
$error = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nik      = trim($_POST['NIK']);
    $nama     = trim($_POST['Nama']);
    $alamat   = trim($_POST['Alamat']);
    $email    = trim($_POST['Email']);
    $password = trim($_POST['Password']);

    if ($nik && $nama && $alamat && $email && $password) {
        $cek = $pdo->prepare("SELECT * FROM user WHERE NIK = ? OR Email = ?");
        $cek->execute([$nik, $email]);

        if ($cek->rowCount() > 0) {
            $error = "NIK atau Email sudah terdaftar!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO user (NIK, Nama, Alamat, Email, Password) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nik, $nama, $alamat, $email, $hashedPassword])) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Gagal menyimpan ke database.";
            }
        }
    } else {
        $error = "Harap lengkapi semua data.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi | SIGATRU</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #9DC08B, #F8F5E9);
            color: #e0e0e0;
        }

        .container {
            width: 100%;
            max-width: 400px;
            margin: 40px auto;
            background: #3A7D44;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        h2 {
            text-align: center;
            color: #f1c40f;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        form label {
            display: block;
            margin-top: 12px;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 0.95em;
        }

        form input {
            width: 100%;
            padding: 10px;
            background-color: #1f2a35;
            border: 1px solid #444;
            border-radius: 6px;
            color: #eee;
            font-size: 1em;
        }

        form input:focus {
            outline: none;
            border-color: #f1c40f;
            box-shadow: 0 0 5px rgba(241, 196, 15, 0.5);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #f1c40f;
            color: #222;
            border: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: bold;
            font-size: 1em;
            cursor: pointer;
        }

        button:hover {
            background-color: #d4ac0d;
        }

        .message {
            text-align: center;
            padding: 10px;
            margin-top: 15px;
            border-radius: 5px;
        }

        .success {
            background-color: #2ecc71;
            color: white;
        }

        .error {
            background-color: #e74c3c;
            color: white;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #f1c40f;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media screen and (max-width: 480px) {
            .container {
                margin: 30px 16px;
                padding: 20px;
            }

            h2 {
                font-size: 1.3em;
            }

            button {
                padding: 10px;
                font-size: 0.95em;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Form Registrasi</h2>

    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="NIK">NIK</label>
        <input type="text" name="NIK" id="NIK" required>

        <label for="Nama">Nama Lengkap</label>
        <input type="text" name="Nama" id="Nama" required>

        <label for="Alamat">Alamat</label>
        <input type="text" name="Alamat" id="Alamat" required>

        <label for="Email">Email</label>
        <input type="email" name="Email" id="Email" required>

        <label for="Password">Password</label>
        <input type="password" name="Password" id="Password" required>

        <button type="submit">Daftar</button>
    </form>

    <div class="back-link">
        Sudah punya akun? <a href="index.php">Login di sini</a>
    </div>
</div>

</body>
</html>
