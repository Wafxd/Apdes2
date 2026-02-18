<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

include "../db/koneksi.php";
include "../db/funct.php";

// ==================== CEK DOMPDF & PHPWORD ====================
$use_dompdf = false;
$use_phpword = false;

if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
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

function hari_indonesia($tanggal) {
    $hari = date('N', strtotime($tanggal));
    $daftar_hari = array(
        1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'
    );
    return $daftar_hari[$hari];
}

// ==================== FUNGSI GET NOMOR SURAT BERIKUTNYA ====================
function getNomorSuratBerikutnya($conn, $jenis_surat = 'SKKe') {
    $tahun = date('Y');
    
    // Cari nomor surat terakhir untuk jenis surat tertentu di tahun ini
    $query = "SELECT no_surat FROM arsip_surat 
              WHERE jenis_surat = '$jenis_surat' 
              AND YEAR(tanggal_surat) = '$tahun'
              ORDER BY id_surat DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        // Format: 56 / 433.312.5 / 2024
        $parts = explode(' / ', $row['no_surat']);
        if (count($parts) == 3) {
            $nomor = intval($parts[0]) + 1;
            return $nomor . ' / ' . $parts[1] . ' / ' . $parts[2];
        }
    }
    
    // Jika belum ada, mulai dari nomor 1
    return '1 / 433.311.05 / ' . $tahun;
}

// ==================== HANDLE AJAX LIVE SEARCH ====================
if (isset($_GET['search_penduduk'])) {
    header('Content-Type: application/json');
    $keyword = mysqli_real_escape_string($conn, $_GET['search_penduduk']);
    
    $query = "SELECT nik, nama_penduduk, tempat_lahir, tanggal_lahir, alamat, 
              jenis_kelamin, status_kawin, agama, pekerjaan
              FROM penduduk 
              WHERE nik LIKE '%$keyword%' OR nama_penduduk LIKE '%$keyword%' 
              LIMIT 10";
    $result = mysqli_query($conn, $query);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    echo json_encode($data);
    exit();
}

// ==================== HANDLE SIMPAN SURAT ====================
if (isset($_POST['action']) && $_POST['action'] == 'simpan_surat') {
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $no_surat = mysqli_real_escape_string($conn, $_POST['no_surat']);
    $keperluan = mysqli_real_escape_string($conn, $_POST['keperluan'] ?? '');
    $tanggal_kejadian = mysqli_real_escape_string($conn, $_POST['tanggal_kejadian'] ?? '');
    $jam_kejadian = mysqli_real_escape_string($conn, $_POST['jam_kejadian'] ?? '');
    $lokasi_kejadian = mysqli_real_escape_string($conn, $_POST['lokasi_kejadian'] ?? '');
    $barang_hilang = mysqli_real_escape_string($conn, $_POST['barang_hilang'] ?? '');
    
    // Ambil data penduduk
    $query_penduduk = "SELECT * FROM penduduk WHERE nik = '$nik'";
    $result_penduduk = mysqli_query($conn, $query_penduduk);
    $penduduk = mysqli_fetch_assoc($result_penduduk);
    
    if ($penduduk) {
        // Simpan ke database
        $tanggal_surat = date('Y-m-d');
        $jenis_surat = 'SKKe';
        $nama_pemohon = $penduduk['nama_penduduk'];
        $tempat_lahir = $penduduk['tempat_lahir'];
        $tanggal_lahir = $penduduk['tanggal_lahir'];
        $alamat = $penduduk['alamat'];
        $created_by = $_SESSION['id_admin'];
        
        // Gabungkan data kejadian ke dalam keterangan
        $keterangan = "Tanggal Kejadian: $tanggal_kejadian\nJam: $jam_kejadian\nLokasi: $lokasi_kejadian\nBarang Hilang: $barang_hilang";
        
        $query_insert = "INSERT INTO arsip_surat 
                        (no_surat, jenis_surat, tanggal_surat, nik, nama_pemohon, 
                         tempat_lahir, tanggal_lahir, alamat, keperluan, keterangan, created_by) 
                        VALUES 
                        ('$no_surat', '$jenis_surat', '$tanggal_surat', '$nik', '$nama_pemohon',
                         '$tempat_lahir', '$tanggal_lahir', '$alamat', '$keperluan', '$keterangan', '$created_by')";
        
        if (mysqli_query($conn, $query_insert)) {
            $_SESSION['success_message'] = "Surat berhasil disimpan dengan nomor: " . $no_surat;
        } else {
            $_SESSION['error_message'] = "Gagal menyimpan surat: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_message'] = "Data penduduk tidak ditemukan!";
    }
    
    header("Location: kehilangan.php?nik=" . urlencode($nik));
    exit();
}

// ==================== HANDLE CETAK SURAT ====================
if (isset($_POST['action']) && $_POST['action'] == 'cetak') {
    $konten = $_POST['konten_surat'] ?? '';
    
    // Hapus semua atribut contenteditable
    $konten = preg_replace('/contenteditable="true"/', '', $konten);
    $konten = preg_replace('/<span[^>]*id="nomor_surat_text"[^>]*>/', '<span>', $konten);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Cetak Surat Kehilangan</title>
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
    exit();
}

// ==================== HANDLE DOWNLOAD PDF ====================
if (isset($_POST['action']) && $_POST['action'] == 'download_pdf') {
    $konten = $_POST['konten_surat'] ?? '';
    
    // Hapus semua atribut contenteditable
    $konten = preg_replace('/contenteditable="true"/', '', $konten);
    $konten = preg_replace('/<span[^>]*id="nomor_surat_text"[^>]*>/', '<span>', $konten);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Surat Kehilangan</title>
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
        ' . $konten . '
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
        
        $dompdf->stream("surat_kehilangan_" . date('Ymd_His') . ".pdf", array("Attachment" => true));
        exit;
    } else {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="surat_kehilangan_' . date('Ymd_His') . '.html"');
        echo $html;
        exit;
    }
}

// ==================== HANDLE DOWNLOAD WORD ====================
if (isset($_POST['action']) && $_POST['action'] == 'download_word') {
    $konten = $_POST['konten_surat'] ?? '';
    $nik = $_POST['nik'] ?? '';
    $no_surat = $_POST['no_surat'] ?? '';
    
    // Ambil data penduduk
    $penduduk_word = null;
    if (!empty($nik)) {
        $nik_aman = mysqli_real_escape_string($conn, $nik);
        $result = mysqli_query($conn, "SELECT * FROM penduduk WHERE nik = '$nik_aman'");
        $penduduk_word = mysqli_fetch_assoc($result);
    }
    
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
    if (file_exists('../img/labang.png')) {
        $cellLogo->addImage('../img/labang.png', [
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
    $section->addText('SURAT KETERANGAN KEHILANGAN', ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
    $section->addText('NO : ' . $no_surat, [], ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);
    
    // Isi surat
    $section->addText('Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:');
    $section->addTextBreak(0.5);
    
    // Data tabel
    $tableData = $section->addTable(['borderSize' => 0]);
    $dataRows = [
        ['N a m a', $penduduk_word['nama_penduduk'] ?? ''],
        ['Tempat / Tgl Lahir', ($penduduk_word['tempat_lahir'] ?? '') . ', ' . tgl_indonesia($penduduk_word['tanggal_lahir'] ?? '')],
        ['NIK', $penduduk_word['nik'] ?? ''],
        ['Jenis Kelamin', ($penduduk_word['jenis_kelamin'] ?? '') == 'LAKI-LAKI' ? 'Laki-laki' : 'Perempuan'],
        ['Agama', $penduduk_word['agama'] ?? ''],
        ['Pekerjaan', $penduduk_word['pekerjaan'] ?? '-'],
        ['Alamat', $penduduk_word['alamat'] ?? '']
    ];
    
    foreach ($dataRows as $row) {
        $tableData->addRow();
        $tableData->addCell(2000)->addText($row[0]);
        $tableData->addCell(500)->addText(':');
        $tableData->addCell(6500)->addText($row[1]);
    }
    
    $section->addTextBreak(1);
    $section->addText('menerangkan dengan sebenarnya bahwa orang tersebut diatas pada hari ' . $_POST['hari_kejadian'] . ' tanggal ' . $_POST['tanggal_kejadian'] . ' sekitar pukul ' . $_POST['jam_kejadian'] . ' telah kehilangan:');
    $section->addTextBreak(0.5);
    
    // Daftar barang hilang
    $barang_list = explode("\n", $_POST['barang_hilang']);
    foreach ($barang_list as $index => $barang) {
        if (trim($barang)) {
            $section->addText(($index + 1) . '. ' . trim($barang), [], ['alignment' => Jc::LEFT]);
        }
    }
    
    $section->addTextBreak(0.5);
    $section->addText('di ' . $_POST['lokasi_kejadian'] . '.');
    $section->addTextBreak(1);
    $section->addText('Demikian surat keterangan kehilangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.');
    $section->addTextBreak(2);
    
    // Tanda tangan
    $section->addText('Sukolilo Timur, ' . tgl_indonesia(date('Y-m-d')), [], ['alignment' => Jc::RIGHT]);
    $section->addText('Kepala Desa Sukolilo Timur', [], ['alignment' => Jc::RIGHT]);
    $section->addTextBreak(0.5);
    
    // Barcode
    if (file_exists('../img/ttd.png')) {
        $section->addImage('../img/ttd.png', [
            'width' => 100,
            'height' => 100,
            'alignment' => Jc::RIGHT
        ]);
    }
    
    $section->addTextBreak(0.5);
    $section->addText('H. ZAINAL ABIDIN', ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::RIGHT]);
    
    // Simpan file Word
    $filename = "surat_kehilangan_" . date('Ymd_His') . ".docx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit;
}

// ==================== AMBIL DATA PENDUDUK ====================
$penduduk = null;
$nomor_surat_otomatis = getNomorSuratBerikutnya($conn, 'SKKe');

if (isset($_GET['nik']) && !empty($_GET['nik'])) {
    $nik = mysqli_real_escape_string($conn, $_GET['nik']);
    $result = mysqli_query($conn, "SELECT * FROM penduduk WHERE nik = '$nik'");
    $penduduk = mysqli_fetch_assoc($result);
}

$tanggal_sekarang = tgl_indonesia(date('Y-m-d'));

$pageTitle = "Surat Keterangan Kehilangan";
$pageHeaderButton = '<a href="../surat.php" class="btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
</a>';

ob_start();
?>

<style>
/* ===== STYLE KHUSUS ===== */
.search-container {
    position: relative;
    margin-bottom: 10px;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #d1d3e2;
    border-radius: 0 0 0.35rem 0.35rem;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.search-result-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.search-result-item:hover {
    background: #f8f9fc;
}

.search-result-item strong {
    color: #4e73df;
}

.search-result-item small {
    color: #6c757d;
    display: block;
}

.data-card {
    background: #f8f9fc;
    border-left: 4px solid #4e73df;
    padding: 20px;
    border-radius: 0.35rem;
    margin-bottom: 20px;
}

.data-label {
    font-weight: bold;
    color: #4e73df;
    min-width: 120px;
    display: inline-block;
}

.editor-container {
    background: white;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    padding: 30px;
    min-height: 800px;
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    line-height: 1.6;
}

[contenteditable="true"] {
    border-bottom: 2px dashed #4e73df;
    min-width: 150px;
    display: inline-block;
    padding: 2px 5px;
    outline: none;
    background: rgba(78, 115, 223, 0.05);
}

[contenteditable="true"]:focus {
    border-bottom: 2px solid #4e73df;
    background: rgba(78, 115, 223, 0.1);
}

.toolbar {
    background: #f8f9fc;
    border: 1px solid #d1d3e2;
    border-bottom: none;
    border-radius: 0.35rem 0.35rem 0 0;
    padding: 10px;
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.toolbar button {
    background: white;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    padding: 5px 10px;
    cursor: pointer;
    transition: all 0.15s;
}

.toolbar button:hover {
    background: #e9ecef;
}

.surat-body {
    background: white;
    padding: 0;
}

/* Form kejadian */
.kejadian-form {
    background: #f8f9fc;
    border: 1px solid #d1d3e2;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.dokumen-item {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.dokumen-item input {
    flex: 1;
}

.btn-add-dokumen {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 15px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-add-dokumen:hover {
    background: #218838;
}

.btn-remove-dokumen {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 6px;
    width: 35px;
    height: 35px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-remove-dokumen:hover {
    background: #c82333;
}

/* Tombol aksi */
.btn-group-action {
    display: flex;
    gap: 5px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.btn-pdf {
    background: #dc3545;
    color: white;
    border: none;
}

.btn-pdf:hover {
    background: #c82333;
}

.btn-word {
    background: #007bff;
    color: white;
    border: none;
}

.btn-word:hover {
    background: #0069d9;
}

.btn-print {
    background: #4e73df;
    color: white;
}

.btn-save {
    background: #28a745;
    color: white;
    border: none;
}

.btn-save:hover {
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

.modal-header.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
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

/* Card styling */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.card-header {
    border-top-left-radius: 12px !important;
    border-top-right-radius: 12px !important;
}

.card-header.bg-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}

/* Alert styling */
.alert {
    border-radius: 8px;
    border-left: 4px solid;
}

.alert-success {
    border-left-color: #28a745;
}

.alert-danger {
    border-left-color: #dc3545;
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
        justify-content: flex-start;
        margin-top: 10px;
    }
    
    .dokumen-item {
        flex-wrap: wrap;
    }
}
</style>

<!-- Alert Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
    <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
    <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<!-- Form Pencarian -->
<div class="card shadow mb-4">
    <div class="card-header py-3 bg-primary text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-search me-2"></i>Cari Data Penduduk
        </h6>
    </div>
    <div class="card-body">
        <form id="formCariPenduduk" method="GET" action="">
            <div class="row">
                <div class="col-md-10 mb-3">
                    <label class="form-label fw-bold">NIK / Nama Penduduk</label>
                    <div class="search-container">
                        <input type="text" class="form-control" id="search_input" 
                               placeholder="Ketik NIK atau nama penduduk..." 
                               value="<?php echo isset($_GET['nik']) ? htmlspecialchars($_GET['nik']) : ''; ?>" 
                               autocomplete="off">
                        <input type="hidden" name="nik" id="selected_nik" value="<?php echo isset($_GET['nik']) ? htmlspecialchars($_GET['nik']) : ''; ?>">
                        <div id="searchResults" class="search-results"></div>
                    </div>
                    <small class="text-muted">Ketik minimal 2 karakter, lalu klik hasil yang muncul</small>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($penduduk): ?>

<!-- Data Penduduk -->
<div class="data-card">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-2"><span class="data-label">NIK</span> : <strong><?php echo $penduduk['nik']; ?></strong></div>
            <div class="mb-2"><span class="data-label">Nama Lengkap</span> : <strong><?php echo $penduduk['nama_penduduk']; ?></strong></div>
            <div class="mb-2"><span class="data-label">Tempat Lahir</span> : <?php echo $penduduk['tempat_lahir']; ?></div>
            <div class="mb-2"><span class="data-label">Tanggal Lahir</span> : <?php echo tgl_indonesia($penduduk['tanggal_lahir']); ?></div>
        </div>
        <div class="col-md-6">
            <div class="mb-2"><span class="data-label">Jenis Kelamin</span> : <?php echo $penduduk['jenis_kelamin']; ?></div>
            <div class="mb-2"><span class="data-label">Agama</span> : <?php echo $penduduk['agama']; ?></div>
            <div class="mb-2"><span class="data-label">Pekerjaan</span> : <?php echo $penduduk['pekerjaan'] ?: '-'; ?></div>
            <div class="mb-2"><span class="data-label">Alamat</span> : <?php echo $penduduk['alamat']; ?></div>
        </div>
    </div>
</div>

<!-- Form Kejadian -->
<div class="kejadian-form">
    <h5 class="mb-3"><i class="fas fa-clock me-2"></i>Detail Kejadian</h5>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label fw-bold">Tanggal Kejadian</label>
            <input type="date" class="form-control" id="tanggal_kejadian" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label fw-bold">Jam Kejadian</label>
            <input type="time" class="form-control" id="jam_kejadian" value="<?php echo date('H:i'); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label fw-bold">Hari Kejadian</label>
            <input type="text" class="form-control" id="hari_kejadian" value="<?php echo hari_indonesia(date('Y-m-d')); ?>" readonly>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-3">
            <label class="form-label fw-bold">Lokasi Kejadian</label>
            <input type="text" class="form-control" id="lokasi_kejadian" value="Diantara Jalan Sukolilo Sampai Jalan Pasar Labang">
        </div>
    </div>
    
    <div class="mb-3">
        <label class="form-label fw-bold">Dokumen / Barang yang Hilang</label>
        <div id="dokumen-container">
            <div class="dokumen-item">
                <input type="text" class="form-control" name="dokumen[]" placeholder="Contoh: SIM A" value="SIM A">
                <button type="button" class="btn-remove-dokumen" onclick="removeDokumen(this)" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <button type="button" class="btn-add-dokumen mt-2" onclick="tambahDokumen()">
            <i class="fas fa-plus me-2"></i>Tambah Dokumen
        </button>
    </div>
</div>

<!-- Editor Surat -->
<form id="formBuatSurat" method="POST">
    <input type="hidden" name="nik" value="<?php echo $penduduk['nik']; ?>">
    <input type="hidden" name="action" id="formAction" value="">
    <input type="hidden" name="tanggal_kejadian" id="hidden_tanggal_kejadian">
    <input type="hidden" name="jam_kejadian" id="hidden_jam_kejadian">
    <input type="hidden" name="hari_kejadian" id="hidden_hari_kejadian">
    <input type="hidden" name="lokasi_kejadian" id="hidden_lokasi_kejadian">
    <input type="hidden" name="barang_hilang" id="hidden_barang_hilang">
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-edit me-2"></i>Editor Surat Kehilangan
            </h6>
            <span class="badge bg-light text-dark">
                <i class="fas fa-calendar"></i> <?php echo date('d-m-Y'); ?>
            </span>
        </div>
        
        <!-- Toolbar -->
        <div class="toolbar">
            <select class="form-select form-select-sm d-inline-block w-auto" id="fontSize" style="width: auto;">
                <option value="10px">10px</option>
                <option value="11px">11px</option>
                <option value="12px" selected>12px</option>
                <option value="14px">14px</option>
                <option value="16px">16px</option>
                <option value="18px">18px</option>
                <option value="20px">20px</option>
            </select>
            
            <button type="button" class="btn btn-sm btn-light" onclick="document.execCommand('bold', false, null)" title="Bold">
                <i class="fas fa-bold"></i>
            </button>
            <button type="button" class="btn btn-sm btn-light" onclick="document.execCommand('italic', false, null)" title="Italic">
                <i class="fas fa-italic"></i>
            </button>
            <button type="button" class="btn btn-sm btn-light" onclick="document.execCommand('underline', false, null)" title="Underline">
                <i class="fas fa-underline"></i>
            </button>
            <button type="button" class="btn btn-sm btn-light" onclick="document.execCommand('justifyLeft', false, null)" title="Left">
                <i class="fas fa-align-left"></i>
            </button>
            <button type="button" class="btn btn-sm btn-light" onclick="document.execCommand('justifyCenter', false, null)" title="Center">
                <i class="fas fa-align-center"></i>
            </button>
            <button type="button" class="btn btn-sm btn-light" onclick="document.execCommand('justifyRight', false, null)" title="Right">
                <i class="fas fa-align-right"></i>
            </button>
        </div>
        
        <!-- Editor Area -->
        <div class="surat-body">
            <div class="editor-container" id="editor" contenteditable="true">
                <!-- KOP SURAT dengan LOGO -->
                <div style="display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
                    <div style="flex: 0 0 80px; margin-left: 20px; margin-right: 15px;">
                        <img src="../img/labang.png" alt="Logo Desa" style="max-width: 80px; max-height: 80px;">
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <h4 style="margin:0; font-weight: bold; font-size: 13pt;">PEMERINTAH KABUPATEN BANGKALAN</h4>
                        <h4 style="margin:0; font-weight: bold; font-size: 13pt;">KECAMATAN LABANG</h4>
                        <h4 style="margin:0; font-weight: bold; font-size: 13pt;">KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                        <p style="margin:0; font-size: 11pt;">Labang 69163</p>
                    </div>
                </div>
                
                <!-- JUDUL SURAT -->
                <div style="text-align: center; margin: 15px 0 15px;">
                    <h4 style="text-decoration: underline; font-weight: bold; margin:0 0 3px 0; font-size: 13pt;">SURAT KETERANGAN KEHILANGAN</h4>
                    <p style="margin:0; font-size: 12pt;">NO : <span id="nomor_surat_text" contenteditable="true"><?php echo $nomor_surat_otomatis; ?></span></p>
                </div>
                
                <!-- ISI SURAT -->
                <div style="margin-top: 10px;">
                    <p style="margin:6px 0;">Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:</p>
                    
                    <table style="width: 100%; margin: 10px 0; border-collapse: collapse;">
                        <tr>
                            <td style="width: 130px; padding: 3px; vertical-align: top;">N a m a</td>
                            <td style="width: 20px; padding: 3px; vertical-align: top;">:</td>
                            <td style="padding: 3px; vertical-align: top;"><span contenteditable="true"><?php echo $penduduk['nama_penduduk']; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; vertical-align: top;">Tempat / Tgl Lahir</td>
                            <td style="padding: 3px; vertical-align: top;">:</td>
                            <td style="padding: 3px; vertical-align: top;"><span contenteditable="true"><?php echo $penduduk['tempat_lahir'] . ', ' . tgl_indonesia($penduduk['tanggal_lahir']); ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; vertical-align: top;">NIK</td>
                            <td style="padding: 3px; vertical-align: top;">:</td>
                            <td style="padding: 3px; vertical-align: top;"><span contenteditable="true"><?php echo $penduduk['nik']; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; vertical-align: top;">Jenis Kelamin</td>
                            <td style="padding: 3px; vertical-align: top;">:</td>
                            <td style="padding: 3px; vertical-align: top;"><span contenteditable="true"><?php echo $penduduk['jenis_kelamin'] == 'LAKI-LAKI' ? 'Laki-Laki' : 'Perempuan'; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; vertical-align: top;">Agama</td>
                            <td style="padding: 3px; vertical-align: top;">:</td>
                            <td style="padding: 3px; vertical-align: top;"><span contenteditable="true"><?php echo $penduduk['agama']; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; vertical-align: top;">Pekerjaan</td>
                            <td style="padding: 3px; vertical-align: top;">:</td>
                            <td style="padding: 3px; vertical-align: top;"><span contenteditable="true"><?php echo $penduduk['pekerjaan'] ?: '-'; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; vertical-align: top;">Alamat</td>
                            <td style="padding: 3px; vertical-align: top;">:</td>
                            <td style="padding: 3px; vertical-align: top;">
                                <span contenteditable="true"><?php echo $penduduk['alamat']; ?></span><br>
                                <span contenteditable="true">Kecamatan Labang, Kabupaten Bangkalan.</span>
                            </td>
                        </tr>
                    </table>
                    
                    <p style="margin-top: 10px;">menerangkan bahwa orang tersebut diatas pada hari <span id="hari_text" contenteditable="true"><?php echo hari_indonesia(date('Y-m-d')); ?></span> tanggal <span id="tanggal_text" contenteditable="true"><?php echo date('d-m-Y'); ?></span> sekitar pukul <span id="jam_text" contenteditable="true"><?php echo date('H:i'); ?></span> telah kehilangan :</p>
                    
                    <div id="dokumen_list">
                        <p style="margin-left: 20px;">1. SIM A</p>
                    </div>
                    
                    <p>di <span id="lokasi_text" contenteditable="true">Diantara Jalan Sukolilo Sampai Jalan Pasar Labang</span>.</p>
                    
                    <p style="margin-top: 15px;">Demikian surat keterangan kehilangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
                </div>
                
                <!-- TANDA TANGAN DENGAN BARCODE -->
                <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
                    <div style="text-align: center; width: 300px;">
                        <p>Sukolilo Timur, <span contenteditable="true"><?php echo $tanggal_sekarang; ?></span></p>
                        <p style="margin-top: 10px;">Kepala Desa Sukolilo Timur</p>
                        
                        <!-- Barcode -->
                        <div style="margin: 15px 0;">
                            <?php if (file_exists('../img/ttd.png')): ?>
                            <img src="../img/ttd.png" alt="Barcode" style="max-width: 100px; max-height: 100px;">
                            <?php endif; ?>
                        </div>
                        
                        <p style="text-decoration: underline; font-weight: bold; margin:0;" contenteditable="true">H. ZAINAL ABIDIN</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer dengan Tombol Aksi -->
        <div class="card-footer bg-light">
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group mb-0">
                        <label>Nomor Surat</label>
                        <input type="text" class="form-control" id="no_surat" name="no_surat" 
                               value="<?php echo $nomor_surat_otomatis; ?>">
                    </div>
                    <div class="form-group mt-2">
                        <label>Keperluan (Opsional)</label>
                        <input type="text" class="form-control" id="keperluan" name="keperluan" 
                               placeholder="Contoh: Pelaporan ke Polisi, Klaim Asuransi, dll">
                    </div>
                </div>
                <div class="col-md-7">
                    <br>
                    <div class="btn-group-action">
                        <button type="button" class="btn btn-info" onclick="previewSurat()">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button type="button" class="btn btn-pdf" onclick="downloadPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button type="button" class="btn btn-word" onclick="downloadWord()">
                            <i class="fas fa-file-word"></i> Word
                        </button>
                        <button type="button" class="btn btn-save" onclick="simpanSurat()">
                            <i class="fas fa-save"></i> Simpan Surat
                        </button>
                        <button type="button" class="btn btn-print" onclick="cetakSurat()">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                        <a href="kehilangan.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php endif; ?>

<!-- Modal Preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-3">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Preview Surat
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalPreview()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="previewContent" style="font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.6; padding: 30px;">
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" onclick="closeModalPreview()">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
                <button type="button" class="btn btn-primary" onclick="cetakPreview()">
                    <i class="fas fa-print me-2"></i>Cetak Preview
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Simpan -->
<div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title">
                    <i class="fas fa-save me-2"></i>Konfirmasi Simpan Surat
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalConfirm()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <i class="fas fa-file-alt fa-4x text-success"></i>
                </div>
                <p class="fs-5 text-center">Apakah Anda yakin ingin menyimpan surat ini?</p>
                <div id="confirmSaveInfo" class="alert alert-info py-2">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td width="40%" class="text-muted">Nomor Surat</td>
                            <td><strong id="confirmNoSurat"></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">NIK</td>
                            <td><strong><?php echo $penduduk['nik'] ?? ''; ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama</td>
                            <td><strong><?php echo $penduduk['nama_penduduk'] ?? ''; ?></strong></td>
                        </tr>
                    </table>
                </div>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Surat akan tersimpan di arsip dan nomor surat akan digunakan untuk surat berikutnya.</strong>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" onclick="closeModalConfirm()">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmSave">
                    <i class="fas fa-save me-2"></i>Ya, Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ===== VARIABEL GLOBAL =====
let modalPreviewInstance = null;
let modalConfirmInstance = null;
let dokumenCount = 1;

// ===== FUNGSI CLOSE MODAL =====
function closeModalPreview() {
    if (modalPreviewInstance) {
        modalPreviewInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeModalConfirm() {
    if (modalConfirmInstance) {
        modalConfirmInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeAlert(element) {
    const alert = element.closest('.alert');
    if (alert) {
        alert.remove();
    }
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
    const previewModalEl = document.getElementById('previewModal');
    const confirmModalEl = document.getElementById('confirmSaveModal');
    
    if (previewModalEl) {
        modalPreviewInstance = new bootstrap.Modal(previewModalEl);
    }
    if (confirmModalEl) {
        modalConfirmInstance = new bootstrap.Modal(confirmModalEl);
    }
    
    // Cleanup backdrop saat modal ditutup
    document.querySelectorAll('.modal').forEach(modalEl => {
        modalEl.addEventListener('hidden.bs.modal', function() {
            cleanupBackdrop();
        });
    });
    
    // Inisialisasi event listeners untuk form kejadian
    initKejadianListeners();
});

// ===== FUNGSI UNTUK FORM KEJADIAN =====
function initKejadianListeners() {
    const tanggalInput = document.getElementById('tanggal_kejadian');
    const jamInput = document.getElementById('jam_kejadian');
    const hariInput = document.getElementById('hari_kejadian');
    
    if (tanggalInput) {
        tanggalInput.addEventListener('change', function() {
            updateHariKejadian();
            updateEditorTanggal();
        });
    }
    
    if (jamInput) {
        jamInput.addEventListener('input', updateEditorJam);
    }
}

function updateHariKejadian() {
    const tanggal = document.getElementById('tanggal_kejadian').value;
    if (!tanggal) return;
    
    const hari = new Date(tanggal).toLocaleDateString('id-ID', { weekday: 'long' });
    const hariMap = {
        'Monday': 'Senin', 'Tuesday': 'Selasa', 'Wednesday': 'Rabu',
        'Thursday': 'Kamis', 'Friday': 'Jumat', 'Saturday': 'Sabtu', 'Sunday': 'Minggu'
    };
    
    const hariIndonesia = hariMap[hari] || hari;
    document.getElementById('hari_kejadian').value = hariIndonesia;
    updateEditorHari(hariIndonesia);
}

function updateEditorHari(hari) {
    const hariText = document.getElementById('hari_text');
    if (hariText) {
        hariText.textContent = hari;
    }
}

function updateEditorTanggal() {
    const tanggal = document.getElementById('tanggal_kejadian').value;
    if (!tanggal) return;
    
    const tgl = new Date(tanggal).toLocaleDateString('id-ID', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    }).split('/').join('-');
    
    const tanggalText = document.getElementById('tanggal_text');
    if (tanggalText) {
        tanggalText.textContent = tgl;
    }
}

function updateEditorJam() {
    const jam = document.getElementById('jam_kejadian').value;
    const jamText = document.getElementById('jam_text');
    if (jamText && jam) {
        jamText.textContent = jam;
    }
}

function updateLokasi() {
    const lokasi = document.getElementById('lokasi_kejadian').value;
    const lokasiText = document.getElementById('lokasi_text');
    if (lokasiText && lokasi) {
        lokasiText.textContent = lokasi;
    }
}

function updateDokumenList() {
    const dokumenInputs = document.querySelectorAll('input[name="dokumen[]"]');
    const dokumenList = document.getElementById('dokumen_list');
    let html = '';
    
    dokumenInputs.forEach((input, index) => {
        if (input.value.trim()) {
            html += `<p style="margin-left: 20px;">${index + 1}. ${input.value}</p>`;
        }
    });
    
    if (dokumenList) {
        dokumenList.innerHTML = html || '<p style="margin-left: 20px;">-</p>';
    }
}

function tambahDokumen() {
    const container = document.getElementById('dokumen-container');
    const newItem = document.createElement('div');
    newItem.className = 'dokumen-item';
    newItem.innerHTML = `
        <input type="text" class="form-control" name="dokumen[]" placeholder="Contoh: SIM A" value="">
        <button type="button" class="btn-remove-dokumen" onclick="removeDokumen(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(newItem);
    
    // Tampilkan tombol remove pada item pertama jika sudah ada lebih dari 1
    if (container.children.length > 1) {
        const firstRemoveBtn = container.children[0].querySelector('.btn-remove-dokumen');
        if (firstRemoveBtn) {
            firstRemoveBtn.style.display = 'block';
        }
    }
    
    dokumenCount++;
}

function removeDokumen(btn) {
    const container = document.getElementById('dokumen-container');
    if (container.children.length > 1) {
        btn.closest('.dokumen-item').remove();
        
        // Sembunyikan tombol remove pada item pertama jika hanya tersisa 1
        if (container.children.length === 1) {
            const firstRemoveBtn = container.children[0].querySelector('.btn-remove-dokumen');
            if (firstRemoveBtn) {
                firstRemoveBtn.style.display = 'none';
            }
        }
    }
}

// ===== LIVE SEARCH =====
const searchInput = document.getElementById('search_input');
const searchResults = document.getElementById('searchResults');
const selectedNik = document.getElementById('selected_nik');
let searchTimeout;

if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const keyword = this.value.trim();
        
        if (keyword.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetch('kehilangan.php?search_penduduk=' + encodeURIComponent(keyword))
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        let html = '';
                        data.forEach(item => {
                            html += `<div class="search-result-item" onclick="pilihPenduduk('${item.nik}', '${item.nama_penduduk}')">
                                <strong>${item.nama_penduduk}</strong>
                                <small>NIK: ${item.nik}</small>
                            </div>`;
                        });
                        searchResults.innerHTML = html;
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<div class="search-result-item">Tidak ada data</div>';
                        searchResults.style.display = 'block';
                    }
                });
        }, 300);
    });
    
    // Tutup search results saat klik di luar
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
}

function pilihPenduduk(nik, nama) {
    searchInput.value = nama + ' (' + nik + ')';
    selectedNik.value = nik;
    searchResults.style.display = 'none';
    document.getElementById('formCariPenduduk').submit();
}

// ===== EDITOR =====
const fontSize = document.getElementById('fontSize');
if (fontSize) {
    fontSize.addEventListener('change', function() {
        document.execCommand('fontSize', false, this.value);
    });
}

// Update nomor surat
const noSurat = document.getElementById('no_surat');
const nomorSuratText = document.getElementById('nomor_surat_text');
if (noSurat && nomorSuratText) {
    noSurat.addEventListener('input', function() {
        nomorSuratText.textContent = this.value;
    });
}

// Update form kejadian ke editor
document.getElementById('lokasi_kejadian')?.addEventListener('input', updateLokasi);

// Update dokumen list ketika ada perubahan
document.addEventListener('input', function(e) {
    if (e.target && e.target.matches('input[name="dokumen[]"]')) {
        updateDokumenList();
    }
});

// ===== PREVIEW =====
function previewSurat() {
    // Update hidden inputs dengan nilai terbaru
    updateHiddenInputs();
    
    const editor = document.getElementById('editor');
    const preview = document.getElementById('previewContent');
    
    // Clone editor content
    const clone = editor.cloneNode(true);
    
    // Remove contenteditable attributes
    clone.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.removeAttribute('contenteditable');
        el.style.borderBottom = 'none';
        el.style.background = 'none';
    });
    
    preview.innerHTML = clone.innerHTML;
    
    if (modalPreviewInstance) {
        modalPreviewInstance.show();
    }
}

function cetakPreview() {
    const previewContent = document.getElementById('previewContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Cetak Surat Kehilangan</title>
            <style>
                body {
                    font-family: 'Times New Roman', Times, serif;
                    font-size: 12pt;
                    line-height: 1.4;
                    margin: 1.5cm;
                    padding: 0;
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
            ${previewContent}
            <script>
                window.onload = function() {
                    window.print();
                    window.onafterprint = function() {
                        window.close();
                    };
                }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// ===== FUNGSI UNTUK UPDATE HIDDEN INPUTS =====
function updateHiddenInputs() {
    const tanggal = document.getElementById('tanggal_kejadian').value;
    const jam = document.getElementById('jam_kejadian').value;
    const hari = document.getElementById('hari_kejadian').value;
    const lokasi = document.getElementById('lokasi_kejadian').value;
    
    const dokumenInputs = document.querySelectorAll('input[name="dokumen[]"]');
    let barangList = [];
    dokumenInputs.forEach(input => {
        if (input.value.trim()) {
            barangList.push(input.value.trim());
        }
    });
    const barangHilang = barangList.join('\n');
    
    document.getElementById('hidden_tanggal_kejadian').value = tanggal;
    document.getElementById('hidden_jam_kejadian').value = jam;
    document.getElementById('hidden_hari_kejadian').value = hari;
    document.getElementById('hidden_lokasi_kejadian').value = lokasi;
    document.getElementById('hidden_barang_hilang').value = barangHilang;
}

// ===== CETAK SURAT =====
function cetakSurat() {
    updateHiddenInputs();
    
    const form = document.getElementById('formBuatSurat');
    const actionInput = document.getElementById('formAction');
    actionInput.value = 'cetak';
    
    // Clone editor untuk menghapus contenteditable
    const editor = document.getElementById('editor').cloneNode(true);
    editor.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.removeAttribute('contenteditable');
        el.style.borderBottom = 'none';
        el.style.background = 'none';
    });
    
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'konten_surat';
    hiddenInput.value = editor.innerHTML;
    form.appendChild(hiddenInput);
    
    form.target = '_blank';
    form.submit();
    
    // Cleanup
    form.removeChild(hiddenInput);
    form.target = '';
    actionInput.value = '';
}

// ===== DOWNLOAD PDF =====
function downloadPDF() {
    updateHiddenInputs();
    
    const form = document.getElementById('formBuatSurat');
    const actionInput = document.getElementById('formAction');
    actionInput.value = 'download_pdf';
    
    // Clone editor untuk menghapus contenteditable
    const editor = document.getElementById('editor').cloneNode(true);
    editor.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.removeAttribute('contenteditable');
        el.style.borderBottom = 'none';
        el.style.background = 'none';
    });
    
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'konten_surat';
    hiddenInput.value = editor.innerHTML;
    form.appendChild(hiddenInput);
    
    form.submit();
    
    // Cleanup
    form.removeChild(hiddenInput);
    actionInput.value = '';
}

// ===== DOWNLOAD WORD =====
function downloadWord() {
    updateHiddenInputs();
    
    const form = document.getElementById('formBuatSurat');
    const actionInput = document.getElementById('formAction');
    actionInput.value = 'download_word';
    
    // Clone editor untuk menghapus contenteditable
    const editor = document.getElementById('editor').cloneNode(true);
    editor.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.removeAttribute('contenteditable');
        el.style.borderBottom = 'none';
        el.style.background = 'none';
    });
    
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'konten_surat';
    hiddenInput.value = editor.innerHTML;
    form.appendChild(hiddenInput);
    
    form.submit();
    
    // Cleanup
    form.removeChild(hiddenInput);
    actionInput.value = '';
}

// ===== SIMPAN SURAT =====
function simpanSurat() {
    updateHiddenInputs();
    
    const noSurat = document.getElementById('no_surat').value;
    document.getElementById('confirmNoSurat').textContent = noSurat;
    
    if (modalConfirmInstance) {
        modalConfirmInstance.show();
    }
}

// Tombol konfirmasi simpan
document.getElementById('btnConfirmSave')?.addEventListener('click', function() {
    const form = document.getElementById('formBuatSurat');
    const actionInput = document.getElementById('formAction');
    actionInput.value = 'simpan_surat';
    
    form.submit();
});

// ===== FORM SUBMIT HANDLER =====
document.getElementById('formBuatSurat')?.addEventListener('submit', function(e) {
    // Prevent default jika tidak ada action yang spesifik
    if (!document.getElementById('formAction').value) {
        e.preventDefault();
    }
});
</script>

<?php
$content = ob_get_clean();
include '../template1/base.php';
?>