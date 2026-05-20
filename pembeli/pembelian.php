<?php
session_start();
require '../config/config.php';

// Proteksi Login
if (!isset($_SESSION['isLoggedIn'])) {
    header("Location: ../login/login.php");
    exit();
}

$admin_id = $_SESSION['currentUser']['id_user'] ?? $_SESSION['currentUser']['id_admin'] ?? null;
$admin_nama = $_SESSION['currentUser']['nama'] ?? 'Petugas';

// ==========================================
// LOGIKA MENYIMPAN TRANSAKSI (PROSES POST AJAX)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $id_vendor = mysqli_real_escape_string($conn, $_POST['id_vendor']);
    $metode_pilih = mysqli_real_escape_string($conn, $_POST['metode_pilih'] ?? 'Tunai');
    
    // Kondisional No Faktur
    if ($metode_pilih === 'Hutang') {
        $no_faktur = mysqli_real_escape_string($conn, $_POST['no_faktur'] ?? '');
        if (empty($no_faktur)) {
            echo json_encode(['status' => 'gagal', 'pesan' => 'Nomor Faktur wajib diisi untuk metode Hutang!']);
            exit();
        }
    } else {
        $no_faktur = "INV-" . time() . "-" . rand(100, 999);
    }
    
    $tanggal_beli = date('Y-m-d H:i:s');
    $total_bayar = floatval($_POST['total'] ?? 0);
    $jumlah_dibayar = floatval($_POST['dibayar'] ?? 0);

    // SQL diubah agar aman dari error 'Unknown column foto_nota'
    $query_save = "INSERT INTO pembelian (no_faktur, tanggal_beli, id_admin, id_vendor, metode) 
                   VALUES ('$no_faktur', '$tanggal_beli', '$admin_id', '$id_vendor', '$jumlah_dibayar')";
    
    if (mysqli_query($conn, $query_save)) {
        $id_beli_terbaru = mysqli_insert_id($conn);
        
        $items = json_decode($_POST['items'], true);
        if (is_array($items)) {
            foreach ($items as $item) {
                $id_produk = mysqli_real_escape_string($conn, $item['id']);
                $qty = intval($item['qty']);
                $harga = floatval($item['price']);

                // Input ke tabel detail_beli sesuai struktur database Anda
                mysqli_query($conn, "INSERT INTO detail_beli (id_beli, id, jumlah, harga) 
                                     VALUES ('$id_beli_terbaru', '$id_produk', '$qty', '$harga')");

                // Update pengurangan/penyesuaian stok barang di database
                mysqli_query($conn, "UPDATE barang SET stok = stok - $qty WHERE id = '$id_produk'");
            }
        }
        
        // Ambil data stok barang terbaru dari database untuk dikembalikan ke tampilan browser via AJAX
        $query_stok_baru = mysqli_query($conn, "SELECT id, stok FROM barang");
        $stok_terupdate = mysqli_fetch_all($query_stok_baru, MYSQLI_ASSOC);
        
        echo json_encode([
            'status' => 'sukses',
            'stok_baru' => $stok_terupdate
        ]);
    } else {
        echo json_encode([
            'status' => 'gagal',
            'pesan' => mysqli_error($conn)
        ]);
    }
    exit();
}

// Ambil Data barang & Vendor untuk Load Pertama
$query_barang = mysqli_query($conn, "SELECT * FROM barang");
$barang_db = ($query_barang) ? mysqli_fetch_all($query_barang, MYSQLI_ASSOC) : [];

$query_vendor = mysqli_query($conn, "SELECT * FROM vendor");
$vendor_db = ($query_vendor) ? mysqli_fetch_all($query_vendor, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin UAM - Kasir Modern</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --uam-green: #198754; --uam-orange: #fd7e14; --uam-white: #ffffff; --bg-body: #f8f9fa; }
        html, body { margin: 0; padding: 0; height: 100vh; width: 100vw; background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; overflow: hidden; }
        .wrapper-utama { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
        .area-konten-kanan { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .halaman-kasir { flex: 1; display: flex; overflow: hidden; }
        .main-content { flex: 1; overflow-y: auto; padding: 25px; }
        .card-item { background: var(--uam-white); border: 1px solid #eee; border-radius: 12px; transition: all 0.2s ease; cursor: pointer; display: flex; flex-direction: column; height: 100%; }
        .card-item:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.06); border-color: var(--uam-orange); }
        .img-box { width: 100%; height: 140px; background: #fdfdfd; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #f5f5f5; border-radius: 10px 10px 0 0; overflow: hidden; position: relative; }
        .img-box img { width: 100%; height: 100%; object-fit: contain; }
        .btn-tambah { background-color: var(--uam-green); color: white; border: none; border-radius: 6px; width: 100%; padding: 6px; font-weight: 600; font-size: 0.8rem; transition: 0.2s; }
        .btn-tambah:hover { background-color: #146c43; color: white; }
        .sidebar-nota { width: 380px; background: var(--uam-white); border-left: 1px solid #dee2e6; display: flex; flex-direction: column; height: 100%; }
        .nota-header { padding: 20px; background: var(--uam-orange); color: white; }
        .nota-list { flex-grow: 1; overflow-y: auto; padding: 20px; }
        .checkout-box { padding: 20px; background: #fff9f5; border-top: 2px solid var(--uam-orange); }
        .hidden { display: none !important; }
        .qty-control .btn { padding: 2px 8px; font-size: 0.75rem; }
    </style>
</head>
<body>

<div class="wrapper-utama">
    <?php include '../assets/sidebar.php'; ?>

    <div class="area-konten-kanan">
        <?php include '../assets/navbar.php'; ?>

        <div class="halaman-kasir">
            <div class="main-content">
                <div class="mb-4">
                    <div class="input-group shadow-sm rounded-pill overflow-hidden" style="border: 1px solid #dee2e6;">
                        <span class="input-group-text border-0 bg-white ps-4"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchBox" class="form-control border-0 p-3" placeholder="Cari menu kantin..." onkeyup="render()">
                    </div>
                </div>
                <div class="row g-3" id="productList"></div>
            </div>

            <div class="sidebar-nota shadow-sm">
                <div class="nota-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="fas fa-shopping-cart me-2"></i>Keranjang Belanja</h6>
                    <button class="btn btn-sm btn-light py-0 text-danger" onclick="clearCart()" style="font-size: 0.7rem; font-weight: bold;">Reset</button>
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
                                <option value="<?php echo $v['id_vendor'] ?? $v['id'] ?? 0; ?>">
                                    <?php echo $v['nama_vendor'] ?? $v['nama'] ?? 'Vendor Umum'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="methodSelect" class="form-select form-select-sm border-0 bg-light p-2" onchange="toggleHutang()">
                            <option value="Tunai">Tunai / Cash</option>
                            <option value="Hutang">Hutang (Kredit)</option>
                        </select>

                        <div id="hutangFields" class="hidden mt-2 border-top pt-2">
                            <label class="small fw-bold text-dark mb-1">No Faktur / Invoice Vendor <span class="text-danger">*</span></label>
                            <input type="text" id="no_faktur" class="form-control form-control-sm mb-2" placeholder="Masukkan No Faktur manual...">

                            <label class="small fw-bold text-danger">Nominal DP / Dibayar Awal</label>
                            <input type="number" id="jumlah_dibayar" class="form-control form-control-sm mb-2" placeholder="Rp 0">
                        </div>
                    </div>

                    <button class="btn btn-success w-100 py-3 fw-bold rounded-3 shadow-sm" id="btnCheckout" onclick="checkout()">
                        <i class="fas fa-check-circle me-2"></i> SELESAIKAN PESANAN
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let productsData = <?php echo json_encode($barang_db); ?>;
    let cart = [];

    function render() {
        const list = document.getElementById('productList');
        const query = document.getElementById('searchBox').value.toLowerCase();
        const filtered = productsData.filter(p => p.nama && p.nama.toLowerCase().includes(query));
        
        list.innerHTML = '';
        filtered.forEach(p => {
            const hargaJual = parseInt(p.harga_jual) || 0; 
            const coreStok = p.stok !== null ? parseInt(p.stok) : 0; 
            const displayStok = coreStok < 0 ? 0 : coreStok; // Tampilan layar tetap 0 walau stok database kosong/minus

            let gambarHTML = (p.foto && p.foto.trim() !== "") ? `<img src="uploads/${p.foto}">` : `<i class="fas fa-hamburger fa-2x text-secondary opacity-20"></i>`;

            list.innerHTML += `
                <div class="col-xl-4 col-md-4 col-6">
                    <div class="card-item p-2 shadow-sm" onclick="addToCart(${p.id})">
                        <div class="img-box mb-2">${gambarHTML}
                            <span class="badge ${displayStok === 0 ? 'bg-danger' : 'bg-dark'} opacity-75" style="position: absolute; bottom: 5px; right: 5px; font-size: 0.65rem;">
                                Stok: ${displayStok}
                            </span>
                        </div>
                        <div class="px-1 flex-grow-1">
                            <p class="small fw-bold mb-1 text-truncate text-dark" style="font-size: 0.85rem;">${p.nama}</p>
                            <p class="small text-success fw-bold mb-2" style="font-size: 0.8rem;">Rp ${hargaJual.toLocaleString()}</p>
                        </div>
                        <button class="btn-tambah mt-auto" onclick="event.stopPropagation(); addToCart(${p.id})">
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

        let itemDiKeranjang = cart.find(x => x.id == id);
        if (itemDiKeranjang) {
            itemDiKeranjang.qty++; 
        } else {
            cart.push({
                id: parseInt(produk.id), 
                name: produk.nama, 
                price: parseInt(produk.harga_jual) || 0, 
                qty: 1
            });
        }
        render();     
        renderCart(); 
    }

    function ubahQtyKeranjang(id, aksi) {
        const produkData = productsData.find(p => p.id == id);
        let item = cart.find(x => x.id == id);
        if (!item) return;

        if (aksi === 'tambah') {
            if (produkData) produkData.stok--;
            item.qty++;
        } else if (aksi === 'kurang') {
            if (produkData) produkData.stok++;
            item.qty--;
            if (item.qty <= 0) {
                cart = cart.filter(x => x.id != id);
            }
        }
        render();
        renderCart();
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
                            <button class="btn btn-outline-secondary py-0 px-2" onclick="ubahQtyKeranjang(${item.id}, 'kurang')">-</button>
                            <span class="btn btn-light disabled py-0 px-2 text-dark fw-bold">${item.qty}</span>
                            <button class="btn btn-outline-secondary py-0 px-2" onclick="ubahQtyKeranjang(${item.id}, 'tambah')">+</button>
                        </div>
                        <span class="fw-bold text-success small ms-2">Rp ${(item.price * item.qty).toLocaleString()}</span>
                    </div>
                </div>`;
        });
        document.getElementById('subtotalTxt').innerText = 'Rp ' + total.toLocaleString();
        document.getElementById('totalHarga').innerText = 'Rp ' + total.toLocaleString();
        return total;
    }

    function clearCart() {
        productsData = <?php echo json_encode($barang_db); ?>;
        cart = [];
        render();
        renderCart();
    }

    // Mengatur kemunculan input No Faktur & Nominal DP secara kondisional
    function toggleHutang() {
        const method = document.getElementById('methodSelect').value;
        const fields = document.getElementById('hutangFields');
        
        if (method === 'Hutang') {
            fields.classList.remove('hidden');
            document.getElementById('no_faktur').setAttribute('required', 'required');
        } else {
            fields.classList.add('hidden');
            document.getElementById('no_faktur').removeAttribute('required');
            document.getElementById('no_faktur').value = '';
            document.getElementById('jumlah_dibayar').value = '';
        }
    }

    // PROSES UTAMA AJAX CLEAN REFRESH (TANPA RELOAD WEBSITE)
    function checkout() {
        const metode = document.getElementById('methodSelect').value;
        const noFaktur = document.getElementById('no_faktur').value.trim();
        
        if (metode === 'Hutang' && !noFaktur) {
            return alert("⚠️ Nomor Faktur wajib diisi jika memilih metode Hutang!");
        }
        if (cart.length === 0) return alert("⚠️ Keranjang masih kosong!");
        if (document.getElementById('id_vendor').value == 0) return alert("⚠️ Pilih vendor terlebih dahulu!");

        const btn = document.getElementById('btnCheckout');
        btn.disabled = true;
        btn.innerHTML = "<i class='fas fa-spinner fa-spin me-2'></i> Menyimpan Pesanan...";

        const totalTagihan = renderCart();
        const formData = new FormData();
        formData.append('action', 'checkout');
        formData.append('metode_pilih', metode);
        formData.append('no_faktur', noFaktur);
        formData.append('id_vendor', document.getElementById('id_vendor').value);
        formData.append('total', totalTagihan);
        formData.append('items', JSON.stringify(cart));

        if (metode === 'Hutang') {
            formData.append('dibayar', document.getElementById('jumlah_dibayar').value || 0);
        } else {
            formData.append('dibayar', totalTagihan); 
        }

        fetch('pembelian.php', { method: 'POST', body: formData })
        .then(r => r.json()) 
        .then(res => {
            if(res.status === 'sukses') {
                alert("✅ Transaksi Berhasil Disimpan!");
                
                // Singkronisasi data stok lokal dengan database terbaru kiriman AJAX
                res.stok_baru.forEach(dbItem => {
                    let lokalItem = productsData.find(p => p.id == dbItem.id);
                    if (lokalItem) lokalItem.stok = dbItem.stok;
                });

                // Bersihkan Keranjang belanja secara instant tanpa reload web
                cart = [];
                document.getElementById('id_vendor').value = "0";
                document.getElementById('methodSelect').value = "Tunai";
                toggleHutang();
                render();
                renderCart();
            } else {
                alert("❌ Gagal Menyimpan: " + res.pesan);
            }
        })
        .catch(err => {
            alert("❌ Terjadi kesalahan respon dari server.");
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = "<i class='fas fa-check-circle me-2'></i> SELESAIKAN PESANAN";
        });
    }
    render();
</script>
</body>
</html>