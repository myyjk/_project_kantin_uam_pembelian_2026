<?php
session_start();
require '../config/config.php';

if (!isset($_SESSION['isLoggedIn'])) {
    header("Location: ../login.php"); exit();
}

$admin_nama    = $_SESSION['currentUser']['username'];
$id_user_login = $_SESSION['currentUser']['id'];
$id_beli_filter = isset($_GET['id_beli']) ? (int)$_GET['id_beli'] : 0;

$where_faktur = $id_beli_filter > 0 ? "AND p.id_beli = '$id_beli_filter'" : "";

// ✅ PERBAIKAN: Hapus filter "AND p.id_admin = '$id_user_login'"
$query = "SELECT 
            ph.id_pembayaran,
            ph.id_beli,
            ph.nominal,
            ph.tanggal,
            ph.foto_faktur,
            p.no_faktur,
            v.nama AS nama_kantin
          FROM pembayaran_hutang ph
          JOIN pembelian p ON ph.id_beli = p.id_beli
          LEFT JOIN vendor v ON p.id_vendor = v.id_vendor
          WHERE 1=1 $where_faktur
          ORDER BY ph.tanggal DESC";

$result   = mysqli_query($conn, $query);
if (!$result) die("Query error: " . mysqli_error($conn));
$all_rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

$total_dibayar = array_sum(array_column($all_rows, 'nominal'));

// Info faktur jika filter spesifik
// ✅ PERBAIKAN: Hapus filter "AND p.id_admin = '$id_user_login'"
$info_faktur = null;
if ($id_beli_filter > 0) {
    $qf = mysqli_query($conn, "SELECT p.no_faktur, 
                IFNULL(SUM(db.jumlah * db.harga), 0) AS total_belanja
              FROM pembelian p
              LEFT JOIN detail_beli db ON p.id_beli = db.id_beli
              WHERE p.id_beli = '$id_beli_filter'
              GROUP BY p.id_beli");
    $info_faktur = mysqli_fetch_assoc($qf);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori Pembayaran - Kantin UAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --green:#198754; --orange:#fd7e14; --black:#1a1a1a; --white:#ffffff; }
        html,body { margin:0; padding:0; height:100vh; font-family:'Plus Jakarta Sans',sans-serif; background:#f5f6fa; font-size:14px; overflow:hidden; }
        .wrapper-utama { display:flex; height:100vh; width:100vw; overflow:hidden; }
        .area-konten-kanan { flex:1; display:flex; flex-direction:column; height:100vh; overflow:hidden; }
        .top-nav { background:var(--white); padding:15px 30px; border-bottom:2px solid var(--green); display:flex; justify-content:space-between; align-items:center; height:70px; flex-shrink:0; }
        .navbar-brand-custom { color:var(--green); font-weight:700; font-size:1.1rem; text-decoration:none; display:flex; align-items:center; }
        .btn-logout { background:#dc3545; color:white; border:none; padding:7px 14px; border-radius:8px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; font-size:0.85rem; }
        .main-scroll { flex:1; overflow-y:auto; padding:24px; }

        .info-box { background:var(--white); border-radius:14px; padding:18px 22px; border-left:4px solid var(--orange); box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:20px; }

        .table-card { background:var(--white); border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow:hidden; }
        .table-card-header { padding:16px 22px; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; }

        table thead th { background:var(--black); color:var(--white); font-size:0.73rem; text-transform:uppercase; letter-spacing:0.4px; padding:12px 14px; border:none; font-weight:600; }
        table tbody td { padding:13px 14px; vertical-align:middle; font-size:0.83rem; border-bottom:1px solid #f5f5f5; }
        table tbody tr:hover td { background:#fafafa; }

        .foto-thumb { width:50px; height:50px; object-fit:cover; border-radius:8px; cursor:pointer; border:2px solid #eee; transition:0.2s; }
        .foto-thumb:hover { border-color:var(--orange); transform:scale(1.05); }

        .lightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; }
        .lightbox.active { display:flex; }
        .lightbox img { max-width:90vw; max-height:90vh; border-radius:12px; box-shadow:0 0 40px rgba(0,0,0,0.5); }
        .lightbox-close { position:absolute; top:20px; right:24px; color:white; font-size:2rem; cursor:pointer; font-weight:700; }
    </style>
</head>
<body>
<div class="wrapper-utama">
    <?php include __DIR__ . '/../assets/sidebar.php'; ?>
    <div class="area-konten-kanan">
        <?php include __DIR__ . '/../assets/navbar.php'; ?>
        <div class="main-scroll">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fas fa-history me-2 text-success"></i>Histori Pembayaran</h5>
                    <p class="text-muted mb-0" style="font-size:0.8rem;">
                        <?= $id_beli_filter > 0 && $info_faktur ? 'Faktur: <strong>' . htmlspecialchars($info_faktur['no_faktur']) . '</strong>' : 'Semua riwayat cicilan pembayaran' ?>
                    </p>
                </div>
                <a href="piutang.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <?php if ($id_beli_filter > 0 && $info_faktur): ?>
            <div class="info-box">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-muted small">No. Faktur</div>
                        <div class="fw-bold"><?= htmlspecialchars($info_faktur['no_faktur']) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Total Belanja</div>
                        <div class="fw-bold">Rp <?= number_format($info_faktur['total_belanja'], 0, ',', '.') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Total Terbayar</div>
                        <div class="fw-bold text-success">Rp <?= number_format($total_dibayar, 0, ',', '.') ?></div>
                    </div>
                </div>
                <?php 
                $sisa = floatval($info_faktur['total_belanja']) - $total_dibayar;
                $persen = $info_faktur['total_belanja'] > 0 ? min(100, ($total_dibayar / $info_faktur['total_belanja']) * 100) : 0;
                ?>
                <div class="mt-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Progress Pelunasan</span>
                        <span class="fw-bold"><?= round($persen) ?>%</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-success" style="width:<?= $persen ?>%"></div>
                    </div>
                    <?php if ($sisa > 0): ?>
                    <div class="text-danger small mt-1 fw-bold">Sisa hutang: Rp <?= number_format($sisa, 0, ',', '.') ?></div>
                    <?php else: ?>
                    <div class="text-success small mt-1 fw-bold"><i class="fas fa-check-circle me-1"></i>Lunas!</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="table-card">
                <div class="table-card-header">
                    <h6 class="fw-bold mb-0" style="font-size:0.95rem;">Riwayat Cicilan</h6>
                    <span class="text-muted" style="font-size:0.78rem;"><?= count($all_rows) ?> transaksi · Total: <strong class="text-success">Rp <?= number_format($total_dibayar, 0, ',', '.') ?></strong></span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. Faktur</th>
                                <th>Kantin</th>
                                <th>Tanggal Bayar</th>
                                <th>Nominal</th>
                                <th class="text-center">Bukti Foto</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($all_rows)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x d-block mb-3 opacity-25"></i>
                                Belum ada riwayat pembayaran
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($all_rows as $i => $row): ?>
                            <tr>
                                <td class="text-muted"><?= $i+1 ?></td>
                                <td><span class="fw-bold" style="font-size:0.78rem;"><?= htmlspecialchars($row['no_faktur']) ?></span></td>
                                <td><?= htmlspecialchars($row['nama_kantin'] ?? 'Kantin UAM') ?></td>
                                <td>
                                    <span class="d-block"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></span>
                                    <small class="text-muted"><?= date('H:i', strtotime($row['tanggal'])) ?> WIB</small>
                                </td>
                                <td><span class="fw-bold text-success">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></span></td>
                                <td class="text-center">
                                    <?php if ($row['foto_faktur'] && file_exists(__DIR__ . "/upload/" . $row['foto_faktur'])): ?>
                                        <img src="upload/<?= htmlspecialchars($row['foto_faktur']) ?>"
                                             class="foto-thumb"
                                             onclick="bukaLightbox(this.src)"
                                             alt="Bukti bayar">
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:0.75rem;">Tidak ada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox" onclick="tutupLightbox()">
    <span class="lightbox-close">&times;</span>
    <img id="lightboxImg" src="" alt="Bukti Pembayaran">
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function bukaLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('active');
}
function tutupLightbox() {
    document.getElementById('lightbox').classList.remove('active');
}
</script>
</body>
</html>
