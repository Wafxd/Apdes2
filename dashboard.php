<?php
session_start();

include "db/koneksi.php";
include "db/funct.php";

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_admin'];
$username = $_SESSION["nama_admin"];

// Daftar dusun
$daftar_dusun = [
    'KEJAWAN',
    'SEPURAN',
    'BUDDAN',
    'PASEREAN',
    'LANGGAR',
    'MORLEKE',
    'PREGIH',
    'KARANG PANDAN',
    'PONG BARU',
    'KRASAK',
    'PERUM BASMALAH'
];

// ==================== FUNGSI HITUNG UMUR ====================
function hitungUmur($tanggal_lahir) {
    if (empty($tanggal_lahir)) return 0;
    $birthDate = new DateTime($tanggal_lahir);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    return $age;
}

// ==================== STATISTIK UMUM ====================
// Total penduduk
$result_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM penduduk");
$total_penduduk = mysqli_fetch_assoc($result_total)['total'];

// Total KK
$result_total_kk = mysqli_query($conn, "SELECT COUNT(*) as total FROM kartu_keluarga");
$total_kk = mysqli_fetch_assoc($result_total_kk)['total'];

// Total Laki-laki
$result_laki = mysqli_query($conn, "SELECT COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'LAKI-LAKI'");
$total_laki = mysqli_fetch_assoc($result_laki)['total'];

// Total Perempuan
$result_perempuan = mysqli_query($conn, "SELECT COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'PEREMPUAN'");
$total_perempuan = mysqli_fetch_assoc($result_perempuan)['total'];

// Total Surat Keluar
$result_surat = mysqli_query($conn, "SELECT COUNT(*) as total FROM arsip_surat");
$total_surat = mysqli_fetch_assoc($result_surat)['total'];

// ==================== DATA PER DUSUN ====================
$dusun_labels = [];
$laki_per_dusun = [];
$perempuan_per_dusun = [];
$total_per_dusun = [];
$kk_per_dusun = [];

foreach ($daftar_dusun as $dusun) {
    $dusun_escape = mysqli_real_escape_string($conn, $dusun);
    
    // Total penduduk per dusun
    $query_total_dusun = "SELECT COUNT(*) as total FROM penduduk WHERE dusun = '$dusun_escape'";
    $result_total_dusun = mysqli_query($conn, $query_total_dusun);
    $total_dusun = mysqli_fetch_assoc($result_total_dusun)['total'];
    
    // Laki-laki per dusun
    $query_laki_dusun = "SELECT COUNT(*) as total FROM penduduk WHERE dusun = '$dusun_escape' AND jenis_kelamin = 'LAKI-LAKI'";
    $result_laki_dusun = mysqli_query($conn, $query_laki_dusun);
    $laki_dusun = mysqli_fetch_assoc($result_laki_dusun)['total'];
    
    // Perempuan per dusun
    $query_perempuan_dusun = "SELECT COUNT(*) as total FROM penduduk WHERE dusun = '$dusun_escape' AND jenis_kelamin = 'PEREMPUAN'";
    $result_perempuan_dusun = mysqli_query($conn, $query_perempuan_dusun);
    $perempuan_dusun = mysqli_fetch_assoc($result_perempuan_dusun)['total'];
    
    // KK per dusun
    $query_kk_dusun = "SELECT COUNT(*) as total FROM kartu_keluarga WHERE dusun = '$dusun_escape'";
    $result_kk_dusun = mysqli_query($conn, $query_kk_dusun);
    $kk_dusun = mysqli_fetch_assoc($result_kk_dusun)['total'];
    
    $dusun_labels[] = $dusun;
    $laki_per_dusun[] = (int)$laki_dusun;
    $perempuan_per_dusun[] = (int)$perempuan_dusun;
    $total_per_dusun[] = (int)$total_dusun;
    $kk_per_dusun[] = (int)$kk_dusun;
}

// ==================== DATA UMUR ====================
$rentang_umur = [
    '0-5' => [0, 5],
    '6-12' => [6, 12],
    '13-17' => [13, 17],
    '18-25' => [18, 25],
    '26-35' => [26, 35],
    '36-45' => [36, 45],
    '46-55' => [46, 55],
    '56-65' => [56, 65],
    '65+' => [65, 150]
];

$data_umur = [];
$query_penduduk_umur = "SELECT nik, tanggal_lahir, dusun FROM penduduk";
$result_penduduk_umur = mysqli_query($conn, $query_penduduk_umur);

while ($row = mysqli_fetch_assoc($result_penduduk_umur)) {
    $umur_penduduk = hitungUmur($row['tanggal_lahir']);
    $dusun_penduduk = $row['dusun'] ?: 'TANPA DUSUN';
    
    foreach ($rentang_umur as $rentang => $range) {
        if ($umur_penduduk >= $range[0] && $umur_penduduk <= $range[1]) {
            if (!isset($data_umur[$rentang])) {
                $data_umur[$rentang] = ['total' => 0, 'per_dusun' => []];
            }
            $data_umur[$rentang]['total']++;
            
            if (!isset($data_umur[$rentang]['per_dusun'][$dusun_penduduk])) {
                $data_umur[$rentang]['per_dusun'][$dusun_penduduk] = 0;
            }
            $data_umur[$rentang]['per_dusun'][$dusun_penduduk]++;
            break;
        }
    }
}

// ==================== DATA STATUS KAWIN ====================
$status_kawin_list = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
$data_status = [];

foreach ($status_kawin_list as $status) {
    $status_escape = mysqli_real_escape_string($conn, $status);
    $query_status = "SELECT COUNT(*) as total FROM penduduk WHERE status_kawin = '$status_escape'";
    $result_status = mysqli_query($conn, $query_status);
    $data_status[$status] = mysqli_fetch_assoc($result_status)['total'];
}

// ==================== DATA PEKERJAAN ====================
$pekerjaan_list = [
    'PNS', 'TNI', 'POLRI', 'PEGAWAI SWASTA', 'WIRASWASTA', 
    'PETANI', 'BURUH', 'PELAJAR/MAHASISWA', 'IRT', 'PENSIUNAN'
];

$data_pekerjaan = [];
foreach ($pekerjaan_list as $pekerjaan) {
    $pekerjaan_escape = mysqli_real_escape_string($conn, $pekerjaan);
    $query_pekerjaan = "SELECT COUNT(*) as total FROM penduduk WHERE pekerjaan = '$pekerjaan_escape'";
    $result_pekerjaan = mysqli_query($conn, $query_pekerjaan);
    $data_pekerjaan[$pekerjaan] = mysqli_fetch_assoc($result_pekerjaan)['total'];
}

$query_lainnya = "SELECT COUNT(*) as total FROM penduduk WHERE pekerjaan NOT IN ('" . implode("','", $pekerjaan_list) . "') OR pekerjaan IS NULL OR pekerjaan = ''";
$result_lainnya = mysqli_query($conn, $query_lainnya);
$data_pekerjaan['Lainnya'] = mysqli_fetch_assoc($result_lainnya)['total'];

// ==================== DATA AGAMA ====================
$agama_list = ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDDHA', 'KONGHUCU'];
$data_agama = [];

foreach ($agama_list as $agama) {
    $agama_escape = mysqli_real_escape_string($conn, $agama);
    $query_agama = "SELECT COUNT(*) as total FROM penduduk WHERE agama = '$agama_escape'";
    $result_agama = mysqli_query($conn, $query_agama);
    $data_agama[$agama] = mysqli_fetch_assoc($result_agama)['total'];
}

// ==================== DATA PENDIDIKAN ====================
$pendidikan_list = ['TIDAK SEKOLAH', 'SD', 'SMP', 'SMA', 'SMK', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'];
$data_pendidikan = [];

foreach ($pendidikan_list as $pendidikan) {
    $pendidikan_escape = mysqli_real_escape_string($conn, $pendidikan);
    $query_pendidikan = "SELECT COUNT(*) as total FROM penduduk WHERE pendidikan = '$pendidikan_escape'";
    $result_pendidikan = mysqli_query($conn, $query_pendidikan);
    $data_pendidikan[$pendidikan] = mysqli_fetch_assoc($result_pendidikan)['total'];
}

// ==================== PAGE HEADER BUTTON (2 TOMBOL TERPISAH) ====================
$pageHeaderButton = '
<div class="btn-group" role="group">
    <a href="export_dashboard.php?type=single" class="btn btn-sm btn-success shadow-sm" target="_blank">
        <i class="fas fa-file-excel fa-sm text-white-50"></i> Export Single Sheet
    </a>
    <a href="export_dashboard.php?type=multiple" class="btn btn-sm btn-primary shadow-sm" target="_blank">
        <i class="fas fa-file-excel fa-sm text-white-50"></i> Export Multiple Sheet
    </a>
</div>';

ob_start();
?>

<style>
    /* Card statistik */
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        border-left: 4px solid #4e73df;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }
    .stat-card h6 {
        color: #5a5c69;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 5px;
        text-transform: uppercase;
    }
    .stat-card .value {
        font-size: 28px;
        font-weight: 700;
        color: #2c3e50;
    }

    /* Filter card */
    .filter-card {
        background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        margin-top: 30px;
        margin-bottom: 30px;
        padding: 25px;
    }
    .filter-card h5 {
        color: #4e73df;
        font-weight: 600;
        margin-bottom: 20px;
        border-bottom: 2px solid #4e73df;
        padding-bottom: 10px;
    }
    .filter-table {
        width: 100%;
        border-collapse: collapse;
    }
    .filter-table td {
        padding: 10px 15px;
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
    .filter-table td .form-control, 
    .filter-table td .form-select {
        border-radius: 8px;
        border: 1px solid #d1d3e2;
        padding: 0.5rem 0.75rem;
        width: 100%;
    }
    .filter-table td .form-control:focus, 
    .filter-table td .form-select:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    .btn-filter {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.6rem 2rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-filter:hover {
        background: linear-gradient(135deg, #2e59d9 0%, #1a3a9e 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .btn-reset {
        background: #858796;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.6rem 2rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-reset:hover {
        background: #6b6d7d;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Result card */
    .result-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        overflow: hidden;
        display: none;
    }
    .result-card.show {
        display: block;
    }
    .result-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
    }
    .result-header i {
        margin-right: 10px;
    }
    .result-body {
        padding: 20px;
    }
    .info-summary {
        background: #f8f9fc;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        border-left: 4px solid #1cc88a;
    }
    .info-summary .total {
        font-size: 24px;
        font-weight: 700;
        color: #1cc88a;
    }

    /* Table tanpa border */
    .table-borderless-custom {
        width: 100%;
        border-collapse: collapse;
    }
    .table-borderless-custom th {
        text-align: left;
        padding: 12px 10px;
        background-color: #f8f9fc;
        color: #4e73df;
        font-weight: 600;
        border-bottom: 2px solid #4e73df;
    }
    .table-borderless-custom td {
        padding: 12px 10px;
        border-bottom: 1px solid #e3e6f0;
        vertical-align: middle;
    }
    .table-borderless-custom tr:last-child td {
        border-bottom: none;
    }
    .table-borderless-custom tr:hover td {
        background-color: #f8f9fc;
    }

    /* Chart cards */
    .chart-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        overflow: hidden;
    }
    .chart-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
    }
    .chart-header i {
        margin-right: 10px;
    }
    .chart-body {
        padding: 20px;
    }
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* Badge */
    .badge-status {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    .badge-kepala {
        background-color: #fff3cd;
        color: #856404;
    }
    .badge-anggota {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    .badge-mandiri {
        background-color: #e2e3e5;
        color: #383d41;
    }

    /* Section divider */
    .section-divider {
        margin: 20px 0 30px;
        text-align: center;
        position: relative;
    }
    .section-divider h3 {
        background: white;
        display: inline-block;
        padding: 0 20px;
        color: #4e73df;
        font-weight: 700;
        position: relative;
        z-index: 2;
    }
    .section-divider:before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, #4e73df, transparent);
        z-index: 1;
    }

    /* Filter note */
    .filter-note {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        color: #856404;
    }
    .filter-note i {
        margin-right: 8px;
    }

    /* Loading spinner */
    .loading-spinner {
        display: none;
        text-align: center;
        padding: 30px;
    }
    .loading-spinner.show {
        display: block;
    }
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #4e73df;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        color: #d1d3e2;
    }
    .empty-state h5 {
        font-size: 18px;
        margin-bottom: 10px;
        color: #5a5c69;
    }

    /* Button export */
    .btn-group .btn {
        margin-right: 5px;
        border-radius: 4px !important;
    }
    .btn-group .btn:last-child {
        margin-right: 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-table td {
            display: block;
            width: 100%;
            padding: 5px;
        }
        .filter-table td.separator {
            display: none;
        }
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .btn-group .btn {
            margin-right: 0;
        }
    }
</style>

<div class="container-fluid">
    <!-- Statistik Umum -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <h6>Total Penduduk</h6>
                <div class="value"><?php echo number_format($total_penduduk); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <h6>Total KK</h6>
                <div class="value"><?php echo number_format($total_kk); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <h6>Laki-laki</h6>
                <div class="value"><?php echo number_format($total_laki); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <h6>Perempuan</h6>
                <div class="value"><?php echo number_format($total_perempuan); ?></div>
            </div>
        </div>
    </div>

    <!-- ==================== BAGIAN STATISTIK LENGKAP ==================== -->
    <div class="section-divider">
        <h3>STATISTIK LENGKAP PENDUDUK</h3>
    </div>

    <!-- Chart 1: Perbandingan Penduduk per Dusun -->
    <div class="chart-card">
        <div class="chart-header">
            <i class="fas fa-chart-bar"></i> Perbandingan Jumlah Penduduk per Dusun (Laki-laki vs Perempuan)
        </div>
        <div class="chart-body">
            <div class="chart-container">
                <canvas id="pendudukChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart Row 2 -->
    <div class="row">
        <!-- Rasio Gender -->
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-pie"></i> Rasio Gender Seluruh Dusun
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="genderRatioChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- KK per Dusun -->
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-bar"></i> Kartu Keluarga per Dusun
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="kkChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Statistik Umur -->
    <div class="row">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-bar"></i> Distribusi Umur Penduduk
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="umurChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-pie"></i> Status Perkawinan
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 4: Statistik Pekerjaan dan Agama -->
    <div class="row">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-briefcase"></i> Pekerjaan Penduduk
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="pekerjaanChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-church"></i> Agama
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="agamaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 5: Statistik Pendidikan -->
    <div class="row">
        <div class="col-lg-12">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-graduation-cap"></i> Tingkat Pendidikan
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="pendidikanChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Data Umur per Dusun -->
    <div class="chart-card">
        <div class="chart-header">
            <i class="fas fa-table"></i> Detail Distribusi Umur per Dusun
        </div>
        <div class="chart-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Rentang Umur</th>
                            <th>Total</th>
                            <?php foreach ($daftar_dusun as $dusun): ?>
                            <th><?php echo $dusun; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rentang_umur as $rentang => $range): ?>
                        <tr>
                            <td><strong><?php echo $rentang; ?> tahun</strong></td>
                            <td><?php echo isset($data_umur[$rentang]['total']) ? $data_umur[$rentang]['total'] : 0; ?></td>
                            <?php foreach ($daftar_dusun as $dusun): ?>
                            <td>
                                <?php 
                                $jumlah = isset($data_umur[$rentang]['per_dusun'][$dusun]) ? $data_umur[$rentang]['per_dusun'][$dusun] : 0;
                                echo $jumlah;
                                ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Total Surat Keluar -->
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="stat-card" style="border-left-color: #f6c23e;">
                <h6>Total Surat Keluar</h6>
                <div class="value"><?php echo number_format($total_surat); ?></div>
            </div>
        </div>
    </div>

    <!-- ==================== BAGIAN FILTER PENCARIAN ==================== -->
    <div class="section-divider mt-5">
        <h3>FILTER PENCARIAN DATA PENDUDUK</h3>
    </div>

    <!-- Filter Card dengan Tabel Tanpa Border -->
    <div class="filter-card">
        <h5><i class="fas fa-search me-2"></i>Cari Data Penduduk</h5>
        
        <!-- Filter note -->
        <div class="filter-note">
            <i class="fas fa-info-circle"></i> 
            Gunakan filter di bawah ini untuk mencari data penduduk. Hasil pencarian akan muncul di bawah tanpa refresh halaman.
        </div>

        <form id="filterForm">
            <table class="filter-table">
                <tr>
                    <td class="label">Dusun</td>
                    <td class="separator">:</td>
                    <td style="width: 25%;">
                        <select class="form-select" name="dusun" id="dusun">
                            <option value="all">Semua Dusun</option>
                            <?php foreach ($daftar_dusun as $dusun): ?>
                            <option value="<?php echo $dusun; ?>"><?php echo $dusun; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="label">Umur</td>
                    <td class="separator">:</td>
                    <td style="width: 25%;">
                        <input type="number" class="form-control" name="umur" id="umur" min="0" max="150" placeholder="Contoh: 25">
                        <small class="text-muted">Kosongkan untuk semua umur</small>
                    </td>
                </tr>
                <tr>
                    <td class="label">Jenis Kelamin</td>
                    <td class="separator">:</td>
                    <td>
                        <select class="form-select" name="jk" id="jk">
                            <option value="all">Semua</option>
                            <option value="LAKI-LAKI">Laki-laki</option>
                            <option value="PEREMPUAN">Perempuan</option>
                        </select>
                    </td>
                    <td class="label">Status Kawin</td>
                    <td class="separator">:</td>
                    <td>
                        <select class="form-select" name="status" id="status">
                            <option value="all">Semua</option>
                            <option value="Belum Kawin">Belum Kawin</option>
                            <option value="Kawin">Kawin</option>
                            <option value="Cerai Hidup">Cerai Hidup</option>
                            <option value="Cerai Mati">Cerai Mati</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="label">Pekerjaan</td>
                    <td class="separator">:</td>
                    <td colspan="4">
                        <select class="form-select" name="pekerjaan" id="pekerjaan">
                            <option value="all">Semua Pekerjaan</option>
                            <option value="PNS">PNS</option>
                            <option value="TNI">TNI</option>
                            <option value="POLRI">POLRI</option>
                            <option value="PEGAWAI SWASTA">Pegawai Swasta</option>
                            <option value="WIRASWASTA">Wiraswasta</option>
                            <option value="PETANI">Petani</option>
                            <option value="BURUH">Buruh</option>
                            <option value="PELAJAR/MAHASISWA">Pelajar/Mahasiswa</option>
                            <option value="IRT">Ibu Rumah Tangga</option>
                            <option value="PENSIUNAN">Pensiunan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="text-center pt-4">
                        <button type="button" class="btn-filter me-2" id="searchBtn">
                            <i class="fas fa-search me-2"></i>Cari
                        </button>
                        <button type="button" class="btn-reset" id="resetBtn">
                            <i class="fas fa-redo me-2"></i>Reset Filter
                        </button>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
        <p>Mencari data...</p>
    </div>

    <!-- Hasil Pencarian -->
    <div class="result-card" id="hasilPencarian">
        <div class="result-header">
            <i class="fas fa-users"></i> Hasil Pencarian
        </div>
        <div class="result-body">
            <div class="info-summary" id="infoSummary">
                <!-- Akan diisi oleh JavaScript -->
            </div>

            <!-- Tabel Hasil tanpa border -->
            <div class="table-responsive" id="tableContainer">
                <!-- Akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data dari PHP
    const dusun = <?php echo json_encode($dusun_labels); ?>;
    const lakiPerDusun = <?php echo json_encode($laki_per_dusun); ?>;
    const perempuanPerDusun = <?php echo json_encode($perempuan_per_dusun); ?>;
    const totalPerDusun = <?php echo json_encode($total_per_dusun); ?>;
    const kkPerDusun = <?php echo json_encode($kk_per_dusun); ?>;
    
    const totalLaki = <?php echo $total_laki; ?>;
    const totalPerempuan = <?php echo $total_perempuan; ?>;
    const totalPenduduk = <?php echo $total_penduduk; ?>;
    
    // Data umur
    const rentangUmur = <?php echo json_encode(array_keys($data_umur)); ?>;
    const jumlahUmur = <?php echo json_encode(array_column($data_umur, 'total')); ?>;
    
    // Data status kawin
    const statusLabels = <?php echo json_encode(array_keys($data_status)); ?>;
    const statusJumlah = <?php echo json_encode(array_values($data_status)); ?>;
    
    // Data pekerjaan
    const pekerjaanLabels = <?php echo json_encode(array_keys($data_pekerjaan)); ?>;
    const pekerjaanJumlah = <?php echo json_encode(array_values($data_pekerjaan)); ?>;
    
    // Data agama
    const agamaLabels = <?php echo json_encode(array_keys($data_agama)); ?>;
    const agamaJumlah = <?php echo json_encode(array_values($data_agama)); ?>;
    
    // Data pendidikan
    const pendidikanLabels = <?php echo json_encode(array_keys($data_pendidikan)); ?>;
    const pendidikanJumlah = <?php echo json_encode(array_values($data_pendidikan)); ?>;
    
    // 1. Chart Perbandingan Penduduk per Dusun
    const ctx1 = document.getElementById('pendudukChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: dusun,
                datasets: [
                    {
                        label: 'Laki-laki',
                        data: lakiPerDusun,
                        backgroundColor: '#36b9cc',
                        borderColor: '#2c9faf',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Perempuan',
                        data: perempuanPerDusun,
                        backgroundColor: '#f6c23e',
                        borderColor: '#dda20a',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Penduduk'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Dusun'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const total = totalPerDusun[context.dataIndex];
                                return `Total: ${total.toLocaleString()}`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 2. Doughnut Chart - Rasio Gender
    const ctx2 = document.getElementById('genderRatioChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [totalLaki, totalPerempuan],
                    backgroundColor: ['#36b9cc', '#f6c23e'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const percentage = Math.round((value / totalPenduduk) * 100);
                                return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 3. Bar Chart - KK per Dusun
    const ctx3 = document.getElementById('kkChart');
    if (ctx3) {
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: dusun,
                datasets: [{
                    label: 'Jumlah KK',
                    data: kkPerDusun,
                    backgroundColor: '#4e73df',
                    borderColor: '#3a56b0',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah KK'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // 4. Bar Chart - Distribusi Umur
    const ctx4 = document.getElementById('umurChart');
    if (ctx4) {
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: rentangUmur,
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: jumlahUmur,
                    backgroundColor: '#1cc88a',
                    borderColor: '#169b6b',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah'
                        }
                    }
                }
            }
        });
    }
    
    // 5. Pie Chart - Status Kawin
    const ctx5 = document.getElementById('statusChart');
    if (ctx5) {
        new Chart(ctx5, {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusJumlah,
                    backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // 6. Bar Chart - Pekerjaan
    const ctx6 = document.getElementById('pekerjaanChart');
    if (ctx6) {
        new Chart(ctx6, {
            type: 'bar',
            data: {
                labels: pekerjaanLabels,
                datasets: [{
                    label: 'Jumlah',
                    data: pekerjaanJumlah,
                    backgroundColor: '#f6c23e',
                    borderColor: '#dda20a',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // 7. Pie Chart - Agama
    const ctx7 = document.getElementById('agamaChart');
    if (ctx7) {
        new Chart(ctx7, {
            type: 'pie',
            data: {
                labels: agamaLabels,
                datasets: [{
                    data: agamaJumlah,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // 8. Bar Chart - Pendidikan
    const ctx8 = document.getElementById('pendidikanChart');
    if (ctx8) {
        new Chart(ctx8, {
            type: 'bar',
            data: {
                labels: pendidikanLabels,
                datasets: [{
                    label: 'Jumlah',
                    data: pendidikanJumlah,
                    backgroundColor: '#4e73df',
                    borderColor: '#3a56b0',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // ==================== FUNGSI PENCARIAN AJAX ====================
    const searchBtn = document.getElementById('searchBtn');
    const resetBtn = document.getElementById('resetBtn');
    const hasilCard = document.getElementById('hasilPencarian');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const infoSummary = document.getElementById('infoSummary');
    const tableContainer = document.getElementById('tableContainer');
    
    // Fungsi untuk mencari data
    function searchData() {
        // Ambil nilai filter
        const dusun = document.getElementById('dusun').value;
        const umur = document.getElementById('umur').value;
        const jk = document.getElementById('jk').value;
        const status = document.getElementById('status').value;
        const pekerjaan = document.getElementById('pekerjaan').value;
        
        // Tampilkan loading spinner
        loadingSpinner.classList.add('show');
        hasilCard.classList.remove('show');
        
        // Buat FormData
        const formData = new FormData();
        formData.append('dusun', dusun);
        formData.append('umur', umur);
        formData.append('jk', jk);
        formData.append('status', status);
        formData.append('pekerjaan', pekerjaan);
        
        // Kirim request AJAX
        fetch('search_penduduk.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Sembunyikan loading spinner
            loadingSpinner.classList.remove('show');
            
            // Tampilkan hasil card
            hasilCard.classList.add('show');
            
            // Buat kriteria pencarian
            const criteria = [];
            if (dusun !== 'all') criteria.push(`Dusun: ${dusun}`);
            if (umur) criteria.push(`Umur: ${umur} tahun`);
            if (jk !== 'all') criteria.push(`Jenis Kelamin: ${jk === 'LAKI-LAKI' ? 'Laki-laki' : 'Perempuan'}`);
            if (status !== 'all') criteria.push(`Status: ${status}`);
            if (pekerjaan !== 'all') criteria.push(`Pekerjaan: ${pekerjaan}`);
            
            const criteriaText = criteria.length > 0 ? criteria.join(' • ') : 'Semua data (tanpa filter)';
            
            // Update info summary
            infoSummary.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Total Data Ditemukan</h6>
                        <span class="total">${data.total} penduduk</span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6>Kriteria Pencarian</h6>
                        <span class="text-muted">${criteriaText}</span>
                    </div>
                </div>
            `;
            
            // Update tabel
            if (data.total > 0) {
                let tableHtml = `
                    <table class="table-borderless-custom">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Dusun</th>
                                <th>Umur</th>
                                <th>Jenis Kelamin</th>
                                <th>Status</th>
                                <th>Pekerjaan</th>
                                <th>Status Keluarga</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                data.data.forEach((item, index) => {
                    tableHtml += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.nik}</td>
                            <td>${item.nama_penduduk}</td>
                            <td>${item.dusun || '-'}</td>
                            <td>${item.umur} tahun</td>
                            <td>${item.jenis_kelamin === 'LAKI-LAKI' ? 'Laki-laki' : 'Perempuan'}</td>
                            <td>${item.status_kawin}</td>
                            <td>${item.pekerjaan || '-'}</td>
                            <td>
                                ${item.is_kepala > 0 ? '<span class="badge-status badge-kepala">Kepala KK</span>' : 
                                  item.is_anggota > 0 ? '<span class="badge-status badge-anggota">Anggota KK</span>' : 
                                  '<span class="badge-status badge-mandiri">Mandiri</span>'}
                            </td>
                        </tr>
                    `;
                });
                
                tableHtml += '</tbody></table>';
                tableContainer.innerHTML = tableHtml;
            } else {
                tableContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h5>Tidak ada data ditemukan</h5>
                        <p>Coba ubah kriteria pencarian Anda</p>
                    </div>
                `;
            }
            
            // Scroll ke hasil pencarian
            hasilCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(error => {
            console.error('Error:', error);
            loadingSpinner.classList.remove('show');
            alert('Terjadi kesalahan saat mencari data');
        });
    }
    
    // Event listener untuk tombol search
    searchBtn.addEventListener('click', function(e) {
        e.preventDefault();
        searchData();
    });
    
    // Event listener untuk tombol reset
    resetBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Reset semua filter ke nilai default
        document.getElementById('dusun').value = 'all';
        document.getElementById('umur').value = '';
        document.getElementById('jk').value = 'all';
        document.getElementById('status').value = 'all';
        document.getElementById('pekerjaan').value = 'all';
        
        // Sembunyikan hasil pencarian
        hasilCard.classList.remove('show');
    });
    
    // Event listener untuk enter key di input umur
    document.getElementById('umur').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchData();
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include 'template1/base.php';
?>