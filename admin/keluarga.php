<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

include "../db/koneksi.php";
include "../db/funct.php";

// Daftar dusun
$daftar_dusun = [
    'KEJAWAN', 'SEPURAN', 'BUDDAN', 'PASEREAN', 'LANGGAR', 
    'MORLEKE', 'PREGIH', 'KARANG PANDAN', 'PONG BARU', 'KRASAK', 'PERUM BASMALAH'
];

// ==================== AJAX HANDLER ====================

// AJAX: Check Nomor KK
if (isset($_GET['check_kk'])) {
    header('Content-Type: application/json');
    $no_kk = mysqli_real_escape_string($conn, $_GET['check_kk']);
    
    if (empty($no_kk)) { echo json_encode(['exists' => false, 'valid' => false, 'message' => 'Nomor KK kosong']); exit(); }
    if (!is_numeric($no_kk)) { echo json_encode(['exists' => false, 'valid' => false, 'message' => 'Nomor KK harus angka']); exit(); }
    if (strlen($no_kk) !== 16) { echo json_encode(['exists' => false, 'valid' => false, 'message' => 'Nomor KK harus 16 digit']); exit(); }
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM kartu_keluarga WHERE no_kk = '$no_kk'");
    $row = mysqli_fetch_assoc($result);
    $exists = ($row['count'] > 0);
    
    echo json_encode(['exists' => $exists, 'valid' => true]);
    exit();
}

// AJAX: Get KK by No KK untuk Edit
if (isset($_GET['ajax_get_kk'])) {
    header('Content-Type: application/json');
    $no_kk = mysqli_real_escape_string($conn, $_GET['ajax_get_kk']);
    
    $query = "SELECT kk.*, p.nama_penduduk as nama_kepala 
              FROM kartu_keluarga kk 
              JOIN penduduk p ON kk.nik_kepala = p.nik 
              WHERE kk.no_kk = '$no_kk'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    }
    exit();
}

// AJAX: Hapus KK
if (isset($_GET['ajax_hapus_kk'])) {
    header('Content-Type: application/json');
    $no_kk = mysqli_real_escape_string($conn, $_GET['ajax_hapus_kk']);
    
    $result = hapus_kk($no_kk);
    if ($result > 0) { echo json_encode(['success' => true, 'message' => 'Data KK berhasil dihapus']); } 
    else { echo json_encode(['success' => false, 'message' => 'Gagal menghapus data KK']); }
    exit();
}

// AJAX: Hapus Anggota Keluarga
if (isset($_GET['ajax_hapus_anggota'])) {
    header('Content-Type: application/json');
    $id_anggota = mysqli_real_escape_string($conn, $_GET['ajax_hapus_anggota']);
    
    $result = hapus_anggota_keluarga($id_anggota);
    if ($result > 0) { echo json_encode(['success' => true, 'message' => 'Anggota berhasil dihapus']); } 
    else { echo json_encode(['success' => false, 'message' => 'Gagal menghapus anggota']); }
    exit();
}

// AJAX: Search Penduduk untuk Kepala Keluarga
if (isset($_GET['search_kepala'])) {
    header('Content-Type: application/json');
    $keyword = mysqli_real_escape_string($conn, $_GET['search_kepala']);
    
    $query = "SELECT nik, nama_penduduk FROM penduduk 
              WHERE nik NOT IN (
                  SELECT nik_kepala FROM kartu_keluarga
                  UNION 
                  SELECT nik FROM anggota_keluarga
              )
              AND (nik LIKE '%$keyword%' OR nama_penduduk LIKE '%$keyword%')
              ORDER BY nama_penduduk
              LIMIT 20";
    $result = mysqli_query($conn, $query);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

// AJAX: Search Penduduk untuk Anggota Keluarga
if (isset($_GET['search_anggota'])) {
    header('Content-Type: application/json');
    $keyword = mysqli_real_escape_string($conn, $_GET['search_anggota']);
    $no_kk = mysqli_real_escape_string($conn, $_GET['no_kk'] ?? '');
    
    $kk = mysqli_query($conn, "SELECT nik_kepala FROM kartu_keluarga WHERE no_kk = '$no_kk'");
    $kk_data = mysqli_fetch_assoc($kk);
    $nik_kepala = $kk_data['nik_kepala'] ?? '';
    
    $query = "SELECT nik, nama_penduduk FROM penduduk 
              WHERE nik NOT IN (
                  SELECT nik_kepala FROM kartu_keluarga WHERE nik_kepala != '$nik_kepala'
                  UNION
                  SELECT nik FROM anggota_keluarga
              )
              AND nik != '$nik_kepala'
              AND (nik LIKE '%$keyword%' OR nama_penduduk LIKE '%$keyword%')
              ORDER BY nama_penduduk
              LIMIT 20";
    $result = mysqli_query($conn, $query);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

// ==================== PROSES FORM ====================

// PROSES TAMBAH KK
if (isset($_POST["submit_tambah_kk"])) {
    if (empty($_POST["no_kk"]) || empty($_POST["nik_kepala"]) || empty($_POST["alamat_kk"])) {
        $_SESSION['error_message'] = "Nomor KK, Kepala Keluarga, dan Alamat wajib diisi!";
        header("Location: keluarga.php");
        exit();
    }
    
    if (isset($_POST['dusun_option']) && $_POST['dusun_option'] == 'pilih') {
        $_POST['dusun'] = $_POST['dusun_select'] ?? '';
    } else {
        $_POST['dusun'] = $_POST['dusun_custom'] ?? '';
    }
    
    $result = tambah_kk($_POST);
    
    if ($result > 0) $_SESSION['success_message'] = "Data Kartu Keluarga berhasil ditambahkan!";
    elseif ($result == -1) $_SESSION['error_message'] = "Nomor KK sudah terdaftar!";
    elseif ($result == -2) $_SESSION['error_message'] = "Penduduk ini sudah menjadi Kepala Keluarga di KK lain!";
    elseif ($result == -3) $_SESSION['error_message'] = "Penduduk ini sudah menjadi Anggota Keluarga di KK lain!";
    else $_SESSION['error_message'] = "Gagal menambahkan data KK!";
    
    header("Location: keluarga.php");
    exit();
}

// PROSES EDIT KK
if (isset($_POST["submit_edit_kk"])) {
    if (empty($_POST["no_kk"])) {
        $_SESSION['error_message'] = "Nomor KK tidak ditemukan!";
        header("Location: keluarga.php");
        exit();
    }
    
    if (isset($_POST['edit_dusun_option']) && $_POST['edit_dusun_option'] == 'pilih') {
        $_POST['dusun'] = $_POST['edit_dusun_select'] ?? '';
    } else {
        $_POST['dusun'] = $_POST['edit_dusun_custom'] ?? '';
    }
    
    $result = edit_kk($_POST);
    
    if ($result >= 0) $_SESSION['success_message'] = "Data Kartu Keluarga berhasil diupdate!";
    else $_SESSION['error_message'] = "Gagal mengupdate data KK!";
    
    if (isset($_POST['from_detail']) && $_POST['from_detail'] == 'true') {
        header("Location: keluarga.php?detail=" . $_POST['no_kk']);
    } else {
        header("Location: keluarga.php");
    }
    exit();
}

// PROSES TAMBAH ANGGOTA
if (isset($_POST["submit_tambah_anggota"])) {
    if (empty($_POST["no_kk"]) || empty($_POST["nik"]) || empty($_POST["hubungan_keluarga"])) {
        $_SESSION['error_message'] = "Penduduk dan Hubungan Keluarga wajib dipilih!";
        header("Location: keluarga.php?detail=" . $_POST['no_kk']);
        exit();
    }
    
    $result = tambah_anggota_keluarga($_POST);
    
    if ($result > 0) $_SESSION['success_message'] = "Anggota keluarga berhasil ditambahkan!";
    else $_SESSION['error_message'] = "Gagal menambahkan anggota keluarga!";
    
    header("Location: keluarga.php?detail=" . $_POST['no_kk']);
    exit();
}

// ==================== QUERY DATA & STATISTIK ====================

$detail_mode = isset($_GET['detail']);
$no_kk_detail = $detail_mode ? mysqli_real_escape_string($conn, $_GET['detail']) : '';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "";
if (!empty($search)) {
    $where = "WHERE kk.no_kk LIKE '%$search%' OR p.nama_penduduk LIKE '%$search%'";
}

// BUG FIX PERHITUNGAN JUMLAH ANGGOTA
// Menggunakan query aman: Hitung anggota_keluarga (selain nik_kepala) lalu + 1 (kepala) 
$query_kk = "SELECT kk.*, p.nama_penduduk as nama_kepala,
             ((SELECT COUNT(ak.id_anggota) FROM anggota_keluarga ak WHERE ak.no_kk = kk.no_kk AND ak.nik != kk.nik_kepala) + 1) as total_anggota_real
             FROM kartu_keluarga kk
             JOIN penduduk p ON kk.nik_kepala = p.nik
             $where
             ORDER BY kk.created_at DESC";
$result_kk = mysqli_query($conn, $query_kk);
$total_kk = mysqli_num_rows($result_kk);

// Statistik Card Data
$stat_kk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kartu_keluarga"))['total'];
$stat_anggota_lain = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM anggota_keluarga"))['total'];
$stat_total_warga = $stat_kk + $stat_anggota_lain; // Kepala + Anggota
$stat_avg = $stat_kk > 0 ? round($stat_total_warga / $stat_kk, 1) : 0;

function formatTanggal2($date) {
    if(!$date) return '-';
    return date('d-m-Y', strtotime($date));
}

function hitungUmurPHP($tanggalLahir) {
    if (!$tanggalLahir) return '-';
    $birthDate = new DateTime($tanggalLahir);
    $today = new DateTime('today');
    $y = $today->diff($birthDate)->y;
    return $y . " Thn";
}

$pageTitle = $detail_mode ? "Detail Keluarga" : "Data Kartu Keluarga";
ob_start();
?>

<style>
/* ===== UI MODERNISASI ===== */
body { background-color: #f8f9fc; }

.statistik-card { transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: none; border-radius: 1rem; box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.04); background: white; overflow: hidden; }
.statistik-card:hover { transform: translateY(-5px); box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.1); }
.border-left-primary { border-left: 5px solid #4e73df !important; }
.border-left-success { border-left: 5px solid #1cc88a !important; }
.border-left-warning { border-left: 5px solid #f6c23e !important; }

/* Filter & Main Card */
.main-card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); }
.main-card > .card-header { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border-bottom: none; border-radius: 1rem 1rem 0 0; color: white;}

/* Modern Table */
.table-container { background: white; border-radius: 0 0 1rem 1rem; padding: 0 10px 15px 10px; }
.table thead th { background-color: #f8f9fc; border-bottom: 2px solid #eaecf4; color: #4e73df; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 15px; vertical-align: middle;}
.table tbody tr { transition: background 0.2s; }
.table tbody tr:hover { background-color: #f1f3f9; }
.table td { vertical-align: middle; color: #5a5c69; border-bottom: 1px solid #eaecf4; padding: 12px 15px; }

/* Z-INDEX FIX: Custom Search Input */
.search-container { position: relative; width: 100%; z-index: 9999 !important; }
.search-results { 
    position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 0.5rem; margin-top: 5px; 
    max-height: 250px; overflow-y: auto; z-index: 99999 !important; display: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
    border: 1px solid #4e73df; animation: fadeIn 0.2s ease-in-out;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.search-result-item { padding: 12px 20px; cursor: pointer; border-bottom: 1px solid #f8f9fc; transition: background 0.2s; display: flex; justify-content: space-between; align-items: center;}
.search-result-item:hover { background: #eaecf4; border-left: 4px solid #4e73df; }
.search-result-item strong { color: #4e73df; font-size: 1.05rem;}

/* Action Buttons Sleek */
.btn-group-action { display: flex; gap: 6px; justify-content: center; }
.action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; color: white; transition: all 0.2s; border: none; cursor: pointer; }
.action-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); filter: brightness(1.1); }
.icon-view { background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%); }
.icon-edit { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); }
.icon-delete { background: #eaecf4; color: #e74a3b; border: 1px solid #e74a3b; }
.icon-delete:hover { background: #e74a3b; color: white; }

/* Copy Button */
.btn-copy { background: transparent; border: none; color: #4e73df; cursor: pointer; padding: 2px 5px; border-radius: 4px; transition: 0.2s; }
.btn-copy:hover { background: #eaecf4; }

/* Modals & Forms - Scroll Fix */
.modal-content { border: none; border-radius: 1rem; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow: hidden; display: flex; flex-direction: column; max-height: 100%;}
.modal-header { border-bottom: none; padding: 1.25rem 1.5rem; flex-shrink: 0;}
.modal-body { padding: 1.5rem; overflow-y: auto; flex-grow: 1; }
.modal-footer { border-top: 1px solid #eaecf4; background: #f8f9fc; padding: 1rem 1.5rem; flex-shrink: 0;}
.modal-body::-webkit-scrollbar { width: 8px; }
.modal-body::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.modal-body::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }

.form-section-title { font-size: 0.85rem; font-weight: 700; color: #4e73df; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #eaecf4; padding-bottom: 8px; margin-bottom: 15px; margin-top: 10px;}
.form-control, .form-select { border-radius: 8px; border: 1px solid #d1d3e2; padding: 0.6rem 1rem; font-size: 0.9rem; background-color: #fdfdfd;}
.form-control:focus, .form-select:focus { border-color: #4e73df; box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25); background-color: #fff;}
.form-label { font-weight: 600; color: #5a5c69; font-size: 0.85rem; margin-bottom: 4px;}

.text-uppercase { text-transform: uppercase; }

/* Data Profile Detail Card */
.detail-card { background: white; border-radius: 1rem; box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.03); overflow: hidden; }
.detail-card-header { background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%); color: white; padding: 15px 20px; font-weight: bold; }
</style>

<div class="container-fluid px-0">

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-lg position-fixed top-0 end-0 m-3" style="z-index:9999;" role="alert">
        <i class="fas fa-check-circle me-2 fs-5 align-middle"></i><span class="align-middle"><?php echo $_SESSION['success_message']; ?></span>
        <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-lg position-fixed top-0 end-0 m-3" style="z-index:9999;" role="alert">
        <i class="fas fa-exclamation-circle me-2 fs-5 align-middle"></i><span class="align-middle"><?php echo $_SESSION['error_message']; ?></span>
        <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                <?php echo $detail_mode ? 'Rincian Keluarga: ' . htmlspecialchars($no_kk_detail) : 'Database Kartu Keluarga'; ?>
            </h1>
            <p class="text-muted small mb-0">Kelola data per-kepala keluarga dan anggotanya.</p>
        </div>
        <div>
            <?php if (!$detail_mode): ?>
            <button type="button" class="btn btn-primary shadow-sm rounded-pill px-4" id="btnTambahKK">
                <i class="fas fa-plus me-2"></i>Entri KK Baru
            </button>
            <?php else: ?>
            <button type="button" class="btn btn-info text-white shadow-sm rounded-pill px-4 me-2" onclick="cetakDataKeluarga()">
                <i class="fas fa-print me-2"></i>Cetak Data KK
            </button>
            <a href="keluarga.php" class="btn btn-secondary shadow-sm rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$detail_mode): ?>
    
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4 mb-xl-0">
            <div class="card statistik-card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Kartu Keluarga</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_kk); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-address-card fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4 mb-xl-0">
            <div class="card statistik-card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Warga dalam KK</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_total_warga); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card statistik-card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Rata-Rata Anggota / KK</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stat_avg; ?> <span class="fs-6 text-muted">Orang</span></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-chart-pie fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card mb-5">
        <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-list me-2"></i>Daftar Kepala Keluarga</h6>
            
            <form method="GET" class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="max-width: 300px;">
                    <input type="text" class="form-control border-0" name="search" placeholder="Cari No. KK / Nama..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-light text-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
                <?php if (!empty($search)): ?>
                    <a href="keluarga.php" class="btn btn-sm btn-light text-danger" title="Reset"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="table-container pt-3">
            <?php if ($total_kk > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="20%">No. Kartu Keluarga</th>
                            <th width="25%">Kepala Keluarga</th>
                            <th width="15%" class="text-center">Jml Anggota</th>
                            <th width="20%">Dusun / Alamat</th>
                            <th width="15%" class="text-center">Aksi Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; mysqli_data_seek($result_kk, 0); while ($kk = mysqli_fetch_assoc($result_kk)): ?>
                        <tr>
                            <td class="text-center text-muted"><?php echo $no++; ?></td>
                            <td>
                                <span class="font-weight-bold text-primary font-family-monospace" id="kk_<?php echo $kk['no_kk']; ?>"><?php echo htmlspecialchars($kk['no_kk']); ?></span>
                                <button class="btn-copy ms-1" onclick="copyText('kk_<?php echo $kk['no_kk']; ?>')" title="Salin No KK"><i class="far fa-copy"></i></button>
                            </td>
                            <td>
                                <div class="font-weight-bold text-gray-800 text-uppercase"><?php echo htmlspecialchars($kk['nama_kepala']); ?></div>
                                <div class="small text-muted">NIK: <?php echo htmlspecialchars($kk['nik_kepala']); ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info px-3 py-2 rounded-pill shadow-sm"><?php echo $kk['total_anggota_real']; ?> Orang</span>
                            </td>
                            <td>
                                <div class="font-weight-bold text-dark small"><?php echo htmlspecialchars($kk['dusun'] ?: '-'); ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($kk['alamat_kk']); ?>"><?php echo htmlspecialchars($kk['alamat_kk']); ?> RT <?php echo $kk['rt'].'/'.$kk['rw']; ?></div>
                            </td>
                            <td>
                                <div class="btn-group-action">
                                    <a href="keluarga.php?detail=<?php echo $kk['no_kk']; ?>" class="action-icon icon-view" title="Rincian Anggota"><i class="fas fa-users"></i></a>
                                    <button type="button" class="action-icon icon-edit btn-edit-kk" data-no-kk="<?php echo htmlspecialchars($kk['no_kk']); ?>" title="Edit Data KK"><i class="fas fa-edit"></i></button>
                                    <button type="button" class="action-icon icon-delete btn-hapus-kk" data-no-kk="<?php echo htmlspecialchars($kk['no_kk']); ?>" data-kepala="<?php echo htmlspecialchars($kk['nama_kepala']); ?>" title="Hapus Permanen"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-light border text-center py-5">
                <i class="fas fa-users-slash fa-3x text-gray-300 mb-3"></i>
                <h5 class="text-gray-500">Data Keluarga Kosong</h5>
                <p class="text-muted">Tidak ada data yang sesuai dengan pencarian Anda.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: 
        // ========== HALAMAN DETAIL KK ==========
        $kk_detail = mysqli_query($conn, "SELECT kk.*, p.nama_penduduk as nama_kepala, p.jenis_kelamin as jk_kepala, 
                                          p.tempat_lahir, p.tanggal_lahir, p.status_kawin, p.pekerjaan,
                                          p.agama, p.pendidikan, p.rt_rw as rt_rw_penduduk, p.nama_ayah, p.nama_ibu
                                          FROM kartu_keluarga kk JOIN penduduk p ON kk.nik_kepala = p.nik 
                                          WHERE kk.no_kk = '$no_kk_detail'");
        
        if (mysqli_num_rows($kk_detail) == 0) {
            echo '<div class="alert alert-danger shadow-sm">Data KK tidak ditemukan! <a href="keluarga.php" class="alert-link">Kembali</a></div>';
        } else {
            $kk = mysqli_fetch_assoc($kk_detail);
            $anggota = mysqli_query($conn, "SELECT ak.*, p.nama_penduduk, p.jenis_kelamin, p.tempat_lahir, p.tanggal_lahir, 
                                            p.status_kawin, p.pekerjaan, p.alamat, p.agama, p.pendidikan, p.nama_ayah, p.nama_ibu
                                            FROM anggota_keluarga ak JOIN penduduk p ON ak.nik = p.nik 
                                            WHERE ak.no_kk = '$no_kk_detail' AND ak.nik != '{$kk['nik_kepala']}'
                                            ORDER BY CASE ak.hubungan_keluarga WHEN 'Suami' THEN 1 WHEN 'Istri' THEN 2 WHEN 'Anak' THEN 3 ELSE 4 END, p.tanggal_lahir ASC");
            
            $total_anggota_lain = mysqli_num_rows($anggota);
            $total_anggota = $total_anggota_lain + 1;
    ?>

    <div id="printAreaDetail" class="detail-card mb-4">
        <div class="detail-card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0"><i class="fas fa-address-card me-2"></i>Informasi Kartu Keluarga</h6>
            <div class="d-flex gap-2 d-print-none">
                <button type="button" class="btn btn-sm btn-light text-primary font-weight-bold rounded-pill shadow-sm" id="btnTambahAnggota" data-no-kk="<?php echo $kk['no_kk']; ?>"><i class="fas fa-user-plus me-1"></i> Tambah Anggota</button>
                <button type="button" class="btn btn-sm btn-warning text-dark font-weight-bold rounded-pill shadow-sm btn-edit-kk-detail" data-no-kk="<?php echo $kk['no_kk']; ?>"><i class="fas fa-edit me-1"></i> Edit KK</button>
            </div>
        </div>
        <div class="card-body bg-white border-bottom">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td width="35%" class="text-muted font-weight-bold">Nomor KK</td>
                            <td>: <span class="font-weight-bold text-primary fs-5" id="detail_no_kk"><?php echo $kk['no_kk']; ?></span> 
                            <button class="btn-copy ms-1 d-print-none" onclick="copyText('detail_no_kk')" title="Salin KK"><i class="far fa-copy"></i></button></td></tr>
                        <tr><td class="text-muted font-weight-bold">Kepala Keluarga</td><td>: <span class="text-uppercase font-weight-bold"><?php echo $kk['nama_kepala']; ?></span></td></tr>
                        <tr><td class="text-muted font-weight-bold">Alamat</td><td class="text-uppercase">: <?php echo $kk['alamat_kk']; ?></td></tr>
                        <tr><td class="text-muted font-weight-bold">RT/RW & Dusun</td><td class="text-uppercase">: <?php echo $kk['rt'] . '/' . $kk['rw']; ?> - <?php echo $kk['dusun'] ?: '-'; ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6 border-start">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td width="35%" class="text-muted font-weight-bold">Desa/Kelurahan</td><td class="text-uppercase">: <?php echo $kk['desa_kel']; ?></td></tr>
                        <tr><td class="text-muted font-weight-bold">Kecamatan</td><td class="text-uppercase">: <?php echo $kk['kecamatan']; ?></td></tr>
                        <tr><td class="text-muted font-weight-bold">Kabupaten/Kota</td><td class="text-uppercase">: <?php echo $kk['kabupaten_kota']; ?></td></tr>
                        <tr><td class="text-muted font-weight-bold">Provinsi</td><td class="text-uppercase">: <?php echo $kk['provinsi']; ?></td></tr>
                        <tr><td class="text-muted font-weight-bold">Kode Pos</td><td>: <?php echo $kk['kode_pos']; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="card-header bg-info py-3 d-flex justify-content-between align-items-center rounded-0 border-0">
            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-users me-2"></i>Daftar Anggota Keluarga LENGKAP (<?php echo $total_anggota; ?> Jiwa)</h6>
            
            <div class="input-group input-group-sm d-print-none" style="width: 250px;">
                <span class="input-group-text bg-white border-0"><i class="fas fa-search text-info"></i></span>
                <input type="text" id="searchTableAnggota" class="form-control border-0" placeholder="Cari nama anggota...">
            </div>
        </div>
        <div class="table-container pt-3" style="border-radius: 0;">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle" id="tabelAnggotaDetail" style="font-size: 0.85rem;">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="3%">No</th>
                            <th>Nama Lengkap</th>
                            <th>NIK</th>
                            <th>Jenis Kelamin</th>
                            <th>Tempat Lahir</th>
                            <th>Tanggal Lahir (Umur)</th>
                            <th>Agama</th>
                            <th>Pendidikan</th>
                            <th>Pekerjaan</th>
                            <th>Status Kawin</th>
                            <th>Hubungan Keluarga</th>
                            <th>Nama Ayah</th>
                            <th>Nama Ibu</th>
                            <th class="d-print-none text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-light">
                            <td class="text-center text-muted font-weight-bold">1</td>
                            <td class="font-weight-bold text-gray-800 text-uppercase"><?php echo htmlspecialchars($kk['nama_kepala']); ?></td>
                            <td class="font-family-monospace text-primary"><?php echo $kk['nik_kepala']; ?></td>
                            <td class="text-center"><?php echo $kk['jk_kepala'] == 'LAKI-LAKI' ? 'Laki-Laki' : 'Perempuan'; ?></td>
                            <td class="text-uppercase"><?php echo htmlspecialchars($kk['tempat_lahir']); ?></td>
                            <td>
                                <?php echo formatTanggal2($kk['tanggal_lahir']); ?> 
                                <span class="badge bg-secondary ms-1"><?php echo hitungUmurPHP($kk['tanggal_lahir']); ?></span>
                            </td>
                            <td><?php echo $kk['agama']; ?></td>
                            <td class="text-uppercase"><?php echo htmlspecialchars($kk['pendidikan'] ?: '-'); ?></td>
                            <td class="text-uppercase"><?php echo htmlspecialchars($kk['pekerjaan'] ?: '-'); ?></td>
                            <td><?php echo $kk['status_kawin']; ?></td>
                            <td class="text-center"><span class="badge bg-warning text-dark"><i class="fas fa-crown me-1"></i> Kepala Keluarga</span></td>
                            <td class="text-uppercase"><?php echo htmlspecialchars($kk['nama_ayah'] ?: '-'); ?></td>
                            <td class="text-uppercase"><?php echo htmlspecialchars($kk['nama_ibu'] ?: '-'); ?></td>
                            <td class="text-center d-print-none">-</td>
                        </tr>
                        
                        <?php if ($total_anggota_lain > 0): $no = 2; while ($ag = mysqli_fetch_assoc($anggota)): ?>
                        <tr class="row-anggota">
                            <td class="text-center text-muted"><?php echo $no++; ?></td>
                            <td class="font-weight-bold text-gray-800 text-uppercase"><?php echo htmlspecialchars($ag['nama_penduduk']); ?></td>
                            <td class="font-family-monospace text-dark"><?php echo $ag['nik']; ?></td>
                            <td class="text-center"><?php echo $ag['jenis_kelamin'] == 'LAKI-LAKI' ? 'Laki-Laki' : 'Perempuan'; ?></td>
                            <td class="text-uppercase"><?php echo htmlspecialchars($ag['tempat_lahir']); ?></td>
                            <td>
                                <?php echo formatTanggal2($ag['tanggal_lahir']); ?>
                                <span class="badge bg-light text-dark border ms-1"><?php echo hitungUmurPHP($ag['tanggal_lahir']); ?></span>
                            </td>
                            <td><?php echo $ag['agama']; ?></td>
                            <td class="text-uppercase"><?php echo htmlspecialchars($ag['pendidikan'] ?: '-'); ?></td>
                            <td class="text-uppercase"><?php echo htmlspecialchars($ag['pekerjaan'] ?: '-'); ?></td>
                            <td><?php echo $ag['status_kawin']; ?></td>
                            <td class="text-center"><span class="badge bg-info px-2 py-1 text-uppercase"><?php echo $ag['hubungan_keluarga']; ?></span></td>
                            <td class="text-uppercase"><?php echo htmlspecialchars($ag['nama_ayah'] ?: '-'); ?></td>
                            <td class="text-uppercase"><?php echo htmlspecialchars($ag['nama_ibu'] ?: '-'); ?></td>
                            <td class="text-center d-print-none">
                                <button type="button" class="btn btn-sm btn-danger btn-hapus-anggota shadow-sm" data-id="<?php echo $ag['id_anggota']; ?>" data-no-kk="<?php echo $kk['no_kk']; ?>" data-nik="<?php echo $ag['nik']; ?>" data-nama="<?php echo htmlspecialchars($ag['nama_penduduk']); ?>" title="Keluarkan dari KK"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="14" class="text-center text-muted py-4">Belum ada anggota keluarga lain yang ditambahkan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php } endif; ?>

</div>

<div class="modal fade" id="tambahKKModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="keluarga.php" id="formTambahKK" style="display:flex; flex-direction:column; height:100%;">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-address-card me-2"></i>Entri Kartu Keluarga Baru</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModalTambahKK()"></button>
                </div>
                <div class="modal-body bg-light p-4">
                    
                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <div class="form-section-title"><i class="fas fa-info-circle me-2"></i>Identitas Kepala Keluarga</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor KK <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-weight-bold text-primary" name="no_kk" id="no_kk" required maxlength="16" pattern="[0-9]{16}" placeholder="Masukkan 16 digit angka" oninput="checkNoKK()">
                                <div id="kkFeedback" class="mt-1 small"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pilih Kepala Keluarga <span class="text-danger">*</span></label>
                                <div class="search-container">
                                    <input type="text" class="form-control bg-white text-uppercase" id="search_kepala" placeholder="Ketik nama atau NIK warga..." autocomplete="off">
                                    <input type="hidden" name="nik_kepala" id="nik_kepala" required>
                                    <div id="searchKepalaResults" class="search-results"></div>
                                </div>
                                <div id="kepalaFeedback" class="mt-1 small text-muted">Hanya penduduk mandiri yang bisa dijadikan Kepala Keluarga.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4">
                        <div class="form-section-title"><i class="fas fa-map-marked-alt me-2"></i>Alamat Keluarga</div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Alamat Jalan <span class="text-danger">*</span></label>
                                <textarea class="form-control text-uppercase" name="alamat_kk" rows="2" required placeholder="Contoh: JL. RAYA SUKOLILO NO. 12"></textarea>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Dusun</label>
                                <div class="d-flex mb-2 gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="dusun_option" id="dusun_pilih" value="pilih" checked onclick="toggleDusun('pilih')">
                                        <label class="form-check-label text-muted" for="dusun_pilih">Pilih Daftar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="dusun_option" id="dusun_tulis" value="tulis" onclick="toggleDusun('tulis')">
                                        <label class="form-check-label text-muted" for="dusun_tulis">Ketik Manual</label>
                                    </div>
                                </div>
                                <div id="dusun_select_container">
                                    <select class="form-select" name="dusun_select" id="dusun_select" onchange="updateDusunHidden('select')">
                                        <option value="">-- Pilih Dusun --</option>
                                        <?php foreach ($daftar_dusun as $dusun): ?>
                                            <option value="<?php echo $dusun; ?>"><?php echo $dusun; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="dusun_input_container" style="display: none;">
                                    <input type="text" class="form-control text-uppercase" name="dusun_custom" id="dusun_custom" placeholder="Ketik nama dusun..." oninput="updateDusunHidden('input')">
                                </div>
                                <input type="hidden" name="dusun" id="dusun_hidden">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">RT</label>
                                <input type="text" class="form-control" name="rt" id="rt" value="001" maxlength="3">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">RW</label>
                                <input type="text" class="form-control" name="rw" id="rw" value="002" maxlength="3">
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Desa / Kelurahan</label>
                                <input type="text" class="form-control text-uppercase" name="desa_kel" id="desa_kel" value="Sukolilo Timur">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kecamatan</label>
                                <input type="text" class="form-control text-uppercase" name="kecamatan" id="kecamatan" value="Labang">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kabupaten</label>
                                <input type="text" class="form-control text-uppercase" name="kabupaten_kota" id="kabupaten_kota" value="Bangkalan">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Provinsi</label>
                                <input type="text" class="form-control text-uppercase" name="provinsi" id="provinsi" value="Jawa Timur">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" class="form-control" name="kode_pos" id="kode_pos" value="69162" maxlength="5">
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalTambahKK()">Batal</button>
                    <button type="submit" name="submit_tambah_kk" class="btn btn-primary px-5 rounded-pill shadow-sm" id="submitTambahKKBtn" disabled><i class="fas fa-save me-2"></i>Simpan KK Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editKKModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="keluarga.php" id="formEditKK" style="display:flex; flex-direction:column; height:100%;">
                <input type="hidden" name="no_kk" id="edit_no_kk">
                <input type="hidden" name="from_detail" id="edit_from_detail" value="false">
                
                <div class="modal-header bg-gradient-warning text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit me-2"></i>Update Kartu Keluarga</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModalEditKK()"></button>
                </div>
                
                <div class="modal-body bg-light p-4">
                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <div class="form-section-title"><i class="fas fa-info-circle me-2"></i>Identitas Kepala Keluarga</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor KK</label>
                                <input type="text" class="form-control bg-light text-muted font-weight-bold" id="edit_no_kk_display" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kepala Keluarga</label>
                                <input type="text" class="form-control bg-light text-muted font-weight-bold text-uppercase" id="edit_kepala" readonly>
                                <small class="text-danger">* Data Kepala KK & No KK tidak dapat diubah di sini.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4">
                        <div class="form-section-title"><i class="fas fa-map-marked-alt me-2"></i>Update Alamat Keluarga</div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Alamat Jalan <span class="text-danger">*</span></label>
                                <textarea class="form-control text-uppercase" name="alamat_kk" id="edit_alamat_kk" rows="2" required></textarea>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Dusun</label>
                                <div class="d-flex mb-2 gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="edit_dusun_option" id="edit_dusun_pilih" value="pilih" onclick="toggleEditDusun('pilih')">
                                        <label class="form-check-label text-muted" for="edit_dusun_pilih">Pilih Daftar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="edit_dusun_option" id="edit_dusun_tulis" value="tulis" onclick="toggleEditDusun('tulis')">
                                        <label class="form-check-label text-muted" for="edit_dusun_tulis">Ketik Manual</label>
                                    </div>
                                </div>
                                <div id="edit_dusun_select_container">
                                    <select class="form-select" name="edit_dusun_select" id="edit_dusun_select" onchange="updateEditDusunHidden('select')">
                                        <option value="">-- Pilih Dusun --</option>
                                        <?php foreach ($daftar_dusun as $dusun): ?>
                                            <option value="<?php echo $dusun; ?>"><?php echo $dusun; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="edit_dusun_input_container" style="display: none;">
                                    <input type="text" class="form-control text-uppercase" name="edit_dusun_custom" id="edit_dusun_custom" placeholder="Ketik nama dusun..." oninput="updateEditDusunHidden('input')">
                                </div>
                                <input type="hidden" name="dusun" id="edit_dusun_hidden">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">RT</label>
                                <input type="text" class="form-control" name="rt" id="edit_rt" maxlength="3">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">RW</label>
                                <input type="text" class="form-control" name="rw" id="edit_rw" maxlength="3">
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Desa / Kelurahan</label>
                                <input type="text" class="form-control text-uppercase" name="desa_kel" id="edit_desa_kel">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kecamatan</label>
                                <input type="text" class="form-control text-uppercase" name="kecamatan" id="edit_kecamatan">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kabupaten</label>
                                <input type="text" class="form-control text-uppercase" name="kabupaten_kota" id="edit_kabupaten_kota">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Provinsi</label>
                                <input type="text" class="form-control text-uppercase" name="provinsi" id="edit_provinsi">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" class="form-control" name="kode_pos" id="edit_kode_pos" maxlength="5">
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalEditKK()">Batal</button>
                    <button type="submit" name="submit_edit_kk" class="btn btn-warning text-dark px-5 rounded-pill shadow-sm"><i class="fas fa-save me-2"></i>Update Data KK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="tambahAnggotaModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white py-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus me-2"></i>Tambah Anggota Keluarga</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalTambahAnggota()"></button>
            </div>
            <form method="POST" action="keluarga.php" id="formTambahAnggota">
                <input type="hidden" name="no_kk" id="tambah_anggota_no_kk" value="<?php echo $kk['no_kk'] ?? ''; ?>">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Pilih Warga (Penduduk Mandiri) <span class="text-danger">*</span></label>
                        <div class="search-container">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-info"></i></span>
                                <input type="text" class="form-control border-start-0 text-uppercase" id="search_anggota" placeholder="Ketik NIK atau nama untuk mencari..." autocomplete="off">
                            </div>
                            <input type="hidden" name="nik" id="nik_anggota" required>
                            <div id="searchAnggotaResults" class="search-results"></div>
                        </div>
                        <div id="anggotaFeedback" class="mt-2 small text-muted">Hanya menampilkan penduduk yang belum terikat di KK manapun.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status Hubungan Dalam Keluarga (SHDK) <span class="text-danger">*</span></label>
                        <select class="form-select" name="hubungan_keluarga" id="hubungan_keluarga" required>
                            <option value="">-- Pilih Hubungan --</option>
                            <option value="Istri">Istri</option>
                            <option value="Suami">Suami</option>
                            <option value="Anak">Anak</option>
                            <option value="Menantu">Menantu</option>
                            <option value="Cucu">Cucu</option>
                            <option value="Orang Tua">Orang Tua</option>
                            <option value="Mertua">Mertua</option>
                            <option value="Famili Lain">Famili Lain</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                        <div id="hubunganFeedback" class="mt-1 small"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalTambahAnggota()">Batal</button>
                    <button type="submit" name="submit_tambah_anggota" class="btn btn-info text-white px-5 rounded-pill shadow-sm" id="submitAnggotaBtn" disabled><i class="fas fa-save me-2"></i>Tambah Anggota</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmHapusKKModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-gradient-danger text-white py-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle me-2"></i>Peringatan Penghapusan</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalHapusKK()"></button>
            </div>
            <div class="modal-body py-5 text-center">
                <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center mb-4 shadow" style="width: 80px; height: 80px;">
                    <i class="fas fa-trash-alt fa-3x"></i>
                </div>
                <h4 class="text-gray-800 font-weight-bold mb-3">Hapus Seluruh Kartu Keluarga?</h4>
                <p class="text-muted mb-4">Data Kepala Keluarga beserta seluruh anggotanya akan kembali menjadi "Mandiri".</p>
                <div id="confirmHapusKKInfo" class="alert alert-light border text-start shadow-sm mx-auto" style="max-width: 350px;"></div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-center">
                <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalHapusKK()">Batal</button>
                <button type="button" class="btn btn-danger px-5 rounded-pill shadow-sm" id="btnConfirmHapusKK"><i class="fas fa-trash me-1"></i> Ya, Hapus KK</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmHapusAnggotaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-gradient-danger text-white py-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-user-minus me-2"></i>Keluarkan Anggota</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalHapusAnggota()"></button>
            </div>
            <div class="modal-body py-5 text-center">
                <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center mb-4 shadow" style="width: 80px; height: 80px;">
                    <i class="fas fa-user-times fa-3x"></i>
                </div>
                <h4 class="text-gray-800 font-weight-bold mb-3">Keluarkan dari KK?</h4>
                <p class="text-muted mb-4">Warga ini akan dikeluarkan dari struktur KK dan kembali berstatus mandiri.</p>
                <div id="confirmHapusAnggotaInfo" class="alert alert-light border text-start shadow-sm mx-auto" style="max-width: 350px;"></div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-center">
                <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalHapusAnggota()">Batal</button>
                <button type="button" class="btn btn-danger px-5 rounded-pill shadow-sm" id="btnConfirmHapusAnggota"><i class="fas fa-user-minus me-1"></i> Keluarkan</button>
            </div>
        </div>
    </div>
</div>

<script>
// ========== FUNGSI PRINT DATA KELUARGA ==========
function cetakDataKeluarga() {
    const prtContent = document.getElementById("printAreaDetail");
    const WinPrint = window.open('', '', 'left=0,top=0,width=800,height=900,toolbar=0,scrollbars=0,status=0');
    
    let style = `
        <style>
            body { font-family: Arial, sans-serif; font-size: 12pt; margin: 20px; }
            h2 { text-align: center; margin-bottom: 20px; text-decoration: underline;}
            .detail-card { margin-bottom: 30px; }
            .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .table th, .table td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 11pt;}
            .table-borderless td { border: none !important; }
            .text-center { text-align: center !important; }
            .d-print-none { display: none !important; }
            @page { size: A4 landscape; margin: 10mm; }
        </style>
    `;
    
    WinPrint.document.write('<html><head><title>Cetak Kartu Keluarga</title>' + style + '</head><body>');
    WinPrint.document.write('<h2>DATA KARTU KELUARGA</h2>');
    WinPrint.document.write(prtContent.innerHTML);
    WinPrint.document.write('</body></html>');
    WinPrint.document.close();
    WinPrint.focus();
    setTimeout(function() {
        WinPrint.print();
        WinPrint.close();
    }, 500);
}

// ========== FITUR CARI DI TABEL DETAIL ==========
document.getElementById('searchTableAnggota')?.addEventListener('input', function() {
    let filter = this.value.toUpperCase();
    let table = document.getElementById("tabelAnggotaDetail");
    let tr = table.getElementsByTagName("tr");
    for (let i = 1; i < tr.length; i++) {
        let tdName = tr[i].getElementsByTagName("td")[1]; // Index Kolom Nama
        let tdNik = tr[i].getElementsByTagName("td")[2]; // Index Kolom NIK
        if (tdName || tdNik) {
            let txtValueName = tdName.textContent || tdName.innerText;
            let txtValueNik = tdNik.textContent || tdNik.innerText;
            if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueNik.indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
});

// ========== FUNGSI COPY CLIPBOARD ==========
function copyText(elementId) {
    var copyText = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(copyText).then(function() {
        showSuccess("Disalin: " + copyText);
    }, function() { showErrorMessage("Gagal menyalin text."); });
}

// ========== AUTO CAPITALIZE ==========
document.addEventListener('DOMContentLoaded', function() {
    const editableElements = document.querySelectorAll('input.text-uppercase, textarea.text-uppercase');
    editableElements.forEach(el => {
        el.addEventListener('input', function() {
            const start = this.selectionStart; const end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, end);
        });
    });
});

// ========== VARIABEL GLOBAL & INISIALISASI MODAL ==========
let modalTambahKKInstance = null; let modalEditKKInstance = null;
let modalTambahAnggotaInstance = null; let modalHapusKKInstance = null; let modalHapusAnggotaInstance = null;
let searchKepalaTimeout = null; let searchAnggotaTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('tambahKKModal')) modalTambahKKInstance = new bootstrap.Modal(document.getElementById('tambahKKModal'));
    if (document.getElementById('editKKModal')) modalEditKKInstance = new bootstrap.Modal(document.getElementById('editKKModal'));
    if (document.getElementById('tambahAnggotaModal')) modalTambahAnggotaInstance = new bootstrap.Modal(document.getElementById('tambahAnggotaModal'));
    if (document.getElementById('confirmHapusKKModal')) modalHapusKKInstance = new bootstrap.Modal(document.getElementById('confirmHapusKKModal'));
    if (document.getElementById('confirmHapusAnggotaModal')) modalHapusAnggotaInstance = new bootstrap.Modal(document.getElementById('confirmHapusAnggotaModal'));
    
    initSearchKepala();
    
    // Tombol Tambah KK
    const btnTambah = document.getElementById('btnTambahKK');
    if (btnTambah) {
        btnTambah.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('formTambahKK')?.reset();
            document.getElementById('search_kepala').value = '';
            document.getElementById('nik_kepala').value = '';
            document.getElementById('kkFeedback').innerHTML = '';
            document.getElementById('no_kk').classList.remove('is-invalid', 'is-valid');
            document.getElementById('kepalaFeedback').innerHTML = '<span class="text-muted">Data penduduk harus mandiri (belum punya KK)</span>';
            document.getElementById('submitTambahKKBtn').disabled = true;
            
            document.getElementById('dusun_pilih').checked = true; toggleDusun('pilih');
            document.getElementById('rt').value = '001'; document.getElementById('rw').value = '002';
            document.getElementById('desa_kel').value = 'SUKOLILO TIMUR'; document.getElementById('kecamatan').value = 'LABANG';
            document.getElementById('kabupaten_kota').value = 'BANGKALAN'; document.getElementById('provinsi').value = 'JAWA TIMUR';
            document.getElementById('kode_pos').value = '69162';
            
            if (modalTambahKKInstance) modalTambahKKInstance.show();
        });
    }

    // Tombol Tambah Anggota
    const btnTambahAnggota = document.getElementById('btnTambahAnggota');
    if (btnTambahAnggota) {
        btnTambahAnggota.addEventListener('click', function(e) {
            e.preventDefault();
            const no_kk = this.getAttribute('data-no-kk');
            document.getElementById('formTambahAnggota')?.reset();
            document.getElementById('search_anggota').value = '';
            document.getElementById('nik_anggota').value = '';
            document.getElementById('tambah_anggota_no_kk').value = no_kk;
            document.getElementById('anggotaFeedback').innerHTML = '';
            document.getElementById('submitAnggotaBtn').disabled = true;
            
            initSearchAnggota(no_kk);
            if (modalTambahAnggotaInstance) modalTambahAnggotaInstance.show();
        });
    }

    // Bind Edit KK
    document.querySelectorAll('.btn-edit-kk, .btn-edit-kk-detail').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            loadEditData(this.getAttribute('data-no-kk'), this, this.classList.contains('btn-edit-kk-detail'));
        });
    });

    // Bind Delete KK
    document.querySelectorAll('.btn-hapus-kk, .btn-hapus-kk-detail').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const no_kk = this.getAttribute('data-no-kk');
            const kepala = this.getAttribute('data-kepala');
            const infoDiv = document.getElementById('confirmHapusKKInfo');
            if (infoDiv) infoDiv.innerHTML = `<div class="mb-2 border-bottom pb-2"><strong>Nomor KK:</strong> <br><span class="text-danger fs-5">${no_kk}</span></div><div><strong>Kepala Keluarga:</strong> <br><span class="text-dark font-weight-bold">${kepala}</span></div>`;
            document.getElementById('btnConfirmHapusKK')?.setAttribute('data-no-kk', no_kk);
            if (modalHapusKKInstance) modalHapusKKInstance.show();
        });
    });

    // Bind Delete Anggota
    document.querySelectorAll('.btn-hapus-anggota').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id_anggota = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const infoDiv = document.getElementById('confirmHapusAnggotaInfo');
            if (infoDiv) infoDiv.innerHTML = `<div class="font-weight-bold text-danger fs-5 mb-1">${nama}</div><div class="text-muted small">NIK: ${this.getAttribute('data-nik')}</div>`;
            const cBtn = document.getElementById('btnConfirmHapusAnggota');
            if (cBtn) { cBtn.setAttribute('data-id', id_anggota); cBtn.setAttribute('data-no-kk', this.getAttribute('data-no-kk')); cBtn.setAttribute('data-nama', nama); }
            if (modalHapusAnggotaInstance) modalHapusAnggotaInstance.show();
        });
    });

    // Validasi Submit Modal Forms
    document.getElementById('formTambahKK')?.addEventListener('submit', function(e) { if(!validateFormTambahKK()) e.preventDefault(); });
    document.getElementById('formTambahAnggota')?.addEventListener('submit', function(e) { if(!validateFormAnggota()) e.preventDefault(); });
    document.getElementById('hubungan_keluarga')?.addEventListener('change', function() { validateFormAnggota(); });
    document.getElementById('no_kk')?.addEventListener('input', checkNoKK);

    setTimeout(() => { document.querySelectorAll('.alert-dismissible').forEach(alert => { try { bootstrap.Alert.getInstance(alert)?.close(); } catch(e){} }); }, 5000);
});

// ========== FUNGSI MODAL CONTROLS ==========
function closeModalTambahKK() { if (modalTambahKKInstance) modalTambahKKInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalEditKK() { if (modalEditKKInstance) modalEditKKInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalTambahAnggota() { if (modalTambahAnggotaInstance) modalTambahAnggotaInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalHapusKK() { if (modalHapusKKInstance) modalHapusKKInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalHapusAnggota() { if (modalHapusAnggotaInstance) modalHapusAnggotaInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function cleanupBackdrop() { document.querySelectorAll('.modal-backdrop').forEach(b => b.remove()); document.body.classList.remove('modal-open'); document.body.style.overflow=''; document.body.style.paddingRight=''; }
function closeAlert(element) { const alert = element.closest('.alert'); if (alert) alert.remove(); }
function showSuccess(message) { const html = `<div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-lg position-fixed top-0 end-0 m-3" style="z-index:9999;"><i class="fas fa-check-circle me-2 fs-5 align-middle"></i><span class="align-middle">${message}</span><button type="button" class="btn-close" onclick="closeAlert(this)"></button></div>`; document.body.insertAdjacentHTML('beforeend', html); setTimeout(() => { const a = document.querySelector('.alert-success.position-fixed'); if(a) a.remove(); }, 3000); }
function showErrorMessage(message) { const html = `<div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-lg position-fixed top-0 end-0 m-3" style="z-index:9999;"><i class="fas fa-exclamation-circle me-2 fs-5 align-middle"></i><span class="align-middle">${message}</span><button type="button" class="btn-close" onclick="closeAlert(this)"></button></div>`; document.body.insertAdjacentHTML('beforeend', html); setTimeout(() => { const a = document.querySelector('.alert-danger.position-fixed'); if(a) a.remove(); }, 5000); }

// ========== AJAX HAPUS ==========
document.getElementById('btnConfirmHapusKK')?.addEventListener('click', function() {
    const no_kk = this.getAttribute('data-no-kk');
    const origTxt = this.innerHTML; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...'; this.disabled = true;
    fetch('keluarga.php?ajax_hapus_kk=' + encodeURIComponent(no_kk)).then(res => res.json()).then(data => {
        if (modalHapusKKInstance) modalHapusKKInstance.hide();
        if (data.success) { showSuccess('KK Dihapus.'); setTimeout(() => location.href='keluarga.php', 1000); }
        else { showErrorMessage(data.message); this.innerHTML = origTxt; this.disabled = false; }
    }).catch(err => { showErrorMessage('Error jaringan.'); this.innerHTML = origTxt; this.disabled = false; });
});

document.getElementById('btnConfirmHapusAnggota')?.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    const origTxt = this.innerHTML; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...'; this.disabled = true;
    fetch('keluarga.php?ajax_hapus_anggota=' + encodeURIComponent(id)).then(res => res.json()).then(data => {
        if (modalHapusAnggotaInstance) modalHapusAnggotaInstance.hide();
        if (data.success) { showSuccess('Anggota Dihapus.'); setTimeout(() => location.reload(), 1000); }
        else { showErrorMessage(data.message); this.innerHTML = origTxt; this.disabled = false; }
    }).catch(err => { showErrorMessage('Error jaringan.'); this.innerHTML = origTxt; this.disabled = false; });
});

// ========== FUNGSI EDIT LOAD DATA KK ==========
function loadEditData(no_kk, button, fromDetail = false) {
    const orig = button.innerHTML; button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; button.disabled = true;
    fetch('keluarga.php?ajax_get_kk=' + encodeURIComponent(no_kk)).then(res => res.json()).then(result => {
        button.innerHTML = orig; button.disabled = false;
        if (result.success) {
            const data = result.data;
            document.getElementById('edit_no_kk').value = data.no_kk;
            document.getElementById('edit_no_kk_display').value = data.no_kk;
            document.getElementById('edit_kepala').value = data.nama_kepala + ' (' + data.nik_kepala + ')';
            document.getElementById('edit_alamat_kk').value = data.alamat_kk || '';
            document.getElementById('edit_rt').value = data.rt || '';
            document.getElementById('edit_rw').value = data.rw || '';
            document.getElementById('edit_desa_kel').value = data.desa_kel || '';
            document.getElementById('edit_kecamatan').value = data.kecamatan || '';
            document.getElementById('edit_kabupaten_kota').value = data.kabupaten_kota || '';
            document.getElementById('edit_provinsi').value = data.provinsi || '';
            document.getElementById('edit_kode_pos').value = data.kode_pos || '';
            document.getElementById('edit_from_detail').value = fromDetail ? 'true' : 'false';
            
            if (data.dusun) {
                const sel = document.getElementById('edit_dusun_select');
                let found = false;
                for (let i=0; i<sel.options.length; i++) {
                    if (sel.options[i].value === data.dusun) { sel.value = data.dusun; document.getElementById('edit_dusun_pilih').checked = true; toggleEditDusun('pilih'); found = true; break; }
                }
                if (!found) { document.getElementById('edit_dusun_custom').value = data.dusun; document.getElementById('edit_dusun_tulis').checked = true; toggleEditDusun('tulis'); }
            } else { document.getElementById('edit_dusun_pilih').checked = true; toggleEditDusun('pilih'); }
            
            if (modalEditKKInstance) modalEditKKInstance.show();
        } else alert('Gagal memuat data KK: ' + result.message);
    }).catch(err => { button.innerHTML = orig; button.disabled = false; alert('Error jaringan.'); });
}

// ========== FUNGSI LIVE SEARCH & VALIDASI ==========
function toggleDusun(mode) {
    if (mode === 'pilih') { document.getElementById('dusun_select_container').style.display = 'block'; document.getElementById('dusun_input_container').style.display = 'none'; document.getElementById('dusun_custom').value = ''; updateDusunHidden('select'); }
    else { document.getElementById('dusun_select_container').style.display = 'none'; document.getElementById('dusun_input_container').style.display = 'block'; document.getElementById('dusun_select').value = ''; updateDusunHidden('input'); }
}
function updateDusunHidden(source) { document.getElementById('dusun_hidden').value = source === 'select' ? document.getElementById('dusun_select').value : document.getElementById('dusun_custom').value; }
function toggleEditDusun(mode) {
    if (mode === 'pilih') { document.getElementById('edit_dusun_select_container').style.display = 'block'; document.getElementById('edit_dusun_input_container').style.display = 'none'; document.getElementById('edit_dusun_custom').value = ''; updateEditDusunHidden('select'); }
    else { document.getElementById('edit_dusun_select_container').style.display = 'none'; document.getElementById('edit_dusun_input_container').style.display = 'block'; document.getElementById('edit_dusun_select').value = ''; updateEditDusunHidden('input'); }
}
function updateEditDusunHidden(source) { document.getElementById('edit_dusun_hidden').value = source === 'select' ? document.getElementById('edit_dusun_select').value : document.getElementById('edit_dusun_custom').value; }

function checkNoKK() {
    const val = document.getElementById('no_kk').value.trim(); const fb = document.getElementById('kkFeedback'); const btn = document.getElementById('submitTambahKKBtn');
    document.getElementById('no_kk').classList.remove('is-invalid', 'is-valid'); fb.innerHTML = '';
    if (!val || val.length !== 16 || !/^\d+$/.test(val)) { fb.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Harus 16 digit angka</span>'; btn.disabled=true; return; }
    fb.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Cek database...</span>'; btn.disabled=true;
    fetch('keluarga.php?check_kk=' + encodeURIComponent(val)).then(res=>res.json()).then(data=>{
        if(data.exists){ document.getElementById('no_kk').classList.add('is-invalid'); fb.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> No KK sudah terdaftar!</span>'; btn.disabled=true; }
        else{ document.getElementById('no_kk').classList.add('is-valid'); fb.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> No KK tersedia.</span>'; if(document.getElementById('nik_kepala').value) btn.disabled=false; }
    });
}

function initSearchKepala() {
    const input = document.getElementById('search_kepala'); const res = document.getElementById('searchKepalaResults');
    if(!input) return;
    input.addEventListener('input', function() {
        const kw = this.value.trim();
        if(kw.length<2){ res.style.display='none'; document.getElementById('nik_kepala').value=''; document.getElementById('submitTambahKKBtn').disabled=true; return; }
        clearTimeout(searchKepalaTimeout);
        searchKepalaTimeout = setTimeout(()=>{
            fetch('keluarga.php?search_kepala='+encodeURIComponent(kw)).then(r=>r.json()).then(d=>{
                if(d.success && d.data.length>0){
                    let h=''; d.data.forEach(i=>{ h+=`<div class="search-result-item" onclick="pilihKepala('${i.nik}', '${i.nama_penduduk}')"><strong>${i.nama_penduduk}</strong><small>NIK: ${i.nik}</small></div>`; });
                    res.innerHTML=h; res.style.display='block';
                } else { res.innerHTML='<div class="search-result-item text-muted">Tidak ada penduduk mandiri.</div>'; res.style.display='block'; }
            });
        }, 300);
    });
    document.addEventListener('click', e=>{ if(!input.contains(e.target) && !res.contains(e.target)) res.style.display='none'; });
}

function pilihKepala(nik, nama) {
    document.getElementById('search_kepala').value = nama + ' (' + nik + ')';
    document.getElementById('nik_kepala').value = nik;
    document.getElementById('searchKepalaResults').style.display = 'none';
    document.getElementById('kepalaFeedback').innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Kepala keluarga terpilih</span>';
    validateFormTambahKK();
}

function initSearchAnggota(no_kk) {
    const input = document.getElementById('search_anggota'); const res = document.getElementById('searchAnggotaResults');
    if(!input) return;
    const newInp = input.cloneNode(true); input.parentNode.replaceChild(newInp, input);
    newInp.addEventListener('input', function() {
        const kw = this.value.trim();
        if(kw.length<2){ res.style.display='none'; document.getElementById('nik_anggota').value=''; document.getElementById('submitAnggotaBtn').disabled=true; return; }
        clearTimeout(searchAnggotaTimeout);
        searchAnggotaTimeout = setTimeout(()=>{
            fetch('keluarga.php?search_anggota='+encodeURIComponent(kw)+'&no_kk='+encodeURIComponent(no_kk)).then(r=>r.json()).then(d=>{
                if(d.success && d.data.length>0){
                    let h=''; d.data.forEach(i=>{ h+=`<div class="search-result-item" onclick="pilihAnggota('${i.nik}', '${i.nama_penduduk}')"><strong>${i.nama_penduduk}</strong><small>NIK: ${i.nik}</small></div>`; });
                    res.innerHTML=h; res.style.display='block';
                } else { res.innerHTML='<div class="search-result-item text-muted">Tidak ada penduduk tersedia.</div>'; res.style.display='block'; }
            });
        }, 300);
    });
    document.addEventListener('click', e=>{ if(!newInp.contains(e.target) && !res.contains(e.target)) res.style.display='none'; });
}

function pilihAnggota(nik, nama) {
    document.getElementById('search_anggota').value = nama + ' (' + nik + ')';
    document.getElementById('nik_anggota').value = nik;
    document.getElementById('searchAnggotaResults').style.display = 'none';
    document.getElementById('anggotaFeedback').innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Anggota terpilih</span>';
    validateFormAnggota();
}

function validateFormTambahKK() {
    const kk = document.getElementById('no_kk')?.value; const kp = document.getElementById('nik_kepala')?.value; const btn = document.getElementById('submitTambahKKBtn');
    if(!kk || kk.length!==16 || !kp) { if(btn) btn.disabled=true; return false; }
    if(btn) btn.disabled=false; return true;
}

function validateFormAnggota() {
    const nik = document.getElementById('nik_anggota')?.value; const hub = document.getElementById('hubungan_keluarga')?.value; const btn = document.getElementById('submitAnggotaBtn');
    if(!nik || !hub) { if(btn) btn.disabled=true; return false; }
    if(btn) btn.disabled=false; return true;
}
</script>

<?php
$content = ob_get_clean();
include '../includes/base.php';
?>