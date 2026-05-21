<?php
require_once '../config/config.php';
$title = "Tambah Transaksi";
$is_edit = isset($_GET['edit']) && intval($_GET['edit']) > 0;
$edit_id = $is_edit ? intval($_GET['edit']) : 0;

// ---- PROSES SAVE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_beli   = intval($_POST['id_beli']);
    $tanggal   = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $metode    = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    $status    = mysqli_real_escape_string($conn, $_POST['status']);
    $keterangan= mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');

    // Produk & jumlah (array)
    $produk_ids = $_POST['id_produk']   ?? [];
    $jumlah_arr = $_POST['jumlah']      ?? [];
    $harga_arr  = $_POST['harga']       ?? [];

    // Hitung total
    $total = 0;
    foreach ($produk_ids as $k => $pid) {
        if (empty($pid)) continue;
        $total += floatval($harga_arr[$k]) * intval($jumlah_arr[$k]);
    }

    if ($is_edit) {
        // Update header
        $sql = "UPDATE transaksi SET id_beli='$id_beli', tanggal='$tanggal', metode_pembayaran='$metode',
                status='$status', keterangan='$keterangan', total_harga='$total'
                WHERE id_transaksi=$edit_id";
        mysqli_query($conn, $sql);
        // Hapus detail lama
        mysqli_query($conn, "DELETE FROM detail_transaksi WHERE id_transaksi=$edit_id");
        $trx_id = $edit_id;
    } else {
        // Generate no faktur
        $tahun   = date('Y');
        $res_seq = mysqli_query($conn, "SELECT COUNT(*)+1 AS seq FROM transaksi WHERE YEAR(tanggal)='$tahun'");
        $seq     = mysqli_fetch_assoc($res_seq)['seq'];
        $no_faktur = 'INV-' . $tahun . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // Insert header
        $sql = "INSERT INTO transaksi (no_faktur,id_beli,tanggal,metode_pembayaran,status,keterangan,total_harga)
                VALUES ('$no_faktur','$id_beli','$tanggal','$metode','$status','$keterangan','$total')";
        mysqli_query($conn, $sql);
        $trx_id = mysqli_insert_id($conn);
    }

    // Insert detail
    foreach ($produk_ids as $k => $pid) {
        if (empty($pid)) continue;
        $pid  = intval($pid);
        $jml  = intval($jumlah_arr[$k]);
        $hrg  = floatval($harga_arr[$k]);
        $sub  = $jml * $hrg;
        mysqli_query($conn, "INSERT INTO detail_transaksi (id_transaksi,id_produk,jumlah,harga,subtotal)
                             VALUES ($trx_id,$pid,$jml,$hrg,$sub)");
    }

    header("Location: transaksi.php?msg=" . ($is_edit ? 'update' : 'tambah'));
    exit;
}

// ---- LOAD DATA EDIT ----
$trx_data = [];
$detail_data = [];
if ($is_edit) {
    $r = mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi=$edit_id LIMIT 1");
    $trx_data = mysqli_fetch_assoc($r) ?? [];
    $r2 = mysqli_query($conn, "SELECT * FROM detail_transaksi WHERE id_transaksi=$edit_id");
    while ($row = mysqli_fetch_assoc($r2)) $detail_data[] = $row;
    $title = "Edit Transaksi";
}

// ---- DATA UNTUK DROPDOWN ----
// Pembeli (dari tabel pembelian)
$pembeli_list = [];
$r = mysqli_query($conn, "SELECT id_beli, nama AS nama_pembeli FROM pembelian ORDER BY nama ASC");
if ($r) while ($row = mysqli_fetch_assoc($r)) $pembeli_list[] = $row;

// Produk
$produk_list = [];
$r = mysqli_query($conn, "SELECT id AS id_produk, nama AS nama_produk, harga_jual AS harga FROM barang WHERE aktif=1 ORDER BY nama ASC");
if ($r) while ($row = mysqli_fetch_assoc($r)) $produk_list[] = $row;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?> | _PROJECT_26</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --primary:#2563eb; --light-bg:#f1f5f9; --dark:#1e293b; }
    body { font-family:'Poppins',sans-serif; background:var(--light-bg); color:var(--dark); }

    .form-card { background:#fff; border-radius:20px; box-shadow:0 4px 24px rgba(0,0,0,.08); overflow:hidden; }
    .form-card-header { background:linear-gradient(135deg,#1e3a8a,#2563eb); color:#fff; padding:1.5rem 2rem; }
    .form-card-body { padding:2rem; }

    .form-label { font-size:.82rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:#475569; margin-bottom:.35rem; }
    .form-control, .form-select {
      border-radius:10px; border:1.5px solid #e2e8f0;
      font-size:.9rem; padding:.6rem 1rem;
      transition:.2s;
    }
    .form-control:focus, .form-select:focus {
      border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.12);
    }

    /* ITEM TABLE */
    .item-table thead th { background:#f8fafc; font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; color:#64748b; font-weight:700; padding:.75rem 1rem; border-bottom:2px solid #e2e8f0; }
    .item-table tbody td { padding:.6rem .75rem; vertical-align:middle; }
    .item-row td { background:#fff; }
    .item-row:hover td { background:#eff6ff; }

    .btn-remove-row { width:32px; height:32px; border-radius:8px; padding:0; font-size:.8rem; display:inline-flex; align-items:center; justify-content:center; }

    .total-panel { background:linear-gradient(135deg,#eff6ff,#dbeafe); border:1px solid #bfdbfe; border-radius:14px; padding:1.2rem 1.5rem; }
    .total-panel .total-lbl { font-size:.82rem; color:#1e40af; font-weight:500; }
    .total-panel .total-val { font-size:1.5rem; font-weight:700; color:#1e3a8a; }

    .section-title { font-size:.78rem; text-transform:uppercase; letter-spacing:.07em; color:#64748b; font-weight:700; border-left:3px solid var(--primary); padding-left:.6rem; margin-bottom:1.2rem; }
  </style>
</head>
<body>
<div class="d-flex">
  <?php include '../assets/sidebar.php'; ?>
  <div class="flex-grow-1">
    <?php include '../assets/navbar.php'; ?>

    <div class="container-fluid p-4">

      <!-- BREADCRUMB -->
      <div class="d-flex align-items-center mb-4">
        <a href="transaksi.php" class="btn btn-outline-secondary rounded-pill btn-sm me-3">
          <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
        <div>
          <h5 class="fw-700 mb-0">
            <i class="fas fa-<?= $is_edit ? 'edit' : 'plus-circle' ?> me-2 text-primary"></i><?= $title ?>
          </h5>
          <small class="text-muted">Isi data transaksi dengan lengkap</small>
        </div>
      </div>

      <form method="POST" id="formTransaksi">
        <div class="row g-4">

          <!-- KOLOM KIRI: Info Transaksi -->
          <div class="col-lg-5">
            <div class="form-card h-100">
              <div class="form-card-header">
                <i class="fas fa-file-invoice me-2"></i>Informasi Transaksi
              </div>
              <div class="form-card-body">

                <!-- PEMBELI -->
                <div class="mb-3">
                  <label class="form-label"><i class="fas fa-user me-1"></i>Pembeli</label>
                  <select name="id_beli" class="form-select" required>
                    <option value="">-- Pilih Pembeli --</option>
                    <?php foreach ($pembeli_list as $p): ?>
                    <option value="<?= $p['id_beli'] ?>"
                      <?= (!empty($trx_data) && $trx_data['id_beli'] == $p['id_beli']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($p['nama_pembeli']) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (empty($pembeli_list)): ?>
                  <div class="form-text text-warning"><i class="fas fa-exclamation-triangle me-1"></i>
                    Belum ada data pembeli. <a href="../pembeli/pembelian.php">Tambah pembeli</a> dulu.
                  </div>
                  <?php endif; ?>
                </div>

                <!-- TANGGAL -->
                <div class="mb-3">
                  <label class="form-label"><i class="fas fa-calendar me-1"></i>Tanggal Transaksi</label>
                  <input type="date" name="tanggal" class="form-control" required
                    value="<?= $is_edit ? $trx_data['tanggal'] : date('Y-m-d') ?>">
                </div>

                <!-- METODE PEMBAYARAN -->
                <div class="mb-3">
                  <label class="form-label"><i class="fas fa-money-bill me-1"></i>Metode Pembayaran</label>
                  <select name="metode_pembayaran" class="form-select" required>
                    <?php foreach (['tunai'=>'Tunai','transfer'=>'Transfer Bank','kredit'=>'Kredit'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= (!empty($trx_data) && $trx_data['metode_pembayaran']==$k)?'selected':'' ?>>
                      <?= $v ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- STATUS -->
                <div class="mb-3">
                  <label class="form-label"><i class="fas fa-flag me-1"></i>Status</label>
                  <select name="status" class="form-select" required>
                    <?php foreach (['lunas'=>'Lunas','pending'=>'Pending','batal'=>'Batal'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= (!empty($trx_data) && $trx_data['status']==$k)?'selected':'' ?>>
                      <?= $v ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- KETERANGAN -->
                <div class="mb-3">
                  <label class="form-label"><i class="fas fa-sticky-note me-1"></i>Keterangan (opsional)</label>
                  <textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan tambahan..."><?= htmlspecialchars($trx_data['keterangan'] ?? '') ?></textarea>
                </div>

                <!-- TOTAL PANEL -->
                <div class="total-panel mt-4">
                  <div class="total-lbl mb-1"><i class="fas fa-calculator me-1"></i>Total Pembayaran</div>
                  <div class="total-val" id="displayTotal">Rp 0</div>
                </div>

              </div>
            </div>
          </div>

          <!-- KOLOM KANAN: Detail Produk -->
          <div class="col-lg-7">
            <div class="form-card">
              <div class="form-card-header d-flex align-items-center justify-content-between">
                <span><i class="fas fa-box-open me-2"></i>Detail Produk</span>
                <button type="button" class="btn btn-light btn-sm rounded-pill" onclick="tambahBaris()">
                  <i class="fas fa-plus me-1"></i>Tambah Produk
                </button>
              </div>
              <div class="form-card-body p-0">
                <div class="table-responsive">
                  <table class="table item-table mb-0" id="tblItem">
                    <thead>
                      <tr>
                        <th>Produk</th>
                        <th style="width:80px">Jumlah</th>
                        <th style="width:130px">Harga</th>
                        <th style="width:120px">Subtotal</th>
                        <th style="width:40px"></th>
                      </tr>
                    </thead>
                    <tbody id="tbodyItem">
                      <?php if (!empty($detail_data)): ?>
                        <?php foreach ($detail_data as $d): ?>
                        <tr class="item-row">
                          <td>
                            <select name="id_produk[]" class="form-select form-select-sm produk-select" onchange="updateHarga(this)">
                              <option value="">-- Pilih --</option>
                              <?php foreach ($produk_list as $p): ?>
                              <option value="<?= $p['id_produk'] ?>" data-harga="<?= $p['harga'] ?>"
                                <?= $d['id_produk']==$p['id_produk']?'selected':'' ?>>
                                <?= htmlspecialchars($p['nama_produk']) ?>
                              </option>
                              <?php endforeach; ?>
                            </select>
                          </td>
                          <td><input type="number" name="jumlah[]" class="form-control form-control-sm jumlah-input" min="1" value="<?= $d['jumlah'] ?>" onchange="hitungSubtotal(this)"></td>
                          <td><input type="number" name="harga[]" class="form-control form-control-sm harga-input" value="<?= $d['harga'] ?>" onchange="hitungSubtotal(this)"></td>
                          <td><input type="number" name="subtotal[]" class="form-control form-control-sm subtotal-input" value="<?= $d['subtotal'] ?>" readonly style="background:#f8fafc;"></td>
                          <td><button type="button" class="btn btn-outline-danger btn-remove-row" onclick="hapusBaris(this)"><i class="fas fa-times"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

                <div class="p-3">
                  <button type="button" class="btn btn-outline-primary w-100 rounded-pill btn-sm" onclick="tambahBaris()">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Baris Produk
                  </button>
                </div>
              </div>
            </div>

            <!-- SUBMIT -->
            <div class="d-flex gap-3 mt-3 justify-content-end">
              <a href="transaksi.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-times me-2"></i>Batal
              </a>
              <button type="submit" class="btn btn-primary rounded-pill px-5">
                <i class="fas fa-save me-2"></i><?= $is_edit ? 'Simpan Perubahan' : 'Simpan Transaksi' ?>
              </button>
            </div>
          </div>

        </div>
      </form>

    </div>
  </div>
</div>

<!-- DATA PRODUK UNTUK JS -->
<script>
const produkData = <?= json_encode(array_column($produk_list, null, 'id_produk')) ?>;

function getProdukOptions(selectedId) {
  let opts = '<option value="">-- Pilih Produk --</option>';
  <?php foreach ($produk_list as $p): ?>
  opts += `<option value="<?= $p['id_produk'] ?>" data-harga="<?= $p['harga'] ?>"
    ${selectedId == <?= $p['id_produk'] ?> ? 'selected' : ''}>
    <?= htmlspecialchars($p['nama_produk']) ?>
  </option>`;
  <?php endforeach; ?>
  return opts;
}

function tambahBaris() {
  const row = document.createElement('tr');
  row.className = 'item-row';
  row.innerHTML = `
    <td>
      <select name="id_produk[]" class="form-select form-select-sm produk-select" onchange="updateHarga(this)">
        ${getProdukOptions(null)}
      </select>
    </td>
    <td><input type="number" name="jumlah[]" class="form-control form-control-sm jumlah-input" min="1" value="1" onchange="hitungSubtotal(this)"></td>
    <td><input type="number" name="harga[]" class="form-control form-control-sm harga-input" value="0" onchange="hitungSubtotal(this)"></td>
    <td><input type="number" name="subtotal[]" class="form-control form-control-sm subtotal-input" value="0" readonly style="background:#f8fafc;"></td>
    <td><button type="button" class="btn btn-outline-danger btn-remove-row" onclick="hapusBaris(this)"><i class="fas fa-times"></i></button></td>
  `;
  document.getElementById('tbodyItem').appendChild(row);
}

function hapusBaris(btn) {
  const rows = document.querySelectorAll('#tbodyItem .item-row');
  if (rows.length > 1) {
    btn.closest('tr').remove();
    hitungTotal();
  } else {
    alert('Minimal 1 produk!');
  }
}

function updateHarga(sel) {
  const opt = sel.options[sel.selectedIndex];
  const harga = opt.dataset.harga || 0;
  const row = sel.closest('tr');
  row.querySelector('.harga-input').value = harga;
  hitungSubtotal(sel);
}

function hitungSubtotal(el) {
  const row = el.closest('tr');
  const jumlah = parseFloat(row.querySelector('.jumlah-input').value) || 0;
  const harga  = parseFloat(row.querySelector('.harga-input').value)  || 0;
  const sub    = jumlah * harga;
  row.querySelector('.subtotal-input').value = sub;
  hitungTotal();
}

function hitungTotal() {
  let total = 0;
  document.querySelectorAll('.subtotal-input').forEach(i => total += parseFloat(i.value)||0);
  document.getElementById('displayTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

// Init: hitung total saat load (mode edit)
window.addEventListener('load', hitungTotal);

// Tambah baris kosong otomatis jika form baru
<?php if (!$is_edit): ?>
window.addEventListener('load', function() {
  if (document.querySelectorAll('#tbodyItem .item-row').length === 0) tambahBaris();
});
<?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
