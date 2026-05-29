<?php
ob_start();
if(!isset($conn)) require_once __DIR__.'/../config/config.php';

$title = "Data Transaksi";

// Ambil semua transaksi (case-insensitive fix untuk Hutang)
$sql = "SELECT t.*,
               COALESCE(p.nama, CONCAT('Pembeli #', t.id_beli)) AS nama_pembeli
        FROM transaksi t
        LEFT JOIN pembelian p ON t.id_beli = p.id_beli
        WHERE LOWER(t.metode_pembayaran) IN ('tunai','hutang')
        ORDER BY t.created_at DESC, t.id_transaksi DESC";
$result = mysqli_query($conn, $sql);
if (!$result) {
    $result = mysqli_query($conn, "SELECT t.*, CONCAT('Pembeli #',t.id_beli) AS nama_pembeli
                                   FROM transaksi t
                                   WHERE LOWER(t.metode_pembayaran) IN ('tunai','hutang')
                                   ORDER BY t.id_transaksi DESC");
}
$transaksi = [];
if ($result) while ($row = mysqli_fetch_assoc($result)) $transaksi[] = $row;

$total_all     = array_sum(array_column($transaksi,'total_harga'));
$total_trx     = count($transaksi);
$total_lunas   = count(array_filter($transaksi, fn($r)=>$r['status']==='lunas'));
$total_pending = count(array_filter($transaksi, fn($r)=>$r['status']==='pending'));
$total_omzet   = array_sum(array_column(array_filter($transaksi,fn($r)=>$r['status']==='lunas'),'total_harga'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?> | Kantin UAM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --orange:     #fd7e14;
      --orange-dark:#e8650a;
      --orange-soft:#fff3e8;
      --green:      #198754;
      --green-soft: #d1f0e0;
      --black:      #1a1a1a;
      --dark:       #212529;
      --gray:       #6c757d;
      --light-bg:   #f8f9fa;
      --white:      #ffffff;
      --border:     #e9ecef;
    }
    * { box-sizing: border-box; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--light-bg); color: var(--dark); margin: 0; }

    .wrapper-utama { display: flex; min-height: 100vh; }
    .area-kanan    { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    .konten-utama  { flex: 1; padding: 28px; overflow-y: auto; }

    .page-header {
      background: linear-gradient(135deg, var(--black) 0%, #2d2d2d 100%);
      border-radius: 16px;
      padding: 1.5rem 2rem;
      color: #fff;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .page-header h4 { font-weight: 800; font-size: 1.3rem; margin: 0; }
    .page-header small { opacity: .7; font-size: .82rem; }
    .page-header .accent { color: var(--orange); }

    .stat-card {
      border-radius: 14px;
      padding: 1.2rem 1.4rem;
      color: #fff;
      position: relative;
      overflow: hidden;
      border: none;
    }
    .stat-card::after {
      content: '';
      position: absolute;
      right: -15px; top: -15px;
      width: 90px; height: 90px;
      border-radius: 50%;
      background: rgba(255,255,255,.12);
    }
    .stat-card .ico  { font-size: 1.8rem; opacity: .9; }
    .stat-card .val  { font-size: 1.4rem; font-weight: 800; margin: .2rem 0 0; }
    .stat-card .lbl  { font-size: .76rem; opacity: .85; font-weight: 600; }
    .sc-orange { background: linear-gradient(135deg, var(--orange), #ffaa5e); }
    .sc-green  { background: linear-gradient(135deg, var(--green),  #28c76f); }
    .sc-dark   { background: linear-gradient(135deg, #343a40,       #6c757d); }
    .sc-black  { background: linear-gradient(135deg, var(--black),  #3d3d3d); }

    .table-card {
      background: var(--white);
      border-radius: 16px;
      box-shadow: 0 2px 16px rgba(0,0,0,.07);
      overflow: hidden;
      border: none;
    }
    .table-card-header {
      background: var(--white);
      padding: 1rem 1.5rem;
      border-bottom: 2px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: .75rem;
    }
    .table-card-header .title {
      font-weight: 800;
      font-size: .95rem;
      color: var(--black);
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .table-card-header .title .dot {
      width: 10px; height: 10px;
      background: var(--orange);
      border-radius: 50%;
      display: inline-block;
    }

    .table thead th {
      background: var(--black);
      color: #fff;
      font-size: .78rem;
      font-weight: 700;
      letter-spacing: .05em;
      text-transform: uppercase;
      border: none;
      padding: .9rem 1rem;
    }
    .table tbody td {
      padding: .8rem 1rem;
      vertical-align: middle;
      font-size: .87rem;
      border-color: var(--border);
      color: var(--dark);
    }
    .table tbody tr:hover td { background: var(--orange-soft); }

    .faktur-link {
      font-weight: 700;
      color: var(--orange);
      cursor: pointer;
      text-decoration: none;
      transition: .15s;
    }
    .faktur-link:hover { color: var(--orange-dark); text-decoration: underline; }

    .bs { padding: .3em .8em; border-radius: 20px; font-size: .74rem; font-weight: 700; display: inline-flex; align-items: center; gap: .3rem; }
    .bs-lunas   { background: var(--green-soft); color: var(--green); border: 1px solid #a3d9be; }
    .bs-pending { background: #fff3cd; color: #856404; border: 1px solid #ffe08a; }
    .bs-batal   { background: #fde8e8; color: #b91c1c; border: 1px solid #fca5a5; }

    .bm { padding: .3em .75em; border-radius: 20px; font-size: .74rem; font-weight: 600; display: inline-flex; align-items: center; gap: .3rem; }
    .bm-tunai    { background: var(--orange-soft); color: var(--orange-dark); border: 1px solid #ffc89a; }
    .bm-hutang   { background: #fff3cd; color: #856404; border: 1px solid #ffe08a; }
    .bm-transfer { background: var(--green-soft);  color: var(--green);      border: 1px solid #a3d9be; }

    .btn-aksi { width: 30px; height: 30px; padding: 0; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: .78rem; transition: .15s; }

    .search-wrap { position: relative; }
    .search-wrap .fa-search { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: var(--gray); font-size: .8rem; }
    .input-search { border-radius: 10px; border: 1.5px solid var(--border); padding: .5rem 1rem .5rem 2.4rem; font-size: .86rem; width: 250px; font-family: inherit; transition: .2s; }
    .input-search:focus { outline: none; border-color: var(--orange); box-shadow: 0 0 0 3px rgba(253,126,20,.12); }

    .table-footer {
      background: var(--orange-soft);
      border-top: 2px solid #ffc89a;
      padding: .9rem 1.5rem;
      text-align: right;
      font-size: .88rem;
      color: var(--orange-dark);
      font-weight: 700;
    }

    /* MODAL NOTA */
    .modal-nota .modal-content {
      background: #1a1a1a;
      border: none;
      border-radius: 18px;
      box-shadow: 0 30px 70px rgba(0,0,0,.6);
      color: #f1f5f9;
    }
    .modal-nota .modal-header {
      background: linear-gradient(135deg, #000, #2d2d2d);
      border-bottom: 2px solid var(--orange);
      border-radius: 18px 18px 0 0;
      padding: 1.1rem 1.5rem;
    }
    .modal-nota .modal-title { font-weight: 800; font-size: 1rem; color: #fff; }
    .modal-nota .modal-title .accent { color: var(--orange); }
    .modal-nota .btn-close { filter: invert(1); opacity: .6; }

    .nota-inner {
      background: #fff;
      border-radius: 14px;
      color: var(--dark);
      padding: 1.5rem 1.8rem;
      margin: 0 .25rem;
    }
    .nota-inner .nota-brand {
      font-size: 1.15rem;
      font-weight: 800;
      color: var(--black);
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .nota-inner .nota-brand .dot-o {
      width: 28px; height: 28px;
      background: var(--orange);
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: .85rem;
    }
    .nota-divider { border: none; border-top: 2px dashed #e2e8f0; margin: 1rem 0; }

    .tbl-nota { width: 100%; font-size: .83rem; border-collapse: collapse; }
    .tbl-nota thead th {
      background: var(--black);
      color: #fff;
      padding: .55rem .8rem;
      font-size: .73rem;
      text-transform: uppercase;
      letter-spacing: .05em;
      font-weight: 700;
    }
    .tbl-nota thead th:first-child { border-radius: 8px 0 0 0; }
    .tbl-nota thead th:last-child  { border-radius: 0 8px 0 0; }
    .tbl-nota tbody td { padding: .6rem .8rem; border-bottom: 1px solid #f1f5f9; color: var(--dark); }
    .tbl-nota tbody tr:last-child td { border-bottom: none; }
    .tbl-nota tbody tr:hover td { background: var(--orange-soft); }

    .nota-summary { background: #f8f9fa; border-radius: 10px; padding: 1rem 1.2rem; margin-top: 1rem; border: 1px solid var(--border); }
    .row-sum { display: flex; justify-content: space-between; font-size: .85rem; padding: .25rem 0; color: var(--gray); }
    .row-sum.grand { font-weight: 800; font-size: 1rem; color: var(--black); border-top: 2px solid var(--orange); margin-top: .5rem; padding-top: .6rem; }
    .row-sum.grand .val-grand { color: var(--orange); }

    .modal-nota .modal-footer {
      background: #111;
      border-top: 1px solid #333;
      border-radius: 0 0 18px 18px;
      padding: 1rem 1.5rem;
      gap: .5rem;
    }

    .nota-loading { text-align: center; padding: 3rem 1rem; color: #94a3b8; }
    .nota-loading .fa-spinner { font-size: 2rem; color: var(--orange); }

    .alert-custom { border-radius: 12px; font-size: .87rem; font-family: inherit; }

    @media print {
      body * { visibility: hidden; }
      #areaCetak, #areaCetak * { visibility: visible; }
      #areaCetak { position: fixed; top: 0; left: 0; width: 100%; background: #fff; padding: 2rem; }
      @page { size: A5 portrait; margin: 1cm; }
    }
  </style>
</head>
<body>
<div class="wrapper-utama">
  <?php include __DIR__.'/../assets/sidebar.php'; ?>
  <div class="area-kanan">
    <?php include __DIR__.'/../assets/navbar.php'; ?>
    <div class="konten-utama">

      <!-- ALERT -->
      <?php if(isset($_GET['msg'])):
        $msgs=['tambah'=>['success','fa-check-circle','Transaksi berhasil ditambahkan!'],
               'update'=>['warning','fa-edit','Transaksi berhasil diperbarui!']];
        $m=$msgs[$_GET['msg']]??null;
        if($m): ?>
      <div class="alert alert-<?=$m[0]?> alert-dismissible fade show alert-custom d-flex align-items-center gap-2 mb-3">
        <i class="fas <?=$m[1]?>"></i> <?=$m[2]?>
        <button class="btn-close ms-auto" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; endif; ?>

      <!-- PAGE HEADER -->
      <div class="page-header mb-4">
        <div>
          <h4><i class="fas fa-receipt me-2"></i>Data <span class="accent">Transaksi</span></h4>
          <small>Riwayat transaksi Tunai &amp; Hutang — Kantin UAM</small>
        </div>
      </div>

      <!-- STAT CARDS -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card sc-orange">
            <div class="ico"><i class="fas fa-file-invoice"></i></div>
            <div class="val"><?=$total_trx?></div>
            <div class="lbl">Total Transaksi</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card sc-green">
            <div class="ico"><i class="fas fa-check-circle"></i></div>
            <div class="val"><?=$total_lunas?></div>
            <div class="lbl">Lunas</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card sc-dark">
            <div class="ico"><i class="fas fa-clock"></i></div>
            <div class="val"><?=$total_pending?></div>
            <div class="lbl">Pending / Hutang</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card sc-black">
            <div class="ico"><i class="fas fa-coins"></i></div>
            <div class="val" style="font-size:1rem;">Rp <?=number_format($total_omzet,0,'.',',')?></div>
            <div class="lbl">Omzet Lunas</div>
          </div>
        </div>
      </div>

      <!-- TABLE CARD -->
      <div class="table-card">
        <div class="table-card-header">
          <div class="title">
            <span class="dot"></span>
            Daftar Transaksi
          </div>
          <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" class="input-search" placeholder="Cari no faktur / pembeli...">
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover mb-0" id="tblTransaksi">
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th>No Faktur</th>
                <th>Pembeli / Vendor</th>
                <th>Tanggal</th>
                <th>Metode</th>
                <th>Total</th>
                <th>Status</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php if(empty($transaksi)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="fas fa-inbox fa-3x mb-3 d-block" style="color:#dee2e6;"></i>
                  Belum ada data transaksi.<br>
                  <small>Transaksi akan muncul otomatis setelah checkout dari kasir.</small>
                </td>
              </tr>
            <?php else: foreach($transaksi as $i=>$t): ?>
              <tr>
                <td class="text-muted fw-600"><?=$i+1?></td>
                <td>
                  <span class="faktur-link" onclick="lihatDetail(<?=$t['id_transaksi']?>,'<?=htmlspecialchars($t['no_faktur'])?>') ">
                    <i class="fas fa-file-alt me-1" style="color:#fd7e14;opacity:.7;"></i>
                    <?=htmlspecialchars($t['no_faktur'])?>
                  </span>
                </td>
                <td class="fw-600"><?=htmlspecialchars($t['nama_pembeli'])?></td>
                <td><?=date('d M Y',strtotime($t['tanggal']))?></td>
                <td>
                  <?php
                    $met    = $t['metode_pembayaran'];
                    $metKey = strtolower($met);
                    $mIco   = ['tunai'=>'fa-money-bill-wave','hutang'=>'fa-file-invoice-dollar','transfer'=>'fa-university'];
                  ?>
                  <span class="bm bm-<?=$metKey?>">
                    <i class="fas <?=$mIco[$metKey]??'fa-money-bill'?>"></i><?=ucfirst($met)?>
                  </span>
                </td>
                <td class="fw-700" style="color:var(--black);">Rp <?=number_format($t['total_harga'],0,'.',',')?></td>
                <td>
                  <?php $s=$t['status']; ?>
                  <span class="bs bs-<?=$s?>">
                    <?php if($s==='lunas') echo '<i class="fas fa-check"></i>Lunas';
                          elseif($s==='pending') echo '<i class="fas fa-clock"></i>Pending';
                          else echo '<i class="fas fa-times"></i>Batal'; ?>
                  </span>
                </td>
                <td class="text-center">
                  <button class="btn-aksi btn btn-outline-secondary" title="Lihat Nota"
                    style="border-color:#fd7e14;color:#fd7e14;"
                    onclick="lihatDetail(<?=$t['id_transaksi']?>,'<?=htmlspecialchars($t['no_faktur'])?>') ">
                    <i class="fas fa-eye"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <?php if(!empty($transaksi)): ?>
        <div class="table-footer">
          <i class="fas fa-coins me-2"></i>Total Omzet (Lunas):
          <strong style="color:var(--orange);font-size:1rem;margin-left:.5rem;">
            Rp <?=number_format($total_omzet,0,'.',',')?>
          </strong>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- MODAL DETAIL NOTA -->
<div class="modal fade modal-nota" id="modalDetailNota" tabindex="-1" data-bs-backdrop="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-file-invoice me-2" style="color:var(--orange);"></i>
          Detail Nota: <span class="accent" id="modalFakturTitle"></span>
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-3">
        <div id="notaLoading" class="nota-loading">
          <i class="fas fa-spinner fa-spin mb-3 d-block"></i>
          <div>Memuat data nota...</div>
        </div>

        <div id="notaContent" style="display:none;">
          <div class="nota-inner" id="areaCetak">

            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="nota-brand">
                  <div class="dot-o"><i class="fas fa-store" style="font-size:.7rem;"></i></div>
                  KANTIN UAM
                </div>
                <div style="font-size:.78rem;color:var(--gray);margin-top:.3rem;">
                  No. Faktur: <strong id="nFaktur" style="color:var(--black);"></strong>
                </div>
                <div style="font-size:.78rem;color:var(--gray);">
                  Tgl: <strong id="nTanggal" style="color:var(--black);"></strong>
                </div>
              </div>
              <div class="text-end">
                <div style="font-size:.75rem;color:var(--gray);">Pembeli / Vendor</div>
                <div id="nPembeli" style="font-weight:800;font-size:.95rem;color:var(--black);"></div>
                <div class="mt-2" id="nStatusWrap"></div>
              </div>
            </div>

            <hr class="nota-divider">

            <div style="font-size:.8rem;font-weight:800;color:var(--gray);margin-bottom:.6rem;text-transform:uppercase;letter-spacing:.04em;">
              <i class="fas fa-shopping-basket me-1" style="color:var(--orange);"></i>Daftar Barang Yang Dibeli
            </div>

            <table class="tbl-nota">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Barang</th>
                  <th class="text-center">QTY</th>
                  <th class="text-end">Harga</th>
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody id="tbodyNota"></tbody>
            </table>

            <div class="nota-summary">
              <div class="row-sum">
                <span>Total Belanja</span>
                <span id="nTotal" class="fw-bold" style="color:var(--black);"></span>
              </div>
              <div class="row-sum">
                <span>Metode Pembayaran</span>
                <span id="nMetode" class="fw-bold" style="color:var(--black);"></span>
              </div>
              <div class="row-sum grand">
                <span>GRAND TOTAL</span>
                <span class="val-grand" id="nGrandTotal"></span>
              </div>
            </div>

            <div class="row mt-4 text-center" style="font-size:.76rem;color:var(--gray);">
              <div class="col-6">
                <div style="border-top:1.5px solid #cbd5e1;width:110px;margin:3rem auto .4rem;"></div>
                Pembeli / Vendor
                <div id="ttdPembeli" class="fw-bold mt-1" style="color:var(--black);font-size:.82rem;"></div>
              </div>
              <div class="col-6">
                <div style="border-top:1.5px solid #cbd5e1;width:110px;margin:3rem auto .4rem;"></div>
                Petugas Kasir
                <div class="fw-bold mt-1" style="color:var(--black);font-size:.82rem;">Admin</div>
              </div>
            </div>

            <div class="text-center mt-3" style="font-size:.7rem;color:#adb5bd;">
              Terima kasih telah berbelanja di <strong>Kantin UAM</strong> 🧡 &nbsp;|&nbsp; Dicetak: <span id="nCetak"></span>
            </div>

          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i>Tutup
        </button>
        <button id="btnCetak" class="btn btn-sm rounded-pill px-3" onclick="cetakNota()"
          style="display:none;background:var(--green);color:#fff;border:none;">
          <i class="fas fa-print me-1"></i>Cetak Nota
        </button>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// SEARCH
document.getElementById('searchInput').addEventListener('input', function(){
  const q = this.value.toLowerCase();
  document.querySelectorAll('#tblTransaksi tbody tr').forEach(tr=>{
    tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
  });
});

// FORMAT RP
function rp(n){ return 'Rp ' + parseInt(n||0).toLocaleString('id-ID'); }

// LIHAT DETAIL NOTA
function lihatDetail(id, faktur){
  document.getElementById('notaLoading').style.display  = 'block';
  document.getElementById('notaContent').style.display  = 'none';
  document.getElementById('btnCetak').style.display     = 'none';
  document.getElementById('modalFakturTitle').textContent = faktur;

  new bootstrap.Modal(document.getElementById('modalDetailNota')).show();

  fetch(`get_detail_transaksi.php?id=${id}`)
  .then(r=>r.json())
  .then(data=>{
    if(!data.transaksi){ throw new Error('Data tidak ditemukan'); }
    const t     = data.transaksi;
    const items = data.detail || [];

    document.getElementById('nFaktur').textContent    = t.no_faktur;
    document.getElementById('nTanggal').textContent   = t.tanggal_fmt;
    document.getElementById('nPembeli').textContent   = t.nama_pembeli;
    document.getElementById('ttdPembeli').textContent = t.nama_pembeli;
    document.getElementById('nMetode').textContent    = t.metode_pembayaran;
    document.getElementById('nCetak').textContent     = new Date().toLocaleString('id-ID');

    const sMap = {
      lunas:   {cls:'bs-lunas',   icon:'✅', label:'Lunas'},
      pending: {cls:'bs-pending', icon:'⏳', label:'Pending'},
      batal:   {cls:'bs-batal',   icon:'❌', label:'Batal'},
    };
    const sm = sMap[t.status] || {cls:'bs-pending',icon:'•',label:t.status};
    document.getElementById('nStatusWrap').innerHTML =
      `<span class="bs ${sm.cls}">${sm.icon} ${sm.label}</span>`;

    const tbody = document.getElementById('tbodyNota');
    tbody.innerHTML = '';
    if(items.length === 0){
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada item</td></tr>';
    } else {
      items.forEach((item,idx)=>{
        const sub = parseInt(item.subtotal||0);
        tbody.innerHTML += `
        <tr>
          <td>${idx+1}</td>
          <td><strong>${item.nama_produk}</strong></td>
          <td class="text-center">${item.jumlah}</td>
          <td class="text-end">${rp(item.harga)}</td>
          <td class="text-end fw-bold" style="color:var(--orange);">${rp(sub)}</td>
        </tr>`;
      });
    }

    document.getElementById('nTotal').textContent      = rp(t.total_harga);
    document.getElementById('nGrandTotal').textContent = rp(t.total_harga);

    document.getElementById('notaLoading').style.display = 'none';
    document.getElementById('notaContent').style.display = 'block';
    document.getElementById('btnCetak').style.display    = 'inline-flex';
  })
  .catch(()=>{
    document.getElementById('notaLoading').innerHTML =
      '<i class="fas fa-exclamation-triangle fa-2x mb-2 d-block" style="color:#fd7e14;"></i>' +
      '<div class="text-danger">Gagal memuat data nota.</div>';
  });
}

// CETAK
function cetakNota(){ window.print(); }
</script>
</body>
</html>
