-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 28, 2026 at 08:50 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_kantin`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id` int UNSIGNED NOT NULL,
  `nama` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_kat` int DEFAULT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `harga_beli` decimal(12,2) NOT NULL DEFAULT '0.00',
  `harga_jual` decimal(12,2) NOT NULL DEFAULT '0.00',
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_jual` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `barcode` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_menu` enum('makanan','minuman','snack','lainnya') COLLATE utf8mb4_unicode_ci DEFAULT 'makanan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `nama`, `id_kat`, `stok`, `harga_beli`, `harga_jual`, `foto`, `foto_jual`, `aktif`, `created_at`, `updated_at`, `barcode`, `jenis_menu`) VALUES
(1, 'Es teh ', 1, -24, '4000.00', '5000.00', 'esteh.png', NULL, 1, '2026-05-13 15:46:59', '2026-05-28 15:22:28', NULL, 'makanan'),
(2, 'es jeruk', 1, 2, '2500.00', '5000.00', 'esjeruk.jpg', NULL, 1, '2026-05-20 10:53:37', '2026-05-28 15:22:28', NULL, 'makanan'),
(3, 'gacoan', 2, -20, '10000.00', '15000.00', 'gacoan.jpg', NULL, 1, '2026-05-20 11:20:03', '2026-05-28 15:22:28', NULL, 'makanan'),
(4, 'Ultra milk', NULL, -33, '20000.00', '22000.00', 'ultramilk.png', NULL, 1, '2026-05-25 09:20:33', '2026-05-28 15:22:28', NULL, 'minuman'),
(5, 'Ayam KFC', NULL, -20, '50000.00', '80000.00', 'https://drive.google.com/file/d/1r8yMMNbFzsguaq1dq55S6JE6XJ3AlcOT/view?usp=drive_link', NULL, 1, '2026-05-26 12:04:53', '2026-05-28 15:22:28', NULL, 'makanan');

-- --------------------------------------------------------

--
-- Table structure for table `detail_beli`
--

CREATE TABLE `detail_beli` (
  `id_detail` int NOT NULL,
  `id_beli` int NOT NULL,
  `id_produk` int NOT NULL,
  `jumlah` int NOT NULL DEFAULT '0',
  `harga` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_beli`
--

INSERT INTO `detail_beli` (`id_detail`, `id_beli`, `id_produk`, `jumlah`, `harga`) VALUES
(77, 53, 4, 1, '22000.00'),
(78, 53, 3, 1, '15000.00'),
(79, 53, 2, 1, '5000.00'),
(80, 54, 5, 1, '80000.00'),
(81, 54, 4, 1, '22000.00'),
(82, 54, 3, 1, '15000.00'),
(83, 54, 2, 1, '5000.00'),
(84, 54, 1, 1, '5000.00');

-- --------------------------------------------------------

--
-- Table structure for table `detail_penjualan`
--

CREATE TABLE `detail_penjualan` (
  `id` int UNSIGNED NOT NULL,
  `id_penjualan` int UNSIGNED NOT NULL,
  `id_barang` int UNSIGNED DEFAULT NULL,
  `nama_item` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int NOT NULL DEFAULT '1',
  `harga` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) GENERATED ALWAYS AS ((`qty` * `harga`)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detail_penjualan`
--

INSERT INTO `detail_penjualan` (`id`, `id_penjualan`, `id_barang`, `nama_item`, `qty`, `harga`) VALUES
(1, 1, NULL, 'Kripik Lumba-lumba', 3, '15000.00'),
(2, 2, NULL, 'Kripik Lumba-lumba', 2, '15000.00'),
(3, 3, NULL, 'Seblak', 6, '15000.00'),
(4, 5, 3, 'Seblak', 3, '15000.00'),
(5, 5, 1, 'Es teh ', 1, '5000.00'),
(20, 20, NULL, 'Seblak', 1, '15000.00'),
(21, 21, NULL, 'Seblak', 4, '15000.00'),
(22, 22, NULL, 'Seblak', 4, '15000.00'),
(23, 23, NULL, 'Seblak', 1, '15000.00'),
(42, 42, 3, 'gacoan', 2, '15000.00'),
(43, 43, 3, 'gacoan', 1, '15000.00'),
(44, 44, 3, 'gacoan', 1, '15000.00'),
(45, 45, 3, 'gacoan', 4, '15000.00'),
(46, 46, 3, 'gacoan', 1, '15000.00'),
(47, 47, 1, 'Es teh ', 1, '5000.00'),
(48, 47, 2, 'es jeruk', 1, '5000.00'),
(49, 47, 3, 'gacoan', 1, '15000.00'),
(50, 48, 1, 'Es teh ', 1, '5000.00'),
(51, 48, 2, 'es jeruk', 1, '5000.00'),
(52, 48, 3, 'gacoan', 1, '15000.00'),
(53, 49, 1, 'Es teh ', 1, '5000.00'),
(54, 49, 2, 'es jeruk', 1, '5000.00'),
(55, 49, 3, 'gacoan', 1, '15000.00'),
(56, 50, 1, 'Es teh ', 1, '5000.00'),
(57, 50, 2, 'es jeruk', 1, '5000.00'),
(58, 50, 3, 'gacoan', 1, '15000.00');

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id_detail` int NOT NULL,
  `id_transaksi` int NOT NULL,
  `id_produk` int NOT NULL COMMENT 'referensi ke barang.id',
  `jumlah` int NOT NULL DEFAULT '1',
  `harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_produk`, `jumlah`, `harga`, `subtotal`) VALUES
(15, 7, 4, 1, '22000.00', '22000.00'),
(16, 7, 3, 1, '15000.00', '15000.00'),
(17, 7, 2, 1, '5000.00', '5000.00'),
(18, 8, 5, 1, '80000.00', '80000.00'),
(19, 8, 4, 1, '22000.00', '22000.00'),
(20, 8, 3, 1, '15000.00', '15000.00'),
(21, 8, 2, 1, '5000.00', '5000.00'),
(22, 8, 1, 1, '5000.00', '5000.00');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `warna` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `warna`) VALUES
(1, 'Minuman', NULL),
(2, 'makanan\r\n', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran_hutang`
--

CREATE TABLE `pembayaran_hutang` (
  `id_pembayaran` int NOT NULL,
  `id_beli` int DEFAULT NULL,
  `nominal` decimal(12,2) DEFAULT NULL,
  `tanggal` datetime DEFAULT NULL,
  `foto_faktur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pembayaran_hutang`
--

INSERT INTO `pembayaran_hutang` (`id_pembayaran`, `id_beli`, `nominal`, `tanggal`, `foto_faktur`) VALUES
(1, 22, '250000.00', '2026-05-19 04:36:08', 'bayar_22_1779158168.jpg'),
(2, 24, '116000.00', '2026-05-19 07:09:14', 'bayar_24_1779167354.jpg'),
(3, 37, '30000.00', '2026-05-20 06:53:29', 'bayar_37_1779252809.jpg'),
(4, 39, '45000.00', '2026-05-20 07:28:16', 'bayar_39_1779254896.jpg'),
(5, 38, '55000.00', '2026-05-21 04:14:06', 'bayar_38_1779329646.jpg'),
(6, 45, '25000.00', '2026-05-26 07:00:22', 'bayar_45_1779771622.jpg'),
(7, 51, '242333.00', '2026-05-28 08:01:36', 'bayar_51_1779948096.png'),
(8, 51, '91667.00', '2026-05-28 08:02:08', 'bayar_51_1779948128.jpg'),
(9, 52, '15000.00', '2026-05-28 08:38:53', 'bayar_52_1779950333.jpeg'),
(10, 53, '42000.00', '2026-05-28 10:22:01', 'bayar_53_1779956521.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `pembelian`
--

CREATE TABLE `pembelian` (
  `id_beli` int NOT NULL,
  `no_faktur` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_beli` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_admin` int UNSIGNED NOT NULL,
  `id_vendor` int NOT NULL,
  `metode` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `metode_pembayaran` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_bayar` decimal(12,2) DEFAULT NULL,
  `dibayar` decimal(12,2) DEFAULT NULL,
  `sisa_hutang` decimal(12,2) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_faktur` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembelian`
--

INSERT INTO `pembelian` (`id_beli`, `no_faktur`, `tanggal_beli`, `id_admin`, `id_vendor`, `metode`, `metode_pembayaran`, `total_bayar`, `dibayar`, `sisa_hutang`, `status`, `foto_faktur`) VALUES
(53, '12345', '2026-05-28 10:21:41', 3, 1, 'Hutang', NULL, NULL, NULL, NULL, NULL, NULL),
(54, 'INV-20260002', '2026-05-28 10:22:28', 3, 2, 'Tunai', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `id` int UNSIGNED NOT NULL,
  `id_kasir` int UNSIGNED DEFAULT NULL,
  `nama_pembeli` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Umum',
  `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pajak` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `metode` enum('Cash','Piutang','Transfer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Cash',
  `status` enum('Lunas','Piutang','Batal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Lunas',
  `uang_masuk` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kembalian` decimal(12,2) NOT NULL DEFAULT '0.00',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `tgl_trx` date GENERATED ALWAYS AS (cast(`tanggal` as date)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penjualan`
--

INSERT INTO `penjualan` (`id`, `id_kasir`, `nama_pembeli`, `tanggal`, `subtotal`, `diskon`, `pajak`, `total`, `metode`, `status`, `uang_masuk`, `kembalian`, `keterangan`) VALUES
(1, 2, 'Davin', '2026-05-19 12:15:04', '45000.00', '2250.00', '3848.00', '46598.00', 'Piutang', 'Piutang', '46598.00', '0.00', 'Hutang dulu laper tapi nggak ada uang'),
(2, 2, 'Selvi', '2026-05-19 13:56:36', '30000.00', '0.00', '0.00', '30000.00', 'Cash', 'Lunas', '50000.00', '20000.00', ''),
(3, 2, 'Selvi', '2026-05-19 14:30:42', '90000.00', '9000.00', '0.00', '81000.00', 'Piutang', 'Piutang', '81000.00', '0.00', 'Bentar kak bokek'),
(4, 2, 'Radit', '2026-05-19 15:06:39', '45000.00', '0.00', '0.00', '45000.00', 'Transfer', 'Lunas', '45000.00', '0.00', ''),
(5, 2, 'Haqqi', '2026-05-20 11:21:52', '5000.00', '0.00', '0.00', '5000.00', 'Cash', 'Lunas', '20000.00', '15000.00', ''),
(20, 2, 'Davin', '2026-05-20 13:10:32', '15000.00', '0.00', '0.00', '15000.00', 'Cash', 'Lunas', '100000.00', '85000.00', ''),
(21, 2, 'Davin', '2026-05-20 13:12:55', '60000.00', '0.00', '0.00', '60000.00', 'Piutang', 'Piutang', '60000.00', '0.00', ''),
(22, 2, 'Davin', '2026-05-20 13:13:53', '60000.00', '0.00', '0.00', '60000.00', 'Piutang', 'Piutang', '60000.00', '0.00', ''),
(23, 2, 'Davin', '2026-05-20 13:15:25', '15000.00', '0.00', '0.00', '15000.00', 'Transfer', 'Lunas', '15000.00', '0.00', ''),
(42, 2, 'Davin', '2026-05-21 11:43:43', '30000.00', '0.00', '0.00', '30000.00', 'Cash', 'Lunas', '100000.00', '70000.00', ''),
(43, 2, 'Umum', '2026-05-21 11:44:04', '15000.00', '0.00', '0.00', '15000.00', 'Cash', 'Lunas', '100000.00', '85000.00', ''),
(44, 2, 'Selvi', '2026-05-21 11:44:26', '15000.00', '0.00', '0.00', '15000.00', 'Cash', 'Lunas', '100000.00', '85000.00', ''),
(45, 2, 'Umum', '2026-05-21 14:10:02', '60000.00', '0.00', '0.00', '60000.00', 'Cash', 'Lunas', '100000.00', '40000.00', ''),
(46, 2, 'Umum', '2026-05-21 14:38:41', '15000.00', '0.00', '0.00', '15000.00', 'Cash', 'Lunas', '50000.00', '35000.00', ''),
(47, 2, 'Umum', '2026-05-21 14:42:44', '25000.00', '0.00', '0.00', '25000.00', 'Cash', 'Lunas', '100000.00', '75000.00', ''),
(48, 2, 'Umum', '2026-05-21 14:47:59', '25000.00', '0.00', '0.00', '25000.00', 'Cash', 'Lunas', '100000.00', '75000.00', ''),
(49, 2, 'Umum', '2026-05-21 14:55:15', '25000.00', '0.00', '0.00', '25000.00', 'Cash', 'Lunas', '100000.00', '75000.00', ''),
(50, 2, 'Umum', '2026-05-21 14:56:05', '25000.00', '0.00', '0.00', '25000.00', 'Cash', 'Lunas', '100000.00', '75000.00', '');

-- --------------------------------------------------------

--
-- Table structure for table `piutang`
--

CREATE TABLE `piutang` (
  `id` int UNSIGNED NOT NULL,
  `id_penjualan` int UNSIGNED DEFAULT NULL,
  `nama_pembeli` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_hutang` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sisa_hutang` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('belum_lunas','lunas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum_lunas',
  `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `keterangan` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `piutang`
--

INSERT INTO `piutang` (`id`, `id_penjualan`, `nama_pembeli`, `total_hutang`, `sisa_hutang`, `status`, `tanggal`, `updated_at`, `keterangan`) VALUES
(1, 1, 'Davin', '46598.00', '0.00', 'lunas', '2026-05-19 12:15:04', '2026-05-19 12:20:09', 'Hutang dulu laper tapi nggak ada uang'),
(2, 3, 'Selvi', '81000.00', '0.00', 'lunas', '2026-05-19 14:30:42', '2026-05-19 15:35:39', 'Bentar kak bokek'),
(3, 21, 'Davin', '60000.00', '0.00', 'lunas', '2026-05-20 13:12:55', '2026-05-20 13:58:18', ''),
(4, 22, 'Davin', '60000.00', '0.00', 'lunas', '2026-05-20 13:13:53', '2026-05-20 13:14:42', '');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_bayar_piutang`
--

CREATE TABLE `riwayat_bayar_piutang` (
  `id` int UNSIGNED NOT NULL,
  `id_piutang` int UNSIGNED NOT NULL,
  `jumlah_bayar` decimal(12,2) NOT NULL,
  `sisa_sebelum` decimal(12,2) NOT NULL,
  `sisa_sesudah` decimal(12,2) NOT NULL,
  `dibayar_oleh` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bukti_foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `riwayat_bayar_piutang`
--

INSERT INTO `riwayat_bayar_piutang` (`id`, `id_piutang`, `jumlah_bayar`, `sisa_sebelum`, `sisa_sesudah`, `dibayar_oleh`, `tanggal`, `bukti_foto`) VALUES
(1, 1, '30000.00', '46598.00', '16598.00', 'Muhammad Davin Nur Ardiyanto', '2026-05-19 12:19:58', NULL),
(2, 1, '16598.00', '16598.00', '0.00', 'Muhammad Davin Nur Ardiyanto', '2026-05-19 12:20:09', NULL),
(3, 2, '10000.00', '81000.00', '71000.00', 'Muhammad Davin Nur Ardiyanto', '2026-05-19 14:31:10', NULL),
(4, 2, '1000.00', '71000.00', '70000.00', 'Muhammad Davin Nur Ardiyanto', '2026-05-19 14:31:25', NULL),
(5, 2, '70000.00', '70000.00', '0.00', 'Muhammad Davin Nur Ardiyanto', '2026-05-19 15:35:39', NULL),
(6, 3, '10000.00', '60000.00', '50000.00', 'Muhammad Davin Nur Ardiyanto', '2026-05-20 13:13:10', NULL),
(7, 4, '60000.00', '60000.00', '0.00', 'Muhammad Davin Nur Ardiyanto', '2026-05-20 13:14:42', NULL),
(8, 3, '50000.00', '50000.00', '0.00', 'Muhammad Davin Nur Ardiyanto', '2026-05-20 13:58:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` tinyint UNSIGNED NOT NULL,
  `kode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `kode`, `nama`) VALUES
(1, 'admin', 'Admin'),
(2, 'kasir', 'Kasir');

-- --------------------------------------------------------

--
-- Table structure for table `statistik`
--

CREATE TABLE `statistik` (
  `id` int UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `total_omset` decimal(15,2) NOT NULL DEFAULT '0.00',
  `pembayaran_tunai` decimal(15,2) NOT NULL DEFAULT '0.00',
  `pembayaran_piutang` decimal(15,2) NOT NULL DEFAULT '0.00',
  `jumlah_transaksi` int UNSIGNED NOT NULL DEFAULT '0',
  `pembayaran_transfer` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int NOT NULL,
  `no_faktur` varchar(30) NOT NULL,
  `id_beli` int NOT NULL,
  `tanggal` date NOT NULL,
  `metode_pembayaran` enum('tunai','hutang') NOT NULL DEFAULT 'tunai',
  `total_harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','lunas','batal') NOT NULL DEFAULT 'pending',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `no_faktur`, `id_beli`, `tanggal`, `metode_pembayaran`, `total_harga`, `status`, `keterangan`, `created_at`) VALUES
(7, '12345', 53, '2026-05-28', 'hutang', '42000.00', 'lunas', NULL, '2026-05-28 08:21:41'),
(8, 'INV-20260002', 54, '2026-05-28', 'tunai', '127000.00', 'lunas', NULL, '2026-05-28 08:22:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `id_role` tinyint UNSIGNED NOT NULL DEFAULT '2',
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `namalengkap` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telepon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_role`, `username`, `namalengkap`, `email`, `password`, `foto`, `telepon`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin', 'Administrator', 'admin@kantin.id', 'uamkantin', NULL, NULL, '2026-05-04 10:05:00', '2026-05-04 15:46:52'),
(2, 2, 'Davin Nur', 'Muhammad Davin Nur Ardiyanto', 'mdavinnura16@gmail.com', '$2y$12$8ZBDmowH9itWLq5jxPUYM.LlLWt3AGK0iU2/UuvMwkngWPL5eieES', NULL, '085851779038', '2026-05-11 15:40:06', '2026-05-25 08:41:30'),
(3, 2, 'sastra', 'Rahmatullah Sastra Ardiansyah', 'rahmatullahsastraardiansyah@gmail.com', '$2a$12$R5OyAUZutshrT5WMqWsp5OAMFsADpZ9GHN4bFTyIeLAzKSCSJGJpS', 'profil_3_1779956623.png', '12345', '2026-05-19 12:56:05', '2026-05-28 10:23:43'),
(4, 2, 'tes', 'testing', 'modeprivasi18@gmail.com', '$2a$12$OMrVziQTloXK1uSWmP/uOOs6H8C4UPyUsCAVAJ7LDN1dtj6FtX5Ti', NULL, NULL, '2026-05-20 12:35:17', '2026-05-20 12:37:11');

-- --------------------------------------------------------

--
-- Table structure for table `vendor`
--

CREATE TABLE `vendor` (
  `id_vendor` int NOT NULL,
  `nama` varchar(300) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendor`
--

INSERT INTO `vendor` (`id_vendor`, `nama`) VALUES
(1, 'sastra'),
(2, 'Davin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_stok_aktif` (`aktif`),
  ADD KEY `idx_kat_aktif` (`id_kat`,`aktif`);

--
-- Indexes for table `detail_beli`
--
ALTER TABLE `detail_beli`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_detail_pembelian` (`id_beli`),
  ADD KEY `fk_detail_produk` (`id_produk`);

--
-- Indexes for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_penjualan` (`id_penjualan`),
  ADD KEY `detail_penjualan_ibfk_2` (`id_barang`);

--
-- Indexes for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `pembayaran_hutang`
--
ALTER TABLE `pembayaran_hutang`
  ADD PRIMARY KEY (`id_pembayaran`);

--
-- Indexes for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`id_beli`),
  ADD KEY `fk_pembelian_vendor` (`id_vendor`),
  ADD KEY `fk_pembelian_admin` (`id_admin`);

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_kasir` (`id_kasir`),
  ADD KEY `idx_penjualan_tanggal` (`tanggal`),
  ADD KEY `idx_penjualan_status` (`status`),
  ADD KEY `idx_tgl_status` (`tgl_trx`,`status`),
  ADD KEY `idx_tgl_metode` (`tgl_trx`,`metode`);

--
-- Indexes for table `piutang`
--
ALTER TABLE `piutang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_penjualan` (`id_penjualan`),
  ADD KEY `idx_piutang_status` (`status`),
  ADD KEY `idx_status_tgl` (`status`,`tanggal`),
  ADD KEY `idx_nama` (`nama_pembeli`);

--
-- Indexes for table `riwayat_bayar_piutang`
--
ALTER TABLE `riwayat_bayar_piutang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_piutang` (`id_piutang`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama` (`nama`);

--
-- Indexes for table `statistik`
--
ALTER TABLE `statistik`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tanggal_unik` (`tanggal`),
  ADD UNIQUE KEY `uq_tanggal` (`tanggal`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD UNIQUE KEY `no_faktur` (`no_faktur`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_role` (`id_role`);

--
-- Indexes for table `vendor`
--
ALTER TABLE `vendor`
  ADD PRIMARY KEY (`id_vendor`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `detail_beli`
--
ALTER TABLE `detail_beli`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pembayaran_hutang`
--
ALTER TABLE `pembayaran_hutang`
  MODIFY `id_pembayaran` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pembelian`
--
ALTER TABLE `pembelian`
  MODIFY `id_beli` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `piutang`
--
ALTER TABLE `piutang`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `riwayat_bayar_piutang`
--
ALTER TABLE `riwayat_bayar_piutang`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `statistik`
--
ALTER TABLE `statistik`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vendor`
--
ALTER TABLE `vendor`
  MODIFY `id_vendor` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_beli`
--
ALTER TABLE `detail_beli`
  ADD CONSTRAINT `fk_detail_pembelian` FOREIGN KEY (`id_beli`) REFERENCES `pembelian` (`id_beli`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_penjualan_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

--
-- Constraints for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE;

--
-- Constraints for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD CONSTRAINT `fk_pembelian_vendor` FOREIGN KEY (`id_vendor`) REFERENCES `vendor` (`id_vendor`) ON UPDATE CASCADE;

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`id_kasir`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `piutang`
--
ALTER TABLE `piutang`
  ADD CONSTRAINT `piutang_ibfk_1` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `riwayat_bayar_piutang`
--
ALTER TABLE `riwayat_bayar_piutang`
  ADD CONSTRAINT `riwayat_bayar_piutang_ibfk_1` FOREIGN KEY (`id_piutang`) REFERENCES `piutang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
