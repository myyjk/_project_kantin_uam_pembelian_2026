<?php
session_start();
require '../config/config.php';

// Proteksi Login
if (!isset($_SESSION['isLoggedIn'])) {
    header("Location: ../login.php"); 
    exit();
}

// Ambil ID Admin login secara fleksibel dari session yang tersedia
$id_user_login = $_SESSION['currentUser']['id'] ?? $_SESSION['currentUser']['id_admin'] ?? $_SESSION['currentUser']['id_user'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: piutang.php"); 
    exit();
}

$id_beli       = (int)$_POST['id_beli'];
$nominal_bayar = floatval($_POST['nominal_bayar']);

// Validasi nominal
if ($nominal_bayar <= 0) {
    echo "<script>alert('⚠️ Nominal bayar harus lebih dari 0!'); window.history.back();</script>"; 
    exit();
}

// Validasi foto bukti
if (!isset($_FILES['foto_faktur']) || $_FILES['foto_faktur']['error'] !== 0) {
    echo "<script>alert('⚠️ Foto bukti pembayaran wajib diupload!'); window.history.back();</script>"; 
    exit();
}

// FIX QUERY: Menghitung total belanja menggunakan id_produk (bukan id_barang/id) sesuai phpMyAdmin
$query_cek = "SELECT 
                p.id_beli,
                IFNULL(SUM(db.jumlah * db.harga), 0) AS total_belanja,
                IFNULL((SELECT SUM(ph.nominal) FROM pembayaran_hutang ph WHERE ph.id_beli = p.id_beli), 0) AS total_terbayar
              FROM pembelian p
              LEFT JOIN detail_beli db ON p.id_beli = db.id_beli
              WHERE p.id_beli = '$id_beli'
              GROUP BY p.id_beli";

$cek = mysqli_query($conn, $query_cek);
$data = mysqli_fetch_assoc($cek);

// Jika data tidak ditemukan di database
if (!$data) {
    echo "<script>alert('⚠️ Data transaksi tidak ditemukan di database!'); window.location='piutang.php';</script>"; 
    exit();
}

$total_belanja  = floatval($data['total_belanja']);
$total_terbayar = floatval($data['total_terbayar']);
$sisa_hutang    = $total_belanja - $total_terbayar;

if ($sisa_hutang <= 0) {
    echo "<script>alert('⚠️ Hutang untuk transaksi ini sudah lunas!'); window.location='piutang.php';</script>"; 
    exit();
}

// Batasi jika input bayar melebihi sisa hutang
if ($nominal_bayar > $sisa_hutang) {
    $nominal_bayar = $sisa_hutang;
}

// Proses Upload Foto Bukti Pembayaran
$target_dir = "upload/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$ext     = strtolower(pathinfo($_FILES['foto_faktur']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowed)) {
    echo "<script>alert('⚠️ Format foto harus JPG, PNG, atau WEBP!'); window.history.back();</script>"; 
    exit();
}

$nama_file = "bayar_{$id_beli}_" . time() . ".$ext";
if (!move_uploaded_file($_FILES['foto_faktur']['tmp_name'], $target_dir . $nama_file)) {
    echo "<script>alert('⚠️ Gagal mengupload foto bukti ke server!'); window.history.back();</script>"; 
    exit();
}

// Simpan data pembayaran baru ke tabel pembayaran_hutang
$tanggal = date('Y-m-d H:i:s');
$nominal_bayar_safe = mysqli_real_escape_string($conn, $nominal_bayar);
$nama_file_safe     = mysqli_real_escape_string($conn, $nama_file);

$insert = "INSERT INTO pembayaran_hutang (id_beli, nominal, tanggal, foto_faktur) 
           VALUES ('$id_beli', '$nominal_bayar_safe', '$tanggal', '$nama_file_safe')";

if (mysqli_query($conn, $insert)) {
    $sisa_setelah = $sisa_hutang - $nominal_bayar;
    $pesan = $sisa_setelah <= 0 
        ? '✅ Pembayaran berhasil! Hutang transaksi ini dinyatakan LUNAS.' 
        : '✅ Pembayaran berhasil dicatat! Sisa hutang saat ini: Rp ' . number_format($sisa_setelah, 0, ',', '.');
    
    echo "<script>alert('$pesan'); window.location='piutang.php';</script>";
} else {
    echo "<script>alert('❌ Gagal menyimpan pembayaran: " . mysqli_error($conn) . "'); window.history.back();</script>";
}
?>