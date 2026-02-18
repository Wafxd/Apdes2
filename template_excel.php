<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

include "db/funct.php";

// Cek parameter type
$type = isset($_GET['type']) ? $_GET['type'] : 'kk';

// Load library PHPExcel
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

try {
    if ($type == 'kk') {
        // Template Data Kartu Keluarga
        $sheet->setTitle('Template KK');
        
        // Header
        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', 'NOMOR KK');
        $sheet->setCellValue('C1', 'NIK KEPALA KELUARGA');
        $sheet->setCellValue('D1', 'NAMA KEPALA KELUARGA');
        $sheet->setCellValue('E1', 'ALAMAT KK');
        $sheet->setCellValue('F1', 'RT');
        $sheet->setCellValue('G1', 'RW');
        $sheet->setCellValue('H1', 'DUSUN');
        $sheet->setCellValue('I1', 'DESA/KEL');
        $sheet->setCellValue('J1', 'KECAMATAN');
        $sheet->setCellValue('K1', 'KABUPATEN/KOTA');
        $sheet->setCellValue('L1', 'PROVINSI');
        $sheet->setCellValue('M1', 'KODE POS');
        
        // Contoh data 4 baris
        $contoh_data = [
            [
                'no' => '1',
                'no_kk' => '3301123456789001',
                'nik_kepala' => '3301123456789001',
                'nama_kepala' => 'SUGENG',
                'alamat' => 'Jl. Merdeka No. 123',
                'rt' => '001',
                'rw' => '002',
                'dusun' => 'Krajan',
                'desa_kel' => 'Sukolilo Timur',
                'kecamatan' => 'Sukolilo',
                'kabupaten' => 'Bangkalan',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '69162'
            ],
            [
                'no' => '2',
                'no_kk' => '3301123456789002',
                'nik_kepala' => '3301123456789002',
                'nama_kepala' => 'BUDI',
                'alamat' => 'Jl. Diponegoro No. 45',
                'rt' => '003',
                'rw' => '004',
                'dusun' => 'Pasar',
                'desa_kel' => 'Sukolilo Barat',
                'kecamatan' => 'Sukolilo',
                'kabupaten' => 'Bangkalan',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '69163'
            ],
            [
                'no' => '3',
                'no_kk' => '3301123456789003',
                'nik_kepala' => '3301123456789003',
                'nama_kepala' => 'SITI',
                'alamat' => 'Jl. Sudirman No. 78',
                'rt' => '005',
                'rw' => '006',
                'dusun' => 'Baru',
                'desa_kel' => 'Sukolilo Utara',
                'kecamatan' => 'Kamal',
                'kabupaten' => 'Bangkalan',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '69164'
            ],
            [
                'no' => '4',
                'no_kk' => '3301123456789004',
                'nik_kepala' => '3301123456789004',
                'nama_kepala' => 'AGUS',
                'alamat' => 'Jl. Gatot Subroto No. 12',
                'rt' => '007',
                'rw' => '008',
                'dusun' => 'Tengah',
                'desa_kel' => 'Sukolilo Selatan',
                'kecamatan' => 'Labang',
                'kabupaten' => 'Bangkalan',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '69165'
            ]
        ];
        
        // Tulis contoh data
        $row = 2;
        foreach ($contoh_data as $data) {
            $sheet->setCellValue('A' . $row, $data['no']);
            $sheet->setCellValue('B' . $row, $data['no_kk']);
            $sheet->setCellValue('C' . $row, $data['nik_kepala']);
            $sheet->setCellValue('D' . $row, $data['nama_kepala']);
            $sheet->setCellValue('E' . $row, $data['alamat']);
            $sheet->setCellValue('F' . $row, $data['rt']);
            $sheet->setCellValue('G' . $row, $data['rw']);
            $sheet->setCellValue('H' . $row, $data['dusun']);
            $sheet->setCellValue('I' . $row, $data['desa_kel']);
            $sheet->setCellValue('J' . $row, $data['kecamatan']);
            $sheet->setCellValue('K' . $row, $data['kabupaten']);
            $sheet->setCellValue('L' . $row, $data['provinsi']);
            $sheet->setCellValue('M' . $row, $data['kode_pos']);
            $row++;
        }
        
        // Format header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4e73df']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);
        
        // Beri border pada contoh data
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A2:M' . ($row-1))->applyFromArray($dataStyle);
        
        // Auto size columns
        foreach(range('A','M') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        $filename = 'Template_KK.xlsx';
        
    } elseif ($type == 'anggota') {
        // Template Data Anggota Keluarga
        $sheet->setTitle('Template Anggota');
        
        // Header
        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', 'NOMOR KK');
        $sheet->setCellValue('C1', 'NIK');
        $sheet->setCellValue('D1', 'NAMA');
        $sheet->setCellValue('E1', 'HUBUNGAN KELUARGA');
        
        // Contoh data 4 baris (2 KK berbeda)
        $contoh_data = [
            [
                'no' => '1',
                'no_kk' => '3301123456789001',
                'nik' => '3301123456789002',
                'nama' => 'SITI',
                'hubungan' => 'Istri'
            ],
            [
                'no' => '2',
                'no_kk' => '3301123456789001',
                'nik' => '3301123456789003',
                'nama' => 'BUDI',
                'hubungan' => 'Anak'
            ],
            [
                'no' => '3',
                'no_kk' => '3301123456789001',
                'nik' => '3301123456789004',
                'nama' => 'ANITA',
                'hubungan' => 'Anak'
            ],
            [
                'no' => '4',
                'no_kk' => '3301123456789002',
                'nik' => '3301123456789005',
                'nama' => 'RINI',
                'hubungan' => 'Istri'
            ]
        ];
        
        // Tulis contoh data
        $row = 2;
        foreach ($contoh_data as $data) {
            $sheet->setCellValue('A' . $row, $data['no']);
            $sheet->setCellValue('B' . $row, $data['no_kk']);
            $sheet->setCellValue('C' . $row, $data['nik']);
            $sheet->setCellValue('D' . $row, $data['nama']);
            $sheet->setCellValue('E' . $row, $data['hubungan']);
            $row++;
        }
        
        // Daftar hubungan keluarga yang valid
        $sheet->setCellValue('G1', 'HUBUNGAN YANG VALID:');
        $valid_hubungan = [
            'Kepala Keluarga',
            'Suami',
            'Istri',
            'Anak',
            'Menantu',
            'Cucu',
            'Orang Tua',
            'Mertua',
            'Famili Lain',
            'Lainnya'
        ];
        
        $row_hubungan = 2;
        foreach ($valid_hubungan as $hubungan) {
            $sheet->setCellValue('G' . $row_hubungan, $hubungan);
            $row_hubungan++;
        }
        
        // Format header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4e73df']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        $sheet->getStyle('G1:G1')->applyFromArray($headerStyle);
        
        // Beri border pada contoh data
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A2:E' . ($row-1))->applyFromArray($dataStyle);
        $sheet->getStyle('G2:G' . ($row_hubungan-1))->applyFromArray($dataStyle);
        
        // Auto size columns
        foreach(range('A','G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        $filename = 'Template_Anggota_Keluarga.xlsx';
        
    } elseif ($type == 'lengkap') {
        // Template Data Lengkap
        $sheet->setTitle('Template Lengkap');
        
        // Header
        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', 'NOMOR KK');
        $sheet->setCellValue('C1', 'NIK');
        $sheet->setCellValue('D1', 'NAMA');
        $sheet->setCellValue('E1', 'HUBUNGAN');
        $sheet->setCellValue('F1', 'ALAMAT KK');
        $sheet->setCellValue('G1', 'RT');
        $sheet->setCellValue('H1', 'RW');
        $sheet->setCellValue('I1', 'DUSUN');
        $sheet->setCellValue('J1', 'DESA/KEL');
        $sheet->setCellValue('K1', 'KECAMATAN');
        $sheet->setCellValue('L1', 'KABUPATEN/KOTA');
        $sheet->setCellValue('M1', 'PROVINSI');
        $sheet->setCellValue('N1', 'KODE POS');
        
        // Contoh data 2 KK dengan masing-masing 2-3 anggota (total 5 baris)
        $contoh_data = [
            // KK 1 - Kepala Keluarga
            [
                'no' => '1',
                'no_kk' => '3301123456789001',
                'nik' => '3301123456789001',
                'nama' => 'SUGENG',
                'hubungan' => 'Kepala Keluarga',
                'alamat' => 'Jl. Merdeka No. 123',
                'rt' => '001',
                'rw' => '002',
                'dusun' => 'Krajan',
                'desa_kel' => 'Sukolilo Timur',
                'kecamatan' => 'Sukolilo',
                'kabupaten' => 'Bangkalan',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '69162'
            ],
            // KK 1 - Istri
            [
                'no' => '2',
                'no_kk' => '3301123456789001',
                'nik' => '3301123456789002',
                'nama' => 'SITI',
                'hubungan' => 'Istri',
                'alamat' => 'Jl. Merdeka No. 123',
                'rt' => '001',
                'rw' => '002',
                'dusun' => 'Krajan',
                'desa_kel' => 'Sukolilo Timur',
                'kecamatan' => 'Sukolilo',
                'kabupaten' => 'Bangkalan',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '69162'
            ],
            // KK 1 - Anak 1
            [
                'no' => '3',
                'no_kk' => '3301123456789001',
                'nik' => '3301123456789003',
                'nama' => 'BUDI',
                'hubungan' => 'Anak',
                'alamat' => 'Jl. Merdeka No. 123',
                'rt' => '001',
                'rw' => '002',
                'dusun' => 'Krajan',
                'desa_kel' => 'Sukolilo Timur',
                'kecamatan' => 'Sukolilo',
                'kabupaten' => 'Bangkalan',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '69162'
            ],
            // KK 2 - Kepala Keluarga
            [
                'no' => '4',
                'no_kk' => '3301123456789002',
                'nik' => '3301123456789004',
                'nama' => 'AGUS',
                'hubungan' => 'Kepala Keluarga',
                'alamat' => 'Jl. Diponegoro No. 45',
                'rt' => '003',
                'rw' => '004',
                'dusun' => 'Pasar',
                'desa_kel' => 'Sukolilo Barat',
                'kecamatan' => 'Sukolilo',
                'kabupaten' => 'Bangkalan',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '69163'
            ],
            // KK 2 - Istri
            [
                'no' => '5',
                'no_kk' => '3301123456789002',
                'nik' => '3301123456789005',
                'nama' => 'RINI',
                'hubungan' => 'Istri',
                'alamat' => 'Jl. Diponegoro No. 45',
                'rt' => '003',
                'rw' => '004',
                'dusun' => 'Pasar',
                'desa_kel' => 'Sukolilo Barat',
                'kecamatan' => 'Sukolilo',
                'kabupaten' => 'Bangkalan',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '69163'
            ]
        ];
        
        // Tulis contoh data
        $row = 2;
        foreach ($contoh_data as $data) {
            $sheet->setCellValue('A' . $row, $data['no']);
            $sheet->setCellValue('B' . $row, $data['no_kk']);
            $sheet->setCellValue('C' . $row, $data['nik']);
            $sheet->setCellValue('D' . $row, $data['nama']);
            $sheet->setCellValue('E' . $row, $data['hubungan']);
            $sheet->setCellValue('F' . $row, $data['alamat']);
            $sheet->setCellValue('G' . $row, $data['rt']);
            $sheet->setCellValue('H' . $row, $data['rw']);
            $sheet->setCellValue('I' . $row, $data['dusun']);
            $sheet->setCellValue('J' . $row, $data['desa_kel']);
            $sheet->setCellValue('K' . $row, $data['kecamatan']);
            $sheet->setCellValue('L' . $row, $data['kabupaten']);
            $sheet->setCellValue('M' . $row, $data['provinsi']);
            $sheet->setCellValue('N' . $row, $data['kode_pos']);
            $row++;
        }
        
        // Format header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4e73df']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);
        
        // Beri border pada contoh data
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A2:N' . ($row-1))->applyFromArray($dataStyle);
        
        // Beri warna berbeda untuk KK yang berbeda (visual grouping)
        $sheet->getStyle('A2:N4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A2:N4')->getFill()->getStartColor()->setARGB('FFF0F8FF'); // Light blue for KK 1
        
        $sheet->getStyle('A5:N6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A5:N6')->getFill()->getStartColor()->setARGB('FFFFF0F5'); // Light pink for KK 2
        
        // Auto size columns
        foreach(range('A','N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        $filename = 'Template_Keluarga_Lengkap.xlsx';
        
    } else {
        throw new Exception('Tipe template tidak valid');
    }
    
    // Tambahkan instruksi
    $sheet->setCellValue('A' . ($row+2), 'PETUNJUK PENGISIAN:');
    $sheet->getStyle('A' . ($row+2))->getFont()->setBold(true);
    
    $petunjuk = [
        "1. Isi data sesuai kolom yang tersedia",
        "2. NIK dan Nomor KK harus 16 digit angka",
        "3. Pastikan NIK sudah terdaftar di data penduduk sebelum mengimport",
        "4. Untuk import anggota, pastikan Nomor KK sudah terdaftar",
        "5. Hapus baris contoh sebelum mengisi data asli",
        "6. Simpan file sebelum mengupload"
    ];
    
    $start_petunjuk = $row + 3;
    foreach ($petunjuk as $index => $text) {
        $sheet->setCellValue('A' . ($start_petunjuk + $index), $text);
    }
    
    // Set header untuk download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Tulis ke output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    echo "<script>
            alert('Error download template: " . addslashes($e->getMessage()) . "');
            window.location.href = 'keluarga.php';
          </script>";
    exit();
}