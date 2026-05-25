<?php
if(!isset($conn)) require_once __DIR__.'/../config/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { header("Location: transaksi.php"); exit; }

// ══════════════════════════════════════════
// HAPUS TRANSAKSI
// ══════════════════════════════════════════
if (isset($_GET['hapus']) && $_GET['hapus'] == $id) {
    // Kembalikan stok barang dulu
    $det = mysqli_query($conn, "SELECT id_produk, jumlah FROM detail_transaksi WHERE id_transaksi=$id");
    if ($det) {
        while ($d = mysqli_fetch_assoc($det)) {
            mysqli_query($conn, "UPDATE barang SET stok = stok + {$d['jumlah']} WHERE id = {$d['id_produk']}");
        }
    }
    // Hapus transaksi (detail ikut terhapus via CASCADE)
    mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi=$id");
    header("Location: /?page=transaksi&msg=hapus");
    exit;
}

// ══════════════════════════════════════════
// SIMPAN EDIT
// ══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal  = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $metode   = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    $status   = mysqli_real_escape_string($conn, $_POST['status']);
    $ket      = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');

    $ids_produk = $_POST['id_produk'] ?? [];
    $jmls       = $_POST['jumlah']    ?? [];
    $hargas     = $_POST['harga']     ?? [];

    // Hitung total baru
    $total_baru = 0;
    foreach ($ids_produk as $k => $pid) {
        if (empty($pid)) continue;
        $total_baru += floatval($hargas[$k]) * intval($jmls[$k]);
    }

    // Ambil detail lama untuk restore stok
    $det_lama = mysqli_query($conn, "SELECT id_produk, jumlah FROM detail_transaksi WHERE id_transaksi=$id");
    if ($det_lama) {
        while ($d = mysqli_fetch_assoc($det_lama)) {
            mysqli_query($conn, "UPDATE barang SET stok = stok + {$d['jumlah']} WHERE id = {$d['id_produk']}");
        }
    }

    // Update header transaksi
    mysqli_query($conn, "UPDATE transaksi SET
        tanggal='$tanggal',
        metode_pembayaran='$metode',
        status='$status',
        keterangan='$ket',
        total_harga='$total_baru'
        WHERE id_transaksi=$id");

    // Hapus detail lama, insert baru
    mysqli_query($conn, "DELETE FROM detail_transaksi WHERE id_transaksi=$id");

    foreach ($ids_produk as $k => $pid) {
        if (empty($pid)) continue;
        $pid     = intval($pid);
        $jml     = intval($jmls[$k]);
        $harga   = floatval($hargas[$k]);
        $subtotal= $jml * $harga;

        mysqli_query($conn, "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, harga, subtotal)
                             VALUES ($id, $pid, $jml, $harga, $subtotal)");

        // Kurangi stok sesuai qty baru
        mysqli_query($conn, "UPDATE barang SET stok = stok - $jml WHERE id = $pid");
    }

    header("Location: /?page=transaksi&msg=update");
    exit;
}

// ══════════════════════════════════════════
// LOAD DATA TRANSAKSI
// ══════════════════════════════════════════
$r   = mysqli_query($conn, "SELECT t.*,
           COALESCE(p.nama, CONCAT('Pembeli #', t.id_beli)) AS nama_pembeli
       FROM transaksi t
       LEFT JOIN pembelian p ON t.id_beli = p.id_beli
       WHERE t.id_transaksi=$id LIMIT 1");
if (!$r) $r = mysqli_query($conn, "SELECT *, CONCAT('Pembeli #', id_beli) AS nama_pembeli FROM transaksi WHERE id_transaksi=$id LIMIT 1");
$trx = $r ? mysqli_fetch_assoc($r) : null;
if (!$trx) { header("Location: transaksi.php"); exit; }

// Detail item
$r2     = mysqli_query($conn, "SELECT dt.*, b.nama AS nama_produk, b.harga_jual
                                FROM detail_transaksi dt
                                LEFT JOIN barang b ON dt.id_produk = b.id
                                WHERE dt.id_transaksi=$id ORDER BY dt.id_detail");
$detail = [];
if ($r2) while ($row = mysqli_fetch_assoc($r2)) $detail[] = $row;

// Daftar barang untuk dropdown
$rb      = mysqli_query($conn, "SELECT id, nama, harga_jual FROM barang WHERE aktif=1 ORDER BY nama");
$barangs = $rb ? mysqli_fetch_all($rb, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi | _PROJECT_26</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--primary:#2563eb;--danger:#dc2626;--success:#16a34a;--warning:#d97706;--light-bg:#f1f5f9;--dark:#1e293b;}
        body{font-family:'Poppins',sans-serif;background:var(--light-bg);color:var(--dark);}

        /* ── CARD ── */
        .card-form{background:#fff;border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;}
        .card-form-header{background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;padding:1.4rem 2rem;}
        .card-form-body{padding:2rem;}

        .form-label{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#475569;margin-bottom:.3rem;}
        .form-control,.form-select{border-radius:10px;border:1.5px solid #e2e8f0;font-size:.9rem;padding:.6rem 1rem;transition:.2s;}
        .form-control:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.12);}

        /* ── TABEL ITEM ── */
        .tbl-item thead th{background:#f8fafc;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;padding:.75rem 1rem;border-bottom:2px solid #e2e8f0;}
        .tbl-item tbody td{padding:.55rem .75rem;vertical-align:middle;}
        .tbl-item tbody tr:hover td{background:#eff6ff;}

        /* ── TOTAL PANEL ── */
        .total-panel{background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #bfdbfe;border-radius:14px;padding:1.2rem 1.5rem;}
        .total-panel .val{font-size:1.4rem;font-weight:700;color:#1e3a8a;}

        /* ── HAPUS MODAL ── */
        .modal-content{border:none;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.15);}

        /* ── BTN REMOVE ── */
        .btn-rm{width:30px;height:30px;padding:0;border-radius:8px;font-size:.75rem;display:inline-flex;align-items:center;justify-content:center;}
    </style>
</head>
<body>
<div class="d-flex">
    <?php include __DIR__.'/../assets/sidebar.php'; ?>
    <div class="flex-grow-1">
        <?php include __DIR__.'/../assets/navbar.php'; ?>

        <div class="container-fluid p-4">

            <!-- BREADCRUMB -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-3">
                    <a href="?page=transaksi" class="btn btn-outline-secondary rounded-pill btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Kembali
                    </a>
                    <div>
                        <h5 class="fw-700 mb-0"><i class="fas fa-edit me-2 text-primary"></i>Edit Transaksi</h5>
                        <small class="text-muted"><?= htmlspecialchars($trx['no_faktur']) ?></small>
                    </div>
                </div>
                <!-- Tombol Hapus -->
                <button class="btn btn-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalHapus">
                    <i class="fas fa-trash me-2"></i>Hapus Transaksi
                </button>
            </div>

            <form method="POST" id="formEdit">
            <div class="row g-4">

                <!-- KOLOM KIRI: Info -->
                <div class="col-lg-4">
                    <div class="card-form">
                        <div class="card-form-header">
                            <i class="fas fa-info-circle me-2"></i>Informasi Transaksi
                        </div>
                        <div class="card-form-body">

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-hashtag me-1"></i>No Faktur</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($trx['no_faktur']) ?>" readonly style="background:#f8fafc;font-weight:600;">
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-user me-1"></i>Pembeli</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($trx['nama_pembeli']) ?>" readonly style="background:#f8fafc;">
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-calendar me-1"></i>Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="<?= $trx['tanggal'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-money-bill me-1"></i>Metode Pembayaran</label>
                                <select name="metode_pembayaran" class="form-select">
                                    <?php foreach(['tunai'=>'Tunai','transfer'=>'Transfer Bank','kredit'=>'Kredit/Hutang'] as $k=>$v): ?>
                                    <option value="<?= $k ?>" <?= $trx['metode_pembayaran']==$k?'selected':'' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-flag me-1"></i>Status</label>
                                <select name="status" class="form-select">
                                    <?php
                                    $statuses = ['lunas'=>'✅ Lunas','pending'=>'⏳ Pending','batal'=>'❌ Batal'];
                                    foreach($statuses as $k=>$v): ?>
                                    <option value="<?= $k ?>" <?= $trx['status']==$k?'selected':'' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-sticky-note me-1"></i>Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($trx['keterangan']??'') ?></textarea>
                            </div>

                            <!-- TOTAL -->
                            <div class="total-panel">
                                <div class="text-muted small mb-1"><i class="fas fa-calculator me-1"></i>Total Pembayaran</div>
                                <div class="val" id="displayTotal">Rp <?= number_format($trx['total_harga'],0,'.','.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: Detail Item -->
                <div class="col-lg-8">
                    <div class="card-form">
                        <div class="card-form-header d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-box-open me-2"></i>Detail Produk / Barang</span>
                            <button type="button" class="btn btn-light btn-sm rounded-pill" onclick="tambahBaris()">
                                <i class="fas fa-plus me-1"></i>Tambah Baris
                            </button>
                        </div>
                        <div class="card-form-body p-0">
                            <div class="table-responsive">
                                <table class="table tbl-item mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th style="width:80px">Jumlah</th>
                                            <th style="width:130px">Harga</th>
                                            <th style="width:130px">Subtotal</th>
                                            <th style="width:38px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyItem">
                                    <?php foreach($detail as $d): ?>
                                    <tr class="item-row">
                                        <td>
                                            <select name="id_produk[]" class="form-select form-select-sm produk-sel" onchange="updateHarga(this)">
                                                <option value="">-- Pilih --</option>
                                                <?php foreach($barangs as $b): ?>
                                                <option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_jual'] ?>"
                                                    <?= $d['id_produk']==$b['id']?'selected':'' ?>>
                                                    <?= htmlspecialchars($b['nama']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="number" name="jumlah[]" class="form-control form-control-sm jml-inp" min="1" value="<?= $d['jumlah'] ?>" onchange="hitungSub(this)"></td>
                                        <td><input type="number" name="harga[]"  class="form-control form-control-sm hrg-inp"  value="<?= $d['harga'] ?>"  onchange="hitungSub(this)"></td>
                                        <td><input type="number" name="subtotal[]" class="form-control form-control-sm sub-inp" value="<?= $d['subtotal'] ?>" readonly style="background:#f8fafc;"></td>
                                        <td><button type="button" class="btn btn-outline-danger btn-rm" onclick="hapusBaris(this)"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-3">
                                <button type="button" class="btn btn-outline-primary w-100 rounded-pill btn-sm" onclick="tambahBaris()">
                                    <i class="fas fa-plus-circle me-2"></i>Tambah Baris Barang
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL SUBMIT -->
                    <div class="d-flex gap-3 justify-content-end mt-3">
                        <a href="?page=transaksi" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                        <a href="transaksi/detail_transaksi.php?id=<?= $id ?>&print=1" target="_blank" class="btn btn-outline-success rounded-pill px-4">
                            <i class="fas fa-print me-2"></i>Cetak Nota
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </div>

            </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS -->
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-700 text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus transaksi <strong><?= htmlspecialchars($trx['no_faktur']) ?></strong>?</p>
                <div class="alert alert-warning rounded-3 py-2 px-3 small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Stok barang akan dikembalikan otomatis. Aksi ini tidak bisa dibatalkan.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <a href="?page=transaksi_edit&id=<?= $id ?>&hapus=<?= $id ?>" class="btn btn-danger rounded-pill px-4">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus
                </a>
            </div>
        </div>
    </div>
</div>

<!-- DATA BARANG UNTUK JS -->
<script>
const barangData = <?= json_encode(array_column($barangs, null, 'id')) ?>;

function getOptions(selId) {
    let o = '<option value="">-- Pilih Barang --</option>';
    <?php foreach($barangs as $b): ?>
    o += `<option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_jual'] ?>" ${selId==<?= $b['id'] ?>?'selected':''}><?= htmlspecialchars($b['nama']) ?></option>`;
    <?php endforeach; ?>
    return o;
}

function tambahBaris() {
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td><select name="id_produk[]" class="form-select form-select-sm produk-sel" onchange="updateHarga(this)">${getOptions(null)}</select></td>
        <td><input type="number" name="jumlah[]"   class="form-control form-control-sm jml-inp" min="1" value="1" onchange="hitungSub(this)"></td>
        <td><input type="number" name="harga[]"    class="form-control form-control-sm hrg-inp"  value="0"        onchange="hitungSub(this)"></td>
        <td><input type="number" name="subtotal[]" class="form-control form-control-sm sub-inp"  value="0" readonly style="background:#f8fafc;"></td>
        <td><button type="button" class="btn btn-outline-danger btn-rm" onclick="hapusBaris(this)"><i class="fas fa-times"></i></button></td>`;
    document.getElementById('tbodyItem').appendChild(tr);
}

function hapusBaris(btn) {
    const rows = document.querySelectorAll('#tbodyItem .item-row');
    if (rows.length > 1) { btn.closest('tr').remove(); hitungTotal(); }
    else alert('Minimal 1 item!');
}

function updateHarga(sel) {
    const opt  = sel.options[sel.selectedIndex];
    const hrg  = opt.dataset.harga || 0;
    sel.closest('tr').querySelector('.hrg-inp').value = hrg;
    hitungSub(sel);
}

function hitungSub(el) {
    const row = el.closest('tr');
    const jml = parseFloat(row.querySelector('.jml-inp').value) || 0;
    const hrg = parseFloat(row.querySelector('.hrg-inp').value) || 0;
    row.querySelector('.sub-inp').value = jml * hrg;
    hitungTotal();
}

function hitungTotal() {
    let total = 0;
    document.querySelectorAll('.sub-inp').forEach(i => total += parseFloat(i.value)||0);
    document.getElementById('displayTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

window.addEventListener('load', hitungTotal);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
