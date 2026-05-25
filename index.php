<?php
// Mengambil parameter 'page' dari URL, jika kosong default ke 'home'
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

switch ($page) {
    case 'pembelian':
        // Ini akan memanggil file pembelian.php yang ada di dalam folder pembeli
        include 'pembeli/pembelian.php';
        break;
        
    case 'transaksi':
        include 'transaksi/transaksi.php';
        break;

    // Tambahkan case lain di sini sesuai nama folder dan filemu
    
    default:
        // Halaman utama jika tidak ada parameter ?page=
        echo "<h1>Selamat Datang di Dashboard</h1>"; 
        break;
}
?>