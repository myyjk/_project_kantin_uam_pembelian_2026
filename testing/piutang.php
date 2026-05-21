<?php
session_start();
require '../config/config.php';

// 1. Proteksi Login
if (!isset($_SESSION['isLoggedIn'])) {
    header("Location: ../login/login.php");
    exit();
}
// 2. Mengambil ID user pembeli yang sedang login
$id_user_login = $_SESSION['currentUser']['id_user'] ?? $_SESSION['currentUser']['id_admin'] ?? null;

if (!$id_user_login) {
    die("Sesi user tidak valid. Silakan login kembali.");
}

// ==========================================================
// PROSES AKSI BARU: HAPUS HISTORI NOTA (Hanya jika SUDAH LUNAS & Milik Sendiri)
// ==========================================================
if (isset($_POST['proses_hapus'])) {
    $id_beli_hapus = mysqli_real_escape_string($conn, $_POST['id_beli']);
    
    // Cek ulang ke database untuk memastikan status terutang memang sudah lunas DAN milik user yang login
    $check_status_query = "SELECT 
                                p.metode AS jumlah_terbayar,
                                IFNULL(SUM(db.jumlah * db.harga), 0) AS total_belanja
                            FROM pembelian p
                            LEFT JOIN detail_beli db ON p.id_beli = db.id_beli
                            WHERE p.id_beli = '$id_beli_hapus' AND p.id_admin = '$id_user_login'
                            GROUP BY p.id_beli";
    $res_check = mysqli_query($conn, $check_status_query);
    $data_check = mysqli_fetch_assoc($res_check);
    
    if (!$data_check) {
        echo "<script>alert('Peringatan! Data tidak ditemukan atau Anda tidak memiliki akses.'); window.history.back();</script>";
        exit();
    }
    
    $total_faktur_check = floatval($data_check['total_belanja'] ?? 0);
    $terbayar_check = floatval($data_check['jumlah_terbayar'] ?? 0);
    $terutang_check = $total_faktur_check - $terbayar_check;

    // Validasi ketat: Hanya boleh dihapus jika total belanja > 0 dan sisa hutang <= 0
    if ($total_faktur_check > 0 && $terutang_check <= 0) {
        // Hapus terlebih dahulu relasi data di detail_beli agar tidak terkena RESTRICT foreign key
        mysqli_query($conn, "DELETE FROM detail_beli WHERE id_beli = '$id_beli_hapus'");
        
        // Hapus data utama di tabel pembelian (pastikan aman dengan id_admin)
        $delete_query = "DELETE FROM pembelian WHERE id_beli = '$id_beli_hapus' AND p.id_admin = '$id_user_login'";
        if (mysqli_query($conn, $delete_query)) {
            echo "<script>alert('Sukses! Data piutang yang telah lunas berhasil dihapus dari sistem.'); window.location='piutang.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal menghapus data dari database.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Peringatan! Data piutang belum lunas atau masih kosong sehingga tidak diizinkan untuk dihapus.'); window.history.back();</script>";
        exit();
    }
}

// ==========================================================
// PROSES SIMPAN PEMBAYARAN (CICIL / LUNAS & Milik Sendiri)
// ==========================================================
if (isset($_POST['proses_bayar'])) {
    $id_beli_bayar = mysqli_real_escape_string($conn, $_POST['id_beli']);
    $nominal_bayar = floatval($_POST['nominal_bayar']);
    
    if (!isset($_FILES['foto_faktur']) || $_FILES['foto_faktur']['error'] == 4) {
        echo "<script>alert('Gagal! Anda wajib mengunggah foto faktur atau bukti pembayaran.'); window.history.back();</script>";
        exit();
    }

    if ($nominal_bayar > 0) {
        // Proteksi: Cek apakah invoice ini benar milik user yang login
        $check_owner = mysqli_query($conn, "SELECT metode FROM pembelian WHERE id_beli = '$id_beli_bayar' AND id_admin = '$id_user_login'");
        $data_bayar = mysqli_fetch_assoc($check_owner);

        if (!$data_bayar) {
            echo "<script>alert('Akses Ditolak! Anda tidak bisa membayar invoice user lain.'); window.history.back();</script>";
            exit();
        }

        $target_dir = "uploads/faktur_bayar/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $ext = pathinfo($_FILES["foto_faktur"]["name"], PATHINFO_EXTENSION);
        $file_name = "faktur_" . $id_beli_bayar . "_" . time() . "." . $ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["foto_faktur"]["tmp_name"], $target_file)) {
            $sudah_dibayar_sebelumnya = floatval($data_bayar['metode'] ?? 0);
            $total_pembayaran_baru = $sudah_dibayar_sebelumnya + $nominal_bayar;

            // Update data dengan validasi kepemilikan user
            $update_query = "UPDATE pembelian SET metode = '$total_pembayaran_baru' WHERE id_beli = '$id_beli_bayar' AND id_admin = '$id_user_login'";
            
            if (mysqli_query($conn, $update_query)) {
                echo "<script>alert('Pembayaran berhasil dicatat!'); window.location='piutang.php';</script>";
                exit();
            } else {
                echo "<script>alert('Gagal memproses ke database: " . mysqli_error($conn) . "');</script>";
            }
        } else {
            echo "<script>alert('Gagal mengunggah foto ke server.');</script>";
        }
    }
}

// ==========================================================
// 3. QUERY UTAMA DENGAN PRIVASI KETAT (SUDAH DIPERBAIKI)
// ==========================================================
$query = "SELECT 
            p.id_beli,
            p.no_faktur, 
            p.tanggal_beli,
            p.id_vendor,
            p.metode AS jumlah_terbayar,
            IFNULL(v.nama, IFNULL(v.nama_vendor, 'Kantin Umum')) AS nama_kantin, 
            IFNULL(SUM(db.jumlah * db.harga), 0) AS total_belanja,
            IFNULL(GROUP_CONCAT(CONCAT(pr.nama, ' (', db.jumlah, 'x)') SEPARATOR ', '), 'Tidak ada detail produk') AS detail_produk
          FROM pembelian p
          LEFT JOIN vendor v ON p.id_vendor = v.id_vendor
          LEFT JOIN detail_beli db ON p.id_beli = db.id_beli
          LEFT JOIN produk pr ON db.id_produk = pr.id_produk
          WHERE p.id_admin = '$id_user_login'
          GROUP BY p.id_beli
          ORDER BY p.tanggal_beli DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    // Jalur alternatif tanpa join vendor (Filter p.id_admin tetap dikunci)
    $query = "SELECT 
                p.id_beli,
                p.no_faktur, 
                p.tanggal_beli,
                p.id_vendor,
                p.metode AS jumlah_terbayar,
                'Kantin Umum' AS nama_kantin,
                IFNULL(SUM(db.jumlah * db.harga), 0) AS total_belanja,
                IFNULL(GROUP_CONCAT(CONCAT(pr.nama, ' (', db.jumlah, 'x)') SEPARATOR ', '), 'Tidak ada detail produk') AS detail_produk
              FROM pembelian p
              LEFT JOIN detail_beli db ON p.id_beli = db.id_beli
              LEFT JOIN produk pr ON db.id_produk = pr.id_produk
              WHERE p.id_admin = '$id_user_login'
              GROUP BY p.id_beli
              ORDER BY p.tanggal_beli DESC";
    $result = mysqli_query($conn, $query);
}

$grand_total_faktur = 0;
$grand_total_terutang = 0;
$grand_total_bayar = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piutang Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Inter', sans-serif; overflow-x: hidden; color: #1a1a1a; }
        .wrapper { display: flex; width: 100%; min-height: 100vh; align-items: stretch; }
        .sidebar-container { width: 260px; flex-shrink: 0; background: #ffffff; border-right: 1px solid #e0e0e0; }
        .main-panel { flex-grow: 1; min-width: 0; display: flex; flex-direction: column; }
        .inner-content { padding: 24px; }
        
        .table-responsive-custom { background: #ffffff; border-radius: 8px; border: 1px solid #e0e0e0; padding: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table { color: #1a1a1a; margin-bottom: 0; }
        .table thead th { background-color: #2c3e50; color: #ffffff; font-weight: 500; border-bottom: 3px solid #ff6600; padding: 12px; font-size: 0.9rem; }
        .table-hover tbody tr:hover { background-color: #fcfcfc; }
        
        .btn-orange { background-color: #ff6600; color: #ffffff; border: none; font-weight: 600; transition: 0.2s; }
        .btn-orange:hover { background-color: #e05500; color: #ffffff; }
        .btn-outline-orange { border: 2px solid #ff6600; color: #ff6600; font-weight: 600; background: transparent; }
        .btn-outline-orange:hover { background: #ff6600; color: #ffffff; }
        
        .accurate-summary-box { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 15px; display: inline-block; min-width: 280px; box-shadow: 0 2px 6px rgba(0,0,0,0.03); }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar-container">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="main-panel">
        <?php include 'navbar.php'; ?>

        <div class="inner-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-0" style="color: #1a1a1a;">Data Invoice & <span style="color: #ff6600;">Piutang Belanja</span></h5>
                    <p class="text-muted small mb-0">Kelola rincian nota tagihan belanja Anda secara pribadi dan aman.</p>
                </div>
                <button class="btn btn-outline-orange rounded-pill btn-sm px-3" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh Data
                </button>
            </div>

            <div class="table-responsive-custom mb-3">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="4%">No</th>
                            <th width="15%">No. Faktur</th>
                            <th width="12%">Nama Kantin</th>
                            <th width="15%">Tanggal & Waktu</th>
                            <th width="12%">Total Faktur</th>
                            <th width="12%">Terutang (Sisa)</th>
                            <th width="12%">Bayar (Cicilan)</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="12%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($result) > 0): 
                            while ($row = mysqli_fetch_assoc($result)): 
                                $total_faktur = floatval($row['total_belanja']);
                                $total_yang_sudah_dibayar = floatval($row['jumlah_terbayar'] ?? 0);
                                $terutang = $total_faktur - $total_yang_sudah_dibayar;

                                $grand_total_faktur += $total_faktur;
                                $grand_total_terutang += ($terutang > 0 ? $terutang : 0);
                                $grand_total_bayar += $total_yang_sudah_dibayar;

                                if ($total_faktur == 0) {
                                    $status = '<span class="badge bg-secondary">Kosong</span>';
                                } elseif ($terutang <= 0) {
                                    $status = '<span class="badge text-white" style="background-color: #00cc66;"><i class="fas fa-check-circle me-1"></i> Lunas</span>';
                                    $terutang = 0; 
                                } elseif ($total_yang_sudah_dibayar > 0 && $terutang > 0) {
                                    $status = '<span class="badge text-white" style="background-color: #ffaa00;"><i class="fas fa-clock me-1"></i> Dicicil</span>';
                                } else {
                                    $status = '<span class="badge text-white" style="background-color: #ff3333;"><i class="fas fa-exclamation-circle me-1"></i> Belum Bayar</span>';
                                }
                        ?>
                        <tr>
                            <td><strong><?= $no++; ?></strong></td>
                            <td><span class="fw-bold text-dark"><?= htmlspecialchars($row['no_faktur'] ?? '-'); ?></span></td>
                            <td class="fw-semibold text-secondary">
                                <i class="fas fa-store me-1 small"></i> <?= htmlspecialchars($row['nama_kantin'] ?? 'Kantin UAM'); ?>
                            </td>
                            <td class="small text-muted">
                                <i class="far fa-calendar-alt me-1"></i> <?= isset($row['tanggal_beli']) ? date('d/m/Y', strtotime($row['tanggal_beli'])) : '-'; ?><br>
                                <i class="far fa-clock me-1"></i> <?= isset($row['tanggal_beli']) ? date('H:i', strtotime($row['tanggal_beli'])) : '-'; ?> WIB
                            </td>
                            <td class="fw-bold text-dark">Rp <?= number_format($total_faktur, 0, ',', '.'); ?></td>
                            <td class="fw-bold <?= $terutang > 0 ? 'text-danger' : 'text-dark'; ?>">Rp <?= number_format($terutang, 0, ',', '.'); ?></td>
                            <td class="fw-semibold" style="color: #00cc66;">Rp <?= number_format($total_yang_sudah_dibayar, 0, ',', '.'); ?></td>
                            <td class="text-center"><?= $status; ?></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <?php if ($terutang > 0 && $total_faktur > 0): ?>
                                        <button class="btn btn-sm btn-orange rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#modalBayar<?= $row['id_beli']; ?>" style="font-size:0.75rem;">
                                            <i class="fas fa-money-bill-wave"></i> Bayar
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light rounded-pill text-muted border" style="font-size:0.75rem;" disabled>Selesai</button>
                                    <?php endif; ?>

                                    <?php if ($total_faktur > 0 && $terutang <= 0): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Apakah anda yakin ingin menghapus permanen riwayat nota piutang yang telah LUNAS ini?')">
                                            <input type="hidden" name="id_beli" value="<?= $row['id_beli']; ?>">
                                            <button type="submit" name="proses_hapus" class="btn btn-sm btn-danger rounded-pill px-2" style="font-size:0.75rem;" title="Hapus Data Lunas">
                                                <i class="fas fa-trash-alt"></i> Hapus
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light rounded-pill text-muted border" style="font-size:0.75rem;" disabled title="Data belum lunas, tidak bisa diubah/dihapus">
                                            <i class="fas fa-lock"></i> Kunci
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalBayar<?= $row['id_beli']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="" enctype="multipart/form-data">
                                        <div class="modal-header">
                                            <h6 class="modal-title fw-bold" style="color: #1a1a1a;"><i class="fas fa-wallet me-2" style="color: #ff6600;"></i>Form Penerimaan Pembayaran</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <input type="hidden" name="id_beli" value="<?= $row['id_beli']; ?>">
                                            <p class="text-muted small mb-3">No. Faktur Aktif: <strong class="text-dark"><?= htmlspecialchars($row['no_faktur']); ?></strong></p>
                                            
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Nilai Terutang Saat Ini</label>
                                                <input type="text" class="form-control fw-bold text-danger bg-light" value="Rp <?= number_format($terutang, 0, ',', '.'); ?>" readonly>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Nilai Pembayaran (Rp)</label>
                                                <div class="input-group">
                                                    <input type="number" id="nominal_input_<?= $row['id_beli']; ?>" name="nominal_bayar" class="form-control" max="<?= $terutang; ?>" min="1" placeholder="Masukkan jumlah pembayaran" required>
                                                    <button type="button" class="btn btn-success text-white fw-bold btn-sm" onclick="document.getElementById('nominal_input_<?= $row['id_beli']; ?>').value = '<?= $terutang; ?>'">Set Lunas</button>
                                                </div>
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label text-muted small">Wajib Lampirkan Foto Faktur / Bukti Fisik</label>
                                                <input type="file" name="foto_faktur" class="form-control" accept="image/*" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="proses_bayar" class="btn btn-success btn-sm fw-bold">Simpan Pembayaran</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                Tidak ditemukan data riwayat transaksi piutang untuk akun Anda.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mb-4">
                <div class="accurate-summary-box">
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>Total Nilai Terutang Anda: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                        <span class="fw-bold text-danger">Rp <?= number_format($grand_total_terutang, 0, ',', '.'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top" style="font-size: 0.95rem;">
                        <span class="fw-bold text-dark">Total Faktur Dibayar: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                        <span class="fw-bold text-success">Rp <?= number_format($grand_total_bayar, 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>