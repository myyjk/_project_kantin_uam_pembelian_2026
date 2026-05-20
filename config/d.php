<?php
// config.php
$host = "192.168.101.124";
$user = "abc";
$pass = "abc";
$db   = "db_kantin"; // Pastikan nama database ini sudah kamu buat di phpMyAdmin

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>