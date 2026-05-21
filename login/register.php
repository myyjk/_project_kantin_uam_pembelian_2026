<?php
// Kita sertakan koneksi di bagian paling atas untuk memproses pendaftaran via AJAX
require '../config/config.php';

// Cek apakah ada kiriman data pendaftaran (setelah OTP sukses di frontend)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_register'])) {
    $namalengkap = mysqli_real_escape_string($conn, $_POST['namalengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // Disimpan string biasa sesuai request sebelumnya

    // Cek apakah email sudah terdaftar di db baru
    $cek_email = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
    // Cek juga apakah username sudah dipakai orang lain
    $cek_user = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");

    if (mysqli_num_rows($cek_email) > 0) {
        echo "email_ada";
    } elseif (mysqli_num_rows($cek_user) > 0) {
        echo "username_ada";
    } else {
        // SESUAI STRUKTUR DATABASE BARU (Foto 2):
        // Kolom: id_role (2 = User), username, namalengkap, email, password
        $query = "INSERT INTO users (id_role, username, namalengkap, email, password) 
                  VALUES (2, '$username', '$namalengkap', '$email', '$password')";
        
        if (mysqli_query($conn, $query)) {
            echo "sukses";
        } else {
            echo "gagal";
        }
    }
    exit; // Berhenti di sini agar tidak merender HTML saat proses AJAX
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Kantin UAM</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>

    <style>
        *{ margin:0; padding:0; box-sizing:border-box; }
        html,body{ height:100%; width:100%; overflow:hidden; font-family:'Poppins', sans-serif; background:#fff; }
        .wrapper{ height:100vh; width:100%; }
        .register-side{ display:flex; align-items:center; justify-content:center; background:#ffffff; padding:2rem; z-index: 10; }
        .register-content{ width:100%; max-width:400px; }
        .logo-img{ max-width:110px; margin-bottom:20px; }
        .text-orange-uam{ color:#fd7e14; }
        .text-green-uam{ color:#198754; }
        .btn-uam{ background:#198754; color:white; border:none; border-radius:10px; font-weight:600; padding:14px; width:100%; transition:0.3s; }
        .btn-uam:hover{ background:#146c43; transform:translateY(-2px); box-shadow:0 10px 20px rgba(25,135,84,0.2); color: white; }
        .form-control{ border-radius:8px; padding:12px; background:#f8f9fa; border:1px solid #eee; }
        .form-control:focus{ border-color:#fd7e14; box-shadow:0 0 0 0.25rem rgba(253,126,20,0.1); background:#fff; }
        #otp-box{ display:none; }
        
        /* --- KANAN: VIDEO AREA PREMIUM --- */
        .video-side{ position:relative; width:100%; height:100vh; overflow:hidden; }
        .video-bg{ width:100%; height:100%; object-fit:cover; }
        
        .overlay{ 
            position:absolute; 
            top:0; 
            left:0; 
            width:100%; 
            height:100%; 
            background: radial-gradient(circle at center, rgba(25, 135, 84, 0.1) 0%, rgba(0, 0, 0, 0.7) 100%); 
        }
        
        .brand-top-left {
            position: absolute;
            top: 40px;
            left: 40px;
            color: white;
            animation: fadeInDown 1s ease-out forwards;
            text-align: left;
        }
        .welcome-text {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #fd7e14;
            margin-bottom: 8px;
            font-weight: 600;
            background: rgba(253, 126, 20, 0.15);
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
        }
        .brand-top-left h1 {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 3px;
            margin-bottom: -2px;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        .brand-top-left h2 {
            font-size: 26px;
            font-weight: 600;
            color: #fff;
            opacity: 0.9;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
            margin-bottom: 0;
        }

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
            .register-side{ width:100%; }
            html, body { overflow: auto; }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0 wrapper">
        
        <div class="col-md-5 col-lg-4 register-side">
            <div class="register-content text-center">
                <img src="../img/logouam.png" alt="Logo UAM" class="logo-img">

                <div id="register-form">
                    <h2 class="fw-bold mb-1">Daftar <span class="text-orange-uam">Kantin UAM</span></h2>
                    <p class="text-muted small mb-4">Pastikan email aktif untuk menerima OTP</p>

                    <form onsubmit="handleSendOTP(event)" class="text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" id="regFullName" class="form-control" placeholder="Nama lengkap sesuai identitas" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" id="regUser" class="form-control" placeholder="username_kamu" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" id="regEmail" class="form-control" placeholder="email@gmail.com" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" id="regPass" class="form-control" placeholder="********" required>
                        </div>
                        <button type="submit" id="btnSend" class="btn btn-uam">
                            KIRIM KODE OTP <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </form>
                </div>

                <div id="otp-box">
                    <h2 class="fw-bold mb-1 text-success">Verifikasi OTP</h2>
                    <p class="text-muted small mb-4">Kode dikirim ke <br><b id="displayEmail"></b></p>
                    <form onsubmit="handleVerify(event)">
                        <div class="mb-4">
                            <input type="text" id="inputOTP" class="form-control text-center fw-bold fs-4" placeholder="000000" maxlength="6" required>
                        </div>
                        <button type="submit" id="btnVerify" class="btn btn-uam mb-3">VERIFIKASI & DAFTAR</button>
                        <button type="button" onclick="location.reload()" class="btn btn-link btn-sm w-100 text-decoration-none text-muted">Ganti Email?</button>
                    </form>
                </div>

                <div class="mt-4 pt-4 border-top">
                    <p class="small text-muted">Sudah punya akun? <a href="login.php" class="fw-bold text-orange-uam text-decoration-none">Login</a></p>
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
    
    let hours = String(now.getHours()).padStart(2, '0');
    let minutes = String(now.getMinutes()).padStart(2, '0');
    let seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('live-time').textContent = `${hours}:${minutes}:${seconds}`;
    
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('live-date').textContent = now.toLocaleDateString('id-ID', options);
    
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

setInterval(updateClockAndWelcome, 1000);
updateClockAndWelcome();
</script>

<script>
    (function () {
        emailjs.init("hviXDTQWlixcjmNHD"); 
    })();

    let generatedOTP;

    function handleSendOTP(event){
        event.preventDefault();
        const email = document.getElementById('regEmail').value;
        const name = document.getElementById('regFullName').value;
        const btn = document.getElementById('btnSend');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';

        generatedOTP = Math.floor(100000 + Math.random() * 900000);

        const templateParams = {
            to_name: name,
            to_email: email,
            otp_code: generatedOTP
        };

        emailjs.send("service_2uvebfs", "template_5jpf0kr", templateParams)
        .then(function(response){
            alert("OTP terkirim ke " + email);
            document.getElementById('register-form').style.display = 'none';
            document.getElementById('otp-box').style.display = 'block';
            document.getElementById('displayEmail').innerText = email;
        }, function(error){
            alert("Gagal mengirim email: " + error.text);
            btn.disabled = false;
            btn.innerHTML = 'KIRIM KODE OTP <i class="fas fa-paper-plane ms-2"></i>';
        });
    }

    function handleVerify(event){
        event.preventDefault();
        const userOTP = document.getElementById('inputOTP').value;
        const btnVerif = document.getElementById('btnVerify');

        if(userOTP == generatedOTP){
            btnVerif.disabled = true;
            btnVerif.innerHTML = 'Mendaftarkan...';

            const formData = new FormData();
            formData.append('ajax_register', 'true');
            formData.append('namalengkap', document.getElementById('regFullName').value);
            formData.append('username', document.getElementById('regUser').value);
            formData.append('email', document.getElementById('regEmail').value);
            formData.append('password', document.getElementById('regPass').value);

            fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                if(data === 'sukses'){
                    alert("Pendaftaran Berhasil! Silahkan login.");
                    window.location.href = "login.php";
                } else if(data === 'email_ada'){
                    alert("Email sudah terdaftar! Gunakan email lain.");
                    btnVerif.disabled = false;
                    btnVerif.innerHTML = 'VERIFIKASI & DAFTAR';
                } else if(data === 'username_ada'){
                    alert("Username sudah digunakan! Gunakan username lain.");
                    btnVerif.disabled = false;
                    btnVerif.innerHTML = 'VERIFIKASI & DAFTAR';
                } else {
                    alert("Terjadi kesalahan database.");
                    btnVerif.disabled = false;
                    btnVerif.innerHTML = 'VERIFIKASI & DAFTAR';
                }
            });
        }else{
            alert("Kode OTP salah!");
        }
    }
</script>

</body>
</html>