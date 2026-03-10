<?php
session_start();

include "../db/koneksi.php";
include "../db/funct.php";

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

$id_user = $_SESSION['id_admin'];
$username = $_SESSION["nama_admin"];

// ==================== FUNGSI WAJIB & AMAN ====================
function tgl_indonesia($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    return $tgl . ' ' . $bln . ' ' . $thn;
}

function hitungUmur($tanggal_lahir) {
    if (empty($tanggal_lahir)) return 0;
    try {
        $birthDate = new DateTime($tanggal_lahir);
        $today = new DateTime();
        return $today->diff($birthDate)->y;
    } catch (Exception $e) {
        return 0;
    }
}

// Fungsi pengaman Query Count
function getCountData($conn, $query) {
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row && isset($row['total'])) return (int)$row['total'];
    }
    return 0;
}

// Daftar dusun
$daftar_dusun = [
    'KEJAWAN', 'SEPURAN', 'BUDDAN', 'PASEREAN', 'LANGGAR', 
    'MORLEKE', 'PREGIH', 'KARANG PANDAN', 'PONG BARU', 'KRASAK', 'PERUM BASMALAH'
];

// ==================== AJAX PENCARIAN PENDUDUK ====================
if (isset($_POST['ajax_search'])) {
    header('Content-Type: application/json');
    
    $dusun = isset($_POST['dusun']) ? mysqli_real_escape_string($conn, $_POST['dusun']) : 'all';
    $umur = isset($_POST['umur']) ? (int)$_POST['umur'] : 0;
    $jk = isset($_POST['jk']) ? mysqli_real_escape_string($conn, $_POST['jk']) : 'all';
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'all';
    $pekerjaan = isset($_POST['pekerjaan']) ? mysqli_real_escape_string($conn, $_POST['pekerjaan']) : 'all';

    $query = "SELECT p.*, 
              (SELECT COUNT(*) FROM kartu_keluarga WHERE nik_kepala = p.nik) as is_kepala,
              (SELECT COUNT(*) FROM anggota_keluarga WHERE nik = p.nik AND hubungan_keluarga != 'Kepala Keluarga') as is_anggota
              FROM penduduk p WHERE 1=1";

    if ($dusun != 'all') $query .= " AND p.dusun = '$dusun'";
    if ($jk != 'all') $query .= " AND p.jenis_kelamin = '$jk'";
    if ($status != 'all') $query .= " AND p.status_kawin = '$status'";
    
    if ($pekerjaan != 'all') {
        if ($pekerjaan == 'Lainnya') {
            $pekerjaan_list = ['PNS', 'TNI', 'POLRI', 'PEGAWAI SWASTA', 'WIRASWASTA', 'PETANI', 'BURUH', 'PELAJAR/MAHASISWA', 'IRT', 'PENSIUNAN'];
            $escaped_list = array_map(function($val) use ($conn) { return "'" . mysqli_real_escape_string($conn, $val) . "'"; }, $pekerjaan_list);
            $query .= " AND (p.pekerjaan NOT IN (" . implode(',', $escaped_list) . ") OR p.pekerjaan IS NULL OR p.pekerjaan = '')";
        } else {
            $query .= " AND p.pekerjaan = '$pekerjaan'";
        }
    }

    $query .= " ORDER BY p.nama_penduduk ASC";
    $result = mysqli_query($conn, $query);

    $filtered_data = [];
    $total_filtered = 0;

    if($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $umur_penduduk = hitungUmur($row['tanggal_lahir']);
            if ($umur > 0 && $umur_penduduk != $umur) continue; 
            
            $row['umur'] = $umur_penduduk;
            $filtered_data[] = $row;
            $total_filtered++;
        }
    }

    echo json_encode(['total' => $total_filtered, 'data' => $filtered_data]);
    exit();
}

// ==================== STATISTIK UMUM ====================
$total_penduduk = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk");
$total_kk = getCountData($conn, "SELECT COUNT(*) as total FROM kartu_keluarga");
$total_laki = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'LAKI-LAKI'");
$total_perempuan = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'PEREMPUAN'");
$total_surat = getCountData($conn, "SELECT COUNT(*) as total FROM arsip_surat");

// STATISTIK MUTASI
$bulan_ini = date('m');
$tahun_ini = date('Y');
$total_kelahiran = getCountData($conn, "SELECT COUNT(*) as total FROM kelahiran");
$kelahiran_bln = getCountData($conn, "SELECT COUNT(*) as total FROM kelahiran WHERE MONTH(tanggal_lahir) = '$bulan_ini' AND YEAR(tanggal_lahir) = '$tahun_ini'");
$total_kematian = getCountData($conn, "SELECT COUNT(*) as total FROM kematian");
$kematian_bln = getCountData($conn, "SELECT COUNT(*) as total FROM kematian WHERE MONTH(tanggal_kematian) = '$bulan_ini' AND YEAR(tanggal_kematian) = '$tahun_ini'");
$total_datang = getCountData($conn, "SELECT COUNT(*) as total FROM kedatangan");
$datang_bln = getCountData($conn, "SELECT COUNT(*) as total FROM kedatangan WHERE MONTH(tanggal_datang) = '$bulan_ini' AND YEAR(tanggal_datang) = '$tahun_ini'");
$total_pindah = getCountData($conn, "SELECT COUNT(*) as total FROM pindah");
$pindah_bln = getCountData($conn, "SELECT COUNT(*) as total FROM pindah WHERE MONTH(tanggal_pindah) = '$bulan_ini' AND YEAR(tanggal_pindah) = '$tahun_ini'");

// ==================== DATA PER DUSUN ====================
$dusun_labels = []; $laki_per_dusun = []; $perempuan_per_dusun = []; $total_per_dusun = []; $kk_per_dusun = [];

foreach ($daftar_dusun as $dusun) {
    $dusun_escape = mysqli_real_escape_string($conn, $dusun);
    $dusun_labels[] = $dusun;
    $laki_per_dusun[] = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE dusun = '$dusun_escape' AND jenis_kelamin = 'LAKI-LAKI'");
    $perempuan_per_dusun[] = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE dusun = '$dusun_escape' AND jenis_kelamin = 'PEREMPUAN'");
    $total_per_dusun[] = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE dusun = '$dusun_escape'");
    $kk_per_dusun[] = getCountData($conn, "SELECT COUNT(*) as total FROM kartu_keluarga WHERE dusun = '$dusun_escape'");
}

$max_penduduk_dusun = !empty($total_per_dusun) ? max($total_per_dusun) : 0;
$dusun_terpadat = !empty($total_per_dusun) ? $dusun_labels[array_search($max_penduduk_dusun, $total_per_dusun)] : '-';

// ==================== DATA UMUR ====================
$rentang_umur = [
    '0-5 (Balita)' => [0, 5], '6-12 (Anak)' => [6, 12], '13-17 (Remaja)' => [13, 17], 
    '18-25 (Pemuda)' => [18, 25], '26-35 (Dewasa)' => [26, 35], '36-45 (Dewasa Tua)' => [36, 45], 
    '46-55 (Pra-Lansia)' => [46, 55], '56-65 (Lansia)' => [56, 65], '65+ (Manula)' => [66, 150]
];
$data_umur = [];
foreach ($rentang_umur as $rentang => $range) {
    $data_umur[$rentang] = ['total' => 0, 'per_dusun' => []];
    foreach ($daftar_dusun as $dsn) $data_umur[$rentang]['per_dusun'][$dsn] = 0;
}
$query_penduduk_umur = mysqli_query($conn, "SELECT nik, tanggal_lahir, dusun FROM penduduk");
if($query_penduduk_umur) {
    while ($row = mysqli_fetch_assoc($query_penduduk_umur)) {
        $umur_penduduk = hitungUmur($row['tanggal_lahir']);
        $dusun_penduduk = !empty($row['dusun']) ? $row['dusun'] : 'TANPA DUSUN';
        foreach ($rentang_umur as $rentang => $range) {
            if ($umur_penduduk >= $range[0] && $umur_penduduk <= $range[1]) {
                $data_umur[$rentang]['total']++;
                if (isset($data_umur[$rentang]['per_dusun'][$dusun_penduduk])) {
                    $data_umur[$rentang]['per_dusun'][$dusun_penduduk]++;
                }
                break;
            }
        }
    }
}

$max_umur_val = 0; $mayoritas_umur = '-';
foreach($data_umur as $key => $val) {
    if($val['total'] > $max_umur_val) { $max_umur_val = $val['total']; $mayoritas_umur = $key; }
}

// ==================== DATA STATUS KAWIN & AGAMA ====================
$status_kawin_list = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
$data_status = [];
foreach ($status_kawin_list as $status) $data_status[$status] = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE status_kawin = '".mysqli_real_escape_string($conn, $status)."'");

$agama_list = ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDDHA', 'KONGHUCU'];
$data_agama = [];
foreach ($agama_list as $agama) $data_agama[$agama] = getCountData($conn, "SELECT COUNT(*) as total FROM penduduk WHERE agama = '".mysqli_real_escape_string($conn, $agama)."'");

// ==================== DATA PEKERJAAN (FIXED: PER DUSUN) ====================
$pekerjaan_list = ['PNS', 'TNI', 'POLRI', 'PEGAWAI SWASTA', 'WIRASWASTA', 'PETANI', 'BURUH', 'PELAJAR/MAHASISWA', 'IRT', 'PENSIUNAN'];
$data_pekerjaan = [];
$data_pekerjaan_dusun = [];

foreach ($pekerjaan_list as $pkj) {
    $data_pekerjaan[$pkj] = 0;
    $data_pekerjaan_dusun[$pkj] = ['total' => 0, 'per_dusun' => []];
    foreach ($daftar_dusun as $dsn) $data_pekerjaan_dusun[$pkj]['per_dusun'][$dsn] = 0;
}
$data_pekerjaan['Lainnya'] = 0;
$data_pekerjaan_dusun['Lainnya'] = ['total' => 0, 'per_dusun' => []];
foreach ($daftar_dusun as $dsn) $data_pekerjaan_dusun['Lainnya']['per_dusun'][$dsn] = 0;

$q_pkj = mysqli_query($conn, "SELECT pekerjaan, dusun FROM penduduk");
if($q_pkj) {
    while ($row = mysqli_fetch_assoc($q_pkj)) {
        $pkj = trim(strtoupper($row['pekerjaan']));
        $dsn = !empty($row['dusun']) ? $row['dusun'] : 'TANPA DUSUN';
        $key = in_array($pkj, $pekerjaan_list) ? $pkj : 'Lainnya';
        
        $data_pekerjaan[$key]++;
        $data_pekerjaan_dusun[$key]['total']++;
        if (isset($data_pekerjaan_dusun[$key]['per_dusun'][$dsn])) {
            $data_pekerjaan_dusun[$key]['per_dusun'][$dsn]++;
        }
    }
}
$pekerjaan_terbesar = $data_pekerjaan;
arsort($pekerjaan_terbesar);
$pekerjaan_dominan = key($pekerjaan_terbesar);
if (empty($pekerjaan_dominan)) $pekerjaan_dominan = '-';

// ==================== DATA PENDIDIKAN (FIXED: CASE INSENSITIVE) ====================
$pendidikan_list = ['TIDAK SEKOLAH', 'SD', 'SMP', 'SMA', 'SMK', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'];
$data_pendidikan = [];
$data_pendidikan_dusun = []; 

foreach ($pendidikan_list as $pend) {
    $data_pendidikan[$pend] = 0;
    $data_pendidikan_dusun[$pend] = ['total' => 0, 'per_dusun' => []];
    foreach ($daftar_dusun as $dsn) $data_pendidikan_dusun[$pend]['per_dusun'][$dsn] = 0;
}

$query_edu = "SELECT pendidikan, dusun FROM penduduk";
$res_edu = mysqli_query($conn, $query_edu);
if($res_edu) {
    while ($row = mysqli_fetch_assoc($res_edu)) {
        $pend = trim(strtoupper($row['pendidikan'])); // Memastikan string seragam (uppercase)
        if(empty($pend) || !in_array($pend, $pendidikan_list)) $pend = 'TIDAK SEKOLAH'; 
        
        $dsn = !empty($row['dusun']) ? $row['dusun'] : 'TANPA DUSUN';
        
        $data_pendidikan[$pend]++;
        $data_pendidikan_dusun[$pend]['total']++;
        if(isset($data_pendidikan_dusun[$pend]['per_dusun'][$dsn])) {
            $data_pendidikan_dusun[$pend]['per_dusun'][$dsn]++;
        }
    }
}

// ==================== AKTIVITAS SURAT TERKINI ====================
$recent_surat = [];
$q_recent = mysqli_query($conn, "SELECT no_surat, jenis_surat, nama_pemohon, tanggal_surat FROM arsip_surat ORDER BY id_surat DESC LIMIT 6");
if($q_recent) {
    while($r = mysqli_fetch_assoc($q_recent)) $recent_surat[] = $r;
}

// ==================== HEADER BUTTONS ====================
$pageHeaderButton = '
<div class="d-flex flex-wrap gap-2">
    <button onclick="window.print()" class="btn btn-sm btn-info shadow-sm text-white rounded-pill px-3">
        <i class="fas fa-print fa-sm text-white-50 me-1"></i> Cetak Tampilan
    </button>
    <a href="export_dashboard.php?type=single" class="btn btn-sm btn-success shadow-sm rounded-pill px-3" target="_blank">
        <i class="fas fa-file-excel fa-sm text-white-50 me-1"></i> 1 Sheet Excel
    </a>
    <a href="export_dashboard.php?type=multiple" class="btn btn-sm btn-primary shadow-sm rounded-pill px-3" target="_blank">
        <i class="fas fa-layer-group fa-sm text-white-50 me-1"></i> Multi Sheet Excel
    </a>
</div>';

ob_start();
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>

<style>
    body { background-color: #f4f7fc; }
    
    .fade-in-up { animation: fadeInUp 0.6s cubic-bezier(0.165, 0.84, 0.44, 1) forwards; opacity: 0; transform: translateY(20px); }
    .delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; } .delay-4 { animation-delay: 0.4s; }
    @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

    .insight-wrapper { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px; }
    .insight-card { flex: 1; min-width: 200px; background: white; border-radius: 15px; padding: 15px 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-left: 4px solid; }
    .ic-1 { border-left-color: #4e73df; } .ic-2 { border-left-color: #1cc88a; } .ic-3 { border-left-color: #f6c23e; } .ic-4 { border-left-color: #858796; }
    .insight-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; justify-content: center; align-items: center; font-size: 1.2rem; color: white;}
    .ic-1 .insight-icon { background: linear-gradient(135deg, #4e73df, #224abe); }
    .ic-2 .insight-icon { background: linear-gradient(135deg, #1cc88a, #13855c); }
    .ic-3 .insight-icon { background: linear-gradient(135deg, #f6c23e, #dda20a); }
    .ic-4 .insight-icon { background: linear-gradient(135deg, #858796, #60616f); }
    .insight-text h6 { margin: 0; font-size: 0.75rem; text-transform: uppercase; color: #858796; font-weight: bold; letter-spacing: 0.5px;}
    .insight-text h4 { margin: 0; font-weight: 800; color: #3a3b45; font-size: 1.1rem; margin-top: 3px;}

    .stat-premium { border-radius: 15px; border: none; padding: 25px; color: white; position: relative; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s; }
    .stat-premium:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
    .bg-grad-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
    .bg-grad-success { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); }
    .bg-grad-info { background: linear-gradient(135deg, #36b9cc 0%, #258391 100%); }
    .bg-grad-danger { background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%); }
    .bg-grad-warning { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); }
    .bg-grad-dark { background: linear-gradient(135deg, #5a5c69 0%, #3a3b45 100%); }
    .stat-premium .bg-icon { position: absolute; right: -10px; bottom: -15px; font-size: 110px; opacity: 0.15; transform: rotate(-15deg); transition: 0.3s;}
    .stat-premium:hover .bg-icon { transform: rotate(0deg) scale(1.1); }
    .stat-premium h6 { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; opacity: 0.9;}
    .stat-premium .value { font-size: 2.5rem; font-weight: 900; line-height: 1; margin-bottom: 0;}
    .stat-premium .small-label { font-size: 0.8rem; opacity: 0.8; font-weight: 600; background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 20px; display: inline-block; margin-top: 10px;}

    .chart-card { background: white; border: none; border-radius: 15px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.08); margin-bottom: 25px; overflow: hidden; display: flex; flex-direction: column; height: calc(100% - 25px);}
    .chart-header { background: #fdfdfe; border-bottom: 1px solid #eaecf4; padding: 1rem 1.25rem; font-weight: 800; color: #4e73df; font-size: 0.9rem; text-transform: uppercase; display: flex; justify-content: space-between; align-items: center;}
    .chart-body { padding: 1.25rem; width: 100%; display: block;}
    .chart-container { position: relative; height: 300px; width: 100%; }

    .table-responsive { max-height: 400px; overflow-y: auto; }
    .table-modern { width: 100%; border-collapse: collapse; margin: 0; font-size: 0.85rem; }
    .table-modern thead th { background-color: #f8f9fc; color: #4e73df; font-weight: 800; padding: 12px; position: sticky; top: 0; z-index: 10; border-bottom: 2px solid #eaecf4; text-align: center; white-space: nowrap;}
    .table-modern thead th:first-child { text-align: left; background-color: #eaecf4; z-index: 11; left: 0; position: sticky;}
    .table-modern tbody td { padding: 10px 12px; border-bottom: 1px solid #f1f3f9; text-align: center; color: #5a5c69; vertical-align: middle;}
    .table-modern tbody td:first-child { text-align: left; font-weight: bold; color: #3a3b45; background-color: #fff; position: sticky; left: 0; z-index: 1; border-right: 1px solid #f1f3f9; white-space: nowrap;}
    .table-modern tbody tr:hover td { background-color: #f8f9fc; }
    
    .btn-export-csv { background: white; color: #1cc88a; border: 1px solid #1cc88a; border-radius: 50px; padding: 3px 12px; font-size: 0.75rem; font-weight: bold; transition: 0.2s;}
    .btn-export-csv:hover { background: #1cc88a; color: white; }

    .filter-card { background: white; border: none; border-radius: 15px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.08); margin-bottom: 30px; padding: 25px; }
    .form-control, .form-select { border-radius: 10px; border: 1px solid #d1d3e2; padding: 0.5rem 1rem !important; font-size: 0.9rem !important; line-height: 1.6 !important; height: auto !important; min-height: 40px !important; background-color: #fdfdfd;}
    .form-select { padding-right: 2.5rem !important; }
    .form-control:focus, .form-select:focus { border-color: #4e73df; box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25); background: white;}
    .btn-rounded { border-radius: 50px !important; font-weight: 600; padding: 0.5rem 1.5rem;}
    
    .result-card { background: white; border-radius: 15px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.08); margin-bottom: 30px; overflow: hidden; display: none; }
    .result-card.show { display: block; animation: fadeIn 0.4s ease; }
    .result-header { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); color: white; padding: 15px 20px; font-weight: bold; font-size: 1rem; display: flex; justify-content: space-between; align-items: center;}
    
    .recent-list { list-style: none; padding: 0; margin: 0; width: 100%;}
    .recent-list li { padding: 12px 10px; border-bottom: 1px dashed #eaecf4; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; border-radius: 8px;}
    .recent-list li:hover { background: #f8f9fc; transform: translateX(5px); }
    .recent-list li:last-child { border-bottom: none; }
    .recent-list .title { font-weight: 800; color: #4e73df; font-size: 0.85rem; text-transform: uppercase;}
    .recent-list .sub { font-size: 0.75rem; color: #858796; }
    .badge-jenis { font-size: 0.65rem; padding: 4px 8px; border-radius: 6px; background: #eaecf4; color: #5a5c69; font-weight: bold;}

    .section-title { font-size: 1.15rem; font-weight: 800; color: #4e73df; margin: 35px 0 20px 0; display: flex; align-items: center; text-transform: uppercase; letter-spacing: 1px;}
    .section-title::after { content: ''; flex-grow: 1; height: 2px; background: #eaecf4; margin-left: 15px; }

    @media print {
        body { background: white; }
        .sidebar, .topbar, .btn-group, .filter-card, .btn-export-csv, #hasilPencarian, .insight-wrapper { display: none !important; }
        .chart-card, .detail-table-wrapper { box-shadow: none !important; border: 1px solid #ccc !important; break-inside: avoid; margin-bottom: 20px;}
        .stat-premium { background: white !important; color: black !important; border: 1px solid #000; box-shadow: none !important;}
        .stat-premium h6, .stat-premium .value, .stat-premium .small-label { color: black !important; background: transparent !important; padding:0;}
        .bg-icon { display: none; }
        .table-modern thead th, .table-modern tbody td:first-child { position: static !important; }
        @page { size: A4 landscape; margin: 1cm; }
    }
</style>

<div class="container-fluid px-0">

    <div class="insight-wrapper fade-in-up">
        <div class="insight-card ic-1">
            <div class="insight-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="insight-text">
                <h6>Dusun Terpadat</h6>
                <h4><?php echo $dusun_terpadat; ?></h4>
            </div>
        </div>
        <div class="insight-card ic-2">
            <div class="insight-icon"><i class="fas fa-users"></i></div>
            <div class="insight-text">
                <h6>Mayoritas Usia</h6>
                <h4><?php echo $mayoritas_umur; ?></h4>
            </div>
        </div>
        <div class="insight-card ic-3">
            <div class="insight-icon"><i class="fas fa-briefcase"></i></div>
            <div class="insight-text">
                <h6>Profesi Utama</h6>
                <h4><?php echo $pekerjaan_dominan; ?></h4>
            </div>
        </div>
        <div class="insight-card ic-4">
            <div class="insight-icon"><i class="far fa-clock"></i></div>
            <div class="insight-text">
                <h6>Waktu Server</h6>
                <h4 id="liveClock">Memuat...</h4>
            </div>
        </div>
    </div>

    <div class="row mb-4 fade-in-up delay-1">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-primary h-100">
                <i class="fas fa-users bg-icon"></i>
                <h6>Total Warga Aktif</h6>
                <div class="value"><?php echo number_format($total_penduduk); ?></div>
                <div class="small-label">Jiwa</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-success h-100">
                <i class="fas fa-address-card bg-icon"></i>
                <h6>Total Keluarga</h6>
                <div class="value"><?php echo number_format($total_kk); ?></div>
                <div class="small-label">Kepala Keluarga</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-info h-100">
                <i class="fas fa-male bg-icon"></i>
                <h6>Laki-Laki</h6>
                <div class="value"><?php echo number_format($total_laki); ?></div>
                <div class="small-label">Jiwa</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-danger h-100">
                <i class="fas fa-female bg-icon"></i>
                <h6>Perempuan</h6>
                <div class="value"><?php echo number_format($total_perempuan); ?></div>
                <div class="small-label">Jiwa</div>
            </div>
        </div>
    </div>

    <div class="section-title fade-in-up delay-1"><i class="fas fa-exchange-alt me-2"></i> Rekapitulasi Mutasi Penduduk</div>
    <div class="row mb-4 fade-in-up delay-2">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-success h-100">
                <i class="fas fa-baby bg-icon"></i>
                <h6>Total Kelahiran</h6>
                <div class="value"><?php echo number_format($total_kelahiran); ?></div>
                <div class="small-label"><i class="fas fa-arrow-up"></i> <?php echo $kelahiran_bln; ?> Bulan Ini</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-dark h-100">
                <i class="fas fa-book-dead bg-icon"></i>
                <h6>Total Kematian</h6>
                <div class="value"><?php echo number_format($total_kematian); ?></div>
                <div class="small-label"><i class="fas fa-arrow-up"></i> <?php echo $kematian_bln; ?> Bulan Ini</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-primary h-100">
                <i class="fas fa-plane-arrival bg-icon"></i>
                <h6>Penduduk Datang</h6>
                <div class="value"><?php echo number_format($total_datang); ?></div>
                <div class="small-label"><i class="fas fa-arrow-up"></i> <?php echo $datang_bln; ?> Bulan Ini</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-warning h-100">
                <i class="fas fa-sign-out-alt bg-icon"></i>
                <h6>Pindah Keluar</h6>
                <div class="value"><?php echo number_format($total_pindah); ?></div>
                <div class="small-label"><i class="fas fa-arrow-up"></i> <?php echo $pindah_bln; ?> Bulan Ini</div>
            </div>
        </div>
    </div>

    <div class="section-title fade-in-up delay-2"><i class="fas fa-chart-pie me-2"></i> Analisis Demografi Visual</div>

    <div class="row fade-in-up delay-3">
        <div class="col-xl-8 col-lg-7">
            <div class="chart-card">
                <div class="chart-header"><span><i class="fas fa-chart-bar me-2"></i> Persebaran Dusun (Gender)</span></div>
                <div class="chart-body"><div class="chart-container"><canvas id="pendudukChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-5">
            <div class="chart-card">
                <div class="chart-header"><span><i class="fas fa-venus-mars me-2"></i> Rasio Gender Global</span></div>
                <div class="chart-body"><div class="chart-container"><canvas id="genderRatioChart"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="row fade-in-up delay-3">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header"><span><i class="fas fa-home me-2"></i> Sebaran Kartu Keluarga per Dusun</span></div>
                <div class="chart-body"><div class="chart-container"><canvas id="kkChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header"><span><i class="fas fa-birthday-cake me-2"></i> Piramida Rentang Usia</span></div>
                <div class="chart-body"><div class="chart-container"><canvas id="umurChart"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="row fade-in-up delay-4">
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header"><span><i class="fas fa-rings me-2"></i> Status Perkawinan</span></div>
                <div class="chart-body"><div class="chart-container" style="height: 250px;"><canvas id="statusChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header"><span><i class="fas fa-praying-hands me-2"></i> Keyakinan Agama</span></div>
                <div class="chart-body"><div class="chart-container" style="height: 250px;"><canvas id="agamaChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header"><span><i class="fas fa-briefcase me-2"></i> Profil Pekerjaan</span></div>
                <div class="chart-body"><div class="chart-container" style="height: 250px;"><canvas id="pekerjaanChart"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="row fade-in-up delay-4">
        <div class="col-xl-8 col-lg-7">
            <div class="chart-card">
                <div class="chart-header"><span><i class="fas fa-graduation-cap me-2"></i> Tingkat Pendidikan Penduduk</span></div>
                <div class="chart-body"><div class="chart-container" style="height: 250px;"><canvas id="pendidikanChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-5">
            <div class="chart-card">
                <div class="chart-header">
                    <span><i class="fas fa-history me-2"></i> Log Surat Terkini</span>
                    <a href="surat_keluar.php" class="text-xs text-primary" style="text-decoration:none; font-weight:bold;">LIHAT SEMUA</a>
                </div>
                <div class="chart-body" style="align-items: stretch; justify-content: flex-start; overflow-y: auto; height: 290px; padding: 10px 20px;">
                    <?php if(empty($recent_surat)): ?>
                        <div class="text-center text-muted mt-5"><i class="fas fa-folder-open mb-2 fa-2x"></i><br>Belum ada surat keluar.</div>
                    <?php else: ?>
                        <ul class="recent-list">
                            <?php foreach($recent_surat as $rs): ?>
                                <li>
                                    <div>
                                        <div class="title"><?php echo htmlspecialchars($rs['nama_pemohon']); ?></div>
                                        <div class="sub"><?php echo tgl_indonesia($rs['tanggal_surat']); ?> • <?php echo htmlspecialchars($rs['no_surat']); ?></div>
                                    </div>
                                    <span class="badge-jenis border"><?php echo htmlspecialchars($rs['jenis_surat']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="section-title fade-in-up delay-4"><i class="fas fa-table me-2"></i> Rincian Tabular Silang per Dusun</div>

    <div class="chart-card detail-table-wrapper fade-in-up delay-4" style="height: auto;">
        <div class="chart-header bg-white border-bottom-0">
            <span><i class="fas fa-th-list me-2"></i> Matriks Rentang Umur per Dusun</span>
            <button class="btn-export-csv" onclick="downloadTableCSV('tabelUmurDusun', 'Statistik_Umur_Dusun.csv')"><i class="fas fa-file-csv me-1"></i> Unduh CSV</button>
        </div>
        <div class="table-responsive pb-2">
            <table class="table-modern" id="tabelUmurDusun">
                <thead>
                    <tr>
                        <th>Kategori Umur</th>
                        <th class="bg-primary text-white text-center">TOTAL</th>
                        <?php foreach ($daftar_dusun as $dusun): ?><th><?php echo $dusun; ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentang_umur as $rentang => $range): ?>
                    <tr>
                        <td><?php echo $rentang; ?></td>
                        <td class="font-weight-bold text-primary fs-6"><?php echo isset($data_umur[$rentang]['total']) ? $data_umur[$rentang]['total'] : 0; ?></td>
                        <?php foreach ($daftar_dusun as $dusun): ?>
                        <td><?php echo isset($data_umur[$rentang]['per_dusun'][$dusun]) ? $data_umur[$rentang]['per_dusun'][$dusun] : 0; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="chart-card detail-table-wrapper fade-in-up delay-4" style="height: auto;">
        <div class="chart-header bg-white border-bottom-0">
            <span><i class="fas fa-th-list me-2"></i> Matriks Tingkat Pendidikan per Dusun</span>
            <button class="btn-export-csv" onclick="downloadTableCSV('tabelPendidikanDusun', 'Statistik_Pendidikan_Dusun.csv')"><i class="fas fa-file-csv me-1"></i> Unduh CSV</button>
        </div>
        <div class="table-responsive pb-2">
            <table class="table-modern" id="tabelPendidikanDusun">
                <thead>
                    <tr>
                        <th>Tingkat Pendidikan</th>
                        <th class="bg-primary text-white text-center">TOTAL</th>
                        <?php foreach ($daftar_dusun as $dusun): ?><th><?php echo $dusun; ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendidikan_list as $pend): ?>
                    <tr>
                        <td><?php echo $pend; ?></td>
                        <td class="font-weight-bold text-primary fs-6"><?php echo isset($data_pendidikan_dusun[$pend]['total']) ? $data_pendidikan_dusun[$pend]['total'] : 0; ?></td>
                        <?php foreach ($daftar_dusun as $dusun): ?>
                        <td><?php echo isset($data_pendidikan_dusun[$pend]['per_dusun'][$dusun]) ? $data_pendidikan_dusun[$pend]['per_dusun'][$dusun] : 0; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="chart-card detail-table-wrapper fade-in-up delay-4" style="height: auto;">
        <div class="chart-header bg-white border-bottom-0">
            <span><i class="fas fa-th-list me-2"></i> Matriks Profesi / Pekerjaan per Dusun</span>
            <button class="btn-export-csv" onclick="downloadTableCSV('tabelPekerjaanDusun', 'Statistik_Pekerjaan_Dusun.csv')"><i class="fas fa-file-csv me-1"></i> Unduh CSV</button>
        </div>
        <div class="table-responsive pb-2">
            <table class="table-modern" id="tabelPekerjaanDusun">
                <thead>
                    <tr>
                        <th>Jenis Pekerjaan</th>
                        <th class="bg-primary text-white text-center">TOTAL</th>
                        <?php foreach ($daftar_dusun as $dusun): ?><th><?php echo $dusun; ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $pkj_list_full = $pekerjaan_list;
                    $pkj_list_full[] = 'Lainnya';
                    foreach ($pkj_list_full as $pkj): ?>
                    <tr>
                        <td><?php echo $pkj; ?></td>
                        <td class="font-weight-bold text-primary fs-6"><?php echo isset($data_pekerjaan_dusun[$pkj]['total']) ? $data_pekerjaan_dusun[$pkj]['total'] : 0; ?></td>
                        <?php foreach ($daftar_dusun as $dusun): ?>
                        <td><?php echo isset($data_pekerjaan_dusun[$pkj]['per_dusun'][$dusun]) ? $data_pekerjaan_dusun[$pkj]['per_dusun'][$dusun] : 0; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-title fade-in-up delay-4"><i class="fas fa-search-plus me-2"></i> Pencarian Data Lanjutan</div>

    <div class="filter-card fade-in-up delay-4">
        <div style="background: #e3f2fd; border-left: 4px solid #4e73df; padding: 12px 20px; border-radius: 10px; margin-bottom: 25px; color: #224abe; font-size: 0.9rem; display: flex; align-items: center;">
            <i class="fas fa-info-circle fa-2x me-3"></i> 
            <div>
                <strong>Smart Filter (AJAX)</strong><br>
                Filter data spesifik penduduk berdasarkan kriteria. Hasil akan dimuat seketika di bawah form ini tanpa *reload* halaman.
            </div>
        </div>

        <form id="filterForm">
            <div class="row g-3">
                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Dusun</label>
                    <select class="form-control" name="dusun" id="f_dusun">
                        <option value="all">Semua Dusun</option>
                        <?php foreach ($daftar_dusun as $dusun): ?><option value="<?php echo $dusun; ?>"><?php echo $dusun; ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Usia Spesifik</label>
                    <input type="number" class="form-control" name="umur" id="f_umur" min="0" max="150" placeholder="Kosong = Semua">
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Jenis Kelamin</label>
                    <select class="form-control" name="jk" id="f_jk">
                        <option value="all">Semua Gender</option><option value="LAKI-LAKI">Laki-laki</option><option value="PEREMPUAN">Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Status Kawin</label>
                    <select class="form-control" name="status" id="f_status">
                        <option value="all">Semua Status</option>
                        <option value="Belum Kawin">Belum Kawin</option><option value="Kawin">Kawin</option><option value="Cerai Hidup">Cerai Hidup</option><option value="Cerai Mati">Cerai Mati</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Pekerjaan</label>
                    <select class="form-control" name="pekerjaan" id="f_pekerjaan">
                        <option value="all">Semua Pekerjaan</option>
                        <?php foreach ($pekerjaan_list as $pkj): ?><option value="<?php echo $pkj; ?>"><?php echo $pkj; ?></option><?php endforeach; ?>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 text-center border-top pt-4">
                <button type="button" class="btn btn-light border text-muted btn-rounded me-2" id="resetBtn"><i class="fas fa-undo me-1"></i> Bersihkan Form</button>
                <button type="button" class="btn btn-primary btn-rounded shadow-sm px-5" id="searchBtn"><i class="fas fa-search me-1"></i> Tampilkan Data</button>
            </div>
        </form>
    </div>

    <div class="result-card" id="hasilPencarian">
        <div class="result-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-clipboard-list me-2"></i> Hasil Filter Database</span>
            <span class="badge bg-white text-success px-3 py-1 rounded-pill shadow-sm" id="resultCounter">0 Data</span>
        </div>
        <div class="card-body p-0">
            <div class="m-3 p-3 bg-light rounded" id="infoSummary" style="border-left: 4px solid #1cc88a; font-size: 0.85rem;"></div>
            
            <div class="text-center py-5" id="loadingSpinner" style="display: none;">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                <p class="mt-3 text-muted font-weight-bold">Menyortir Data...</p>
            </div>
            
            <div class="table-responsive pb-3 px-3" id="tableContainer"></div>
        </div>
    </div>

</div>

<script>
function updateClock() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    const clockEl = document.getElementById('liveClock');
    if(clockEl) clockEl.textContent = now.toLocaleDateString('id-ID', options);
}
setInterval(updateClock, 1000); updateClock();

function downloadTableCSV(table_id, filename) {
    var csv = [];
    var rows = document.querySelectorAll("table#" + table_id + " tr");
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++) row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        csv.push(row.join(","));
    }
    var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

document.addEventListener('DOMContentLoaded', function() {
    
    if(typeof Chart === 'undefined') {
        console.error("Chart.js gagal dimuat! Periksa koneksi internet atau CDN.");
        return; 
    }

    Chart.defaults.global.defaultFontFamily = "'Nunito', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'";
    Chart.defaults.global.defaultFontColor = '#858796';

    const dusun = <?php echo json_encode($dusun_labels) ?: '[]'; ?>;
    const lakiPerDusun = <?php echo json_encode($laki_per_dusun) ?: '[]'; ?>;
    const perempuanPerDusun = <?php echo json_encode($perempuan_per_dusun) ?: '[]'; ?>;
    const kkPerDusun = <?php echo json_encode($kk_per_dusun) ?: '[]'; ?>;
    
    const rentangUmur = <?php echo json_encode(array_keys($data_umur)) ?: '[]'; ?>;
    const jumlahUmur = <?php echo json_encode(array_column($data_umur, 'total')) ?: '[]'; ?>;
    
    const statusLabels = <?php echo json_encode(array_keys($data_status)) ?: '[]'; ?>;
    const statusJumlah = <?php echo json_encode(array_values($data_status)) ?: '[]'; ?>;
    
    const pekerjaanLabels = <?php echo json_encode(array_keys($data_pekerjaan)) ?: '[]'; ?>;
    const pekerjaanJumlah = <?php echo json_encode(array_values($data_pekerjaan)) ?: '[]'; ?>;
    
    const agamaLabels = <?php echo json_encode(array_keys($data_agama)) ?: '[]'; ?>;
    const agamaJumlah = <?php echo json_encode(array_values($data_agama)) ?: '[]'; ?>;
    
    const pendidikanLabels = <?php echo json_encode(array_keys($data_pendidikan)) ?: '[]'; ?>;
    const pendidikanJumlah = <?php echo json_encode(array_values($data_pendidikan)) ?: '[]'; ?>;

    function renderChartSafely(id, config) {
        const canvas = document.getElementById(id);
        if(!canvas) return;
        try { new Chart(canvas, config); } catch(e) { console.error("Chart Error rendering " + id, e); }
    }

    renderChartSafely('pendudukChart', {
        type: 'bar',
        data: { labels: dusun, datasets: [{label: 'Laki-laki', data: lakiPerDusun, backgroundColor: '#4e73df'}, {label: 'Perempuan', data: perempuanPerDusun, backgroundColor: '#e74a3b'}] },
        options: { responsive: true, maintainAspectRatio: false, legend: {display: true}, scales: { xAxes: [{ gridLines: { display: false } }], yAxes: [{ ticks: { beginAtZero: true } }] } }
    });

    renderChartSafely('genderRatioChart', {
        type: 'doughnut', 
        data: { labels: ['Laki-laki', 'Perempuan'], datasets: [{ data: [<?php echo (int)$total_laki; ?>, <?php echo (int)$total_perempuan; ?>], backgroundColor: ['#4e73df', '#e74a3b'], hoverBorderColor: '#fff', borderWidth: 2 }] },
        options: { maintainAspectRatio: false, cutoutPercentage: 70, legend: { position: 'bottom' } }
    });

    renderChartSafely('kkChart', {
        type: 'bar', 
        data: { labels: dusun, datasets: [{label: 'Jumlah Kartu Keluarga', data: kkPerDusun, backgroundColor: '#1cc88a'}] },
        options: { maintainAspectRatio: false, legend: { display: false }, scales: { xAxes: [{ gridLines: {display:false} }], yAxes: [{ ticks: {beginAtZero: true}}] } }
    });

    renderChartSafely('umurChart', {
        type: 'bar', 
        data: { labels: rentangUmur, datasets: [{label: 'Jiwa', data: jumlahUmur, backgroundColor: '#f6c23e'}] },
        options: { maintainAspectRatio: false, legend: { display: false }, scales: { xAxes: [{ gridLines: {display:false} }], yAxes: [{ ticks: {beginAtZero: true}}] } }
    });

    renderChartSafely('statusChart', {
        type: 'pie', 
        data: { labels: statusLabels, datasets: [{ data: statusJumlah, backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b'], borderWidth: 2, hoverBorderColor: '#fff' }] },
        options: { maintainAspectRatio: false, legend: { position: 'right' } }
    });

    renderChartSafely('pekerjaanChart', {
        type: 'horizontalBar', 
        data: { labels: pekerjaanLabels, datasets: [{label: 'Orang', data: pekerjaanJumlah, backgroundColor: '#858796'}] },
        options: { maintainAspectRatio: false, legend: { display: false }, scales: { xAxes: [{ ticks: {beginAtZero: true} }], yAxes: [{ gridLines: {display:false} }] } }
    });

    renderChartSafely('agamaChart', {
        type: 'doughnut', 
        data: { labels: agamaLabels, datasets: [{ data: agamaJumlah, backgroundColor: ['#1cc88a', '#4e73df', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'], borderWidth: 2 }] },
        options: { maintainAspectRatio: false, cutoutPercentage: 60, legend: { position: 'right' } }
    });

    renderChartSafely('pendidikanChart', {
        type: 'bar', 
        data: { labels: pendidikanLabels, datasets: [{label: 'Penduduk', data: pendidikanJumlah, backgroundColor: '#4e73df'}] },
        options: { maintainAspectRatio: false, legend: { display: false }, scales: { xAxes: [{ gridLines: {display:false} }], yAxes: [{ ticks: {beginAtZero: true}}] } }
    });

    const searchBtn = document.getElementById('searchBtn');
    const resetBtn = document.getElementById('resetBtn');
    const hasilCard = document.getElementById('hasilPencarian');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const tableContainer = document.getElementById('tableContainer');
    const resCounter = document.getElementById('resultCounter');
    
    function searchData() {
        const d = document.getElementById('f_dusun').value;
        const u = document.getElementById('f_umur').value;
        const j = document.getElementById('f_jk').value;
        const s = document.getElementById('f_status').value;
        const p = document.getElementById('f_pekerjaan').value;
        
        tableContainer.innerHTML = ''; 
        hasilCard.style.display = 'block'; 
        loadingSpinner.style.display = 'block';
        
        const formData = new FormData();
        formData.append('ajax_search', '1');
        formData.append('dusun', d); formData.append('umur', u); formData.append('jk', j); 
        formData.append('status', s); formData.append('pekerjaan', p);
        
        fetch('dashboard.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            loadingSpinner.style.display = 'none'; 
            resCounter.textContent = data.total + ' Ditemukan';
            
            let c = [];
            if (d!=='all') c.push(`Dusun: ${d}`); if (u) c.push(`Usia: ${u} thn`);
            if (j!=='all') c.push(`Gender: ${j}`); if (s!=='all') c.push(`Status: ${s}`); if (p!=='all') c.push(`Kerja: ${p}`);
            document.getElementById('infoSummary').innerHTML = c.length > 0 ? "<b>Filter Aktif:</b> " + c.join(' &bull; ') : "<b>Filter Aktif:</b> Semua Penduduk Desa";
            
            if (data.total > 0) {
                let html = `<table class="table-modern"><thead><tr><th>No</th><th>Nama Lengkap</th><th>NIK</th><th>Dusun</th><th>Usia</th><th>Pekerjaan</th><th>Keluarga</th></tr></thead><tbody>`;
                data.data.forEach((i, idx) => {
                    let badge = '<span style="background:#e3e6f0; color:#858796; padding:4px 10px; border-radius:12px; font-size:0.7rem; font-weight:bold;">MANDIRI</span>';
                    if (i.is_kepala > 0) badge = '<span style="background:#fef0c7; color:#f6c23e; padding:4px 10px; border-radius:12px; font-size:0.7rem; border: 1px solid #f6c23e; font-weight:bold;"><i class="fas fa-crown"></i> KEPALA KK</span>';
                    else if (i.is_anggota > 0) badge = '<span style="background:#e0f2f4; color:#36b9cc; padding:4px 10px; border-radius:12px; font-size:0.7rem; border: 1px solid #36b9cc; font-weight:bold;"><i class="fas fa-user"></i> ANGGOTA</span>';
                    
                    html += `<tr><td class="text-muted text-center">${idx+1}</td><td class="text-uppercase text-gray-800 font-weight-bold">${i.nama_penduduk}</td><td class="font-family-monospace text-primary">${i.nik}</td><td class="font-weight-bold">${i.dusun||'-'}</td><td>${i.umur} Thn</td><td class="text-uppercase">${i.pekerjaan||'-'}</td><td>${badge}</td></tr>`;
                });
                html += '</tbody></table>'; tableContainer.innerHTML = html;
            } else { tableContainer.innerHTML = `<div class="text-center py-5"><i class="fas fa-search-minus fa-4x text-gray-300 mb-3"></i><p class="text-muted">Pencarian Tidak Membuahkan Hasil.</p></div>`; }
            
            hasilCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }).catch(err => { loadingSpinner.style.display = 'none'; alert('Gagal memuat data pencarian dari server.'); });
    }
    
    searchBtn.addEventListener('click', e => { e.preventDefault(); searchData(); });
    resetBtn.addEventListener('click', e => { 
        e.preventDefault(); document.getElementById('filterForm').reset();
        hasilCard.style.display = 'none'; 
    });
    document.getElementById('f_umur').addEventListener('keypress', e => { if (e.key === 'Enter') { e.preventDefault(); searchData(); } });
});
</script>

<?php
$content = ob_get_clean();
include '../includes/base.php';
?>