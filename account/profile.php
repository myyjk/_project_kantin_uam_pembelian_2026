<?php
session_start();
require '../config/config.php';

if (!isset($_SESSION['isLoggedIn'])) {
    header("Location: ../login/login.php");
    exit();
}

// =========================================================================
// PERBAIKAN LOGIKA SESSION & QUERY
// =========================================================================
// Ambil ID user yang sedang login dari session
$user_id    = $_SESSION['currentUser']['id']; 
$admin_nama = $_SESSION['currentUser']['username'];

// Ambil data user yang valid berdasarkan ID user tersebut
$q    = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user = mysqli_fetch_assoc($q);

// Ambil ID role dari database (1 = Admin, 2 = User, dst.)
$role_id    = $user['id_role']; 

// Menentukan batas hari berdasarkan id_role (Misal: id_role 1 adalah Admin)
$batas_hari = ($role_id == 1) ? 30 : 7;
$bisa_edit  = true;
$sisa_hari  = 0;

if ($user['last_update_profile']) {
    $diff = (strtotime(date('Y-m-d H:i:s')) - strtotime($user['last_update_profile'])) / 86400;
    if ($diff < $batas_hari) {
        $bisa_edit = false;
        $sisa_hari = ceil($batas_hari - $diff);
    }
}

$pesan_sukses = "";
$pesan_error  = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bisa_edit) {
    // Variabel $nama sekarang ditujukan untuk kolom 'namalengkap'
    $nama  = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = trim($_POST['password']);
    $now   = date('Y-m-d H:i:s');

    $cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id != '$user_id'");
    if (mysqli_num_rows($cek) > 0) {
        $pesan_error = "Email sudah digunakan akun lain!";
    } else {
        $foto = $user['foto'];
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $ext_ok = ['jpg','jpeg','png','webp'];
            $ext    = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $ext_ok)) {
                $nama_foto = "profil_{$user_id}_" . time() . ".$ext";
                if (!is_dir("uploads/")) mkdir("uploads/", 0777, true);
                move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/$nama_foto");
                if ($foto && file_exists("uploads/$foto")) unlink("uploads/$foto");
                $foto = $nama_foto;
            } else {
                $pesan_error = "Format foto harus JPG, PNG, atau WEBP!";
            }
        }

        if (!$pesan_error) {
            $pass_sql = $pass !== '' ? ", password='$pass'" : "";
            
            // PERBAIKAN: Update diarahkan ke kolom 'namalengkap'
            $sql = "UPDATE users SET namalengkap='$nama', email='$email', foto='$foto' $pass_sql, last_update_profile='$now' WHERE id='$user_id'";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['currentUser']['nama'] = $nama;
                $pesan_sukses = "Profil berhasil diperbarui!";
                
                // Refresh data user setelah update
                $q    = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
                $user = mysqli_fetch_assoc($q);
                $bisa_edit = false;
                $sisa_hari = $batas_hari;
            } else {
                $pesan_error = "Gagal: " . mysqli_error($conn);
            }
        }
    }
}

// Inisial mengambil dari huruf pertama Nama Lengkap asli
$inisial = strtoupper(substr($user['namalengkap'] ?? $user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Kantin UAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            height: 100vh;
            font-family: var(--font);
            background: var(--light-bg);
            overflow: hidden;
            color: var(--black);
        }

        .wrapper-utama { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
        .area-konten-kanan { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; min-width: 0; }
        .main-scroll { flex: 1; overflow-y: auto; padding: 28px 28px 40px 28px; }
        .main-scroll::-webkit-scrollbar { width: 5px; }
        .main-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 99px; }

        .profile-hero {
            background: var(--black);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 22px;
            position: relative;
            min-height: 170px;
            display: flex;
            align-items: stretch;
        }
        .hero-stripe {
            position: absolute; inset: 0;
            background: repeating-linear-gradient(
                -55deg, transparent, transparent 18px,
                rgba(255,255,255,0.025) 18px, rgba(255,255,255,0.025) 36px
            );
        }
        .hero-accent {
            position: absolute; top: 0; right: 0;
            width: 200px; height: 100%;
            background: var(--orange);
            clip-path: polygon(40% 0, 100% 0, 100% 100%, 0% 100%);
        }
        .hero-green-bar {
            position: absolute; bottom: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--green) 0%, var(--green-dark) 100%);
        }
        .hero-content {
            position: relative; z-index: 2;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            gap: 22px;
            width: 100%;
        }

        .foto-ring {
            width: 88px; height: 88px;
            border-radius: 50%;
            border: 3px solid var(--orange);
            background: var(--white);
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            position: relative;
            cursor: pointer;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.25);
        }
        .foto-ring img { width: 100%; height: 100%; object-fit: cover; }
        .foto-inisial {
            font-size: 2.2rem; font-weight: 800;
            color: var(--green);
            line-height: 1;
        }
        .foto-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,0.45);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: 0.2s; border-radius: 50%;
        }
        .foto-ring:hover .foto-overlay { opacity: 1; }
        .foto-overlay i { color: white; font-size: 1.2rem; }

        .hero-nama { font-size: 1.35rem; font-weight: 800; color: white; line-height: 1.2; }
        .hero-email { font-size: 0.8rem; color: rgba(255,255,255,0.55); margin-top: 3px; }
        .badge-role {
            display: inline-flex; align-items: center; gap: 5px;
            background: var(--orange);
            color: white;
            font-size: 0.7rem; font-weight: 800;
            padding: 3px 10px; border-radius: 20px;
            margin-top: 8px; letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .hero-stats {
            margin-left: auto;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }
        .stat-chip {
            display: flex; align-items: center; gap: 7px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px;
            padding: 5px 13px;
            color: rgba(255,255,255,0.85);
            font-size: 0.75rem; font-weight: 600;
        }
        .stat-chip i { font-size: 0.75rem; color: var(--orange); }

        .form-card {
            background: var(--white);
            border-radius: 16px;
            border: 1.5px solid var(--border);
            overflow: hidden;
            box-shadow: 0 1px 8px rgba(0,0,0,0.04);
        }
        .form-card-header {
            background: linear-gradient(90deg, var(--green) 0%, var(--green-dark) 100%);
            padding: 14px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .form-card-header h6 {
            color: white; font-weight: 700; margin: 0;
            font-size: 0.9rem; display: flex; align-items: center; gap: 8px;
        }
        .lock-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,0.18);
            color: white;
            font-size: 0.7rem; font-weight: 700;
            padding: 3px 10px; border-radius: 20px;
            letter-spacing: 0.3px;
        }
        .unlock-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: var(--orange);
            color: white;
            font-size: 0.7rem; font-weight: 700;
            padding: 3px 10px; border-radius: 20px;
        }

        .form-card-body { padding: 24px; }

        .alert-waktu {
            background: var(--orange-soft);
            border: 1.5px solid #fed7aa;
            border-radius: var(--radius);
            padding: 12px 16px;
            font-size: 0.82rem;
            color: #7c2d12;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .alert-waktu i { color: var(--orange); font-size: 1rem; flex-shrink: 0; }

        .alert-custom {
            border-radius: var(--radius);
            padding: 12px 16px;
            font-size: 0.84rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .alert-success-custom { background: var(--green-soft); border: 1.5px solid var(--green-mid); color: #14532d; }
        .alert-error-custom   { background: #fef2f2; border: 1.5px solid #fecaca; color: #7f1d1d; }

        .field-group { margin-bottom: 16px; }
        .field-label {
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 6px;
            display: flex; align-items: center; gap: 6px;
        }
        .field-label i { color: var(--green); font-size: 0.8rem; }
        .field-input {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            padding: 10px 14px;
            font-family: var(--font);
            font-size: 0.88rem;
            color: var(--black);
            background: var(--white);
            transition: border-color var(--transition), box-shadow var(--transition);
            outline: none;
        }
        .field-input:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(22,163,74,0.1);
        }
        .field-input:disabled,
        .field-input.readonly-field {
            background: var(--light-bg);
            color: #94a3b8;
            cursor: not-allowed;
        }

        .pass-wrap { position: relative; }
        .pass-wrap .field-input { padding-right: 44px; }
        .pass-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #94a3b8;
            cursor: pointer; font-size: 0.95rem; padding: 0; line-height: 1;
            transition: color var(--transition);
        }
        .pass-toggle:hover { color: var(--green); }

        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .file-input-wrap {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 14px 16px;
            text-align: center;
            cursor: pointer;
            transition: border-color var(--transition), background var(--transition);
            position: relative;
        }
        .file-input-wrap:hover { border-color: var(--green); background: var(--green-soft); }
        .file-input-wrap input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .file-input-wrap .file-icon { font-size: 1.4rem; color: var(--green); margin-bottom: 4px; }
        .file-input-wrap p { font-size: 0.8rem; color: var(--gray); margin: 0; font-weight: 600; }
        .file-input-wrap small { font-size: 0.72rem; color: #94a3b8; }

        .divider { border: none; border-top: 1.5px dashed var(--border); margin: 18px 0; }

        .btn-simpan {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, var(--green) 0%, var(--green-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-family: var(--font);
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity var(--transition), box-shadow var(--transition);
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 6px;
            box-shadow: 0 4px 14px rgba(22,163,74,0.3);
        }
        .btn-simpan:hover:not(:disabled) {
            opacity: 0.9;
            box-shadow: 0 6px 18px rgba(22,163,74,0.4);
        }
        .btn-simpan:disabled {
            background: linear-gradient(90deg, var(--orange) 0%, var(--orange-dark) 100%);
            box-shadow: none;
            cursor: not-allowed;
            opacity: 0.75;
        }

        .info-waktu-bawah {
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 10px;
        }
        .info-waktu-bawah strong { color: var(--orange); }
        .field-hint { font-size: 0.72rem; color: #94a3b8; margin-top: 4px; }
    </style>
</head>
<body>
<div class="wrapper-utama">
    <?php include '../assets/sidebar.php'; ?>

    <div class="area-konten-kanan">
        <?php include '../assets/navbar.php'; ?>

        <div class="main-scroll">

            <?php if ($pesan_sukses): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-circle-check"></i> <?= $pesan_sukses ?>
            </div>
            <?php endif; ?>
            <?php if ($pesan_error): ?>
            <div class="alert-custom alert-error-custom">
                <i class="fas fa-circle-xmark"></i> <?= $pesan_error ?>
            </div>
            <?php endif; ?>

            <div class="profile-hero">
                <div class="hero-stripe"></div>
                <div class="hero-accent"></div>
                <div class="hero-green-bar"></div>

                <div class="hero-content">
                    <form method="POST" enctype="multipart/form-data" id="formFotoHero" style="display:contents;">
                        <input type="file" name="foto" id="fotoInputHero" accept="image/*"
                               style="display:none" <?= !$bisa_edit ? 'disabled' : '' ?>
                               onchange="previewFoto(this)">
                        <div class="foto-ring"
                             onclick="<?= $bisa_edit ? "document.getElementById('fotoInputHero').click()" : '' ?>">
                            <?php if ($user['foto'] && file_exists("uploads/" . $user['foto'])): ?>
                                <img src="uploads/<?= $user['foto'] ?>" id="prevFoto" alt="Foto">
                            <?php else: ?>
                                <div class="foto-inisial" id="prevFoto"><?= $inisial ?></div>
                            <?php endif; ?>
                            <?php if ($bisa_edit): ?>
                            <div class="foto-overlay"><i class="fas fa-camera"></i></div>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div>
                        <div class="hero-nama"><?= htmlspecialchars($user['namalengkap'] ?? $user['username']) ?></div>
                        <div class="hero-email"><?= htmlspecialchars($user['email']) ?></div>
                        <div class="badge-role">
                            <i class="fas fa-shield-halved"></i> ID Role: <?= $user['id_role'] ?>
                        </div>
                    </div>

                    <div class="hero-stats d-none d-md-flex">
                        <div class="stat-chip">
                            <i class="fas fa-calendar-check"></i>
                            Anggota sejak <?= date('Y', strtotime($user['created_at'] ?? 'now')) ?>
                        </div>
                        <div class="stat-chip">
                            <i class="fas fa-rotate"></i>
                            Edit tiap <?= $batas_hari ?> hari
                        </div>
                        <?php if (!$bisa_edit): ?>
                        <div class="stat-chip">
                            <i class="fas fa-lock"></i>
                            Terkunci <?= $sisa_hari ?> hari lagi
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-card-header">
                    <h6><i class="fas fa-user-pen"></i> Edit Informasi Profil</h6>
                    <?php if (!$bisa_edit): ?>
                        <span class="lock-badge"><i class="fas fa-lock"></i> Terkunci <?= $sisa_hari ?> hari lagi</span>
                    <?php else: ?>
                        <span class="unlock-badge"><i class="fas fa-unlock"></i> Bisa Diedit</span>
                    <?php endif; ?>
                </div>

                <div class="form-card-body">

                    <?php if (!$bisa_edit): ?>
                    <div class="alert-waktu">
                        <i class="fas fa-clock"></i>
                        <div>
                            Profil tidak bisa diedit saat ini. Tersisa <strong><?= $sisa_hari ?> hari</strong> lagi.&nbsp;
                            <span style="color:#94a3b8;">(<?= ($role_id == 1) ? 'Admin: 30 hari' : 'User: 7 hari' ?> sekali)</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">

                        <?php if ($bisa_edit): ?>
                        <div class="field-group">
                            <div class="field-label"><i class="fas fa-camera"></i> Foto Profil</div>
                            <div class="file-input-wrap">
                                <input type="file" name="foto" accept="image/*" onchange="previewFoto(this)">
                                <div class="file-icon"><i class="fas fa-cloud-arrow-up"></i></div>
                                <p>Klik untuk upload foto</p>
                                <small>JPG, PNG, WEBP · Maks 2MB</small>
                            </div>
                        </div>
                        <hr class="divider">
                        <?php endif; ?>
                        
                        <div class="field-group">
                            <div class="field-label"><i class="fas fa-user-tag"></i> Username Login</div>
                            <input type="text" name="nama"   class="field-input"
                                   value="<?= htmlspecialchars($user['username']) ?>" >
                                   <?= $bisa_edit ? 'disabled' : 'required' ?>
                        </div>
                        <div class="field-row">
                            <div class="field-group">
                                <div class="field-label"><i class="fas fa-user"></i> Nama Lengkap</div>
                                <input type="text" name="nama" class="field-input"
                                       value="<?= htmlspecialchars($user['namalengkap'] ?? '') ?>"
                                       <?= !$bisa_edit ? 'disabled' : 'required' ?>>
                            </div>
                            <div class="field-group">
                                <div class="field-label"><i class="fas fa-envelope"></i> Email</div>
                                <input type="email" name="email" class="field-input"
                                       value="<?= htmlspecialchars($user['email']) ?>"
                                       <?= !$bisa_edit ? 'disabled' : 'required' ?>>
                            </div>
                        </div>

                        
                        <div class="field-row">
                            <div class="field-group">
                                <div class="field-label"><i class="fas fa-id-badge"></i> ID Role</div>
                                <input type="text" class="field-input readonly-field"
                                value="<?= htmlspecialchars($user['id_role']) ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="field-group">
                                
                            <div class="field-label">
                                <i class="fas fa-lock"></i> Password Baru
                                <span style="font-weight:500;text-transform:none;letter-spacing:0;color:#94a3b8;font-size:0.72rem;">
                                    (kosongkan jika tidak ingin ganti)
                                </span>
                            </div>
                            <div class="pass-wrap">
                                <input type="password" name="password" id="passInput"
                                       class="field-input" placeholder="••••••••"
                                       <?= !$bisa_edit ? 'disabled' : '' ?>>
                                <button type="button" class="pass-toggle" onclick="togglePass()">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn-simpan" <?= !$bisa_edit ? 'disabled' : '' ?>>
                            <?php if ($bisa_edit): ?>
                                <i class="fas fa-floppy-disk"></i> Simpan Perubahan
                            <?php else: ?>
                                <i class="fas fa-lock"></i> Tidak Dapat Diedit Saat Ini
                            <?php endif; ?>
                        </button>

                        <?php if ($bisa_edit): ?>
                        <div class="info-waktu-bawah">
                            <i class="fas fa-circle-info"></i>
                            Setelah disimpan, profil baru bisa diedit lagi dalam
                            <strong><?= $batas_hari ?> hari</strong>
                        </div>
                        <?php endif; ?>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewFoto(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const el = document.getElementById('prevFoto');
        if (el.tagName === 'IMG') {
            el.src = e.target.result;
        } else {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.id = 'prevFoto';
            el.replaceWith(img);
        }
    };
    reader.readAsDataURL(input.files[0]);
}

function togglePass() {
    const inp  = document.getElementById('passInput');
    const icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>