<?php
// 1. Include file fungsi Anda
include "db/funct.php"; // Sesuaikan path jika file ini ada di folder berbeda

// 2. Cek apakah ada NIK yang dikirim lewat URL (metode GET)
if (isset($_GET['nik'])) {
    
    // 3. Ambil NIK dari URL
    $nik_yang_mau_dihapus = $_GET['nik'];
    
    // 4. Panggil fungsi hapus_penduduk()
    if (hapus_penduduk($nik_yang_mau_dihapus) > 0) {
        // 5. Jika sukses (fungsi mengembalikan > 0)
        echo "<script>
                alert('Data penduduk berhasil dihapus');
                window.location.href = 'penduduk.php';
              </script>";
    } else {
        // 6. Jika gagal
        echo "<script>
                alert('Gagal menghapus data penduduk');
                window.location.href = 'penduduk.php';
              </script>";
    }

} else {
    // 7. Jika file ini diakses tanpa NIK di URL
    echo "<script>
            alert('Aksi tidak valid. NIK tidak ditemukan.');
            window.location.href = 'penduduk.php';
          </script>";
}

?>