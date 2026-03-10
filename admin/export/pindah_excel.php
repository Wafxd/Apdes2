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
$filename = "Laporan_Pindah_Keluar_Desa_" . date('Ymd_His') . ".xls";

// Memaksa browser mendownload file sebagai Excel
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil semua data arsip pindah dari database
$query = "SELECT * FROM pindah ORDER BY tanggal_pindah DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Data Pindah Keluar</title>
    <style>
        /* Styling CSS ini akan di-render langsung oleh Microsoft Excel */
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11pt; }
        th, td { border: 1px solid #000000; padding: 6px 10px; vertical-align: middle; }
        /* Warna Biru Cyan/Teal (Info) agar senada dengan tema halaman Pindah */
        th { background-color: #36b9cc; color: #ffffff; text-align: center; font-weight: bold; } 
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
        <tr><td colspan="12" class="header-title">LAPORAN ARSIP MUTASI PINDAH KELUAR PENDUDUK</td></tr>
        <tr><td colspan="12" class="header-subtitle">DESA SUKOLILO TIMUR, KEC. LABANG, KAB. BANGKALAN</td></tr>
        <tr><td colspan="12" class="text-center no-border">Tanggal Diunduh: <?php echo date('d M Y - H:i'); ?> WIB</td></tr>
        <tr><td colspan="12" class="no-border"></td></tr> <tr>
            <th>No</th>
            <th>NIK Warga</th>
            <th>Nama Lengkap</th>
            <th>Tempat, Tgl Lahir</th>
            <th>Jenis Kelamin</th>
            <th>Agama</th>
            <th>Alamat Asal (Desa)</th>
            <th>Tanggal Pindah</th>
            <th>Alamat Tujuan Pindah</th>
            <th>Alasan Pindah</th>
            <th>Keterangan Tambahan</th>
            <th>Waktu Dicatat</th>
        </tr>
        
        <?php 
        if (mysqli_num_rows($result) > 0): 
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)): 
                // Format Tempat, Tanggal lahir agar rapi
                $ttl = strtoupper($row['tempat_lahir'] ?? '-') . ', ' . ($row['tanggal_lahir'] ? date('d-m-Y', strtotime($row['tanggal_lahir'])) : '-');
        ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-string"><?php echo htmlspecialchars($row['nik_pindah']); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['nama_pindah'])); ?></td>
                <td><?php echo htmlspecialchars($ttl); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['jenis_kelamin']); ?></td>
                <td class="text-center"><?php echo strtoupper(htmlspecialchars($row['agama'] ?? '-')); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['alamat_asal'] ?? '-')); ?></td>
                
                <td class="text-center"><?php echo date('d-m-Y', strtotime($row['tanggal_pindah'])); ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['alamat_tujuan'])); ?></td>
                <td class="text-center"><?php echo strtoupper(htmlspecialchars($row['alasan_pindah'])); ?></td>
                <td><?php echo htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
                <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
            </tr>
        <?php 
            endwhile; 
        else: 
        ?>
            <tr>
                <td colspan="12" class="text-center">Tidak ada arsip data warga pindah yang ditemukan.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>