<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

include "db/koneksi.php";
include "db/funct.php";

// ==================== CEK PARAMETER TYPE ====================
$type = isset($_GET['type']) ? $_GET['type'] : 'multiple';
if (!in_array($type, ['single', 'multiple'])) {
    $type = 'multiple';
}

// ==================== CEK PHP SPREADSHEET ====================
$use_phpspreadsheet = false;
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        $use_phpspreadsheet = true;
    }
}

// ==================== USE STATEMENT ====================
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// ==================== FUNGSI FORMAT TANGGAL ====================
function tgl_indonesia($tanggal) {
    if (empty($tanggal)) return '';
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    return $tgl . ' ' . $bln . ' ' . $thn;
}

// ==================== FUNGSI HITUNG UMUR ====================
function hitungUmur($tanggal_lahir) {
    if (empty($tanggal_lahir)) return 0;
    $birthDate = new DateTime($tanggal_lahir);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    return $age;
}

// ==================== AMBIL DATA STATISTIK ====================

// Daftar dusun
$daftar_dusun = [
    'KEJAWAN',
    'SEPURAN',
    'BUDDAN',
    'PASEREAN',
    'LANGGAR',
    'MORLEKE',
    'PREGIH',
    'KARANG PANDAN',
    'PONG BARU',
    'KRASAK',
    'PERUM BASMALAH'
];

// Statistik umum
$total_penduduk = get_total_penduduk();
$total_kk = get_total_kk();
$total_laki = get_total_laki();
$total_perempuan = get_total_perempuan();
$total_surat = get_total_surat();

// Data per dusun
$data_per_dusun = [];
$laki_per_dusun = get_laki_per_dusun();
$perempuan_per_dusun = get_perempuan_per_dusun();
$kk_per_dusun = get_kk_per_dusun();

foreach ($daftar_dusun as $dusun) {
    $data_per_dusun[$dusun] = [
        'laki' => $laki_per_dusun[$dusun] ?? 0,
        'perempuan' => $perempuan_per_dusun[$dusun] ?? 0,
        'total' => ($laki_per_dusun[$dusun] ?? 0) + ($perempuan_per_dusun[$dusun] ?? 0),
        'kk' => $kk_per_dusun[$dusun] ?? 0
    ];
}

// Data umur
$rentang_umur = [
    '0-5' => [0, 5],
    '6-12' => [6, 12],
    '13-17' => [13, 17],
    '18-25' => [18, 25],
    '26-35' => [26, 35],
    '36-45' => [36, 45],
    '46-55' => [46, 55],
    '56-65' => [56, 65],
    '65+' => [65, 150]
];

$data_umur = [];
$query_penduduk = mysqli_query($conn, "SELECT nik, tanggal_lahir, dusun FROM penduduk");

while ($row = mysqli_fetch_assoc($query_penduduk)) {
    $umur = hitungUmur($row['tanggal_lahir']);
    $dusun = $row['dusun'] ?: 'TANPA DUSUN';
    
    foreach ($rentang_umur as $rentang => $range) {
        if ($umur >= $range[0] && $umur <= $range[1]) {
            if (!isset($data_umur[$rentang])) {
                $data_umur[$rentang] = ['total' => 0, 'per_dusun' => []];
            }
            $data_umur[$rentang]['total']++;
            
            if (!isset($data_umur[$rentang]['per_dusun'][$dusun])) {
                $data_umur[$rentang]['per_dusun'][$dusun] = 0;
            }
            $data_umur[$rentang]['per_dusun'][$dusun]++;
            break;
        }
    }
}

// Data status kawin
$status_kawin_list = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
$data_status = [];

foreach ($status_kawin_list as $status) {
    $status_escape = mysqli_real_escape_string($conn, $status);
    $query = "SELECT COUNT(*) as total FROM penduduk WHERE status_kawin = '$status_escape'";
    $result = mysqli_query($conn, $query);
    $data_status[$status] = mysqli_fetch_assoc($result)['total'];
}

// Data pekerjaan
$pekerjaan_list = [
    'PNS', 'TNI', 'POLRI', 'PEGAWAI SWASTA', 'WIRASWASTA', 
    'PETANI', 'BURUH', 'PELAJAR/MAHASISWA', 'IRT', 'PENSIUNAN'
];

$data_pekerjaan = [];
foreach ($pekerjaan_list as $pekerjaan) {
    $pekerjaan_escape = mysqli_real_escape_string($conn, $pekerjaan);
    $query = "SELECT COUNT(*) as total FROM penduduk WHERE pekerjaan = '$pekerjaan_escape'";
    $result = mysqli_query($conn, $query);
    $data_pekerjaan[$pekerjaan] = mysqli_fetch_assoc($result)['total'];
}

$query_lainnya = "SELECT COUNT(*) as total FROM penduduk WHERE pekerjaan NOT IN ('" . implode("','", $pekerjaan_list) . "') OR pekerjaan IS NULL OR pekerjaan = ''";
$result_lainnya = mysqli_query($conn, $query_lainnya);
$data_pekerjaan['Lainnya'] = mysqli_fetch_assoc($result_lainnya)['total'];

// Data agama
$agama_list = ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDDHA', 'KONGHUCU'];
$data_agama = [];

foreach ($agama_list as $agama) {
    $agama_escape = mysqli_real_escape_string($conn, $agama);
    $query = "SELECT COUNT(*) as total FROM penduduk WHERE agama = '$agama_escape'";
    $result = mysqli_query($conn, $query);
    $data_agama[$agama] = mysqli_fetch_assoc($result)['total'];
}

// Data pendidikan
$pendidikan_list = ['TIDAK SEKOLAH', 'SD', 'SMP', 'SMA', 'SMK', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'];
$data_pendidikan = [];

foreach ($pendidikan_list as $pendidikan) {
    $pendidikan_escape = mysqli_real_escape_string($conn, $pendidikan);
    $query = "SELECT COUNT(*) as total FROM penduduk WHERE pendidikan = '$pendidikan_escape'";
    $result = mysqli_query($conn, $query);
    $data_pendidikan[$pendidikan] = mysqli_fetch_assoc($result)['total'];
}

// Data surat per jenis
$jenis_surat_list = ['SKD', 'SKTM', 'SKU', 'SKKe', 'SK'];
$data_surat = [];
$jenis_nama = [
    'SKD' => 'Surat Keterangan Domisili',
    'SKTM' => 'Surat Keterangan Tidak Mampu',
    'SKU' => 'Surat Keterangan Usaha',
    'SKKe' => 'Surat Keterangan Kehilangan',
    'SK' => 'Surat Keterangan Umum'
];

foreach ($jenis_surat_list as $jenis) {
    $jenis_escape = mysqli_real_escape_string($conn, $jenis);
    $query = "SELECT COUNT(*) as total FROM arsip_surat WHERE jenis_surat = '$jenis_escape'";
    $result = mysqli_query($conn, $query);
    $data_surat[$jenis] = mysqli_fetch_assoc($result)['total'];
}

// ==================== EXPORT KE EXCEL ====================
if ($use_phpspreadsheet) {
    try {
        // Buat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        
        if ($type == 'single') {
            // ========== SINGLE SHEET ==========
            $spreadsheet->setActiveSheetIndex(0);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Laporan Statistik');
            
            // Judul
            $sheet->mergeCells('A1:E1');
            $sheet->setCellValue('A1', 'LAPORAN STATISTIK PENDUDUK');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            // Informasi Export
            $sheet->setCellValue('A3', 'Tanggal Export:');
            $sheet->setCellValue('B3', tgl_indonesia(date('Y-m-d')) . ' ' . date('H:i:s') . ' WIB');
            $sheet->setCellValue('A4', 'Diekspor Oleh:');
            $sheet->setCellValue('B4', $_SESSION['nama_admin']);
            
            // STATISTIK UMUM
            $sheet->mergeCells('A6:E6');
            $sheet->setCellValue('A6', 'A. STATISTIK UMUM');
            $sheet->getStyle('A6')->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FC']]
            ]);
            
            $row = 7;
            $sheet->setCellValue('A' . $row, 'Total Penduduk');
            $sheet->setCellValue('B' . $row, $total_penduduk);
            $row++;
            $sheet->setCellValue('A' . $row, 'Total KK');
            $sheet->setCellValue('B' . $row, $total_kk);
            $row++;
            $sheet->setCellValue('A' . $row, 'Laki-laki');
            $sheet->setCellValue('B' . $row, $total_laki);
            $row++;
            $sheet->setCellValue('A' . $row, 'Perempuan');
            $sheet->setCellValue('B' . $row, $total_perempuan);
            $row++;
            $sheet->setCellValue('A' . $row, 'Total Surat Keluar');
            $sheet->setCellValue('B' . $row, $total_surat);
            
            // DATA PER DUSUN
            $row += 2;
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $sheet->setCellValue('A' . $row, 'B. DATA PENDUDUK PER DUSUN');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FC']]
            ]);
            
            $row++;
            $sheet->setCellValue('A' . $row, 'No');
            $sheet->setCellValue('B' . $row, 'Dusun');
            $sheet->setCellValue('C' . $row, 'Laki-laki');
            $sheet->setCellValue('D' . $row, 'Perempuan');
            $sheet->setCellValue('E' . $row, 'Total');
            $sheet->setCellValue('F' . $row, 'KK');
            
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8E8E8']]
            ]);
            
            $row++;
            $no = 1;
            foreach ($daftar_dusun as $dusun) {
                $data = $data_per_dusun[$dusun];
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, $dusun);
                $sheet->setCellValue('C' . $row, $data['laki']);
                $sheet->setCellValue('D' . $row, $data['perempuan']);
                $sheet->setCellValue('E' . $row, $data['total']);
                $sheet->setCellValue('F' . $row, $data['kk']);
                $row++;
            }
            
            // DISTRIBUSI UMUR
            $row += 2;
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $sheet->setCellValue('A' . $row, 'C. DISTRIBUSI UMUR');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FC']]
            ]);
            
            $row++;
            $sheet->setCellValue('A' . $row, 'Rentang Umur');
            $sheet->setCellValue('B' . $row, 'Jumlah');
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8E8E8']]
            ]);
            
            $row++;
            foreach ($data_umur as $rentang => $data) {
                $sheet->setCellValue('A' . $row, $rentang . ' tahun');
                $sheet->setCellValue('B' . $row, $data['total']);
                $row++;
            }
            
            // STATUS KAWIN
            $row += 2;
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $sheet->setCellValue('A' . $row, 'D. STATUS PERKAWINAN');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FC']]
            ]);
            
            $row++;
            $sheet->setCellValue('A' . $row, 'Status');
            $sheet->setCellValue('B' . $row, 'Jumlah');
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8E8E8']]
            ]);
            
            $row++;
            foreach ($data_status as $status => $jumlah) {
                $sheet->setCellValue('A' . $row, $status);
                $sheet->setCellValue('B' . $row, $jumlah);
                $row++;
            }
            
            // PEKERJAAN
            $row += 2;
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $sheet->setCellValue('A' . $row, 'E. PEKERJAAN');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FC']]
            ]);
            
            $row++;
            $sheet->setCellValue('A' . $row, 'Pekerjaan');
            $sheet->setCellValue('B' . $row, 'Jumlah');
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E8E8E8']]
            ]);
            
            $row++;
            foreach ($data_pekerjaan as $pekerjaan => $jumlah) {
                $sheet->setCellValue('A' . $row, $pekerjaan);
                $sheet->setCellValue('B' . $row, $jumlah);
                $row++;
            }
            
            // Atur lebar kolom
            $sheet->getColumnDimension('A')->setWidth(30);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            
        } else {
            // ========== MULTIPLE SHEETS (10 SHEET) ==========
            
            // SHEET 1: RINGKASAN
            $spreadsheet->setActiveSheetIndex(0);
            $sheet1 = $spreadsheet->getActiveSheet();
            $sheet1->setTitle('Ringkasan');
            
            $sheet1->mergeCells('A1:C1');
            $sheet1->setCellValue('A1', 'LAPORAN STATISTIK PENDUDUK');
            $sheet1->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $sheet1->setCellValue('A3', 'Tanggal Export:');
            $sheet1->setCellValue('B3', tgl_indonesia(date('Y-m-d')) . ' ' . date('H:i:s') . ' WIB');
            $sheet1->setCellValue('A4', 'Diekspor Oleh:');
            $sheet1->setCellValue('B4', $_SESSION['nama_admin']);
            
            $sheet1->mergeCells('A6:C6');
            $sheet1->setCellValue('A6', 'STATISTIK UMUM');
            $sheet1->getStyle('A6')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FC']]
            ]);
            
            $row = 7;
            $sheet1->setCellValue('A' . $row, 'Total Penduduk');
            $sheet1->setCellValue('B' . $row, $total_penduduk);
            $row++;
            $sheet1->setCellValue('A' . $row, 'Total KK');
            $sheet1->setCellValue('B' . $row, $total_kk);
            $row++;
            $sheet1->setCellValue('A' . $row, 'Laki-laki');
            $sheet1->setCellValue('B' . $row, $total_laki);
            $row++;
            $sheet1->setCellValue('A' . $row, 'Perempuan');
            $sheet1->setCellValue('B' . $row, $total_perempuan);
            $row++;
            $sheet1->setCellValue('A' . $row, 'Total Surat Keluar');
            $sheet1->setCellValue('B' . $row, $total_surat);
            
            $sheet1->getColumnDimension('A')->setWidth(30);
            $sheet1->getColumnDimension('B')->setWidth(20);
            
            // SHEET 2: DATA PER DUSUN
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(1);
            $sheet2 = $spreadsheet->getActiveSheet();
            $sheet2->setTitle('Data per Dusun');
            
            $sheet2->mergeCells('A1:F1');
            $sheet2->setCellValue('A1', 'DATA PENDUDUK PER DUSUN');
            $sheet2->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $sheet2->setCellValue('A3', 'No');
            $sheet2->setCellValue('B3', 'Dusun');
            $sheet2->setCellValue('C3', 'Laki-laki');
            $sheet2->setCellValue('D3', 'Perempuan');
            $sheet2->setCellValue('E3', 'Total');
            $sheet2->setCellValue('F3', 'KK');
            
            $sheet2->getStyle('A3:F3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4E73DF']]
            ]);
            
            $row = 4;
            $no = 1;
            foreach ($daftar_dusun as $dusun) {
                $data = $data_per_dusun[$dusun];
                $sheet2->setCellValue('A' . $row, $no++);
                $sheet2->setCellValue('B' . $row, $dusun);
                $sheet2->setCellValue('C' . $row, $data['laki']);
                $sheet2->setCellValue('D' . $row, $data['perempuan']);
                $sheet2->setCellValue('E' . $row, $data['total']);
                $sheet2->setCellValue('F' . $row, $data['kk']);
                $row++;
            }
            
            $sheet2->getColumnDimension('A')->setWidth(5);
            $sheet2->getColumnDimension('B')->setWidth(20);
            $sheet2->getColumnDimension('C')->setWidth(12);
            $sheet2->getColumnDimension('D')->setWidth(12);
            $sheet2->getColumnDimension('E')->setWidth(12);
            $sheet2->getColumnDimension('F')->setWidth(12);
            
            // SHEET 3: DISTRIBUSI UMUR
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(2);
            $sheet3 = $spreadsheet->getActiveSheet();
            $sheet3->setTitle('Distribusi Umur');
            
            $sheet3->mergeCells('A1:C1');
            $sheet3->setCellValue('A1', 'DISTRIBUSI UMUR');
            $sheet3->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $sheet3->setCellValue('A3', 'Rentang Umur');
            $sheet3->setCellValue('B3', 'Jumlah');
            
            $sheet3->getStyle('A3:B3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4E73DF']]
            ]);
            
            $row = 4;
            foreach ($data_umur as $rentang => $data) {
                $sheet3->setCellValue('A' . $row, $rentang . ' tahun');
                $sheet3->setCellValue('B' . $row, $data['total']);
                $row++;
            }
            
            $sheet3->getColumnDimension('A')->setWidth(20);
            $sheet3->getColumnDimension('B')->setWidth(15);
            
            // SHEET 4: STATUS KAWIN
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(3);
            $sheet4 = $spreadsheet->getActiveSheet();
            $sheet4->setTitle('Status Kawin');
            
            $sheet4->mergeCells('A1:B1');
            $sheet4->setCellValue('A1', 'STATUS PERKAWINAN');
            $sheet4->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $sheet4->setCellValue('A3', 'Status');
            $sheet4->setCellValue('B3', 'Jumlah');
            
            $sheet4->getStyle('A3:B3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4E73DF']]
            ]);
            
            $row = 4;
            foreach ($data_status as $status => $jumlah) {
                $sheet4->setCellValue('A' . $row, $status);
                $sheet4->setCellValue('B' . $row, $jumlah);
                $row++;
            }
            
            $sheet4->getColumnDimension('A')->setWidth(25);
            $sheet4->getColumnDimension('B')->setWidth(15);
            
            // SHEET 5: PEKERJAAN
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(4);
            $sheet5 = $spreadsheet->getActiveSheet();
            $sheet5->setTitle('Pekerjaan');
            
            $sheet5->mergeCells('A1:B1');
            $sheet5->setCellValue('A1', 'PEKERJAAN');
            $sheet5->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $sheet5->setCellValue('A3', 'Pekerjaan');
            $sheet5->setCellValue('B3', 'Jumlah');
            
            $sheet5->getStyle('A3:B3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4E73DF']]
            ]);
            
            $row = 4;
            foreach ($data_pekerjaan as $pekerjaan => $jumlah) {
                $sheet5->setCellValue('A' . $row, $pekerjaan);
                $sheet5->setCellValue('B' . $row, $jumlah);
                $row++;
            }
            
            $sheet5->getColumnDimension('A')->setWidth(30);
            $sheet5->getColumnDimension('B')->setWidth(15);
            
            // SHEET 6: AGAMA
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(5);
            $sheet6 = $spreadsheet->getActiveSheet();
            $sheet6->setTitle('Agama');
            
            $sheet6->mergeCells('A1:B1');
            $sheet6->setCellValue('A1', 'AGAMA');
            $sheet6->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $sheet6->setCellValue('A3', 'Agama');
            $sheet6->setCellValue('B3', 'Jumlah');
            
            $sheet6->getStyle('A3:B3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4E73DF']]
            ]);
            
            $row = 4;
            foreach ($data_agama as $agama => $jumlah) {
                $sheet6->setCellValue('A' . $row, $agama);
                $sheet6->setCellValue('B' . $row, $jumlah);
                $row++;
            }
            
            $sheet6->getColumnDimension('A')->setWidth(20);
            $sheet6->getColumnDimension('B')->setWidth(15);
            
            // SHEET 7: PENDIDIKAN
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(6);
            $sheet7 = $spreadsheet->getActiveSheet();
            $sheet7->setTitle('Pendidikan');
            
            $sheet7->mergeCells('A1:B1');
            $sheet7->setCellValue('A1', 'PENDIDIKAN');
            $sheet7->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $sheet7->setCellValue('A3', 'Pendidikan');
            $sheet7->setCellValue('B3', 'Jumlah');
            
            $sheet7->getStyle('A3:B3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4E73DF']]
            ]);
            
            $row = 4;
            foreach ($data_pendidikan as $pendidikan => $jumlah) {
                $sheet7->setCellValue('A' . $row, $pendidikan);
                $sheet7->setCellValue('B' . $row, $jumlah);
                $row++;
            }
            
            $sheet7->getColumnDimension('A')->setWidth(25);
            $sheet7->getColumnDimension('B')->setWidth(15);
            
            // SHEET 8: SURAT KELUAR
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(7);
            $sheet8 = $spreadsheet->getActiveSheet();
            $sheet8->setTitle('Surat Keluar');
            
            $sheet8->mergeCells('A1:C1');
            $sheet8->setCellValue('A1', 'SURAT KELUAR');
            $sheet8->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $sheet8->setCellValue('A3', 'Jenis');
            $sheet8->setCellValue('B3', 'Keterangan');
            $sheet8->setCellValue('C3', 'Jumlah');
            
            $sheet8->getStyle('A3:C3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4E73DF']]
            ]);
            
            $row = 4;
            foreach ($jenis_surat_list as $jenis) {
                $sheet8->setCellValue('A' . $row, $jenis);
                $sheet8->setCellValue('B' . $row, $jenis_nama[$jenis] ?? '-');
                $sheet8->setCellValue('C' . $row, $data_surat[$jenis] ?? 0);
                $row++;
            }
            
            $sheet8->getColumnDimension('A')->setWidth(10);
            $sheet8->getColumnDimension('B')->setWidth(35);
            $sheet8->getColumnDimension('C')->setWidth(15);
            
            // SHEET 9: DAFTAR PENDUDUK
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(8);
            $sheet9 = $spreadsheet->getActiveSheet();
            $sheet9->setTitle('Daftar Penduduk');
            
            $sheet9->mergeCells('A1:J1');
            $sheet9->setCellValue('A1', 'DAFTAR PENDUDUK');
            $sheet9->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $headers = ['No', 'NIK', 'Nama', 'Dusun', 'Tempat Lahir', 'Tanggal Lahir', 'Umur', 'JK', 'Status', 'Pekerjaan'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet9->setCellValue($col . '3', $header);
                $col++;
            }
            
            $sheet9->getStyle('A3:J3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4E73DF']]
            ]);
            
            $query = "SELECT * FROM penduduk ORDER BY nama_penduduk";
            $result = mysqli_query($conn, $query);
            
            $row = 4;
            $no = 1;
            while ($p = mysqli_fetch_assoc($result)) {
                $umur = hitungUmur($p['tanggal_lahir']);
                
                $sheet9->setCellValue('A' . $row, $no++);
                $sheet9->setCellValue('B' . $row, $p['nik']);
                $sheet9->setCellValue('C' . $row, $p['nama_penduduk']);
                $sheet9->setCellValue('D' . $row, $p['dusun'] ?? '-');
                $sheet9->setCellValue('E' . $row, $p['tempat_lahir']);
                $sheet9->setCellValue('F' . $row, date('d-m-Y', strtotime($p['tanggal_lahir'])));
                $sheet9->setCellValue('G' . $row, $umur . ' tahun');
                $sheet9->setCellValue('H' . $row, $p['jenis_kelamin']);
                $sheet9->setCellValue('I' . $row, $p['status_kawin']);
                $sheet9->setCellValue('J' . $row, $p['pekerjaan'] ?: '-');
                $row++;
            }
            
            for ($i = 0; $i < 10; $i++) {
                $sheet9->getColumnDimension(chr(65 + $i))->setAutoSize(true);
            }
            
            // SHEET 10: DAFTAR KK
            $spreadsheet->createSheet();
            $spreadsheet->setActiveSheetIndex(9);
            $sheet10 = $spreadsheet->getActiveSheet();
            $sheet10->setTitle('Daftar KK');
            
            $sheet10->mergeCells('A1:F1');
            $sheet10->setCellValue('A1', 'DAFTAR KARTU KELUARGA');
            $sheet10->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $headers_kk = ['No', 'Nomor KK', 'Kepala Keluarga', 'NIK Kepala', 'Dusun', 'Jumlah Anggota'];
            $col = 'A';
            foreach ($headers_kk as $header) {
                $sheet10->setCellValue($col . '3', $header);
                $col++;
            }
            
            $sheet10->getStyle('A3:F3')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4E73DF']]
            ]);
            
            $query_kk = "SELECT kk.*, p.nama_penduduk as nama_kepala,
                         (SELECT COUNT(*) FROM anggota_keluarga WHERE no_kk = kk.no_kk) as jumlah_anggota
                         FROM kartu_keluarga kk
                         JOIN penduduk p ON kk.nik_kepala = p.nik
                         ORDER BY kk.no_kk";
            $result_kk = mysqli_query($conn, $query_kk);
            
            $row = 4;
            $no = 1;
            while ($kk = mysqli_fetch_assoc($result_kk)) {
                $sheet10->setCellValue('A' . $row, $no++);
                $sheet10->setCellValue('B' . $row, $kk['no_kk']);
                $sheet10->setCellValue('C' . $row, $kk['nama_kepala']);
                $sheet10->setCellValue('D' . $row, $kk['nik_kepala']);
                $sheet10->setCellValue('E' . $row, $kk['dusun'] ?? '-');
                $sheet10->setCellValue('F' . $row, $kk['jumlah_anggota'] + 1);
                $row++;
            }
            
            for ($i = 0; $i < 6; $i++) {
                $sheet10->getColumnDimension(chr(65 + $i))->setAutoSize(true);
            }
        }
        
        // Buat file Excel
        $writer = new Xlsx($spreadsheet);
        
        // Nama file
        $filename = "laporan_statistik_" . ($type == 'single' ? '1sheet_' : 'multisheet_') . date('Y-m-d_H-i-s') . ".xlsx";
        
        // Set header untuk download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Simpan ke output
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Gagal mengekspor data: " . $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Library PhpSpreadsheet tidak tersedia.";
    header("Location: dashboard.php");
    exit();
}
?>