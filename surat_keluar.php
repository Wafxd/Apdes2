<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

include "db/koneksi.php";
include "db/funct.php";

// ==================== CEK DOMPDF & PHPWORD ====================
$use_dompdf = false;
$use_phpword = false;

if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    $use_dompdf = true;
    $use_phpword = true;
}

// ==================== USE STATEMENT ====================
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

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

// ==================== HANDLE HAPUS SURAT ====================
if (isset($_GET['ajax_hapus_surat'])) {
    header('Content-Type: application/json');
    $id_surat = mysqli_real_escape_string($conn, $_GET['ajax_hapus_surat']);
    
    $query = "DELETE FROM arsip_surat WHERE id_surat = '$id_surat'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_affected_rows($conn) > 0) {
        echo json_encode(['success' => true, 'message' => 'Surat berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus surat']);
    }
    exit();
}

// ==================== HANDLE GET SURAT BY ID ====================
if (isset($_GET['ajax_get_surat'])) {
    header('Content-Type: application/json');
    $id_surat = mysqli_real_escape_string($conn, $_GET['ajax_get_surat']);
    
    $query = "SELECT * FROM arsip_surat WHERE id_surat = '$id_surat'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    }
    exit();
}

// ==================== HANDLE CETAK ULANG ====================
if (isset($_POST['action']) && $_POST['action'] == 'cetak_ulang') {
    $id_surat = mysqli_real_escape_string($conn, $_POST['id_surat']);
    
    $query = "SELECT * FROM arsip_surat WHERE id_surat = '$id_surat'";
    $result = mysqli_query($conn, $query);
    $surat = mysqli_fetch_assoc($result);
    
    if ($surat) {
        // Buat template surat berdasarkan data
        $jenis_surat = $surat['jenis_surat'];
        $no_surat = $surat['no_surat'];
        $nama = $surat['nama_pemohon'];
        $nik = $surat['nik'];
        $tempat_lahir = $surat['tempat_lahir'] ?? '-';
        $tanggal_lahir = $surat['tanggal_lahir'] ? tgl_indonesia($surat['tanggal_lahir']) : '-';
        $alamat = $surat['alamat'] ?? '-';
        $keperluan = $surat['keperluan'] ?? '-';
        $tanggal_surat = tgl_indonesia($surat['tanggal_surat']);
        
        // Buat konten surat
        $konten = '
        <div style="display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
            <div style="flex: 0 0 80px; margin-left: 20px; margin-right: 15px;">
                <img src="img/labang.png" alt="Logo Desa" style="max-width: 80px; max-height: 80px;">
            </div>
            <div style="flex: 1; text-align: center;">
                <h4 style="margin:0; font-weight: bold; font-size: 13pt;">PEMERINTAH KABUPATEN BANGKALAN</h4>
                <h4 style="margin:0; font-weight: bold; font-size: 13pt;">KECAMATAN LABANG</h4>
                <h4 style="margin:0; font-weight: bold; font-size: 13pt;">KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                <p style="margin:0; font-size: 11pt;">Labang 69163</p>
            </div>
        </div>
        
        <div style="text-align: center; margin: 15px 0 15px;">
            <h4 style="text-decoration: underline; font-weight: bold; margin:0 0 3px 0; font-size: 13pt;">SURAT KETERANGAN DOMISILI</h4>
            <p style="margin:0; font-size: 12pt;">NO : ' . $no_surat . '</p>
        </div>
        
        <div style="margin-top: 10px;">
            <p style="margin:6px 0;">Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:</p>
            
            <table style="width: 100%; margin: 10px 0; border-collapse: collapse;">
                <tr>
                    <td style="width: 130px; padding: 3px; vertical-align: top;">N a m a</td>
                    <td style="width: 20px; padding: 3px; vertical-align: top;">:</td>
                    <td style="padding: 3px; vertical-align: top;">' . $nama . '</td>
                </tr>
                <tr>
                    <td style="padding: 3px; vertical-align: top;">Tempat / Tgl Lahir</td>
                    <td style="padding: 3px; vertical-align: top;">:</td>
                    <td style="padding: 3px; vertical-align: top;">' . $tempat_lahir . ', ' . $tanggal_lahir . '</td>
                </tr>
                <tr>
                    <td style="padding: 3px; vertical-align: top;">NIK</td>
                    <td style="padding: 3px; vertical-align: top;">:</td>
                    <td style="padding: 3px; vertical-align: top;">' . $nik . '</td>
                </tr>
                <tr>
                    <td style="padding: 3px; vertical-align: top;">Alamat</td>
                    <td style="padding: 3px; vertical-align: top;">:</td>
                    <td style="padding: 3px; vertical-align: top;">' . $alamat . '</td>
                </tr>
            </table>
            
            <p style="margin-top: 10px;">menerangkan dengan sebenarnya bahwa orang tersebut di atas benar-benar berdomisili di Dsn. Paserean Desa Sukolilo Timur.</p>
            
            <p style="margin-top: 15px;">Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
        </div>
        
        <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
            <div style="text-align: center; width: 300px;">
                <p>Sukolilo Timur, ' . $tanggal_surat . '</p>
                <p style="margin-top: 10px;">Kepala Desa Sukolilo Timur</p>
                
                <div style="margin: 15px 0;">';
        
        if (file_exists('img/ttd.png')) {
            $konten .= '<img src="img/ttd.png" alt="Barcode" style="max-width: 100px; max-height: 100px;">';
        }
        
        $konten .= '
                </div>
                
                <p style="text-decoration: underline; font-weight: bold; margin:0;">H. ZAINAL ABIDIN</p>
            </div>
        </div>';
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Cetak Ulang Surat - <?php echo $surat['no_surat']; ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Times New Roman', Times, serif;
                    font-size: 12pt;
                    line-height: 1.4;
                    margin: 1.5cm;
                    background: white;
                }
                .kop-surat {
                    display: flex;
                    align-items: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                }
                .logo-container {
                    flex: 0 0 80px;
                    margin-left: 20px;
                    margin-right: 15px;
                }
                .logo-container img {
                    max-width: 80px;
                    max-height: 80px;
                }
                .kop-text {
                    flex: 1;
                    text-align: center;
                }
                .kop-text h4 {
                    margin: 0;
                    font-weight: bold;
                    font-size: 13pt;
                    line-height: 1.3;
                }
                .kop-text p { margin: 0; font-size: 11pt; }
                .judul-surat { text-align: center; margin: 15px 0; }
                .judul-surat h4 {
                    text-decoration: underline;
                    font-weight: bold;
                    margin: 0 0 3px 0;
                    font-size: 13pt;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                td {
                    padding: 3px;
                    vertical-align: top;
                }
                td:first-child { width: 130px; }
                .ttd-area {
                    margin-top: 40px;
                    display: flex;
                    justify-content: flex-end;
                }
                .ttd-box {
                    text-align: center;
                    width: 300px;
                }
                .barcode-container { margin: 10px 0; }
                .barcode-container img { max-width: 120px; max-height: 120px; }
                .underline { text-decoration: underline; }
                .no-print { display: none !important; }
                @media print {
                    @page { margin: 1.5cm; }
                    body { margin: 0; padding: 0; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="surat-container">
                <?php echo $konten; ?>
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    window.onafterprint = function() { window.close(); };
                }
            </script>
        </body>
        </html>
        <?php
    }
    exit();
}

// ==================== HANDLE DOWNLOAD PDF ====================
if (isset($_POST['action']) && $_POST['action'] == 'download_pdf_arsip') {
    $id_surat = mysqli_real_escape_string($conn, $_POST['id_surat']);
    
    $query = "SELECT * FROM arsip_surat WHERE id_surat = '$id_surat'";
    $result = mysqli_query($conn, $query);
    $surat = mysqli_fetch_assoc($result);
    
    if ($surat) {
        // Buat template surat (sama seperti di atas)
        $jenis_surat = $surat['jenis_surat'];
        $no_surat = $surat['no_surat'];
        $nama = $surat['nama_pemohon'];
        $nik = $surat['nik'];
        $tempat_lahir = $surat['tempat_lahir'] ?? '-';
        $tanggal_lahir = $surat['tanggal_lahir'] ? tgl_indonesia($surat['tanggal_lahir']) : '-';
        $alamat = $surat['alamat'] ?? '-';
        $keperluan = $surat['keperluan'] ?? '-';
        $tanggal_surat = tgl_indonesia($surat['tanggal_surat']);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Surat - ' . $surat['no_surat'] . '</title>
            <style>
                body {
                    font-family: "Times New Roman", Times, serif;
                    font-size: 12pt;
                    line-height: 1.4;
                    margin: 1.5cm;
                }
                .kop-surat {
                    display: flex;
                    align-items: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                }
                .logo-container {
                    flex: 0 0 80px;
                    margin-left: 20px;
                    margin-right: 15px;
                }
                .logo-container img {
                    max-width: 80px;
                    max-height: 80px;
                }
                .kop-text {
                    flex: 1;
                    text-align: center;
                }
                .kop-text h4 {
                    margin: 0;
                    font-weight: bold;
                    font-size: 13pt;
                }
                .kop-text p { margin: 0; font-size: 11pt; }
                .judul-surat { text-align: center; margin: 15px 0; }
                .judul-surat h4 {
                    text-decoration: underline;
                    margin: 0 0 3px 0;
                    font-size: 13pt;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                td {
                    padding: 3px;
                    vertical-align: top;
                }
                td:first-child { width: 130px; }
                .ttd-area {
                    margin-top: 40px;
                    display: flex;
                    justify-content: flex-end;
                }
                .ttd-box {
                    text-align: center;
                    width: 300px;
                }
                .barcode-container { margin: 10px 0; }
                .barcode-container img { max-width: 120px; max-height: 120px; }
                .underline { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div style="display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
                <div style="flex: 0 0 80px; margin-left: 20px; margin-right: 15px;">
                    <img src="img/labang.png" alt="Logo Desa" style="max-width: 80px; max-height: 80px;">
                </div>
                <div style="flex: 1; text-align: center;">
                    <h4 style="margin:0; font-weight: bold; font-size: 13pt;">PEMERINTAH KABUPATEN BANGKALAN</h4>
                    <h4 style="margin:0; font-weight: bold; font-size: 13pt;">KECAMATAN LABANG</h4>
                    <h4 style="margin:0; font-weight: bold; font-size: 13pt;">KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                    <p style="margin:0; font-size: 11pt;">Labang 69163</p>
                </div>
            </div>
            
            <div style="text-align: center; margin: 15px 0 15px;">
                <h4 style="text-decoration: underline; font-weight: bold; margin:0 0 3px 0; font-size: 13pt;">SURAT KETERANGAN DOMISILI</h4>
                <p style="margin:0; font-size: 12pt;">NO : ' . $no_surat . '</p>
            </div>
            
            <div style="margin-top: 10px;">
                <p style="margin:6px 0;">Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:</p>
                
                <table style="width: 100%; margin: 10px 0; border-collapse: collapse;">
                    <tr>
                        <td style="width: 130px; padding: 3px; vertical-align: top;">N a m a</td>
                        <td style="width: 20px; padding: 3px; vertical-align: top;">:</td>
                        <td style="padding: 3px; vertical-align: top;">' . $nama . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px; vertical-align: top;">Tempat / Tgl Lahir</td>
                        <td style="padding: 3px; vertical-align: top;">:</td>
                        <td style="padding: 3px; vertical-align: top;">' . $tempat_lahir . ', ' . $tanggal_lahir . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px; vertical-align: top;">NIK</td>
                        <td style="padding: 3px; vertical-align: top;">:</td>
                        <td style="padding: 3px; vertical-align: top;">' . $nik . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px; vertical-align: top;">Alamat</td>
                        <td style="padding: 3px; vertical-align: top;">:</td>
                        <td style="padding: 3px; vertical-align: top;">' . $alamat . '</td>
                    </tr>
                </table>
                
                <p style="margin-top: 10px;">menerangkan dengan sebenarnya bahwa orang tersebut di atas benar-benar berdomisili di Dsn. Paserean Desa Sukolilo Timur.</p>
                
                <p style="margin-top: 15px;">Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
            </div>
            
            <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
                <div style="text-align: center; width: 300px;">
                    <p>Sukolilo Timur, ' . $tanggal_surat . '</p>
                    <p style="margin-top: 10px;">Kepala Desa Sukolilo Timur</p>
                    
                    <div style="margin: 15px 0;">';
        
        if (file_exists('img/ttd.png')) {
            $html .= '<img src="img/ttd.png" alt="Barcode" style="max-width: 100px; max-height: 100px;">';
        }
        
        $html .= '
                    </div>
                    
                    <p style="text-decoration: underline; font-weight: bold; margin:0;">H. ZAINAL ABIDIN</p>
                </div>
            </div>
        </body>
        </html>';
        
        if ($use_dompdf) {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Times New Roman');
            
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $filename = "surat_" . preg_replace('/[^a-zA-Z0-9]/', '_', $surat['no_surat']) . ".pdf";
            $dompdf->stream($filename, array("Attachment" => true));
            exit;
        } else {
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="surat_' . date('Ymd_His') . '.html"');
            echo $html;
            exit;
        }
    }
}

// ==================== HANDLE DOWNLOAD WORD ====================
if (isset($_POST['action']) && $_POST['action'] == 'download_word_arsip') {
    $id_surat = mysqli_real_escape_string($conn, $_POST['id_surat']);
    
    $query = "SELECT * FROM arsip_surat WHERE id_surat = '$id_surat'";
    $result = mysqli_query($conn, $query);
    $surat = mysqli_fetch_assoc($result);
    
    if ($surat && $use_phpword) {
        // Buat dokumen Word
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginLeft' => 1440,
            'marginRight' => 1440,
            'marginTop' => 1440,
            'marginBottom' => 1440
        ]);
        
        // Set font default
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);
        
        // Kop surat dengan logo
        $table = $section->addTable(['borderSize' => 0, 'borderColor' => '000000', 'width' => '100%']);
        $table->addRow();
        
        // Kolom untuk logo
        $cellLogo = $table->addCell(1500);
        if (file_exists('img/labang.png')) {
            $cellLogo->addImage('img/labang.png', [
                'width' => 80,
                'height' => 80,
                'alignment' => Jc::CENTER
            ]);
        }
        
        // Kolom untuk teks kop surat
        $cellText = $table->addCell(8500);
        $cellText->addText('PEMERINTAH KABUPATEN BANGKALAN', ['bold' => true], ['alignment' => Jc::CENTER]);
        $cellText->addText('KECAMATAN LABANG', ['bold' => true], ['alignment' => Jc::CENTER]);
        $cellText->addText('KANTOR KEPALA DESA SUKOLILO TIMUR', ['bold' => true], ['alignment' => Jc::CENTER]);
        $cellText->addText('Labang 69163', [], ['alignment' => Jc::CENTER]);
        
        // Garis bawah
        $section->addTextBreak(0.5);
        $section->addLine(['weight' => 2, 'width' => '100%', 'height' => 0]);
        $section->addTextBreak(0.5);
        
        // Judul surat
        $section->addText('SURAT KETERANGAN DOMISILI', ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
        $section->addText('NO : ' . $surat['no_surat'], [], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);
        
        // Isi surat
        $section->addText('Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:');
        $section->addTextBreak(0.5);
        
        // Data tabel
        $tableData = $section->addTable(['borderSize' => 0]);
        $dataRows = [
            ['N a m a', $surat['nama_pemohon']],
            ['Tempat / Tgl Lahir', ($surat['tempat_lahir'] ?? '') . ', ' . tgl_indonesia($surat['tanggal_lahir'] ?? '')],
            ['NIK', $surat['nik']],
            ['Alamat', $surat['alamat'] ?? '-'],
            ['Keperluan', $surat['keperluan'] ?? '-']
        ];
        
        foreach ($dataRows as $row) {
            $tableData->addRow();
            $tableData->addCell(2000)->addText($row[0]);
            $tableData->addCell(500)->addText(':');
            $tableData->addCell(6500)->addText($row[1]);
        }
        
        $section->addTextBreak(1);
        $section->addText('menerangkan dengan sebenarnya bahwa orang tersebut di atas benar-benar berdomisili di Dsn. Paserean Desa Sukolilo Timur.');
        $section->addTextBreak(1);
        $section->addText('Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.');
        $section->addTextBreak(2);
        
        // Tanda tangan
        $section->addText('Sukolilo Timur, ' . tgl_indonesia($surat['tanggal_surat']), [], ['alignment' => Jc::RIGHT]);
        $section->addText('Kepala Desa Sukolilo Timur', [], ['alignment' => Jc::RIGHT]);
        $section->addTextBreak(0.5);
        
        // Barcode
        if (file_exists('img/ttd.png')) {
            $section->addImage('img/ttd.png', [
                'width' => 100,
                'height' => 100,
                'alignment' => Jc::RIGHT
            ]);
        }
        
        $section->addTextBreak(0.5);
        $section->addText('H. ZAINAL ABIDIN', ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::RIGHT]);
        
        // Simpan file Word
        $filename = "surat_" . preg_replace('/[^a-zA-Z0-9]/', '_', $surat['no_surat']) . ".docx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save('php://output');
        exit;
    }
}

// ==================== FILTER DAN PENCARIAN ====================
$where = "WHERE 1=1";
$params = [];

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

// Hitung statistik
$tahun_ini = date('Y');
$bulan_ini = date('m');
$tanggal_ini = date('Y-m-d');

$query_total = "SELECT COUNT(*) as total FROM arsip_surat";
$result_total = mysqli_query($conn, $query_total);
$total_all = mysqli_fetch_assoc($result_total)['total'];

$query_bulan_ini = "SELECT COUNT(*) as total FROM arsip_surat WHERE YEAR(tanggal_surat) = '$tahun_ini' AND MONTH(tanggal_surat) = '$bulan_ini'";
$result_bulan_ini = mysqli_query($conn, $query_bulan_ini);
$total_bulan_ini = mysqli_fetch_assoc($result_bulan_ini)['total'];

$query_hari_ini = "SELECT COUNT(*) as total FROM arsip_surat WHERE tanggal_surat = '$tanggal_ini'";
$result_hari_ini = mysqli_query($conn, $query_hari_ini);
$total_hari_ini = mysqli_fetch_assoc($result_hari_ini)['total'];

$query_sktm = "SELECT COUNT(*) as total FROM arsip_surat WHERE jenis_surat = 'SKTM' AND YEAR(tanggal_surat) = '$tahun_ini'";
$result_sktm = mysqli_query($conn, $query_sktm);
$total_sktm = mysqli_fetch_assoc($result_sktm)['total'];

$pageTitle = "Arsip Surat Keluar";
$pageHeaderButton = '<a href="surat.php" class="btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
</a>';

ob_start();
?>

<style>
/* ===== STYLE KHUSUS ===== */
.statistik-card {
    transition: transform 0.2s;
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.statistik-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.border-left-primary {
    border-left: 4px solid #4e73df !important;
}

.border-left-success {
    border-left: 4px solid #1cc88a !important;
}

.border-left-info {
    border-left: 4px solid #36b9cc !important;
}

.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}

/* Styling untuk filter card dengan tabel tanpa border */
.filter-card {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.filter-card .card-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    border-radius: 12px 12px 0 0 !important;
    color: white;
}

.filter-table {
    width: 100%;
    border-collapse: collapse;
}

.filter-table td {
    padding: 8px 10px;
    vertical-align: middle;
    border: none;
}

.filter-table td.label {
    font-weight: 600;
    color: #4e73df;
    width: 120px;
}

.filter-table td.separator {
    width: 20px;
    text-align: center;
    color: #6c757d;
}

/* Styling untuk tabel data */
.table thead th {
    background-color: #f8f9fc;
    border-bottom: 2px solid #4e73df;
    color: #4e73df;
    font-weight: 600;
}

.table tbody tr:hover {
    background-color: #f8f9fc;
}

/* Styling untuk badge */
.badge-sktm {
    background-color: #4e73df;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
}

.badge-sku {
    background-color: #1cc88a;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
}

.badge-skd {
    background-color: #36b9cc;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
}

.badge-skbm {
    background-color: #f6c23e;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
}

.badge-skk {
    background-color: #e74a3b;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
}

.badge-skp {
    background-color: #6f42c1;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
}

/* Styling untuk tombol aksi */
.btn-group-action {
    display: flex;
    gap: 3px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-group-action .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 6px;
    transition: all 0.2s;
}

.btn-group-action .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-excel {
    background: #28a745;
    color: white;
    border: none;
}

.btn-excel:hover {
    background: #218838;
}

/* Styling untuk modal */
.modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-header {
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    padding: 1rem 1.5rem;
}

.modal-header.bg-info {
    background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%);
}

.modal-header.bg-warning {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
    color: #fff;
}

.modal-header.bg-danger {
    background: linear-gradient(135deg, #e74a3b 0%, #be2e22 100%);
}

.modal-body {
    padding: 1.5rem;
    max-height: 70vh;
    overflow-y: auto;
}

.modal-body::-webkit-scrollbar {
    width: 8px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.modal-footer {
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
}

/* Styling untuk backdrop */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop.show {
    opacity: 0.5;
}

/* Styling untuk preview surat */
.surat-preview {
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    line-height: 1.6;
    padding: 30px;
    background: white;
}

.surat-preview .kop-surat {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
}

.surat-preview .logo-container {
    flex: 0 0 80px;
    margin-left: 20px;
    margin-right: 15px;
}

.surat-preview .logo-container img {
    max-width: 80px;
    max-height: 80px;
}

.surat-preview .kop-text {
    flex: 1;
    text-align: center;
}

.surat-preview .kop-text h4 {
    margin: 0;
    font-weight: bold;
    font-size: 13pt;
}

.surat-preview .judul-surat {
    text-align: center;
    margin: 15px 0;
}

.surat-preview .judul-surat h4 {
    text-decoration: underline;
    margin: 0 0 3px 0;
    font-size: 13pt;
}

.surat-preview table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
}

.surat-preview td {
    padding: 3px;
    vertical-align: top;
}

.surat-preview td:first-child {
    width: 130px;
}

/* Styling untuk form select dan input */
.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #d1d3e2;
    padding: 0.5rem 0.75rem;
    transition: all 0.2s;
}

.form-control:focus, .form-select:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

/* Tombol aksi */
.btn-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    border: none;
    border-radius: 8px;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2e59d9 0%, #1a3a9e 100%);
}

.btn-secondary {
    background: linear-gradient(135deg, #858796 0%, #60616f 100%);
    border: none;
    border-radius: 8px;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #6b6d7d 0%, #4a4b56 100%);
}

.btn-info {
    background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%);
    border: none;
    color: white;
}

.btn-info:hover {
    background: linear-gradient(135deg, #2c9faf 0%, #147a8a 100%);
}

.btn-warning {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
    border: none;
    color: white;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #e2b33b 0%, #c49109 100%);
}

.btn-danger {
    background: linear-gradient(135deg, #e74a3b 0%, #be2e22 100%);
    border: none;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #d33426 0%, #9e251b 100%);
}

.btn-excel {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    border: none;
    color: white;
}

.btn-excel:hover {
    background: linear-gradient(135deg, #218838 0%, #19692c 100%);
}

/* Responsive */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .btn-group-action {
        flex-wrap: wrap;
    }
    
    .filter-table td {
        display: block;
        width: 100%;
        padding: 5px;
    }
    
    .filter-table td.separator {
        display: none;
    }
}
</style>

<!-- Content Row -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Arsip Surat Keluar</h1>
        <div class="d-flex gap-2">
            <a href="export_surat.php" class="btn btn-excel btn-sm shadow-sm">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </a>
            <span class="badge bg-primary text-white p-2">
                <i class="fas fa-envelope me-1"></i> Total: <?php echo $total_all; ?> Surat
            </span>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card statistik-card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Surat Keluar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_all; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card statistik-card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Surat Bulan Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_bulan_ini; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card statistik-card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                SKTM (Tahun Ini)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_sktm; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card statistik-card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Surat Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_hari_ini; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card dengan Tabel Tanpa Border -->
    <div class="card filter-card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-filter me-2"></i>Filter Surat
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" id="filterForm">
                <table class="filter-table">
                    <tr>
                        <td class="label">Jenis Surat</td>
                        <td class="separator">:</td>
                        <td>
                            <select class="form-select" name="jenis_surat" id="jenis_surat">
                                <option value="">Semua Jenis Surat</option>
                                <option value="SKD" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKD') ? 'selected' : ''; ?>>Surat Keterangan Domisili (SKD)</option>
                                <option value="SKTM" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKTM') ? 'selected' : ''; ?>>Surat Keterangan Tidak Mampu (SKTM)</option>
                                <option value="SKU" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKU') ? 'selected' : ''; ?>>Surat Keterangan Usaha (SKU)</option>
                                <option value="SKBM" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKBM') ? 'selected' : ''; ?>>Surat Keterangan Belum Menikah</option>
                                <option value="SKK" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKK') ? 'selected' : ''; ?>>Surat Keterangan Kematian</option>
                                <option value="SKP" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKP') ? 'selected' : ''; ?>>Surat Keterangan Penghasilan</option>
                            </select>
                        </td>
                        <td class="label">Bulan</td>
                        <td class="separator">:</td>
                        <td>
                            <select class="form-select" name="bulan" id="bulan">
                                <option value="">Semua Bulan</option>
                                <option value="1" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '1') ? 'selected' : ''; ?>>Januari</option>
                                <option value="2" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '2') ? 'selected' : ''; ?>>Februari</option>
                                <option value="3" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '3') ? 'selected' : ''; ?>>Maret</option>
                                <option value="4" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '4') ? 'selected' : ''; ?>>April</option>
                                <option value="5" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '5') ? 'selected' : ''; ?>>Mei</option>
                                <option value="6" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '6') ? 'selected' : ''; ?>>Juni</option>
                                <option value="7" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '7') ? 'selected' : ''; ?>>Juli</option>
                                <option value="8" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '8') ? 'selected' : ''; ?>>Agustus</option>
                                <option value="9" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '9') ? 'selected' : ''; ?>>September</option>
                                <option value="10" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '10') ? 'selected' : ''; ?>>Oktober</option>
                                <option value="11" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '11') ? 'selected' : ''; ?>>November</option>
                                <option value="12" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == '12') ? 'selected' : ''; ?>>Desember</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Tahun</td>
                        <td class="separator">:</td>
                        <td>
                            <select class="form-select" name="tahun" id="tahun">
                                <option value="">Semua Tahun</option>
                                <?php
                                $tahun_sekarang = date('Y');
                                for ($t = $tahun_sekarang; $t >= $tahun_sekarang - 5; $t--):
                                ?>
                                <option value="<?php echo $t; ?>" <?php echo (isset($_GET['tahun']) && $_GET['tahun'] == $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                <?php endfor; ?>
                            </select>
                        </td>
                        <td class="label">Pencarian</td>
                        <td class="separator">:</td>
                        <td>
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" id="search" 
                                       placeholder="No. Surat / Nama / NIK" 
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" class="text-end pt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Terapkan Filter
                            </button>
                            <?php if (isset($_GET['jenis_surat']) || isset($_GET['bulan']) || isset($_GET['tahun']) || isset($_GET['search'])): ?>
                            <a href="surat_keluar.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-times me-2"></i>Reset
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list me-2"></i>Daftar Surat Keluar
            </h6>
            <span class="badge bg-primary text-white">
                Ditemukan: <?php echo $total_surat; ?> surat
            </span>
        </div>
        <div class="card-body">
            <?php if ($total_surat > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Tanggal</th>
                            <th>Nomor Surat</th>
                            <th>Jenis Surat</th>
                            <th>Nama Pemohon</th>
                            <th>NIK</th>
                            <th>Keperluan</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($surat = mysqli_fetch_assoc($result)): 
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($surat['tanggal_surat'])); ?></td>
                            <td><?php echo htmlspecialchars($surat['no_surat']); ?></td>
                            <td>
                                <?php
                                $badge_class = '';
                                $label = '';
                                switch($surat['jenis_surat']) {
                                    case 'SKD':
                                        $badge_class = 'badge-skd';
                                        $label = 'SKD';
                                        break;
                                    case 'SKTM':
                                        $badge_class = 'badge-sktm';
                                        $label = 'SKTM';
                                        break;
                                    case 'SKU':
                                        $badge_class = 'badge-sku';
                                        $label = 'SKU';
                                        break;
                                    case 'SKBM':
                                        $badge_class = 'badge-skbm';
                                        $label = 'SKBM';
                                        break;
                                    case 'SKK':
                                        $badge_class = 'badge-skk';
                                        $label = 'SKK';
                                        break;
                                    case 'SKP':
                                        $badge_class = 'badge-skp';
                                        $label = 'SKP';
                                        break;
                                    default:
                                        $badge_class = 'badge-secondary';
                                        $label = $surat['jenis_surat'];
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $label; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($surat['nama_pemohon']); ?></td>
                            <td><?php echo htmlspecialchars($surat['nik']); ?></td>
                            <td><?php echo htmlspecialchars($surat['keperluan'] ?: '-'); ?></td>
                            <td>
                                <div class="btn-group-action">
                                    <button type="button" class="btn btn-sm btn-info" onclick="lihatSurat('<?php echo $surat['id_surat']; ?>')" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="cetakUlang('<?php echo $surat['id_surat']; ?>')" title="Cetak Ulang">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="downloadPDF('<?php echo $surat['id_surat']; ?>')" title="Download PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="downloadWord('<?php echo $surat['id_surat']; ?>')" title="Download Word">
                                        <i class="fas fa-file-word"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="hapusSurat('<?php echo $surat['id_surat']; ?>', '<?php echo htmlspecialchars($surat['no_surat']); ?>')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Tidak ada data surat keluar. Silakan buat surat terlebih dahulu.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Lihat Surat -->
<div class="modal fade" id="viewSuratModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-3">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Detail Surat
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalView()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewSuratContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 fs-5">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" onclick="closeModalView()">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
                <button type="button" class="btn btn-primary" onclick="printFromModal()">
                    <i class="fas fa-print me-2"></i>Cetak
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="confirmHapusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalHapus()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-4x text-danger"></i>
                </div>
                <p class="fs-5 text-center">Yakin ingin menghapus surat ini?</p>
                <div id="confirmHapusInfo" class="alert alert-warning py-2">
                    <small>Memuat data...</small>
                </div>
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Data yang dihapus tidak dapat dikembalikan!</strong>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" onclick="closeModalHapus()">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmHapus">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ===== VARIABEL GLOBAL =====
let modalViewInstance = null;
let modalHapusInstance = null;

// ===== FUNGSI CLOSE MODAL =====
function closeModalView() {
    if (modalViewInstance) {
        modalViewInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeModalHapus() {
    if (modalHapusInstance) {
        modalHapusInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function cleanupBackdrop() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

// ===== INISIALISASI MODAL =====
document.addEventListener('DOMContentLoaded', function() {
    const viewModalEl = document.getElementById('viewSuratModal');
    const hapusModalEl = document.getElementById('confirmHapusModal');
    
    if (viewModalEl) {
        modalViewInstance = new bootstrap.Modal(viewModalEl);
    }
    if (hapusModalEl) {
        modalHapusInstance = new bootstrap.Modal(hapusModalEl);
    }
    
    // Cleanup backdrop saat modal ditutup
    document.querySelectorAll('.modal').forEach(modalEl => {
        modalEl.addEventListener('hidden.bs.modal', function() {
            cleanupBackdrop();
        });
    });
});

// ===== FUNGSI LIHAT SURAT =====
function lihatSurat(id) {
    const contentDiv = document.getElementById('viewSuratContent');
    contentDiv.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 fs-5">Memuat data...</p>
        </div>
    `;
    
    if (modalViewInstance) {
        modalViewInstance.show();
    }
    
    fetch('surat_keluar.php?ajax_get_surat=' + id)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;
                const tglSurat = new Date(data.tanggal_surat).toLocaleDateString('id-ID', {
                    day: '2-digit', month: 'long', year: 'numeric'
                });
                
                let html = `
                    <div class="surat-preview">
                        <div class="kop-surat">
                            <div class="logo-container">
                                <img src="img/labang.png" alt="Logo Desa">
                            </div>
                            <div class="kop-text">
                                <h4>PEMERINTAH KABUPATEN BANGKALAN</h4>
                                <h4>KECAMATAN LABANG</h4>
                                <h4>KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                                <p>Labang 69163</p>
                            </div>
                        </div>
                        
                        <div class="judul-surat">
                            <h4>SURAT KETERANGAN DOMISILI</h4>
                            <p>NO : ${data.no_surat}</p>
                        </div>
                        
                        <div style="margin-top: 10px;">
                            <p>Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:</p>
                            
                            <table>
                                <tr>
                                    <td>N a m a</td>
                                    <td>:</td>
                                    <td>${data.nama_pemohon}</td>
                                </tr>
                                <tr>
                                    <td>Tempat / Tgl Lahir</td>
                                    <td>:</td>
                                    <td>${data.tempat_lahir ? data.tempat_lahir + ', ' + new Date(data.tanggal_lahir).toLocaleDateString('id-ID', {day: '2-digit', month: 'long', year: 'numeric'}) : '-'}</td>
                                </tr>
                                <tr>
                                    <td>NIK</td>
                                    <td>:</td>
                                    <td>${data.nik}</td>
                                </tr>
                                <tr>
                                    <td>Alamat</td>
                                    <td>:</td>
                                    <td>${data.alamat || '-'}</td>
                                </tr>
                                <tr>
                                    <td>Keperluan</td>
                                    <td>:</td>
                                    <td>${data.keperluan || '-'}</td>
                                </tr>
                            </table>
                            
                            <p style="margin-top: 10px;">menerangkan dengan sebenarnya bahwa orang tersebut di atas benar-benar berdomisili di Dsn. Paserean Desa Sukolilo Timur.</p>
                            
                            <p style="margin-top: 15px;">Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
                        </div>
                        
                        <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
                            <div style="text-align: center; width: 300px;">
                                <p>Sukolilo Timur, ${tglSurat}</p>
                                <p style="margin-top: 10px;">Kepala Desa Sukolilo Timur</p>
                                
                                <div style="margin: 15px 0;">
                `;
                
                if (data.ttd_image) {
                    html += `<img src="img/ttd.png" alt="Barcode" style="max-width: 100px; max-height: 100px;">`;
                }
                
                html += `
                                </div>
                                
                                <p style="text-decoration: underline; font-weight: bold; margin:0;">H. ZAINAL ABIDIN</p>
                            </div>
                        </div>
                    </div>
                `;
                
                contentDiv.innerHTML = html;
            } else {
                contentDiv.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Gagal mengambil data: ${result.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contentDiv.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Terjadi kesalahan saat mengambil data
                </div>
            `;
        });
}

// ===== FUNGSI CETAK ULANG =====
function cetakUlang(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'surat_keluar.php';
    form.target = '_blank';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'cetak_ulang';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id_surat';
    idInput.value = id;
    
    form.appendChild(actionInput);
    form.appendChild(idInput);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// ===== FUNGSI DOWNLOAD PDF =====
function downloadPDF(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'surat_keluar.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'download_pdf_arsip';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id_surat';
    idInput.value = id;
    
    form.appendChild(actionInput);
    form.appendChild(idInput);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// ===== FUNGSI DOWNLOAD WORD =====
function downloadWord(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'surat_keluar.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'download_word_arsip';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id_surat';
    idInput.value = id;
    
    form.appendChild(actionInput);
    form.appendChild(idInput);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// ===== FUNGSI CETAK DARI MODAL =====
function printFromModal() {
    const content = document.getElementById('viewSuratContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Cetak Surat</title>
            <style>
                body {
                    font-family: 'Times New Roman', Times, serif;
                    font-size: 12pt;
                    line-height: 1.4;
                    margin: 1.5cm;
                }
                .kop-surat {
                    display: flex;
                    align-items: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                }
                .logo-container {
                    flex: 0 0 80px;
                    margin-left: 20px;
                    margin-right: 15px;
                }
                .logo-container img {
                    max-width: 80px;
                    max-height: 80px;
                }
                .kop-text {
                    flex: 1;
                    text-align: center;
                }
                .kop-text h4 {
                    margin: 0;
                    font-weight: bold;
                    font-size: 13pt;
                }
                .judul-surat {
                    text-align: center;
                    margin: 15px 0;
                }
                .judul-surat h4 {
                    text-decoration: underline;
                    margin: 0 0 3px 0;
                    font-size: 13pt;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                td {
                    padding: 3px;
                    vertical-align: top;
                }
                td:first-child {
                    width: 130px;
                }
                .ttd-area {
                    margin-top: 40px;
                    display: flex;
                    justify-content: flex-end;
                }
                .ttd-box {
                    text-align: center;
                    width: 300px;
                }
                .barcode-container {
                    margin: 15px 0;
                }
                .barcode-container img {
                    max-width: 100px;
                    max-height: 100px;
                }
                .underline {
                    text-decoration: underline;
                }
                @media print {
                    @page { margin: 1.5cm; }
                    body { margin: 0; padding: 0; }
                }
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);
    printWindow.document.close();
}

// ===== FUNGSI HAPUS SURAT =====
function hapusSurat(id, no_surat) {
    const infoDiv = document.getElementById('confirmHapusInfo');
    if (infoDiv) {
        infoDiv.innerHTML = `
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td width="40%" class="text-muted">Nomor Surat</td>
                    <td><strong>${no_surat}</strong></td>
                </tr>
            </table>
        `;
    }
    
    const confirmBtn = document.getElementById('btnConfirmHapus');
    if (confirmBtn) {
        confirmBtn.setAttribute('data-id', id);
    }
    
    if (modalHapusInstance) {
        modalHapusInstance.show();
    }
}

// Tombol konfirmasi hapus
document.getElementById('btnConfirmHapus')?.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    if (!id) return;
    
    const originalText = this.innerHTML;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
    this.disabled = true;
    
    fetch('surat_keluar.php?ajax_hapus_surat=' + id)
        .then(response => response.json())
        .then(data => {
            if (modalHapusInstance) {
                modalHapusInstance.hide();
            }
            
            if (data.success) {
                alert('Surat berhasil dihapus');
                setTimeout(() => window.location.reload(), 500);
            } else {
                alert('Gagal menghapus surat: ' + data.message);
            }
            
            this.innerHTML = originalText;
            this.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            if (modalHapusInstance) {
                modalHapusInstance.hide();
            }
            alert('Terjadi kesalahan saat menghapus data');
            this.innerHTML = originalText;
            this.disabled = false;
        });
});

// ===== FORM FILTER SUBMIT =====
document.getElementById('filterForm')?.addEventListener('submit', function(e) {
    // Form akan submit secara normal
});
</script>

<?php
$content = ob_get_clean();
include 'template1/base.php';
?>