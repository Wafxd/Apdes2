<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../../login.php");
    exit();
}

include "../../db/koneksi.php";

$filename = "Laporan_Kelahiran_Desa_" . date('Ymd_His') . ".xls";

// Memaksa browser mendownload file sebagai Excel
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil data lengkap (JOIN ke tabel penduduk untuk Dusun, RT, dan Alamat)
$query = "SELECT k.*, p.agama, p.dusun, p.rt_rw, p.alamat 
          FROM kelahiran k 
          LEFT JOIN penduduk p ON k.nik_bayi = p.nik 
          ORDER BY k.tanggal_lahir DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Data Kelahiran</title>
    <style>
        /* Tampilan akan dibaca oleh Excel secara langsung */
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11pt; }
        th, td { border: 1px solid #000000; padding: 6px 10px; vertical-align: middle; }
        th { background-color: #1cc88a; color: #ffffff; text-align: center; font-weight: bold; }
        .text-center { text-align: center; }
        /* Trik agar NIK 16 digit tidak berubah jadi angka error (E+15) */
        .text-string { mso-number-format: "\@"; }
        .header-title { font-size: 15pt; font-weight: bold; text-align: center; border: none !important; }
        .header-subtitle { font-size: 12pt; font-weight: bold; text-align: center; border: none !important; }
        .no-border { border: none !important; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="15" class="header-title">LAPORAN DATA KELAHIRAN PENDUDUK</td></tr>
        <tr><td colspan="15" class="header-subtitle">DESA SUKOLILO TIMUR, KEC. LABANG, KAB. BANGKALAN</td></tr>
        <tr><td colspan="15" class="text-center no-border">Tanggal Diunduh: <?php echo date('d M Y - H:i'); ?> WIB</td></tr>
        <tr><td colspan="15" class="no-border"></td></tr>
        
        <tr>
            <th>No</th>
            <th>NIK Bayi</th>
            <th>Nama Lengkap Bayi</th>
            <th>Tempat Lahir</th>
            <th>Tanggal Lahir</th>
            <th>Jenis Kelamin</th>
            <th>Agama</th>
            <th>Anak Ke</th>
            <th>Berat (Kg)</th>
            <th>Panjang (Cm)</th>
            <th>Nama Ayah</th>
            <th>Nama Ibu</th>
            <th>Dusun</th>
            <th>RT/RW</th>
            <th>Alamat Lengkap</th>
        </tr>
        <?php 
        if (mysqli_num_rows($result) > 0): 
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)): 
        ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-string"><?php echo htmlspecialchars($row['nik_bayi']); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['nama_bayi'])); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['tempat_lahir'])); ?></td>
                <td class="text-center"><?php echo date('d-m-Y', strtotime($row['tanggal_lahir'])); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['jenis_kelamin']); ?></td>
                <td class="text-center"><?php echo strtoupper(htmlspecialchars($row['agama'] ?? '-')); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['anak_ke'] ?: '-'); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['berat_bayi'] ?: '-'); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['panjang_bayi'] ?: '-'); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['nama_ayah'] ?: '-')); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['nama_ibu'] ?: '-')); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['dusun'] ?: '-')); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['rt_rw'] ?: '-'); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['alamat'] ?: '-')); ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="15" class="text-center">Tidak ada data kelahiran yang ditemukan.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>