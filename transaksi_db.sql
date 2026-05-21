-- =============================================
-- DATABASE: Transaksi & Detail Transaksi
-- Sesuaikan dengan db_kantin (_PROJECT_26)
-- Tabel barang: id, nama, harga_jual
-- Tabel pembelian: id_beli, nama
-- =============================================

-- Tabel transaksi (header transaksi)
CREATE TABLE IF NOT EXISTS `transaksi` (
  `id_transaksi`      INT(11) NOT NULL AUTO_INCREMENT,
  `no_faktur`         VARCHAR(30) NOT NULL UNIQUE,
  `id_beli`           INT(11) NOT NULL,
  `tanggal`           DATE NOT NULL,
  `metode_pembayaran` ENUM('tunai','transfer','kredit') NOT NULL DEFAULT 'tunai',
  `total_harga`       DECIMAL(15,2) NOT NULL DEFAULT 0,
  `status`            ENUM('pending','lunas','batal') NOT NULL DEFAULT 'pending',
  `keterangan`        TEXT,
  `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel detail_transaksi (item per transaksi)
-- id_produk merujuk ke barang.id (bukan tabel produk)
CREATE TABLE IF NOT EXISTS `detail_transaksi` (
  `id_detail`    INT(11) NOT NULL AUTO_INCREMENT,
  `id_transaksi` INT(11) NOT NULL,
  `id_produk`    INT(11) NOT NULL COMMENT 'referensi ke barang.id',
  `jumlah`       INT(11) NOT NULL DEFAULT 1,
  `harga`        DECIMAL(15,2) NOT NULL DEFAULT 0,
  `subtotal`     DECIMAL(15,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_detail`),
  FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi`(`id_transaksi`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Contoh data dummy (opsional, sesuaikan id_beli & id_produk)
-- id_beli  = id dari tabel pembelian
-- id_produk = id dari tabel barang (Es teh=1, es jeruk=2)
-- =============================================
-- INSERT INTO `transaksi` (`no_faktur`,`id_beli`,`tanggal`,`metode_pembayaran`,`total_harga`,`status`) VALUES
-- ('INV-20250001', 1, '2025-05-01', 'tunai', 10000, 'lunas');
-- INSERT INTO `detail_transaksi` (`id_transaksi`,`id_produk`,`jumlah`,`harga`,`subtotal`) VALUES
-- (1, 1, 2, 5000, 10000);
