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

// ==================== USE STATEMENT - DI LUAR IF ====================
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

// ==================== HANDLE AJAX LIVE SEARCH ====================
if (isset($_GET['search_penduduk'])) {
    header('Content-Type: application/json');
    $keyword = mysqli_real_escape_string($conn, $_GET['search_penduduk']);
    
    $query = "SELECT nik, nama_penduduk FROM penduduk 
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
        <title>Cetak Surat Domisili</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Times New Roman', Times, serif;
                font-size: 12pt;
                line-height: 1.4;
                margin: 0;
                padding: 1.5cm;
                background: white;
            }
            .surat-container {
                max-width: 100%;
                margin: 0 auto;
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
                padding: 0;
                font-weight: bold;
                font-size: 13pt;
                line-height: 1.3;
            }
            .kop-text p {
                margin: 0;
                font-size: 11pt;
            }
            .judul-surat {
                text-align: center;
                margin: 15px 0 15px;
            }
            .judul-surat h4 {
                text-decoration: underline;
                font-weight: bold;
                margin: 0 0 3px 0;
                font-size: 13pt;
            }
            .isi-surat {
                margin: 10px 0;
            }
            .isi-surat p {
                margin: 6px 0;
                text-align: justify;
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
                margin: 10px 0;
            }
            .barcode-container img {
                max-width: 120px;
                max-height: 120px;
            }
            .underline {
                text-decoration: underline;
            }
            .no-print {
                display: none !important;
            }
            @media print {
                @page {
                    margin: 1.5cm;
                }
                body {
                    margin: 0;
                    padding: 0;
                }
                .no-print {
                    display: none !important;
                }
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
                window.onafterprint = function() {
                    window.close();
                };
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
        <title>Surat Domisili</title>
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
            .kop-text p {
                margin: 0;
                font-size: 11pt;
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
                margin: 10px 0;
            }
            .barcode-container img {
                max-width: 120px;
                max-height: 120px;
            }
            .underline {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        ' . $konten . '
    </body>
    </html>';
    
    if ($use_dompdf) {
        // Gunakan DOMPDF untuk membuat PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times New Roman');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $dompdf->stream("surat_domisili_" . date('Ymd_His') . ".pdf", array("Attachment" => true));
        exit;
    } else {
        // Fallback: download sebagai HTML
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="surat_domisili_' . date('Ymd_His') . '.html"');
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
        'marginLeft' => 1440,  // 1.5cm dalam twips (1cm = 567 twips)
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
    $section->addText('SURAT KETERANGAN DOMISILI', ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
    $section->addText('NO : ' . $no_surat, [], ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);
    
    // Isi surat
    $section->addText('Yang bertanda tangan di bawah ini Pj. Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:');
    $section->addTextBreak(0.5);
    
    // Data tabel
    $tableData = $section->addTable(['borderSize' => 0]);
    $dataRows = [
        ['N a m a', $penduduk_word['nama_penduduk'] ?? ''],
        ['Tempat / Tgl Lahir', ($penduduk_word['tempat_lahir'] ?? '') . ', ' . tgl_indonesia($penduduk_word['tanggal_lahir'] ?? '')],
        ['NIK', $penduduk_word['nik'] ?? ''],
        ['Jenis Kelamin', ($penduduk_word['jenis_kelamin'] ?? '') == 'LAKI-LAKI' ? 'Laki-laki' : 'Perempuan'],
        ['Status Perkawinan', $penduduk_word['status_kawin'] ?? ''],
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
    $section->addText('menerangkan dengan sebenarnya bahwa orang tersebut di atas benar-benar berdomisili di Dsn. Paserean Desa Sukolilo Timur.');
    $section->addTextBreak(1);
    $section->addText('Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.');
    $section->addTextBreak(2);
    
    // Tanda tangan dengan barcode di tengah
    $section->addText('Sukolilo Timur, ' . tgl_indonesia(date('Y-m-d')), [], ['alignment' => Jc::RIGHT]);
    $section->addText('Pj. Kepala Desa Sukolilo Timur', [], ['alignment' => Jc::RIGHT]);
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
    $section->addText('MOH. JASULIN RAHMATULAH', ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::RIGHT]);
    $section->addText('NIP. 197204122009061001', [], ['alignment' => Jc::RIGHT]);
    
    // Simpan file Word
    $filename = "surat_domisili_" . date('Ymd_His') . ".docx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit;
}

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

// ==================== AMBIL DATA PENDUDUK ====================
$penduduk = null;
if (isset($_GET['nik']) && !empty($_GET['nik'])) {
    $nik = mysqli_real_escape_string($conn, $_GET['nik']);
    $result = mysqli_query($conn, "SELECT * FROM penduduk WHERE nik = '$nik'");
    $penduduk = mysqli_fetch_assoc($result);
}

$tanggal_sekarang = tgl_indonesia(date('Y-m-d'));

$pageTitle = "Surat Keterangan Domisili";
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

/* Tombol download */
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
</style>

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
            <div class="mb-2"><span class="data-label">Status Kawin</span> : <?php echo $penduduk['status_kawin']; ?></div>
            <div class="mb-2"><span class="data-label">Agama</span> : <?php echo $penduduk['agama']; ?></div>
            <div class="mb-2"><span class="data-label">Pekerjaan</span> : <?php echo $penduduk['pekerjaan'] ?: '-'; ?></div>
            <div class="mb-2"><span class="data-label">Alamat</span> : <?php echo $penduduk['alamat']; ?></div>
        </div>
    </div>
</div>

<!-- Editor Surat -->
<form id="formBuatSurat" method="POST">
    <input type="hidden" name="nik" value="<?php echo $penduduk['nik']; ?>">
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-edit me-2"></i>Editor Surat Domisili
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
                    <h4 style="text-decoration: underline; font-weight: bold; margin:0 0 3px 0; font-size: 13pt;">SURAT KETERANGAN DOMISILI</h4>
                    <p style="margin:0; font-size: 12pt;">NO : <span id="nomor_surat_text" contenteditable="true">56 / 433.312.5 / <?php echo date('Y'); ?></span></p>
                </div>
                
                <!-- ISI SURAT -->
                <div style="margin-top: 10px;">
                    <p style="margin:6px 0;">Yang bertanda tangan di bawah ini Pj. Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:</p>
                    
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
                            <td style="padding: 3px; vertical-align: top;"><span contenteditable="true"><?php echo $penduduk['jenis_kelamin'] == 'LAKI-LAKI' ? 'Laki-laki' : 'Perempuan'; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 3px; vertical-align: top;">Status Perkawinan</td>
                            <td style="padding: 3px; vertical-align: top;">:</td>
                            <td style="padding: 3px; vertical-align: top;"><span contenteditable="true"><?php echo $penduduk['status_kawin']; ?></span></td>
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
                                <span contenteditable="true">Kecamatan Labang Kabupaten Bangkalan</span>
                            </td>
                        </tr>
                    </table>
                    
                    <p style="margin-top: 10px;">menerangkan dengan sebenarnya bahwa orang tersebut di atas benar-benar berdomisili di <span contenteditable="true">Dsn. Paserean Desa Sukolilo Timur</span>.</p>
                    
                    <p style="margin-top: 15px;">Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
                </div>
                
                <!-- TANDA TANGAN DENGAN BARCODE DI TENGAH -->
                <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
                    <div style="text-align: center; width: 300px;">
                        <p>Sukolilo Timur, <span contenteditable="true"><?php echo $tanggal_sekarang; ?></span></p>
                        <p style="margin-top: 10px;">Pj. Kepala Desa Sukolilo Timur</p>
                        
                        <!-- Barcode di sini - antara jabatan dan nama -->
                        <div style="margin: 15px 0;">
                            <?php if (file_exists('../img/ttd.png')): ?>
                            <img src="../img/ttd.png" alt="Barcode" style="max-width: 100px; max-height: 100px;">
                            <?php endif; ?>
                        </div>
                        
                        <p style="text-decoration: underline; font-weight: bold; margin:0;" contenteditable="true">MOH. JASULIN RAHMATULAH</p>
                        <p contenteditable="true">NIP. 197204122009061001</p>
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
                               value="56 / 433.312.5 / <?php echo date('Y'); ?>">
                    </div>
                </div>
                <div class="col-md-7">
                    <br>
                    <div class="btn-group-action">
                        <button type="button" class="btn btn-info" onclick="previewSurat()">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button type="button" class="btn btn-pdf" id="btnDownloadPDF" onclick="downloadPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button type="button" class="btn btn-word" id="btnDownloadWord" onclick="downloadWord()">
                            <i class="fas fa-file-word"></i> Word
                        </button>
                        <button type="submit" class="btn btn-print" id="btnCetak" name="action" value="cetak" formtarget="_blank">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                        <a href="domisili.php" class="btn btn-secondary">
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
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Preview Surat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent" style="font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.6; padding: 30px;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="cetakPreview()">
                    <i class="fas fa-print"></i> Cetak Preview
                </button>
            </div>
        </div>
    </div>
</div>

<script>
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
            fetch('domisili.php?search_penduduk=' + encodeURIComponent(keyword))
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
}

function pilihPenduduk(nik, nama) {
    searchInput.value = nama + ' (' + nik + ')';
    selectedNik.value = nik;
    searchResults.style.display = 'none';
    document.getElementById('formCariPenduduk').submit();
}

document.addEventListener('click', function(e) {
    if (searchInput && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.style.display = 'none';
    }
});

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

// ===== PREVIEW =====
function previewSurat() {
    const editor = document.getElementById('editor');
    const preview = document.getElementById('previewContent');
    
    preview.innerHTML = editor.innerHTML;
    
    preview.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.removeAttribute('contenteditable');
        el.style.borderBottom = 'none';
        el.style.background = 'none';
    });
    
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function cetakPreview() {
    const previewContent = document.getElementById('previewContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Cetak Surat Domisili</title>
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

// ===== DOWNLOAD PDF =====
function downloadPDF() {
    const editor = document.getElementById('editor').cloneNode(true);
    editor.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.removeAttribute('contenteditable');
        el.style.borderBottom = 'none';
        el.style.background = 'none';
    });
    
    const formData = new FormData();
    formData.append('action', 'download_pdf');
    formData.append('nik', document.querySelector('input[name="nik"]').value);
    formData.append('no_surat', document.getElementById('no_surat').value);
    formData.append('konten_surat', editor.innerHTML);
    
    fetch('domisili.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'surat_domisili_' + new Date().toISOString().slice(0,10) + '.pdf';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal mendownload PDF');
    });
}

// ===== DOWNLOAD WORD =====
function downloadWord() {
    const editor = document.getElementById('editor').cloneNode(true);
    editor.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.removeAttribute('contenteditable');
        el.style.borderBottom = 'none';
        el.style.background = 'none';
    });
    
    const formData = new FormData();
    formData.append('action', 'download_word');
    formData.append('nik', document.querySelector('input[name="nik"]').value);
    formData.append('no_surat', document.getElementById('no_surat').value);
    formData.append('konten_surat', editor.innerHTML);
    
    fetch('domisili.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'surat_domisili_' + new Date().toISOString().slice(0,10) + '.docx';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal mendownload Word');
    });
}

// ===== FORM SUBMIT UNTUK CETAK =====
document.getElementById('formBuatSurat').addEventListener('submit', function(e) {
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
    this.appendChild(hiddenInput);
});
</script>

<?php
$content = ob_get_clean();
include '../template1/base.php';
?>