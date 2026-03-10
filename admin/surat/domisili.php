<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../../login.php");
    exit();
}

include "../../db/koneksi.php";
include "../../db/funct.php";

// ==================== CEK DOMPDF & PHPWORD ====================
$use_dompdf = false;
$use_phpword = false;

$autoload_paths = [
    '../../vendor/autoload.php',
    '../vendor/autoload.php',
    'vendor/autoload.php'
];

foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $use_dompdf = class_exists('Dompdf\Dompdf');
        $use_phpword = class_exists('PhpOffice\PhpWord\PhpWord');
        break;
    }
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

// ==================== FUNGSI GET NOMOR SURAT BERIKUTNYA ====================
function getNomorSuratBerikutnya($conn, $jenis_surat = 'SKD') {
    $tahun = date('Y');
    $query = "SELECT no_surat FROM arsip_surat WHERE jenis_surat = '$jenis_surat' AND YEAR(tanggal_surat) = '$tahun' ORDER BY id_surat DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $parts = explode(' / ', $row['no_surat']);
        if (count($parts) == 3) {
            $nomor = intval($parts[0]) + 1;
            return $nomor . ' / ' . $parts[1] . ' / ' . $parts[2];
        }
    }
    return '1 / 433.312.5 / ' . $tahun;
}

// ==================== HANDLE AJAX LIVE SEARCH ====================
if (isset($_GET['search_penduduk'])) {
    header('Content-Type: application/json');
    $keyword = mysqli_real_escape_string($conn, $_GET['search_penduduk']);
    $query = "SELECT nik, nama_penduduk, tempat_lahir, tanggal_lahir, alamat, jenis_kelamin, status_kawin, agama, pekerjaan FROM penduduk WHERE nik LIKE '%$keyword%' OR nama_penduduk LIKE '%$keyword%' LIMIT 10";
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
    
    $query_penduduk = "SELECT * FROM penduduk WHERE nik = '$nik'";
    $result_penduduk = mysqli_query($conn, $query_penduduk);
    $penduduk = mysqli_fetch_assoc($result_penduduk);
    
    if ($penduduk) {
        $tanggal_surat = date('Y-m-d');
        $jenis_surat = 'SKD';
        $nama_pemohon = $penduduk['nama_penduduk'];
        $query_insert = "INSERT INTO arsip_surat (no_surat, jenis_surat, tanggal_surat, nik, nama_pemohon, tempat_lahir, tanggal_lahir, alamat, keperluan, keterangan, created_by) VALUES ('$no_surat', '$jenis_surat', '$tanggal_surat', '$nik', '$nama_pemohon', '{$penduduk['tempat_lahir']}', '{$penduduk['tanggal_lahir']}', '{$penduduk['alamat']}', '$keperluan', 'Surat Keterangan Domisili', '{$_SESSION['id_admin']}')";
        
        if (mysqli_query($conn, $query_insert)) $_SESSION['success_message'] = "Surat berhasil disimpan dengan nomor: " . $no_surat;
        else $_SESSION['error_message'] = "Gagal menyimpan surat: " . mysqli_error($conn);
    } else {
        $_SESSION['error_message'] = "Data penduduk tidak ditemukan!";
    }
    header("Location: domisili.php?nik=" . urlencode($nik));
    exit();
}

// ==================== HANDLE CETAK SURAT ====================
if (isset($_POST['action']) && $_POST['action'] == 'cetak') {
    $konten = $_POST['konten_surat'] ?? '';
    $paper_size = $_POST['paper_size'] ?? 'A4';
    $mt = (float)($_POST['margin_top'] ?? 2);
    $mb = (float)($_POST['margin_bottom'] ?? 2);
    $ml = (float)($_POST['margin_left'] ?? 3);
    $mr = (float)($_POST['margin_right'] ?? 2);
    $font_fam = $_POST['font_family'] ?? "'Times New Roman', Times, serif";
    $font_sz = $_POST['font_size'] ?? "12pt";
    
    $css_size = ($paper_size == 'F4') ? '215.9mm 330.2mm' : 'A4 portrait';
    $konten = preg_replace('/contenteditable="true"/', '', $konten);
    $konten = preg_replace('/<span[^>]*id="nomor_surat_text"[^>]*>/', '<span>', $konten);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Cetak Surat Domisili</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: <?php echo $font_fam; ?>; font-size: <?php echo $font_sz; ?>; background: white; color: #000; }
            @page { size: <?php echo $css_size; ?>; margin: 0; }
            .surat-container {
                padding-top: <?php echo $mt; ?>cm;
                padding-right: <?php echo $mr; ?>cm;
                padding-bottom: <?php echo $mb; ?>cm;
                padding-left: <?php echo $ml; ?>cm;
                width: 100%; height: 100vh; overflow: hidden;
            }
            table { width: 100%; border-collapse: collapse; margin: 5px 0; font-size: inherit; color: #000; }
            td { padding: 3px 0; vertical-align: top; }
        </style>
    </head>
    <body>
        <div class="surat-container"><?php echo $konten; ?></div>
        <script>window.onload = function() { window.print(); window.onafterprint = function() { window.close(); }; }</script>
    </body>
    </html>
    <?php
    exit();
}

// ==================== HANDLE DOWNLOAD PDF ====================
if (isset($_POST['action']) && $_POST['action'] == 'download_pdf') {
    if (!$use_dompdf) die("<h3>Error: Library Dompdf belum terinstall.</h3>");

    $konten = $_POST['konten_surat'] ?? '';
    $paper_size = $_POST['paper_size'] ?? 'A4';
    $mt = (float)($_POST['margin_top'] ?? 2);
    $mb = (float)($_POST['margin_bottom'] ?? 2);
    $ml = (float)($_POST['margin_left'] ?? 3);
    $mr = (float)($_POST['margin_right'] ?? 2);
    $font_fam = $_POST['font_family'] ?? "'Times New Roman', Times, serif";
    $font_sz = $_POST['font_size'] ?? "12pt";
    
    $konten = preg_replace('/contenteditable="true"/', '', $konten);
    $konten = preg_replace('/<span[^>]*id="nomor_surat_text"[^>]*>/', '<span>', $konten);
    
    $absolute_img_dir = str_replace('\\', '/', realpath(__DIR__ . '/../../img')) . '/';
    $konten = str_replace('../../img/', $absolute_img_dir, $konten);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            @page { margin: ' . $mt . 'cm ' . $mr . 'cm ' . $mb . 'cm ' . $ml . 'cm; }
            body { font-family: ' . $font_fam . '; font-size: ' . $font_sz . '; line-height: 1.3; color: #000000; }
            table { width: 100%; margin: 10px 0; border-collapse: collapse; font-size: inherit; }
            td { padding: 3px; vertical-align: top; color: #000000; }
        </style>
    </head>
    <body>' . $konten . '</body>
    </html>';
    
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('chroot', realpath(__DIR__ . '/../../'));
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    
    if ($paper_size == 'F4') $dompdf->setPaper(array(0, 0, 612.00, 936.00), 'portrait');
    else $dompdf->setPaper('A4', 'portrait');
    
    $dompdf->render();
    $dompdf->stream("surat_domisili_" . date('Ymd_His') . ".pdf", array("Attachment" => true));
    exit;
}

// ==================== HANDLE DOWNLOAD WORD ====================
if (isset($_POST['action']) && $_POST['action'] == 'download_word') {
    if (!$use_phpword) die("<h3>Error: Library PHPWord belum terinstall.</h3>");

    $nik = $_POST['nik'] ?? '';
    $no_surat = $_POST['no_surat'] ?? '';
    $paper_size = $_POST['paper_size'] ?? 'A4';
    $jenis_ttd = $_POST['jenis_ttd'] ?? 'barcode';
    $is_blanko = $_POST['is_blanko'] ?? 'false';
    
    $mt = (float)($_POST['margin_top'] ?? 2) * 567;
    $mb = (float)($_POST['margin_bottom'] ?? 2) * 567;
    $ml = (float)($_POST['margin_left'] ?? 3) * 567;
    $mr = (float)($_POST['margin_right'] ?? 2) * 567;
    
    $penduduk_word = null;
    if (!empty($nik)) {
        $nik_aman = mysqli_real_escape_string($conn, $nik);
        $result = mysqli_query($conn, "SELECT * FROM penduduk WHERE nik = '$nik_aman'");
        $penduduk_word = mysqli_fetch_assoc($result);
    }
    
    $phpWord = new PhpWord();
    
    $sectionStyle = [
        'marginTop' => $mt, 'marginBottom' => $mb,
        'marginLeft' => $ml, 'marginRight' => $mr
    ];
    
    if ($paper_size == 'F4') {
        $sectionStyle['paperSize'] = 'Custom';
        $sectionStyle['pageSizeW'] = 12240; 
        $sectionStyle['pageSizeH'] = 18720; 
    } else {
        $sectionStyle['paperSize'] = 'A4';
    }

    $section = $phpWord->addSection($sectionStyle);
    $phpWord->setDefaultFontName('Times New Roman');
    $phpWord->setDefaultFontSize(12);
    
    if ($is_blanko === 'false') {
        $table = $section->addTable(['borderSize' => 0, 'width' => '100%']);
        $table->addRow();
        $cellLogo = $table->addCell(1500);
        if (file_exists('../../img/labang.png')) {
            $cellLogo->addImage('../../img/labang.png', ['width' => 70, 'height' => 70, 'alignment' => Jc::CENTER]);
        }
        
        $cellText = $table->addCell(8500);
        $cellText->addText('PEMERINTAH KABUPATEN BANGKALAN', ['bold' => true, 'size'=>13], ['alignment' => Jc::CENTER]);
        $cellText->addText('KECAMATAN LABANG', ['bold' => true, 'size'=>13], ['alignment' => Jc::CENTER]);
        $cellText->addText('KANTOR KEPALA DESA SUKOLILO TIMUR', ['bold' => true, 'size'=>13], ['alignment' => Jc::CENTER]);
        $cellText->addText('Labang 69163', ['size'=>11], ['alignment' => Jc::CENTER]);
        
        $section->addTextBreak(0.5);
        $section->addLine(['weight' => 2, 'width' => '100%', 'height' => 0]);
    }
    
    $section->addText('SURAT KETERANGAN DOMISILI', ['bold' => true, 'underline' => 'single', 'size'=>13], ['alignment' => Jc::CENTER]);
    $section->addText('NO : ' . $no_surat, [], ['alignment' => Jc::CENTER]);
    $section->addTextBreak(0.5);
    
    $section->addText('Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:');
    
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
        $tableData->addCell(2500)->addText($row[0]);
        $tableData->addCell(300)->addText(':');
        $tableData->addCell(7000)->addText($row[1]);
    }
    
    $section->addTextBreak(0.5);
    $section->addText('menerangkan dengan sebenarnya bahwa orang tersebut di atas benar-benar berdomisili di Dsn. Paserean Desa Sukolilo Timur.');
    $section->addTextBreak(0.5);
    $section->addText('Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.');
    $section->addTextBreak(1.5);
    
    $section->addText('Sukolilo Timur, ' . tgl_indonesia(date('Y-m-d')), [], ['alignment' => Jc::RIGHT]);
    $section->addText('Kepala Desa Sukolilo Timur', [], ['alignment' => Jc::RIGHT]);
    
    if ($jenis_ttd == 'barcode' && file_exists('../../img/ttd.png')) {
        $section->addImage('../../img/ttd.png', ['width' => 80, 'height' => 80, 'alignment' => Jc::RIGHT]);
    } else {
        $section->addTextBreak(3);
    }
    
    $section->addText('H. ZAINAL ABIDIN', ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::RIGHT]);
    
    $filename = "surat_domisili_" . date('Ymd_His') . ".docx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit;
}

// ==================== AMBIL DATA PENDUDUK ====================
$penduduk = null;
$nomor_surat_otomatis = getNomorSuratBerikutnya($conn, 'SKD');

if (isset($_GET['nik']) && !empty($_GET['nik'])) {
    $nik = mysqli_real_escape_string($conn, $_GET['nik']);
    $result = mysqli_query($conn, "SELECT * FROM penduduk WHERE nik = '$nik'");
    $penduduk = mysqli_fetch_assoc($result);
}

$tanggal_sekarang = tgl_indonesia(date('Y-m-d'));
$pageTitle = "Surat Keterangan Domisili";
$pageHeaderButton = '<a href="../surat.php" class="btn btn-sm btn-primary shadow-sm rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Kembali</a>';

ob_start();
?>

<style>
/* Modern UI Overrides */
body { background-color: #f8f9fc; }
.card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); overflow: visible; /* PERBAIKAN: Overflow harus visible agar dropdown bisa keluar */ }
.card-header.bg-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border-bottom: none; border-radius: 1rem 1rem 0 0; }

/* PERBAIKAN FATAL: Custom Search Input Z-INDEX */
.search-container { position: relative; width: 100%; z-index: 9999 !important; }
.search-results { 
    position: absolute; 
    top: 100%; 
    left: 0; 
    right: 0; 
    background: white; 
    border-radius: 0.5rem; 
    margin-top: 5px; 
    max-height: 250px; 
    overflow-y: auto; 
    z-index: 99999 !important; /* Maksimal z-index */
    display: none; 
    box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
    border: 1px solid #4e73df; 
    animation: fadeIn 0.2s ease-in-out;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

.search-result-item { padding: 12px 20px; cursor: pointer; border-bottom: 1px solid #f8f9fc; transition: background 0.2s; display: flex; justify-content: space-between; align-items: center;}
.search-result-item:hover { background: #eaecf4; border-left: 4px solid #4e73df; }
.search-result-item strong { color: #4e73df; font-size: 1.05rem;}
.search-result-item small { color: #858796; display: block; }
.search-result-info { display: flex; flex-direction: column; }
.quick-peek { color: #858796; font-size: 0.85rem; background: #e3e6f0; padding: 2px 8px; border-radius: 10px; }

/* Profile Data Card */
.data-card { background: white; border-left: 5px solid #4e73df; padding: 25px; border-radius: 1rem; margin-bottom: 25px; box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.03); transition: transform 0.2s; position: relative; z-index: 1;}
.data-card:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08); }
.data-label { font-weight: 600; color: #858796; min-width: 130px; display: inline-block; }
.data-value { color: #3a3b45; font-weight: 500; }

/* Editor Workspace & Fullscreen Feature */
#editorWrapper { position: relative; transition: all 0.3s ease; z-index: 10;}
#editorWrapper.fullscreen-mode {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    z-index: 999999; background: #e2e8f0; padding: 20px;
    display: flex; flex-direction: column; overflow-y: auto;
}
.editor-workspace { background-color: #eaecf4; padding: 40px 15px; display: flex; justify-content: center; border-radius: 0 0 1rem 1rem; overflow-x: auto; box-shadow: inset 0 3px 6px rgba(0,0,0,0.04); }
#editorWrapper.fullscreen-mode .editor-workspace { flex: 1; border-radius: 1rem; }

/* Paper Container */
.editor-container { background: white; box-shadow: 0 15px 35px rgba(0,0,0,0.1); font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.4; color: #000; transition: all 0.3s ease; box-sizing: border-box; position: relative; }
.editor-container.paper-a4 { width: 210mm; min-height: 297mm; }
.editor-container.paper-f4 { width: 215.9mm; min-height: 330.2mm; }

/* Content Editable Highlight */
[contenteditable="true"] { border-bottom: 1px dashed #4e73df; min-width: 50px; display: inline-block; padding: 0 5px; outline: none; background: rgba(78, 115, 223, 0.05); border-radius: 3px; transition: background 0.2s; }
[contenteditable="true"]:focus { border-bottom: 2px solid #4e73df; background: rgba(78, 115, 223, 0.15); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

/* FITUR BARU: Auto Capitalize */
.text-uppercase { text-transform: uppercase; }

/* Modern Toolbar Grouping */
.toolbar { background: white; padding: 15px 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; border-bottom: 1px solid #e3e6f0; }
#editorWrapper.fullscreen-mode .toolbar { border-radius: 1rem; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.toolbar .input-group-text { font-size: 0.85rem; font-weight: 600; background-color: #f8f9fc; border-color: #d1d3e2; color: #4e73df; }
.toolbar .form-control, .toolbar .form-select { font-size: 0.85rem; border-color: #d1d3e2; }
.toolbar .form-control:focus, .toolbar .form-select:focus { border-color: #4e73df; box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25); }
.toolbar-btn { background: #f8f9fc; border: 1px solid #d1d3e2; border-radius: 0.35rem; padding: 6px 14px; cursor: pointer; color: #5a5c69; transition: all 0.2s; font-size: 0.9rem; }
.toolbar-btn:hover { background: #4e73df; color: white; border-color: #4e73df; box-shadow: 0 2px 5px rgba(78,115,223,0.3); }
.toolbar-divider { height: 35px; width: 2px; background-color: #eaecf4; border-radius: 1px; margin: 0 2px; }

/* Action Buttons as Borderless Table */
.action-table { width: 100%; border-collapse: separate; border-spacing: 8px; border: none; }
.action-table td { padding: 0; vertical-align: middle; border: none; }
.btn-action { width: 100%; padding: 10px 15px; font-weight: 600; border-radius: 0.5rem; transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-action:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.15); opacity: 0.95; }
.btn-preview { background: #f8f9fc; color: #5a5c69; border: 1px solid #d1d3e2; }
.btn-preview:hover { background: #eaecf4; color: #3a3b45; }
.btn-pdf { background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%); color: white; border: none; }
.btn-word { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: white; border: none; }
.btn-print { background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%); color: white; border: none; }
.btn-save { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); color: white; border: none; }
</style>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-lg" role="alert">
    <i class="fas fa-check-circle me-2 fs-5 align-middle"></i>
    <span class="align-middle"><?php echo $_SESSION['success_message']; ?></span>
    <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-lg" role="alert">
    <i class="fas fa-exclamation-circle me-2 fs-5 align-middle"></i>
    <span class="align-middle"><?php echo $_SESSION['error_message']; ?></span>
    <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<div class="card mb-4" style="z-index: 999 !important;">
    <div class="card-header bg-primary text-white py-3">
        <h6 class="m-0 font-weight-bold"><i class="fas fa-search me-2"></i>Pencarian Data Penduduk</h6>
    </div>
    <div class="card-body bg-white">
        <form id="formCariPenduduk" method="GET" action="">
            <div class="row align-items-center">
                <div class="col-md-10 mb-3 mb-md-0">
                    <label class="form-label font-weight-bold text-gray-800 mb-2">Pilih Penduduk Berdasarkan NIK / Nama</label>
                    <div class="search-container">
                        <div class="input-group input-group-lg shadow-sm rounded-lg">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-primary"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="search_input" 
                                   placeholder="Mulai ketik NIK atau nama untuk memunculkan data..." 
                                   value="<?php echo isset($_GET['nik']) ? htmlspecialchars($_GET['nik']) : ''; ?>" 
                                   autocomplete="off">
                        </div>
                        <input type="hidden" name="nik" id="selected_nik" value="<?php echo isset($_GET['nik']) ? htmlspecialchars($_GET['nik']) : ''; ?>">
                        <div id="searchResults" class="search-results"></div>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end mt-2 mt-md-0">
                    <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm rounded-lg"><i class="fas fa-search me-1"></i> Cari</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($penduduk): ?>

<div class="data-card">
    <h5 class="font-weight-bold text-primary border-bottom pb-3 mb-4"><i class="fas fa-address-card me-2"></i>Informasi Pemohon</h5>
    <div class="row text-gray-800">
        <div class="col-md-6">
            <div class="mb-3"><span class="data-label"><i class="fas fa-id-badge me-2 text-gray-400"></i>NIK</span> <span class="me-2">:</span> <span class="data-value fs-6"><?php echo $penduduk['nik']; ?></span></div>
            <div class="mb-3"><span class="data-label"><i class="fas fa-user me-2 text-gray-400"></i>Nama</span> <span class="me-2">:</span> <span class="data-value fs-6 font-weight-bold"><?php echo $penduduk['nama_penduduk']; ?></span></div>
            <div class="mb-3"><span class="data-label"><i class="fas fa-map-marker-alt me-2 text-gray-400"></i>Tempat Lahir</span> <span class="me-2">:</span> <span class="data-value"><?php echo $penduduk['tempat_lahir']; ?></span></div>
            <div class="mb-3"><span class="data-label"><i class="fas fa-calendar-day me-2 text-gray-400"></i>Tanggal Lahir</span> <span class="me-2">:</span> <span class="data-value"><?php echo tgl_indonesia($penduduk['tanggal_lahir']); ?></span></div>
        </div>
        <div class="col-md-6">
            <div class="mb-3"><span class="data-label"><i class="fas fa-venus-mars me-2 text-gray-400"></i>Kelamin</span> <span class="me-2">:</span> <span class="data-value"><?php echo $penduduk['jenis_kelamin']; ?></span></div>
            <div class="mb-3"><span class="data-label"><i class="fas fa-ring me-2 text-gray-400"></i>Status</span> <span class="me-2">:</span> <span class="data-value"><?php echo $penduduk['status_kawin']; ?></span></div>
            <div class="mb-3"><span class="data-label"><i class="fas fa-briefcase me-2 text-gray-400"></i>Pekerjaan</span> <span class="me-2">:</span> <span class="data-value"><?php echo $penduduk['pekerjaan'] ?: '-'; ?></span></div>
            <div class="mb-3"><span class="data-label"><i class="fas fa-home me-2 text-gray-400"></i>Alamat</span> <span class="me-2">:</span> <span class="data-value"><?php echo $penduduk['alamat']; ?></span></div>
        </div>
    </div>
</div>

<form id="formBuatSurat" method="POST">
    <input type="hidden" name="nik" value="<?php echo $penduduk['nik']; ?>">
    <input type="hidden" name="action" id="formAction" value="">
    
    <input type="hidden" name="paper_size" id="hiddenPaperSize" value="A4">
    <input type="hidden" name="jenis_ttd" id="hiddenJenisTtd" value="barcode">
    <input type="hidden" name="margin_top" id="hiddenMarginTop" value="2">
    <input type="hidden" name="margin_bottom" id="hiddenMarginBottom" value="2">
    <input type="hidden" name="margin_left" id="hiddenMarginLeft" value="3">
    <input type="hidden" name="margin_right" id="hiddenMarginRight" value="2">
    <input type="hidden" name="font_family" id="hiddenFontFamily" value="'Times New Roman', Times, serif">
    <input type="hidden" name="font_size" id="hiddenFontSize" value="12pt">
    <input type="hidden" name="is_blanko" id="hiddenIsBlanko" value="false">
    
    <div class="card mb-4" id="editorWrapper">
        <div class="card-header bg-primary py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-edit me-2"></i>Ruang Kerja Editor Surat</h6>
            <span class="badge bg-white text-primary px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('d M Y'); ?></span>
        </div>
        
        <div class="toolbar">
            
            <button type="button" class="toolbar-btn bg-gradient-primary text-primary border-primary bg-light" onclick="toggleFullScreen()" id="btnFullscreen" title="Mode Layar Penuh">
                <i class="fas fa-expand-arrows-alt"></i> Fullscreen
            </button>
            
            <div class="toolbar-divider"></div>

            <div class="input-group" style="width: auto;">
                <span class="input-group-text"><i class="fas fa-file-alt me-1"></i> Kertas</span>
                <select class="form-select font-weight-bold" id="paperSelect">
                    <option value="A4">A4 Default</option>
                    <option value="F4">F4 (Folio)</option>
                </select>
            </div>
            
            <div class="toolbar-divider"></div>

            <div class="input-group" style="width: auto;">
                <span class="input-group-text"><i class="fas fa-border-style me-1"></i> Margin (cm)</span>
                
                <span class="input-group-text bg-white border-end-0 pe-1 text-muted" title="Batas Atas">Atas</span>
                <input type="number" id="inputMarginTop" class="form-control text-center ps-1 font-weight-bold text-primary" value="2" min="0" step="0.5" style="width: 55px;">
                
                <span class="input-group-text bg-white border-end-0 border-start-0 pe-1 text-muted" title="Batas Bawah">Bawah</span>
                <input type="number" id="inputMarginBottom" class="form-control text-center ps-1 font-weight-bold text-primary" value="2" min="0" step="0.5" style="width: 55px;">
                
                <span class="input-group-text bg-white border-end-0 border-start-0 pe-1 text-muted" title="Batas Kiri">Kiri</span>
                <input type="number" id="inputMarginLeft" class="form-control text-center ps-1 font-weight-bold text-primary" value="3" min="0" step="0.5" style="width: 55px;">
                
                <span class="input-group-text bg-white border-end-0 border-start-0 pe-1 text-muted" title="Batas Kanan">Kanan</span>
                <input type="number" id="inputMarginRight" class="form-control text-center ps-1 font-weight-bold text-primary" value="2" min="0" step="0.5" style="width: 55px;">
            </div>

            <div class="toolbar-divider"></div>

            <div class="input-group" style="width: auto;">
                <span class="input-group-text"><i class="fas fa-font me-1"></i> Font</span>
                <select class="form-select font-weight-bold text-dark" id="fontFamilySelect">
                    <option value="'Times New Roman', Times, serif">Times New Roman</option>
                    <option value="Arial, Helvetica, sans-serif">Arial</option>
                    <option value="Calibri, sans-serif">Calibri</option>
                </select>
                <select class="form-select font-weight-bold text-dark border-start-0" id="fontSizeSelect" style="width: 70px;">
                    <option value="11pt">11</option>
                    <option value="12pt" selected>12</option>
                    <option value="14pt">14</option>
                </select>
            </div>

            <div class="toolbar-divider"></div>

            <div class="input-group" style="width: auto;">
                <span class="input-group-text"><i class="fas fa-pen-nib me-1"></i> Mode TTD</span>
                <select class="form-select font-weight-bold text-dark" id="ttdSelect">
                    <option value="barcode">Cetak Barcode</option>
                    <option value="basah">Ruang TTD Basah</option>
                </select>
            </div>

            <div class="toolbar-divider"></div>

            <div class="form-check form-switch mt-1 ms-1">
                <input class="form-check-input" type="checkbox" id="toggleBlanko">
                <label class="form-check-label font-weight-bold text-primary" for="toggleBlanko" title="Aktifkan jika ingin mencetak di kertas ber-Kop resmi">Kertas Blanko</label>
            </div>
            
            <div class="toolbar-divider"></div>

            <div class="btn-group" role="group">
                <button type="button" class="toolbar-btn rounded-start" onclick="document.execCommand('bold', false, null)" title="Tebal (Ctrl+B)"><i class="fas fa-bold"></i></button>
                <button type="button" class="toolbar-btn border-start-0 border-end-0" onclick="document.execCommand('italic', false, null)" title="Miring (Ctrl+I)"><i class="fas fa-italic"></i></button>
                <button type="button" class="toolbar-btn rounded-end" onclick="document.execCommand('underline', false, null)" title="Garis Bawah (Ctrl+U)"><i class="fas fa-underline"></i></button>
            </div>
        </div>
        
        <div class="editor-workspace">
            <div class="editor-container paper-a4" id="editor" contenteditable="true" style="padding: 2cm 2cm 2cm 3cm; font-family: 'Times New Roman', Times, serif; font-size: 12pt;">
                
                <div id="kopSuratArea" style="display: table; width: 100%; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 8px;">
                    <div style="display: table-cell; width: 90px; vertical-align: middle;">
                        <img src="../../img/labang.png" alt="Logo Desa" style="max-width: 80px; max-height: 80px;">
                    </div>
                    <div style="display: table-cell; vertical-align: middle; text-align: center; color: #000;">
                        <h4 style="margin:0; font-weight: bold; font-size: 14pt; line-height: 1.2; color: #000;">PEMERINTAH KABUPATEN BANGKALAN</h4>
                        <h4 style="margin:0; font-weight: bold; font-size: 14pt; line-height: 1.2; color: #000;">KECAMATAN LABANG</h4>
                        <h4 style="margin:0; font-weight: bold; font-size: 14pt; line-height: 1.2; color: #000;">KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                        <p style="margin:0; font-size: 11pt; color: #000;">Labang 69163</p>
                    </div>
                </div>
                
                <div style="text-align: center; margin: 15px 0 20px; color: #000;">
                    <h4 style="text-decoration: underline; font-weight: bold; margin:0 0 3px 0; font-size: 13pt; color: #000;">SURAT KETERANGAN DOMISILI</h4>
                    <p style="margin:0; font-size: inherit; color: #000;">NO : <span id="nomor_surat_text" contenteditable="true"><?php echo $nomor_surat_otomatis; ?></span></p>
                </div>
                
                <div style="color: #000;">
                    <p style="margin-bottom: 10px;">Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:</p>
                    
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; color: #000; font-size: inherit;">
                        <tr>
                            <td style="width: 150px; padding: 4px 0;">N a m a</td>
                            <td style="width: 20px; padding: 4px 0;">:</td>
                            <td style="padding: 4px 0;"><span contenteditable="true" class="text-uppercase font-weight-bold"><?php echo $penduduk['nama_penduduk']; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0;">Tempat / Tgl Lahir</td>
                            <td style="padding: 4px 0;">:</td>
                            <td style="padding: 4px 0;"><span contenteditable="true"><?php echo $penduduk['tempat_lahir'] . ', ' . tgl_indonesia($penduduk['tanggal_lahir']); ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0;">NIK</td>
                            <td style="padding: 4px 0;">:</td>
                            <td style="padding: 4px 0;"><span contenteditable="true"><?php echo $penduduk['nik']; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0;">Jenis Kelamin</td>
                            <td style="padding: 4px 0;">:</td>
                            <td style="padding: 4px 0;"><span contenteditable="true"><?php echo $penduduk['jenis_kelamin'] == 'LAKI-LAKI' ? 'Laki-laki' : 'Perempuan'; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0;">Status Perkawinan</td>
                            <td style="padding: 4px 0;">:</td>
                            <td style="padding: 4px 0;"><span contenteditable="true"><?php echo $penduduk['status_kawin']; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0;">Agama</td>
                            <td style="padding: 4px 0;">:</td>
                            <td style="padding: 4px 0;"><span contenteditable="true"><?php echo $penduduk['agama']; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0;">Pekerjaan</td>
                            <td style="padding: 4px 0;">:</td>
                            <td style="padding: 4px 0;"><span contenteditable="true"><?php echo $penduduk['pekerjaan'] ?: '-'; ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0;">Alamat</td>
                            <td style="padding: 4px 0;">:</td>
                            <td style="padding: 4px 0;">
                                <span contenteditable="true" class="text-uppercase"><?php echo $penduduk['alamat']; ?></span><br>
                                <span contenteditable="true">Kecamatan Labang Kabupaten Bangkalan</span>
                            </td>
                        </tr>
                    </table>
                    
                    <p style="margin-bottom: 10px;">menerangkan dengan sebenarnya bahwa orang tersebut di atas benar-benar berdomisili di <span contenteditable="true" class="font-weight-bold">Dsn. Paserean Desa Sukolilo Timur</span>.</p>
                    <p>Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
                </div>
                
                <table style="width: 100%; border: none; margin-top: 30px; color: #000; font-size: inherit;">
                    <tr>
                        <td style="width: 55%; border: none;"></td>
                        <td style="width: 45%; border: none; text-align: center; color: #000; vertical-align: top;">
                            Sukolilo Timur, <span contenteditable="true"><?php echo $tanggal_sekarang; ?></span><br>
                            Kepala Desa Sukolilo Timur<br><br><br> <div id="ttd_area" style="text-align: center;">
                                <?php if (file_exists('../../img/ttd.png')): ?>
                                    <img id="ttd_barcode" src="../../img/ttd.png" alt="Barcode" style="width: 90px; height: 90px; display: inline-block;">
                                <?php else: ?>
                                    <span id="ttd_barcode" style="display: inline-block; width: 90px; height: 90px; color: red; font-size: 10pt;">[Tanpa Barcode]</span>
                                <?php endif; ?>
                                <div id="ttd_basah" style="height: 90px; width: 100%; display: none;"></div>
                            </div>
                            <br><br> <span style="text-decoration: underline; font-weight: bold; color: #000;" contenteditable="true">H. ZAINAL ABIDIN</span>
                        </td>
                    </tr>
                </table>

            </div>
        </div>
        
        <div class="card-footer bg-white py-4 border-top-0 rounded-bottom">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-4 mb-lg-0 border-end pe-4">
                    <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-cog me-2"></i>Pengaturan Simpan</h6>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label small font-weight-bold text-gray-600 mb-1">Nomor Arsip Surat</label>
                            <input type="text" class="form-control" id="no_surat" name="no_surat" value="<?php echo $nomor_surat_otomatis; ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small font-weight-bold text-gray-600 mb-1">Keperluan (Opsional)</label>
                            <input type="text" class="form-control" id="keperluan" name="keperluan" placeholder="Misal: Pembuatan KTP">
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 ps-lg-4">
                    <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-magic me-2"></i>Aksi Eksekusi Surat</h6>
                    
                    <table class="action-table">
                        <tr>
                            <td style="width: 25%;">
                                <button type="button" class="btn-action btn-preview" onclick="previewSurat()">
                                    <i class="fas fa-eye text-info"></i> Lihat Preview
                                </button>
                            </td>
                            <td style="width: 25%;">
                                <button type="button" class="btn-action btn-pdf" onclick="downloadPDF()">
                                    <i class="fas fa-file-pdf"></i> Unduh PDF
                                </button>
                            </td>
                            <td style="width: 25%;">
                                <button type="button" class="btn-action btn-word" onclick="downloadWord()">
                                    <i class="fas fa-file-word"></i> Unduh Word
                                </button>
                            </td>
                            <td style="width: 25%;">
                                <button type="button" class="btn-action btn-print" onclick="cetakSurat()">
                                    <i class="fas fa-print"></i> Cetak Kertas
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 5px;">
                                <a href="domisili.php" class="btn-action btn-preview" style="background: #fdfdfd;">
                                    <i class="fas fa-redo text-secondary"></i> Reset Form Pilihan
                                </a>
                            </td>
                            <td colspan="2" style="padding-top: 5px;">
                                <button type="button" class="btn-action btn-save shadow-lg" onclick="simpanSurat()" style="font-size: 1.05rem;">
                                    <i class="fas fa-save"></i> Simpan Arsip Surat
                                </button>
                            </td>
                        </tr>
                    </table>

                </div>
            </div>
        </div>
    </div>
</form>

<?php endif; ?>

<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white py-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-eye me-2"></i>Pratinjau Hasil Akhir</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalPreview()"></button>
            </div>
            <div class="modal-body bg-light d-flex justify-content-center py-5">
                <div id="previewContent" class="bg-white shadow-lg" style="color: #000; border-radius: 3px;"></div>
            </div>
            <div class="modal-footer bg-white py-3">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" onclick="closeModalPreview()"><i class="fas fa-times me-1"></i> Tutup Pratinjau</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmSaveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-gradient-success text-white py-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-save me-2"></i>Konfirmasi Arsip</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalConfirm()"></button>
            </div>
            <div class="modal-body text-center py-5">
                <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-4 shadow" style="width: 80px; height: 80px;">
                    <i class="fas fa-file-alt fa-3x"></i>
                </div>
                <h4 class="text-gray-800 font-weight-bold mb-3">Simpan Surat ke Arsip?</h4>
                <p class="text-muted mb-4">Pastikan data sudah benar sebelum menyimpan.</p>
                <div class="alert alert-light border text-start shadow-sm mx-auto" style="max-width: 350px;">
                    <div class="mb-2 border-bottom pb-2"><strong>Nomor Surat:</strong> <br><span id="confirmNoSurat" class="text-primary fs-5"></span></div>
                    <div><strong>Pemohon:</strong> <br><span class="text-dark font-weight-bold"><?php echo $penduduk['nama_penduduk'] ?? ''; ?></span></div>
                </div>
            </div>
            <div class="modal-footer bg-light py-3 d-flex justify-content-center">
                <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalConfirm()">Batal</button>
                <button type="button" class="btn btn-success px-5 rounded-pill shadow-sm" id="btnConfirmSave"><i class="fas fa-check me-1"></i> Ya, Simpan Sekarang</button>
            </div>
        </div>
    </div>
</div>

<script>
// ===== VARIABEL GLOBAL & INISIALISASI =====
let modalPreviewInstance = null;
let modalConfirmInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    if(document.getElementById('previewModal')) modalPreviewInstance = new bootstrap.Modal(document.getElementById('previewModal'));
    if(document.getElementById('confirmSaveModal')) modalConfirmInstance = new bootstrap.Modal(document.getElementById('confirmSaveModal'));
    
    // Auto Capitalize Event Listener
    const editableElements = document.querySelectorAll('.text-uppercase[contenteditable="true"]');
    editableElements.forEach(el => {
        el.addEventListener('input', function() {
            let cursorPosition = getCaretCharacterOffsetWithin(this);
            this.textContent = this.textContent.toUpperCase();
            setCaretPosition(this, cursorPosition);
        });
    });
});

function closeModalPreview() { if (modalPreviewInstance) modalPreviewInstance.hide(); }
function closeModalConfirm() { if (modalConfirmInstance) modalConfirmInstance.hide(); }
function closeAlert(element) { const alert = element.closest('.alert'); if (alert) alert.remove(); }

// Fungsi pembantu untuk Auto-Capitalize agar kursor tidak lompat
function getCaretCharacterOffsetWithin(element) {
    let caretOffset = 0;
    let doc = element.ownerDocument || element.document;
    let win = doc.defaultView || doc.parentWindow;
    let sel;
    if (typeof win.getSelection != "undefined") {
        sel = win.getSelection();
        if (sel.rangeCount > 0) {
            let range = win.getSelection().getRangeAt(0);
            let preCaretRange = range.cloneRange();
            preCaretRange.selectNodeContents(element);
            preCaretRange.setEnd(range.endContainer, range.endOffset);
            caretOffset = preCaretRange.toString().length;
        }
    }
    return caretOffset;
}
function setCaretPosition(el, pos) {
    let range = document.createRange();
    let sel = window.getSelection();
    if(el.childNodes.length > 0) {
        let textNode = el.childNodes[0];
        if(pos > textNode.length) pos = textNode.length;
        range.setStart(textNode, pos);
        range.collapse(true);
        sel.removeAllRanges();
        sel.addRange(range);
    }
}

// ===== FITUR FULLSCREEN MODE =====
function toggleFullScreen() {
    const wrapper = document.getElementById('editorWrapper');
    const btn = document.getElementById('btnFullscreen');
    
    wrapper.classList.toggle('fullscreen-mode');
    
    if (wrapper.classList.contains('fullscreen-mode')) {
        document.body.style.overflow = 'hidden'; 
        btn.innerHTML = '<i class="fas fa-compress-arrows-alt"></i> Keluar Fullscreen';
        btn.classList.replace('bg-light', 'bg-warning');
        btn.classList.replace('text-primary', 'text-dark');
    } else {
        document.body.style.overflow = ''; 
        btn.innerHTML = '<i class="fas fa-expand-arrows-alt"></i> Fullscreen';
        btn.classList.replace('bg-warning', 'bg-light');
        btn.classList.replace('text-dark', 'text-primary');
    }
}
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        const wrapper = document.getElementById('editorWrapper');
        if (wrapper && wrapper.classList.contains('fullscreen-mode')) toggleFullScreen();
    }
});

// ===== LIVE SEARCH =====
const searchInput = document.getElementById('search_input');
const searchResults = document.getElementById('searchResults');
const selectedNik = document.getElementById('selected_nik');
let searchTimeout;

if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const keyword = this.value.trim();
        if (keyword.length < 2) { searchResults.style.display = 'none'; return; }
        
        searchTimeout = setTimeout(() => {
            fetch('domisili.php?search_penduduk=' + encodeURIComponent(keyword))
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        let html = '';
                        data.forEach(item => {
                            html += `<div class="search-result-item" onclick="pilihPenduduk('${item.nik}', '${item.nama_penduduk}')">
                                        <div class="search-result-info">
                                            <strong>${item.nama_penduduk}</strong>
                                            <small>NIK: ${item.nik}</small>
                                        </div>
                                        <span class="quick-peek"><i class="fas fa-arrow-right"></i> Pilih</span>
                                     </div>`;
                        });
                        searchResults.innerHTML = html;
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<div class="search-result-item text-muted text-center" style="justify-content: center;"><i class="fas fa-info-circle me-1"></i> Tidak ada data penduduk ditemukan</div>';
                        searchResults.style.display = 'block';
                    }
                });
        }, 300);
    });
    
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

// ===== PENGATURAN KERTAS, FONT, MARGIN & MODE TTD =====
const editor = document.getElementById('editor');

document.getElementById('paperSelect')?.addEventListener('change', function() {
    document.getElementById('hiddenPaperSize').value = this.value;
    if (this.value === 'F4') {
        editor.classList.remove('paper-a4'); editor.classList.add('paper-f4');
    } else {
        editor.classList.remove('paper-f4'); editor.classList.add('paper-a4');
    }
});

document.getElementById('ttdSelect')?.addEventListener('change', function() {
    document.getElementById('hiddenJenisTtd').value = this.value;
    if (this.value === 'barcode') {
        document.getElementById('ttd_barcode').style.display = 'inline-block';
        document.getElementById('ttd_basah').style.display = 'none';
    } else {
        document.getElementById('ttd_barcode').style.display = 'none';
        document.getElementById('ttd_basah').style.display = 'block';
    }
});

document.getElementById('fontFamilySelect')?.addEventListener('change', function() {
    document.getElementById('hiddenFontFamily').value = this.value;
    editor.style.fontFamily = this.value;
});

document.getElementById('fontSizeSelect')?.addEventListener('change', function() {
    document.getElementById('hiddenFontSize').value = this.value;
    editor.style.fontSize = this.value;
});

document.getElementById('toggleBlanko')?.addEventListener('change', function() {
    document.getElementById('hiddenIsBlanko').value = this.checked;
    if(this.checked) {
        document.getElementById('kopSuratArea').style.visibility = 'hidden'; 
    } else {
        document.getElementById('kopSuratArea').style.visibility = 'visible';
    }
});

function applyMargins() {
    let t = document.getElementById('inputMarginTop').value || 0;
    let b = document.getElementById('inputMarginBottom').value || 0;
    let l = document.getElementById('inputMarginLeft').value || 0;
    let r = document.getElementById('inputMarginRight').value || 0;
    
    document.getElementById('hiddenMarginTop').value = t;
    document.getElementById('hiddenMarginBottom').value = b;
    document.getElementById('hiddenMarginLeft').value = l;
    document.getElementById('hiddenMarginRight').value = r;

    editor.style.padding = `${t}cm ${r}cm ${b}cm ${l}cm`;
}

document.getElementById('inputMarginTop')?.addEventListener('input', applyMargins);
document.getElementById('inputMarginBottom')?.addEventListener('input', applyMargins);
document.getElementById('inputMarginLeft')?.addEventListener('input', applyMargins);
document.getElementById('inputMarginRight')?.addEventListener('input', applyMargins);

document.getElementById('no_surat')?.addEventListener('input', function() {
    document.getElementById('nomor_surat_text').textContent = this.value;
});

// ===== CORE FUNCTIONS =====
function getCleanEditorContent() {
    const clone = editor.cloneNode(true);
    
    if(document.getElementById('toggleBlanko') && document.getElementById('toggleBlanko').checked) {
        const kop = clone.querySelector('#kopSuratArea');
        if(kop) kop.remove();
    }

    clone.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.removeAttribute('contenteditable');
        el.style.borderBottom = 'none';
        el.style.background = 'none';
        el.style.boxShadow = 'none';
    });
    return clone.innerHTML;
}

function submitFormWithAction(action, blank = false) {
    const form = document.getElementById('formBuatSurat');
    document.getElementById('formAction').value = action;
    
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'konten_surat';
    hiddenInput.value = getCleanEditorContent();
    form.appendChild(hiddenInput);
    
    if (blank) form.target = '_blank';
    else form.target = '';
    
    form.submit();
    form.removeChild(hiddenInput);
    document.getElementById('formAction').value = '';
}

function previewSurat() {
    const preview = document.getElementById('previewContent');
    preview.innerHTML = getCleanEditorContent();
    
    let paper = document.getElementById('paperSelect').value;
    preview.style.padding = editor.style.padding;
    preview.style.fontFamily = editor.style.fontFamily;
    preview.style.fontSize = editor.style.fontSize;
    
    if(paper === 'F4') {
        preview.style.width = '215.9mm'; preview.style.minHeight = '330.2mm';
    } else {
        preview.style.width = '210mm'; preview.style.minHeight = '297mm';
    }
    
    if (modalPreviewInstance) modalPreviewInstance.show();
}

function cetakSurat() { submitFormWithAction('cetak', true); }
function downloadPDF() { submitFormWithAction('download_pdf'); }
function downloadWord() { submitFormWithAction('download_word'); }

function simpanSurat() {
    document.getElementById('confirmNoSurat').textContent = document.getElementById('no_surat').value;
    if (modalConfirmInstance) modalConfirmInstance.show();
}

document.getElementById('btnConfirmSave')?.addEventListener('click', function() {
    submitFormWithAction('simpan_surat');
});

document.getElementById('formBuatSurat')?.addEventListener('submit', function(e) {
    if (!document.getElementById('formAction').value) e.preventDefault();
});
</script>

<?php
$content = ob_get_clean();
include '../../includes/base.php';
?>