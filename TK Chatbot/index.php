<?php
require_once 'api\config.php';

// Jika sudah login sebagai guru, langsung ke dashboard guru
if (isLoggedIn() && isGuru()) {
    header('Location: dashboard_guru.php');
    exit();
}

// Jika belum login, arahkan ke login
header('Location: login.php');
exit();
?>