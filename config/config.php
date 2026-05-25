<?php
// config.php
$host = "192.168.5.206"; // Biasanya localhost, tapi bisa berbeda jika hosting
$user = "abc"; // Biasanya root, tapi bisa berbeda jika hosting
$pass = "abc"; // Biasanya kosong, tapi bisa berbeda jika hosting
$db   = "db_kantin"; // Pastikan nama database ini sudah kamu buat di phpMyAdmin

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>