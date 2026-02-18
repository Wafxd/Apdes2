<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

include "db/funct.php";
include "db/koneksi.php";

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
    $nik = $_POST['nik'] ?? '';
    
    // Ambil data penduduk untuk ditampilkan di cetakan
    $penduduk = null;
    if (!empty($nik)) {
        $result = mysqli_query($conn, "SELECT * FROM penduduk WHERE nik = '$nik'");
        $penduduk = mysqli_fetch_assoc($result);
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Cetak Surat</title>
        <style>
            body {
                font-family: 'Times New Roman', Times, serif;
                font-size: 12pt;
                line-height: 1.5;
                margin: 2cm;
            }
            .kop-surat {
                text-align: center;
                margin-bottom: 20px;
                position: relative;
            }
            .logo-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100px;
                height: 100px;
            }
            .logo-container img {
                max-width: 100%;
                max-height: 100%;
            }
            .kop-text {
                margin-left: 120px;
            }
            .kop-surat h4 {
                margin: 0;
                padding: 0;
                font-weight: bold;
                font-size: 14pt;
            }
            .kop-surat p {
                margin: 0;
            }
            table {
                border-collapse: collapse;
                width: 100%;
            }
            .table-borderless td {
                border: none;
                padding: 3px 5px;
            }
            .table-bordered {
                border: 1px solid #000;
            }
            .table-bordered th,
            .table-bordered td {
                border: 1px solid #000;
                padding: 5px;
            }
            .ttd-area {
                margin-top: 100px;
            }
            .underline {
                text-decoration: underline;
            }
            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
            .font-weight-bold {
                font-weight: bold;
            }
            @media print {
                body { margin: 1.5cm; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <?php echo $konten; ?>
        
        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" class="btn btn-primary">Cetak</button>
            <button onclick="window.close()">Tutup</button>
        </div>
        
        <script>
            window.onload = function() {
                // Auto print jika diperlukan
                // window.print();
            }
        </script>
    </body>
    </html>
    <?php
    exit();
}

$pageTitle = "Pembuatan Surat Keterangan";
$pageHeaderButton = '<a href="surat.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-file-alt fa-sm text-white-50"></i> Buat Surat Baru
</a>';

ob_start();
?>

<style>
/* ===== STYLE UNTUK SURAT ===== */
.editor-container {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    min-height: 800px;
    padding: 30px;
    background: white;
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    line-height: 1.5;
    overflow-y: auto;
}

.toolbar {
    background: #f8f9fc;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem 0.35rem 0 0;
    padding: 10px;
    margin-bottom: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.toolbar button, .toolbar select {
    margin: 0;
}

.kop-surat {
    text-align: center;
    margin-bottom: 25px;
    position: relative;
    font-family: 'Times New Roman', Times, serif;
}

.logo-container {
    position: absolute;
    left: 0;
    top: 0;
    width: 100px;
    height: 100px;
}

.logo-container img {
    max-width: 100%;
    max-height: 100%;
}

.kop-text {
    margin-left: 120px;
}

.kop-surat h4 {
    margin: 0;
    padding: 0;
    font-weight: bold;
    font-size: 14pt;
}

.kop-surat p {
    margin: 0;
    font-size: 12pt;
}

.table-borderless td {
    border: none;
    padding: 3px 5px;
}

.ttd-area {
    margin-top: 100px;
}

.underline {
    text-decoration: underline;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

.font-weight-bold {
    font-weight: bold;
}

.surat-body {
    background: white;
    padding: 20px;
    border: 1px solid #d1d3e2;
    border-top: none;
    border-radius: 0 0 0.35rem 0.35rem;
}

.data-penduduk-card {
    background: #f8f9fc;
    border-left: 4px solid #4e73df;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 0.35rem;
}

.data-penduduk-label {
    font-weight: bold;
    color: #4e73df;
    min-width: 120px;
    display: inline-block;
}

.badge-surat {
    background: #4e73df;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 14px;
}

/* Live search */
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

/* Field editable */
[contenteditable="true"] {
    border-bottom: 1px dashed #4e73df;
    min-width: 100px;
    display: inline-block;
    padding: 2px 5px;
    outline: none;
}

[contenteditable="true"]:focus {
    border-bottom: 2px solid #4e73df;
    background: #f8f9fc;
}

/* Modal preview */
.modal-preview {
    max-width: 1000px;
}

.modal-preview .modal-body {
    max-height: 600px;
    overflow-y: auto;
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    line-height: 1.5;
    padding: 30px;
}
</style>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Pembuatan Surat Keterangan</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Buat Surat</li>
        </ol>
    </div>

    <!-- Form Pencarian dengan Live Search -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-search me-2"></i>Cari Data Penduduk
            </h6>
        </div>
        <div class="card-body">
            <form id="formCariPenduduk" method="GET" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Jenis Surat</label>
                        <select class="form-select" name="jenis_surat" id="jenis_surat" required>
                            <option value="">-- Pilih Jenis Surat --</option>
                            <option value="domisili" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'domisili') ? 'selected' : ''; ?>>Surat Keterangan Domisili</option>
                            <option value="usaha" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'usaha') ? 'selected' : ''; ?>>Surat Keterangan Usaha</option>
                            <option value="nikah_sirih" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'nikah_sirih') ? 'selected' : ''; ?>>Surat Keterangan Nikah Sirih</option>
                            <option value="kehilangan" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'kehilangan') ? 'selected' : ''; ?>>Surat Keterangan Kehilangan</option>
                            <option value="skck" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'skck') ? 'selected' : ''; ?>>Surat Keterangan Catatan Kepolisian (SKCK)</option>
                            <option value="kuasa" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'kuasa') ? 'selected' : ''; ?>>Surat Kuasa</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3 position-relative">
                        <label class="form-label fw-bold">NIK / Nama Penduduk</label>
                        <input type="text" class="form-control" id="search_input" 
                               placeholder="Ketik NIK atau nama penduduk..." 
                               value="<?php echo isset($_GET['nik']) ? htmlspecialchars($_GET['nik']) : ''; ?>" 
                               autocomplete="off">
                        <input type="hidden" name="nik" id="selected_nik" value="<?php echo isset($_GET['nik']) ? htmlspecialchars($_GET['nik']) : ''; ?>">
                        <input type="hidden" name="nama" id="selected_nama" value="">
                        
                        <!-- Live search results -->
                        <div id="searchResults" class="search-results"></div>
                        
                        <small class="text-muted">Ketik untuk mencari - klik hasil yang muncul</small>
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" id="btnCari">
                            <i class="fas fa-search"></i> Cari
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Ambil data penduduk jika ada NIK di URL
    $penduduk = null;
    if (isset($_GET['nik']) && !empty($_GET['nik'])) {
        $nik = mysqli_real_escape_string($conn, $_GET['nik']);
        $result = mysqli_query($conn, "SELECT * FROM penduduk WHERE nik = '$nik'");
        $penduduk = mysqli_fetch_assoc($result);
    }
    ?>

    <?php if (isset($_GET['nik']) && !empty($_GET['nik'])): ?>
        <?php if ($penduduk): ?>
            <!-- Data Penduduk Ditemukan -->
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Data Penduduk Ditemukan!</h5>
                        <span>NIK: <strong><?php echo $penduduk['nik']; ?></strong> | Nama: <strong><?php echo $penduduk['nama_penduduk']; ?></strong></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <!-- Data Penduduk Card -->
            <div class="data-penduduk-card">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2"><span class="data-penduduk-label">NIK</span> : <strong><?php echo $penduduk['nik']; ?></strong></div>
                        <div class="mb-2"><span class="data-penduduk-label">Nama Lengkap</span> : <strong><?php echo $penduduk['nama_penduduk']; ?></strong></div>
                        <div class="mb-2"><span class="data-penduduk-label">Tempat Lahir</span> : <?php echo $penduduk['tempat_lahir']; ?></div>
                        <div class="mb-2"><span class="data-penduduk-label">Tanggal Lahir</span> : <?php echo date('d-m-Y', strtotime($penduduk['tanggal_lahir'])); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2"><span class="data-penduduk-label">Jenis Kelamin</span> : <?php echo $penduduk['jenis_kelamin']; ?></div>
                        <div class="mb-2"><span class="data-penduduk-label">Agama</span> : <?php echo $penduduk['agama']; ?></div>
                        <div class="mb-2"><span class="data-penduduk-label">Pekerjaan</span> : <?php echo $penduduk['pekerjaan'] ?: '-'; ?></div>
                        <div class="mb-2"><span class="data-penduduk-label">Alamat</span> : <?php echo $penduduk['alamat']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Editor Surat -->
            <form id="formBuatSurat" method="POST">
                <input type="hidden" name="nik" value="<?php echo $penduduk['nik']; ?>">
                <input type="hidden" name="action" value="cetak">
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-edit me-2"></i>Editor Surat
                            <?php if (isset($_GET['jenis_surat'])): ?>
                            - <span class="badge bg-light text-dark"><?php echo strtoupper(str_replace('_', ' ', $_GET['jenis_surat'])); ?></span>
                            <?php endif; ?>
                        </h6>
                        <div>
                            <span class="badge bg-light text-dark me-2">
                                <i class="fas fa-calendar"></i> <?php echo date('d-m-Y'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Toolbar -->
                        <div class="toolbar">
                            <select class="form-select d-inline-block w-auto" id="fontSize" style="width: auto;">
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
                            
                            <button type="button" class="btn btn-sm btn-light" onclick="document.execCommand('insertUnorderedList', false, null)" title="Bullet">
                                <i class="fas fa-list-ul"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light" onclick="document.execCommand('insertOrderedList', false, null)" title="Number">
                                <i class="fas fa-list-ol"></i>
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
                                <?php
                                $jenis_surat = isset($_GET['jenis_surat']) ? $_GET['jenis_surat'] : '';
                                
                                // Format tanggal Indonesia
                                $bulan_indonesia = array(
                                    'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
                                    'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
                                    'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
                                    'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
                                );
                                $bulan_ini = $bulan_indonesia[date('F')];
                                $tanggal_indonesia = date('d') . ' ' . $bulan_ini . ' ' . date('Y');
                                
                                // Cek apakah ada file logo
                                $logo_path = '';
                                if (file_exists('uploads/logo_desa.png')) {
                                    $logo_path = 'uploads/logo_desa.png';
                                } elseif (file_exists('assets/img/logo_desa.png')) {
                                    $logo_path = 'assets/img/logo_desa.png';
                                } elseif (file_exists('img/logo_desa.png')) {
                                    $logo_path = 'img/logo_desa.png';
                                }
                                
                                if ($jenis_surat == 'domisili'):
                                ?>
                                <div class="kop-surat">
                                    <?php if ($logo_path): ?>
                                    <div class="logo-container">
                                        <img src="<?php echo $logo_path; ?>" alt="Logo Desa">
                                    </div>
                                    <div class="kop-text">
                                    <?php endif; ?>
                                        <h4>PEMERINTAH KABUPATEN BANGKALAN</h4>
                                        <h4>KECAMATAN LABANG</h4>
                                        <h4>KANTOR DESA SUKOLILO TIMUR</h4>
                                        <p>Labang 69163</p>
                                    <?php if ($logo_path): ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <h4 style="text-decoration: underline; font-weight: bold;">SURAT KETERANGAN DOMISILI</h4>
                                    <p>NO : <span id="nomor_surat_text" contenteditable="true">474/ /433.411.05/<?php echo date('Y'); ?></span></p>
                                </div>
                                
                                <p>Yang bertanda tangan di bawah ini :</p>
                                <table class="table-borderless" style="width: 100%; margin-left: 20px;">
                                    <tr>
                                        <td style="width: 120px;">Nama</td>
                                        <td style="width: 20px;">:</td>
                                        <td>H. ZAINAL ABIDIN</td>
                                    </tr>
                                    <tr>
                                        <td>Jabatan</td>
                                        <td>:</td>
                                        <td>Kepala Desa Sukolilo Timur</td>
                                    </tr>
                                    <tr>
                                        <td>Kecamatan</td>
                                        <td>:</td>
                                        <td>Labang</td>
                                    </tr>
                                    <tr>
                                        <td>Kabupaten</td>
                                        <td>:</td>
                                        <td>Bangkalan</td>
                                    </tr>
                                </table>
                                
                                <p style="margin-top: 20px;">Menerangkan bahwa nama-nama yang tersebut dibawah ini :</p>
                                
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                    <thead>
                                        <tr style="background-color: #f2f2f2;">
                                            <th style="border: 1px solid #000; padding: 8px; text-align: center;">NO</th>
                                            <th style="border: 1px solid #000; padding: 8px; text-align: center;">NAMA</th>
                                            <th style="border: 1px solid #000; padding: 8px; text-align: center;">ALAMAT</th>
                                            <th style="border: 1px solid #000; padding: 8px; text-align: center;">KET</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="border: 1px solid #000; padding: 8px; text-align: center;">1.</td>
                                            <td style="border: 1px solid #000; padding: 8px;"><?php echo $penduduk['nama_penduduk']; ?></td>
                                            <td style="border: 1px solid #000; padding: 8px;"><?php echo $penduduk['alamat']; ?></td>
                                            <td style="border: 1px solid #000; padding: 8px;" contenteditable="true">KAYA (MAMPU)</td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <p>Demikian surat keterangan ini kami buat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
                                
                                <div class="ttd-area">
                                    <div style="display: flex; justify-content: flex-end;">
                                        <div style="text-align: center;">
                                            <p>Sukolilo Timur, <?php echo $tanggal_indonesia; ?></p>
                                            <p>Kepala Desa Sukolilo Timur</p>
                                            <br><br><br>
                                            <p style="text-decoration: underline; font-weight: bold;">H. ZAINAL ABIDIN</p>
                                        </div>
                                    </div>
                                </div>

                                <?php elseif ($jenis_surat == 'usaha'): ?>
                                <div class="kop-surat">
                                    <?php if ($logo_path): ?>
                                    <div class="logo-container">
                                        <img src="<?php echo $logo_path; ?>" alt="Logo Desa">
                                    </div>
                                    <div class="kop-text">
                                    <?php endif; ?>
                                        <h4>PEMERINTAH KABUPATEN BANGKALAN</h4>
                                        <h4>KECAMATAN LABANG</h4>
                                        <h4>KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                                        <p>Labang 69163</p>
                                    <?php if ($logo_path): ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <h4 style="text-decoration: underline; font-weight: bold;">SURAT KETERANGAN MEMILIKI USAHA</h4>
                                    <p>Nomor: <span contenteditable="true" id="nomor_surat_text">474/ /433.411.08/<?php echo date('Y'); ?></span></p>
                                </div>
                                
                                <p>Yang bertanda tangan di bawah ini kami Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:</p>
                                
                                <table class="table-borderless" style="width: 100%; margin-left: 20px;">
                                    <tr>
                                        <td style="width: 140px;">Nama</td>
                                        <td style="width: 20px;">:</td>
                                        <td><?php echo $penduduk['nama_penduduk']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>NIK</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['nik']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tempat, Tgl Lahir</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($penduduk['tanggal_lahir'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Jenis Kelamin</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['jenis_kelamin']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Agama</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['agama']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Pekerjaan</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['pekerjaan'] ?: '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Alamat</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['alamat']; ?></td>
                                    </tr>
                                </table>
                                
                                <p style="margin-top: 20px;">Orang tersebut diatas benar-benar mempunyai usaha <span contenteditable="true">Toko Klontong dan Bengkel Motor</span> yang berlokasi di <?php echo $penduduk['alamat']; ?>.</p>
                                
                                <p>Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
                                
                                <div class="ttd-area">
                                    <div style="display: flex; justify-content: flex-end;">
                                        <div style="text-align: center;">
                                            <p>Sukolilo Timur, <?php echo $tanggal_indonesia; ?></p>
                                            <p>Kepala Desa Sukolilo Timur</p>
                                            <br><br><br>
                                            <p style="text-decoration: underline; font-weight: bold;">H. ZAINAL ABIDIN</p>
                                        </div>
                                    </div>
                                </div>

                                <?php elseif ($jenis_surat == 'skck'): ?>
                                <div class="kop-surat">
                                    <?php if ($logo_path): ?>
                                    <div class="logo-container">
                                        <img src="<?php echo $logo_path; ?>" alt="Logo Desa">
                                    </div>
                                    <div class="kop-text">
                                    <?php endif; ?>
                                        <h4>PEMERINTAH KABUPATEN BANGKALAN</h4>
                                        <h4>KECAMATAN LABANG</h4>
                                        <h4>KANTOR DESA SUKOLILO TIMUR</h4>
                                        <p>Labang 69163</p>
                                    <?php if ($logo_path): ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <h4 style="text-decoration: underline; font-weight: bold;">SURAT KETERANGAN CATATAN KEPOLISIAN</h4>
                                    <p>No. <span contenteditable="true" id="nomor_surat_text">474/ /433.312.05/<?php echo date('Y'); ?></span></p>
                                </div>
                                
                                <p>Yang bertanda tangan di bawah ini kami Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa :</p>
                                
                                <table class="table-borderless" style="width: 100%; margin-left: 20px;">
                                    <tr>
                                        <td style="width: 140px;">Nama</td>
                                        <td style="width: 20px;">:</td>
                                        <td><?php echo $penduduk['nama_penduduk']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tempat/Tgl.lahir</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($penduduk['tanggal_lahir'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td>NIK</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['nik']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Jenis Kelamin</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['jenis_kelamin'] == 'LAKI-LAKI' ? 'Laki-laki' : 'Perempuan'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Pekerjaan</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['pekerjaan'] ?: '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Status</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['status_kawin']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Agama</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['agama']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Alamat</td>
                                        <td>:</td>
                                        <td><?php echo $penduduk['alamat']; ?></td>
                                    </tr>
                                </table>
                                
                                <p style="margin-top: 20px;">Sepanjang pengetahuan dan selama berada di Desa kami serta beralamat seperti diatas, orang tersebut berkelakuan baik dan belum pernah tersangkut perkara polisi.</p>
                                
                                <p>Surat Keterangan ini diberikan kepada yang bersangkutan untuk <span contenteditable="true">keperluan melamar pekerjaan</span>.</p>
                                
                                <p>Demikian untuk menjadi maklum.</p>
                                
                                <div class="ttd-area">
                                    <div style="display: flex; justify-content: space-between;">
                                        <div style="text-align: center; width: 40%;">
                                            <p>Mengetahui,</p>
                                            <p>CAMAT LABANG</p>
                                            <br><br><br>
                                            <p contenteditable="true" style="text-decoration: underline;">(.............................)</p>
                                        </div>
                                        <div style="text-align: center; width: 40%;">
                                            <p>Sukolilo Timur, <?php echo $tanggal_indonesia; ?></p>
                                            <p>Kepala Desa Sukolilo Timur</p>
                                            <br><br><br>
                                            <p style="text-decoration: underline; font-weight: bold;">H. ZAINAL ABIDIN</p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-0">
                                    <label for="no_surat">Nomor Surat</label>
                                    <input type="text" class="form-control" id="no_surat" name="no_surat" 
                                           value="<?php 
                                               if ($jenis_surat == 'domisili') echo '474/ /433.411.05/'.date('Y');
                                               elseif ($jenis_surat == 'usaha') echo '474/ /433.411.08/'.date('Y');
                                               elseif ($jenis_surat == 'skck') echo '474/ /433.312.05/'.date('Y');
                                               else echo '123/SK/'.date('Y');
                                           ?>">
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <br>
                                <button type="button" class="btn btn-info" onclick="previewSurat()">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button type="submit" class="btn btn-primary" id="btnCetak">
                                    <i class="fas fa-print"></i> Cetak
                                </button>
                                <a href="surat.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        <?php else: ?>
            <!-- Data Tidak Ditemukan -->
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                Data penduduk dengan NIK/Nama "<strong><?php echo htmlspecialchars($_GET['nik']); ?></strong>" tidak ditemukan.
                <a href="penduduk.php" class="alert-link">Tambah data penduduk?</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal Preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-preview">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Preview Surat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
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
const selectedNama = document.getElementById('selected_nama');
const btnCari = document.getElementById('btnCari');

let searchTimeout;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const keyword = this.value.trim();
    
    if (keyword.length < 2) {
        searchResults.style.display = 'none';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch('surat.php?search_penduduk=' + encodeURIComponent(keyword))
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

function pilihPenduduk(nik, nama) {
    searchInput.value = nama + ' (' + nik + ')';
    selectedNik.value = nik;
    selectedNama.value = nama;
    searchResults.style.display = 'none';
    
    // Submit form otomatis
    document.getElementById('formCariPenduduk').submit();
}

// Tutup search results saat klik di luar
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.style.display = 'none';
    }
});

// ===== EDITOR FUNCTIONS =====
document.getElementById('fontSize')?.addEventListener('change', function() {
    document.execCommand('fontSize', false, this.value);
});

// Update nomor surat text
document.getElementById('no_surat')?.addEventListener('input', function() {
    const nomorText = document.getElementById('nomor_surat_text');
    if (nomorText) nomorText.textContent = this.value;
});

// ===== PREVIEW FUNCTION =====
function previewSurat() {
    const editor = document.getElementById('editor');
    const preview = document.getElementById('previewContent');
    
    preview.innerHTML = editor.innerHTML;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function cetakPreview() {
    const previewContent = document.getElementById('previewContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Preview Surat</title>
            <style>
                body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; padding: 2cm; }
                .kop-surat { text-align: center; margin-bottom: 25px; position: relative; }
                .logo-container { position: absolute; left: 0; top: 0; width: 100px; height: 100px; }
                .logo-container img { max-width: 100%; max-height: 100%; }
                .kop-text { margin-left: 120px; }
                .kop-surat h4 { margin: 0; font-weight: bold; }
                table { border-collapse: collapse; width: 100%; }
                .table-borderless td { border: none; padding: 3px 5px; }
                .table-bordered { border: 1px solid #000; }
                .table-bordered th, .table-bordered td { border: 1px solid #000; padding: 5px; }
                .ttd-area { margin-top: 100px; }
                .underline { text-decoration: underline; }
                [contenteditable="true"] { border-bottom: 1px dashed #999; }
            </style>
        </head>
        <body>
            ${previewContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// ===== FORM SUBMIT =====
document.getElementById('formBuatSurat')?.addEventListener('submit', function(e) {
    // Simpan konten editor ke hidden input
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'konten_surat';
    hiddenInput.value = document.getElementById('editor').innerHTML;
    this.appendChild(hiddenInput);
    
    // Form akan submit ke halaman yang sama dengan action=cetak
    // yang akan memproses cetak di bagian atas file
});
</script>

<?php
$content = ob_get_clean();
include 'template1/base.php';
?>