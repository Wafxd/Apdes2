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
$filename = "Laporan_Kematian_Desa_" . date('Ymd_His') . ".xls";

// Memaksa browser mendownload file sebagai Excel
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil semua data arsip kematian dari database
$query = "SELECT * FROM kematian ORDER BY tanggal_kematian DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Data Kematian</title>
    <style>
        /* Styling CSS ini akan di-render langsung oleh Microsoft Excel */
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11pt; }
        th, td { border: 1px solid #000000; padding: 6px 10px; vertical-align: middle; }
        /* Menggunakan warna abu-abu gelap agar senada dengan tema Kematian */
        th { background-color: #5a5c69; color: #ffffff; text-align: center; font-weight: bold; } 
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
        <tr><td colspan="13" class="header-title">LAPORAN ARSIP DATA KEMATIAN PENDUDUK</td></tr>
        <tr><td colspan="13" class="header-subtitle">DESA SUKOLILO TIMUR, KEC. LABANG, KAB. BANGKALAN</td></tr>
        <tr><td colspan="13" class="text-center no-border">Tanggal Diunduh: <?php echo date('d M Y - H:i'); ?> WIB</td></tr>
        <tr><td colspan="13" class="no-border"></td></tr> <tr>
            <th>No</th>
            <th>NIK Jenazah</th>
            <th>Nama Lengkap</th>
            <th>Tempat, Tgl Lahir</th>
            <th>Jenis Kelamin</th>
            <th>Agama</th>
            <th>Alamat Terakhir</th>
            <th>Tanggal Wafat</th>
            <th>Waktu Wafat</th>
            <th>Tempat Meninggal</th>
            <th>Sebab Kematian</th>
            <th>Nama Pelapor</th>
            <th>Hubungan Pelapor</th>
        </tr>
        
        <?php 
        if (mysqli_num_rows($result) > 0): 
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)): 
                // Format Tempat, Tanggal lahir agar rapi
                $ttl = strtoupper($row['tempat_lahir'] ?? '-') . ', ' . ($row['tanggal_lahir'] ? date('d-m-Y', strtotime($row['tanggal_lahir'])) : '-');
                // Format Waktu Wafat
                $waktu = $row['waktu_kematian'] ? date('H:i', strtotime($row['waktu_kematian'])) . ' WIB' : '-';
        ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-string"><?php echo htmlspecialchars($row['nik_jenazah']); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['nama_jenazah'])); ?></td>
                <td><?php echo htmlspecialchars($ttl); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['jenis_kelamin']); ?></td>
                <td class="text-center"><?php echo strtoupper(htmlspecialchars($row['agama'] ?? '-')); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['alamat'] ?? '-')); ?></td>
                
                <td class="text-center"><?php echo date('d-m-Y', strtotime($row['tanggal_kematian'])); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($waktu); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['tempat_kematian'])); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['penyebab_kematian'])); ?></td>
                
                <td><?php echo strtoupper(htmlspecialchars($row['nama_pelapor'] ?: '-')); ?></td>
                <td class="text-center"><?php echo strtoupper(htmlspecialchars($row['hubungan_pelapor'] ?: '-')); ?></td>
            </tr>
        <?php 
            endwhile; 
        else: 
        ?>
            <tr>
                <td colspan="13" class="text-center">Tidak ada arsip data kematian yang ditemukan.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>