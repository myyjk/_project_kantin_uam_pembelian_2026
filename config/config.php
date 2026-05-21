<?php
// config.php
$host = "localhost"; // Biasanya localhost, tapi bisa berbeda jika hosting
$user = "root"; // Biasanya root, tapi bisa berbeda jika hosting
$pass = ""; // Biasanya kosong, tapi bisa berbeda jika hosting
$db   = "db_kantin"; // Pastikan nama database ini sudah kamu buat di phpMyAdmin

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>