<?php
require_once '../config/config.php';
$title = "Detail Transaksi";

// Ambil id
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { header("Location: transaksi.php"); exit; }

// Ambil header transaksi + nama pembeli (tabel pembelian db_kantin: kolom 'nama')
$sql = "SELECT t.*,
               COALESCE(p.nama, CONCAT('Pembeli #', t.id_beli)) AS nama_pembeli,
               COALESCE(p.alamat, '-')  AS alamat_pembeli,
               COALESCE(p.telepon, '-') AS telepon_pembeli
        FROM transaksi t
        LEFT JOIN pembelian p ON t.id_beli = p.id_beli
        WHERE t.id_transaksi = $id LIMIT 1";
$res = mysqli_query($conn, $sql);
if (!$res) {
    $res = mysqli_query($conn, "SELECT t.*,
                CONCAT('Pembeli #', t.id_beli) AS nama_pembeli,
                '-' AS alamat_pembeli, '-' AS telepon_pembeli
                FROM transaksi t WHERE t.id_transaksi = $id LIMIT 1");
}
$trx = $res ? mysqli_fetch_assoc($res) : null;
if (!$trx) { header("Location: transaksi.php"); exit; }

// Ambil detail item (JOIN ke tabel barang — db_kantin pakai 'barang' bukan 'produk')
// Kolom barang: id, nama, harga_jual
$sql2 = "SELECT dt.*,
                COALESCE(b.nama, CONCAT('Barang #', dt.id_produk)) AS nama_produk,
                'pcs' AS satuan
          FROM detail_transaksi dt
          LEFT JOIN barang b ON dt.id_produk = b.id
          WHERE dt.id_transaksi = $id
          ORDER BY dt.id_detail ASC";
$res2 = mysqli_query($conn, $sql2);
if (!$res2) {
    $res2 = mysqli_query($conn, "SELECT dt.*,
                CONCAT('Barang #', dt.id_produk) AS nama_produk, 'pcs' AS satuan
                FROM detail_transaksi dt WHERE dt.id_transaksi = $id ORDER BY dt.id_detail ASC");
}
$detail = [];
if ($res2) while ($row = mysqli_fetch_assoc($res2)) $detail[] = $row;

$is_print = isset($_GET['print']) && $_GET['print'] == 1;
$status_class = ['lunas'=>'success','pending'=>'warning','batal'=>'danger'];
$metode_icon  = ['tunai'=>'fa-money-bill-wave','transfer'=>'fa-university','kredit'=>'fa-credit-card'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?> - <?= htmlspecialchars($trx['no_faktur']) ?> | _PROJECT_26</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #2563eb;
      --light-bg: #f1f5f9;
      --dark: #1e293b;
    }
    body { font-family:'Poppins',sans-serif; background:var(--light-bg); color:var(--dark); }

    /* ---- NOTA CARD ---- */
    .nota-wrap { max-width: 860px; margin: 0 auto; }
    .nota-card { background:#fff; border-radius:20px; box-shadow:0 4px 30px rgba(0,0,0,.1); overflow:hidden; }
    .nota-header { background:linear-gradient(135deg,#1e3a8a,#2563eb); color:#fff; padding:2rem 2.5rem; }
    .nota-header .company-name { font-size:1.6rem; font-weight:700; letter-spacing:.02em; }
    .nota-header .faktur-badge { background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.4); border-radius:30px; padding:.4em 1.2em; font-size:.85rem; font-weight:600; display:inline-block; }
    .nota-body { padding:2rem 2.5rem; }
    .info-block { background:#f8fafc; border-radius:12px; padding:1.2rem 1.5rem; height:100%; }
    .info-block .lbl { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; font-weight:600; margin-bottom:.15rem; }
    .info-block .val { font-size:.95rem; font-weight:600; color:var(--dark); }

    /* ---- TABLE NOTA ---- */
    .tbl-nota thead th { background:#1e3a8a; color:#fff; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; padding:.8rem 1rem; border:none; }
    .tbl-nota tbody td { padding:.75rem 1rem; vertical-align:middle; font-size:.88rem; border-color:#f1f5f9; }
    .tbl-nota tfoot td { padding:.75rem 1rem; font-size:.9rem; border-top:2px solid #e2e8f0; }
    .tbl-nota tbody tr:last-child td { border-bottom:none; }

    /* ---- BADGE STATUS ---- */
    .badge-lunas   { background:#dcfce7; color:#16a34a; }
    .badge-pending { background:#fef9c3; color:#d97706; }
    .badge-batal   { background:#fee2e2; color:#dc2626; }
    .badge-s { padding:.4em 1em; border-radius:20px; font-size:.8rem; font-weight:700; }

    /* ---- TOTAL AREA ---- */
    .total-box { background:linear-gradient(135deg,#eff6ff,#dbeafe); border:1px solid #bfdbfe; border-radius:14px; padding:1.2rem 1.8rem; }
    .total-box .total-lbl { font-size:.85rem; color:#1e40af; font-weight:500; }
    .total-box .total-val { font-size:1.6rem; font-weight:700; color:#1e3a8a; }

    /* ---- TTD AREA ---- */
    .ttd-box { border:1px dashed #cbd5e1; border-radius:12px; padding:1.5rem; text-align:center; font-size:.82rem; color:#64748b; }
    .ttd-line { border-top:1px solid #94a3b8; width:120px; margin:3.5rem auto .5rem; }

    /* ===================== PRINT STYLE ===================== */
    @media print {
      body { background:#fff !important; font-size:11pt; }
      .no-print { display:none !important; }
      .nota-wrap { max-width:100%; }
      .nota-card { box-shadow:none !important; border-radius:0 !important; }
      .nota-header { background:#1e3a8a !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .tbl-nota thead th { background:#1e3a8a !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .total-box { background:#eff6ff !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      @page { margin:1cm; size:A5 portrait; }
    }
  </style>
</head>
<body>

<?php if (!$is_print): ?>
<div class="d-flex">
  <?php include '../includes/sidebar.php'; ?>
  <div class="flex-grow-1">
    <?php include '../includes/navbar.php'; ?>
    <div class="container-fluid p-4">
      <!-- ACTION BAR -->
      <div class="d-flex align-items-center justify-content-between mb-4 no-print">
        <div>
          <a href="transaksi.php" class="btn btn-outline-secondary rounded-pill btn-sm me-2">
            <i class="fas fa-arrow-left me-1"></i>Kembali
          </a>
          <span class="text-muted small">Detail Transaksi</span>
        </div>
        <div class="d-flex gap-2">
          <a href="tambah_transaksi.php?edit=<?= $id ?>" class="btn btn-warning rounded-pill btn-sm">
            <i class="fas fa-edit me-1"></i>Edit
          </a>
          <button onclick="cetakNota()" class="btn btn-primary rounded-pill btn-sm">
            <i class="fas fa-print me-1"></i>Cetak Nota
          </button>
        </div>
      </div>
<?php endif; ?>

      <!-- ========== NOTA ========== -->
      <div class="nota-wrap <?= $is_print ? 'mt-0' : '' ?>">
        <div class="nota-card">

          <!-- HEADER NOTA -->
          <div class="nota-header">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
              <div>
                <div class="company-name mb-1"><i class="fas fa-store me-2"></i>_PROJECT_26</div>
                <div style="font-size:.82rem;opacity:.8;">Nota Transaksi Penjualan</div>
              </div>
              <div class="text-end">
                <div class="faktur-badge mb-2"><?= htmlspecialchars($trx['no_faktur']) ?></div><br>
                <span class="badge-s badge-<?= $trx['status'] ?>">
                  <?php if ($trx['status']==='lunas') echo '<i class="fas fa-check-circle me-1"></i>LUNAS';
                        elseif ($trx['status']==='pending') echo '<i class="fas fa-clock me-1"></i>PENDING';
                        else echo '<i class="fas fa-times-circle me-1"></i>BATAL'; ?>
                </span>
              </div>
            </div>
          </div>

          <div class="nota-body">

            <!-- INFO TRANSAKSI -->
            <div class="row g-3 mb-4">
              <div class="col-md-3 col-6">
                <div class="info-block">
                  <div class="lbl"><i class="fas fa-calendar me-1"></i>Tanggal</div>
                  <div class="val"><?= date('d M Y', strtotime($trx['tanggal'])) ?></div>
                </div>
              </div>
              <div class="col-md-3 col-6">
                <div class="info-block">
                  <div class="lbl"><i class="fas fa-user me-1"></i>Pembeli</div>
                  <div class="val"><?= htmlspecialchars($trx['nama_pembeli']) ?></div>
                </div>
              </div>
              <div class="col-md-3 col-6">
                <div class="info-block">
                  <div class="lbl"><i class="fas fa-phone me-1"></i>Telepon</div>
                  <div class="val"><?= htmlspecialchars($trx['telepon_pembeli']) ?></div>
                </div>
              </div>
              <div class="col-md-3 col-6">
                <div class="info-block">
                  <div class="lbl"><i class="fas fa-<?= $metode_icon[$trx['metode_pembayaran']] ?? 'money-bill' ?> me-1"></i>Metode</div>
                  <div class="val"><?= ucfirst($trx['metode_pembayaran']) ?></div>
                </div>
              </div>
            </div>

            <?php if ($trx['alamat_pembeli'] !== '-'): ?>
            <div class="info-block mb-4">
              <div class="lbl"><i class="fas fa-map-marker-alt me-1"></i>Alamat</div>
              <div class="val"><?= htmlspecialchars($trx['alamat_pembeli']) ?></div>
            </div>
            <?php endif; ?>

            <!-- TABEL ITEM -->
            <div class="table-responsive mb-4">
              <table class="table tbl-nota">
                <thead>
                  <tr>
                    <th style="width:40px">#</th>
                    <th>Nama Produk</th>
                    <th class="text-center" style="width:90px">Jumlah</th>
                    <th class="text-end" style="width:140px">Harga Satuan</th>
                    <th class="text-end" style="width:150px">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($detail)): ?>
                  <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada item</td></tr>
                  <?php else: ?>
                  <?php foreach ($detail as $i => $d): ?>
                  <tr>
                    <td class="text-muted"><?= $i+1 ?></td>
                    <td>
                      <span class="fw-500"><?= htmlspecialchars($d['nama_produk']) ?></span><br>
                      <small class="text-muted"><?= $d['satuan'] ?></small>
                    </td>
                    <td class="text-center"><?= $d['jumlah'] ?></td>
                    <td class="text-end">Rp <?= number_format($d['harga'],0,'.','.') ?></td>
                    <td class="text-end fw-600">Rp <?= number_format($d['subtotal'],0,'.','.') ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="text-end text-muted fw-500">Subtotal Item</td>
                    <td class="text-end fw-600">Rp <?= number_format(array_sum(array_column($detail,'subtotal')),0,'.','.') ?></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- TOTAL & TTD -->
            <div class="row align-items-end">
              <div class="col-md-7">
                <?php if ($trx['keterangan']): ?>
                <div class="info-block mb-3">
                  <div class="lbl"><i class="fas fa-sticky-note me-1"></i>Keterangan</div>
                  <div class="val" style="font-weight:400;font-size:.88rem;"><?= nl2br(htmlspecialchars($trx['keterangan'])) ?></div>
                </div>
                <?php endif; ?>
                <!-- TTD -->
                <div class="row g-3 mt-1">
                  <div class="col-6">
                    <div class="ttd-box">
                      <div class="ttd-line"></div>
                      <div>Pembeli</div>
                      <div class="fw-600 text-dark" style="font-size:.85rem;"><?= htmlspecialchars($trx['nama_pembeli']) ?></div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="ttd-box">
                      <div class="ttd-line"></div>
                      <div>Petugas</div>
                      <div class="fw-600 text-dark" style="font-size:.85rem;">Admin</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-5 mt-3 mt-md-0">
                <div class="total-box text-end">
                  <div class="total-lbl mb-1">TOTAL PEMBAYARAN</div>
                  <div class="total-val">Rp <?= number_format($trx['total_harga'],0,'.','.') ?></div>
                  <div style="font-size:.75rem;color:#1e40af;margin-top:.25rem;">
                    <?= ucfirst($trx['metode_pembayaran']) ?> &bull; <?= date('d M Y', strtotime($trx['tanggal'])) ?>
                  </div>
                </div>
              </div>
            </div>

            <!-- FOOTER NOTA -->
            <hr class="my-4" style="border-color:#e2e8f0;">
            <div class="text-center text-muted" style="font-size:.75rem;">
              <i class="fas fa-heart text-danger me-1"></i>
              Terima kasih telah berbelanja di <strong>_PROJECT_26</strong>. Simpan nota ini sebagai bukti transaksi.
              <br>Dicetak: <?= date('d M Y H:i') ?>
            </div>

          </div><!-- /nota-body -->
        </div><!-- /nota-card -->
      </div><!-- /nota-wrap -->

<?php if (!$is_print): ?>
    </div><!-- /container -->
  </div><!-- /flex-grow -->
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function cetakNota() {
  window.open('detail_transaksi.php?id=<?= $id ?>&print=1','_blank');
}
// Auto print jika mode print
<?php if ($is_print): ?>
window.addEventListener('load', function() {
  setTimeout(function(){ window.print(); }, 400);
});
<?php endif; ?>
</script>
</body>
</html>
