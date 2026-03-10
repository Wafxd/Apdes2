<?php
// includes/auth_check.php
// File ini untuk mengecek apakah user sudah login

if (!isset($_SESSION['id_admin']) || !isset($_SESSION['nama_admin'])) {
    // Deteksi posisi file untuk redirect
    $current_file = $_SERVER['SCRIPT_NAME'];
    
    // Tambahkan kondisi untuk folder /informasi/ agar sejajar dengan /surat/ dan /landing/
    if (strpos($current_file, '/surat/') !== false || 
        strpos($current_file, '/landing/') !== false || 
        strpos($current_file, '/informasi/') !== false) {
        header("Location: ../../login.php");
    } elseif (strpos($current_file, '/admin/') !== false) {
        header("Location: ../login.php");
    } else {
        header("Location: login.php");
    }
    exit();
}
?>