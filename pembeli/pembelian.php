<?php
session_start();
if (!isset($conn)) require_once __DIR__ . '/../config/config.php';

$admin_id = 0;
$possible_keys = ['id','id_admin','id_petugas','id_kasir','id_user','user_id','userid'];
foreach ($possible_keys as $key) {
    if (!empty($_SESSION['currentUser'][$key])) { $admin_id = (int)$_SESSION['currentUser'][$key]; break; }
}
if ($admin_id === 0) {
    $nama_session = $_SESSION['currentUser']['nama'] ?? $_SESSION['currentUser']['username'] ?? $_SESSION['currentUser']['name'] ?? '';
    if ($nama_session !== '') {
        $ns = mysqli_real_escape_string($conn, $nama_session);
        $r = mysqli_query($conn, "SELECT id FROM users WHERE nama='$ns' OR username='$ns' LIMIT 1");
        if ($r && mysqli_num_rows($r) > 0) $admin_id = (int)mysqli_fetch_assoc($r)['id'];
        if ($admin_id === 0) {
            $r2 = mysqli_query($conn, "SELECT id_admin FROM admin WHERE nama='$ns' OR username='$ns' LIMIT 1");
            if ($r2 && mysqli_num_rows($r2) > 0) $admin_id = (int)mysqli_fetch_assoc($r2)['id_admin'];
        }
    }
}
if ($admin_id === 0) { $r3 = mysqli_query($conn, "SELECT id FROM users LIMIT 1"); if ($r3 && mysqli_num_rows($r3) > 0) $admin_id = (int)mysqli_fetch_assoc($r3)['id']; }
if ($admin_id === 0) { $r4 = mysqli_query($conn, "SELECT id_admin FROM admin LIMIT 1"); if ($r4 && mysqli_num_rows($r4) > 0) $admin_id = (int)mysqli_fetch_assoc($r4)['id_admin']; }
$admin_nama = $_SESSION['currentUser']['nama'] ?? 'Petugas';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    mysqli_query($conn, "ALTER TABLE detail_beli DROP FOREIGN KEY fk_detail_produk");
    mysqli_query($conn, "ALTER TABLE detail_beli ADD CONSTRAINT fk_detail_barang_baru FOREIGN KEY (id_produk) REFERENCES barang(id) ON UPDATE CASCADE ON DELETE RESTRICT");
    $id_vendor    = mysqli_real_escape_string($conn, $_POST['id_vendor']);
    $metode_pilih = mysqli_real_escape_string($conn, $_POST['metode_pilih'] ?? 'Tunai');
    $total_bayar  = floatval($_POST['total'] ?? 0);
    $dibayar      = floatval($_POST['dibayar'] ?? 0);
    if ($metode_pilih === 'Hutang') {
        $no_faktur = mysqli_real_escape_string($conn, $_POST['no_faktur'] ?? '');
        if (empty($no_faktur)) { echo json_encode(['status'=>'gagal','pesan'=>'Nomor Faktur wajib diisi!']); exit(); }
    } else {
        $tahun = date('Y');
        $res_seq = mysqli_query($conn, "SELECT COUNT(*)+1 AS seq FROM pembelian WHERE YEAR(tanggal_beli)='$tahun'");
        $seq = $res_seq ? ((int)(mysqli_fetch_assoc($res_seq)['seq'] ?? 1)) : 1;
        $no_faktur = 'INV-' . $tahun . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
    $tanggal_beli = date('Y-m-d H:i:s');
    $q = "INSERT INTO pembelian (no_faktur, tanggal_beli, id_admin, id_vendor, metode) VALUES ('$no_faktur','$tanggal_beli','$admin_id','$id_vendor','$metode_pilih')";
    if (!mysqli_query($conn, $q)) { echo json_encode(['status'=>'gagal','pesan'=>'Gagal simpan: '.mysqli_error($conn)]); exit(); }
    $id_beli_baru = mysqli_insert_id($conn);
    $items = json_decode($_POST['items'], true);
    if (is_array($items)) {
        foreach ($items as $item) {
            $id_produk = (int)$item['id']; $qty = (int)$item['qty']; $harga = floatval($item['price']);
            $qd = "INSERT INTO detail_beli (id_beli, id_produk, jumlah, harga) VALUES ('$id_beli_baru','$id_produk','$qty','$harga')";
            if (!mysqli_query($conn, $qd)) { echo json_encode(['status'=>'gagal','pesan'=>'Gagal detail: '.mysqli_error($conn)]); exit(); }
            mysqli_query($conn, "UPDATE barang SET stok = stok - $qty WHERE id = $id_produk");
        }
    }
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `transaksi` (`id_transaksi` INT(11) NOT NULL AUTO_INCREMENT,`no_faktur` VARCHAR(30) NOT NULL,`id_beli` INT(11) NOT NULL,`tanggal` DATE NOT NULL,`metode_pembayaran` VARCHAR(20) NOT NULL DEFAULT 'tunai',`total_harga` DECIMAL(15,2) NOT NULL DEFAULT 0,`status` VARCHAR(20) NOT NULL DEFAULT 'lunas',`keterangan` TEXT,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY (`id_transaksi`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `detail_transaksi` (`id_detail` INT(11) NOT NULL AUTO_INCREMENT,`id_transaksi` INT(11) NOT NULL,`id_produk` INT(11) NOT NULL,`jumlah` INT(11) NOT NULL DEFAULT 1,`harga` DECIMAL(15,2) NOT NULL DEFAULT 0,`subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,PRIMARY KEY (`id_detail`),FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi`(`id_transaksi`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $tanggal_trx = date('Y-m-d'); $status_trx = ($metode_pilih === 'Tunai') ? 'lunas' : 'pending';
    $qt = "INSERT INTO transaksi (no_faktur, id_beli, tanggal, metode_pembayaran, total_harga, status) VALUES ('$no_faktur','$id_beli_baru','$tanggal_trx','$metode_pilih','$total_bayar','$status_trx')";
    if (mysqli_query($conn, $qt)) {
        $id_trx_baru = mysqli_insert_id($conn);
        if (is_array($items)) {
            foreach ($items as $item) {
                $id_produk=(int)$item['id'];$qty=(int)$item['qty'];$harga=floatval($item['price']);$subtotal=$qty*$harga;
                mysqli_query($conn,"INSERT INTO detail_transaksi (id_transaksi,id_produk,jumlah,harga,subtotal) VALUES ('$id_trx_baru','$id_produk','$qty','$harga','$subtotal')");
            }
        }
    }
    $stok_res = mysqli_query($conn, "SELECT id, stok FROM barang");
    $stok_baru = mysqli_fetch_all($stok_res, MYSQLI_ASSOC);
    echo json_encode(['status'=>'sukses','no_faktur'=>$no_faktur,'stok_baru'=>$stok_baru]);
    exit();
}

$barang_db  = ($r = mysqli_query($conn,"SELECT * FROM barang"))  ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
$vendor_db  = ($r = mysqli_query($conn,"SELECT * FROM vendor"))  ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
$kategori_db= ($r = mysqli_query($conn,"SELECT DISTINCT jenis_menu FROM barang WHERE jenis_menu IS NOT NULL AND jenis_menu!='' ORDER BY jenis_menu")) ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kantin UAM - Kasir</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --green:   #198754;
  --green-d: #146c43;
  --green-l: #e8f5ee;
  --orange:  #fd7e14;
  --orange-d:#e06800;
  --orange-l:#fff4e8;
  --black:   #1c1c1c;
  --gray-1:  #f4f5f7;
  --gray-2:  #e8eaed;
  --gray-3:  #b0b7c3;
  --white:   #ffffff;
  --radius:  12px;
  --shadow:  0 2px 12px rgba(0,0,0,0.07);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100vh;width:100vw;background:var(--gray-1);font-family:'Plus Jakarta Sans',sans-serif;overflow:hidden;color:var(--black)}

/* ── Shell ── */
.wrapper-utama{display:flex;height:100vh;width:100vw;overflow:hidden}
.area-konten-kanan{flex:1;display:flex;flex-direction:column;height:100vh;overflow:hidden;min-width:0}
.halaman-kasir{flex:1;display:flex;overflow:hidden;gap:0}

/* ── Left: Product Area ── */
.main-content{
  flex:1;min-width:0;
  display:flex;flex-direction:column;
  padding:20px 20px 16px 20px;
  overflow:hidden;gap:14px;
}

/* ── Search Bar ── */
.search-wrap{
  display:flex;align-items:center;gap:10px;
  background:var(--white);border:2px solid var(--gray-2);
  border-radius:50px;padding:10px 20px;
  box-shadow:var(--shadow);transition:border-color .2s,box-shadow .2s;
}
.search-wrap:focus-within{border-color:var(--orange);box-shadow:0 0 0 4px rgba(253,126,20,.12)}
.search-wrap i{color:var(--gray-3);font-size:.9rem;flex-shrink:0}
.search-wrap input{border:none;outline:none;background:transparent;font-family:inherit;font-size:.92rem;width:100%;color:var(--black)}
.search-wrap input::placeholder{color:var(--gray-3)}

/* ── Kategori Tabs ── */
.kat-bar{display:flex;gap:8px;overflow-x:auto;padding-bottom:2px;flex-shrink:0}
.kat-bar::-webkit-scrollbar{height:0}
.kat-btn{
  flex-shrink:0;white-space:nowrap;
  padding:8px 18px;border-radius:50px;
  border:2px solid var(--gray-2);
  background:var(--white);color:var(--gray-3);
  font-size:.8rem;font-weight:700;cursor:pointer;
  font-family:inherit;transition:all .18s;
  text-transform:capitalize;
}
.kat-btn:hover{border-color:var(--orange);color:var(--orange);background:var(--orange-l)}
.kat-btn.active{background:var(--orange);border-color:var(--orange);color:var(--white);box-shadow:0 3px 12px rgba(253,126,20,.35)}

/* ── Product Grid: 5 kolom × 2 baris ── */
.product-grid{
  flex:1;
  display:grid;
  grid-template-columns:repeat(5,1fr);
  grid-template-rows:repeat(2,1fr);
  gap:14px;
  overflow:hidden;
  min-height:0;
}

/* ── Card ── */
.card-item{
  background:var(--white);
  border:2px solid var(--gray-2);
  border-radius:var(--radius);
  display:flex;flex-direction:column;
  overflow:hidden;
  cursor:pointer;
  transition:transform .18s,box-shadow .18s,border-color .18s;
  animation:fadeUp .25s ease both;
  position:relative;
}
.card-item:hover{transform:translateY(-4px);box-shadow:0 10px 28px rgba(0,0,0,.1);border-color:var(--orange)}
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

/* Gambar */
.card-img{
  flex:1;min-height:0;
  position:relative;
  background:#fafafa;
  display:flex;align-items:center;justify-content:center;
  overflow:hidden;
  border-bottom:1px solid var(--gray-2);
}
.card-img img{width:100%;height:100%;object-fit:cover;display:block}
.card-img .no-img{font-size:2.2rem;color:var(--gray-2)}

/* badge stok */
.stok-pill{
  position:absolute;top:8px;right:8px;
  font-size:.6rem;font-weight:800;
  padding:3px 9px;border-radius:20px;
  letter-spacing:.3px;pointer-events:none;
}
.stok-pill.ada  {background:#dcfce7;color:#15803d}
.stok-pill.habis{background:#fee2e2;color:#dc2626}

/* Info */
.card-info{padding:10px 12px 8px;flex-shrink:0}
.card-info .nama{
  font-size:.78rem;font-weight:700;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  margin-bottom:2px;color:var(--black);
}
.card-info .harga{font-size:.76rem;font-weight:800;color:var(--green)}

/* Tombol Tambah */
.btn-tambah{
  display:block;width:100%;
  padding:9px 0;
  background:var(--green);color:var(--white);
  border:none;border-radius:0 0 var(--radius) var(--radius);
  font-size:.75rem;font-weight:800;letter-spacing:.5px;
  cursor:pointer;font-family:inherit;
  transition:background .18s;
  flex-shrink:0;
}
.btn-tambah:hover{background:var(--green-d)}
.btn-tambah:active{transform:scale(.98)}

/* ── Pagination Bar ── */
.pg-bar{
  display:flex;align-items:center;justify-content:space-between;
  flex-shrink:0;padding-top:4px;
}
.pg-info{font-size:.78rem;color:var(--gray-3);font-weight:600}
.pg-info b{color:var(--orange)}
.pg-actions{display:flex;align-items:center;gap:10px}
.pg-dots{display:flex;gap:5px;align-items:center}
.pg-dot{
  width:7px;height:7px;border-radius:50%;
  background:var(--gray-2);cursor:pointer;transition:all .2s;
}
.pg-dot.active{background:var(--orange);width:20px;border-radius:4px}
.pg-btn{
  display:flex;align-items:center;gap:6px;
  padding:8px 18px;border-radius:8px;
  border:2px solid var(--gray-2);
  background:var(--white);color:var(--black);
  font-size:.8rem;font-weight:700;cursor:pointer;
  font-family:inherit;transition:all .18s;
}
.pg-btn:hover:not(:disabled){border-color:var(--orange);color:var(--orange)}
.pg-btn:disabled{opacity:.35;cursor:not-allowed}

/* ── Sidebar Keranjang ── */
.sidebar-nota{
  width:350px;flex-shrink:0;
  background:var(--white);
  border-left:2px solid var(--gray-2);
  display:flex;flex-direction:column;
  height:100%;overflow:hidden;
}

.nota-head{
  padding:18px 20px;
  background:linear-gradient(135deg,var(--orange) 0%,var(--orange-d) 100%);
  color:var(--white);
  display:flex;align-items:center;justify-content:space-between;
  flex-shrink:0;
}
.nota-head h6{margin:0;font-weight:800;font-size:.95rem}
.btn-reset{
  background:rgba(255,255,255,.22);border:none;color:var(--white);
  font-size:.72rem;font-weight:700;padding:5px 13px;
  border-radius:20px;cursor:pointer;font-family:inherit;transition:background .2s;
}
.btn-reset:hover{background:rgba(255,255,255,.38)}

.nota-list{flex:1;overflow-y:auto;padding:14px 18px;min-height:0}
.nota-list::-webkit-scrollbar{width:4px}
.nota-list::-webkit-scrollbar-thumb{background:var(--orange-l);border-radius:10px}

.empty-msg{text-align:center;padding:50px 20px;color:var(--gray-3)}
.empty-msg i{font-size:2.5rem;margin-bottom:12px;display:block;opacity:.4}
.empty-msg p{font-size:.82rem;margin:0}

.cart-row{
  display:flex;align-items:center;gap:10px;
  padding:11px 0;border-bottom:1px dashed var(--gray-2);
}
.cart-row:last-child{border-bottom:none}
.cart-name{font-size:.8rem;font-weight:700;line-height:1.3}
.cart-price{font-size:.72rem;color:var(--gray-3);margin-top:1px}
.cart-right{display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0}
.qty-ctrl{display:flex;align-items:center;gap:5px}
.qty-btn{
  width:24px;height:24px;border-radius:6px;
  border:2px solid var(--gray-2);background:var(--white);
  font-size:.85rem;font-weight:700;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:all .15s;line-height:1;color:var(--black);
}
.qty-btn:hover{border-color:var(--orange);color:var(--orange)}
.qty-num{font-size:.82rem;font-weight:800;min-width:18px;text-align:center}
.cart-sub{font-size:.77rem;font-weight:800;color:var(--green)}

/* ── Checkout ── */
.checkout-box{
  padding:16px 18px;
  background:var(--orange-l);
  border-top:2px solid var(--orange);
  flex-shrink:0;
}
.total-line{display:flex;justify-content:space-between;align-items:center;margin-bottom:4px}
.total-line .lbl{font-size:.78rem;color:var(--gray-3)}
.total-line .val{font-size:.82rem;font-weight:700}
.grand{font-size:1.25rem;font-weight:800;color:var(--green)}

.form-box{
  background:var(--white);border-radius:10px;
  border:2px solid var(--gray-2);padding:12px 14px;
  margin:12px 0;display:flex;flex-direction:column;gap:8px;
}
.flbl{font-size:.7rem;font-weight:800;color:var(--gray-3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px}
.fctl{
  width:100%;padding:9px 12px;border-radius:8px;
  border:1.5px solid var(--gray-2);
  font-size:.82rem;font-family:inherit;
  background:var(--gray-1);color:var(--black);
  outline:none;transition:border-color .2s;
}
.fctl:focus{border-color:var(--orange);background:var(--white)}
.hutang-block{border-top:1px dashed var(--gray-2);padding-top:10px;display:flex;flex-direction:column;gap:8px}
.hidden{display:none!important}
.text-red{color:#dc2626}

.btn-checkout{
  width:100%;padding:14px;border:none;border-radius:10px;
  background:linear-gradient(135deg,var(--green) 0%,var(--green-d) 100%);
  color:var(--white);font-size:.88rem;font-weight:800;
  cursor:pointer;font-family:inherit;letter-spacing:.4px;
  box-shadow:0 4px 16px rgba(25,135,84,.28);
  transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;
}
.btn-checkout:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 7px 22px rgba(25,135,84,.38)}
.btn-checkout:disabled{opacity:.7;cursor:not-allowed;transform:none}

/* Toast */
#toast{
  display:none;position:fixed;bottom:24px;right:24px;z-index:9999;
  background:#16a34a;color:#fff;padding:13px 20px;border-radius:12px;
  font-weight:700;font-size:.85rem;box-shadow:0 8px 24px rgba(0,0,0,.2);
  align-items:center;gap:10px;animation:slideUp .3s ease;
}
@keyframes slideUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>
<body>
<div class="wrapper-utama">
  <?php include __DIR__ . '/../assets/sidebar.php'; ?>
  <div class="area-konten-kanan">
    <?php include __DIR__ . '/../assets/navbar.php'; ?>
    <div class="halaman-kasir">

      <!-- ══ AREA PRODUK ══ -->
      <div class="main-content">

        <!-- Search -->
        <div class="search-wrap">
          <i class="fas fa-search"></i>
          <input type="text" id="searchBox" placeholder="Cari produk kantin..." oninput="onSearch()">
        </div>

        <!-- Kategori -->
        <div class="kat-bar" id="katBar">
          <button class="kat-btn active" onclick="setKat('semua',this)">
            <i class="fas fa-border-all"></i> Semua
          </button>
          <?php foreach($kategori_db as $k):
            $jm   = htmlspecialchars($k['jenis_menu']);
            $jml  = strtolower($k['jenis_menu']);
            $ico  = 'fa-utensils';
            if(str_contains($jml,'minum')||str_contains($jml,'drink')) $ico='fa-mug-hot';
            elseif(str_contains($jml,'snack')||str_contains($jml,'cemil')) $ico='fa-cookie-bite';
            elseif(str_contains($jml,'deser')||str_contains($jml,'kue')) $ico='fa-cake-candles';
          ?>
          <button class="kat-btn" onclick="setKat('<?= $jm ?>',this)">
            <i class="fas <?= $ico ?>"></i> <?= ucfirst($jm) ?>
          </button>
          <?php endforeach; ?>
        </div>

        <!-- Grid Produk -->
        <div class="product-grid" id="productGrid"></div>

        <!-- Pagination -->
        <div class="pg-bar">
          <div class="pg-info">
            Tampil <b id="pgFrom">0</b>–<b id="pgTo">0</b> dari <b id="pgAll">0</b> produk
          </div>
          <div class="pg-actions">
            <div class="pg-dots" id="pgDots"></div>
            <button class="pg-btn" id="btnPrev" onclick="goPage(-1)" disabled>
              <i class="fas fa-chevron-left"></i> Prev
            </button>
            <button class="pg-btn" id="btnNext" onclick="goPage(1)">
              Next <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- ══ KERANJANG ══ -->
      <div class="sidebar-nota">
        <div class="nota-head">
          <h6><i class="fas fa-basket-shopping me-2"></i>Keranjang Belanja</h6>
          <button class="btn-reset" onclick="clearCart()"><i class="fas fa-rotate-left me-1"></i>Reset</button>
        </div>

        <div class="nota-list" id="cartList">
          <div class="empty-msg"><i class="fas fa-basket-shopping"></i><p>Keranjang masih kosong</p></div>
        </div>

        <div class="checkout-box">
          <div class="total-line"><span class="lbl">Subtotal</span><span class="val" id="subtotal">Rp 0</span></div>
          <div class="total-line"><span style="font-weight:800">Total Tagihan</span><span class="grand" id="grandTotal">Rp 0</span></div>

          <div class="form-box">
            <div>
              <div class="flbl">Vendor</div>
              <select id="id_vendor" class="fctl">
                <option value="0">-- Pilih Vendor --</option>
                <?php foreach($vendor_db as $v): ?>
                <option value="<?= $v['id_vendor'] ?? $v['id'] ?? 0 ?>">
                  <?= htmlspecialchars($v['nama_vendor'] ?? $v['nama'] ?? 'Vendor') ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <div class="flbl">Metode Bayar</div>
              <select id="methodSelect" class="fctl" onchange="toggleHutang()">
                <option value="Tunai">💵 Tunai / Cash</option>
                <option value="Hutang">📋 Hutang (Kredit)</option>
              </select>
            </div>
            <div id="hutangFields" class="hutang-block hidden">
              <div>
                <div class="flbl">No Faktur <span class="text-red">*</span></div>
                <input type="text" id="no_faktur" class="fctl" placeholder="Nomor faktur...">
              </div>
              <div>
                <div class="flbl">DP / Dibayar Awal</div>
                <input type="number" id="jumlah_dibayar" class="fctl" placeholder="Rp 0">
              </div>
            </div>
          </div>

          <button class="btn-checkout" id="btnCheckout" onclick="checkout()">
            <i class="fas fa-check-circle"></i> SELESAIKAN PESANAN
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toast">
  <i class="fas fa-check-circle"></i>
  <span id="toastMsg"></span>
  <a id="toastLink" href="#" style="color:#bbf7d0;font-size:.8rem;text-decoration:underline"></a>
</div>

<script>
// ── Data dari PHP ──
let allProducts  = <?= json_encode($barang_db) ?>;
let productsData = allProducts.map(p => ({...p})); // working copy dgn stok live
let cart = [];
let page = 1, currentKat = 'semua', currentQ = '';
const PER_PAGE = 10;

// ── Konversi foto ──
function fotoUrl(f) {
  if (!f || !f.trim()) return null;
  f = f.trim();
  if (f.includes('drive.google.com/file/d/')) {
    const m = f.match(/\/d\/([a-zA-Z0-9_-]+)/);
    if (m) return 'https://drive.google.com/thumbnail?id='+m[1]+'&sz=w300';
  }
  if (f.includes('drive.google.com/open?id=')) {
    const m = f.match(/[?&]id=([a-zA-Z0-9_-]+)/);
    if (m) return 'https://drive.google.com/thumbnail?id='+m[1]+'&sz=w300';
  }
  if (f.startsWith('http://') || f.startsWith('https://')) return f;
  return 'uploads/' + f;
}

// ── Filter ──
function getFiltered() {
  return productsData.filter(p => {
    const q = !currentQ || (p.nama||'').toLowerCase().includes(currentQ);
    const k = currentKat === 'semua' || (p.jenis_menu||'').toLowerCase() === currentKat.toLowerCase();
    return q && k;
  });
}

// ── Render Grid ──
function render() {
  const filtered = getFiltered();
  const total    = filtered.length;
  const pages    = Math.max(1, Math.ceil(total / PER_PAGE));
  if (page > pages) page = pages;

  const start = (page-1)*PER_PAGE;
  const end   = Math.min(start+PER_PAGE, total);
  const slice = filtered.slice(start, end);

  const grid = document.getElementById('productGrid');
  grid.innerHTML = '';

  if (!slice.length) {
    grid.style.placeItems = 'center';
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;color:#ccc;padding:40px 0">
      <i class="fas fa-search" style="font-size:2rem;margin-bottom:10px;display:block"></i>
      <span style="font-size:.85rem">Produk tidak ditemukan</span>
    </div>`;
  } else {
    grid.style.placeItems = '';
    slice.forEach((p, i) => {
      const harga = parseInt(p.harga_jual)||0;
      const stok  = parseInt(p.stok)||0;
      const url   = fotoUrl(p.foto);

      const card = document.createElement('div');
      card.className = 'card-item';
      card.dataset.pid = p.id;
      card.style.animationDelay = (i*0.03)+'s';

      card.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-tambah')) return;
        addToCart(p.id);
      });

      card.innerHTML = `
        <div class="card-img">
          ${url
            ? `<img src="${url}" alt="${p.nama}"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
            : ''}
          <span class="no-img" style="${url?'display:none':''}">
            <i class="fas fa-hamburger"></i>
          </span>
          <span class="stok-pill ${stok<=0?'habis':'ada'}" data-stok-pill="${p.id}">
            Stok: ${stok < 0 ? 0 : stok}
          </span>
        </div>
        <div class="card-info">
          <div class="nama" title="${p.nama}">${p.nama}</div>
          <div class="harga">Rp ${harga.toLocaleString('id-ID')}</div>
        </div>
        <button class="btn-tambah" onclick="event.stopPropagation();addToCart(${p.id})">
          <i class="fas fa-plus"></i> TAMBAH
        </button>`;

      grid.appendChild(card);
    });
  }

  // Pagination info
  document.getElementById('pgFrom').textContent = total ? start+1 : 0;
  document.getElementById('pgTo').textContent   = end;
  document.getElementById('pgAll').textContent  = total;
  document.getElementById('btnPrev').disabled   = page <= 1;
  document.getElementById('btnNext').disabled   = page >= pages;

  // Dots
  const dotsEl = document.getElementById('pgDots');
  dotsEl.innerHTML = '';
  const maxD = Math.min(pages, 8);
  for (let i = 1; i <= maxD; i++) {
    const d = document.createElement('div');
    d.className = 'pg-dot' + (i===page?' active':'');
    d.onclick = () => { page = i; render(); };
    dotsEl.appendChild(d);
  }
}

// ── Update badge stok di card tanpa re-render grid ──
function updateStokBadge(id) {
  const p    = productsData.find(x => x.id == id);
  const pill = document.querySelector(`[data-stok-pill="${id}"]`);
  if (!pill || !p) return;
  const stok = parseInt(p.stok) || 0;
  pill.textContent = 'Stok: ' + (stok < 0 ? 0 : stok);
  pill.className   = 'stok-pill ' + (stok <= 0 ? 'habis' : 'ada');
}

function setKat(kat, el) {
  currentKat = kat; page = 1;
  document.querySelectorAll('.kat-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  render();
}
function onSearch() {
  currentQ = document.getElementById('searchBox').value.toLowerCase().trim();
  page = 1; render();
}
function goPage(dir) {
  const pages = Math.max(1, Math.ceil(getFiltered().length/PER_PAGE));
  page = Math.max(1, Math.min(page+dir, pages));
  render();
}

// ── Keranjang ──
function addToCart(id) {
  const p = productsData.find(x => x.id == id);
  if (!p) return;

  // Kurangi stok hanya kalau masih ada
  if (parseInt(p.stok) > 0) p.stok--;

  const item = cart.find(x => x.id == id);
  if (item) { item.qty++; }
  else { cart.push({ id: parseInt(p.id), name: p.nama, price: parseInt(p.harga_jual)||0, qty: 1 }); }

  // Hanya update badge stok di card — TIDAK re-render grid
  updateStokBadge(id);
  renderCart();
}

function changeQty(id, dir) {
  const p    = productsData.find(x => x.id == id);
  const item = cart.find(x => x.id == id);
  if (!item) return;

  if (dir > 0) {
    if (p && parseInt(p.stok) > 0) p.stok--;
    item.qty++;
  } else {
    if (p) p.stok++;
    item.qty--;
    if (item.qty <= 0) cart = cart.filter(x => x.id != id);
  }
  // Hanya update badge stok — TIDAK re-render grid
  updateStokBadge(id);
  renderCart();
}

function renderCart() {
  const el = document.getElementById('cartList');
  if (!cart.length) {
    el.innerHTML = `<div class="empty-msg"><i class="fas fa-basket-shopping"></i><p>Keranjang masih kosong</p></div>`;
    document.getElementById('subtotal').textContent  = 'Rp 0';
    document.getElementById('grandTotal').textContent = 'Rp 0';
    return 0;
  }
  let total = 0;
  el.innerHTML = '';
  cart.forEach(item => {
    total += item.price * item.qty;
    const row = document.createElement('div');
    row.className = 'cart-row';
    row.innerHTML = `
      <div style="flex:1;min-width:0">
        <div class="cart-name">${item.name}</div>
        <div class="cart-price">Rp ${item.price.toLocaleString('id-ID')} / pcs</div>
      </div>
      <div class="cart-right">
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="changeQty(${item.id},-1)">−</button>
          <span class="qty-num">${item.qty}</span>
          <button class="qty-btn" onclick="changeQty(${item.id},1)">+</button>
        </div>
        <div class="cart-sub">Rp ${(item.price*item.qty).toLocaleString('id-ID')}</div>
      </div>`;
    el.appendChild(row);
  });
  document.getElementById('subtotal').textContent  = 'Rp '+total.toLocaleString('id-ID');
  document.getElementById('grandTotal').textContent = 'Rp '+total.toLocaleString('id-ID');
  return total;
}

function clearCart() {
  // Reset stok ke data awal lalu render ulang grid
  productsData = allProducts.map(p => ({...p}));
  cart = []; render(); renderCart();
}

function toggleHutang() {
  const m = document.getElementById('methodSelect').value;
  document.getElementById('hutangFields').classList.toggle('hidden', m !== 'Hutang');
  if (m !== 'Hutang') {
    document.getElementById('no_faktur').value      = '';
    document.getElementById('jumlah_dibayar').value = '';
  }
}

function showToast(msg, link='', linkTxt='') {
  document.getElementById('toastMsg').textContent = msg;
  const a = document.getElementById('toastLink');
  a.href = link||'#'; a.textContent = linkTxt;
  const t = document.getElementById('toast');
  t.style.display = 'flex';
  setTimeout(() => t.style.display='none', 5000);
}

function checkout() {
  const metode   = document.getElementById('methodSelect').value;
  const noFaktur = document.getElementById('no_faktur').value.trim();
  if (metode==='Hutang' && !noFaktur) return alert('⚠️ Nomor Faktur wajib diisi!');
  if (!cart.length) return alert('⚠️ Keranjang masih kosong!');
  if (document.getElementById('id_vendor').value == 0) return alert('⚠️ Pilih vendor terlebih dahulu!');

  const btn = document.getElementById('btnCheckout');
  btn.disabled = true;
  btn.innerHTML = "<i class='fas fa-spinner fa-spin'></i> Menyimpan...";

  const totalTagihan = renderCart();
  const fd = new FormData();
  fd.append('action','checkout');
  fd.append('metode_pilih', metode);
  fd.append('no_faktur',    noFaktur);
  fd.append('id_vendor',    document.getElementById('id_vendor').value);
  fd.append('total',        totalTagihan);
  fd.append('items',        JSON.stringify(cart));
  fd.append('dibayar',      metode==='Hutang'?(document.getElementById('jumlah_dibayar').value||0):totalTagihan);

  const url = (() => {
    const p = window.location.pathname;
    return (p.endsWith('index.php')||p.endsWith('/')||p.endsWith('_project_26/'))
      ? 'pembeli/pembelian.php' : 'pembelian.php';
  })();

  fetch(url, {method:'POST',body:fd})
    .then(r => r.json())
    .then(res => {
      if (res.status === 'sukses') {
        // Sync stok dari server
        res.stok_baru.forEach(db => {
          const lp = productsData.find(p => p.id==db.id);
          const la = allProducts.find(p => p.id==db.id);
          if (lp) lp.stok = db.stok;
          if (la) la.stok = db.stok;
        });
        cart = [];
        document.getElementById('id_vendor').value    = '0';
        document.getElementById('methodSelect').value = 'Tunai';
        toggleHutang(); render(); renderCart();
        showToast('✅ '+(metode==='Tunai'?'💵 Tunai':'📋 Hutang')+' – '+res.no_faktur+' berhasil!','?page=transaksi','→ Lihat Transaksi');
      } else {
        alert('❌ Gagal: ' + res.pesan);
      }
    })
    .catch(() => alert('❌ Kesalahan respon server.'))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = "<i class='fas fa-check-circle'></i> SELESAIKAN PESANAN";
    });
}

// Init
render();
</script>
</body>
</html>
