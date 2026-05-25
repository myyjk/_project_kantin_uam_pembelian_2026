<?php
// Dipanggil AJAX dari transaksi.php - tidak perlu session
if(!isset($conn)) require_once __DIR__.'/../config/config.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { echo json_encode(['error'=>'ID tidak valid']); exit; }

$sql = "SELECT t.*,
               COALESCE(p.nama, CONCAT('Pembeli #', t.id_beli)) AS nama_pembeli
        FROM transaksi t
        LEFT JOIN pembelian p ON t.id_beli = p.id_beli
        WHERE t.id_transaksi = $id LIMIT 1";
$r = mysqli_query($conn, $sql);
if (!$r) {
    $r = mysqli_query($conn, "SELECT *, CONCAT('Pembeli #',id_beli) AS nama_pembeli
                               FROM transaksi WHERE id_transaksi=$id LIMIT 1");
}
$trx = $r ? mysqli_fetch_assoc($r) : null;
if (!$trx) { echo json_encode(['error'=>'Transaksi tidak ditemukan']); exit; }

$trx['tanggal_fmt'] = date('d/m/Y', strtotime($trx['tanggal']));

$sql2 = "SELECT dt.*,
                COALESCE(b.nama, CONCAT('Barang #', dt.id_produk)) AS nama_produk
          FROM detail_transaksi dt
          LEFT JOIN barang b ON dt.id_produk = b.id
          WHERE dt.id_transaksi = $id
          ORDER BY dt.id_detail ASC";
$r2 = mysqli_query($conn, $sql2);
$detail = [];
if ($r2) while ($row = mysqli_fetch_assoc($r2)) $detail[] = $row;

echo json_encode(['transaksi' => $trx, 'detail' => $detail]);
