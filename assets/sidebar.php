<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$nama_petugas    = $_SESSION['currentUser']['username'] ?? $_SESSION['currentUser']['namalengkap'] ?? 'Sastra';
$inisial_sidebar = strtoupper(substr($nama_petugas, 0, 1));
$foto_path_sidebar = '';

if (!empty($_SESSION['currentUser']['id']) && isset($conn)) {
    $uid  = $_SESSION['currentUser']['id'];
    $qf   = mysqli_query($conn, "SELECT foto FROM users WHERE id='$uid' LIMIT 1");
    $rowf = mysqli_fetch_assoc($qf);
    $foto_file = $rowf['foto'] ?? '';
    if ($foto_file && file_exists(__DIR__ . '/../account/uploads/' . $foto_file)) {
        $foto_path_sidebar = '../account/uploads/' . $foto_file;
    }
}

// Deteksi halaman aktif dari ?page=
$current_page = $_GET['page'] ?? 'pembeli';
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    :root {
        --orange:      #f97316;
        --orange-dark: #ea580c;
        --orange-soft: #fff7ed;
        --green:       #16a34a;
        --green-dark:  #15803d;
        --green-soft:  #f0fdf4;
        --green-mid:   #bbf7d0;
        --white:       #ffffff;
        --black:       #0f172a;
        --gray:        #64748b;
        --light-bg:    #f8fafc;
        --border:      #e2e8f0;
        --sidebar-w:   268px;
        --nav-h:       68px;
        --font:        'Plus Jakarta Sans', sans-serif;
        --radius:      10px;
        --transition:  0.18s ease;
    }
    * { font-family: var(--font); }
    .sidebar {
        width: var(--sidebar-w);
        height: 100vh;
        background: var(--white);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        position: sticky;
        top: 0;
        overflow: hidden;
    }
    .sidebar-brand {
        height: var(--nav-h);
        display: flex;
        align-items: center;
        padding: 0 18px;
        gap: 12px;
        border-bottom: 3px solid var(--green);
        background: linear-gradient(135deg, var(--green-soft) 0%, #fff 100%);
        flex-shrink: 0;
    }
    .sidebar-brand .brand-logo {
        width: 42px; height: 42px;
        border-radius: 10px;
        object-fit: contain;
        background: var(--white);
        padding: 3px;
        box-shadow: 0 0 0 2px var(--green-mid);
    }
    .sidebar-brand .brand-text strong {
        display: block;
        font-size: 11.5px; font-weight: 700;
        color: var(--black); line-height: 1.2;
        letter-spacing: -0.2px;
    }
    .sidebar-brand .brand-badge {
        display: inline-flex; align-items: center; gap: 4px;
        margin-top: 3px;
        background: var(--orange); color: var(--white);
        font-size: 9.5px; font-weight: 800;
        letter-spacing: 0.7px; text-transform: uppercase;
        padding: 2px 7px; border-radius: 20px;
    }
    .sidebar-nav {
        flex: 1; overflow-y: auto;
        padding: 8px 10px 4px 10px;
        scrollbar-width: none;
    }
    .sidebar-nav::-webkit-scrollbar { display: none; }
    .sidebar-heading {
        font-size: 10px; font-weight: 800;
        letter-spacing: 0.9px; text-transform: uppercase;
        color: var(--gray);
        padding: 18px 10px 6px 10px;
        display: flex; align-items: center; gap: 6px;
    }
    .sidebar-heading::after {
        content: ''; flex: 1;
        height: 1px; background: var(--border);
    }
    .menu-link {
        display: flex; align-items: center; gap: 11px;
        padding: 10px 14px;
        color: #334155; font-size: 13.5px; font-weight: 500;
        text-decoration: none;
        border-radius: var(--radius);
        margin-bottom: 3px;
        transition: background var(--transition), color var(--transition), transform var(--transition);
        position: relative;
    }
    .menu-link .menu-icon {
        width: 34px; height: 34px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px;
        background: var(--light-bg); color: var(--gray);
        flex-shrink: 0;
        transition: background var(--transition), color var(--transition);
    }
    .menu-link:hover { background: var(--green-soft); color: var(--green-dark); transform: translateX(2px); }
    .menu-link:hover .menu-icon { background: var(--green-mid); color: var(--green-dark); }
    .menu-link.active {
        background: linear-gradient(90deg, var(--green) 0%, var(--green-dark) 100%);
        color: #fff; font-weight: 700;
        box-shadow: 0 4px 14px rgba(22,163,74,0.3);
        transform: none;
    }
    .menu-link.active .menu-icon { background: rgba(255,255,255,0.25); color: #fff; }
    .menu-link.active::before {
        content: ''; position: absolute;
        left: -10px; top: 50%; transform: translateY(-50%);
        width: 4px; height: 60%;
        border-radius: 0 4px 4px 0;
        background: var(--orange);
    }
    .sidebar-footer {
        border-top: 1px solid var(--border);
        padding: 10px;
        background: var(--light-bg);
        flex-shrink: 0;
    }
    .sidebar-user-card {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px;
        background: var(--white);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        margin-bottom: 8px;
    }
    .sidebar-user-card .avatar {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, var(--green) 0%, var(--orange) 100%);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 15px; flex-shrink: 0;
    }
    .sidebar-user-card .user-info small {
        display: block; font-size: 10px; color: var(--gray);
        font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase;
    }
    .sidebar-user-card .user-info span {
        display: block; font-size: 13px; font-weight: 700;
        color: var(--black); white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis; max-width: 140px;
    }
    .logout-link {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 14px;
        color: var(--orange-dark); font-size: 13px; font-weight: 600;
        text-decoration: none;
        border-radius: var(--radius);
        border: 1px solid #fed7aa;
        background: var(--orange-soft);
        transition: all var(--transition);
    }
    .logout-link:hover { background: var(--orange-dark); color: #fff; border-color: var(--orange-dark); }
    .logout-link i { font-size: 14px; }
</style>

<div class="sidebar shadow-sm">

    <div class="sidebar-brand">
        <img src="/<?= basename(dirname(dirname(__DIR__ ?? $_SERVER['DOCUMENT_ROOT']))) ?>/_project_26/img/logouam.png"
             onerror="this.style.display='none'"
             alt="Logo UAM" class="brand-logo">
        <div class="brand-text">
            <strong>Universitas Anwar Medika</strong>
            <div class="brand-badge">
                <i class="fas fa-store" style="font-size:8px;"></i> Kantin UAM
            </div>
        </div>
    </div>

    <div class="sidebar-nav">

        <div class="sidebar-heading">Manajemen Data</div>

        <?php
        // Helper aktif
        function isActive($pages) {
            global $current_page;
            if (is_array($pages)) return in_array($current_page, $pages) ? 'active' : '';
            return $current_page === $pages ? 'active' : '';
        }
        ?>

        <a href="/_project_26/?page=pembeli" class="menu-link <?= isActive(['pembeli','pembelian']) ?>">
            <div class="menu-icon"><i class="fas fa-users"></i></div>
            Pembeli
        </a>

        <div class="sidebar-heading">Keuangan</div>

        <a href="/_project_26/?page=transaksi" class="menu-link <?= isActive('transaksi') ?>">
            <div class="menu-icon"><i class="fas fa-cash-register"></i></div>
            Transaksi
        </a>

        <a href="/_project_26/?page=piutang" class="menu-link <?= isActive(['piutang','hutang']) ?>">
            <div class="menu-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            Hutang & Pembayaran
        </a>

    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user-card">
            <div class="avatar">
                <?php if ($foto_path_sidebar): ?>
                    <img src="<?= $foto_path_sidebar ?>" alt="Foto"
                         style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <span style="font-size:15px;font-weight:800;line-height:1;">
                        <?= htmlspecialchars($inisial_sidebar) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <small><?= $_SESSION['currentUser']['namarole'] ?? 'Kasir' ?> Aktif</small>
                <span><?= htmlspecialchars($nama_petugas) ?></span>
            </div>
        </div>

        <a href="/_project_26/?page=profile" class="menu-link mb-2 <?= isActive('profile') ?>">
            <div class="menu-icon"><i class="fas fa-user-gear"></i></div>
            Profile Saya
        </a>

        <a href="/_project_26/login/logout.php"
           class="logout-link"
           onclick="return confirm('Yakin ingin keluar?')">
            <i class="fas fa-right-from-bracket"></i>
            Logout dari Sistem
        </a>
    </div>

</div>
