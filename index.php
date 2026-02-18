<?php
// Mulai session dengan aman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login berdasarkan session yang ada di login.php
$isLoggedIn = isset($_SESSION['id_admin']) && isset($_SESSION['nama_admin']);

// Session timeout check (30 menit)
if ($isLoggedIn && isset($_SESSION['LAST_ACTIVITY'])) {
    $timeout = 30 * 60; // 30 menit dalam detik
    if (time() - $_SESSION['LAST_ACTIVITY'] > $timeout) {
        // Session expired
        session_unset();
        session_destroy();
        $isLoggedIn = false;
    }
}

// Update last activity jika sudah login
if ($isLoggedIn) {
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Redirect berdasarkan status login
if ($isLoggedIn) {
    // Sudah login -> ke dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Belum login -> ke login page
    header("Location: login.php");
    exit();
}
?>