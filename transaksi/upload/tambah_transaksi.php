<?php
if(!isset($conn)) require_once __DIR__.'/../config/config.php';

$title = "Tambah Transaksi Manual";

// ══════════════════════════════════════════
// PROSES SIMPAN
// ══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_beli    = intval($_POST['id_beli']);
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $metode     = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    $status     = mysqli_real_escape_string($conn, $_POST['status']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');

    $ids_produk = $_POST['id_produk'] ?? [];
    $jmls       = $_POST['jumlah']    ?? [];
    $hargas     = $_POST['harga']     ?? [];

    // Hitung total
    $total = 0;
    foreach ($ids_produk as $k => $pid) {
        if (empty($pid)) continue;
        $total += floatval($hargas[$k]) * intval($jmls[$k]);
    }

    // Generate no faktur: INV-YYYYXXXX
    $tahun     = date('Y');
    $res_seq   = mysqli_query($conn, "SELECT COUNT(*)+1 AS seq FROM transaksi WHERE YEAR(tanggal)='$tahun'");
    $seq       = $res_seq ? (int)(mysqli_fetch_assoc($res_seq)['seq'] ?? 1) : 1;
    $no_faktur = 'INV-' . $tahun . str_pad($seq, 4, '0', STR_PAD_LEFT);

    // Insert transaksi header
    $sql = "INSERT INTO transaksi (no_faktur, id_beli, tanggal, metode_pembayaran, status, keterangan, total_harga)
            VALUES ('$no_faktur','$id_beli','$tanggal','$metode','$status','$keterangan','$total')";

    if (mysqli_query($conn, $sql)) {
        $trx_id = mysqli_insert_id($conn);

        // Insert detail
        foreach ($ids_produk as $k => $pid) {
            if (empty($pid)) continue;
            $pid     = intval($pid);
            $jml     = intval($jmls[$k]);
            $hrg     = floatval($hargas[$k]);
            $subtotal= $jml * $hrg;
            mysqli_query($conn, "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, harga, subtotal)
                                 VALUES ($trx_id, $pid, $jml, $hrg, $subtotal)");
            // Kurangi stok barang
            mysqli_query($conn, "UPDATE barang SET stok = stok - $jml WHERE id = $pid");
        }
        header("Location: /?page=transaksi&msg=tambah");
    } else {
        $error = "Gagal menyimpan: " . mysqli_error($conn);
    }
}

// ══════════════════════════════════════════
// DATA DROPDOWN
// ══════════════════════════════════════════

// Cek kolom nama di tabel pembelian
$kol_nama_pb = 'id_beli';
$cek = mysqli_query($conn, "SHOW COLUMNS FROM pembelian");
if ($cek) {
    while ($c = mysqli_fetch_assoc($cek)) {
        $f = strtolower($c['Field']);
        if (in_array($f, ['nama','nama_pembeli','nama_pelanggan','nama_vendor','nama_customer'])) {
            $kol_nama_pb = $c['Field']; break;
        }
    }
}
$pembeli_list = [];
if ($kol_nama_pb !== 'id_beli') {
    $r = mysqli_query($conn, "SELECT id_beli, $kol_nama_pb AS label FROM pembelian ORDER BY $kol_nama_pb ASC");
} else {
    $r = mysqli_query($conn, "SELECT id_beli, CONCAT('Pembelian #', id_beli) AS label FROM pembelian ORDER BY id_beli DESC");
}
if ($r) while ($row = mysqli_fetch_assoc($r)) $pembeli_list[] = $row;

// Barang
$barang_list = [];
$r = mysqli_query($conn, "SELECT id, nama, harga_jual FROM barang WHERE aktif=1 ORDER BY nama ASC");
if (!$r) $r = mysqli_query($conn, "SELECT id, nama, harga_jual FROM barang ORDER BY nama ASC");
if ($r) while ($row = mysqli_fetch_assoc($r)) $barang_list[] = $row;
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
        :root{--primary:#2563eb;--light-bg:#f1f5f9;--dark:#1e293b;--orange:#fd7e14;}
        body{font-family:'Poppins',sans-serif;background:var(--light-bg);color:var(--dark);}

        /* CARD */
        .card-form{background:#fff;border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;}
        .card-head{padding:1.4rem 2rem;color:#fff;}
        .card-head.blue{background:linear-gradient(135deg,#1e3a8a,#2563eb);}
        .card-head.orange{background:linear-gradient(135deg,#c2410c,#fd7e14);}
        .card-body-p{padding:1.8rem 2rem;}

        /* FORM */
        .form-label{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#475569;margin-bottom:.3rem;}
        .form-control,.form-select{border-radius:10px;border:1.5px solid #e2e8f0;font-size:.9rem;padding:.6rem 1rem;transition:.2s;}
        .form-control:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.12);}

        /* TABEL ITEM */
        .tbl-item thead th{background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;font-weight:700;padding:.8rem 1rem;border:none;}
        .tbl-item tbody td{padding:.6rem .75rem;vertical-align:middle;border-color:#f1f5f9;}
        .tbl-item tbody tr:hover td{background:#eff6ff;}
        .tbl-item tfoot td{background:#f8fafc;padding:.75rem 1rem;border-top:2px solid #e2e8f0;}

        /* TOTAL */
        .total-panel{background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #bfdbfe;border-radius:14px;padding:1.3rem 1.8rem;}
        .total-panel .lbl{font-size:.8rem;color:#1e40af;font-weight:600;}
        .total-panel .val{font-size:1.6rem;font-weight:700;color:#1e3a8a;}

        /* BTN REMOVE */
        .btn-rm{width:30px;height:30px;padding:0;border-radius:8px;font-size:.75rem;display:inline-flex;align-items:center;justify-content:center;}

        /* SECTION TITLE */
        .sec-title{font-size:.75rem;text-transform:uppercase;letter-spacing:.07em;color:#64748b;font-weight:700;border-left:3px solid var(--primary);padding-left:.6rem;margin-bottom:1rem;}

        /* INFO BOX */
        .info-note{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:1rem 1.2rem;font-size:.83rem;color:#9a3412;}
    </style>
</head>
<body>
<div class="d-flex">
    <?php include __DIR__.'/../assets/sidebar.php'; ?>
    <div class="flex-grow-1">
        <?php include __DIR__.'/../assets/navbar.php'; ?>

        <div class="container-fluid p-4">

            <!-- HEADER -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="?page=transaksi" class="btn btn-outline-secondary rounded-pill btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </a>
                <div>
                    <h5 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i><?= $title ?></h5>
                    <small class="text-muted">Input transaksi secara manual</small>
                </div>
            </div>

            <!-- INFO NOTE -->
            <div class="info-note mb-4">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Info:</strong> Transaksi biasanya otomatis masuk dari halaman <strong>Pembeli</strong> saat checkout.
                Gunakan form ini untuk input transaksi manual jika diperlukan.
            </div>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger rounded-3 mb-4"><i class="fas fa-times-circle me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" id="formTambah">
            <div class="row g-4">

                <!-- KOLOM KIRI: Info Transaksi -->
                <div class="col-lg-4">
                    <div class="card-form h-100">
                        <div class="card-head blue">
                            <i class="fas fa-file-invoice me-2"></i>Informasi Transaksi
                        </div>
                        <div class="card-body-p">

                            <!-- REFERENSI PEMBELIAN -->
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-shopping-cart me-1"></i>Referensi Pembelian</label>
                                <select name="id_beli" class="form-select" required>
                                    <option value="">-- Pilih Pembelian --</option>
                                    <?php foreach($pembeli_list as $p): ?>
                                    <option value="<?= $p['id_beli'] ?>"><?= htmlspecialchars($p['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-muted" style="font-size:.75rem;">
                                    <i class="fas fa-info-circle me-1"></i>Pilih ID pembelian yang menjadi referensi
                                </div>
                            </div>

                            <!-- TANGGAL -->
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-calendar me-1"></i>Tanggal Transaksi</label>
                                <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- METODE -->
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-money-bill-wave me-1"></i>Metode Pembayaran</label>
                                <select name="metode_pembayaran" class="form-select" required>
                                    <option value="Tunai">💵 Tunai / Cash</option>
                                    <option value="Hutang">📋 Hutang (Kredit)</option>
                                    <option value="transfer">🏦 Transfer Bank</option>
                                </select>
                            </div>

                            <!-- STATUS -->
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-flag me-1"></i>Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="lunas">✅ Lunas</option>
                                    <option value="pending">⏳ Pending</option>
                                    <option value="batal">❌ Batal</option>
                                </select>
                            </div>

                            <!-- KETERANGAN -->
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-sticky-note me-1"></i>Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)..."></textarea>
                            </div>

                            <!-- TOTAL PANEL -->
                            <div class="total-panel">
                                <div class="lbl mb-1"><i class="fas fa-calculator me-1"></i>Total Pembayaran</div>
                                <div class="val" id="displayTotal">Rp 0</div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: Detail Barang -->
                <div class="col-lg-8">
                    <div class="card-form">
                        <div class="card-head orange d-flex align-items-center justify-content-between">
                            <span><i class="fas fa-box-open me-2"></i>Detail Barang</span>
                            <button type="button" class="btn btn-light btn-sm rounded-pill fw-600" onclick="tambahBaris()">
                                <i class="fas fa-plus me-1"></i>Tambah Barang
                            </button>
                        </div>
                        <div>
                            <div class="table-responsive">
                                <table class="table tbl-item mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Barang</th>
                                            <th style="width:85px">Jumlah</th>
                                            <th style="width:135px">Harga Satuan</th>
                                            <th style="width:130px">Subtotal</th>
                                            <th style="width:38px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyItem">
                                        <!-- baris awal diisi JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end fw-600 text-muted">Grand Total</td>
                                            <td class="fw-700 text-primary" id="footTotal">Rp 0</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="p-3">
                                <button type="button" class="btn btn-outline-primary w-100 rounded-pill btn-sm" onclick="tambahBaris()">
                                    <i class="fas fa-plus-circle me-2"></i>Tambah Baris Barang
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL AKSI -->
                    <div class="d-flex gap-3 justify-content-end mt-3">
                        <a href="?page=transaksi" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-600">
                            <i class="fas fa-save me-2"></i>Simpan Transaksi
                        </button>
                    </div>
                </div>

            </div>
            </form>
        </div>
    </div>
</div>

<!-- DATA BARANG UNTUK JS -->
<script>
const barangData = <?= json_encode($barang_list) ?>;
let rowCount = 0;

function getBarangOptions(selectedId = null) {
    let o = '<option value="">-- Pilih Barang --</option>';
    barangData.forEach(b => {
        const sel = selectedId && selectedId == b.id ? 'selected' : '';
        o += `<option value="${b.id}" data-harga="${b.harga_jual}" ${sel}>${b.nama} — Rp ${parseInt(b.harga_jual).toLocaleString('id-ID')}</option>`;
    });
    return o;
}

function tambahBaris(selId = null, jml = 1, hrg = 0) {
    rowCount++;
    const tbody = document.getElementById('tbodyItem');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.dataset.row = rowCount;
    const sub = jml * hrg;
    tr.innerHTML = `
        <td class="text-muted fw-500 text-center">${rowCount}</td>
        <td>
            <select name="id_produk[]" class="form-select form-select-sm produk-sel" onchange="updateHarga(this)">
                ${getBarangOptions(selId)}
            </select>
        </td>
        <td>
            <input type="number" name="jumlah[]" class="form-control form-control-sm jml-inp"
                   min="1" value="${jml}" onchange="hitungSub(this)">
        </td>
        <td>
            <input type="number" name="harga[]" class="form-control form-control-sm hrg-inp"
                   value="${hrg}" onchange="hitungSub(this)">
        </td>
        <td>
            <input type="number" name="subtotal[]" class="form-control form-control-sm sub-inp"
                   value="${sub}" readonly style="background:#f8fafc;font-weight:600;">
        </td>
        <td>
            <button type="button" class="btn btn-outline-danger btn-rm" onclick="hapusBaris(this)" title="Hapus baris">
                <i class="fas fa-times"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
    hitungTotal();
}

function hapusBaris(btn) {
    const rows = document.querySelectorAll('#tbodyItem .item-row');
    if (rows.length <= 1) { alert('Minimal 1 barang harus ada!'); return; }
    btn.closest('tr').remove();
    renumberRows();
    hitungTotal();
}

function renumberRows() {
    document.querySelectorAll('#tbodyItem .item-row').forEach((tr, i) => {
        tr.cells[0].textContent = i + 1;
    });
}

function updateHarga(sel) {
    const opt = sel.options[sel.selectedIndex];
    const hrg = parseFloat(opt.dataset.harga) || 0;
    const row = sel.closest('tr');
    row.querySelector('.hrg-inp').value = hrg;
    hitungSub(sel);
}

function hitungSub(el) {
    const row = el.closest('tr');
    const jml = parseFloat(row.querySelector('.jml-inp').value) || 0;
    const hrg = parseFloat(row.querySelector('.hrg-inp').value) || 0;
    const sub = jml * hrg;
    row.querySelector('.sub-inp').value = sub;
    hitungTotal();
}

function hitungTotal() {
    let total = 0;
    document.querySelectorAll('.sub-inp').forEach(i => total += parseFloat(i.value) || 0);
    const fmt = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('displayTotal').textContent = fmt;
    document.getElementById('footTotal').textContent    = fmt;
}

// Validasi sebelum submit
document.getElementById('formTambah').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('#tbodyItem .item-row');
    let valid = true;
    rows.forEach(r => {
        if (!r.querySelector('.produk-sel').value) valid = false;
        if (parseInt(r.querySelector('.jml-inp').value) < 1) valid = false;
    });
    if (!valid) {
        e.preventDefault();
        alert('⚠️ Pastikan semua baris barang sudah dipilih dan jumlah minimal 1!');
    }
});

// Load 1 baris kosong saat awal
window.addEventListener('load', () => tambahBaris());
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
