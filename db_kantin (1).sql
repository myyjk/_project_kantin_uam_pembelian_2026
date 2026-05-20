-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 20, 2026 at 04:03 AM
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
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `nama`, `id_kat`, `stok`, `harga_beli`, `harga_jual`, `foto`, `aktif`, `created_at`, `updated_at`) VALUES
(1, 'Es teh ', 1, 4, '4000.00', '5000.00', 'esteh.png', 1, '2026-05-13 15:46:59', '2026-05-20 10:53:10'),
(2, 'es jeruk', 1, 2, '2500.00', '5000.00', 'esjeruk.jpg', 1, '2026-05-20 10:53:37', NULL);

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
(1, 1, 1, 'Kripik Lumba-lumba', 3, '15000.00'),
(2, 2, 1, 'Kripik Lumba-lumba', 2, '15000.00'),
(3, 3, 1, 'Seblak', 6, '15000.00'),
(4, 4, 1, 'Seblak', 3, '15000.00');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(1, 'Minuman'),
(2, 'makanan\r\n');

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
(2, 24, '116000.00', '2026-05-19 07:09:14', 'bayar_24_1779167354.jpg');

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
  `keterangan` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penjualan`
--

INSERT INTO `penjualan` (`id`, `id_kasir`, `nama_pembeli`, `tanggal`, `subtotal`, `diskon`, `pajak`, `total`, `metode`, `status`, `uang_masuk`, `kembalian`, `keterangan`) VALUES
(1, 2, 'Davin', '2026-05-19 12:15:04', '45000.00', '2250.00', '3848.00', '46598.00', 'Piutang', 'Piutang', '46598.00', '0.00', 'Hutang dulu laper tapi nggak ada uang'),
(2, 2, 'Selvi', '2026-05-19 13:56:36', '30000.00', '0.00', '0.00', '30000.00', 'Cash', 'Lunas', '50000.00', '20000.00', ''),
(3, 2, 'Selvi', '2026-05-19 14:30:42', '90000.00', '9000.00', '0.00', '81000.00', 'Piutang', 'Piutang', '81000.00', '0.00', 'Bentar kak bokek'),
(4, 2, 'Radit', '2026-05-19 15:06:39', '45000.00', '0.00', '0.00', '45000.00', 'Transfer', 'Lunas', '45000.00', '0.00', '');

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
(2, 3, 'Selvi', '81000.00', '0.00', 'lunas', '2026-05-19 14:30:42', '2026-05-19 15:35:39', 'Bentar kak bokek');

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
(5, 2, '70000.00', '70000.00', '0.00', 'Muhammad Davin Nur Ardiyanto', '2026-05-19 15:35:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_stok`
--

CREATE TABLE `riwayat_stok` (
  `id` int UNSIGNED NOT NULL,
  `id_barang` int UNSIGNED DEFAULT NULL,
  `nama_barang` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('masuk','keluar','restock','tambah_barang') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` int NOT NULL DEFAULT '0',
  `harga_satuan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pemasok` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dicatat_oleh` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_user` int UNSIGNED DEFAULT NULL,
  `nama` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cashdrawer` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saldo_awal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_akhir` decimal(12,2) DEFAULT NULL,
  `total_penjualan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `waktu_mulai` datetime NOT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `status` enum('aktif','selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_barang`
--

CREATE TABLE `stok_barang` (
  `id` int UNSIGNED NOT NULL,
  `nama_barang` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe` enum('makanan','minuman','snack','lainnya') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'makanan',
  `stok` int NOT NULL DEFAULT '0',
  `harga_dasar` decimal(12,2) NOT NULL DEFAULT '0.00',
  `harga_jual` decimal(12,2) NOT NULL DEFAULT '0.00',
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stok_barang`
--

INSERT INTO `stok_barang` (`id`, `nama_barang`, `tipe`, `stok`, `harga_dasar`, `harga_jual`, `foto`, `aktif`, `created_at`, `updated_at`) VALUES
(1, 'Seblak', 'makanan', 91, '12000.00', '15000.00', NULL, 1, '2026-05-13 15:46:59', '2026-05-19 15:06:39');

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
(2, 2, 'Davin Nur', 'Muhammad Davin Nur Ardiyanto', 'mdavinnura16@gmail.com', '$2y$12$rsO34H3lBFbKuYeYyOe26OXZ/ie9U7ATJNEQ5RPjdpEL7.v1mMh5u', NULL, '085851779038', '2026-05-11 15:40:06', '2026-05-20 08:44:22'),
(3, 2, 'sastra', 'Rahmatullah  Ardiansyah', 'rahmatullahsastraardiansyah@gmail.com', '$2a$12$R5OyAUZutshrT5WMqWsp5OAMFsADpZ9GHN4bFTyIeLAzKSCSJGJpS', NULL, '12345', '2026-05-19 12:56:05', '2026-05-19 13:36:17');

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
(1, 'sastra1'),
(2, 'testing');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stok_aktif` (`aktif`);

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
  ADD KEY `id_barang` (`id_barang`);

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
  ADD KEY `idx_penjualan_status` (`status`);

--
-- Indexes for table `piutang`
--
ALTER TABLE `piutang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_penjualan` (`id_penjualan`),
  ADD KEY `idx_piutang_status` (`status`);

--
-- Indexes for table `riwayat_bayar_piutang`
--
ALTER TABLE `riwayat_bayar_piutang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_piutang` (`id_piutang`);

--
-- Indexes for table `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `idx_riwayat_stok_tgl` (`tanggal`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama` (`nama`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `stok_barang`
--
ALTER TABLE `stok_barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stok_aktif` (`aktif`);

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `detail_beli`
--
ALTER TABLE `detail_beli`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pembayaran_hutang`
--
ALTER TABLE `pembayaran_hutang`
  MODIFY `id_pembayaran` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pembelian`
--
ALTER TABLE `pembelian`
  MODIFY `id_beli` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `piutang`
--
ALTER TABLE `piutang`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `riwayat_bayar_piutang`
--
ALTER TABLE `riwayat_bayar_piutang`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stok_barang`
--
ALTER TABLE `stok_barang`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  ADD CONSTRAINT `fk_detail_pembelian` FOREIGN KEY (`id_beli`) REFERENCES `pembelian` (`id_beli`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE;

--
-- Constraints for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_penjualan_ibfk_2` FOREIGN KEY (`id_barang`) REFERENCES `stok_barang` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  ADD CONSTRAINT `riwayat_stok_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `stok_barang` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shifts`
--
ALTER TABLE `shifts`
  ADD CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
