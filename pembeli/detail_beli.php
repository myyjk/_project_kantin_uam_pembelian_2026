<?php
session_start();
require '../config/config.php';

// Proteksi Login
if (!isset($_SESSION['isLoggedIn'])) {
    header("Location: ../login/login.php");
    exit();
}

// Mengambil ID Pembelian dari parameter URL
$id_beli = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_beli) {
    echo "<div class='alert alert-danger m-3'>⚠️ ID Pembelian tidak valid atau tidak ditemukan.</div>";
    exit();
}

// 1. Ambil data utama pembelian (Nomor Faktur, Tanggal, dan Nama Vendor)
$query_pembelian = mysqli_query($conn, "
    SELECT p.*, v.nama 
    FROM pembelian p 
    LEFT JOIN vendor v ON p.id_vendor = v.id_vendor 
    WHERE p.id_beli = '$id_beli'
");

if (!$query_pembelian) {
    die("<div class='alert alert-danger m-3'>❌ SQL Error Utama: " . mysqli_error($conn) . "</div>");
}

$transaksi = mysqli_fetch_assoc($query_pembelian);

if (!$transaksi) {
    echo "<div class='alert alert-danger m-3'>❌ Data transaksi tidak ditemukan di database.</div>";
    exit();
}

// 2. PERBAIKAN: Hitung total cicilan/pembayaran tambahan dari tabel pembayaran_hutang
$query_pembayaran_tambahan = mysqli_query($conn, "
    SELECT IFNULL(SUM(nominal), 0) AS total_mencicil 
    FROM pembayaran_hutang 
    WHERE id_beli = '$id_beli'
");
$data_pembayaran = mysqli_fetch_assoc($query_pembayaran_tambahan);
$total_mencicil   = floatval($data_pembayaran['total_mencicil'] ?? 0);

// 3. Ambil semua item barang yang dibeli dalam satu transaksi ini
$query_detail = mysqli_query($conn, "
    SELECT db.*, b.nama AS nama_produk 
    FROM detail_beli db
    JOIN barang b ON db.id = b.id
    WHERE db.id_beli = '$id_beli'
");

if (!$query_detail) {
    die("<div class='alert alert-danger m-3'>❌ SQL Error Detail: " . mysqli_error($conn) . "</div>");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Barang Pembelian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; }
        .nota-box { background: #ffffff; border-radius: 8px; border: 1px solid #dee2e6; position: relative; overflow: hidden; }
        .table th { background-color: #f8f9fa; color: #495057; font-weight: 600; }
        
        /* Gaya Stempel Lunas Melayang ala Nota Kasir Fisik */
        .stamp-lunas {
            position: absolute;
            top: 15px;
            right: 15px;
            border: 3px dashed #198754;
            color: #198754;
            font-weight: 800;
            font-size: 1.3rem;
            padding: 2px 12px;
            transform: rotate(-12deg);
            border-radius: 6px;
            text-transform: uppercase;
            opacity: 0.85;
            z-index: 10;
            background: rgba(25, 135, 84, 0.04);
        }

        @media print {
            .d-print-none { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container my-2" style="max-width: 650px;">
    
    <div class="mb-3 d-flex justify-content-end d-print-none">
        <button onclick="window.print()" class="btn btn-sm btn-dark">🖨️ Cetak</button>
    </div>

    <div class="p-4 nota-box shadow-sm">
        
        <?php 
        // 4. Hitung akumulasi total belanja real
        $query_total_sementara = mysqli_query($conn, "SELECT SUM(jumlah * harga) AS total FROM detail_beli WHERE id_beli = '$id_beli'");
        $row_total = mysqli_fetch_assoc($query_total_sementara);
        $total_belanja_faktur = floatval($row_total['total'] ?? 0);

        // Pembayaran awal (DP) dari kolom metode
        $dp_awal = floatval($transaksi['metode'] ?? 0); 
        
        // Akumulasi sisa hutang nyata = Total Belanja - (DP Awal + Seluruh Cicilan)
        $sisa_hutang_akhir = $total_belanja_faktur - ($dp_awal + $total_mencicil);

        // Jika sisa hutang habis, tampilkan stempel lunas visual di atas nota
        if ($sisa_hutang_akhir <= 0 && $total_belanja_faktur > 0): 
        ?>
            <div class="stamp-lunas">✓ LUNAS</div>
        <?php endif; ?>

        <div class="row mb-4 pb-3 border-bottom text-muted small">
            <div class="col-6">
                <span class="d-block text-dark fw-bold">KANTIN UAM</span>
                <span>No. Faktur: <strong><?php echo htmlspecialchars($transaksi['no_faktur']); ?></strong></span>
            </div>
            <div class="col-6 text-end">
                <span class="d-block text-dark fw-bold">Vendor: <?php echo htmlspecialchars($transaksi['nama_vendor'] ?? 'Umum'); ?></span>
                <span>Tgl: <?php echo date('d/m/Y H:i', strtotime($transaksi['tanggal_beli'])); ?></span>
            </div>
        </div>

        <h6 class="fw-bold text-dark mb-3"> Daftar Barang Yang Dibeli :</h6>

        <div class="table-responsive">
            <table class="table table-sm table-borderless align-middle" style="font-size: 0.9rem;">
                <thead>
                    <tr class="border-bottom text-muted" style="font-size: 0.8rem;">
                        <th width="5%">NO</th>
                        <th>NAMA BARANG</th>
                        <th class="text-center" width="15%">QTY</th>
                        <th class="text-end" width="25%">HARGA</th>
                        <th class="text-end" width="25%">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    $total_tagihan = 0;
                    while ($item = mysqli_fetch_assoc($query_detail)) : 
                        $subtotal = $item['jumlah'] * $item['harga']; 
                        $total_tagihan += $subtotal;
                    ?>
                    <tr class="border-bottom">
                        <td class="py-2 text-muted"><?php echo $no++; ?></td>
                        <td class="py-2 fw-bold text-dark"><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                        <td class="py-2 text-center"><span class="badge bg-light text-dark border px-2"><?php echo $item['jumlah']; ?></span></td>
                        <td class="py-2 text-end text-muted">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                        <td class="py-2 text-end fw-bold text-success">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end mt-4">
            <div class="col-7">
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Total Belanja:</span>
                    <span class="fw-bold text-dark">Rp <?php echo number_format($total_tagihan, 0, ',', '.'); ?></span>
                </div>
                
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Dibayar (DP):</span>
                    <span class="fw-bold text-primary">Rp <?php echo number_format($dp_awal, 0, ',', '.'); ?></span>
                </div>

                <?php if ($total_mencicil > 0): ?>
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Cicilan Piutang:</span>
                    <span class="fw-bold text-success">Rp <?php echo number_format($total_mencicil, 0, ',', '.'); ?></span>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between pt-2 border-top">
                    <?php if ($sisa_hutang_akhir > 0): ?>
                        <span class="text-danger fw-bold small">Sisa Piutang/Hutang:</span>
                        <span class="fw-bold text-danger">Rp <?php echo number_format($sisa_hutang_akhir, 0, ',', '.'); ?></span>
                    <?php else: ?>
                        <span class="text-success fw-bold small">Status:</span>
                        <span class="badge bg-success px-3 py-1 fw-bold">LUNAS</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>