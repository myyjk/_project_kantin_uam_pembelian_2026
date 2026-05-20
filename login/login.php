<?php
session_start();

// --- KONFIGURASI DATABASE ---
include '../config/config.php';

// Ensure $conn is defined. If config.php didn't set it, try a default local connection.
if (!isset($conn) || !$conn) {
    $conn = mysqli_connect('localhost', 'root', '', ''); // adjust DB name if needed
    if (!$conn) {
        die("Database connection not found and default connection failed.");
    }
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $userIn = mysqli_real_escape_string($conn, $_POST['username']);
    $passIn = $_POST['password'];
    $keyIn  = mysqli_real_escape_string($conn, $_POST['accessKey']);

    // Cari di database berdasarkan nama atau email
    $query = "SELECT u.*, r.kode as koderole, r.nama as namarole FROM users u left join roles r on u.id_role = r.id WHERE username  = '$userIn' OR email = '$userIn' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        // die(print_r($row));
        // Cek Password
        if ( password_verify($passIn,$row['password'])) {
            

        switch ($row['koderole']) {
            case 'admin':
            case 'kasir':
                $_SESSION['isLoggedIn'] = true;
                $_SESSION['koderole'] = $row['koderole'];
                $_SESSION['namarole'] = $row['namarole'];
                $_SESSION['currentUser'] = $row;
                if ($row['koderole'] === 'admin')
                    header("Location: admin_dashboard.php");
                 else
                    header("Location: ../pembeli/pembelian.php");
                exit();
                break;
            
            default:
                 $error_message = "Salah role";
                break;
        }

            // --- LOGIKA PEMISAH FITUR ---
            
            // 1. Jika User memasukkan Access Key
            // if (!empty($keyIn)) {
            //     if ($row['koderole'] === 'admin' /* && $row['access_key'] === $keyIn */) {
            //         // Berhasil sebagai Admin
            //         $_SESSION['isLoggedIn'] = true;
            //         $_SESSION['koderole'] = $row['koderole'];
            //         $_SESSION['namarole'] = $row['namarole'];
            //         $_SESSION['currentUser'] = $row;
            //         header("Location: admin_dashboard.php");
            //         exit();
            //     }
            // // } 
            // // // 2. Jika Access Key dikosongkan (Login sebagai Pembeli Biasa)
            // // else {
            //     else if ($row['koderole'] === 'kasir') {
            //         // Berhasil sebagai Pembeli
            //         $_SESSION['isLoggedIn'] = true;
            //         $_SESSION['koderole'] = $row['koderole'];
            //         $_SESSION['namarole'] = $row['namarole'];
            //         $_SESSION['currentUser'] = $row;
            //         header("Location: ../pembeli/pembelian.php");
            //         exit();
            //     } else {
            //         $error_message = "Akun Admin wajib memasukkan Access Key!";
            //     }
            // }
        } else {
            $error_message = "Gagal Login: Password salah!";
        }
    } else {
        $error_message = "Gagal Login: Nama User/Email tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kantin UAM</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        *{ margin:0; padding:0; box-sizing:border-box; }
        html, body{ width:100%; height:100%; font-family:'Poppins', sans-serif; overflow:hidden; background:#fff; }
        .wrapper{ height:100vh; }
        .login-side{ display:flex; justify-content:center; align-items:center; background:#fff; padding:2rem; z-index: 10; }
        .login-content{ width:100%; max-width:400px; }
        .logo-img{ max-width:110px; margin-bottom:20px; }
        .text-orange-uam{ color:#fd7e14; }
        .text-green-uam{ color:#198754; }
        .btn-uam{ background:#198754; color:#fff; border:none; border-radius:10px; padding:14px; font-weight:600; transition:0.3s; width: 100%; }
        .btn-uam:hover{ background:#146c43; transform:translateY(-2px); box-shadow:0 10px 20px rgba(25,135,84,0.2); color: white; }
        .form-control{ border-radius:8px; padding:12px; background:#f8f9fa; border:1px solid #eee; }
        .form-control:focus{ border-color:#fd7e14; box-shadow:0 0 0 0.25rem rgba(253,126,20,0.1); background:#fff; }
        .input-group-text{ background:#f8f9fa; border:1px solid #eee; color:#ccc; }
        .optional-badge{ font-size:10px; background:#e9ecef; color:#6c757d; padding:2px 6px; border-radius:4px; margin-left:5px; text-transform:uppercase; }
        
        /* --- KANAN: VIDEO AREA PREMIUM --- */
        .video-side{ position:relative; width:100%; height:100vh; overflow:hidden; }
        .video-bg{ width:100%; height:100%; object-fit:cover; }
        
        /* Overlay Vinyet Sinematik */
        .overlay{ 
            position:absolute; 
            top:0; 
            left:0; 
            width:100%; 
            height:100%; 
            background: radial-gradient(circle at center, rgba(25, 135, 84, 0.1) 0%, rgba(0, 0, 0, 0.7) 100%); 
        }
        
        /* 1. KIRI ATAS: Identitas Kampus + Ucapan Waktu Dinamis */
        .brand-top-left {
            position: absolute;
            top: 40px;
            left: 40px;
            color: white;
            animation: fadeInDown 1s ease-out forwards;
        }
        .welcome-text {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #fd7e14;
            margin-bottom: 5px;
            font-weight: 600;
            background: rgba(253, 126, 20, 0.15);
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
        }
        .brand-top-left h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 3px;
            margin-bottom: 0;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        .brand-top-left h2 {
            font-size: 24px;
            font-weight: 600;
            color: #fff;
            opacity: 0.9;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        .brand-top-left p {
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: 0.6;
            margin-top: 5px;
        }

        /* 2. KANAN ATAS: Live Digital Clock Widget */
        .clock-widget {
            position: absolute;
            top: 40px;
            right: 40px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 10px 20px;
            border-radius: 14px;
            color: white;
            text-align: right;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: fadeInDown 1s ease-out forwards;
        }
        #live-time {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #fff;
        }
        #live-date {
            font-size: 10px;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* 3. KANAN BAWAH: Dock Menu Sosmed Kapsul */
        .social-dock-bottom {
            position: absolute;
            bottom: 50px;
            right: 40px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 12px 20px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: fadeInUp 1s ease-out forwards;
        }
        .dock-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 600;
            border-right: 1px solid rgba(255,255,255,0.2);
            padding-right: 15px;
        }
        .social-links { display: flex; gap: 10px; }
        .social-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .social-btn:hover {
            color: #fff;
            transform: translateY(-4px) scale(1.15);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .social-btn.btn-web:hover { background: #198754; border-color: #198754; }
        .social-btn.btn-ig:hover { background: #e1306c; border-color: #e1306c; }
        .social-btn.btn-yt:hover { background: #ff0000; border-color: #ff0000; }
        .social-btn.btn-tt:hover { background: #000000; border-color: #fff; }

        /* 4. PALING BAWAH: Running Text Info Marquee */
        .info-marquee {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            color: rgba(255,255,255,0.8);
            font-size: 11px;
            padding: 6px 0;
            letter-spacing: 1px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* KEYFRAMES ANIMASI */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width:768px){ 
            .video-side{ display:none; } 
            .login-side{ width:100%; } 
            html, body { overflow: auto; }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0 wrapper">
        
        <div class="col-md-5 col-lg-4 login-side">
            <div class="login-content text-center">
                <img src="../img/logouam.png" alt="Logo UAM" class="logo-img">
                <h2 class="fw-bold mb-1">Kantin <span class="text-orange-uam">UAM</span></h2>
                <p class="text-muted small mb-4">Silahkan masuk ke akun anda</p>

                <?php if($error_message != ""): ?>
                    <div class="alert alert-danger small py-2"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form action="../login/login.php" method="POST" class="text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama User / Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan nama user atau email" required >
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" >
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Access Key <span class="optional-badge">Opsional</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="text" name="accessKey" class="form-control" placeholder="Masukkan key (jika admin)">
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-uam">
                            LOGIN SEKARANG <i class="fas fa-sign-in-alt ms-2"></i>
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="#" class="text-decoration-none small text-orange-uam">Lupa password?</a>
                    </div>
                </form>

                <div class="mt-4 pt-4 border-top">
                    <p class="small text-muted">Belum punya akun? <a href="../login/register.php" class="fw-bold text-green-uam text-decoration-none">Daftar Akun</a></p>
                </div>
            </div>
        </div>

        <div class="col-md-7 col-lg-8 p-0">
            <div class="video-side">
                <video autoplay muted loop playsinline class="video-bg">
                    <source src="../video/uam.mp4" type="video/mp4">
                </video>
                <div class="overlay"></div>
                
                <div class="brand-top-left">
                    <div class="welcome-text" id="welcome-shift">Welcome</div>
                    <h1>UNIVERSITAS</h1>
                    <h2>ANWAR MEDIKA</h2>
                    <p>Smart • Modern • Islamic University</p>
                </div>
                
                <div class="clock-widget">
                    <div id="live-time">00:00:00</div>
                    <div id="live-date">Loading date...</div>
                </div>
                
                <div class="social-dock-bottom">
                    <span class="dock-label">Hubungi Kami</span>
                    <div class="social-links">
                        <a href="https://www.uam.ac.id/" class="social-btn btn-web" title="Website Resmi UAM" target="_blank">
                            <i class="fas fa-globe"></i>
                        </a>
                        <a href="https://www.instagram.com/uamcampus?igsh=MTY0N2NzdzlqZ3FmNw==" class="social-btn btn-ig" title="Instagram UAM" target="_blank">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://www.youtube.com/@universitasanwarmedika" class="social-btn btn-yt" title="YouTube UAM" target="_blank">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="https://www.tiktok.com/@uamcampus?is_from_webapp=1&sender_device=pc" class="social-btn btn-tt" title="TikTok UAM" target="_blank">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                </div>

                <div class="info-marquee">
                    <marquee scrollamount="4">Selamat Datang di Aplikasi E-Kantin Universitas Anwar Medika (UAM) • Nikmati kemudahan memesan makanan & minuman favoritmu secara cerdas dan modern! • Cek promo spesial harian di dalam aplikasi setelah login.</marquee>
                </div>

            </div>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function updateClockAndWelcome() {
    const now = new Date();
    
    // Format Jam Digital
    let hours = String(now.getHours()).padStart(2, '0');
    let minutes = String(now.getMinutes()).padStart(2, '0');
    let seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('live-time').textContent = `${hours}:${minutes}:${seconds}`;
    
    // Format Tanggal Indonesia
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('live-date').textContent = now.toLocaleDateString('id-ID', options);
    
    // Logika Ganti Ucapan Otomatis Berdasarkan Waktu Jam Berjalan
    let currentHour = now.getHours();
    let greet = "";
    if (currentHour >= 5 && currentHour < 11) {
        greet = "Selamat Pagi ✨";
    } else if (currentHour >= 11 && currentHour < 15) {
        greet = "Selamat Siang 🔥";
    } else if (currentHour >= 15 && currentHour < 18) {
        greet = "Selamat Sore 🌤️";
    } else {
        greet = "Selamat Malam 🌙";
    }
    document.getElementById('welcome-shift').textContent = greet;
}

// Jalankan fungsi setiap 1 detik sekali secara real-time
setInterval(updateClockAndWelcome, 1000);
updateClockAndWelcome(); // Panggil sekali di awal load
</script>

</body>
</html>