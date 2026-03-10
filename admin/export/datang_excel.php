<?php
session_start();

// Pengecekan sesi login
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../../login.php");
    exit();
}

// Path mundur 2 kali karena file berada di folder admin/export/
include "../../db/koneksi.php";

// Nama file yang terdownload otomatis
$filename = "Laporan_Kedatangan_Penduduk_" . date('Ymd_His') . ".xls";

// Memaksa browser mendownload file sebagai Excel
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil semua data arsip kedatangan dengan JOIN ke tabel penduduk untuk alamat barunya
$query = "SELECT d.*, p.dusun, p.rt_rw, p.alamat AS alamat_baru 
          FROM kedatangan d 
          LEFT JOIN penduduk p ON d.nik_datang = p.nik 
          ORDER BY d.tanggal_datang DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Data Kedatangan</title>
    <style>
        /* Styling CSS ini akan di-render langsung oleh Microsoft Excel */
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11pt; }
        th, td { border: 1px solid #000000; padding: 6px 10px; vertical-align: middle; }
        /* Warna Biru Primary agar senada dengan tema halaman Datang */
        th { background-color: #4e73df; color: #ffffff; text-align: center; font-weight: bold; } 
        .text-center { text-align: center; }
        /* Trik penting agar angka NIK 16 digit tidak rusak/berubah menjadi E+15 di Excel */
        .text-string { mso-number-format: "\@"; }
        
        .header-title { font-size: 15pt; font-weight: bold; text-align: center; border: none !important; }
        .header-subtitle { font-size: 12pt; font-weight: bold; text-align: center; border: none !important; }
        .no-border { border: none !important; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="10" class="header-title">LAPORAN ARSIP PENDUDUK DATANG (MASUK)</td></tr>
        <tr><td colspan="10" class="header-subtitle">DESA SUKOLILO TIMUR, KEC. LABANG, KAB. BANGKALAN</td></tr>
        <tr><td colspan="10" class="text-center no-border">Tanggal Diunduh: <?php echo date('d M Y - H:i'); ?> WIB</td></tr>
        <tr><td colspan="10" class="no-border"></td></tr> <tr>
            <th>No</th>
            <th>NIK Pendatang</th>
            <th>Nama Lengkap</th>
            <th>Jenis Kelamin</th>
            <th>Tanggal Datang</th>
            <th>Alamat Asal (Sebelumnya)</th>
            <th>Alasan Pindah / Datang</th>
            <th>Alamat Tujuan (Di Desa Ini)</th>
            <th>Dusun Tujuan</th>
            <th>Waktu Dicatat</th>
        </tr>
        
        <?php 
        if (mysqli_num_rows($result) > 0): 
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)): 
                // Susun alamat baru di desa
                $alamat_desa = ($row['alamat_baru'] ?? '') . ' RT/RW ' . ($row['rt_rw'] ?? '');
        ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-string"><?php echo htmlspecialchars($row['nik_datang']); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['nama_datang'])); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['jenis_kelamin']); ?></td>
                <td class="text-center"><?php echo date('d-m-Y', strtotime($row['tanggal_datang'])); ?></td>
                
                <td><?php echo strtoupper(htmlspecialchars($row['alamat_asal'])); ?></td>
                <td class="text-center"><?php echo strtoupper(htmlspecialchars($row['alasan_datang'] ?? '-')); ?></td>
                
                <td><?php echo strtoupper(htmlspecialchars($alamat_desa)); ?></td>
                <td class="text-center"><?php echo strtoupper(htmlspecialchars($row['dusun'] ?? '-')); ?></td>
                
                <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
            </tr>
        <?php 
            endwhile; 
        else: 
        ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada arsip data kedatangan yang ditemukan.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>