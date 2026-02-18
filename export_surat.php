<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

include "db/koneksi.php";
include "db/funct.php";

// ==================== CEK PHP SPREADSHEET ====================
$use_phpspreadsheet = false;
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    
    // Cek apakah kelas tersedia
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

// ==================== AMBIL DATA FILTER ====================
$where = "WHERE 1=1";

// Filter jenis surat
if (isset($_GET['jenis_surat']) && !empty($_GET['jenis_surat'])) {
    $jenis_surat = mysqli_real_escape_string($conn, $_GET['jenis_surat']);
    $where .= " AND jenis_surat = '$jenis_surat'";
}

// Filter bulan
if (isset($_GET['bulan']) && !empty($_GET['bulan'])) {
    $bulan = (int)$_GET['bulan'];
    $where .= " AND MONTH(tanggal_surat) = '$bulan'";
}

// Filter tahun
if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
    $tahun = (int)$_GET['tahun'];
    $where .= " AND YEAR(tanggal_surat) = '$tahun'";
}

// Pencarian
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (no_surat LIKE '%$search%' OR nama_pemohon LIKE '%$search%' OR nik LIKE '%$search%')";
}

// ==================== AMBIL DATA SURAT ====================
$query = "SELECT * FROM arsip_surat $where ORDER BY tanggal_surat DESC, id_surat DESC";
$result = mysqli_query($conn, $query);
$total_surat = mysqli_num_rows($result);

// ==================== HITUNG STATISTIK ====================
$tahun_ini = date('Y');
$bulan_ini = date('m');

$query_total_all = "SELECT COUNT(*) as total FROM arsip_surat";
$result_total_all = mysqli_query($conn, $query_total_all);
$total_all = mysqli_fetch_assoc($result_total_all)['total'];

$query_total_filter = "SELECT COUNT(*) as total FROM arsip_surat $where";
$result_total_filter = mysqli_query($conn, $query_total_filter);
$total_filter = mysqli_fetch_assoc($result_total_filter)['total'];

// ==================== EKSPOR KE EXCEL ====================
if ($use_phpspreadsheet) {
    try {
        // Buat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Surat Keluar');
        
        // ===== SHEET 1: DATA SURAT =====
        
        // Header kolom
        $headers = [
            'No',
            'Tanggal Surat',
            'Nomor Surat',
            'Jenis Surat',
            'NIK',
            'Nama Pemohon',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Alamat',
            'Keperluan',
            'Keterangan',
            'Dibuat Pada'
        ];
        
        // Set header style
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '4e73df']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]);
            $sheet->getColumnDimension($column)->setAutoSize(true);
            $column++;
        }
        
        // Data rows
        $row = 2;
        $no = 1;
        
        if (mysqli_num_rows($result) > 0) {
            mysqli_data_seek($result, 0); // Reset pointer
            while ($surat = mysqli_fetch_assoc($result)) {
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, date('d-m-Y', strtotime($surat['tanggal_surat'])));
                $sheet->setCellValue('C' . $row, $surat['no_surat']);
                $sheet->setCellValue('D' . $row, $surat['jenis_surat']);
                $sheet->setCellValue('E' . $row, $surat['nik']);
                $sheet->setCellValue('F' . $row, $surat['nama_pemohon']);
                $sheet->setCellValue('G' . $row, $surat['tempat_lahir'] ?? '-');
                $sheet->setCellValue('H' . $row, $surat['tanggal_lahir'] ? date('d-m-Y', strtotime($surat['tanggal_lahir'])) : '-');
                $sheet->setCellValue('I' . $row, $surat['alamat'] ?? '-');
                $sheet->setCellValue('J' . $row, $surat['keperluan'] ?? '-');
                $sheet->setCellValue('K' . $row, $surat['keterangan'] ?? '-');
                $sheet->setCellValue('L' . $row, date('d-m-Y H:i', strtotime($surat['created_at'])));
                
                // Style untuk baris data
                $sheet->getStyle('A' . $row . ':L' . $row)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD']
                        ]
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
                
                // Format khusus untuk kolom tanggal
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DATETIME);
                
                $row++;
            }
        }
        
        // ===== SHEET 2: RINGKASAN STATISTIK =====
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(1);
        $sheet2 = $spreadsheet->getActiveSheet();
        $sheet2->setTitle('Ringkasan');
        
        // Header ringkasan
        $sheet2->setCellValue('A1', 'RINGKASAN DATA SURAT KELUAR');
        $sheet2->mergeCells('A1:C1');
        $sheet2->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '4e73df']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        
        // Informasi filter
        $sheet2->setCellValue('A3', 'Informasi Filter');
        $sheet2->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'E8E8E8']
            ]
        ]);
        
        $row2 = 4;
        $sheet2->setCellValue('A' . $row2, 'Jenis Surat');
        $sheet2->setCellValue('B' . $row2, ': ' . (isset($_GET['jenis_surat']) ? $_GET['jenis_surat'] : 'Semua'));
        $row2++;
        $sheet2->setCellValue('A' . $row2, 'Bulan');
        $sheet2->setCellValue('B' . $row2, ': ' . (isset($_GET['bulan']) ? $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ][(int)$_GET['bulan']] : 'Semua'));
        $row2++;
        $sheet2->setCellValue('A' . $row2, 'Tahun');
        $sheet2->setCellValue('B' . $row2, ': ' . (isset($_GET['tahun']) ? $_GET['tahun'] : 'Semua'));
        $row2++;
        $sheet2->setCellValue('A' . $row2, 'Pencarian');
        $sheet2->setCellValue('B' . $row2, ': ' . (isset($_GET['search']) ? $_GET['search'] : '-'));
        $row2 += 2;
        
        // Statistik
        $sheet2->setCellValue('A' . $row2, 'Statistik');
        $sheet2->getStyle('A' . $row2)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'E8E8E8']
            ]
        ]);
        
        $row2++;
        $sheet2->setCellValue('A' . $row2, 'Total Seluruh Surat');
        $sheet2->setCellValue('B' . $row2, ': ' . $total_all . ' surat');
        $row2++;
        $sheet2->setCellValue('A' . $row2, 'Total Surat (Hasil Filter)');
        $sheet2->setCellValue('B' . $row2, ': ' . $total_filter . ' surat');
        $row2++;
        $sheet2->setCellValue('A' . $row2, 'Tanggal Ekspor');
        $sheet2->setCellValue('B' . $row2, ': ' . tgl_indonesia(date('Y-m-d')) . ' ' . date('H:i:s') . ' WIB');
        $row2 += 2;
        
        // Statistik per jenis surat
        $sheet2->setCellValue('A' . $row2, 'Jumlah per Jenis Surat');
        $sheet2->getStyle('A' . $row2)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'E8E8E8']
            ]
        ]);
        
        $row2++;
        $sheet2->setCellValue('A' . $row2, 'Jenis Surat');
        $sheet2->setCellValue('B' . $row2, 'Jumlah');
        $sheet2->getStyle('A' . $row2 . ':B' . $row2)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'D3D3D3']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
        
        // Query untuk statistik per jenis
        $jenis_surat_list = ['SKD', 'SKTM', 'SKU', 'SKBM', 'SKK', 'SKP'];
        foreach ($jenis_surat_list as $jenis) {
            $row2++;
            $query_jenis = "SELECT COUNT(*) as total FROM arsip_surat WHERE jenis_surat = '$jenis'";
            $result_jenis = mysqli_query($conn, $query_jenis);
            $total_jenis = mysqli_fetch_assoc($result_jenis)['total'];
            
            $sheet2->setCellValue('A' . $row2, $jenis);
            $sheet2->setCellValue('B' . $row2, $total_jenis . ' surat');
            
            $sheet2->getStyle('A' . $row2 . ':B' . $row2)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD']
                    ]
                ]
            ]);
        }
        
        // Atur lebar kolom untuk sheet ringkasan
        $sheet2->getColumnDimension('A')->setWidth(30);
        $sheet2->getColumnDimension('B')->setWidth(20);
        $sheet2->getColumnDimension('C')->setWidth(15);
        
        // ===== SHEET 3: STATISTIK BULANAN =====
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(2);
        $sheet3 = $spreadsheet->getActiveSheet();
        $sheet3->setTitle('Statistik Bulanan');
        
        // Header
        $sheet3->setCellValue('A1', 'STATISTIK SURAT PER BULAN - TAHUN ' . date('Y'));
        $sheet3->mergeCells('A1:C1');
        $sheet3->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '4e73df']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ]
        ]);
        
        // Header tabel
        $sheet3->setCellValue('A3', 'Bulan');
        $sheet3->setCellValue('B3', 'SKD');
        $sheet3->setCellValue('C3', 'SKTM');
        $sheet3->setCellValue('D3', 'SKU');
        $sheet3->setCellValue('E3', 'SKBM');
        $sheet3->setCellValue('F3', 'SKK');
        $sheet3->setCellValue('G3', 'SKP');
        $sheet3->setCellValue('H3', 'Total');
        
        $sheet3->getStyle('A3:H3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'E8E8E8']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ]
        ]);
        
        // Data per bulan
        $bulan_list = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $row3 = 4;
        $tahun = date('Y');
        
        for ($b = 1; $b <= 12; $b++) {
            $sheet3->setCellValue('A' . $row3, $bulan_list[$b]);
            
            $total_bulan = 0;
            $col = 'B';
            
            foreach ($jenis_surat_list as $jenis) {
                $query_bulan = "SELECT COUNT(*) as total FROM arsip_surat 
                               WHERE jenis_surat = '$jenis' 
                               AND YEAR(tanggal_surat) = '$tahun' 
                               AND MONTH(tanggal_surat) = '$b'";
                $result_bulan = mysqli_query($conn, $query_bulan);
                $total_jenis_bulan = mysqli_fetch_assoc($result_bulan)['total'];
                
                $sheet3->setCellValue($col . $row3, $total_jenis_bulan);
                $total_bulan += $total_jenis_bulan;
                
                $sheet3->getStyle($col . $row3)->getAlignment()
                       ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;
            }
            
            $sheet3->setCellValue('H' . $row3, $total_bulan);
            $sheet3->getStyle('H' . $row3)->getAlignment()
                   ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $sheet3->getStyle('A' . $row3 . ':H' . $row3)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD']
                    ]
                ]
            ]);
            
            $row3++;
        }
        
        // Total keseluruhan
        $sheet3->setCellValue('A' . $row3, 'TOTAL');
        $sheet3->getStyle('A' . $row3)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'D3D3D3']
            ]
        ]);
        
        $col = 'B';
        $grand_total = 0;
        foreach ($jenis_surat_list as $jenis) {
            $query_total_jenis = "SELECT COUNT(*) as total FROM arsip_surat 
                                 WHERE jenis_surat = '$jenis' AND YEAR(tanggal_surat) = '$tahun'";
            $result_total_jenis = mysqli_query($conn, $query_total_jenis);
            $total_jenis_tahun = mysqli_fetch_assoc($result_total_jenis)['total'];
            
            $sheet3->setCellValue($col . $row3, $total_jenis_tahun);
            $sheet3->getStyle($col . $row3)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'F0F0F0']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ]
            ]);
            $grand_total += $total_jenis_tahun;
            $col++;
        }
        
        $sheet3->setCellValue('H' . $row3, $grand_total);
        $sheet3->getStyle('H' . $row3)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'F0F0F0']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ]
        ]);
        
        // Atur lebar kolom
        $sheet3->getColumnDimension('A')->setWidth(15);
        for ($c = 'B'; $c <= 'H'; $c++) {
            $sheet3->getColumnDimension($c)->setWidth(10);
        }
        
        // Kembalikan ke sheet pertama
        $spreadsheet->setActiveSheetIndex(0);
        
        // Buat file Excel
        $writer = new Xlsx($spreadsheet);
        
        // Nama file
        $filename = "data_surat_keluar_" . date('Y-m-d_H-i-s') . ".xlsx";
        
        // Set header untuk download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Simpan ke output
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        // Fallback ke Excel basic jika error
        export_excel_basic($result, $total_all, $total_filter);
    }
} else {
    // Fallback ke Excel basic
    export_excel_basic($result, $total_all, $total_filter);
}

// ==================== FUNGSI EXPORT EXCEL BASIC (FALLBACK) ====================
function export_excel_basic($result, $total_all, $total_filter) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="data_surat_keluar_' . date('Y-m-d_H-i-s') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "<html><head><meta charset='UTF-8'>";
    echo "<style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th { 
            background-color: #4e73df; 
            color: white; 
            font-weight: bold; 
            padding: 8px; 
            border: 1px solid #000; 
            text-align: center; 
        }
        td { 
            padding: 6px; 
            border: 1px solid #ddd; 
            vertical-align: top;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #4e73df;
            text-align: center;
            padding: 10px;
        }
        .sub-header {
            background-color: #f8f9fc;
            font-weight: bold;
        }
    </style></head><body>";
    
    // Title
    echo "<h2 class='header-title'>DATA SURAT KELUAR DESA SUKOLILO TIMUR</h2>";
    echo "<p>Tanggal Ekspor: " . date('d-m-Y H:i:s') . "</p>";
    echo "<p>Total Surat: " . $total_filter . " (dari " . $total_all . " total surat)</p>";
    
    // Tabel Data
    echo "<table>";
    
    // Header tabel
    echo "<tr>";
    echo "<th>No</th>";
    echo "<th>Tanggal Surat</th>";
    echo "<th>Nomor Surat</th>";
    echo "<th>Jenis Surat</th>";
    echo "<th>NIK</th>";
    echo "<th>Nama Pemohon</th>";
    echo "<th>Tempat Lahir</th>";
    echo "<th>Tanggal Lahir</th>";
    echo "<th>Alamat</th>";
    echo "<th>Keperluan</th>";
    echo "<th>Keterangan</th>";
    echo "<th>Dibuat Pada</th>";
    echo "</tr>";
    
    // Data
    if (mysqli_num_rows($result) > 0) {
        mysqli_data_seek($result, 0);
        $no = 1;
        while ($surat = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . date('d-m-Y', strtotime($surat['tanggal_surat'])) . "</td>";
            echo "<td>" . htmlspecialchars($surat['no_surat']) . "</td>";
            echo "<td>" . htmlspecialchars($surat['jenis_surat']) . "</td>";
            echo "<td>" . htmlspecialchars($surat['nik']) . "</td>";
            echo "<td>" . htmlspecialchars($surat['nama_pemohon']) . "</td>";
            echo "<td>" . htmlspecialchars($surat['tempat_lahir'] ?? '-') . "</td>";
            echo "<td>" . ($surat['tanggal_lahir'] ? date('d-m-Y', strtotime($surat['tanggal_lahir'])) : '-') . "</td>";
            echo "<td>" . htmlspecialchars($surat['alamat'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($surat['keperluan'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($surat['keterangan'] ?? '-') . "</td>";
            echo "<td>" . date('d-m-Y H:i', strtotime($surat['created_at'])) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='12' class='text-center'>Tidak ada data</td></tr>";
    }
    
    echo "</table>";
    
    // Statistik per jenis
    echo "<br><br>";
    echo "<h3>Statistik per Jenis Surat</h3>";
    echo "<table>";
    echo "<tr><th>Jenis Surat</th><th>Jumlah</th></tr>";
    
    $jenis_surat_list = ['SKD', 'SKTM', 'SKU', 'SKBM', 'SKK', 'SKP'];
    global $conn;
    foreach ($jenis_surat_list as $jenis) {
        $query = "SELECT COUNT(*) as total FROM arsip_surat WHERE jenis_surat = '$jenis'";
        $result_jenis = mysqli_query($conn, $query);
        $total = mysqli_fetch_assoc($result_jenis)['total'];
        echo "<tr><td>$jenis</td><td>$total surat</td></tr>";
    }
    
    echo "</table>";
    
    echo "</body></html>";
    exit;
}
?>