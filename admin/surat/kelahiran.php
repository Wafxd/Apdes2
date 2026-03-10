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

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

// ==================== FUNGSI FORMAT TANGGAL ====================
function tgl_indonesia($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    return $tgl . ' ' . $bln . ' ' . $thn;
}

// ==================== FUNGSI GET NOMOR SURAT ====================
function getNomorSuratBerikutnya($conn, $jenis_surat = 'SKL') {
    $tahun = date('Y');
    $query = "SELECT no_surat FROM arsip_surat WHERE jenis_surat = '$jenis_surat' AND YEAR(tanggal_surat) = '$tahun' ORDER BY id_surat DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $parts = explode(' / ', $row['no_surat']);
        if (count($parts) == 3) {
            $nomor = intval($parts[0]) + 1;
            return $nomor . ' / 474.1 / ' . $tahun;
        }
    }
    return '1 / 474.1 / ' . $tahun;
}

// ==================== AJAX: CARI BAYI (Untuk Sidebar) ====================
if (isset($_GET['search_bayi'])) {
    header('Content-Type: application/json');
    $keyword = mysqli_real_escape_string($conn, $_GET['search_bayi']);
    $query = "SELECT k.id_kelahiran, k.nik_bayi, k.nama_bayi, k.tempat_lahir, k.tanggal_lahir, k.nama_ayah, k.nama_ibu 
              FROM kelahiran k 
              WHERE k.nik_bayi LIKE '%$keyword%' OR k.nama_bayi LIKE '%$keyword%' LIMIT 10";
    $result = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit();
}

// ==================== AJAX: CARI DATA ORTU PENGISI SURAT ====================
if (isset($_GET['search_ortu'])) {
    header('Content-Type: application/json');
    $keyword = mysqli_real_escape_string($conn, $_GET['search_ortu']);
    $jk = mysqli_real_escape_string($conn, $_GET['jk']);
    
    $query = "SELECT nik, nama_penduduk, tempat_lahir, tanggal_lahir, pekerjaan, alamat, rt_rw, dusun 
              FROM penduduk 
              WHERE (nama_penduduk LIKE '%$keyword%' OR nik LIKE '%$keyword%') AND jenis_kelamin = '$jk' LIMIT 5";
    $result = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['tanggal_lahir_indo'] = tgl_indonesia($row['tanggal_lahir']);
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
    
    $query_bayi = "SELECT * FROM kelahiran WHERE nik_bayi = '$nik'";
    $result_bayi = mysqli_query($conn, $query_bayi);
    $bayi = mysqli_fetch_assoc($result_bayi);
    
    if ($bayi) {
        $tanggal_surat = date('Y-m-d');
        $jenis_surat = 'SKL'; 
        $nama_pemohon = $bayi['nama_bayi'];
        $query_insert = "INSERT INTO arsip_surat (no_surat, jenis_surat, tanggal_surat, nik, nama_pemohon, tempat_lahir, tanggal_lahir, keperluan, keterangan, created_by) 
                         VALUES ('$no_surat', '$jenis_surat', '$tanggal_surat', '$nik', '$nama_pemohon', '{$bayi['tempat_lahir']}', '{$bayi['tanggal_lahir']}', '$keperluan', 'Surat Keterangan Lahir', '{$_SESSION['id_admin']}')";
        
        if (mysqli_query($conn, $query_insert)) $_SESSION['success_message'] = "Surat berhasil disimpan dengan nomor: " . $no_surat;
        else $_SESSION['error_message'] = "Gagal menyimpan surat: " . mysqli_error($conn);
    } else {
        $_SESSION['error_message'] = "Data bayi tidak ditemukan!";
    }
    header("Location: kelahiran.php?nik=" . urlencode($nik));
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
        <title>Cetak Surat Kelahiran</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: <?php echo $font_fam; ?>; font-size: <?php echo $font_sz; ?>; background: white; color: #000; }
            @page { size: <?php echo $css_size; ?>; margin: 0; }
            .surat-container {
                padding-top: <?php echo $mt; ?>cm; padding-right: <?php echo $mr; ?>cm;
                padding-bottom: <?php echo $mb; ?>cm; padding-left: <?php echo $ml; ?>cm;
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

// ==================== AMBIL DATA BAYI ====================
$bayi = null;
$nomor_surat_otomatis = getNomorSuratBerikutnya($conn, 'SKL');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT k.*, p.alamat, p.dusun, p.rt_rw 
              FROM kelahiran k 
              LEFT JOIN penduduk p ON k.nik_bayi = p.nik 
              WHERE k.id_kelahiran = $id";
    $result = mysqli_query($conn, $query);
    $bayi = mysqli_fetch_assoc($result);
} elseif (isset($_GET['nik']) && !empty($_GET['nik'])) {
    $nik = mysqli_real_escape_string($conn, $_GET['nik']);
    $query = "SELECT k.*, p.alamat, p.dusun, p.rt_rw 
              FROM kelahiran k 
              LEFT JOIN penduduk p ON k.nik_bayi = p.nik 
              WHERE k.nik_bayi = '$nik'";
    $result = mysqli_query($conn, $query);
    $bayi = mysqli_fetch_assoc($result);
}

$tanggal_sekarang = tgl_indonesia(date('Y-m-d'));
$pageTitle = "Cetak Surat Keterangan Kelahiran";
ob_start();
?>

<style>
body { background-color: #f8f9fc; }
.card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); overflow: visible; }
.card-header.bg-success { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); border-bottom: none; border-radius: 1rem 1rem 0 0; }

.search-container { position: relative; width: 100%; z-index: 9999 !important; }
.search-results { position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 0.5rem; margin-top: 5px; max-height: 250px; overflow-y: auto; z-index: 99999 !important; display: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2); border: 1px solid #1cc88a; animation: fadeIn 0.2s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

.search-result-item { padding: 12px 20px; cursor: pointer; border-bottom: 1px solid #f8f9fc; transition: background 0.2s; display: flex; justify-content: space-between; align-items: center;}
.search-result-item:hover { background: #e8f5e9; border-left: 4px solid #1cc88a; }
.search-result-item strong { color: #1cc88a; font-size: 1.05rem;}
.search-result-item small { color: #858796; display: block; }

.data-card { background: white; border-left: 5px solid #1cc88a; padding: 25px; border-radius: 1rem; margin-bottom: 25px; box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.03); transition: transform 0.2s; position: relative; z-index: 1;}
.data-label { font-weight: 600; color: #858796; min-width: 130px; display: inline-block; }
.data-value { color: #3a3b45; font-weight: 500; }

#editorWrapper { position: relative; transition: all 0.3s ease; z-index: 10;}
#editorWrapper.fullscreen-mode { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 999999; background: #e2e8f0; padding: 20px; display: flex; flex-direction: column; overflow-y: auto; }
.editor-workspace { background-color: #eaecf4; padding: 40px 15px; display: flex; justify-content: center; border-radius: 0 0 1rem 1rem; overflow-x: auto; box-shadow: inset 0 3px 6px rgba(0,0,0,0.04); }
#editorWrapper.fullscreen-mode .editor-workspace { flex: 1; border-radius: 1rem; }

.editor-container { background: white; box-shadow: 0 15px 35px rgba(0,0,0,0.1); font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.4; color: #000; transition: all 0.3s ease; box-sizing: border-box; position: relative; }
.editor-container.paper-a4 { width: 210mm; min-height: 297mm; }
.editor-container.paper-f4 { width: 215.9mm; min-height: 330.2mm; }

[contenteditable="true"] { border-bottom: 1px dashed #1cc88a; min-width: 30px; display: inline-block; padding: 0 5px; outline: none; background: rgba(28, 200, 138, 0.05); border-radius: 3px; transition: background 0.2s; }
[contenteditable="true"]:focus { border-bottom: 2px solid #1cc88a; background: rgba(28, 200, 138, 0.15); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.text-uppercase { text-transform: uppercase; }

.toolbar { background: white; padding: 15px 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; border-bottom: 1px solid #e3e6f0; }
#editorWrapper.fullscreen-mode .toolbar { border-radius: 1rem; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.toolbar .input-group-text { font-size: 0.85rem; font-weight: 600; background-color: #f8f9fc; border-color: #d1d3e2; color: #1cc88a; }
.toolbar .form-control, .toolbar .form-select { font-size: 0.85rem; border-color: #d1d3e2; }
.toolbar-btn { background: #f8f9fc; border: 1px solid #d1d3e2; border-radius: 0.35rem; padding: 6px 14px; cursor: pointer; color: #5a5c69; transition: all 0.2s; font-size: 0.9rem; }
.toolbar-btn:hover { background: #1cc88a; color: white; border-color: #1cc88a; box-shadow: 0 2px 5px rgba(28,200,138,0.3); }
.toolbar-divider { height: 35px; width: 2px; background-color: #eaecf4; border-radius: 1px; margin: 0 2px; }

.action-table { width: 100%; border-collapse: separate; border-spacing: 8px; border: none; }
.btn-action { width: 100%; padding: 10px 15px; font-weight: 600; border-radius: 0.5rem; transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; align-items: center; justify-content: center; gap: 8px; border: none;}
.btn-action:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.15); opacity: 0.95; }
.btn-print { background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%); color: white; }
.btn-save { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); color: white; }

/* Panel Cari Ortu */
.ortu-panel { background: #e8f5e9; border: 1px dashed #1cc88a; padding: 10px 15px; border-radius: 8px; display: flex; gap: 15px; margin-bottom: 15px; align-items: center;}
.ortu-search-box { position: relative; flex: 1; }
.ortu-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d3e2; z-index: 100; max-height: 200px; overflow-y: auto; display: none; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-radius: 4px; }
</style>

<div class="container-fluid px-0">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Cetak Surat Kelahiran</h1>
            <p class="text-muted small mb-0">Format otomatis sesuai data bayi dan penduduk desa.</p>
        </div>
        <a href="../kelahiran.php" class="btn btn-sm btn-secondary shadow-sm rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Kembali ke Data Kelahiran</a>
    </div>

    <div class="card mb-4" style="z-index: 999 !important;">
        <div class="card-header bg-success text-white py-3">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-search me-2"></i>Pencarian Data Bayi (Arsip Kelahiran)</h6>
        </div>
        <div class="card-body bg-white">
            <form id="formCariBayi" method="GET" action="">
                <div class="row align-items-center">
                    <div class="col-md-10 mb-3 mb-md-0">
                        <div class="search-container">
                            <div class="input-group input-group-lg shadow-sm rounded-lg">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-baby text-success"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="search_input" 
                                       placeholder="Ketik NIK Bayi atau Nama Bayi..." 
                                       value="<?php echo isset($_GET['nik']) ? htmlspecialchars($_GET['nik']) : ''; ?>" 
                                       autocomplete="off">
                            </div>
                            <input type="hidden" name="nik" id="selected_nik" value="<?php echo isset($_GET['nik']) ? htmlspecialchars($_GET['nik']) : ''; ?>">
                            <div id="searchResults" class="search-results"></div>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end mt-2 mt-md-0">
                        <button type="submit" class="btn btn-success btn-lg w-100 shadow-sm rounded-lg"><i class="fas fa-search me-1"></i> Cari</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($bayi): ?>
    <div class="data-card">
        <h5 class="font-weight-bold text-success border-bottom pb-3 mb-4"><i class="fas fa-baby me-2"></i>Informasi Target Surat</h5>
        <div class="row text-gray-800">
            <div class="col-md-6">
                <div class="mb-2"><span class="data-label">NIK Bayi</span> <span class="me-2">:</span> <span class="data-value fs-6 font-weight-bold"><?php echo $bayi['nik_bayi']; ?></span></div>
                <div class="mb-2"><span class="data-label">Nama Bayi</span> <span class="me-2">:</span> <span class="data-value text-uppercase"><?php echo $bayi['nama_bayi']; ?></span></div>
                <div class="mb-2"><span class="data-label">TTL</span> <span class="me-2">:</span> <span class="data-value"><?php echo $bayi['tempat_lahir'] . ', ' . tgl_indonesia($bayi['tanggal_lahir']); ?></span></div>
                <div class="mb-2"><span class="data-label">Anak Ke</span> <span class="me-2">:</span> <span class="data-value"><?php echo $bayi['anak_ke']; ?></span></div>
            </div>
            <div class="col-md-6">
                <div class="mb-2"><span class="data-label">Jenis Kelamin</span> <span class="me-2">:</span> <span class="data-value"><?php echo $bayi['jenis_kelamin']; ?></span></div>
                <div class="mb-2"><span class="data-label">Nama Ayah</span> <span class="me-2">:</span> <span class="data-value text-uppercase"><?php echo $bayi['nama_ayah'] ?: '-'; ?></span></div>
                <div class="mb-2"><span class="data-label">Nama Ibu</span> <span class="me-2">:</span> <span class="data-value text-uppercase"><?php echo $bayi['nama_ibu'] ?: '-'; ?></span></div>
            </div>
        </div>
    </div>

    <form id="formBuatSurat" method="POST">
        <input type="hidden" name="nik" value="<?php echo $bayi['nik_bayi']; ?>">
        <input type="hidden" name="action" id="formAction" value="">
        <input type="hidden" name="paper_size" id="hiddenPaperSize" value="A4">
        <input type="hidden" name="jenis_ttd" id="hiddenJenisTtd" value="barcode"> <input type="hidden" name="margin_top" id="hiddenMarginTop" value="2">
        <input type="hidden" name="margin_bottom" id="hiddenMarginBottom" value="2">
        <input type="hidden" name="margin_left" id="hiddenMarginLeft" value="3">
        <input type="hidden" name="margin_right" id="hiddenMarginRight" value="2">
        <input type="hidden" name="font_family" id="hiddenFontFamily" value="'Times New Roman', Times, serif">
        <input type="hidden" name="font_size" id="hiddenFontSize" value="12pt">
        <input type="hidden" name="is_blanko" id="hiddenIsBlanko" value="false">
        
        <div class="card mb-4" id="editorWrapper">
            <div class="card-header bg-success py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-edit me-2"></i>Ruang Kerja Editor Surat</h6>
            </div>
            
            <div class="toolbar">
                <button type="button" class="toolbar-btn bg-light text-success border-success" onclick="toggleFullScreen()" id="btnFullscreen"><i class="fas fa-expand-arrows-alt"></i> Fullscreen</button>
                
                <div class="toolbar-divider"></div>
                
                <div class="input-group" style="width: auto;">
                    <span class="input-group-text"><i class="fas fa-file-alt"></i></span>
                    <select class="form-select font-weight-bold" id="paperSelect">
                        <option value="A4">A4</option><option value="F4">F4 (Folio)</option>
                    </select>
                </div>
                
                <div class="toolbar-divider"></div>
                
                <div class="input-group" style="width: auto;">
                    <span class="input-group-text"><i class="fas fa-border-style"></i> Margin</span>
                    <input type="number" id="inputMarginTop" class="form-control text-center px-1" value="2" step="0.5" style="width: 45px;" title="Atas">
                    <input type="number" id="inputMarginBottom" class="form-control text-center px-1" value="2" step="0.5" style="width: 45px;" title="Bawah">
                    <input type="number" id="inputMarginLeft" class="form-control text-center px-1" value="3" step="0.5" style="width: 45px;" title="Kiri">
                    <input type="number" id="inputMarginRight" class="form-control text-center px-1" value="2" step="0.5" style="width: 45px;" title="Kanan">
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
                    <label class="form-check-label font-weight-bold text-success" for="toggleBlanko">Kertas Kop</label>
                </div>
                
                <div class="toolbar-divider"></div>
                
                <div class="btn-group" role="group">
                    <button type="button" class="toolbar-btn rounded-start" onclick="document.execCommand('bold', false, null)"><i class="fas fa-bold"></i></button>
                    <button type="button" class="toolbar-btn border-start-0 border-end-0" onclick="document.execCommand('italic', false, null)"><i class="fas fa-italic"></i></button>
                    <button type="button" class="toolbar-btn rounded-end" onclick="document.execCommand('underline', false, null)"><i class="fas fa-underline"></i></button>
                </div>
            </div>

            <div class="bg-white px-4 pt-3 pb-2 border-bottom">
                <p class="text-success font-weight-bold small mb-2"><i class="fas fa-magic"></i> Penarik Data Orang Tua Otomatis (Live Search Penduduk)</p>
                <div class="ortu-panel">
                    <div class="ortu-search-box">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-female text-danger"></i></span>
                            <input type="text" class="form-control" id="cari_ibu" placeholder="Cari Data IBU..." autocomplete="off">
                        </div>
                        <div id="drop_ibu" class="ortu-dropdown"></div>
                    </div>
                    <div class="ortu-search-box">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-male text-primary"></i></span>
                            <input type="text" class="form-control" id="cari_ayah" placeholder="Cari Data AYAH..." autocomplete="off">
                        </div>
                        <div id="drop_ayah" class="ortu-dropdown"></div>
                    </div>
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
                    
                    <div style="color: #000;">
                        <p style="text-align: justify; margin-bottom: 10px;">Yang bertanda tangan di bawah ini, Kepala Desa Sukolilo Timur Kecamatan Labang, dengan ini menerangkan kepada :</p>
                        
                        <table style="width: 100%; border-collapse: collapse; color: #000; font-size: inherit;">
                            <tr>
                                <td style="width: 180px; padding: 2px 0;">Nama</td><td style="width: 20px; padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" class="text-uppercase font-weight-bold"><?php echo $bayi['nama_bayi']; ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">Jenis Kelamin</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true"><?php echo $bayi['jenis_kelamin'] == 'LAKI-LAKI' ? 'Laki-Laki' : 'Perempuan'; ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">NIK</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true"><?php echo $bayi['nik_bayi']; ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">Tempat/Tgl lahir</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true"><?php echo $bayi['tempat_lahir'] . ', ' . tgl_indonesia($bayi['tanggal_lahir']); ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">Alamat</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" class="text-uppercase"><?php echo ($bayi['alamat'] ?? 'Dsn. ') . ($bayi['rt_rw'] ? ' RT/RW ' . $bayi['rt_rw'] : '') . ' Ds. Sukolilo Timur'; ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">Anak Ke</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true"><?php echo $bayi['anak_ke']; ?></span></td>
                            </tr>
                        </table>

                        <br>
                        
                        <table style="width: 100%; border-collapse: collapse; color: #000; font-size: inherit;">
                            <tr>
                                <td style="width: 180px; padding: 2px 0; padding-left: 30px;">Dari seorang Ibu</td><td style="width: 20px; padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">Nama</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" class="text-uppercase" id="sp_ibu_nama"><?php echo $bayi['nama_ibu']; ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">Tempat/Tanggal Lahir</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" id="sp_ibu_ttl">...................................................</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">NIK</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" id="sp_ibu_nik">...................................................</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">Pekerjaan</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" class="text-uppercase" id="sp_ibu_pekerjaan">MENGURUS RUMAH TANGGA</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0; vertical-align: top;">Alamat</td><td style="padding: 2px 0; vertical-align: top;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" class="text-uppercase" id="sp_ibu_alamat"><?php echo ($bayi['alamat'] ?? 'Dsn. ') . ' Ds. Sukolilo Timur'; ?></span></td>
                            </tr>
                        </table>

                        <br>

                        <table style="width: 100%; border-collapse: collapse; color: #000; font-size: inherit;">
                            <tr>
                                <td style="width: 180px; padding: 2px 0; padding-left: 30px;">Istri dari</td><td style="width: 20px; padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">Nama</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" class="text-uppercase" id="sp_ayah_nama"><?php echo $bayi['nama_ayah']; ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">Tempat/Tanggal Lahir</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" id="sp_ayah_ttl">...................................................</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">NIK</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" id="sp_ayah_nik">...................................................</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0;">Pekerjaan</td><td style="padding: 2px 0;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" class="text-uppercase" id="sp_ayah_pekerjaan">...................................................</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0; vertical-align: top;">Alamat</td><td style="padding: 2px 0; vertical-align: top;">:</td>
                                <td style="padding: 2px 0;"><span contenteditable="true" class="text-uppercase" id="sp_ayah_alamat"><?php echo ($bayi['alamat'] ?? 'Dsn. ') . ' Ds. Sukolilo Timur'; ?></span></td>
                            </tr>
                        </table>

                        <br>
                        <p style="text-align: justify;">Demikian Surat Keterangan Lahir ini dibuat dengan benar untuk digunakan perlunya.</p>
                    </div>
                    
                    <table style="width: 100%; border: none; margin-top: 40px; color: #000; font-size: inherit;">
                        <tr>
                            <td style="width: 55%; border: none;"></td>
                            <td style="width: 45%; border: none; text-align: center; color: #000; vertical-align: top;">
                                Sukolilo Timur, <span contenteditable="true"><?php echo $tanggal_sekarang; ?></span><br>
                                Kepala Desa Sukolilo Timur<br><br><br> 
                                <div id="ttd_area" style="text-align: center;">
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
                        <h6 class="font-weight-bold text-success mb-3"><i class="fas fa-cog me-2"></i>Pengaturan Simpan</h6>
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label small font-weight-bold text-gray-600 mb-1">Nomor Arsip Surat Kelahiran</label>
                                <input type="text" class="form-control" id="no_surat" name="no_surat" value="<?php echo $nomor_surat_otomatis; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7 ps-lg-4">
                        <table class="action-table">
                            <tr>
                                <td style="width: 33%;">
                                    <button type="button" class="btn-action btn-print" onclick="cetakSurat()"><i class="fas fa-print"></i> Cetak Kertas</button>
                                </td>
                                <td style="width: 33%;">
                                    <button type="button" class="btn-action" style="background:#e74a3b; color:white;" onclick="alert('Download PDF sedang disiapkan')"><i class="fas fa-file-pdf"></i> Unduh PDF</button>
                                </td>
                                <td style="width: 33%;">
                                    <button type="button" class="btn-action" style="background:#4e73df; color:white;" onclick="alert('Download Word sedang disiapkan')"><i class="fas fa-file-word"></i> Unduh Word</button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding-top: 5px;">
                                    <button type="button" class="btn-action btn-save shadow-lg" onclick="simpanSurat()" style="font-size: 1.05rem;"><i class="fas fa-save"></i> Simpan Arsip Surat</button>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<div class="modal fade" id="confirmSaveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-gradient-success text-white py-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-save me-2"></i>Konfirmasi Arsip</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-5">
                <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-4 shadow" style="width: 80px; height: 80px;">
                    <i class="fas fa-file-alt fa-3x"></i>
                </div>
                <h4 class="text-gray-800 font-weight-bold mb-3">Simpan Surat ke Arsip?</h4>
                <div class="alert alert-light border text-start shadow-sm mx-auto" style="max-width: 350px;">
                    <div class="mb-2 border-bottom pb-2"><strong>Nomor Surat:</strong> <br><span id="confirmNoSurat" class="text-success fs-5"></span></div>
                    <div><strong>Pemohon:</strong> <br><span class="text-dark font-weight-bold"><?php echo $bayi['nama_bayi'] ?? ''; ?></span></div>
                </div>
            </div>
            <div class="modal-footer bg-light py-3 d-flex justify-content-center">
                <button type="button" class="btn btn-light border px-4 rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success px-5 rounded-pill shadow-sm" id="btnConfirmSave"><i class="fas fa-check me-1"></i> Ya, Simpan Sekarang</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== JS TTD BASAH (Baru Ditambahkan) =====
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

// ===== AUTO-CAPITALIZE =====
document.querySelectorAll('.text-uppercase[contenteditable="true"]').forEach(el => {
    el.addEventListener('input', function() {
        let sel = window.getSelection(); let range = sel.getRangeAt(0); let pos = range.startOffset;
        this.textContent = this.textContent.toUpperCase();
        if(this.childNodes.length > 0) {
            let textNode = this.childNodes[0];
            let newRange = document.createRange();
            if(pos > textNode.length) pos = textNode.length;
            newRange.setStart(textNode, pos); newRange.collapse(true);
            sel.removeAllRanges(); sel.addRange(newRange);
        }
    });
});

// ===== LIVE SEARCH BAYI =====
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
            fetch('kelahiran.php?search_bayi=' + encodeURIComponent(keyword))
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        let html = '';
                        data.forEach(item => {
                            html += `<div class="search-result-item" onclick="pilihBayi('${item.nik_bayi}', '${item.nama_bayi}')">
                                        <div><strong>${item.nama_bayi}</strong><small>NIK: ${item.nik_bayi}</small></div>
                                     </div>`;
                        });
                        searchResults.innerHTML = html;
                        searchResults.style.display = 'block';
                    } else { searchResults.innerHTML = '<div class="p-3 text-center text-muted">Tidak ada data bayi</div>'; searchResults.style.display = 'block'; }
                });
        }, 300);
    });
    document.addEventListener('click', e => { if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) searchResults.style.display = 'none'; });
}
function pilihBayi(nik, nama) { selectedNik.value = nik; document.getElementById('formCariBayi').submit(); }

// ===== LIVE SEARCH ORANG TUA (PENGISI SURAT) =====
function setupOrtuSearch(inputId, dropId, jk, prefixSpan) {
    const input = document.getElementById(inputId); const drop = document.getElementById(dropId);
    let to;
    if(!input) return;
    input.addEventListener('input', function() {
        clearTimeout(to); const keyword = this.value.trim();
        if (keyword.length < 2) { drop.style.display = 'none'; return; }
        to = setTimeout(() => {
            fetch(`kelahiran.php?search_ortu=${encodeURIComponent(keyword)}&jk=${jk}`)
            .then(res => res.json()).then(data => {
                if (data.length > 0) {
                    let html = '';
                    data.forEach(item => {
                        html += `<div class="p-2 border-bottom" style="cursor:pointer;" onclick='isiDataOrtu(${JSON.stringify(item)}, "${prefixSpan}")'>
                                    <strong>${item.nama_penduduk}</strong><br><small class="text-muted">${item.nik}</small>
                                 </div>`;
                    });
                    drop.innerHTML = html; drop.style.display = 'block';
                } else { drop.innerHTML = '<div class="p-2 text-muted small">Tidak ditemukan</div>'; drop.style.display = 'block'; }
            });
        }, 300);
    });
    document.addEventListener('click', e => { if (!input.contains(e.target) && !drop.contains(e.target)) drop.style.display = 'none'; });
}

function isiDataOrtu(item, prefix) {
    document.getElementById(`sp_${prefix}_nama`).innerText = item.nama_penduduk.toUpperCase();
    document.getElementById(`sp_${prefix}_ttl`).innerText = (item.tempat_lahir + ', ' + item.tanggal_lahir_indo).toUpperCase();
    document.getElementById(`sp_${prefix}_nik`).innerText = item.nik;
    document.getElementById(`sp_${prefix}_pekerjaan`).innerText = (item.pekerjaan || '-').toUpperCase();
    
    let alamat = (item.alamat || '') + ' RT/RW ' + (item.rt_rw || '') + ' DSN. ' + (item.dusun || '');
    document.getElementById(`sp_${prefix}_alamat`).innerText = alamat.toUpperCase();
    
    document.getElementById(`drop_${prefix}`).style.display = 'none';
    document.getElementById(`cari_${prefix}`).value = item.nama_penduduk; // Feedback visual
}

setupOrtuSearch('cari_ibu', 'drop_ibu', 'PEREMPUAN', 'ibu');
setupOrtuSearch('cari_ayah', 'drop_ayah', 'LAKI-LAKI', 'ayah');

// ===== TOOLBAR EDITOR =====
const editor = document.getElementById('editor');
document.getElementById('paperSelect')?.addEventListener('change', function() {
    document.getElementById('hiddenPaperSize').value = this.value;
    if (this.value === 'F4') { editor.classList.remove('paper-a4'); editor.classList.add('paper-f4'); } 
    else { editor.classList.remove('paper-f4'); editor.classList.add('paper-a4'); }
});
document.getElementById('toggleBlanko')?.addEventListener('change', function() {
    document.getElementById('hiddenIsBlanko').value = this.checked;
    document.getElementById('kopSuratArea').style.display = this.checked ? 'none' : 'table';
});

function applyMargins() {
    let t = document.getElementById('inputMarginTop').value || 0; let b = document.getElementById('inputMarginBottom').value || 0;
    let l = document.getElementById('inputMarginLeft').value || 0; let r = document.getElementById('inputMarginRight').value || 0;
    editor.style.padding = `${t}cm ${r}cm ${b}cm ${l}cm`;
}
['Top','Bottom','Left','Right'].forEach(dir => document.getElementById(`inputMargin${dir}`)?.addEventListener('input', applyMargins));

function toggleFullScreen() {
    const wrap = document.getElementById('editorWrapper');
    wrap.classList.toggle('fullscreen-mode');
    document.body.style.overflow = wrap.classList.contains('fullscreen-mode') ? 'hidden' : '';
}

// ===== CETAK & SIMPAN =====
function getCleanEditorContent() {
    const clone = editor.cloneNode(true);
    if(document.getElementById('toggleBlanko') && document.getElementById('toggleBlanko').checked) {
        const kop = clone.querySelector('#kopSuratArea'); if(kop) kop.remove();
    }
    clone.querySelectorAll('[contenteditable="true"]').forEach(el => {
        el.removeAttribute('contenteditable'); el.style.borderBottom = 'none'; el.style.background = 'none'; el.style.boxShadow = 'none';
    });
    return clone.innerHTML;
}

function submitFormWithAction(action, blank = false) {
    const form = document.getElementById('formBuatSurat');
    document.getElementById('formAction').value = action;
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden'; hiddenInput.name = 'konten_surat'; hiddenInput.value = getCleanEditorContent();
    form.appendChild(hiddenInput);
    form.target = blank ? '_blank' : '';
    form.submit();
    form.removeChild(hiddenInput); document.getElementById('formAction').value = '';
}

function cetakSurat() { submitFormWithAction('cetak', true); }

let confirmModal = null;
function simpanSurat() {
    document.getElementById('confirmNoSurat').textContent = document.getElementById('no_surat').value;
    if(!confirmModal) confirmModal = new bootstrap.Modal(document.getElementById('confirmSaveModal'));
    confirmModal.show();
}
document.getElementById('btnConfirmSave')?.addEventListener('click', function() { submitFormWithAction('simpan_surat'); });
</script>

<?php
$content = ob_get_clean();
include '../../includes/base.php';
?>