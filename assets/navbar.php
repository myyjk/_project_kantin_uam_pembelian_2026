<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$nama_petugas   = $_SESSION['currentUser']['username'] ?? $_SESSION['currentUser']['namalengkap'] ?? 'Sastra';
$inisial_navbar = strtoupper(substr($nama_petugas, 0, 1));
$foto_path_navbar = '';

// Ambil foto langsung dari DB agar selalu up-to-date
if (!empty($_SESSION['currentUser']['id']) && isset($conn)) {
    $uid  = $_SESSION['currentUser']['id'];
    $qf   = mysqli_query($conn, "SELECT foto FROM users WHERE id='$uid' LIMIT 1");
    $rowf = mysqli_fetch_assoc($qf);
    $foto_file = $rowf['foto'] ?? '';
    if ($foto_file && file_exists(__DIR__ . '/../account/uploads/' . $foto_file)) {
        $foto_path_navbar = '../account/uploads/' . $foto_file;
    }
}
?>

<style>
    :root {
        --orange:      #f97316;
        --orange-dark: #ea580c;
        --orange-soft: #fff7ed;
        --green:       #16a34a;
        --green-dark:  #15803d;
        --green-soft:  #f0fdf4;
        --white:       #ffffff;
        --black:       #0f172a;
        --gray:        #64748b;
        --light-bg:    #f8fafc;
        --border:      #e2e8f0;
        --nav-h:       68px;
        --font:        'Plus Jakarta Sans', sans-serif;
        --transition:  0.18s ease;
    }

    * { font-family: var(--font); }

    .top-nav {
        height: var(--nav-h);
        background: var(--white);
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 24px;
        gap: 12px;
        box-shadow: 0 1px 8px rgba(0,0,0,0.06);
        position: sticky;
        top: 0;
        z-index: 100;
        flex-shrink: 0;
    }

    .nav-brand {
        display: flex;
        flex-direction: column;
        gap: 1px;
    }
    .nav-brand .nav-eyebrow {
        font-size: 9.5px;
        font-weight: 800;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: var(--gray);
    }
    .nav-brand .nav-title {
        display: flex;
        align-items: center;
        gap: 7px;
        text-decoration: none;
    }
    .nav-brand .nav-title .icon-box {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        background: var(--orange);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 12px;
    }
    .nav-brand .nav-title span {
        font-size: 16px;
        font-weight: 800;
        color: var(--green-dark);
        letter-spacing: -0.4px;
    }

    .nav-info-bar {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .nav-info-pill {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 13px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid var(--border);
        background: var(--light-bg);
        color: #334155;
        white-space: nowrap;
    }
    .nav-info-pill i { font-size: 12px; color: var(--green); }
    .nav-info-pill strong { color: var(--black); font-weight: 700; }

    .nav-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .nav-user-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px 6px 8px;
        border-radius: 20px;
        border: 1px solid var(--border);
        background: var(--light-bg);
        cursor: default;
    }
    .nav-user-badge .avatar-sm {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--green), var(--orange));
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 13px;
    }
    .nav-user-badge .user-text small {
        display: block;
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        color: var(--gray);
        line-height: 1;
    }
    .nav-user-badge .user-text span {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: var(--black);
        line-height: 1.3;
    }
    .btn-logout {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 20px;
        border: 1.5px solid var(--orange);
        background: var(--orange-soft);
        color: var(--orange-dark);
        font-size: 12.5px;
        font-weight: 700;
        text-decoration: none;
        transition: all var(--transition);
        white-space: nowrap;
    }
    .btn-logout:hover {
        background: var(--orange-dark);
        color: #fff;
        border-color: var(--orange-dark);
        box-shadow: 0 4px 12px rgba(234,88,12,0.35);
    }
    .btn-logout i { font-size: 13px; }
</style>

<div class="top-nav">

    <div class="nav-brand">
        <span class="nav-eyebrow">Aplikasi Kasir</span>
        <a href="#" class="nav-title">
            <div class="icon-box"><i class="fas fa-utensils"></i></div>
            <span>KANTIN UAM</span>
        </a>
    </div>

    <div class="nav-info-bar d-none d-md-flex">
        <div class="nav-info-pill">
            <i class="far fa-calendar-alt"></i>
            Hari ini: <strong><?= date('d F Y'); ?></strong>
        </div>
  <div class="nav-info-pill">
    <i class="far fa-clock-alt"></i>
    Zona Waktu: 
    | <span id="jam-wib" style="font-weight: bold;">00:00:00</span>
</div>
    </div>

    <div class="nav-right">
        <div class="nav-user-badge d-none d-sm-flex">
            <div class="avatar-sm">
                <?php if ($foto_path_navbar): ?>
                    <img src="<?= $foto_path_navbar ?>" alt="Foto"
                         style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <span style="font-size:13px;font-weight:800;line-height:1;">
                        <?= htmlspecialchars($inisial_navbar) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="user-text">
                <small><?= $_SESSION["namarole"] ?> Aktif</small>
                <span><?= htmlspecialchars($nama_petugas); ?></span>
            </div>
        </div>
        <a href="../login/logout.php" class="btn-logout"
           onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem Kantin UAM?')">
            <i class="fas fa-sign-out-alt"></i>
            <span class="d-none d-sm-inline">Keluar</span>
        </a>
    </div>

</div>

<script>
    function jalankanJam() {
        const opsi = { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }; 
        const formatJam = new Intl.DateTimeFormat('id-ID', opsi);
        document.getElementById('jam-wib').textContent = formatJam.format(new Date());
    }
    jalankanJam(); // Jalankan langsung saat web dibuka
    setInterval(jalankanJam, 1000); // Update setiap 1 detik
</script>
