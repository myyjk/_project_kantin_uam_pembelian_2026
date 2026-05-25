<?php
require 'config/config.php';

if (!isset($_SESSION['isLoggedIn'])) {
    header("Location: login.php"); exit();
}

// Sesuaikan dengan kolom 'id' di tabel users
$admin_nama    = $_SESSION['currentUser']['username'] ?? 'Admin';
$id_user_login = $_SESSION['currentUser']['id'] ?? 0;

// QUERY UTAMA: Menampilkan data pembelian/hutang
// QUERY UTAMA: Menampilkan data pembelian/hutang
$query = "SELECT 
            p.id_beli,
            p.no_faktur,
            p.tanggal_beli,
            v.nama AS nama_kantin,
            IFNULL(SUM(db.jumlah * db.harga), 0) AS total_belanja,
            IFNULL((SELECT SUM(ph.nominal) FROM pembayaran_hutang ph WHERE ph.id_beli = p.id_beli), 0) AS total_terbayar,
            IFNULL(GROUP_CONCAT(CONCAT(pr.nama, ' (', db.jumlah, 'x)') ORDER BY db.id_detail SEPARATOR ', '), '-') AS detail_barang
          FROM pembelian p
          LEFT JOIN vendor v ON p.id_vendor = v.id_vendor
          LEFT JOIN detail_beli db ON p.id_beli = db.id_beli
          LEFT JOIN barang pr ON db.id_produk = pr.id -- <-- PERBAIKAN DI SINI
          GROUP BY p.id_beli
          ORDER BY p.tanggal_beli DESC";

$result   = mysqli_query($conn, $query);
if (!$result) die("Query error: " . mysqli_error($conn));
$all_rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

$grand_faktur   = 0;
$grand_terbayar = 0;
$grand_sisa     = 0;
foreach ($all_rows as $r) {
    $grand_faktur   += floatval($r['total_belanja']);
    $grand_terbayar += floatval($r['total_terbayar']);
    $grand_sisa     += max(0, floatval($r['total_belanja']) - floatval($r['total_terbayar']));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utang Belanja - Kantin UAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --green:#198754; --orange:#fd7e14; --black:#1a1a1a; --white:#ffffff; }
        html,body { margin:0; padding:0; height:100vh; font-family:'Plus Jakarta Sans',sans-serif; background:#f5f6fa; font-size:14px; overflow:hidden; }
        .wrapper-utama { display:flex; height:100vh; width:100vw; overflow:hidden; }
        .area-konten-kanan { flex:1; display:flex; flex-direction:column; height:100vh; overflow:hidden; }
        .main-scroll { flex:1; overflow-y:auto; padding:24px; }
        .stat-card { background:var(--white); border-radius:14px; padding:18px 22px; border-left:4px solid var(--green); box-shadow:0 2px 8px rgba(0,0,0,0.05); }
        .stat-card.orange { border-left-color:var(--orange); }
        .stat-card.red { border-left-color:#dc3545; }
        .stat-label { font-size:0.73rem; color:#888; font-weight:600; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:4px; }
        .stat-value { font-size:1.2rem; font-weight:800; color:var(--black); }
        .table-card { background:var(--white); border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow:hidden; }
        .table-card-header { padding:16px 22px; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; }
        table thead th { background:var(--black); color:var(--white); font-size:0.73rem; text-transform:uppercase; letter-spacing:0.4px; padding:12px 14px; border:none; font-weight:600; }
        table tbody td { padding:13px 14px; vertical-align:middle; font-size:0.83rem; border-bottom:1px solid #f5f5f5; }
        table tbody tr:hover td { background:#fafafa; }
        .badge-lunas { background:#d1fae5; color:#065f46; padding:4px 10px; border-radius:20px; font-size:0.72rem; font-weight:700; }
        .badge-dicicil { background:#fff3cd; color:#856404; padding:4px 10px; border-radius:20px; font-size:0.72rem; font-weight:700; }
        .badge-belum { background:#fee2e2; color:#991b1b; padding:4px 10px; border-radius:20px; font-size:0.72rem; font-weight:700; }
        .progress-thin { height:5px; border-radius:10px; background:#e9ecef; margin-top:4px; overflow:hidden; }
        .progress-thin div { height:100%; border-radius:10px; background:var(--green); }
        .detail-text { font-size:0.72rem; color:#888; max-width:180px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .modal-header { background:var(--black); color:var(--white); }
        .modal-header .btn-close { filter:invert(1); }
        .btn-orange { background:var(--orange); color:white; border:none; font-weight:700; }
        .btn-orange:hover { background:#e06c0c; color:white; }
        .summary-box { background:var(--white); border-radius:12px; padding:18px 22px; box-shadow:0 2px 8px rgba(0,0,0,0.05); border-top:3px solid var(--orange); min-width:300px; }
        
        /* Gaya efek interaksi nomor faktur */
        .faktur-link { color: var(--green); transition: all 0.2s; cursor: pointer; }
        .faktur-link:hover { color: var(--orange) !important; text-decoration: underline !important; }
        
        /* Styling iFrame didalam modal agar rapi */
        .embed-responsive-container { position: relative; width: 100%; height: 500px; }
        .embed-responsive-iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; border-radius: 8px; }
    </style>
</head>
<body>
<div class="wrapper-utama">
    <?php include 'assets/sidebar.php'; ?>
    <div class="area-konten-kanan">
        <?php include 'assets/navbar.php'; ?>
        <div class="main-scroll">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Invoice & <span style="color:var(--orange);">Tagihan Pembelian</span></h5>
                    <p class="text-muted mb-0" style="font-size:0.8rem;">Riwayat hutang belanja ke vendor/kantin</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="histori.php" class="btn btn-sm btn-outline-success rounded-pill px-3">
                        <i class="fas fa-history me-1"></i> Histori Bayar
                    </a>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-label">Total Faktur</div>
                        <div class="stat-value">Rp <?= number_format($grand_faktur, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card orange">
                        <div class="stat-label">Total Terbayar</div>
                        <div class="stat-value" style="color:var(--orange);">Rp <?= number_format($grand_terbayar, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card red">
                        <div class="stat-label">Total Sisa Hutang</div>
                        <div class="stat-value" style="color:#dc3545;">Rp <?= number_format($grand_sisa, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <div class="table-card mb-4">
                <div class="table-card-header">
                    <h6 class="fw-bold mb-0" style="font-size:0.95rem;"><i class="fas fa-file-invoice-dollar me-2 text-success"></i>Daftar Transaksi</h6>
                    <span class="text-muted" style="font-size:0.78rem;"><?= count($all_rows) ?> transaksi</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. Faktur (Klik untuk Nota)</th>
                                <th>Kantin</th>
                                <th>Tanggal</th>
                                <th>Detail barang</th>
                                <th>Total</th>
                                <th>Terbayar</th>
                                <th>Sisa</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($all_rows)): ?>
                            <tr><td colspan="10" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x d-block mb-3 opacity-25"></i>
                                Belum ada riwayat transaksi
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($all_rows as $i => $row):
                                $total    = floatval($row['total_belanja']);
                                $terbayar = floatval($row['total_terbayar']);
                                $sisa     = max(0, $total - $terbayar);
                                $persen   = $total > 0 ? min(100, ($terbayar / $total) * 100) : 0;

                                if ($total == 0)       $badge = '<span class="badge-belum">Kosong</span>';
                                elseif ($sisa <= 0)    $badge = '<span class="badge-lunas"><i class="fas fa-check me-1"></i>Lunas</span>';
                                elseif ($terbayar > 0) $badge = '<span class="badge-dicicil"><i class="fas fa-clock me-1"></i>Dicicil</span>';
                                else                   $badge = '<span class="badge-belum"><i class="fas fa-exclamation me-1"></i>Belum Bayar</span>';
                            ?>
                            <tr>
                                <td class="text-muted"><?= $i+1 ?></td>
                                <td>
                                    <span class="fw-bold faktur-link text-decoration-none" 
                                          onclick="bukaNotaMengambang('<?= $row['id_beli'] ?>', '<?= htmlspecialchars($row['no_faktur']) ?>')">
                                        <i class="fas fa-receipt me-1"></i> <?= htmlspecialchars($row['no_faktur']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['nama_kantin'] ?? 'Kantin UAM') ?></td>
                                <td>
                                    <span class="d-block"><?= date('d/m/Y', strtotime($row['tanggal_beli'])) ?></span>
                                    <small class="text-muted"><?= date('H:i', strtotime($row['tanggal_beli'])) ?> WIB</small>
                                </td>
                                <td>
                                    <span class="detail-text" title="<?= htmlspecialchars($row['detail_barang']) ?>"><?= htmlspecialchars($row['detail_barang']) ?></span>
                                    <div class="progress-thin"><div style="width:<?= $persen ?>%"></div></div>
                                </td>
                                <td class="fw-bold">Rp <?= number_format($total, 0, ',', '.') ?></td>
                                <td style="color:var(--green);font-weight:600;">Rp <?= number_format($terbayar, 0, ',', '.') ?></td>
                                <td class="fw-bold <?= $sisa > 0 ? 'text-danger' : 'text-muted' ?>">Rp <?= number_format($sisa, 0, ',', '.') ?></td>
                                <td class="text-center"><?= $badge ?></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        
                                        <?php if ($sisa > 0 && $total > 0): ?>
                                        <button class="btn btn-sm btn-orange rounded-pill px-2" style="font-size:0.72rem;"
                                                data-bs-toggle="modal" data-bs-target="#modalBayar<?= $row['id_beli'] ?>">
                                            <i class="fas fa-money-bill-wave"></i> Bayar
                                        </button>
                                        <?php endif; ?>
                                        
                                        <a href="histori.php?id_beli=<?= $row['id_beli'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2" style="font-size:0.72rem;">
                                            <i class="fas fa-history"></i> Histori
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalBayar<?= $row['id_beli'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST" action="proses_bayar.php" enctype="multipart/form-data">
                                            <div class="modal-header">
                                                <h6 class="modal-title fw-bold"><i class="fas fa-wallet me-2" style="color:var(--orange);"></i>Form Pembayaran</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="id_beli" value="<?= $row['id_beli'] ?>">
                                                <p class="text-muted small mb-3">No. Faktur: <strong class="text-dark"><?= htmlspecialchars($row['no_faktur']) ?></strong></p>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-muted">Sisa Hutang</label>
                                                    <input type="text" class="form-control fw-bold text-danger bg-light" value="Rp <?= number_format($sisa, 0, ',', '.') ?>" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-muted">Nominal Pembayaran (Rp)</label>
                                                    <div class="input-group">
                                                        <input type="number" name="nominal_bayar" id="nom_<?= $row['id_beli'] ?>"
                                                               class="form-control" min="1" max="<?= $sisa ?>"
                                                               placeholder="Masukkan nominal" required>
                                                        <button type="button" class="btn btn-success fw-bold"
                                                            onclick="document.getElementById('nom_<?= $row['id_beli'] ?>').value='<?= $sisa ?>'">
                                                            Lunas
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small fw-bold text-muted">Foto Bukti Pembayaran <span class="text-danger">*Wajib</span></label>
                                                    <input type="file" name="foto_faktur" class="form-control" accept="image/*" required>
                                                    <small class="text-muted">Format: JPG, PNG, WEBP</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-success fw-bold">
                                                    <i class="fas fa-save me-1"></i> Simpan Pembayaran
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <div class="summary-box">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Total Nilai Faktur</span>
                        <span class="fw-bold">Rp <?= number_format($grand_faktur, 0, ',', '.') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Total Sudah Dibayar</span>
                        <span class="fw-bold text-success">Rp <?= number_format($grand_terbayar, 0, ',', '.') ?></span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top">
                        <span class="fw-bold">Total Sisa Hutang</span>
                        <span class="fw-bold text-danger" style="font-size:1rem;">Rp <?= number_format($grand_sisa, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalNotaMengambang" tabindex="-1" aria-labelledby="modalNotaTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold" id="modalNotaTitle"><i class="fas fa-file-invoice me-2 text-info"></i> Detail Nota Belanja</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="embed-responsive-container">
                    <iframe id="iframeNota" class="embed-responsive-iframe" src="about:blank"></iframe>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Tutup Nota</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fungsi JavaScript untuk melempar id_beli ke dalam iframe secara dinamis tanpa ganti halaman
function bukaNotaMengambang(idBeli, noFaktur) {
    var iframe = document.getElementById('iframeNota');
    var modalTitle = document.getElementById('modalNotaTitle');
    
    // Set judul modal sesuai nomor faktur yang diklik
    modalTitle.innerHTML = '<i class="fas fa-file-invoice me-2 text-info"></i> Detail Nota Belanja: ' + noFaktur;
    
    // Set src iFrame menuju halaman detail_beli.php pembeli
    iframe.src = "../pembeli/detail_beli.php?id=" + idBeli;
    
    // Munculkan modal mengambang secara programmatif
    var myModal = new bootstrap.Modal(document.getElementById('modalNotaMengambang'));
    myModal.show();
}

// Reset src iFrame saat modal ditutup agar performa tetap ringan
document.getElementById('modalNotaMengambang').addEventListener('hidden.bs.modal', function () {
    document.getElementById('iframeNota').src = "about:blank";
});
</script>
</body>
</html>