<?php
// Mulai session dan sertakan koneksi database
session_start();
require '../config/config.php';

// Pastikan user sudah login, jika tidak lempar ke login.php
if (!isset($_SESSION['username'])) {
    header("Location: ../login/login.php");
    exit;
}

// Ambil data transaksi dari database
// Catatan: Sesuaikan nama tabel (misal: pengeluaran, pembelian, atau transaksi) dan kolomnya dengan DB barumu
$query = "SELECT * FROM transaksi ORDER BY tanggal_transaksi DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Transaksi - Kantin UAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .main-content { margin-left: 260px; padding: 30px; transition: all 0.3s; }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .badge-tunai { background-color: #198754; color: white; }
        .badge-hutang { background-color: #dc3545; color: white; }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

    <?php include '../assets/sidebar.php'; ?>

    <div class="main-content">
        
        <?php include '../assets/navbar.php'; ?>

        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark">Riwayat Transaksi Pembelian</h3>
                <button class="btn btn-success" onclick="window.location.href='tambah_transaksi.php'">
                    <i class="fas fa-plus me-2"></i> Tambah Transaksi
                </button>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">No</th>
                                    <th>No. Nota / ID</th>
                                    <th>Tanggal</th>
                                    <th>Total Bayar</th>
                                    <th>Metode Pembayaran</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) { 
                                ?>
                                <tr>
                                    <td class="ps-3"><?= $no++; ?></td>
                                    <td class="fw-bold">#<?= $row['id_transaksi']; ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($row['tanggal_transaksi'])); ?></td>
                                    <td>Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if (strtolower($row['metode_pembayaran']) == 'tunai') : ?>
                                            <span class="badge badge-tunai p-2 rounded-pill"><i class="fas fa-money-bill-wave me-1"></i> Tunai</span>
                                        <?php else : ?>
                                            <span class="badge badge-hutang p-2 rounded-pill"><i class="fas fa-credit-card me-1"></i> Hutang</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-outline-primary btn-sm px-3 rounded-pill btn-detail" data-id="<?= $row['id_transaksi']; ?>">
                                            <i class="fas fa-eye me-1"></i> Detail Beli
                                        </button>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-4 text-muted'>Belum ada data transaksi.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="modalDetailLabel"><i class="fas fa-shopping-bag text-success me-2"></i> Rincian Barang Diberli</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="isi-detail-transaksi">
                    <div class="text-center py-3">
                        <div class="spinner-border text-success" role="status"></div>
                        <p class="mt-2 small text-muted">Memuat rincian...</p>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.querySelectorAll('.btn-detail').forEach(button => {
            button.addEventListener('click', function() {
                const idTransaksi = this.getAttribute('data-id');
                const myModal = new bootstrap.Modal(document.getElementById('modalDetail'));
                
                // Tampilkan modal kosong dengan animasi loading terlebih dahulu
                myModal.show();
                
                // Ambil data detail barang lewat AJAX fetch
                fetch('get_detail.php?id=' + idTransaksi)
                    .then(response => response.text())
                    .then(htmlContent => {
                        document.getElementById('isi-detail-transaksi').innerHTML = htmlContent;
                    })
                    .catch(err => {
                        document.getElementById('isi-detail-transaksi').innerHTML = '<div class="alert alert-danger">Gagal memuat data detail.</div>';
                    });
            });
        });
    </script>
</body>
</html>