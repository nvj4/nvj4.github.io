<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sigatru";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nik      = mysqli_real_escape_string($conn, $_POST['NIK']);
    $nama     = mysqli_real_escape_string($conn, $_POST['Nama']);
    $alamat   = mysqli_real_escape_string($conn, $_POST['Alamat']);
    $email    = mysqli_real_escape_string($conn, $_POST['Email']);
    $password = mysqli_real_escape_string($conn, $_POST['Password']);

    if ($nik && $nama && $alamat && $email && $password) {
        $cek = mysqli_query($conn, "SELECT * FROM user WHERE NIK='$nik' OR Email='$email'");
        if (mysqli_num_rows($cek) > 0) {
            $error = "NIK atau Email sudah terdaftar!";
        } else {
            $query = "INSERT INTO user (NIK, Nama, Alamat, Email, Password) 
                      VALUES ('$nik', '$nama', '$alamat', '$email', '$password')";
            if (mysqli_query($conn, $query)) {
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
    <title>Registrasi | SIGATRU</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #1a1a1d, #2c3e50);
            color: #e0e0e0;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 420px;
            margin: 50px auto;
            background: #2c3e50;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        }

        h2 {
            text-align: center;
            color: #f1c40f;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            margin-top: 15px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            background: #1f2a35;
            border: 1px solid #444;
            border-radius: 6px;
            color: #eee;
        }

        input:focus {
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
            font-weight: bold;
            font-size: 1em;
            border-radius: 8px;
            margin-top: 25px;
            cursor: pointer;
        }

        button:hover {
            background-color: #d4ac0d;
        }

        .message {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
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
    </style>
</head>
<body>

<div class="container">
    <h2>Form Registrasi</h2>

    <?php if ($success): ?>
        <div class="message success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="message error"><?= $error ?></div>
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
        Sudah punya akun? <a href="login.php">Login di sini</a>
    </div>
</div>

</body>
</html>
