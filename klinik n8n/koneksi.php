<?php

$koneksi = mysqli_connect("localhost", "root", "", "klinik_db");

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

?>