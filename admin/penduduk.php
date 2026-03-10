<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

include "../db/koneksi.php";
include "../db/funct.php";

// ==================== AJAX HANDLER ====================

// AJAX: Get Penduduk by NIK untuk Edit/View
if (isset($_GET['ajax_get_penduduk'])) {
    header('Content-Type: application/json');
    $nik = mysqli_real_escape_string($conn, $_GET['ajax_get_penduduk']);
    $query = "SELECT * FROM penduduk WHERE nik = '$nik'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    }
    exit();
}

// AJAX: Hapus Penduduk
if (isset($_GET['ajax_hapus'])) {
    header('Content-Type: application/json');
    $nik = mysqli_real_escape_string($conn, $_GET['ajax_hapus']);
    $result = hapus_penduduk($nik);
    
    if ($result > 0) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus']);
    } elseif ($result == -2) {
        echo json_encode(['success' => false, 'error_code' => -2, 'message' => 'Data ini adalah Kepala Keluarga']);
    } elseif ($result == -3) {
        echo json_encode(['success' => false, 'error_code' => -3, 'message' => 'Data ini adalah Anggota Keluarga']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data']);
    }
    exit();
}

// AJAX: Check NIK
if (isset($_GET['check_nik'])) {
    header('Content-Type: application/json');
    $nik = mysqli_real_escape_string($conn, $_GET['check_nik']);
    
    if (empty($nik)) {
        echo json_encode(['exists' => false, 'valid' => false, 'message' => 'NIK kosong']);
        exit();
    }
    if (!is_numeric($nik)) {
        echo json_encode(['exists' => false, 'valid' => false, 'message' => 'NIK harus angka']);
        exit();
    }
    if (strlen($nik) !== 16) {
        echo json_encode(['exists' => false, 'valid' => false, 'message' => 'NIK harus 16 digit']);
        exit();
    }
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM penduduk WHERE nik = '$nik'");
    $row = mysqli_fetch_assoc($result);
    $exists = ($row['count'] > 0);
    
    echo json_encode(['exists' => $exists, 'valid' => true]);
    exit();
}

// ==================== PROSES FORM TAMBAH PENDUDUK ====================
if (isset($_POST["submit_tambah"])) {
    if (empty($_POST["nik"]) || empty($_POST["nama_penduduk"]) || empty($_POST["tanggal_lahir"])) {
        $_SESSION['error_message'] = "NIK, Nama, dan Tanggal Lahir wajib diisi!";
        header("Location: penduduk.php");
        exit();
    }
    
    if (isset($_POST['dusun_option']) && $_POST['dusun_option'] == 'pilih') {
        $_POST['dusun'] = $_POST['dusun_select'] ?? '';
    } else {
        $_POST['dusun'] = $_POST['dusun_custom'] ?? '';
    }
    
    $result = add_penduduk($_POST);
    
    if ($result > 0) $_SESSION['success_message'] = "Data penduduk berhasil ditambahkan!";
    elseif ($result == -1) $_SESSION['error_message'] = "NIK sudah terdaftar!";
    elseif ($result == -4) $_SESSION['error_message'] = "NIK harus 16 digit!";
    else $_SESSION['error_message'] = "Gagal menambahkan data penduduk!";
    
    header("Location: penduduk.php");
    exit();
}

// ==================== PROSES FORM EDIT PENDUDUK ====================
if (isset($_POST["submit_edit"])) {
    if (empty($_POST["nik"]) || empty($_POST["nama_penduduk"]) || empty($_POST["tanggal_lahir"])) {
        $_SESSION['error_message'] = "NIK, Nama, dan Tanggal Lahir wajib diisi!";
        header("Location: penduduk.php");
        exit();
    }
    
    if (isset($_POST['edit_dusun_option']) && $_POST['edit_dusun_option'] == 'pilih') {
        $_POST['dusun'] = $_POST['edit_dusun_select'] ?? '';
    } else {
        $_POST['dusun'] = $_POST['edit_dusun_custom'] ?? '';
    }
    
    $result = edit_penduduk($_POST);
    
    if ($result >= 0) $_SESSION['success_message'] = "Data penduduk berhasil diupdate!";
    elseif ($result == -4) $_SESSION['error_message'] = "NIK harus 16 digit!";
    else $_SESSION['error_message'] = "Gagal mengupdate data penduduk!";
    
    header("Location: penduduk.php");
    exit();
}

// ==================== QUERY STATISTIK & DATA ====================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_dusun = isset($_GET['filter_dusun']) ? mysqli_real_escape_string($conn, $_GET['filter_dusun']) : '';
$filter_jk = isset($_GET['filter_jk']) ? mysqli_real_escape_string($conn, $_GET['filter_jk']) : '';

$where = "WHERE 1=1";
if (!empty($search)) $where .= " AND (nik LIKE '%$search%' OR nama_penduduk LIKE '%$search%' OR alamat LIKE '%$search%')";
if (!empty($filter_dusun)) $where .= " AND dusun = '$filter_dusun'";
if (!empty($filter_jk)) $where .= " AND jenis_kelamin = '$filter_jk'";

$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$query_total = "SELECT COUNT(*) as total FROM penduduk $where";
$result_total = mysqli_query($conn, $query_total);
$total_data = mysqli_fetch_assoc($result_total)['total'];
$total_pages = ceil($total_data / $limit);

$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM kartu_keluarga WHERE nik_kepala = p.nik) as is_kepala,
          (SELECT COUNT(*) FROM anggota_keluarga WHERE nik = p.nik AND hubungan_keluarga != 'Kepala Keluarga') as is_anggota
          FROM penduduk p 
          $where 
          ORDER BY p.nama_penduduk ASC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

$stat_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penduduk"))['total'];
$stat_lk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'LAKI-LAKI'"))['total'];
$stat_pr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'PEREMPUAN'"))['total'];
$stat_kk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kartu_keluarga"))['total'];

$daftar_dusun = ['KEJAWAN', 'SEPURAN', 'BUDDAN', 'PASEREAN', 'LANGGAR', 'MORLEKE', 'PREGIH', 'KARANG PANDAN', 'PONG BARU', 'KRASAK', 'PERUM BASMALAH'];

$pageTitle = "Data Master Penduduk";
ob_start();
?>

<style>
/* ===== UI MODERNISASI ===== */
body { background-color: #f8f9fc; }

.statistik-card { transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: none; border-radius: 1rem; box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.04); background: white; overflow: hidden; }
.statistik-card:hover { transform: translateY(-5px); box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.1); }
.border-left-primary { border-left: 5px solid #4e73df !important; }
.border-left-success { border-left: 5px solid #1cc88a !important; }
.border-left-info { border-left: 5px solid #36b9cc !important; }
.border-left-warning { border-left: 5px solid #f6c23e !important; }

.main-card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); }
.main-card > .card-header { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border-bottom: none; border-radius: 1rem 1rem 0 0; color: white;}

.table-container { background: white; border-radius: 0 0 1rem 1rem; padding: 0 10px 15px 10px; }
.table thead th { background-color: #f8f9fc; border-bottom: 2px solid #eaecf4; color: #4e73df; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 15px; }
.table tbody tr { transition: background 0.2s; }
.table tbody tr:hover { background-color: #f1f3f9; }
.table td { vertical-align: middle; color: #5a5c69; border-bottom: 1px solid #eaecf4; padding: 12px 15px; }

.badge-status { padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.7rem; letter-spacing: 0.3px;}
.bg-kk { background-color: #fef0c7; color: #f6c23e; border: 1px solid #f6c23e;}
.bg-anggota { background-color: #e0f2f4; color: #36b9cc; border: 1px solid #36b9cc;}
.bg-mandiri { background-color: #e3e6f0; color: #858796; border: 1px solid #d1d3e2;}
.gender-icon { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.8rem; margin-right: 8px;}
.icon-L { background-color: #e3f2fd; color: #36b9cc; }
.icon-P { background-color: #fce4e4; color: #e74a3b; }

.btn-group-action { display: flex; gap: 6px; justify-content: center; }
.action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; color: white; transition: all 0.2s; border: none; cursor: pointer; }
.action-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); filter: brightness(1.1); }
.icon-view { background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%); }
.icon-edit { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); }
.icon-info { background: linear-gradient(135deg, #858796 0%, #60616f 100%); }
.icon-delete { background: #eaecf4; color: #e74a3b; border: 1px solid #e74a3b; }
.icon-delete:hover { background: #e74a3b; color: white; }

.btn-copy { background: transparent; border: none; color: #4e73df; cursor: pointer; padding: 2px 5px; border-radius: 4px; transition: 0.2s; }
.btn-copy:hover { background: #eaecf4; }

/* FIX MODAL SCROLLING & STYLE */
.modal-content { border: none; border-radius: 1rem; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow: hidden; display: flex; flex-direction: column; max-height: 100%;}
.modal-header { border-bottom: none; padding: 1.25rem 1.5rem; flex-shrink: 0;}
.modal-body { padding: 1.5rem; overflow-y: auto; flex-grow: 1; }
.modal-footer { border-top: 1px solid #eaecf4; background: #f8f9fc; padding: 1rem 1.5rem; flex-shrink: 0;}

/* Scrollbar Modals */
.modal-body::-webkit-scrollbar { width: 8px; }
.modal-body::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.modal-body::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
.modal-body::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

.form-section-title { font-size: 0.85rem; font-weight: 700; color: #4e73df; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #eaecf4; padding-bottom: 8px; margin-bottom: 15px; margin-top: 10px;}
.form-control, .form-select { border-radius: 8px; border: 1px solid #d1d3e2; padding: 0.6rem 1rem; font-size: 0.9rem; background-color: #fdfdfd;}
.form-control:focus, .form-select:focus { border-color: #4e73df; box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25); background-color: #fff;}
.form-label { font-weight: 600; color: #5a5c69; font-size: 0.85rem; margin-bottom: 4px;}

.pagination .page-link { border-radius: 6px; margin: 0 3px; border: none; color: #5a5c69; font-weight: 600;}
.pagination .page-item.active .page-link { background-color: #4e73df; color: white; box-shadow: 0 2px 5px rgba(78, 115, 223, 0.3); }

/* Animasi Live Age */
.live-age-badge { transition: all 0.3s ease; opacity: 0; transform: translateY(-5px); display: inline-block;}
.live-age-badge.show { opacity: 1; transform: translateY(0); }
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

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card statistik-card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Penduduk</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_total); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card statistik-card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Laki-Laki</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_lk); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-male fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4 mb-md-0">
            <div class="card statistik-card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Perempuan</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_pr); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-female fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card statistik-card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kepala Keluarga (KK)</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_kk); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-address-card fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card mb-5">
        <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-database me-2"></i>Database Penduduk Desa</h6>
            <button type="button" class="btn btn-light text-primary font-weight-bold shadow-sm rounded-pill px-4" id="btnTambahPenduduk">
                <i class="fas fa-user-plus me-1"></i> Entri Warga Baru
            </button>
        </div>
        
        <div class="card-body bg-white pb-0">
            <form method="GET" class="mb-4 bg-light p-3 rounded-lg border">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Filter Dusun</label>
                        <select class="form-select form-select-sm" name="filter_dusun">
                            <option value="">Semua Dusun</option>
                            <?php foreach ($daftar_dusun as $dsn): ?>
                                <option value="<?php echo $dsn; ?>" <?php echo ($filter_dusun == $dsn) ? 'selected' : ''; ?>><?php echo $dsn; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Jenis Kelamin</label>
                        <select class="form-select form-select-sm" name="filter_jk">
                            <option value="">Semua</option>
                            <option value="LAKI-LAKI" <?php echo ($filter_jk == 'LAKI-LAKI') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="PEREMPUAN" <?php echo ($filter_jk == 'PEREMPUAN') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Pencarian Data</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-primary"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" placeholder="Cari NIK, Nama, atau Alamat..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i> Filter</button>
                        <?php if (!empty($search) || !empty($filter_dusun) || !empty($filter_jk)): ?>
                            <a href="penduduk.php" class="btn btn-secondary btn-sm" title="Reset"><i class="fas fa-redo"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="22%">Nama Lengkap</th>
                            <th width="15%">Nomor Induk (NIK)</th>
                            <th width="18%">TTL</th>
                            <th width="15%">Dusun / Alamat</th>
                            <th width="12%" class="text-center">Status KK</th>
                            <th width="13%" class="text-center">Aksi Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="text-center text-muted"><?php echo $no++; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="gender-icon <?php echo $row['jenis_kelamin'] == 'LAKI-LAKI' ? 'icon-L' : 'icon-P'; ?>" title="<?php echo $row['jenis_kelamin']; ?>">
                                            <i class="fas <?php echo $row['jenis_kelamin'] == 'LAKI-LAKI' ? 'fa-mars' : 'fa-venus'; ?>"></i>
                                        </div>
                                        <span class="font-weight-bold text-gray-800 text-uppercase"><?php echo htmlspecialchars($row['nama_penduduk']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-family-monospace" id="nik_<?php echo $row['nik']; ?>"><?php echo htmlspecialchars($row['nik']); ?></span>
                                    <button class="btn-copy ms-1" onclick="copyText('nik_<?php echo $row['nik']; ?>')" title="Salin NIK"><i class="far fa-copy"></i></button>
                                </td>
                                <td>
                                    <div class="small text-uppercase"><?php echo htmlspecialchars($row['tempat_lahir']); ?></div>
                                    <div class="small text-muted"><?php echo date('d M Y', strtotime($row['tanggal_lahir'])); ?></div>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-primary small"><?php echo htmlspecialchars($row['dusun'] ?? '-'); ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($row['alamat']); ?>"><?php echo htmlspecialchars($row['alamat']); ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['is_kepala'] > 0): ?>
                                        <span class="badge-status bg-kk"><i class="fas fa-crown me-1"></i> Kepala KK</span>
                                    <?php elseif ($row['is_anggota'] > 0): ?>
                                        <span class="badge-status bg-anggota"><i class="fas fa-user-friends me-1"></i> Anggota</span>
                                    <?php else: ?>
                                        <span class="badge-status bg-mandiri">Mandiri</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group-action">
                                        <button class="action-icon icon-view btn-view" data-nik="<?php echo htmlspecialchars($row['nik']); ?>" title="Profil Lengkap"><i class="fas fa-eye"></i></button>
                                        <button class="action-icon icon-edit btn-edit" data-nik="<?php echo htmlspecialchars($row['nik']); ?>" title="Edit Data"><i class="fas fa-edit"></i></button>
                                        <?php if ($row['is_kepala'] > 0 || $row['is_anggota'] > 0): ?>
                                            <button class="action-icon icon-info btn-info-penggunaan" data-nik="<?php echo htmlspecialchars($row['nik']); ?>" data-nama="<?php echo htmlspecialchars($row['nama_penduduk']); ?>" data-is-kepala="<?php echo $row['is_kepala'] > 0 ? 'true' : 'false'; ?>" data-is-anggota="<?php echo $row['is_anggota'] > 0 ? 'true' : 'false'; ?>" title="Info Terikat KK"><i class="fas fa-link"></i></button>
                                        <?php else: ?>
                                            <button class="action-icon icon-delete btn-hapus" data-nik="<?php echo htmlspecialchars($row['nik']); ?>" title="Hapus Permanen"><i class="fas fa-trash-alt"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-users-slash fa-3x text-gray-300 mb-3"></i>
                                    <h5 class="text-gray-500">Data Penduduk Kosong</h5>
                                    <p class="text-muted">Tidak ada data yang sesuai dengan pencarian Anda.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-3">
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm shadow-sm">
                        <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&filter_dusun=<?php echo urlencode($filter_dusun); ?>&filter_jk=<?php echo urlencode($filter_jk); ?>"><i class="fas fa-chevron-left"></i></a></li>
                        <?php endif; ?>

                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_dusun=<?php echo urlencode($filter_dusun); ?>&filter_jk=<?php echo urlencode($filter_jk); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&filter_dusun=<?php echo urlencode($filter_dusun); ?>&filter_jk=<?php echo urlencode($filter_jk); ?>"><i class="fas fa-chevron-right"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="tambahPendudukModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="penduduk.php" id="formTambahPenduduk" onsubmit="return validateFormTambah()" style="display:flex; flex-direction:column; height:100%;">
                
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus me-2"></i>Entri Data Penduduk Baru</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModalTambah()"></button>
                </div>
                
                <div class="modal-body bg-light p-4">
                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <div class="form-section-title"><i class="fas fa-id-card me-2"></i>Data Utama Pribadi</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor Induk Kependudukan (NIK) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nik" id="tambah_nik" required maxlength="16" pattern="[0-9]{16}" placeholder="Masukkan 16 digit angka NIK" oninput="checkNIKTambah(this.value)">
                                <div id="tambah_nik_feedback" class="mt-1 small"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="nama_penduduk" id="tambah_nama" required placeholder="Sesuai KTP">
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" class="form-control text-uppercase" name="tempat_lahir" id="tambah_tempat_lahir" placeholder="Contoh: BANGKALAN">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="date" class="form-control" name="tanggal_lahir" id="tambah_tanggal_lahir" required max="<?php echo date('Y-m-d'); ?>" onchange="calculateAgeLive(this.value, 'tambah_umur_badge')">
                                    <span id="tambah_umur_badge" class="badge bg-info p-2 live-age-badge shadow-sm" style="font-size: 0.85rem;"><i class="fas fa-birthday-cake me-1"></i> -</span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kelamin</label>
                                <select class="form-select" name="jenis_kelamin" id="tambah_jenis_kelamin">
                                    <option value="LAKI-LAKI">Laki-Laki</option>
                                    <option value="PEREMPUAN">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Agama</label>
                                <select class="form-select" name="agama" id="tambah_agama">
                                    <option value="ISLAM">Islam</option>
                                    <option value="KRISTEN">Kristen Protestan</option>
                                    <option value="KATOLIK">Katolik</option>
                                    <option value="HINDU">Hindu</option>
                                    <option value="BUDDHA">Buddha</option>
                                    <option value="KONGHUCU">Konghucu</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <div class="form-section-title"><i class="fas fa-info-circle me-2"></i>Detail Lanjutan</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Status Perkawinan</label>
                                <select class="form-select" name="status_kawin" id="tambah_status_kawin">
                                    <option value="Belum Kawin">Belum Kawin</option>
                                    <option value="Kawin">Kawin</option>
                                    <option value="Cerai Hidup">Cerai Hidup</option>
                                    <option value="Cerai Mati">Cerai Mati</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pendidikan Terakhir</label>
                                <select class="form-select" name="pendidikan" id="tambah_pendidikan">
                                    <option value="">-- Pilih Pendidikan --</option>
                                    <option value="TIDAK SEKOLAH">Tidak Sekolah</option>
                                    <option value="SD">SD/Sederajat</option>
                                    <option value="SMP">SMP/Sederajat</option>
                                    <option value="SMA">SMA/Sederajat</option>
                                    <option value="D1">Diploma I</option>
                                    <option value="D3">Diploma III</option>
                                    <option value="S1">Strata I (S1)</option>
                                    <option value="S2">Strata II (S2)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Pekerjaan</label>
                                <input type="text" class="form-control text-uppercase" name="pekerjaan" id="tambah_pekerjaan" placeholder="Contoh: WIRASWASTA, PETANI">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Ayah</label>
                                <input type="text" class="form-control text-uppercase" name="nama_ayah" id="tambah_nama_ayah" placeholder="Nama lengkap Ayah">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Ibu</label>
                                <input type="text" class="form-control text-uppercase" name="nama_ibu" id="tambah_nama_ibu" placeholder="Nama lengkap Ibu">
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4">
                        <div class="form-section-title"><i class="fas fa-map-marked-alt me-2"></i>Informasi Alamat Domisili</div>
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
                            <div class="col-md-2">
                                <label class="form-label">RT/RW</label>
                                <input type="text" class="form-control" name="rt_rw" id="tambah_rt_rw" value="001/002" placeholder="001/002">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" class="form-control" name="kodepos" id="tambah_kodepos" value="69162" maxlength="5">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Alamat Lengkap (Jalan/Gang)</label>
                                <textarea class="form-control text-uppercase" name="alamat" id="tambah_alamat" rows="2" placeholder="Contoh: JL. RAYA SUKOLILO NO. 12"></textarea>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Desa / Kelurahan</label>
                                <input type="text" class="form-control text-uppercase" name="kel_des" id="tambah_kel_des" value="SUKOLILO TIMUR">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kecamatan</label>
                                <input type="text" class="form-control text-uppercase" name="kecamatan" id="tambah_kecamatan" value="LABANG">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kabupaten</label>
                                <input type="text" class="form-control text-uppercase" name="kabupaten_kota" id="tambah_kabupaten_kota" value="BANGKALAN">
                            </div>
                        </div>
                        <input type="hidden" name="provinsi" id="tambah_provinsi" value="JAWA TIMUR">
                    </div>
                </div>
                
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalTambah()">Batal</button>
                    <button type="submit" name="submit_tambah" class="btn btn-primary px-5 rounded-pill shadow-sm" id="submitTambahBtn"><i class="fas fa-save me-2"></i>Simpan Penduduk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editPendudukModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="penduduk.php" id="formEditPenduduk" onsubmit="return validateFormEdit()" style="display:flex; flex-direction:column; height:100%;">
                
                <div class="modal-header bg-gradient-warning text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-user-edit me-2"></i>Update Data Penduduk</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModalEdit()"></button>
                </div>
                
                <div class="modal-body bg-light p-4">
                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <div class="form-section-title"><i class="fas fa-id-card me-2"></i>Data Utama Pribadi</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor Induk Kependudukan (NIK)</label>
                                <input type="text" class="form-control bg-light text-muted" name="nik" id="edit_nik" readonly>
                                <small class="text-danger">* NIK bersifat permanen dan tidak dapat diubah.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="nama_penduduk" id="edit_nama_penduduk" required>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" class="form-control text-uppercase" name="tempat_lahir" id="edit_tempat_lahir">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="date" class="form-control" name="tanggal_lahir" id="edit_tanggal_lahir" required onchange="calculateAgeLive(this.value, 'edit_umur_badge')">
                                    <span id="edit_umur_badge" class="badge bg-info p-2 live-age-badge shadow-sm" style="font-size: 0.85rem;"><i class="fas fa-birthday-cake me-1"></i> -</span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kelamin</label>
                                <select class="form-select" name="jenis_kelamin" id="edit_jenis_kelamin">
                                    <option value="LAKI-LAKI">Laki-Laki</option>
                                    <option value="PEREMPUAN">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Agama</label>
                                <select class="form-select" name="agama" id="edit_agama">
                                    <option value="ISLAM">Islam</option>
                                    <option value="KRISTEN">Kristen Protestan</option>
                                    <option value="KATOLIK">Katolik</option>
                                    <option value="HINDU">Hindu</option>
                                    <option value="BUDDHA">Buddha</option>
                                    <option value="KONGHUCU">Konghucu</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <div class="form-section-title"><i class="fas fa-info-circle me-2"></i>Detail Lanjutan</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Status Perkawinan</label>
                                <select class="form-select" name="status_kawin" id="edit_status_kawin">
                                    <option value="Belum Kawin">Belum Kawin</option>
                                    <option value="Kawin">Kawin</option>
                                    <option value="Cerai Hidup">Cerai Hidup</option>
                                    <option value="Cerai Mati">Cerai Mati</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pendidikan Terakhir</label>
                                <select class="form-select" name="pendidikan" id="edit_pendidikan">
                                    <option value="">-- Pilih Pendidikan --</option>
                                    <option value="TIDAK SEKOLAH">Tidak Sekolah</option>
                                    <option value="SD">SD/Sederajat</option>
                                    <option value="SMP">SMP/Sederajat</option>
                                    <option value="SMA">SMA/Sederajat</option>
                                    <option value="D1">Diploma I</option>
                                    <option value="D3">Diploma III</option>
                                    <option value="S1">Strata I (S1)</option>
                                    <option value="S2">Strata II (S2)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Pekerjaan</label>
                                <input type="text" class="form-control text-uppercase" name="pekerjaan" id="edit_pekerjaan">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Ayah</label>
                                <input type="text" class="form-control text-uppercase" name="nama_ayah" id="edit_nama_ayah">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Ibu</label>
                                <input type="text" class="form-control text-uppercase" name="nama_ibu" id="edit_nama_ibu">
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4">
                        <div class="form-section-title"><i class="fas fa-map-marked-alt me-2"></i>Informasi Alamat Domisili</div>
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
                            <div class="col-md-2">
                                <label class="form-label">RT/RW</label>
                                <input type="text" class="form-control" name="rt_rw" id="edit_rt_rw">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" class="form-control" name="kodepos" id="edit_kodepos" maxlength="5">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Alamat Lengkap (Jalan/Gang)</label>
                                <textarea class="form-control text-uppercase" name="alamat" id="edit_alamat" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Desa / Kelurahan</label>
                                <input type="text" class="form-control text-uppercase" name="kel_des" id="edit_kel_des">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kecamatan</label>
                                <input type="text" class="form-control text-uppercase" name="kecamatan" id="edit_kecamatan">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kabupaten</label>
                                <input type="text" class="form-control text-uppercase" name="kabupaten_kota" id="edit_kabupaten_kota">
                            </div>
                        </div>
                        <input type="hidden" name="provinsi" id="edit_provinsi" value="JAWA TIMUR">
                    </div>
                </div>
                
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalEdit()">Batal</button>
                    <button type="submit" name="submit_edit" class="btn btn-warning text-dark px-5 rounded-pill shadow-sm"><i class="fas fa-save me-2"></i>Update Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewPendudukModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-address-card me-2"></i>Detail Profil Penduduk</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalView()"></button>
            </div>
            <div class="modal-body bg-light" id="viewPendudukContent" style="min-height: 300px;">
                </div>
            <div class="modal-footer bg-white d-flex justify-content-between">
                <button type="button" class="btn btn-outline-info rounded-pill" id="btnCopyAllData" onclick="copyAllDetailData()"><i class="fas fa-copy me-1"></i> Salin Semua Data Text</button>
                <button type="button" class="btn btn-light border rounded-pill px-4" onclick="closeModalView()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmHapusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus Data</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalHapus()"></button>
            </div>
            <div class="modal-body py-5 text-center">
                <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center mb-4 shadow" style="width: 80px; height: 80px;">
                    <i class="fas fa-trash-alt fa-3x"></i>
                </div>
                <h4 class="text-gray-800 font-weight-bold mb-3">Hapus Penduduk Ini?</h4>
                <p class="text-muted mb-4">Tindakan ini tidak dapat dibatalkan. Pastikan data tidak terkait dengan Kartu Keluarga manapun.</p>
                <div id="confirmHapusInfo" class="alert alert-light border mx-auto text-start" style="max-width: 350px;"></div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-center">
                <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalHapus()">Batal</button>
                <button type="button" class="btn btn-danger px-5 rounded-pill shadow-sm" id="btnConfirmHapus"><i class="fas fa-trash me-1"></i> Ya, Hapus Permanen</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="infoPenggunaanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-secondary text-white py-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-link me-2"></i>Keterkaitan Data Keluarga</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalInfo()"></button>
            </div>
            <div class="modal-body py-4 bg-light" id="infoPenggunaanContent">
                </div>
            <div class="modal-footer bg-white d-flex justify-content-center">
                <button type="button" class="btn btn-secondary border rounded-pill px-5" onclick="closeModalInfo()">Tutup Mengerti</button>
            </div>
        </div>
    </div>
</div>

<script>
// ========== FUNGSI COPY NIK & ALL DATA ==========
function copyText(elementId) {
    var copyText = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(copyText).then(function() {
        showSuccess("Berhasil menyalin NIK: " + copyText);
    }, function() { showErrorMessage("Gagal menyalin NIK."); });
}

// FITUR BARU: Copy seluruh biodata ke format rapi (WA-Ready)
function copyAllDetailData() {
    const rawData = document.getElementById('viewPendudukContent').innerText;
    // Membersihkan jarak dan enter berlebih agar rapi di WA
    let cleanData = rawData.replace(/\n\s*\n/g, '\n').replace(/:\s+/g, ': ');
    navigator.clipboard.writeText("BIODATA PENDUDUK\n------------------------\n" + cleanData).then(function() {
        showSuccess("Seluruh data biodata berhasil disalin ke clipboard!");
    }, function() { showErrorMessage("Gagal menyalin data."); });
}

// ========== FUNGSI LIVE AGE CALCULATOR ==========
function calculateAgeLive(dateString, badgeId) {
    const badge = document.getElementById(badgeId);
    if(!badge) return;
    
    if (!dateString) {
        badge.innerHTML = '<i class="fas fa-birthday-cake me-1"></i> -';
        badge.classList.remove('show');
        return;
    }
    const ageStr = hitungUmur(dateString);
    badge.innerHTML = `<i class="fas fa-birthday-cake me-1"></i> Usia saat ini: ${ageStr}`;
    badge.classList.add('show');
}

// ========== VARIABEL GLOBAL & INISIALISASI MODAL ==========
let modalTambahInstance = null;
let modalEditInstance = null;
let modalViewInstance = null;
let modalHapusInstance = null;
let modalInfoInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('tambahPendudukModal')) modalTambahInstance = new bootstrap.Modal(document.getElementById('tambahPendudukModal'));
    if (document.getElementById('editPendudukModal')) modalEditInstance = new bootstrap.Modal(document.getElementById('editPendudukModal'));
    if (document.getElementById('viewPendudukModal')) modalViewInstance = new bootstrap.Modal(document.getElementById('viewPendudukModal'));
    if (document.getElementById('confirmHapusModal')) modalHapusInstance = new bootstrap.Modal(document.getElementById('confirmHapusModal'));
    if (document.getElementById('infoPenggunaanModal')) modalInfoInstance = new bootstrap.Modal(document.getElementById('infoPenggunaanModal'));
    
    // Bind Button Tambah
    const btnTambah = document.getElementById('btnTambahPenduduk');
    if (btnTambah) {
        btnTambah.addEventListener('click', function(e) {
            e.preventDefault();
            resetTambahForm();
            if (modalTambahInstance) modalTambahInstance.show();
        });
    }

    // Bind Buttons View, Edit, Info, Hapus
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.addEventListener('click', function(e) { e.preventDefault(); loadViewData(this.getAttribute('data-nik')); });
    });
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function(e) { e.preventDefault(); loadEditData(this.getAttribute('data-nik'), this); });
    });
    document.querySelectorAll('.btn-info-penggunaan').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            showInfoPenggunaan(this.getAttribute('data-nik'), this.getAttribute('data-nama'), this.getAttribute('data-is-kepala') === 'true', this.getAttribute('data-is-anggota') === 'true');
        });
    });
    document.querySelectorAll('.btn-hapus').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const nik = this.getAttribute('data-nik');
            const infoDiv = document.getElementById('confirmHapusInfo');
            if (infoDiv) infoDiv.innerHTML = `<div class="mb-1 text-muted small">NIK Target Penghapusan:</div><div class="font-weight-bold text-danger fs-5">${nik}</div>`;
            const confirmBtn = document.getElementById('btnConfirmHapus');
            if (confirmBtn) confirmBtn.setAttribute('data-nik', nik);
            if (modalHapusInstance) modalHapusInstance.show();
        });
    });

    // Dismiss Alerts otomatis
    setTimeout(() => { document.querySelectorAll('.alert-dismissible').forEach(alert => { try { bootstrap.Alert.getInstance(alert)?.close(); } catch(e){} }); }, 5000);
});

// ========== FUNGSI MODAL CONTROLS ==========
function closeModalTambah() { if (modalTambahInstance) modalTambahInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalEdit() { if (modalEditInstance) modalEditInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalView() { if (modalViewInstance) modalViewInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalHapus() { if (modalHapusInstance) modalHapusInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalInfo() { if (modalInfoInstance) modalInfoInstance.hide(); setTimeout(cleanupBackdrop, 100); }
function cleanupBackdrop() { document.querySelectorAll('.modal-backdrop').forEach(b => b.remove()); document.body.classList.remove('modal-open'); document.body.style.overflow=''; document.body.style.paddingRight=''; }
function closeAlert(element) { const alert = element.closest('.alert'); if (alert) alert.remove(); }

// ========== FUNGSI UTILITIES ==========
function showSuccess(message) {
    const html = `<div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-lg position-fixed top-0 end-0 m-3" style="z-index:9999;" role="alert"><i class="fas fa-check-circle me-2 fs-5 align-middle"></i><span class="align-middle">${message}</span><button type="button" class="btn-close" onclick="closeAlert(this)"></button></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    setTimeout(() => { const a = document.querySelector('.alert-success.position-fixed'); if(a) a.remove(); }, 3000);
}
function showErrorMessage(message) {
    const html = `<div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-lg position-fixed top-0 end-0 m-3" style="z-index:9999;" role="alert"><i class="fas fa-exclamation-circle me-2 fs-5 align-middle"></i><span class="align-middle">${message}</span><button type="button" class="btn-close" onclick="closeAlert(this)"></button></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    setTimeout(() => { const a = document.querySelector('.alert-danger.position-fixed'); if(a) a.remove(); }, 5000);
}
function hitungUmur(tanggalLahir) {
    if (!tanggalLahir) return '-';
    const birthDate = new Date(tanggalLahir); const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    return age + ' Tahun';
}

// ========== FUNGSI AJAX VIEW PENDUDUK ==========
function loadViewData(nik) {
    const contentDiv = document.getElementById('viewPendudukContent');
    contentDiv.innerHTML = `<div class="text-center py-5 mt-4"><div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div><p class="mt-3 fs-5 text-muted">Mengekstrak data kependudukan...</p></div>`;
    if (modalViewInstance) modalViewInstance.show();
    
    fetch('penduduk.php?ajax_get_penduduk=' + encodeURIComponent(nik))
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;
                const tglLahir = new Date(data.tanggal_lahir).toLocaleDateString('id-ID', {day: '2-digit', month: 'long', year: 'numeric'});
                const umur = hitungUmur(data.tanggal_lahir);
                
                contentDiv.innerHTML = `
                    <div class="container-fluid px-0">
                        <div class="text-center mb-4 bg-white p-4 rounded-3 shadow-sm border">
                            <div class="position-relative d-inline-block">
                                <i class="fas ${data.jenis_kelamin=='LAKI-LAKI'?'fa-user-tie':'fa-user-nurse'} fa-6x ${data.jenis_kelamin=='LAKI-LAKI'?'text-primary':'text-danger'}"></i>
                                ${data.is_kepala > 0 ? '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark border"><i class="fas fa-crown"></i> Kepala KK</span>' : ''}
                            </div>
                            <h3 class="mb-1 mt-3 font-weight-bold text-gray-800 text-uppercase">${data.nama_penduduk || '-'}</h3>
                            <h5 class="text-primary font-family-monospace">${data.nik || '-'}</h5>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-header bg-white border-bottom"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-id-card me-2"></i>Biodata Personal</h6></div>
                                    <div class="card-body p-0">
                                        <table class="table table-hover table-borderless mb-0">
                                            <tr><td width="35%" class="text-muted ps-4 py-3">Tempat, Tgl Lahir</td><td class="font-weight-bold text-uppercase">${data.tempat_lahir}, ${tglLahir}</td></tr>
                                            <tr><td class="text-muted ps-4 py-3">Usia</td><td class="font-weight-bold">${umur}</td></tr>
                                            <tr><td class="text-muted ps-4 py-3">Kelamin</td><td class="font-weight-bold">${data.jenis_kelamin}</td></tr>
                                            <tr><td class="text-muted ps-4 py-3">Agama</td><td class="font-weight-bold">${data.agama}</td></tr>
                                            <tr><td class="text-muted ps-4 py-3">Status Kawin</td><td class="font-weight-bold">${data.status_kawin}</td></tr>
                                            <tr><td class="text-muted ps-4 py-3">Pendidikan</td><td class="font-weight-bold">${data.pendidikan || '-'}</td></tr>
                                            <tr><td class="text-muted ps-4 py-3">Pekerjaan</td><td class="font-weight-bold">${data.pekerjaan || '-'}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-header bg-white border-bottom"><h6 class="m-0 font-weight-bold text-success"><i class="fas fa-map-marked-alt me-2"></i>Detail Alamat Domisili</h6></div>
                                    <div class="card-body p-0">
                                        <table class="table table-hover table-borderless mb-0">
                                            <tr><td width="35%" class="text-muted ps-4 py-3">Dusun</td><td class="font-weight-bold text-success text-uppercase">${data.dusun || '-'}</td></tr>
                                            <tr><td class="text-muted ps-4 py-3">RT/RW</td><td class="font-weight-bold">${data.rt_rw || '-'}</td></tr>
                                            <tr><td class="text-muted ps-4 py-3">Alamat Lengkap</td><td class="font-weight-bold text-uppercase">${data.alamat || '-'}</td></tr>
                                            <tr><td class="text-muted ps-4 py-3">Desa / Kel</td><td class="font-weight-bold text-uppercase">${data.kel_des || '-'}</td></tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-white border-bottom"><h6 class="m-0 font-weight-bold text-info"><i class="fas fa-users me-2"></i>Data Orang Tua</h6></div>
                                    <div class="card-body p-0">
                                        <table class="table table-hover table-borderless mb-0">
                                            <tr><td width="35%" class="text-muted ps-4 py-3">Nama Ayah</td><td class="font-weight-bold text-uppercase">${data.nama_ayah || '-'}</td></tr>
                                            <tr><td class="text-muted ps-4 py-3">Nama Ibu</td><td class="font-weight-bold text-uppercase">${data.nama_ibu || '-'}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `<div class="alert alert-danger m-3"><i class="fas fa-exclamation-triangle"></i> Gagal memuat data: ${result.message}</div>`;
            }
        })
        .catch(err => { contentDiv.innerHTML = `<div class="alert alert-danger m-3"><i class="fas fa-wifi"></i> Error koneksi saat memuat data.</div>`; });
}

// ========== FUNGSI AJAX EDIT PENDUDUK ==========
function loadEditData(nik, button) {
    const origHtml = button.innerHTML; button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; button.disabled = true;
    
    fetch('penduduk.php?ajax_get_penduduk=' + encodeURIComponent(nik))
        .then(response => response.json())
        .then(result => {
            button.innerHTML = origHtml; button.disabled = false;
            if (result.success) {
                const data = result.data;
                document.getElementById('edit_nik').value = data.nik || '';
                document.getElementById('edit_nama_penduduk').value = data.nama_penduduk || '';
                document.getElementById('edit_nama_ayah').value = data.nama_ayah || '';
                document.getElementById('edit_nama_ibu').value = data.nama_ibu || '';
                document.getElementById('edit_tempat_lahir').value = data.tempat_lahir || '';
                document.getElementById('edit_tanggal_lahir').value = data.tanggal_lahir || '';
                document.getElementById('edit_jenis_kelamin').value = data.jenis_kelamin || 'LAKI-LAKI';
                document.getElementById('edit_agama').value = data.agama || 'ISLAM';
                document.getElementById('edit_pendidikan').value = data.pendidikan || '';
                document.getElementById('edit_pekerjaan').value = data.pekerjaan || '';
                document.getElementById('edit_status_kawin').value = data.status_kawin || 'Belum Kawin';
                document.getElementById('edit_alamat').value = data.alamat || '';
                document.getElementById('edit_rt_rw').value = data.rt_rw || '001/002';
                document.getElementById('edit_kel_des').value = data.kel_des || 'SUKOLILO TIMUR';
                document.getElementById('edit_kecamatan').value = data.kecamatan || 'SUKOLILO';
                document.getElementById('edit_kabupaten_kota').value = data.kabupaten_kota || 'BANGKALAN';
                document.getElementById('edit_provinsi').value = data.provinsi || 'JAWA TIMUR';
                document.getElementById('edit_kodepos').value = data.kodepos || '69162';
                
                // Trigger Hitung Umur untuk Modal Edit
                calculateAgeLive(data.tanggal_lahir, 'edit_umur_badge');

                // Dusun Logic
                if (data.dusun) {
                    const select = document.getElementById('edit_dusun_select');
                    const hidden = document.getElementById('edit_dusun_hidden');
                    let found = false;
                    for (let i = 0; i < select.options.length; i++) {
                        if (select.options[i].value === data.dusun) {
                            select.value = data.dusun; document.getElementById('edit_dusun_pilih').checked = true;
                            document.getElementById('edit_dusun_select_container').style.display = 'block';
                            document.getElementById('edit_dusun_input_container').style.display = 'none';
                            hidden.value = data.dusun; found = true; break;
                        }
                    }
                    if (!found) {
                        document.getElementById('edit_dusun_custom').value = data.dusun; document.getElementById('edit_dusun_tulis').checked = true;
                        document.getElementById('edit_dusun_select_container').style.display = 'none';
                        document.getElementById('edit_dusun_input_container').style.display = 'block';
                        hidden.value = data.dusun;
                    }
                }
                if (modalEditInstance) modalEditInstance.show();
            } else alert('Gagal memuat data: ' + result.message);
        })
        .catch(err => { button.innerHTML = origHtml; button.disabled = false; alert('Terjadi kesalahan jaringan.'); });
}

// ========== FUNGSI INFO PENGGUNAAN KK ==========
function showInfoPenggunaan(nik, nama, isKepala, isAnggota) {
    const contentDiv = document.getElementById('infoPenggunaanContent');
    let content = `
        <div class="text-center mb-4">
            <div class="rounded-circle bg-white shadow-sm d-inline-flex align-items-center justify-content-center p-3 mb-3">
                <i class="fas fa-network-wired text-primary" style="font-size: 40px;"></i>
            </div>
            <h5 class="font-weight-bold text-gray-800">${nama}</h5>
            <p class="text-muted font-family-monospace">${nik}</p>
        </div>
    `;
    
    if (isKepala) content += `<div class="alert alert-warning border-left-warning shadow-sm"><i class="fas fa-crown me-2"></i><strong>Status: Kepala Keluarga</strong><br><small>Data ini bertindak sebagai Kepala Keluarga di master KK. Anda tidak bisa menghapusnya langsung.</small></div>`;
    if (isAnggota) content += `<div class="alert alert-info border-left-info shadow-sm"><i class="fas fa-user-friends me-2"></i><strong>Status: Anggota Keluarga</strong><br><small>Data ini terikat sebagai anggota di sebuah KK. Anda tidak bisa menghapusnya langsung.</small></div>`;
    if (!isKepala && !isAnggota) content += `<div class="alert alert-success border-left-success shadow-sm"><i class="fas fa-check-circle me-2"></i><strong>Status: Mandiri (Bebas)</strong><br><small>Data ini tidak terikat pada KK manapun. Aman untuk dihapus.</small></div>`;
    
    content += `<p class="text-center text-muted small mt-4"><i class="fas fa-lightbulb text-warning me-1"></i> Untuk menghapus data terikat, cabut dahulu statusnya melalui menu <b>Kartu Keluarga</b>.</p>`;
    contentDiv.innerHTML = content;
    if (modalInfoInstance) modalInfoInstance.show();
}

// ========== FUNGSI HAPUS AJAX ==========
document.getElementById('btnConfirmHapus')?.addEventListener('click', function() {
    const nik = this.getAttribute('data-nik');
    const origTxt = this.innerHTML; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...'; this.disabled = true;
    
    fetch('penduduk.php?ajax_hapus=' + encodeURIComponent(nik))
        .then(res => res.json())
        .then(data => {
            if (modalHapusInstance) modalHapusInstance.hide();
            if (data.success) { showSuccess('Penduduk dihapus permanen.'); setTimeout(() => location.reload(), 1000); }
            else { showErrorMessage(data.message); this.innerHTML = origTxt; this.disabled = false; }
        })
        .catch(err => { if(modalHapusInstance) modalHapusInstance.hide(); showErrorMessage('Kesalahan jaringan saat menghapus.'); this.innerHTML = origTxt; this.disabled = false; });
});

// ========== VALIDASI & DUSUN TOGGLES ==========
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

function checkNIKTambah(nik) {
    const input = document.getElementById('tambah_nik'); const feedback = document.getElementById('tambah_nik_feedback'); const btn = document.getElementById('submitTambahBtn');
    input.classList.remove('is-invalid', 'is-valid'); feedback.innerHTML = '';
    if (!nik) return;
    if (!/^\d+$/.test(nik)) { input.classList.add('is-invalid'); feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Harus berupa angka</span>'; return; }
    if (nik.length !== 16) { input.classList.add('is-invalid'); feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Kurang lengkap (16 Digit)</span>'; return; }
    
    feedback.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Cek Database...</span>';
    fetch('penduduk.php?check_nik=' + encodeURIComponent(nik)).then(res => res.json()).then(data => {
        if (data.exists) { input.classList.add('is-invalid'); feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> NIK Sudah Terdaftar!</span>'; btn.disabled = true; }
        else { input.classList.add('is-valid'); feedback.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> NIK Tersedia</span>'; btn.disabled = false; }
    });
}

function validateFormTambah() {
    const dusunOption = document.querySelector('input[name="dusun_option"]:checked');
    if (dusunOption) updateDusunHidden(dusunOption.value);
    const nik = document.getElementById('tambah_nik')?.value;
    if (!nik || nik.length !== 16) { alert('NIK harus 16 digit angka!'); return false; }
    return true;
}

function validateFormEdit() {
    const dusunOption = document.querySelector('input[name="edit_dusun_option"]:checked');
    if (dusunOption) updateEditDusunHidden(dusunOption.value);
    return true;
}

function resetTambahForm() {
    document.getElementById('formTambahPenduduk')?.reset();
    document.getElementById('tambah_nik')?.classList.remove('is-invalid', 'is-valid');
    if(document.getElementById('tambah_nik_feedback')) document.getElementById('tambah_nik_feedback').innerHTML = '';
    document.getElementById('dusun_select_container').style.display = 'block';
    document.getElementById('dusun_input_container').style.display = 'none';
    document.getElementById('dusun_hidden').value = '';
    
    document.getElementById('tambah_umur_badge').innerHTML = '<i class="fas fa-birthday-cake me-1"></i> -';
    document.getElementById('tambah_umur_badge').classList.remove('show');
    
    // Set Defaults untuk Desa
    document.getElementById('tambah_rt_rw').value = '001/002';
    document.getElementById('tambah_kel_des').value = 'SUKOLILO TIMUR';
    document.getElementById('tambah_kecamatan').value = 'LABANG';
    document.getElementById('tambah_kabupaten_kota').value = 'BANGKALAN';
    document.getElementById('tambah_provinsi').value = 'JAWA TIMUR';
    document.getElementById('tambah_kodepos').value = '69162';
}
</script>

<?php
$content = ob_get_clean();
include '../includes/base.php';
?>