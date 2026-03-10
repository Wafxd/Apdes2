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

$autoload_paths = [
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

// ==================== FUNGSI FORMAT TANGGAL & HARI ====================
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

function hari_indonesia($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $hari = date('D', strtotime($tanggal));
    $daftar_hari = array(
        'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
        'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jum\'at', 'Sat' => 'Sabtu'
    );
    return $daftar_hari[$hari];
}

// ==================== ENGINE PEMBUAT TEMPLATE HTML DINAMIS ====================
function generateSuratHtml($surat) {
    global $conn; 
    
    $jenis = $surat['jenis_surat'];
    $no_surat = $surat['no_surat'];
    $nama = strtoupper($surat['nama_pemohon']);
    $nik = $surat['nik'];
    $tempat_lahir = $surat['tempat_lahir'] ?? '-';
    $tanggal_lahir = $surat['tanggal_lahir'] ? tgl_indonesia($surat['tanggal_lahir']) : '-';
    $alamat = strtoupper($surat['alamat'] ?? '-');
    $tanggal_surat = tgl_indonesia($surat['tanggal_surat']);
    
    // Parse Keterangan Ekstra
    $extra = [];
    if (!empty($surat['keterangan'])) {
        $lines = explode("\n", $surat['keterangan']);
        foreach ($lines as $line) {
            $parts = explode(":", $line, 2);
            if (count($parts) == 2) $extra[trim($parts[0])] = trim($parts[1]);
        }
    }

    // --- KOP SURAT ---
    $html = '
    <div style="display: table; width: 100%; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 8px;">
        <div style="display: table-cell; width: 90px; vertical-align: middle;">
            <img src="../img/labang.png" alt="Logo Desa" style="max-width: 80px; max-height: 80px;">
        </div>
        <div style="display: table-cell; vertical-align: middle; text-align: center; color: #000;">
            <h4 style="margin:0; font-weight: bold; font-size: 14pt; line-height: 1.2;">PEMERINTAH KABUPATEN BANGKALAN</h4>
            <h4 style="margin:0; font-weight: bold; font-size: 14pt; line-height: 1.2;">KECAMATAN LABANG</h4>
            <h4 style="margin:0; font-weight: bold; font-size: 14pt; line-height: 1.2;">KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
            <p style="margin:0; font-size: 11pt;">Labang 69163</p>
        </div>
    </div>';

    // --- JUDUL SURAT ---
    $judul_surat = "SURAT KETERANGAN";
    if($jenis == 'SKD') $judul_surat = "SURAT KETERANGAN DOMISILI";
    if($jenis == 'SKTM') $judul_surat = "SURAT KETERANGAN TIDAK MAMPU";
    if($jenis == 'SKU') $judul_surat = "SURAT KETERANGAN MEMILIKI USAHA";
    if($jenis == 'SKKe') $judul_surat = "SURAT KETERANGAN KEHILANGAN";
    if($jenis == 'SKL') $judul_surat = "SURAT KETERANGAN KELAHIRAN";
    if($jenis == 'SKKM') $judul_surat = "SURAT KETERANGAN KEMATIAN";

    $html .= '
    <div style="text-align: center; margin: 15px 0 20px; color: #000;">
        <h4 style="text-decoration: underline; font-weight: bold; margin:0 0 3px 0; font-size: 13pt;">'.$judul_surat.'</h4>
        <p style="margin:0; font-size: 12pt;">NO : ' . $no_surat . '</p>
    </div>';

    // --- BODY SURAT BERDASARKAN JENIS ---
    $html .= '<div style="color: #000; font-size: 12pt; text-align: justify;">';

    if ($jenis == 'SKL') {
        $q_bayi = mysqli_query($conn, "SELECT k.*, p.alamat, p.rt_rw, p.dusun FROM kelahiran k LEFT JOIN penduduk p ON k.nik_bayi = p.nik WHERE k.nik_bayi = '$nik'");
        $b = mysqli_fetch_assoc($q_bayi);

        if ($b) {
            $html .= '<p style="margin-bottom: 10px;">Yang bertanda tangan di bawah ini, Kepala Desa Sukolilo Timur Kecamatan Labang, dengan ini menerangkan kepada :</p>
            <table style="width: 100%; border-collapse: collapse; font-size: 12pt; margin-bottom: 10px;">
                <tr><td style="width: 180px; padding: 2px 0;">Nama</td><td style="width: 20px;">:</td><td style="font-weight:bold;">'.strtoupper($b['nama_bayi']).'</td></tr>
                <tr><td style="padding: 2px 0;">Jenis Kelamin</td><td>:</td><td>'.($b['jenis_kelamin']=='LAKI-LAKI'?'Laki-Laki':'Perempuan').'</td></tr>
                <tr><td style="padding: 2px 0;">NIK</td><td>:</td><td>'.$b['nik_bayi'].'</td></tr>
                <tr><td style="padding: 2px 0;">Tempat/Tgl lahir</td><td>:</td><td>'.$b['tempat_lahir'].', '.tgl_indonesia($b['tanggal_lahir']).'</td></tr>
                <tr><td style="padding: 2px 0;">Alamat</td><td>:</td><td>'.strtoupper(($b['alamat'] ?? 'Dsn. ') . ' Ds. Sukolilo Timur').'</td></tr>
                <tr><td style="padding: 2px 0;">Anak Ke</td><td>:</td><td>'.$b['anak_ke'].'</td></tr>
            </table>
            <table style="width: 100%; border-collapse: collapse; font-size: 12pt; margin-bottom: 10px;">
                <tr><td style="width: 180px; padding: 2px 0; padding-left: 30px;">Dari seorang Ibu</td><td style="width: 20px;">:</td><td></td></tr>
                <tr><td style="padding: 2px 0;">Nama</td><td>:</td><td style="font-weight:bold;">'.strtoupper($b['nama_ibu']).'</td></tr>
                <tr><td style="padding: 2px 0;">Pekerjaan</td><td>:</td><td>MENGURUS RUMAH TANGGA</td></tr>
            </table>
            <table style="width: 100%; border-collapse: collapse; font-size: 12pt; margin-bottom: 10px;">
                <tr><td style="width: 180px; padding: 2px 0; padding-left: 30px;">Istri dari (Ayah)</td><td style="width: 20px;">:</td><td></td></tr>
                <tr><td style="padding: 2px 0;">Nama</td><td>:</td><td style="font-weight:bold;">'.strtoupper($b['nama_ayah']).'</td></tr>
            </table>
            <p style="margin-bottom: 10px;">Demikian Surat Keterangan Lahir ini dibuat dengan benar untuk digunakan perlunya.</p>';
        } else {
            $html .= '<p class="text-danger"><i>Arsip biodata kelahiran tidak ditemukan di database.</i></p>';
        }

    } elseif ($jenis == 'SKKM') {
        $q_kematian = mysqli_query($conn, "SELECT * FROM kematian WHERE nik_jenazah = '$nik'");
        $k = mysqli_fetch_assoc($q_kematian);

        if ($k) {
            $jam = $k['waktu_kematian'] ? date('H.i', strtotime($k['waktu_kematian'])).' WIB' : '-';
            $pelapor = ($k['nama_pelapor']?:'-') . ' (' . ($k['hubungan_pelapor']?:'-') . ')';
            
            $html .= '<p style="margin-bottom: 15px; text-indent: 40px;">Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan dengan sesungguhnya bahwa :</p>
            <table style="width: 100%; border-collapse: collapse; font-size: 12pt; margin-bottom: 15px;">
                <tr><td style="width: 180px; padding: 2px 0;">Nama</td><td style="width: 20px;">:</td><td style="font-weight:bold;">'.strtoupper($k['nama_jenazah']).'</td></tr>
                <tr><td style="padding: 2px 0;">Tempat tanggal lahir</td><td>:</td><td>'.$k['tempat_lahir'].', '.tgl_indonesia($k['tanggal_lahir']).'</td></tr>
                <tr><td style="padding: 2px 0;">Jenis Kelamin</td><td>:</td><td>'.($k['jenis_kelamin']=='LAKI-LAKI'?'Laki-laki':'Perempuan').'</td></tr>
                <tr><td style="padding: 2px 0;">Alamat rumah</td><td>:</td><td>'.strtoupper($k['alamat']).'</td></tr>
                <tr><td style="padding: 2px 0;">Agama</td><td>:</td><td>'.ucfirst(strtolower($k['agama'])).'</td></tr>
            </table>
            <p style="margin-bottom: 15px;">Yang bersangkutan benar telah Meninggal Dunia pada :</p>
            <table style="width: 100%; border-collapse: collapse; font-size: 12pt; margin-bottom: 25px;">
                <tr><td style="width: 180px; padding: 2px 0;">Hari / tanggal</td><td style="width: 20px;">:</td><td style="font-weight:bold;">'.hari_indonesia($k['tanggal_kematian']).', '.tgl_indonesia($k['tanggal_kematian']).'</td></tr>
                <tr><td style="padding: 2px 0;">Jam</td><td>:</td><td>'.$jam.'</td></tr>
                <tr><td style="padding: 2px 0;">Sebab Kematian</td><td>:</td><td>'.strtoupper($k['penyebab_kematian']).'</td></tr>
                <tr><td style="padding: 2px 0;">Pelapor Kematian</td><td>:</td><td>'.strtoupper($pelapor).'</td></tr>
                <tr><td style="padding: 2px 0;">Tempat Kematian</td><td>:</td><td>'.strtoupper($k['tempat_kematian']).'</td></tr>
            </table>
            <p style="text-align: justify; text-indent: 40px;">Demikian Surat Keterangan Kematian ini diberikan untuk dapat dipergunakan sebagaimana mestinya.</p>';
        } else {
            $html .= '<p class="text-danger"><i>Arsip biodata kematian tidak ditemukan di database.</i></p>';
        }

    } else {
        // FORMAT SURAT UMUM
        $html .= '<p style="margin-bottom: 10px;">Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:</p>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 12pt;">
            <tr><td style="width: 150px; padding: 4px 0;">N a m a</td><td style="width: 20px;">:</td><td style="font-weight: bold;">' . $nama . '</td></tr>
            <tr><td style="padding: 4px 0;">Tempat / Tgl Lahir</td><td>:</td><td>' . $tempat_lahir . ', ' . $tanggal_lahir . '</td></tr>
            <tr><td style="padding: 4px 0;">NIK</td><td>:</td><td>' . $nik . '</td></tr>
            <tr><td style="padding: 4px 0; vertical-align: top;">Alamat</td><td style="vertical-align: top;">:</td><td>' . $alamat . '<br>Kecamatan Labang Kabupaten Bangkalan</td></tr>
        </table>';

        if ($jenis == 'SKD') {
            $html .= '<p style="margin-bottom: 10px;">menerangkan dengan sebenarnya bahwa orang tersebut di atas benar-benar berdomisili di <b>Dsn. Paserean Desa Sukolilo Timur</b>.</p>';
        } elseif ($jenis == 'SKTM') {
            $tujuan = $extra['Tujuan SKTM'] ?? $surat['keperluan'] ?? 'mendapatkan bantuan';
            $html .= '<p style="margin-bottom: 10px;">Bahwa nama yang tercantum di atas adalah benar-benar berdomisili di <b>DESA SUKOLILO TIMUR, KECAMATAN LABANG</b>. Sepanjang pengamatan kami dan sesuai data yang ada dalam catatan kependudukan, orang tersebut di atas benar tergolong dalam keluarga prasejahtera (Keluarga Berpenghasilan Rendah). Surat Keterangan ini diberikan untuk <b>'.$tujuan.'</b>.</p>';
        } elseif ($jenis == 'SKU') {
            $j_usaha = $extra['Jenis Usaha'] ?? 'Perdagangan';
            $n_usaha = $extra['Nama Usaha'] ?? 'Usaha Milik Warga';
            $a_usaha = $extra['Alamat Usaha'] ?? 'Dsn. Paserean';
            $html .= '<p style="margin-bottom: 10px;">Menerangkan dengan sebenarnya bahwa orang tersebut benar-benar penduduk Desa Sukolilo Timur, dan bersangkutan benar-benar memiliki Usaha <b>'.$j_usaha.'</b> dengan nama <b>'.$n_usaha.'</b> yang beralamat di <b>'.$a_usaha.'</b>.</p>';
        } elseif ($jenis == 'SKKe') {
            $tgl_k = $extra['Tanggal Kejadian'] ?? '-';
            $jam_k = $extra['Jam'] ?? '-';
            $lok_k = $extra['Lokasi'] ?? '-';
            $brg_k = $extra['Barang Hilang'] ?? '-';
            $html .= '<p style="margin-bottom: 10px;">menerangkan dengan sebenarnya bahwa orang tersebut diatas pada tanggal <b>'.$tgl_k.'</b> sekitar pukul <b>'.$jam_k.'</b> telah kehilangan <b>'.$brg_k.'</b> di <b>'.$lok_k.'</b>.</p>';
        } else {
            $keperluan = $extra['Keperluan'] ?? $surat['keperluan'] ?? '-';
            $html .= '<p style="margin-bottom: 10px;">Menerangkan dengan sebenarnya orang tersebut diatas adalah benar-benar Penduduk Desa Sukolilo Timur dan Surat ini dikeluarkan untuk <b>'.$keperluan.'</b>.</p>';
        }
        $html .= '<p style="margin-bottom: 10px;">Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>';
    }

    $html .= '</div>';

    // --- TTD ---
    $html .= '
    <table style="width: 100%; border: none; margin-top: 30px; color: #000; font-size: 12pt;">
        <tr>
            <td style="width: 55%; border: none;"></td>
            <td style="width: 45%; border: none; text-align: center; color: #000; vertical-align: top;">
                Sukolilo Timur, ' . $tanggal_surat . '<br>
                Kepala Desa Sukolilo Timur<br><br><br>
                <div style="text-align: center;">';
    if (file_exists('../img/ttd.png')) {
        $html .= '<img src="../img/ttd.png" alt="Barcode" style="width: 90px; height: 90px; display: inline-block;">';
    } else {
        $html .= '<span style="display: inline-block; width: 90px; height: 90px; color: red; font-size: 10pt;">[TTD Basah]</span>';
    }
    $html .= '  </div>
                <br><br>
                <span style="text-decoration: underline; font-weight: bold; color: #000;">H. ZAINAL ABIDIN</span>
            </td>
        </tr>
    </table>';

    return $html;
}

// ==================== HANDLE HAPUS SURAT ====================
if (isset($_GET['ajax_hapus_surat'])) {
    header('Content-Type: application/json');
    $id_surat = mysqli_real_escape_string($conn, $_GET['ajax_hapus_surat']);
    $query = "DELETE FROM arsip_surat WHERE id_surat = '$id_surat'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_affected_rows($conn) > 0) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'message' => 'Gagal menghapus surat']);
    exit();
}

// ==================== HANDLE GET SURAT BY ID (AJAX PREVIEW) ====================
if (isset($_GET['ajax_get_surat'])) {
    header('Content-Type: application/json');
    $id_surat = mysqli_real_escape_string($conn, $_GET['ajax_get_surat']);
    $query = "SELECT * FROM arsip_surat WHERE id_surat = '$id_surat'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $htmlContent = generateSuratHtml($data);
        echo json_encode(['success' => true, 'html' => $htmlContent]);
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
        $konten = generateSuratHtml($surat);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Cetak Arsip Surat - <?php echo htmlspecialchars($surat['no_surat']); ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Times New Roman', Times, serif; background: white; color: #000; }
                @page { size: A4 portrait; margin: 0; }
                .surat-container {
                    padding-top: 2cm; padding-right: 2cm; padding-bottom: 2cm; padding-left: 3cm;
                    width: 100%; height: 100vh; overflow: hidden;
                }
            </style>
        </head>
        <body>
            <div class="surat-container"><?php echo $konten; ?></div>
            <script>window.onload = function() { window.print(); window.onafterprint = function() { window.close(); }; }</script>
        </body>
        </html>
        <?php
    }
    exit();
}

// ==================== HANDLE DOWNLOAD PDF ====================
if (isset($_POST['action']) && $_POST['action'] == 'download_pdf_arsip') {
    if (!$use_dompdf) die("<h3>Error: Library Dompdf belum terinstall.</h3>");
    
    $id_surat = mysqli_real_escape_string($conn, $_POST['id_surat']);
    $query = "SELECT * FROM arsip_surat WHERE id_surat = '$id_surat'";
    $result = mysqli_query($conn, $query);
    $surat = mysqli_fetch_assoc($result);
    
    if ($surat) {
        $konten = generateSuratHtml($surat);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 2cm 2cm 2cm 3cm; }
                body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.3; color: #000000; }
                table { width: 100%; border-collapse: collapse; font-size: inherit; }
                td { padding: 3px; vertical-align: top; color: #000; }
            </style>
        </head>
        <body>' . $konten . '</body>
        </html>';
        
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', realpath(__DIR__ . '/../'));
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = "Arsip_Surat_" . preg_replace('/[^a-zA-Z0-9]/', '_', $surat['no_surat']) . ".pdf";
        $dompdf->stream($filename, array("Attachment" => true));
        exit;
    }
}

// ==================== FILTER DAN PENCARIAN ====================
$where = "WHERE 1=1";
if (isset($_GET['jenis_surat']) && !empty($_GET['jenis_surat'])) {
    $jenis_surat = mysqli_real_escape_string($conn, $_GET['jenis_surat']);
    $where .= " AND jenis_surat = '$jenis_surat'";
}
if (isset($_GET['bulan']) && !empty($_GET['bulan'])) {
    $bulan = (int)$_GET['bulan'];
    $where .= " AND MONTH(tanggal_surat) = '$bulan'";
}
if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
    $tahun = (int)$_GET['tahun'];
    $where .= " AND YEAR(tanggal_surat) = '$tahun'";
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (no_surat LIKE '%$search%' OR nama_pemohon LIKE '%$search%' OR nik LIKE '%$search%')";
}

// ==================== AMBIL DATA SURAT ====================
$query = "SELECT * FROM arsip_surat $where ORDER BY tanggal_surat DESC, id_surat DESC";
$result = mysqli_query($conn, $query);
$total_surat = mysqli_num_rows($result);

// Hitung statistik Box Atas
$tahun_ini = date('Y');
$bulan_ini = date('m');
$tanggal_ini = date('Y-m-d');

$total_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM arsip_surat"))['total'];
$total_skl = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM arsip_surat WHERE jenis_surat = 'SKL'"))['total'];
$total_skkm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM arsip_surat WHERE jenis_surat = 'SKKM'"))['total'];
$total_skd = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM arsip_surat WHERE jenis_surat = 'SKD'"))['total'];

$pageTitle = "Data Arsip Surat Keluar";
$pageHeaderButton = '<a href="surat.php" class="btn btn-sm btn-primary shadow-sm rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Kembali</a>';

ob_start();
?>

<style>
body { background-color: #f8f9fc; }

.statistik-card {
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: none;
    border-radius: 1rem;
    box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.04);
    background: white;
    overflow: hidden;
}
.statistik-card:hover { transform: translateY(-5px); box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.1); }
.border-left-primary { border-left: 5px solid #4e73df !important; }
.border-left-success { border-left: 5px solid #1cc88a !important; }
.border-left-dark { border-left: 5px solid #5a5c69 !important; }
.border-left-info { border-left: 5px solid #36b9cc !important; }

.filter-card { border: none; border-radius: 1rem; box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.03); background: white; }
.filter-card .card-header { background: transparent; border-bottom: 1px solid #eaecf4; padding: 1.25rem 1.5rem; }

.table-container { background: white; border-radius: 1rem; box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.03); overflow: hidden; padding: 20px;}
.table thead th { background-color: #f8f9fc; border-bottom: 2px solid #eaecf4; color: #4e73df; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px;}
.table tbody tr { transition: background 0.2s; }
.table tbody tr:hover { background-color: #f1f3f9; }
.table td { vertical-align: middle; color: #5a5c69; border-bottom: 1px solid #eaecf4; }

.badge { padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 0.75rem; }
.badge-skd { background-color: #e3e6f0; color: #4e73df; }
.badge-sktm { background-color: #fce4e4; color: #e74a3b; }
.badge-sku { background-color: #e3fbed; color: #1cc88a; }
.badge-skke { background-color: #fef0c7; color: #f6c23e; }
.badge-skl { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9;}
.badge-skkm { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2;}
.badge-sk { background-color: #e0f2f4; color: #36b9cc; }

.btn-group-action { display: flex; gap: 6px; justify-content: center; }
.action-icon { 
    width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; 
    border-radius: 8px; color: white; transition: all 0.2s; border: none; cursor: pointer;
}
.action-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); filter: brightness(1.1); }
.icon-wa { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); } 
.icon-view { background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%); }
.icon-print { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
.icon-pdf { background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%); }
.icon-delete { background: #eaecf4; color: #e74a3b; border: 1px solid #e74a3b;}
.icon-delete:hover { background: #e74a3b; color: white; }

.form-control, .form-select { border-radius: 8px; border: 1px solid #d1d3e2; padding: 0.6rem 1rem; font-size: 0.9rem; }
.form-control:focus, .form-select:focus { border-color: #4e73df; box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25); }

/* FIX MODAL SCROLLING SAMA PERSIS SEPERTI SEBELUMNYA */
.modal-content { border: none; border-radius: 1rem; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow: hidden; display: flex; flex-direction: column; max-height: 100%;}
.modal-header { border-bottom: none; padding: 1.25rem 1.5rem; flex-shrink: 0;}
.modal-body { padding: 1.5rem; overflow-y: auto; flex-grow: 1; }
.modal-footer { border-top: 1px solid #eaecf4; background: #f8f9fc; padding: 1rem 1.5rem; flex-shrink: 0;}
</style>

<div class="container-fluid px-0">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Database Arsip Surat</h1>
            <p class="text-muted small">Kelola, cetak ulang, bagikan ke WA, dan unduh riwayat surat keluar desa.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="surat/export_surat.php" class="btn btn-success shadow-sm rounded-pill px-3" onclick="alert('Fitur Export Excel akan disiapkan')">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card statistik-card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Semua Arsip Surat</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $total_all; ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-folder-open fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card statistik-card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Surat Kelahiran (SKL)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $total_skl; ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-baby fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card statistik-card border-left-dark h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Surat Kematian (SKKM)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $total_skkm; ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-book-dead fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card statistik-card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Surat Domisili (SKD)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $total_skd; ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-map-marked-alt fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="filter-card mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-sliders-h me-2"></i>Filter Data Arsip</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" id="filterForm">
                <div class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label small font-weight-bold text-muted mb-1">Kategori Surat</label>
                        <select class="form-select" name="jenis_surat" id="jenis_surat">
                            <option value="">Semua Kategori</option>
                            <option value="SKD" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKD') ? 'selected' : ''; ?>>Domisili (SKD)</option>
                            <option value="SKTM" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKTM') ? 'selected' : ''; ?>>Tidak Mampu (SKTM)</option>
                            <option value="SKU" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKU') ? 'selected' : ''; ?>>Usaha (SKU)</option>
                            <option value="SKKe" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKKe') ? 'selected' : ''; ?>>Kehilangan (SKKe)</option>
                            <option value="SKL" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKL') ? 'selected' : ''; ?>>Kelahiran (SKL)</option>
                            <option value="SKKM" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SKKM') ? 'selected' : ''; ?>>Kematian (SKKM)</option>
                            <option value="SK" <?php echo (isset($_GET['jenis_surat']) && $_GET['jenis_surat'] == 'SK') ? 'selected' : ''; ?>>Keterangan Umum (SK)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small font-weight-bold text-muted mb-1">Bulan</label>
                        <select class="form-select" name="bulan" id="bulan">
                            <option value="">Semua Bulan</option>
                            <?php 
                            $nm_bulan = ['1'=>'Jan', '2'=>'Feb', '3'=>'Mar', '4'=>'Apr', '5'=>'Mei', '6'=>'Jun', '7'=>'Jul', '8'=>'Agt', '9'=>'Sep', '10'=>'Okt', '11'=>'Nov', '12'=>'Des'];
                            foreach($nm_bulan as $num => $name): ?>
                                <option value="<?php echo $num; ?>" <?php echo (isset($_GET['bulan']) && $_GET['bulan'] == $num) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small font-weight-bold text-muted mb-1">Tahun</label>
                        <select class="form-select" name="tahun" id="tahun">
                            <option value="">Semua Tahun</option>
                            <?php $thn = date('Y'); for ($t = $thn; $t >= $thn - 5; $t--): ?>
                                <option value="<?php echo $t; ?>" <?php echo (isset($_GET['tahun']) && $_GET['tahun'] == $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small font-weight-bold text-muted mb-1">Pencarian Bebas</label>
                        <input type="text" class="form-control" name="search" placeholder="Cari Nama / NIK / No. Surat" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill"><i class="fas fa-search me-1"></i> Cari</button>
                        <?php if (isset($_GET['jenis_surat']) || isset($_GET['bulan']) || isset($_GET['tahun']) || isset($_GET['search'])): ?>
                            <a href="surat_keluar.php" class="btn btn-light border rounded-pill" title="Reset Filter"><i class="fas fa-redo"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container mb-5">
        <?php if ($total_surat > 0): ?>
        <div class="table-responsive">
            <table class="table table-borderless align-middle">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="12%">Tanggal</th>
                        <th width="15%">Nomor Surat</th>
                        <th width="12%">Tipe</th>
                        <th width="20%">Atas Nama</th>
                        <th width="16%">NIK Terkait</th>
                        <th width="20%" class="text-center">Aksi Dokumen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while ($surat = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td class="text-center text-muted"><?php echo $no++; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($surat['tanggal_surat'])); ?></td>
                        <td class="font-weight-bold text-primary"><?php echo htmlspecialchars($surat['no_surat']); ?></td>
                        <td>
                            <?php
                            $badge = 'badge-sk'; $lbl = 'Umum';
                            if($surat['jenis_surat']=='SKD') { $badge='badge-skd'; $lbl='Domisili'; }
                            if($surat['jenis_surat']=='SKTM') { $badge='badge-sktm'; $lbl='SKTM'; }
                            if($surat['jenis_surat']=='SKU') { $badge='badge-sku'; $lbl='Usaha'; }
                            if($surat['jenis_surat']=='SKKe') { $badge='badge-skke'; $lbl='Kehilangan'; }
                            if($surat['jenis_surat']=='SKL') { $badge='badge-skl'; $lbl='Kelahiran'; }
                            if($surat['jenis_surat']=='SKKM') { $badge='badge-skkm'; $lbl='Kematian'; }
                            ?>
                            <span class="badge <?php echo $badge; ?>"><?php echo $lbl; ?></span>
                        </td>
                        <td class="font-weight-bold text-gray-800 text-uppercase"><?php echo htmlspecialchars($surat['nama_pemohon']); ?></td>
                        <td><span class="text-muted font-monospace"><i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($surat['nik']); ?></span></td>
                        <td>
                            <div class="btn-group-action">
                                <button type="button" class="action-icon icon-wa" onclick="bukaModalWA('<?php echo htmlspecialchars($surat['no_surat']); ?>', '<?php echo htmlspecialchars($surat['nama_pemohon']); ?>', '<?php echo $lbl; ?>')" title="Kirim Info via WhatsApp"><i class="fab fa-whatsapp"></i></button>
                                
                                <button type="button" class="action-icon icon-view" onclick="lihatSurat('<?php echo $surat['id_surat']; ?>')" title="Lihat Pratinjau Kertas"><i class="fas fa-eye"></i></button>
                                <button type="button" class="action-icon icon-print" onclick="cetakUlang('<?php echo $surat['id_surat']; ?>')" title="Cetak Kertas Langsung"><i class="fas fa-print"></i></button>
                                <button type="button" class="action-icon icon-pdf" onclick="downloadPDF('<?php echo $surat['id_surat']; ?>')" title="Unduh Arsip PDF"><i class="fas fa-file-pdf"></i></button>
                                <button type="button" class="action-icon icon-delete" onclick="hapusSurat('<?php echo $surat['id_surat']; ?>', '<?php echo htmlspecialchars($surat['no_surat']); ?>')" title="Hapus Permanen"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-light border text-center py-5">
            <i class="fas fa-folder-open fa-3x text-gray-300 mb-3"></i>
            <h5 class="text-gray-600">Tidak ada data arsip ditemukan.</h5>
            <p class="text-muted mb-0">Silakan ubah filter pencarian atau buat surat baru.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="waModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-gradient-success text-white py-3">
                <h5 class="modal-title font-weight-bold"><i class="fab fa-whatsapp me-2"></i>Kirim Notifikasi WA</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalWa()"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-3">Kirimkan pesan otomatis ke warga bahwa surat mereka sudah selesai dicetak.</p>
                
                <input type="hidden" id="wa_no_surat">
                <input type="hidden" id="wa_nama">
                <input type="hidden" id="wa_jenis">
                
                <div class="mb-3">
                    <label class="form-label font-weight-bold text-dark">Nomor WhatsApp Tujuan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-success font-weight-bold">+62</span>
                        <input type="number" class="form-control font-weight-bold" id="wa_nomor" placeholder="81234567890">
                    </div>
                    <small class="text-muted mt-1 d-block">Masukkan nomor diawali dengan 8 (contoh: 812...)</small>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-end">
                <button type="button" class="btn btn-light border rounded-pill px-4" onclick="closeModalWa()">Batal</button>
                <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" onclick="eksekusiWA()"><i class="fas fa-paper-plane me-1"></i> Kirim Pesan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewSuratModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-file-alt me-2"></i>Arsip Pratinjau Surat</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalView()"></button>
            </div>
            <div class="modal-body bg-light d-flex justify-content-center py-5">
                <div id="viewSuratContent" class="shadow-lg" style="background: white; padding: 2cm 2cm 2cm 3cm; width: 210mm; min-height: 297mm;">
                    <div class="text-center py-5 mt-5">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                        <p class="mt-3 fs-5 text-muted">Mengekstrak Arsip Digital...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-light border rounded-pill px-4" onclick="closeModalView()">Tutup</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" onclick="printFromModal()"><i class="fas fa-print me-1"></i> Cetak Dokumen Ini</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmHapusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle me-2"></i>Peringatan Penghapusan</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalHapus()"></button>
            </div>
            <div class="modal-body py-5 text-center">
                <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center mb-4 shadow" style="width: 80px; height: 80px;">
                    <i class="fas fa-trash-alt fa-3x"></i>
                </div>
                <h4 class="text-gray-800 font-weight-bold mb-3">Hapus Arsip Surat?</h4>
                <p class="text-muted mb-4">Tindakan ini akan menghapus riwayat surat secara permanen dari database.</p>
                <div id="confirmHapusInfo" class="alert alert-light border mx-auto text-start" style="max-width: 350px;"></div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-center">
                <button type="button" class="btn btn-light border rounded-pill px-4" onclick="closeModalHapus()">Batal</button>
                <button type="button" class="btn btn-danger rounded-pill px-5 shadow-sm" id="btnConfirmHapus"><i class="fas fa-trash me-1"></i> Ya, Hapus Permanen</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let modalViewInstance = null;
let modalHapusInstance = null;
let modalWaInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('viewSuratModal')) modalViewInstance = new bootstrap.Modal(document.getElementById('viewSuratModal'));
    if (document.getElementById('confirmHapusModal')) modalHapusInstance = new bootstrap.Modal(document.getElementById('confirmHapusModal'));
    if (document.getElementById('waModal')) modalWaInstance = new bootstrap.Modal(document.getElementById('waModal'));
});

// ===== FUNGSI PEMBERSIH BACKDROP & TUTUP MODAL =====
function cleanupBackdrop() { 
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove()); 
    document.body.classList.remove('modal-open'); 
    document.body.style.overflow=''; 
    document.body.style.paddingRight=''; 
}

function closeModalView() { if (modalViewInstance) modalViewInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalHapus() { if (modalHapusInstance) modalHapusInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalWa() { if (modalWaInstance) modalWaInstance.hide(); setTimeout(cleanupBackdrop, 100); }

// ===== FITUR BARU: BAGIKAN VIA WHATSAPP =====
function bukaModalWA(no_surat, nama, jenis_surat) {
    document.getElementById('wa_no_surat').value = no_surat;
    document.getElementById('wa_nama').value = nama;
    document.getElementById('wa_jenis').value = jenis_surat;
    document.getElementById('wa_nomor').value = '';
    
    if (modalWaInstance) modalWaInstance.show();
    
    // Auto focus ke input nomor setelah modal muncul
    setTimeout(() => { document.getElementById('wa_nomor').focus(); }, 500);
}

function eksekusiWA() {
    let nope = document.getElementById('wa_nomor').value;
    if (!nope || nope.trim() === '') {
        alert("Mohon masukkan nomor WhatsApp terlebih dahulu!");
        document.getElementById('wa_nomor').focus();
        return;
    }

    let no_surat = document.getElementById('wa_no_surat').value;
    let nama = document.getElementById('wa_nama').value;
    let jenis_surat = document.getElementById('wa_jenis').value;

    // Bersihkan karakter aneh pada nomor HP dan standarisasi kode negara
    nope = nope.replace(/[^0-9]/g, '');
    if (nope.startsWith('0')) { 
        nope = '62' + nope.substring(1); 
    } else if (!nope.startsWith('62')) { 
        nope = '62' + nope; 
    }

    let pesan = `Halo Bpk/Ibu *${nama}*,\n\nKami dari Pemerintah Desa Sukolilo Timur menginformasikan bahwa *Surat Keterangan ${jenis_surat}* Anda dengan Nomor Indeks: *${no_surat}* telah selesai dicetak.\n\nSilakan datang ke Balai Desa Sukolilo Timur pada jam kerja untuk mengambil dokumen fisik tersebut.\n\nTerima kasih.`;

    let url = `https://api.whatsapp.com/send?phone=${nope}&text=${encodeURIComponent(pesan)}`;
    
    // Buka WhatsApp di tab baru
    window.open(url, '_blank');
    
    // Tutup modal WA
    closeModalWa();
}

// ===== FUNGSI LIHAT SURAT (AJAX) =====
function lihatSurat(id) {
    const contentDiv = document.getElementById('viewSuratContent');
    contentDiv.innerHTML = `<div class="text-center py-5 mt-5"><div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div><p class="mt-3 text-muted">Mengekstrak Arsip Digital...</p></div>`;
    if (modalViewInstance) modalViewInstance.show();
    
    fetch('surat_keluar.php?ajax_get_surat=' + id)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                contentDiv.innerHTML = `<div style="font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.4; color:#000;">${result.html}</div>`;
            } else {
                contentDiv.innerHTML = `<div class="alert alert-danger m-5"><i class="fas fa-times-circle me-2"></i>Gagal memuat arsip: ${result.message}</div>`;
            }
        })
        .catch(error => {
            contentDiv.innerHTML = `<div class="alert alert-danger m-5">Terjadi kesalahan koneksi saat memuat arsip.</div>`;
        });
}

// ===== FUNGSI CETAK ULANG =====
function cetakUlang(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'surat_keluar.php';
    form.target = '_blank';
    
    const actionInput = document.createElement('input'); actionInput.type = 'hidden'; actionInput.name = 'action'; actionInput.value = 'cetak_ulang';
    const idInput = document.createElement('input'); idInput.type = 'hidden'; idInput.name = 'id_surat'; idInput.value = id;
    
    form.appendChild(actionInput); form.appendChild(idInput);
    document.body.appendChild(form); form.submit(); document.body.removeChild(form);
}

// ===== FUNGSI DOWNLOAD PDF =====
function downloadPDF(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'surat_keluar.php';
    
    const actionInput = document.createElement('input'); actionInput.type = 'hidden'; actionInput.name = 'action'; actionInput.value = 'download_pdf_arsip';
    const idInput = document.createElement('input'); idInput.type = 'hidden'; idInput.name = 'id_surat'; idInput.value = id;
    
    form.appendChild(actionInput); form.appendChild(idInput);
    document.body.appendChild(form); form.submit(); document.body.removeChild(form);
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
            <title>Cetak Arsip Surat</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Times New Roman', Times, serif; color: #000; background: white; }
                @page { size: A4 portrait; margin: 0; }
                .print-area { padding-top: 2cm; padding-right: 2cm; padding-bottom: 2cm; padding-left: 3cm; width: 100%; height: 100vh; overflow: hidden; }
            </style>
        </head>
        <body>
            <div class="print-area">${content}</div>
            <script>window.onload = function() { window.print(); window.onafterprint = function() { window.close(); }; }<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// ===== FUNGSI HAPUS SURAT =====
function hapusSurat(id, no_surat) {
    const infoDiv = document.getElementById('confirmHapusInfo');
    if (infoDiv) infoDiv.innerHTML = `<div class="mb-1 text-muted small">Nomor Surat Target:</div><div class="font-weight-bold text-danger fs-6">${no_surat}</div>`;
    
    const confirmBtn = document.getElementById('btnConfirmHapus');
    if (confirmBtn) confirmBtn.setAttribute('data-id', id);
    if (modalHapusInstance) modalHapusInstance.show();
}

document.getElementById('btnConfirmHapus')?.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    if (!id) return;
    
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
    this.disabled = true;
    
    fetch('surat_keluar.php?ajax_hapus_surat=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) { location.reload(); } 
            else { alert('Gagal menghapus: ' + data.message); this.innerHTML = 'Ya, Hapus Permanen'; this.disabled = false; }
        })
        .catch(error => { alert('Terjadi kesalahan sistem.'); this.innerHTML = 'Ya, Hapus Permanen'; this.disabled = false; });
});
</script>

<?php
$content = ob_get_clean();
include '../includes/base.php';
?>