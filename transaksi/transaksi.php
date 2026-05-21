<?php
require_once '../config/config.php';
$title = "Data Transaksi";

// Hapus transaksi
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi = $id");
    header("Location: transaksi.php?msg=hapus");
    exit;
}

// Ambil semua transaksi + nama pembeli (db_kantin: tabel pembelian)
$sql = "SELECT t.*, 
               COALESCE(p.nama, CONCAT('Pembeli #', t.id_beli)) AS nama_pembeli
        FROM transaksi t
        LEFT JOIN pembelian p ON t.id_beli = p.id_beli
        ORDER BY t.tanggal DESC, t.id_transaksi DESC";
$result = mysqli_query($conn, $sql);
// Fallback jika kolom nama beda
if (!$result) {
    $result = mysqli_query($conn, "SELECT t.*, CONCAT('Pembeli #', t.id_beli) AS nama_pembeli
                                   FROM transaksi t ORDER BY t.tanggal DESC, t.id_transaksi DESC");
}
$transaksi = [];
if ($result) { while ($row = mysqli_fetch_assoc($result)) $transaksi[] = $row; }

// Total keseluruhan
$total_all = array_sum(array_column($transaksi, 'total_harga'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?> | _PROJECT_26</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary:   #2563eb;
      --primary-light: #eff6ff;
      --success:   #16a34a;
      --warning:   #d97706;
      --danger:    #dc2626;
      --dark:      #1e293b;
      --gray:      #64748b;
      --light-bg:  #f1f5f9;
      --sidebar-w: 250px;
    }
    body { font-family: 'Poppins', sans-serif; background: var(--light-bg); color: var(--dark); }

    /* ---- CARD STAT ---- */
    .stat-card { border: none; border-radius: 16px; padding: 1.25rem 1.5rem; color: #fff; position: relative; overflow: hidden; }
    .stat-card::after { content:''; position:absolute; right:-20px; top:-20px; width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,.15); }
    .stat-card .stat-icon { font-size: 2rem; opacity:.85; }
    .stat-card .stat-val  { font-size: 1.5rem; font-weight: 700; margin: .25rem 0 0; }
    .stat-card .stat-lbl  { font-size: .78rem; opacity:.85; }
    .bg-primary-g { background: linear-gradient(135deg,#2563eb,#60a5fa); }
    .bg-success-g { background: linear-gradient(135deg,#16a34a,#4ade80); }
    .bg-warning-g { background: linear-gradient(135deg,#d97706,#fbbf24); }
    .bg-danger-g  { background: linear-gradient(135deg,#dc2626,#f87171); }

    /* ---- TABLE ---- */
    .card-table { border: none; border-radius: 16px; box-shadow: 0 2px 20px rgba(0,0,0,.07); overflow: hidden; }
    .card-table .card-header { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.5rem; }
    .table thead th { background: var(--primary); color: #fff; font-weight: 600; font-size: .82rem; letter-spacing: .04em; text-transform: uppercase; border: none; padding: .85rem 1rem; }
    .table tbody td { padding: .8rem 1rem; vertical-align: middle; font-size: .88rem; border-color: #f1f5f9; }
    .table tbody tr:hover { background: var(--primary-light); }
    .no-faktur-link { font-weight: 600; color: var(--primary); cursor: pointer; text-decoration: none; }
    .no-faktur-link:hover { text-decoration: underline; }

    /* ---- BADGE STATUS ---- */
    .badge-lunas   { background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; }
    .badge-pending { background:#fef9c3; color:#d97706; border:1px solid #fde68a; }
    .badge-batal   { background:#fee2e2; color:#dc2626; border:1px solid #fecaca; }
    .badge-status  { padding:.35em .8em; border-radius:20px; font-size:.75rem; font-weight:600; }

    /* ---- BADGE METODE ---- */
    .badge-tunai    { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
    .badge-transfer { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
    .badge-kredit   { background:#fff7ed; color:#d97706; border:1px solid #fed7aa; }

    /* ---- BTN AKSI ---- */
    .btn-aksi { width:32px; height:32px; padding:0; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; font-size:.8rem; border:none; }

    /* ---- SEARCH ---- */
    .input-search { border-radius: 10px; border: 1px solid #e2e8f0; padding: .5rem 1rem .5rem 2.5rem; font-size: .88rem; }
    .search-wrap  { position: relative; }
    .search-wrap .fa-search { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:var(--gray); font-size:.82rem; }

    /* ---- ALERT ---- */
    .alert-top { border-radius: 12px; font-size: .88rem; }
  </style>
</head>
<body>

<div class="d-flex">
  <?php include '../assets/sidebar.php'; ?>

  <div class="flex-grow-1" style="min-height:100vh;">
    <?php include '../assets/navbar.php'; ?>

    <div class="container-fluid p-4">

      <!-- ALERT -->
      <?php if (isset($_GET['msg'])): ?>
        <?php $msgs = ['hapus'=>['danger','fa-trash','Transaksi berhasil dihapus!'], 'tambah'=>['success','fa-check-circle','Transaksi berhasil ditambahkan!'], 'update'=>['info','fa-edit','Transaksi berhasil diperbarui!']]; $m = $msgs[$_GET['msg']] ?? null; ?>
        <?php if ($m): ?>
        <div class="alert alert-<?= $m[0] ?> alert-dismissible fade show alert-top d-flex align-items-center gap-2" role="alert">
          <i class="fas <?= $m[1] ?>"></i> <?= $m[2] ?>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- PAGE HEADER -->
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h4 class="fw-700 mb-0"><i class="fas fa-receipt me-2 text-primary"></i>Data Transaksi</h4>
          <small class="text-muted">Kelola semua transaksi penjualan</small>
        </div>
        <a href="tambah_transaksi.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
          <i class="fas fa-plus me-2"></i>Tambah Transaksi
        </a>
      </div>

      <!-- STAT CARDS -->
      <div class="row g-3 mb-4">
        <?php
          $total_trx    = count($transaksi);
          $total_lunas  = count(array_filter($transaksi, fn($r) => $r['status']==='lunas'));
          $total_pending= count(array_filter($transaksi, fn($r) => $r['status']==='pending'));
          $total_batal  = count(array_filter($transaksi, fn($r) => $r['status']==='batal'));
        ?>
        <div class="col-6 col-md-3">
          <div class="stat-card bg-primary-g">
            <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
            <div class="stat-val"><?= $total_trx ?></div>
            <div class="stat-lbl">Total Transaksi</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card bg-success-g">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-val"><?= $total_lunas ?></div>
            <div class="stat-lbl">Lunas</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card bg-warning-g">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-val"><?= $total_pending ?></div>
            <div class="stat-lbl">Pending</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card bg-danger-g">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-val">Rp <?= number_format($total_all,0,'.','.') ?></div>
            <div class="stat-lbl">Total Omzet</div>
          </div>
        </div>
      </div>

      <!-- TABLE CARD -->
      <div class="card card-table">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
          <span class="fw-600"><i class="fas fa-list me-2 text-primary"></i>Daftar Transaksi</span>
          <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" class="input-search form-control" placeholder="Cari no faktur / pembeli...">
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0" id="tblTransaksi">
              <thead>
                <tr>
                  <th>#</th>
                  <th>No Faktur</th>
                  <th>Pembeli</th>
                  <th>Tanggal</th>
                  <th>Metode</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($transaksi)): ?>
                <tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Belum ada data transaksi</td></tr>
                <?php else: ?>
                <?php foreach ($transaksi as $i => $t): ?>
                <tr>
                  <td class="text-muted fw-500"><?= $i+1 ?></td>
                  <td>
                    <a href="detail_transaksi.php?id=<?= $t['id_transaksi'] ?>" class="no-faktur-link">
                      <i class="fas fa-file-alt me-1"></i><?= htmlspecialchars($t['no_faktur']) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($t['nama_pembeli']) ?></td>
                  <td><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                  <td>
                    <?php $m = $t['metode_pembayaran']; $icons=['tunai'=>'fa-money-bill','transfer'=>'fa-university','kredit'=>'fa-credit-card']; ?>
                    <span class="badge-status badge-<?= $m ?>">
                      <i class="fas <?= $icons[$m] ?? 'fa-money-bill' ?> me-1"></i><?= ucfirst($m) ?>
                    </span>
                  </td>
                  <td class="fw-600 text-dark">Rp <?= number_format($t['total_harga'],0,'.','.') ?></td>
                  <td>
                    <?php $s=$t['status']; ?>
                    <span class="badge-status badge-<?= $s ?>">
                      <?php if($s==='lunas') echo '<i class="fas fa-check me-1"></i>Lunas';
                            elseif($s==='pending') echo '<i class="fas fa-clock me-1"></i>Pending';
                            else echo '<i class="fas fa-times me-1"></i>Batal'; ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                      <!-- Lihat detail -->
                      <a href="detail_transaksi.php?id=<?= $t['id_transaksi'] ?>" class="btn-aksi btn btn-outline-primary" title="Detail">
                        <i class="fas fa-eye"></i>
                      </a>
                      <!-- Print nota -->
                      <a href="detail_transaksi.php?id=<?= $t['id_transaksi'] ?>&print=1" target="_blank" class="btn-aksi btn btn-outline-success" title="Cetak Nota">
                        <i class="fas fa-print"></i>
                      </a>
                      <!-- Edit -->
                      <a href="tambah_transaksi.php?edit=<?= $t['id_transaksi'] ?>" class="btn-aksi btn btn-outline-warning" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <!-- Hapus -->
                      <button class="btn-aksi btn btn-outline-danger" title="Hapus"
                        onclick="konfirmasiHapus(<?= $t['id_transaksi'] ?>, '<?= htmlspecialchars($t['no_faktur']) ?>')">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php if (!empty($transaksi)): ?>
        <div class="card-footer bg-white text-end pe-4 py-3">
          <span class="text-muted me-2 small">Total Omzet Keseluruhan:</span>
          <strong class="text-primary fs-6">Rp <?= number_format($total_all,0,'.','.') ?></strong>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /container -->
  </div><!-- /flex-grow -->
</div>

<!-- MODAL HAPUS -->
<div class="modal fade" id="modalHapus" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-700"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Yakin ingin menghapus transaksi <strong id="spanFaktur"></strong>?<br>
        <small class="text-muted">Semua detail transaksi juga akan ikut terhapus.</small></p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
        <a id="btnHapusConfirm" href="#" class="btn btn-danger rounded-pill px-4">
          <i class="fas fa-trash me-1"></i>Hapus
        </a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search filter
document.getElementById('searchInput').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#tblTransaksi tbody tr').forEach(tr => {
    tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
  });
});

// Konfirmasi hapus
function konfirmasiHapus(id, faktur) {
  document.getElementById('spanFaktur').textContent = faktur;
  document.getElementById('btnHapusConfirm').href = `transaksi.php?hapus=${id}`;
  new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>
</body>
</html>
