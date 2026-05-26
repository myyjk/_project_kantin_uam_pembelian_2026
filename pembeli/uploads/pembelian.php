<?php
if (!isset($conn)) require_once __DIR__ . '/../config/config.php';

// ── Ambil admin_id dari semua kemungkinan key session ──
$admin_id = 0;
$possible_keys = ['id','id_admin','id_petugas','id_kasir','id_user','user_id','userid'];
foreach ($possible_keys as $key) {
    if (!empty($_SESSION['currentUser'][$key])) {
        $admin_id = (int)$_SESSION['currentUser'][$key];
        break;
    }
}

// ── Fallback: cari id dari tabel users/admin berdasarkan nama session ──
if ($admin_id === 0) {
    $nama_session = $_SESSION['currentUser']['nama'] ?? $_SESSION['currentUser']['username'] ?? $_SESSION['currentUser']['name'] ?? '';
    if ($nama_session !== '') {
        $ns = mysqli_real_escape_string($conn, $nama_session);
        // coba tabel users
        $r = mysqli_query($conn, "SELECT id FROM users WHERE nama='$ns' OR username='$ns' LIMIT 1");
        if ($r && mysqli_num_rows($r) > 0) {
            $admin_id = (int)mysqli_fetch_assoc($r)['id'];
        }
        // coba tabel admin
        if ($admin_id === 0) {
            $r2 = mysqli_query($conn, "SELECT id_admin FROM admin WHERE nama='$ns' OR username='$ns' LIMIT 1");
            if ($r2 && mysqli_num_rows($r2) > 0) {
                $admin_id = (int)mysqli_fetch_assoc($r2)['id_admin'];
            }
        }
    }
}

// ── Fallback terakhir: ambil id pertama dari tabel users ──
if ($admin_id === 0) {
    $r3 = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
    if ($r3 && mysqli_num_rows($r3) > 0) $admin_id = (int)mysqli_fetch_assoc($r3)['id'];
}
if ($admin_id === 0) {
    $r4 = mysqli_query($conn, "SELECT id_admin FROM admin LIMIT 1");
    if ($r4 && mysqli_num_rows($r4) > 0) $admin_id = (int)mysqli_fetch_assoc($r4)['id_admin'];
}

$admin_nama = $_SESSION['currentUser']['nama'] ?? 'Petugas';

// ==========================================
// PROSES CHECKOUT (AJAX POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {

    // Auto-fix foreign key
    mysqli_query($conn, "ALTER TABLE detail_beli DROP FOREIGN KEY fk_detail_produk");
    mysqli_query($conn, "ALTER TABLE detail_beli ADD CONSTRAINT fk_detail_barang_baru FOREIGN KEY (id_produk) REFERENCES barang(id) ON UPDATE CASCADE ON DELETE RESTRICT");

    $id_vendor    = mysqli_real_escape_string($conn, $_POST['id_vendor']);
    $metode_pilih = mysqli_real_escape_string($conn, $_POST['metode_pilih'] ?? 'Tunai');
    $total_bayar  = floatval($_POST['total']   ?? 0);
    $dibayar      = floatval($_POST['dibayar'] ?? 0);

    // Generate no_faktur
    if ($metode_pilih === 'Hutang') {
        $no_faktur = mysqli_real_escape_string($conn, $_POST['no_faktur'] ?? '');
        if (empty($no_faktur)) {
            echo json_encode(['status'=>'gagal','pesan'=>'Nomor Faktur wajib diisi untuk metode Hutang!']);
            exit();
        }
    } else {
        $tahun   = date('Y');
        $res_seq = mysqli_query($conn, "SELECT COUNT(*)+1 AS seq FROM pembelian WHERE YEAR(tanggal_beli)='$tahun'");
        $seq     = $res_seq ? ((int)(mysqli_fetch_assoc($res_seq)['seq'] ?? 1)) : 1;
        $no_faktur = 'INV-' . $tahun . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    $tanggal_beli = date('Y-m-d H:i:s');

    // INSERT pembelian
    $q = "INSERT INTO pembelian (no_faktur, tanggal_beli, id_admin, id_vendor, metode)
          VALUES ('$no_faktur','$tanggal_beli','$admin_id','$id_vendor','$metode_pilih')";

    if (!mysqli_query($conn, $q)) {
        echo json_encode(['status'=>'gagal','pesan'=>'Gagal simpan pembelian: '.mysqli_error($conn)]);
        exit();
    }

    $id_beli_baru = mysqli_insert_id($conn);
    $items = json_decode($_POST['items'], true);

    if (is_array($items)) {
        foreach ($items as $item) {
            $id_produk = (int)$item['id'];
            $qty       = (int)$item['qty'];
            $harga     = floatval($item['price']);

            $qd = "INSERT INTO detail_beli (id_beli, id_produk, jumlah, harga)
                   VALUES ('$id_beli_baru','$id_produk','$qty','$harga')";
            if (!mysqli_query($conn, $qd)) {
                echo json_encode(['status'=>'gagal','pesan'=>'Gagal simpan detail: '.mysqli_error($conn)]);
                exit();
            }

            mysqli_query($conn, "UPDATE barang SET stok = stok - $qty WHERE id = $id_produk");
        }
    }

    // ══════════════════════════════════════════════════════
    // OTOMATIS MASUK KE TRANSAKSI
    // ══════════════════════════════════════════════════════
    {
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `transaksi` (
            `id_transaksi`      INT(11) NOT NULL AUTO_INCREMENT,
            `no_faktur`         VARCHAR(30) NOT NULL,
            `id_beli`           INT(11) NOT NULL,
            `tanggal`           DATE NOT NULL,
            `metode_pembayaran` VARCHAR(20) NOT NULL DEFAULT 'tunai',
            `total_harga`       DECIMAL(15,2) NOT NULL DEFAULT 0,
            `status`            VARCHAR(20) NOT NULL DEFAULT 'lunas',
            `keterangan`        TEXT,
            `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_transaksi`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `detail_transaksi` (
            `id_detail`    INT(11) NOT NULL AUTO_INCREMENT,
            `id_transaksi` INT(11) NOT NULL,
            `id_produk`    INT(11) NOT NULL,
            `jumlah`       INT(11) NOT NULL DEFAULT 1,
            `harga`        DECIMAL(15,2) NOT NULL DEFAULT 0,
            `subtotal`     DECIMAL(15,2) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id_detail`),
            FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi`(`id_transaksi`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $tanggal_trx = date('Y-m-d');
        $status_trx  = ($metode_pilih === 'Tunai') ? 'lunas' : 'pending';

        $qt = "INSERT INTO transaksi (no_faktur, id_beli, tanggal, metode_pembayaran, total_harga, status)
               VALUES ('$no_faktur','$id_beli_baru','$tanggal_trx','$metode_pilih','$total_bayar','$status_trx')";

        if (mysqli_query($conn, $qt)) {
            $id_trx_baru = mysqli_insert_id($conn);
            if (is_array($items)) {
                foreach ($items as $item) {
                    $id_produk = (int)$item['id'];
                    $qty       = (int)$item['qty'];
                    $harga     = floatval($item['price']);
                    $subtotal  = $qty * $harga;
                    mysqli_query($conn, "INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, harga, subtotal)
                                        VALUES ('$id_trx_baru','$id_produk','$qty','$harga','$subtotal')");
                }
            }
        }
    }

    $stok_res  = mysqli_query($conn, "SELECT id, stok FROM barang");
    $stok_baru = mysqli_fetch_all($stok_res, MYSQLI_ASSOC);

    echo json_encode(['status'=>'sukses', 'no_faktur'=>$no_faktur, 'stok_baru'=>$stok_baru]);
    exit();
}

// Load data awal
$barang_db = ($r = mysqli_query($conn,"SELECT * FROM barang"))  ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
$vendor_db = ($r = mysqli_query($conn,"SELECT * FROM vendor"))  ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin UAM - Kasir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--uam-green:#198754;--uam-orange:#fd7e14;--uam-white:#fff;--bg-body:#f8f9fa}
        html,body{margin:0;padding:0;height:100vh;width:100vw;background:var(--bg-body);font-family:'Plus Jakarta Sans',sans-serif;overflow:hidden}
        .wrapper-utama{display:flex;height:100vh;width:100vw;overflow:hidden}
        .area-konten-kanan{flex:1;display:flex;flex-direction:column;height:100vh;overflow:hidden}
        .halaman-kasir{flex:1;display:flex;overflow:hidden}
        .main-content{flex:1;overflow-y:auto;padding:25px}
        .card-item{background:#fff;border:1px solid #eee;border-radius:12px;transition:.2s ease;cursor:pointer;display:flex;flex-direction:column;height:100%}
        .card-item:hover{transform:translateY(-3px);box-shadow:0 6px 12px rgba(0,0,0,.06);border-color:var(--uam-orange)}
        .img-box{width:100%;height:140px;background:#fdfdfd;display:flex;align-items:center;justify-content:center;border-bottom:1px solid #f5f5f5;border-radius:10px 10px 0 0;overflow:hidden;position:relative}
        .img-box img{width:100%;height:100%;object-fit:contain}
        .btn-tambah{background:var(--uam-green);color:#fff;border:none;border-radius:6px;width:100%;padding:6px;font-weight:600;font-size:.8rem;transition:.2s}
        .btn-tambah:hover{background:#146c43;color:#fff}
        .sidebar-nota{width:380px;background:#fff;border-left:1px solid #dee2e6;display:flex;flex-direction:column;height:100%}
        .nota-header{padding:20px;background:var(--uam-orange);color:#fff}
        .nota-list{flex-grow:1;overflow-y:auto;padding:20px}
        .checkout-box{padding:20px;background:#fff9f5;border-top:2px solid var(--uam-orange)}
        .hidden{display:none!important}
        .qty-control .btn{padding:2px 8px;font-size:.75rem}
        #toastSukses{
            display:none;position:fixed;bottom:24px;right:24px;z-index:9999;
            background:#16a34a;color:#fff;padding:14px 22px;border-radius:14px;
            font-weight:600;font-size:.9rem;box-shadow:0 8px 24px rgba(0,0,0,.2);
            align-items:center;gap:10px;animation:slideUp .35s ease;
        }
        @keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    </style>
</head>
<body>
<div class="wrapper-utama">
    <?php include __DIR__ . '/../assets/sidebar.php'; ?>
    <div class="area-konten-kanan">
        <?php include __DIR__ . '/../assets/navbar.php'; ?>
        <div class="halaman-kasir">

            <!-- PRODUK -->
            <div class="main-content">
                <div class="mb-4">
                    <div class="input-group shadow-sm rounded-pill overflow-hidden" style="border:1px solid #dee2e6">
                        <span class="input-group-text border-0 bg-white ps-4"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchBox" class="form-control border-0 p-3" placeholder="Cari menu kantin..." onkeyup="render()">
                    </div>
                </div>
                <div class="row g-3" id="productList"></div>
            </div>

            <!-- KERANJANG -->
            <div class="sidebar-nota shadow-sm">
                <div class="nota-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="fas fa-shopping-cart me-2"></i>Keranjang Belanja</h6>
                    <button class="btn btn-sm btn-light py-0 text-danger fw-bold" onclick="clearCart()" style="font-size:.7rem">Reset</button>
                </div>
                <div class="nota-list" id="cartItems"></div>
                <div class="checkout-box">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Subtotal</span>
                        <span class="fw-bold text-dark" id="subtotalTxt">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">Total Tagihan</span>
                        <h4 class="fw-bold text-success mb-0" id="totalHarga">Rp 0</h4>
                    </div>
                    <div class="p-3 bg-white rounded-3 border mb-3">
                        <label class="small fw-bold text-muted mb-1">Vendor & Pembayaran</label>
                        <select id="id_vendor" class="form-select form-select-sm border-0 bg-light mb-2 p-2">
                            <option value="0">-- Pilih Vendor --</option>
                            <?php foreach($vendor_db as $v): ?>
                            <option value="<?= $v['id_vendor'] ?? $v['id'] ?? 0 ?>">
                                <?= htmlspecialchars($v['nama_vendor'] ?? $v['nama'] ?? 'Vendor') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="methodSelect" class="form-select form-select-sm border-0 bg-light p-2" onchange="toggleHutang()">
                            <option value="Tunai">Tunai / Cash</option>
                            <option value="Hutang">Hutang (Kredit)</option>
                        </select>
                        <div id="hutangFields" class="hidden mt-2 border-top pt-2">
                            <label class="small fw-bold text-dark mb-1">No Faktur <span class="text-danger">*</span></label>
                            <input type="text" id="no_faktur" class="form-control form-control-sm mb-2" placeholder="No Faktur manual...">
                            <label class="small fw-bold text-danger">Nominal DP / Dibayar Awal</label>
                            <input type="number" id="jumlah_dibayar" class="form-control form-control-sm mb-2" placeholder="Rp 0">
                        </div>
                    </div>
                    <button class="btn btn-success w-100 py-3 fw-bold rounded-3 shadow-sm" id="btnCheckout" onclick="checkout()">
                        <i class="fas fa-check-circle me-2"></i>SELESAIKAN PESANAN
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="toastSukses">
    <i class="fas fa-check-circle fa-lg"></i>
    <span id="toastMsg">Transaksi berhasil disimpan!</span>
    <a id="toastLink" href="#" style="color:#bbf7d0;margin-left:8px;font-size:.82rem;text-decoration:underline"></a>
</div>

<script>
let productsData = <?= json_encode($barang_db) ?>;
let cart = [];

function render() {
    const list  = document.getElementById('productList');
    const query = document.getElementById('searchBox').value.toLowerCase();
    const filtered = productsData.filter(p => p.nama && p.nama.toLowerCase().includes(query));
    list.innerHTML = '';
    filtered.forEach(p => {
        const harga      = parseInt(p.harga_jual) || 0;
        const stokReal   = p.stok !== null ? parseInt(p.stok) : 0;
        const displayStok= stokReal < 0 ? 0 : stokReal;
        const gambar     = (p.foto && p.foto.trim() !== '')
            ? `<img src="uploads/${p.foto}">`
            : `<i class="fas fa-hamburger fa-2x text-secondary opacity-25"></i>`;
        list.innerHTML += `
        <div class="col-xl-4 col-md-4 col-6">
          <div class="card-item p-2 shadow-sm" onclick="addToCart(${p.id})">
            <div class="img-box mb-2">${gambar}
              <span class="badge ${displayStok===0?'bg-danger':'bg-dark'} opacity-75"
                    style="position:absolute;bottom:5px;right:5px;font-size:.65rem;">Stok: ${displayStok}</span>
            </div>
            <div class="px-1 flex-grow-1">
              <p class="small fw-bold mb-1 text-truncate text-dark" style="font-size:.85rem;">${p.nama}</p>
              <p class="small text-success fw-bold mb-2" style="font-size:.8rem;">Rp ${harga.toLocaleString()}</p>
            </div>
            <button class="btn-tambah mt-auto" onclick="event.stopPropagation();addToCart(${p.id})">
              <i class="fas fa-plus-circle"></i> TAMBAH
            </button>
          </div>
        </div>`;
    });
}

function addToCart(id) {
    const produk = productsData.find(p => p.id == id);
    if (!produk) return;
    produk.stok--;
    let item = cart.find(x => x.id == id);
    if (item) { item.qty++; }
    else { cart.push({id:parseInt(produk.id),name:produk.nama,price:parseInt(produk.harga_jual)||0,qty:1}); }
    render(); renderCart();
}

function ubahQtyKeranjang(id, aksi) {
    const pd = productsData.find(p => p.id == id);
    let item  = cart.find(x => x.id == id);
    if (!item) return;
    if (aksi==='tambah') { if(pd) pd.stok--; item.qty++; }
    else { if(pd) pd.stok++; item.qty--; if(item.qty<=0) cart=cart.filter(x=>x.id!=id); }
    render(); renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    container.innerHTML = cart.length ? '' : '<div class="text-center mt-5"><span class="text-muted small">Keranjang masih kosong</span></div>';
    let total = 0;
    cart.forEach(item => {
        total += item.price * item.qty;
        container.innerHTML += `
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
          <div>
            <span class="d-block fw-bold small text-dark">${item.name}</span>
            <span class="text-muted small">Rp ${item.price.toLocaleString()}</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div class="btn-group btn-group-sm qty-control">
              <button class="btn btn-outline-secondary py-0 px-2" onclick="ubahQtyKeranjang(${item.id},'kurang')">-</button>
              <span class="btn btn-light disabled py-0 px-2 text-dark fw-bold">${item.qty}</span>
              <button class="btn btn-outline-secondary py-0 px-2" onclick="ubahQtyKeranjang(${item.id},'tambah')">+</button>
            </div>
            <span class="fw-bold text-success small ms-2">Rp ${(item.price*item.qty).toLocaleString()}</span>
          </div>
        </div>`;
    });
    document.getElementById('subtotalTxt').innerText = 'Rp ' + total.toLocaleString();
    document.getElementById('totalHarga').innerText  = 'Rp ' + total.toLocaleString();
    return total;
}

function clearCart() {
    productsData = <?= json_encode($barang_db) ?>;
    cart = []; render(); renderCart();
}

function toggleHutang() {
    const method = document.getElementById('methodSelect').value;
    const fields = document.getElementById('hutangFields');
    if (method==='Hutang') {
        fields.classList.remove('hidden');
        document.getElementById('no_faktur').setAttribute('required','required');
    } else {
        fields.classList.add('hidden');
        document.getElementById('no_faktur').removeAttribute('required');
        document.getElementById('no_faktur').value = '';
        document.getElementById('jumlah_dibayar').value = '';
    }
}

function tampilToast(pesan, link='', linkTeks='') {
    const t = document.getElementById('toastSukses');
    document.getElementById('toastMsg').textContent = pesan;
    const a = document.getElementById('toastLink');
    if (link) { a.href = link; a.textContent = linkTeks; }
    else { a.textContent = ''; }
    t.style.display = 'flex';
    setTimeout(() => { t.style.display='none'; }, 5000);
}

function checkout() {
    const metode   = document.getElementById('methodSelect').value;
    const noFaktur = document.getElementById('no_faktur').value.trim();
    if (metode==='Hutang' && !noFaktur) return alert('⚠️ Nomor Faktur wajib diisi jika memilih Hutang!');
    if (cart.length===0) return alert('⚠️ Keranjang masih kosong!');
    if (document.getElementById('id_vendor').value==0) return alert('⚠️ Pilih vendor terlebih dahulu!');

    const btn = document.getElementById('btnCheckout');
    btn.disabled = true;
    btn.innerHTML = "<i class='fas fa-spinner fa-spin me-2'></i>Menyimpan Pesanan...";

    const totalTagihan = renderCart();
    const formData = new FormData();
    formData.append('action',       'checkout');
    formData.append('metode_pilih', metode);
    formData.append('no_faktur',    noFaktur);
    formData.append('id_vendor',    document.getElementById('id_vendor').value);
    formData.append('total',        totalTagihan);
    formData.append('items',        JSON.stringify(cart));
    formData.append('dibayar',      metode==='Hutang' ? (document.getElementById('jumlah_dibayar').value||0) : totalTagihan);

    fetch((() => {
        const p = window.location.pathname;
        return p.endsWith('index.php') || p.endsWith('/') || p.endsWith('_project_26/')
               ? 'pembeli/pembelian.php'
               : 'pembelian.php';
    })(), {method:'POST', body:formData})
    .then(r => r.json())
    .then(res => {
        if (res.status==='sukses') {
            res.stok_baru.forEach(db => {
                let lk = productsData.find(p => p.id==db.id);
                if (lk) lk.stok = db.stok;
            });
            cart = [];
            document.getElementById('id_vendor').value    = '0';
            document.getElementById('methodSelect').value = 'Tunai';
            toggleHutang(); render(); renderCart();

            const labelMetode = metode === 'Tunai' ? '💵 Tunai' : '📋 Hutang';
            tampilToast(
                '✅ ' + labelMetode + ' - ' + res.no_faktur + ' berhasil disimpan!',
                '?page=transaksi',
                '→ Lihat Transaksi'
            );
        } else {
            alert('❌ Gagal Menyimpan: ' + res.pesan);
        }
    })
    .catch(() => alert('❌ Terjadi kesalahan respon dari server.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = "<i class='fas fa-check-circle me-2'></i>SELESAIKAN PESANAN";
    });
}
render();
</script>
</body>
</html>
