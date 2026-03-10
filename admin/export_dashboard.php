<?php
session_start();
error_reporting(0); // MENCEGAH WARNING/NOTICE MERUSAK FORMAT EXCEL
ini_set('memory_limit', '-1'); 
set_time_limit(0); 

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

include "../db/koneksi.php";

$type = isset($_GET['type']) ? $_GET['type'] : 'multiple';
if (!in_array($type, ['single', 'multiple'])) {
    $type = 'multiple';
}

$use_phpspreadsheet = false;
$autoload_paths = [
    '../vendor/autoload.php',
    'vendor/autoload.php'
];

foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $use_phpspreadsheet = true;
        }
        break;
    }
}

if (!$use_phpspreadsheet) {
    die("<script>alert('Library PhpSpreadsheet tidak tersedia. Hubungi Administrator.'); window.close();</script>");
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// FUNGSI BANTUAN
function tgl_indonesia($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    return $tgl . ' ' . $bln . ' ' . $thn;
}

function hitungUmur($tanggal_lahir) {
    if (empty($tanggal_lahir)) return 0;
    try {
        $birthDate = new DateTime($tanggal_lahir);
        $today = new DateTime();
        return $today->diff($birthDate)->y;
    } catch (Exception $e) {
        return 0;
    }
}

function getCountData($conn, $query) {
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row && isset($row['total'])) return (int)$row['total'];
    }
    return 0;
}

$excelCols = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

$daftar_dusun = ['KEJAWAN', 'SEPURAN', 'BUDDAN', 'PASEREAN', 'LANGGAR', 'MORLEKE', 'PREGIH', 'KARANG PANDAN', 'PONG BARU', 'KRASAK', 'PERUM BASMALAH'];

// 1. Statistik Umum
$total_penduduk = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk");
$total_kk = getCountData($conn, "SELECT COUNT(*) as total FROM kartu_keluarga");
$total_laki = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'LAKI-LAKI'");
$total_perempuan = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'PEREMPUAN'");
$total_surat = getCountData($conn, "SELECT COUNT(*) as total FROM arsip_surat");

$bulan_ini = date('m');
$tahun_ini = date('Y');
$tot_kelahiran = getCountData($conn, "SELECT COUNT(*) as total FROM kelahiran");
$bln_kelahiran = getCountData($conn, "SELECT COUNT(*) as total FROM kelahiran WHERE MONTH(tanggal_lahir) = '$bulan_ini' AND YEAR(tanggal_lahir) = '$tahun_ini'");
$tot_kematian = getCountData($conn, "SELECT COUNT(*) as total FROM kematian");
$bln_kematian = getCountData($conn, "SELECT COUNT(*) as total FROM kematian WHERE MONTH(tanggal_kematian) = '$bulan_ini' AND YEAR(tanggal_kematian) = '$tahun_ini'");
$tot_datang = getCountData($conn, "SELECT COUNT(*) as total FROM kedatangan");
$bln_datang = getCountData($conn, "SELECT COUNT(*) as total FROM kedatangan WHERE MONTH(tanggal_datang) = '$bulan_ini' AND YEAR(tanggal_datang) = '$tahun_ini'");
$tot_pindah = getCountData($conn, "SELECT COUNT(*) as total FROM pindah");
$bln_pindah = getCountData($conn, "SELECT COUNT(*) as total FROM pindah WHERE MONTH(tanggal_pindah) = '$bulan_ini' AND YEAR(tanggal_pindah) = '$tahun_ini'");

// 2. Data Per Dusun
$data_dusun = [];
foreach ($daftar_dusun as $dsn) {
    $d_esc = mysqli_real_escape_string($conn, $dsn);
    $data_dusun[$dsn] = [
        'laki' => getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE dusun = '$d_esc' AND jenis_kelamin = 'LAKI-LAKI'"),
        'perempuan' => getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE dusun = '$d_esc' AND jenis_kelamin = 'PEREMPUAN'"),
        'total' => getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE dusun = '$d_esc'"),
        'kk' => getCountData($conn, "SELECT COUNT(*) as total FROM kartu_keluarga WHERE dusun = '$d_esc'")
    ];
}

// 3. Matriks Umur
$rentang_umur = [
    '0-5 (Balita)' => [0, 5], '6-12 (Anak)' => [6, 12], '13-17 (Remaja)' => [13, 17], 
    '18-25 (Pemuda)' => [18, 25], '26-35 (Dewasa)' => [26, 35], '36-45 (Dewasa Tua)' => [36, 45], 
    '46-55 (Pra-Lansia)' => [46, 55], '56-65 (Lansia)' => [56, 65], '65+ (Manula)' => [66, 150]
];
$data_umur = [];
foreach ($rentang_umur as $rentang => $range) {
    $data_umur[$rentang] = ['total' => 0, 'per_dusun' => []];
    foreach ($daftar_dusun as $dsn) $data_umur[$rentang]['per_dusun'][$dsn] = 0;
}
$query_penduduk_umur = mysqli_query($conn, "SELECT nik, tanggal_lahir, dusun FROM penduduk");
if($query_penduduk_umur) {
    while ($row = mysqli_fetch_assoc($query_penduduk_umur)) {
        $umur_p = hitungUmur($row['tanggal_lahir']);
        $dsn_p = $row['dusun'] ?: 'TANPA DUSUN';
        foreach ($rentang_umur as $rentang => $range) {
            if ($umur_p >= $range[0] && $umur_p <= $range[1]) {
                $data_umur[$rentang]['total']++;
                if (isset($data_umur[$rentang]['per_dusun'][$dsn_p])) $data_umur[$rentang]['per_dusun'][$dsn_p]++;
                break;
            }
        }
    }
}

// 4. Data Status Kawin & Agama
$status_kawin_list = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
$data_status = [];
foreach ($status_kawin_list as $status) $data_status[$status] = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE status_kawin = '".mysqli_real_escape_string($conn, $status)."'");

$agama_list = ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDDHA', 'KONGHUCU'];
$data_agama = [];
foreach ($agama_list as $agama) $data_agama[$agama] = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE agama = '".mysqli_real_escape_string($conn, $agama)."'");

// 5. Matriks Pekerjaan (DIPERBAIKI)
$pekerjaan_list = ['PNS', 'TNI', 'POLRI', 'PEGAWAI SWASTA', 'WIRASWASTA', 'PETANI', 'BURUH', 'PELAJAR/MAHASISWA', 'IRT', 'PENSIUNAN'];
$data_pekerjaan_dusun = []; 
foreach ($pekerjaan_list as $pkj) {
    $data_pekerjaan_dusun[$pkj] = ['total' => 0, 'per_dusun' => []];
    foreach ($daftar_dusun as $dsn) $data_pekerjaan_dusun[$pkj]['per_dusun'][$dsn] = 0;
}
$data_pekerjaan_dusun['Lainnya'] = ['total' => 0, 'per_dusun' => []];
foreach ($daftar_dusun as $dsn) $data_pekerjaan_dusun['Lainnya']['per_dusun'][$dsn] = 0;

$q_pkj = mysqli_query($conn, "SELECT pekerjaan, dusun FROM penduduk");
if($q_pkj) {
    while ($row = mysqli_fetch_assoc($q_pkj)) {
        $pkj = trim(strtoupper($row['pekerjaan']));
        $dsn = $row['dusun'] ?: 'TANPA DUSUN';
        $key = in_array($pkj, $pekerjaan_list) ? $pkj : 'Lainnya';
        $data_pekerjaan_dusun[$key]['total']++;
        if (isset($data_pekerjaan_dusun[$key]['per_dusun'][$dsn])) $data_pekerjaan_dusun[$key]['per_dusun'][$dsn]++;
    }
}

// 6. Matriks Pendidikan (DIPERBAIKI)
$pendidikan_list = ['TIDAK SEKOLAH', 'SD', 'SMP', 'SMA', 'SMK', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'];
$data_pendidikan_dusun = [];
foreach ($pendidikan_list as $pend) {
    $data_pendidikan_dusun[$pend] = ['total' => 0, 'per_dusun' => []];
    foreach ($daftar_dusun as $dsn) $data_pendidikan_dusun[$pend]['per_dusun'][$dsn] = 0;
}
$q_edu = mysqli_query($conn, "SELECT pendidikan, dusun FROM penduduk");
if($q_edu) {
    while ($row = mysqli_fetch_assoc($q_edu)) {
        $pend = trim(strtoupper($row['pendidikan']));
        if(empty($pend) || !in_array($pend, $pendidikan_list)) $pend = 'TIDAK SEKOLAH'; 
        $dsn = $row['dusun'] ?: 'TANPA DUSUN';
        $data_pendidikan_dusun[$pend]['total']++;
        if(isset($data_pendidikan_dusun[$pend]['per_dusun'][$dsn])) $data_pendidikan_dusun[$pend]['per_dusun'][$dsn]++;
    }
}

// 7. Rekap Surat Keluar
$jenis_surat_list = ['SKD', 'SKTM', 'SKU', 'SKKe', 'SKL', 'SKKM', 'SK'];
$jenis_nama = ['SKD'=>'Ket. Domisili', 'SKTM'=>'Ket. Tidak Mampu', 'SKU'=>'Ket. Usaha', 'SKKe'=>'Ket. Kehilangan', 'SKL'=>'Ket. Kelahiran', 'SKKM'=>'Ket. Kematian', 'SK'=>'Keterangan Umum'];
$data_surat = [];
foreach ($jenis_surat_list as $jenis) {
    $data_surat[$jenis] = getCountData($conn, "SELECT COUNT(*) as total FROM arsip_surat WHERE jenis_surat = '$jenis'");
}

// ==================== MULAI PROSES SPREADSHEET ====================
try {
    $spreadsheet = new Spreadsheet();
    
    // STYLE STANDAR (Elegan & Rapi)
    $styleHeader = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4E73DF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $styleTitle = [
        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '4E73DF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'EAECF4']],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $styleBorder = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];

    function renderMatrix(&$sheet, &$r, $title, $row_data, $dusun_list, $styleTitle, $styleHeader, $styleBorder, $excelCols) {
        $endCol = $excelCols[count($dusun_list) + 1]; 
        $sheet->mergeCells("A$r:$endCol$r"); 
        $sheet->setCellValue("A$r", $title); 
        $sheet->getStyle("A$r:$endCol$r")->applyFromArray($styleTitle);
        $r++;
        
        $sheet->setCellValue("A$r", "Kategori"); 
        $sheet->setCellValue("B$r", "TOTAL");
        
        $cIndex = 2; 
        foreach($dusun_list as $dsn) { 
            $sheet->setCellValue($excelCols[$cIndex].$r, $dsn); 
            $cIndex++; 
        }
        $sheet->getStyle("A$r:".$excelCols[$cIndex-1].$r)->applyFromArray($styleHeader);
        
        $startR = $r;
        $r++;
        foreach($row_data as $kat => $val) {
            $sheet->setCellValue("A$r", $kat);
            $sheet->setCellValue("B$r", $val['total']);
            $cIndex = 2;
            foreach($dusun_list as $dsn) {
                $sheet->setCellValue($excelCols[$cIndex].$r, $val['per_dusun'][$dsn]);
                $cIndex++;
            }
            $r++;
        }
        $sheet->getStyle("A$startR:".$excelCols[$cIndex-1].($r-1))->applyFromArray($styleBorder);
        $r++; // Spasi bawah
    }

    if ($type == 'single') {
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Master Dashboard');

        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'LAPORAN MASTER STATISTIK & DATABASE KEPENDUDUKAN DESA SUKOLILO TIMUR');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        
        $sheet->setCellValue('A3', 'Tanggal Export :'); $sheet->setCellValue('C3', tgl_indonesia(date('Y-m-d')) . ' ' . date('H:i:s'));
        $sheet->setCellValue('A4', 'Admin Bertugas :'); $sheet->setCellValue('C4', $_SESSION['nama_admin']);

        // 1. STATISTIK UMUM
        $r = 6;
        $sheet->mergeCells("A$r:C$r"); $sheet->setCellValue("A$r", 'A. RINGKASAN UMUM'); $sheet->getStyle("A$r:C$r")->applyFromArray($styleTitle);
        $sheet->mergeCells("E$r:G$r"); $sheet->setCellValue("E$r", 'B. MUTASI PENDUDUK (Total / Bln Ini)'); $sheet->getStyle("E$r:G$r")->applyFromArray($styleTitle);
        
        $r++; $startR = $r;
        $sheet->setCellValue("A$r", "Total Penduduk Aktif"); $sheet->setCellValue("C$r", $total_penduduk . " Jiwa"); 
        $sheet->setCellValue("E$r", "Kelahiran Bayi"); $sheet->setCellValue("G$r", "$tot_kelahiran / $bln_kelahiran Jiwa"); $r++;
        
        $sheet->setCellValue("A$r", "Total Keluarga (KK)"); $sheet->setCellValue("C$r", $total_kk . " KK"); 
        $sheet->setCellValue("E$r", "Warga Kematian"); $sheet->setCellValue("G$r", "$tot_kematian / $bln_kematian Jiwa"); $r++;
        
        $sheet->setCellValue("A$r", "Laki-Laki"); $sheet->setCellValue("C$r", $total_laki . " Jiwa"); 
        $sheet->setCellValue("E$r", "Penduduk Datang"); $sheet->setCellValue("G$r", "$tot_datang / $bln_datang Jiwa"); $r++;
        
        $sheet->setCellValue("A$r", "Perempuan"); $sheet->setCellValue("C$r", $total_perempuan . " Jiwa"); 
        $sheet->setCellValue("E$r", "Pindah Keluar"); $sheet->setCellValue("G$r", "$tot_pindah / $bln_pindah Jiwa"); $r++;
        
        $sheet->getStyle("A$startR:C".($r-1))->applyFromArray($styleBorder);
        $sheet->getStyle("E$startR:G".($r-1))->applyFromArray($styleBorder);
        
        $r++; $startR = $r;
        $sheet->mergeCells("A$r:C$r"); $sheet->setCellValue("A$r", 'STATUS PERKAWINAN'); $sheet->getStyle("A$r:C$r")->applyFromArray($styleHeader);
        $sheet->mergeCells("E$r:G$r"); $sheet->setCellValue("E$r", 'PEMELUK AGAMA'); $sheet->getStyle("E$r:G$r")->applyFromArray($styleHeader);
        $sheet->mergeCells("I$r:M$r"); $sheet->setCellValue("I$r", 'REKAP ARSIP SURAT KELUAR'); $sheet->getStyle("I$r:M$r")->applyFromArray($styleHeader);
        
        $r++; $rowKawin = $r; $rowAgama = $r; $rowSurat = $r;
        foreach($data_status as $k=>$v) { $sheet->setCellValue("A$rowKawin", $k); $sheet->setCellValue("C$rowKawin", $v); $rowKawin++; }
        foreach($data_agama as $k=>$v) { $sheet->setCellValue("E$rowAgama", $k); $sheet->setCellValue("G$rowAgama", $v); $rowAgama++; }
        foreach($data_surat as $k=>$v) { $sheet->setCellValue("I$rowSurat", $jenis_nama[$k]); $sheet->setCellValue("M$rowSurat", $v); $rowSurat++; }
        
        $sheet->getStyle("A$startR:C".($rowKawin-1))->applyFromArray($styleBorder);
        $sheet->getStyle("E$startR:G".($rowAgama-1))->applyFromArray($styleBorder);
        $sheet->getStyle("I$startR:M".($rowSurat-1))->applyFromArray($styleBorder);

        // 2. DEMOGRAFI DUSUN
        $r = max($rowKawin, $rowAgama, $rowSurat) + 1; 
        $sheet->mergeCells("A$r:F$r"); $sheet->setCellValue("A$r", 'C. DEMOGRAFI PER DUSUN'); $sheet->getStyle("A$r:F$r")->applyFromArray($styleTitle);
        $r++; $startR = $r;
        $headers = ['No', 'Nama Dusun', 'Laki-Laki', 'Perempuan', 'Total Warga', 'Total KK'];
        foreach($headers as $i => $h) { $sheet->setCellValue($excelCols[$i].$r, $h); }
        $sheet->getStyle("A$r:F$r")->applyFromArray($styleHeader);
        $r++; $no = 1;
        foreach($daftar_dusun as $dsn) {
            $sheet->setCellValue("A$r", $no++); $sheet->setCellValue("B$r", $dsn);
            $sheet->setCellValue("C$r", $data_dusun[$dsn]['laki']); $sheet->setCellValue("D$r", $data_dusun[$dsn]['perempuan']);
            $sheet->setCellValue("E$r", $data_dusun[$dsn]['total']); $sheet->setCellValue("F$r", $data_dusun[$dsn]['kk']);
            $r++;
        }
        $sheet->getStyle("A$startR:F".($r-1))->applyFromArray($styleBorder);
        $r++;

        // 3. MATRIKS SILANG
        renderMatrix($sheet, $r, 'D. MATRIKS RENTANG UMUR PER DUSUN', $data_umur, $daftar_dusun, $styleTitle, $styleHeader, $styleBorder, $excelCols);
        renderMatrix($sheet, $r, 'E. MATRIKS TINGKAT PENDIDIKAN PER DUSUN', $data_pendidikan_dusun, $daftar_dusun, $styleTitle, $styleHeader, $styleBorder, $excelCols);
        renderMatrix($sheet, $r, 'F. MATRIKS PROFESI / PEKERJAAN PER DUSUN', $data_pekerjaan_dusun, $daftar_dusun, $styleTitle, $styleHeader, $styleBorder, $excelCols);

        // 4. DATABASE PENDUDUK
        $sheet->mergeCells("A$r:M$r"); $sheet->setCellValue("A$r", 'G. DATABASE MENTAH SELURUH PENDUDUK'); $sheet->getStyle("A$r:M$r")->applyFromArray($styleTitle);
        $r++; $startR = $r;
        $h_pend = ['No', 'NIK', 'Nama Lengkap', 'JK', 'Tempat Lahir', 'Tgl Lahir', 'Umur', 'Agama', 'Status Kawin', 'Pendidikan', 'Pekerjaan', 'Dusun', 'Alamat'];
        foreach($h_pend as $i => $h) { $sheet->setCellValue($excelCols[$i].$r, $h); }
        $sheet->getStyle("A$r:M$r")->applyFromArray($styleHeader);
        $r++; $no = 1;
        $qp = mysqli_query($conn, "SELECT * FROM penduduk ORDER BY dusun, nama_penduduk");
        if($qp) {
            while($p = mysqli_fetch_assoc($qp)) {
                $sheet->setCellValue("A$r", $no++);
                $sheet->setCellValueExplicit("B$r", $p['nik'], DataType::TYPE_STRING);
                $sheet->setCellValue("C$r", $p['nama_penduduk']); $sheet->setCellValue("D$r", $p['jenis_kelamin']);
                $sheet->setCellValue("E$r", $p['tempat_lahir']); $sheet->setCellValue("F$r", date('d-m-Y', strtotime($p['tanggal_lahir'])));
                $sheet->setCellValue("G$r", hitungUmur($p['tanggal_lahir']) . ' Thn'); $sheet->setCellValue("H$r", $p['agama']);
                $sheet->setCellValue("I$r", $p['status_kawin']); $sheet->setCellValue("J$r", $p['pendidikan']);
                $sheet->setCellValue("K$r", $p['pekerjaan']); $sheet->setCellValue("L$r", $p['dusun']); $sheet->setCellValue("M$r", $p['alamat']);
                $r++;
            }
        }
        $sheet->getStyle("A$startR:M".($r-1))->applyFromArray($styleBorder);
        $r++;

        // 5. DATABASE KK
        $sheet->mergeCells("A$r:I$r"); $sheet->setCellValue("A$r", 'H. DATABASE MENTAH KARTU KELUARGA'); $sheet->getStyle("A$r:I$r")->applyFromArray($styleTitle);
        $r++; $startR = $r;
        $h_kk = ['No', 'Nomor KK', 'Nama Kepala Keluarga', 'NIK Kepala', 'Dusun', 'RT', 'RW', 'Alamat', 'Total Anggota'];
        foreach($h_kk as $i => $h) { $sheet->setCellValue($excelCols[$i].$r, $h); }
        $sheet->getStyle("A$r:I$r")->applyFromArray($styleHeader);
        $r++; $no = 1;
        $qkk = mysqli_query($conn, "SELECT kk.*, p.nama_penduduk as nama_kepala, 
                 (SELECT COUNT(*) FROM anggota_keluarga ak WHERE ak.no_kk = kk.no_kk AND ak.nik != kk.nik_kepala) as j_ang 
                 FROM kartu_keluarga kk JOIN penduduk p ON kk.nik_kepala = p.nik ORDER BY kk.dusun, kk.no_kk");
        if($qkk) {
            while($k = mysqli_fetch_assoc($qkk)) {
                $sheet->setCellValue("A$r", $no++);
                $sheet->setCellValueExplicit("B$r", $k['no_kk'], DataType::TYPE_STRING);
                $sheet->setCellValue("C$r", $k['nama_kepala']);
                $sheet->setCellValueExplicit("D$r", $k['nik_kepala'], DataType::TYPE_STRING);
                $sheet->setCellValue("E$r", $k['dusun']); $sheet->setCellValue("F$r", $k['rt']); $sheet->setCellValue("G$r", $k['rw']);
                $sheet->setCellValue("H$r", $k['alamat_kk']); $sheet->setCellValue("I$r", ($k['j_ang'] + 1) . ' Jiwa');
                $r++;
            }
        }
        $sheet->getStyle("A$startR:I".($r-1))->applyFromArray($styleBorder);

        foreach(range('A','M') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

    } else {
        // =========================================================================
        // MODE 2: MULTIPLE SHEETS
        // =========================================================================
        
        $spreadsheet->setActiveSheetIndex(0);
        $s1 = $spreadsheet->getActiveSheet(); $s1->setTitle('1. Ringkasan Demografi');
        $s1->mergeCells('A1:G1'); $s1->setCellValue('A1', 'RINGKASAN DEMOGRAFI & MUTASI DESA'); $s1->getStyle('A1')->applyFromArray(['font'=>['bold'=>true, 'size'=>14]]);
        
        $r=3;
        $s1->setCellValue("A$r", 'Total Penduduk Aktif'); $s1->setCellValue("C$r", $total_penduduk); 
        $s1->setCellValue("E$r", 'Kelahiran (Total / Bln Ini)'); $s1->setCellValue("G$r", "$tot_kelahiran / $bln_kelahiran"); $r++;
        
        $s1->setCellValue("A$r", 'Total KK'); $s1->setCellValue("C$r", $total_kk);
        $s1->setCellValue("E$r", 'Kematian (Total / Bln Ini)'); $s1->setCellValue("G$r", "$tot_kematian / $bln_kematian"); $r++;
        
        $s1->setCellValue("A$r", 'Laki-Laki'); $s1->setCellValue("C$r", $total_laki);
        $s1->setCellValue("E$r", 'Pendatang (Total / Bln Ini)'); $s1->setCellValue("G$r", "$tot_datang / $bln_datang"); $r++;
        
        $s1->setCellValue("A$r", 'Perempuan'); $s1->setCellValue("C$r", $total_perempuan);
        $s1->setCellValue("E$r", 'Pindah Keluar (Total / Bln Ini)'); $s1->setCellValue("G$r", "$tot_pindah / $bln_pindah"); $r+=2;
        
        $s1->getStyle("A3:C6")->applyFromArray($styleBorder);
        $s1->getStyle("E3:G6")->applyFromArray($styleBorder);
        
        $s1->setCellValue("A$r", 'STATUS KAWIN'); $s1->setCellValue("B$r", 'JUMLAH'); $s1->getStyle("A$r:B$r")->applyFromArray($styleHeader); $rC=$r+1;
        foreach($data_status as $k=>$v) { $s1->setCellValue("A$rC", $k); $s1->setCellValue("B$rC", $v); $rC++; } $s1->getStyle("A$r:B".($rC-1))->applyFromArray($styleBorder);

        $s1->setCellValue("D$r", 'AGAMA'); $s1->setCellValue("E$r", 'JUMLAH'); $s1->getStyle("D$r:E$r")->applyFromArray($styleHeader); $rA=$r+1;
        foreach($data_agama as $k=>$v) { $s1->setCellValue("D$rA", $k); $s1->setCellValue("E$rA", $v); $rA++; } $s1->getStyle("D$r:E".($rA-1))->applyFromArray($styleBorder);

        $r = max($rC, $rA) + 1;
        $s1->setCellValue("A$r", 'JENIS SURAT KELUAR'); $s1->setCellValue("C$r", 'JUMLAH'); $s1->getStyle("A$r:C$r")->applyFromArray($styleHeader); $rS=$r+1;
        foreach($data_surat as $k=>$v) { $s1->setCellValue("A$rS", $jenis_nama[$k]); $s1->setCellValue("C$rS", $v); $rS++; } $s1->getStyle("A$r:C".($rS-1))->applyFromArray($styleBorder);
        foreach(range('A','G') as $col) $s1->getColumnDimension($col)->setAutoSize(true);

        $spreadsheet->createSheet(); $spreadsheet->setActiveSheetIndex(1);
        $s2 = $spreadsheet->getActiveSheet(); $s2->setTitle('2. Demografi Dusun');
        $headers = ['No', 'Dusun', 'Laki-Laki', 'Perempuan', 'Total Warga', 'Total KK'];
        foreach($headers as $i=>$h) $s2->setCellValue($excelCols[$i].'1', $h);
        $s2->getStyle('A1:F1')->applyFromArray($styleHeader);
        $r=2; $no=1;
        foreach($daftar_dusun as $dsn) {
            $s2->setCellValue("A$r", $no++); $s2->setCellValue("B$r", $dsn); $s2->setCellValue("C$r", $data_dusun[$dsn]['laki']);
            $s2->setCellValue("D$r", $data_dusun[$dsn]['perempuan']); $s2->setCellValue("E$r", $data_dusun[$dsn]['total']); $s2->setCellValue("F$r", $data_dusun[$dsn]['kk']); $r++;
        }
        $s2->getStyle("A1:F".($r-1))->applyFromArray($styleBorder); foreach(range('A','F') as $col) $s2->getColumnDimension($col)->setAutoSize(true);

        function createMatrixTab($spreadsheet, $index, $tabName, $title, $data, $dusun_list, $styleHeader, $styleBorder, $excelCols) {
            $spreadsheet->createSheet(); $spreadsheet->setActiveSheetIndex($index);
            $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle($tabName);
            $sheet->setCellValue('A1', $title); $sheet->setCellValue('B1', 'TOTAL');
            $c = 2; foreach($dusun_list as $dsn) { $sheet->setCellValue($excelCols[$c].'1', $dsn); $c++; }
            $sheet->getStyle("A1:".$excelCols[$c-1]."1")->applyFromArray($styleHeader);
            $r=2;
            foreach($data as $k => $v) {
                $sheet->setCellValue("A$r", $k); $sheet->setCellValue("B$r", $v['total']);
                $c = 2; foreach($dusun_list as $dsn) { $sheet->setCellValue($excelCols[$c].$r, $v['per_dusun'][$dsn]); $c++; }
                $r++;
            }
            $sheet->getStyle("A1:".$excelCols[$c-1].($r-1))->applyFromArray($styleBorder);
            foreach(range('A', $excelCols[$c-1]) as $cAuto) $sheet->getColumnDimension($cAuto)->setAutoSize(true);
        }

        createMatrixTab($spreadsheet, 2, '3. Matriks Umur', 'Kategori Umur', $data_umur, $daftar_dusun, $styleHeader, $styleBorder, $excelCols);
        createMatrixTab($spreadsheet, 3, '4. Matriks Pendidikan', 'Tingkat Pendidikan', $data_pendidikan_dusun, $daftar_dusun, $styleHeader, $styleBorder, $excelCols);
        createMatrixTab($spreadsheet, 4, '5. Matriks Pekerjaan', 'Jenis Profesi', $data_pekerjaan_dusun, $daftar_dusun, $styleHeader, $styleBorder, $excelCols);

        $spreadsheet->createSheet(); $spreadsheet->setActiveSheetIndex(5);
        $s6 = $spreadsheet->getActiveSheet(); $s6->setTitle('6. DB Warga');
        $h_pend = ['No', 'NIK', 'Nama Lengkap', 'JK', 'Tempat Lahir', 'Tgl Lahir', 'Umur', 'Agama', 'Status Kawin', 'Pendidikan', 'Pekerjaan', 'Dusun', 'Alamat'];
        foreach($h_pend as $i=>$h) $s6->setCellValue($excelCols[$i].'1', $h);
        $s6->getStyle("A1:M1")->applyFromArray($styleHeader); $s6->freezePane('A2'); 
        $qp = mysqli_query($conn, "SELECT * FROM penduduk ORDER BY dusun, nama_penduduk");
        $r=2; $no=1;
        if($qp) {
            while($p = mysqli_fetch_assoc($qp)) {
                $s6->setCellValue("A$r", $no++); $s6->setCellValueExplicit("B$r", $p['nik'], DataType::TYPE_STRING); 
                $s6->setCellValue("C$r", $p['nama_penduduk']); $s6->setCellValue("D$r", $p['jenis_kelamin']); $s6->setCellValue("E$r", $p['tempat_lahir']);
                $s6->setCellValue("F$r", date('d-m-Y', strtotime($p['tanggal_lahir']))); $s6->setCellValue("G$r", hitungUmur($p['tanggal_lahir']));
                $s6->setCellValue("H$r", $p['agama']); $s6->setCellValue("I$r", $p['status_kawin']); $s6->setCellValue("J$r", $p['pendidikan']);
                $s6->setCellValue("K$r", $p['pekerjaan']); $s6->setCellValue("L$r", $p['dusun']); $s6->setCellValue("M$r", $p['alamat']); $r++;
            }
            $s6->getStyle("A1:M".($r-1))->applyFromArray($styleBorder); foreach(range('A','M') as $col) $s6->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->createSheet(); $spreadsheet->setActiveSheetIndex(6);
        $s7 = $spreadsheet->getActiveSheet(); $s7->setTitle('7. DB Keluarga');
        $h_kk = ['No', 'Nomor KK', 'Nama Kepala', 'NIK Kepala', 'Dusun', 'RT', 'RW', 'Alamat', 'Total Anggota'];
        foreach($h_kk as $i=>$h) $s7->setCellValue($excelCols[$i].'1', $h);
        $s7->getStyle("A1:I1")->applyFromArray($styleHeader); $s7->freezePane('A2'); 
        $qkk = mysqli_query($conn, "SELECT kk.*, p.nama_penduduk as nama_kepala, (SELECT COUNT(*) FROM anggota_keluarga ak WHERE ak.no_kk = kk.no_kk AND ak.nik != kk.nik_kepala) as j_ang FROM kartu_keluarga kk JOIN penduduk p ON kk.nik_kepala = p.nik ORDER BY kk.dusun, kk.no_kk");
        $r=2; $no=1;
        if($qkk) {
            while($k = mysqli_fetch_assoc($qkk)) {
                $s7->setCellValue("A$r", $no++); $s7->setCellValueExplicit("B$r", $k['no_kk'], DataType::TYPE_STRING);
                $s7->setCellValue("C$r", $k['nama_kepala']); $s7->setCellValueExplicit("D$r", $k['nik_kepala'], DataType::TYPE_STRING);
                $s7->setCellValue("E$r", $k['dusun']); $s7->setCellValue("F$r", $k['rt']); $s7->setCellValue("G$r", $k['rw']);
                $s7->setCellValue("H$r", $k['alamat_kk']); $s7->setCellValue("I$r", $k['j_ang'] + 1); $r++;
            }
            $s7->getStyle("A1:I".($r-1))->applyFromArray($styleBorder); foreach(range('A','I') as $col) $s7->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0); 
    }
    
    // ==================== MEMBERSIHKAN BUFFER DAN EXPORT ====================
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    $writer = new Xlsx($spreadsheet);
    $filename = "Export_Database_Desa_" . ($type == 'single' ? 'FullSheet_' : 'MultiTab_') . date('Y-m-d_Hi') . ".xlsx";
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    if (ob_get_length()) ob_end_clean();
    echo "<script>alert('Sistem gagal memproses data ke Excel: " . addslashes($e->getMessage()) . "'); window.close();</script>";
    exit();
}
?>