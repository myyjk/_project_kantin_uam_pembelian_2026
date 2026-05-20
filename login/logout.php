<?php
session_start();

// Hapus semua data session
session_unset();

// Hancurkan session
session_destroy();

// Hapus cookie session jika ada (opsional untuk keamanan tambahan)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Tendang balik ke halaman login
header("Location: login.php");
exit();
?>