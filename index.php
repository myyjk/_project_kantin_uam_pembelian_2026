<?php
session_start();

// Proteksi login
if (!isset($_SESSION['isLoggedIn'])) {
    header("Location: login/login.php");
    exit();
}

// Ambil parameter page
$page = isset($_GET['page']) ? $_GET['page'] : 'pembeli';

switch ($page) {

    case 'pembeli':
    case 'pembelian':
        include 'pembeli/pembelian.php';
        break;

    case 'transaksi':
        include 'transaksi/transaksi.php';
        break;

    case 'transaksi_tambah':
        include 'transaksi/tambah_transaksi.php';
        break;

    case 'transaksi_edit':
        include 'transaksi/edit_transaksi.php';
        break;

    case 'transaksi_detail':
        include 'transaksi/detail_transaksi.php';
        break;

    case 'piutang':
    case 'hutang':
        include 'piutang/piutang.php';
        break;

    default:
        include 'pembeli/pembelian.php';
        break;
}
?>
