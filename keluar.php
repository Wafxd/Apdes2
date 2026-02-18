<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

include "db/koneksi.php";
include "db/funct.php";

// ==================== AJAX HANDLER ====================

// AJAX: Check Nomor KK
if (isset($_GET['check_kk'])) {
    header('Content-Type: application/json');
    $no_kk = mysqli_real_escape_string($conn, $_GET['check_kk']);
    
    if (empty($no_kk)) {
        echo json_encode(['exists' => false, 'valid' => false, 'message' => 'Nomor KK kosong']);
        exit();
    }
    
    if (!is_numeric($no_kk)) {
        echo json_encode(['exists' => false, 'valid' => false, 'message' => 'Nomor KK harus angka']);
        exit();
    }
    
    if (strlen($no_kk) !== 16) {
        echo json_encode(['exists' => false, 'valid' => false, 'message' => 'Nomor KK harus 16 digit']);
        exit();
    }
    
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

// AJAX: Get Anggota Keluarga
if (isset($_GET['ajax_get_anggota'])) {
    header('Content-Type: application/json');
    $no_kk = mysqli_real_escape_string($conn, $_GET['ajax_get_anggota']);
    
    $query = "SELECT ak.*, p.nama_penduduk, p.jenis_kelamin, p.tempat_lahir, p.tanggal_lahir, 
              p.status_kawin, p.pekerjaan, p.alamat
              FROM anggota_keluarga ak 
              JOIN penduduk p ON ak.nik = p.nik 
              WHERE ak.no_kk = '$no_kk' AND ak.nik != (SELECT nik_kepala FROM kartu_keluarga WHERE no_kk = '$no_kk')
              ORDER BY 
                CASE hubungan_keluarga 
                    WHEN 'Suami' THEN 1
                    WHEN 'Istri' THEN 2
                    WHEN 'Anak' THEN 3
                    ELSE 4
                END, p.nama_penduduk";
    $result = mysqli_query($conn, $query);
    
    $anggota = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $anggota[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $anggota]);
    exit();
}

// AJAX: Hapus KK
if (isset($_GET['ajax_hapus_kk'])) {
    header('Content-Type: application/json');
    $no_kk = mysqli_real_escape_string($conn, $_GET['ajax_hapus_kk']);
    
    $result = hapus_kk($no_kk);
    
    if ($result > 0) {
        echo json_encode(['success' => true, 'message' => 'Data KK berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data KK']);
    }
    exit();
}

// AJAX: Hapus Anggota Keluarga
if (isset($_GET['ajax_hapus_anggota'])) {
    header('Content-Type: application/json');
    $id_anggota = mysqli_real_escape_string($conn, $_GET['ajax_hapus_anggota']);
    
    $result = hapus_anggota_keluarga($id_anggota);
    
    if ($result > 0) {
        echo json_encode(['success' => true, 'message' => 'Anggota berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus anggota']);
    }
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
    
    // Ambil nik_kepala dari KK ini
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
    
    $result = tambah_kk($_POST);
    
    if ($result > 0) {
        $_SESSION['success_message'] = "Data Kartu Keluarga berhasil ditambahkan!";
    } elseif ($result == -1) {
        $_SESSION['error_message'] = "Nomor KK sudah terdaftar!";
    } elseif ($result == -2) {
        $_SESSION['error_message'] = "Penduduk ini sudah menjadi Kepala Keluarga di KK lain!";
    } elseif ($result == -3) {
        $_SESSION['error_message'] = "Penduduk ini sudah menjadi Anggota Keluarga di KK lain!";
    } else {
        $_SESSION['error_message'] = "Gagal menambahkan data KK!";
    }
    
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
    
    $result = edit_kk($_POST);
    
    if ($result >= 0) {
        $_SESSION['success_message'] = "Data Kartu Keluarga berhasil diupdate!";
    } else {
        $_SESSION['error_message'] = "Gagal mengupdate data KK!";
    }
    
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
    
    if ($result > 0) {
        $_SESSION['success_message'] = "Anggota keluarga berhasil ditambahkan!";
    } else {
        $_SESSION['error_message'] = "Gagal menambahkan anggota keluarga!";
    }
    
    header("Location: keluarga.php?detail=" . $_POST['no_kk']);
    exit();
}

// ==================== QUERY DATA ====================

// Cek apakah ada parameter detail
$detail_mode = isset($_GET['detail']);
$no_kk_detail = $detail_mode ? mysqli_real_escape_string($conn, $_GET['detail']) : '';

// Search untuk halaman utama
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "";
if (!empty($search)) {
    $where = "WHERE kk.no_kk LIKE '%$search%' OR p.nama_penduduk LIKE '%$search%'";
}

// Query data KK dengan jumlah anggota
$query_kk = "SELECT kk.*, p.nama_penduduk as nama_kepala,
             (SELECT COUNT(*) FROM anggota_keluarga ak WHERE ak.no_kk = kk.no_kk) + 1 as jumlah_anggota
             FROM kartu_keluarga kk
             JOIN penduduk p ON kk.nik_kepala = p.nik
             $where
             ORDER BY kk.created_at DESC";
$result_kk = mysqli_query($conn, $query_kk);
$total_kk = mysqli_num_rows($result_kk);

$pageTitle = $detail_mode ? "Detail Keluarga" : "Data Keluarga";
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

.modal-header.bg-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}

.modal-header.bg-info {
    background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%);
}

.modal-header.bg-warning {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
    color: #fff;
}

.modal-header.bg-danger {
    background: linear-gradient(135deg, #e74a3b 0%, #be2e22 100%);
}

.modal-header.bg-secondary {
    background: linear-gradient(135deg, #858796 0%, #60616f 100%);
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

/* Styling untuk form */
.form-label {
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
    color: #495057;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #d1d3e2;
    padding: 0.5rem 0.75rem;
    transition: all 0.2s;
}

.form-control:focus, .form-select:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

/* Styling untuk tombol */
.btn {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 500;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2e59d9 0%, #1a3a9e 100%);
}

.btn-secondary {
    background: linear-gradient(135deg, #858796 0%, #60616f 100%);
    border: none;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #6b6d7d 0%, #4a4b56 100%);
}

.btn-danger {
    background: linear-gradient(135deg, #e74a3b 0%, #be2e22 100%);
    border: none;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #d33426 0%, #9e251b 100%);
}

.btn-info {
    background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%);
    border: none;
    color: white;
}

.btn-info:hover {
    background: linear-gradient(135deg, #2c9faf 0%, #147a8a 100%);
}

.btn-warning {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
    border: none;
    color: white;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #e2b33b 0%, #c49109 100%);
}

/* Styling untuk validasi */
.is-invalid {
    border-color: #dc3545 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.is-valid {
    border-color: #198754 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.invalid-feedback, .valid-feedback {
    display: block;
    font-size: 80%;
    margin-top: 0.25rem;
}

.invalid-feedback {
    color: #dc3545;
}

.valid-feedback {
    color: #198754;
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

/* Table styling */
.table-primary {
    background-color: #e3f2fd !important;
}

.table thead th {
    background-color: #f8f9fc;
    border-bottom: 2px solid #4e73df;
    color: #4e73df;
    font-weight: 600;
}

.table tbody tr:hover {
    background-color: #f8f9fc;
}

/* Badge styling */
.badge {
    padding: 5px 10px;
    border-radius: 20px;
}

.badge.bg-warning {
    background-color: #f6c23e !important;
    color: #000;
}

.badge.bg-info {
    background-color: #36b9cc !important;
}

/* Responsive */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .btn-lg {
        padding: 0.5rem 1rem;
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

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <?php echo $detail_mode ? 'Detail Kartu Keluarga: ' . htmlspecialchars($no_kk_detail) : 'Data Keluarga'; ?>
    </h1>
    <div>
        <?php if (!$detail_mode): ?>
        <button type="button" class="btn btn-primary" id="btnTambahKK">
            <i class="fas fa-plus me-2"></i>Tambah Kartu Keluarga
        </button>
        <?php else: ?>
        <a href="keluarga.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar KK
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$detail_mode): ?>

<!-- Search Form -->
<div class="card shadow mb-4">
    <div class="card-header py-3 bg-primary text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-search me-2"></i>Cari Kartu Keluarga
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" class="form-control" name="search" placeholder="Cari Nomor KK atau Nama Kepala Keluarga..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Cari
                </button>
            </div>
            <?php if (!empty($search)): ?>
            <div class="col-12">
                <a href="keluarga.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Reset Pencarian
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabel Data KK (Halaman Utama) -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Kartu Keluarga</h6>
    </div>
    <div class="card-body">
        <?php if ($total_kk > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="50">No</th>
                        <th>Nomor KK</th>
                        <th>Kepala Keluarga</th>
                        <th>NIK Kepala</th>
                        <th>Jumlah Anggota</th>
                        <th>Alamat</th>
                        <th>RT/RW</th>
                        <th>Desa/Kel</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    mysqli_data_seek($result_kk, 0); // Reset pointer
                    while ($kk = mysqli_fetch_assoc($result_kk)): 
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($kk['no_kk']); ?></td>
                        <td><?php echo htmlspecialchars($kk['nama_kepala']); ?></td>
                        <td><?php echo htmlspecialchars($kk['nik_kepala']); ?></td>
                        <td class="text-center">
                            <?php 
                            // PERBAIKAN: Jumlah anggota sudah benar dari query
                            echo $kk['jumlah_anggota']; 
                            ?> orang
                        </td>
                        <td><?php echo htmlspecialchars($kk['alamat_kk']); ?></td>
                        <td><?php echo $kk['rt'] . '/' . $kk['rw']; ?></td>
                        <td><?php echo htmlspecialchars($kk['desa_kel']); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="keluarga.php?detail=<?php echo $kk['no_kk']; ?>" class="btn btn-sm btn-info" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-warning btn-edit-kk" 
                                        data-no-kk="<?php echo htmlspecialchars($kk['no_kk']); ?>"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger btn-hapus-kk" 
                                        data-no-kk="<?php echo htmlspecialchars($kk['no_kk']); ?>"
                                        data-kepala="<?php echo htmlspecialchars($kk['nama_kepala']); ?>"
                                        title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Belum ada data Kartu Keluarga. Silakan tambah data baru.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: 
    // ========== HALAMAN DETAIL KK ==========
    $kk_detail = mysqli_query($conn, "SELECT kk.*, p.nama_penduduk as nama_kepala, p.jenis_kelamin as jk_kepala, 
                                      p.tempat_lahir, p.tanggal_lahir, p.status_kawin, p.pekerjaan,
                                      p.agama, p.pendidikan, p.rt_rw as rt_rw_penduduk
                                      FROM kartu_keluarga kk 
                                      JOIN penduduk p ON kk.nik_kepala = p.nik 
                                      WHERE kk.no_kk = '$no_kk_detail'");
    
    if (mysqli_num_rows($kk_detail) == 0) {
        echo '<div class="alert alert-danger">Data KK tidak ditemukan!</div>';
        echo '<a href="keluarga.php" class="btn btn-primary">Kembali</a>';
    } else {
        $kk = mysqli_fetch_assoc($kk_detail);
        
        // PERBAIKAN: Ambil anggota keluarga dengan data lengkap
        $anggota = mysqli_query($conn, "SELECT ak.*, 
                                        p.nama_penduduk, 
                                        p.jenis_kelamin, 
                                        p.tempat_lahir, 
                                        p.tanggal_lahir, 
                                        p.status_kawin, 
                                        p.pekerjaan, 
                                        p.alamat,
                                        p.agama,
                                        p.pendidikan,
                                        p.nama_ayah,
                                        p.nama_ibu
                                        FROM anggota_keluarga ak 
                                        JOIN penduduk p ON ak.nik = p.nik 
                                        WHERE ak.no_kk = '$no_kk_detail' AND ak.nik != '{$kk['nik_kepala']}'
                                        ORDER BY 
                                          CASE ak.hubungan_keluarga 
                                              WHEN 'Suami' THEN 1
                                              WHEN 'Istri' THEN 2
                                              WHEN 'Anak' THEN 3
                                              ELSE 4
                                          END, p.nama_penduduk");
        
        // PERBAIKAN: Hitung total anggota dengan benar
        $total_anggota_lain = mysqli_num_rows($anggota);
        $total_anggota = $total_anggota_lain + 1; // +1 untuk kepala keluarga
?>

<!-- ========== DETAIL KARTU KELUARGA ========== -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-address-card me-2"></i>Informasi Kartu Keluarga
                </h6>
                <div>
                    <button type="button" class="btn btn-sm btn-light" id="btnTambahAnggota" data-no-kk="<?php echo $kk['no_kk']; ?>">
                        <i class="fas fa-user-plus me-1"></i>Tambah Anggota
                    </button>
                    <button type="button" class="btn btn-sm btn-warning btn-edit-kk-detail" data-no-kk="<?php echo $kk['no_kk']; ?>">
                        <i class="fas fa-edit me-1"></i>Edit KK
                    </button>
                    <button type="button" class="btn btn-sm btn-danger btn-hapus-kk-detail" 
                            data-no-kk="<?php echo $kk['no_kk']; ?>"
                            data-kepala="<?php echo htmlspecialchars($kk['nama_kepala']); ?>">
                        <i class="fas fa-trash me-1"></i>Hapus KK
                    </button>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td width="20%"><strong>Nomor KK</strong></td>
                        <td width="30%">: <?php echo $kk['no_kk']; ?></td>
                        <td width="20%"><strong>RT/RW</strong></td>
                        <td width="30%">: <?php echo $kk['rt'] . '/' . $kk['rw']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kepala Keluarga</strong></td>
                        <td>: <?php echo $kk['nama_kepala']; ?> (<?php echo $kk['nik_kepala']; ?>)</td>
                        <td><strong>Jenis Kelamin</strong></td>
                        <td>: <?php echo $kk['jk_kepala']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tempat, Tgl Lahir</strong></td>
                        <td>: <?php echo $kk['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($kk['tanggal_lahir'])); ?></td>
                        <td><strong>Status Kawin</strong></td>
                        <td>: <?php echo $kk['status_kawin']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Agama</strong></td>
                        <td>: <?php echo $kk['agama']; ?></td>
                        <td><strong>Pendidikan</strong></td>
                        <td>: <?php echo $kk['pendidikan'] ?: '-'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Pekerjaan</strong></td>
                        <td>: <?php echo $kk['pekerjaan'] ?: '-'; ?></td>
                        <td><strong>Dusun</strong></td>
                        <td>: <?php echo $kk['dusun'] ?: '-'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Alamat</strong></td>
                        <td colspan="3">: <?php echo $kk['alamat_kk']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Desa/Kel</strong></td>
                        <td>: <?php echo $kk['desa_kel']; ?></td>
                        <td><strong>Kecamatan</strong></td>
                        <td>: <?php echo $kk['kecamatan']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kabupaten/Kota</strong></td>
                        <td>: <?php echo $kk['kabupaten_kota']; ?></td>
                        <td><strong>Provinsi</strong></td>
                        <td>: <?php echo $kk['provinsi']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kode Pos</strong></td>
                        <td>: <?php echo $kk['kode_pos']; ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========== DAFTAR ANGGOTA KELUARGA DENGAN DATA LENGKAP ========== -->
<div class="card shadow mb-4">
    <div class="card-header py-3 bg-info text-white">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-users me-2"></i>Daftar Anggota Keluarga (Total: <?php echo $total_anggota; ?> orang)
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="tabelAnggota">
                <thead class="table-light">
                    <tr>
                        <th width="50">No</th>
                        <th>NIK</th>
                        <th>Nama Lengkap</th>
                        <th>Jenis Kelamin</th>
                        <th>Tempat Lahir</th>
                        <th>Tanggal Lahir</th>
                        <th>Status Kawin</th>
                        <th>Pekerjaan</th>
                        <th>Agama</th>
                        <th>Pendidikan</th>
                        <th>Nama Ayah</th>
                        <th>Nama Ibu</th>
                        <th>Alamat</th>
                        <th>Hubungan Keluarga</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Kepala Keluarga dengan data lengkap -->
                    <tr class="table-primary">
                        <td>1</td>
                        <td><?php echo $kk['nik_kepala']; ?></td>
                        <td><strong><?php echo $kk['nama_kepala']; ?></strong></td>
                        <td><?php echo $kk['jk_kepala']; ?></td>
                        <td><?php echo $kk['tempat_lahir']; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($kk['tanggal_lahir'])); ?></td>
                        <td><?php echo $kk['status_kawin']; ?></td>
                        <td><?php echo $kk['pekerjaan'] ?: '-'; ?></td>
                        <td><?php echo $kk['agama']; ?></td>
                        <td><?php echo $kk['pendidikan'] ?: '-'; ?></td>
                        <td>-</td>
                        <td>-</td>
                        <td><?php echo $kk['alamat_kk']; ?></td>
                        <td><span class="badge bg-primary">Kepala Keluarga</span></td>
                        <td>-</td>
                    </tr>
                    
                    <!-- Anggota Lainnya dengan data lengkap -->
                    <?php if ($total_anggota_lain > 0): ?>
                        <?php $no = 2; ?>
                        <?php while ($ag = mysqli_fetch_assoc($anggota)): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $ag['nik']; ?></td>
                            <td><?php echo htmlspecialchars($ag['nama_penduduk']); ?></td>
                            <td><?php echo $ag['jenis_kelamin']; ?></td>
                            <td><?php echo htmlspecialchars($ag['tempat_lahir']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($ag['tanggal_lahir'])); ?></td>
                            <td><?php echo $ag['status_kawin']; ?></td>
                            <td><?php echo htmlspecialchars($ag['pekerjaan'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($ag['agama']); ?></td>
                            <td><?php echo htmlspecialchars($ag['pendidikan'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($ag['nama_ayah'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($ag['nama_ibu'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($ag['alamat']); ?></td>
                            <td><?php echo $ag['hubungan_keluarga']; ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger btn-hapus-anggota" 
                                        data-id="<?php echo $ag['id_anggota']; ?>"
                                        data-no-kk="<?php echo $kk['no_kk']; ?>"
                                        data-nik="<?php echo $ag['nik']; ?>"
                                        data-nama="<?php echo htmlspecialchars($ag['nama_penduduk']); ?>"
                                        title="Hapus dari KK">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="15" class="text-center">Belum ada anggota keluarga lain</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
    } 
endif; 
?>

<!-- ========== MODAL TAMBAH KK ========== -->
<div class="modal fade" id="tambahKKModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title">
                    <i class="fas fa-address-card me-2"></i>Tambah Kartu Keluarga
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalTambahKK()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="keluarga.php" id="formTambahKK">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="container-fluid p-3">
                        <table class="table table-borderless" style="width: 100%;">
                            <!-- Nomor KK -->
                            <tr>
                                <td style="width: 20%; vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Nomor KK <span class="text-danger">*</span></label>
                                </td>
                                <td style="width: 30%;">
                                    <input type="text" class="form-control" name="no_kk" id="no_kk" required 
                                           maxlength="16" pattern="[0-9]{16}" placeholder="16 digit angka"
                                           oninput="checkNoKK()">
                                    <div id="kkFeedback" class="mt-1 small"></div>
                                    <small class="text-muted">16 digit angka</small>
                                </td>
                                <td style="width: 20%; vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kepala Keluarga <span class="text-danger">*</span></label>
                                </td>
                                <td style="width: 30%;">
                                    <div class="search-container">
                                        <input type="text" class="form-control" id="search_kepala" 
                                               placeholder="Ketik NIK atau nama..." autocomplete="off">
                                        <input type="hidden" name="nik_kepala" id="nik_kepala" required>
                                        <div id="searchKepalaResults" class="search-results"></div>
                                    </div>
                                    <div id="kepalaFeedback" class="mt-1 small"></div>
                                    <small class="text-muted">Ketik minimal 2 karakter untuk mencari</small>
                                </td>
                            </tr>
                            
                            <!-- Alamat -->
                            <tr>
                                <td style="vertical-align: top;">
                                    <label class="form-label fw-bold mb-0">Alamat <span class="text-danger">*</span></label>
                                </td>
                                <td colspan="3">
                                    <textarea class="form-control" name="alamat_kk" rows="2" required placeholder="Masukkan alamat lengkap"></textarea>
                                </td>
                            </tr>
                            
                            <!-- RT, RW, Dusun -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">RT</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="rt" id="rt" value="001" maxlength="3">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">RW</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="rw" id="rw" value="002" maxlength="3">
                                </td>
                            </tr>
                            
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Dusun</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="dusun" id="dusun" placeholder="Opsional">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Desa/Kel</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="desa_kel" id="desa_kel" value="Sukolilo Timur">
                                </td>
                            </tr>
                            
                            <!-- Kecamatan, Kabupaten -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kecamatan</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kecamatan" id="kecamatan" value="Sukolilo">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kabupaten/Kota</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kabupaten_kota" id="kabupaten_kota" value="Bangkalan">
                                </td>
                            </tr>
                            
                            <!-- Provinsi, Kode Pos -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Provinsi</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="provinsi" id="provinsi" value="Jawa Timur">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kode Pos</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kode_pos" id="kode_pos" value="69162" maxlength="5">
                                </td>
                            </tr>
                        </table>
                        
                        <div class="text-muted small mt-3 p-2 bg-light rounded">
                            <span class="text-danger">*</span> Field wajib diisi
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-lg" onclick="closeModalTambahKK()">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" name="submit_tambah_kk" class="btn btn-primary btn-lg" id="submitTambahKKBtn">
                        <i class="fas fa-save me-2"></i>Simpan KK
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Anggota (untuk melihat data lengkap) -->
<div class="modal fade" id="detailAnggotaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-3">
                <h5 class="modal-title">
                    <i class="fas fa-user me-2"></i>Detail Anggota Keluarga
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalDetail()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="detailAnggotaContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 fs-5">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" onclick="closeModalDetail()">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit KK -->
<div class="modal fade" id="editKKModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning py-3">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Kartu Keluarga
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalEditKK()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="keluarga.php" id="formEditKK">
                <input type="hidden" name="no_kk" id="edit_no_kk">
                <input type="hidden" name="from_detail" id="edit_from_detail" value="false">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="container-fluid p-3">
                        <table class="table table-borderless" style="width: 100%;">
                            <!-- Nomor KK (readonly) -->
                            <tr>
                                <td style="width: 20%; vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Nomor KK</label>
                                </td>
                                <td style="width: 30%;">
                                    <input type="text" class="form-control bg-light" id="edit_no_kk_display" readonly>
                                </td>
                                <td style="width: 20%; vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kepala Keluarga</label>
                                </td>
                                <td style="width: 30%;">
                                    <input type="text" class="form-control bg-light" id="edit_kepala" readonly>
                                    <small class="text-muted">Tidak dapat diubah</small>
                                </td>
                            </tr>
                            
                            <!-- Alamat -->
                            <tr>
                                <td style="vertical-align: top;">
                                    <label class="form-label fw-bold mb-0">Alamat <span class="text-danger">*</span></label>
                                </td>
                                <td colspan="3">
                                    <textarea class="form-control" name="alamat_kk" id="edit_alamat_kk" rows="2" required></textarea>
                                </td>
                            </tr>
                            
                            <!-- RT, RW, Dusun -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">RT</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="rt" id="edit_rt" maxlength="3">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">RW</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="rw" id="edit_rw" maxlength="3">
                                </td>
                            </tr>
                            
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Dusun</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="dusun" id="edit_dusun">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Desa/Kel</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="desa_kel" id="edit_desa_kel">
                                </td>
                            </tr>
                            
                            <!-- Kecamatan, Kabupaten -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kecamatan</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kecamatan" id="edit_kecamatan">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kabupaten/Kota</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kabupaten_kota" id="edit_kabupaten_kota">
                                </td>
                            </tr>
                            
                            <!-- Provinsi, Kode Pos -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Provinsi</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="provinsi" id="edit_provinsi">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kode Pos</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kode_pos" id="edit_kode_pos" maxlength="5">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-lg" onclick="closeModalEditKK()">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" name="submit_edit_kk" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Update KK
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Anggota -->
<div class="modal fade" id="tambahAnggotaModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-3">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Tambah Anggota Keluarga
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalTambahAnggota()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="keluarga.php" id="formTambahAnggota">
                <input type="hidden" name="no_kk" id="tambah_anggota_no_kk" value="<?php echo $kk['no_kk'] ?? ''; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Penduduk <span class="text-danger">*</span></label>
                        <div class="search-container">
                            <input type="text" class="form-control" id="search_anggota" 
                                   placeholder="Ketik NIK atau nama..." autocomplete="off">
                            <input type="hidden" name="nik" id="nik_anggota" required>
                            <div id="searchAnggotaResults" class="search-results"></div>
                        </div>
                        <div id="anggotaFeedback" class="mt-1 small"></div>
                        <small class="text-muted">Ketik minimal 2 karakter untuk mencari</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Hubungan Keluarga <span class="text-danger">*</span></label>
                        <select class="form-select" name="hubungan_keluarga" id="hubungan_keluarga" required>
                            <option value="">-- Pilih Hubungan --</option>
                            <option value="Suami">Suami</option>
                            <option value="Istri">Istri</option>
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
                    <button type="button" class="btn btn-secondary" onclick="closeModalTambahAnggota()">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" name="submit_tambah_anggota" class="btn btn-primary" id="submitAnggotaBtn">
                        <i class="fas fa-save me-2"></i>Tambah Anggota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus KK -->
<div class="modal fade" id="confirmHapusKKModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus KK
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalHapusKK()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-4x text-danger"></i>
                </div>
                <p class="fs-5 text-center">Yakin ingin menghapus Kartu Keluarga ini?</p>
                <div id="confirmHapusKKInfo" class="alert alert-warning py-2">
                    <small>Memuat data...</small>
                </div>
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Semua anggota keluarga juga akan dihapus!</strong>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" onclick="closeModalHapusKK()">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmHapusKK">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Anggota -->
<div class="modal fade" id="confirmHapusAnggotaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus Anggota
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalHapusAnggota()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <i class="fas fa-user-minus fa-4x text-danger"></i>
                </div>
                <p class="fs-5 text-center">Yakin ingin menghapus anggota keluarga ini?</p>
                <div id="confirmHapusAnggotaInfo" class="alert alert-warning py-2">
                    <small>Memuat data...</small>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" onclick="closeModalHapusAnggota()">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmHapusAnggota">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ===== VARIABEL GLOBAL =====
let modalTambahKKInstance = null;
let modalEditKKInstance = null;
let modalTambahAnggotaInstance = null;
let modalHapusKKInstance = null;
let modalHapusAnggotaInstance = null;
let modalDetailAnggotaInstance = null;

let searchKepalaTimeout = null;
let searchAnggotaTimeout = null;

// ===== FUNGSI CLOSE MODAL =====
function closeModalTambahKK() {
    if (modalTambahKKInstance) {
        modalTambahKKInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeModalEditKK() {
    if (modalEditKKInstance) {
        modalEditKKInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeModalTambahAnggota() {
    if (modalTambahAnggotaInstance) {
        modalTambahAnggotaInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeModalHapusKK() {
    if (modalHapusKKInstance) {
        modalHapusKKInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeModalHapusAnggota() {
    if (modalHapusAnggotaInstance) {
        modalHapusAnggotaInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeModalDetail() {
    if (modalDetailAnggotaInstance) {
        modalDetailAnggotaInstance.hide();
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

// ===== FUNGSI UTILITAS =====
function formatTanggal(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
}

function showLoading(containerId, message = 'Memuat data...') {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 fs-5">${message}</p>
            </div>
        `;
    }
}

function showError(containerId, message) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${message}
            </div>
        `;
    }
}

function showSuccess(message) {
    const alertHtml = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>${message}
            <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
        </div>
    `;
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
    }
    
    setTimeout(() => {
        const alert = document.querySelector('.alert-success');
        if (alert) alert.remove();
    }, 5000);
}

function showErrorMessage(message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>${message}
            <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
        </div>
    `;
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
    }
    
    setTimeout(() => {
        const alert = document.querySelector('.alert-danger');
        if (alert) alert.remove();
    }, 5000);
}

// ===== FUNGSI CHECK NOMOR KK =====
function checkNoKK() {
    const kkInput = document.getElementById('no_kk');
    if (!kkInput) return;
    
    const kkValue = kkInput.value.trim();
    const feedbackDiv = document.getElementById('kkFeedback');
    const submitBtn = document.getElementById('submitTambahKKBtn');
    
    if (!feedbackDiv || !submitBtn) return;
    
    kkInput.classList.remove('is-invalid', 'is-valid');
    feedbackDiv.innerHTML = '';
    feedbackDiv.className = 'mt-1 small';
    
    if (!kkValue) {
        feedbackDiv.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Masukkan Nomor KK 16 digit</span>';
        submitBtn.disabled = true;
        return;
    }
    
    if (!/^\d+$/.test(kkValue)) {
        kkInput.classList.add('is-invalid');
        feedbackDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Nomor KK harus angka</span>';
        feedbackDiv.classList.add('invalid-feedback');
        submitBtn.disabled = true;
        return;
    }
    
    if (kkValue.length !== 16) {
        kkInput.classList.add('is-invalid');
        feedbackDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Nomor KK harus 16 digit (sekarang ' + kkValue.length + ' digit)</span>';
        feedbackDiv.classList.add('invalid-feedback');
        submitBtn.disabled = true;
        return;
    }
    
    if (kkValue.length === 16 && /^\d+$/.test(kkValue)) {
        feedbackDiv.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Memeriksa Nomor KK...</span>';
        submitBtn.disabled = true;
        
        fetch('keluarga.php?check_kk=' + encodeURIComponent(kkValue))
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    kkInput.classList.add('is-invalid');
                    feedbackDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Nomor KK sudah terdaftar</span>';
                    feedbackDiv.classList.add('invalid-feedback');
                    submitBtn.disabled = true;
                } else {
                    kkInput.classList.add('is-valid');
                    feedbackDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Nomor KK tersedia</span>';
                    feedbackDiv.classList.add('valid-feedback');
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                feedbackDiv.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Gagal memeriksa Nomor KK</span>';
                submitBtn.disabled = false;
            });
    }
}

// ===== FUNGSI SEARCH KEPALA KELUARGA =====
function initSearchKepala() {
    const searchInput = document.getElementById('search_kepala');
    const resultsDiv = document.getElementById('searchKepalaResults');
    const nikInput = document.getElementById('nik_kepala');
    const feedbackDiv = document.getElementById('kepalaFeedback');
    
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const keyword = this.value.trim();
        
        if (keyword.length < 2) {
            resultsDiv.style.display = 'none';
            nikInput.value = '';
            if (feedbackDiv) feedbackDiv.innerHTML = '';
            return;
        }
        
        clearTimeout(searchKepalaTimeout);
        searchKepalaTimeout = setTimeout(() => {
            fetch('keluarga.php?search_kepala=' + encodeURIComponent(keyword))
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        let html = '';
                        result.data.forEach(item => {
                            html += `<div class="search-result-item" onclick="pilihKepala('${item.nik}', '${item.nama_penduduk}')">
                                <strong>${item.nama_penduduk}</strong>
                                <small>NIK: ${item.nik}</small>
                            </div>`;
                        });
                        resultsDiv.innerHTML = html;
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div class="search-result-item text-muted">Tidak ada data penduduk yang tersedia</div>';
                        resultsDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultsDiv.innerHTML = '<div class="search-result-item text-danger">Gagal memuat data</div>';
                    resultsDiv.style.display = 'block';
                });
        }, 300);
    });
    
    // Tutup hasil pencarian saat klik di luar
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
}

function pilihKepala(nik, nama) {
    document.getElementById('search_kepala').value = nama + ' (' + nik + ')';
    document.getElementById('nik_kepala').value = nik;
    document.getElementById('searchKepalaResults').style.display = 'none';
    
    const feedbackDiv = document.getElementById('kepalaFeedback');
    if (feedbackDiv) {
        feedbackDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Kepala keluarga dipilih</span>';
    }
    
    validateFormTambahKK();
}

// ===== FUNGSI SEARCH ANGGOTA KELUARGA =====
function initSearchAnggota(no_kk) {
    const searchInput = document.getElementById('search_anggota');
    const resultsDiv = document.getElementById('searchAnggotaResults');
    const nikInput = document.getElementById('nik_anggota');
    const feedbackDiv = document.getElementById('anggotaFeedback');
    
    if (!searchInput) return;
    
    // Hapus event listener lama
    const newSearchInput = searchInput.cloneNode(true);
    searchInput.parentNode.replaceChild(newSearchInput, searchInput);
    
    newSearchInput.addEventListener('input', function() {
        const keyword = this.value.trim();
        
        if (keyword.length < 2) {
            resultsDiv.style.display = 'none';
            nikInput.value = '';
            if (feedbackDiv) feedbackDiv.innerHTML = '';
            return;
        }
        
        clearTimeout(searchAnggotaTimeout);
        searchAnggotaTimeout = setTimeout(() => {
            fetch('keluarga.php?search_anggota=' + encodeURIComponent(keyword) + '&no_kk=' + encodeURIComponent(no_kk))
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        let html = '';
                        result.data.forEach(item => {
                            html += `<div class="search-result-item" onclick="pilihAnggota('${item.nik}', '${item.nama_penduduk}')">
                                <strong>${item.nama_penduduk}</strong>
                                <small>NIK: ${item.nik}</small>
                            </div>`;
                        });
                        resultsDiv.innerHTML = html;
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div class="search-result-item text-muted">Tidak ada penduduk tersedia</div>';
                        resultsDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultsDiv.innerHTML = '<div class="search-result-item text-danger">Gagal memuat data</div>';
                    resultsDiv.style.display = 'block';
                });
        }, 300);
    });
    
    // Tutup hasil pencarian saat klik di luar
    document.addEventListener('click', function(e) {
        if (!newSearchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
}

function pilihAnggota(nik, nama) {
    document.getElementById('search_anggota').value = nama + ' (' + nik + ')';
    document.getElementById('nik_anggota').value = nik;
    document.getElementById('searchAnggotaResults').style.display = 'none';
    
    const feedbackDiv = document.getElementById('anggotaFeedback');
    if (feedbackDiv) {
        feedbackDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Anggota dipilih</span>';
    }
    
    validateFormAnggota();
}

// ===== FUNGSI VALIDASI FORM =====
function validateFormTambahKK() {
    const no_kk = document.getElementById('no_kk')?.value;
    const nik_kepala = document.getElementById('nik_kepala')?.value;
    const submitBtn = document.getElementById('submitTambahKKBtn');
    
    if (!no_kk || no_kk.length !== 16) {
        if (submitBtn) submitBtn.disabled = true;
        return false;
    }
    
    if (!nik_kepala) {
        if (submitBtn) submitBtn.disabled = true;
        return false;
    }
    
    if (submitBtn) submitBtn.disabled = false;
    return true;
}

function validateFormAnggota() {
    const nik = document.getElementById('nik_anggota')?.value;
    const hubungan = document.getElementById('hubungan_keluarga')?.value;
    const submitBtn = document.getElementById('submitAnggotaBtn');
    
    if (!nik || !hubungan) {
        if (submitBtn) submitBtn.disabled = true;
        return false;
    }
    
    if (submitBtn) submitBtn.disabled = false;
    return true;
}

// ===== FUNGSI LOAD DATA EDIT =====
function loadEditData(no_kk, button, fromDetail = false) {
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    fetch('keluarga.php?ajax_get_kk=' + encodeURIComponent(no_kk))
        .then(response => response.json())
        .then(result => {
            button.innerHTML = originalHTML;
            button.disabled = false;
            
            if (result.success) {
                const data = result.data;
                
                // Isi form edit
                document.getElementById('edit_no_kk').value = data.no_kk;
                document.getElementById('edit_no_kk_display').value = data.no_kk;
                document.getElementById('edit_kepala').value = data.nama_kepala + ' (' + data.nik_kepala + ')';
                document.getElementById('edit_alamat_kk').value = data.alamat_kk || '';
                document.getElementById('edit_rt').value = data.rt || '001';
                document.getElementById('edit_rw').value = data.rw || '002';
                document.getElementById('edit_dusun').value = data.dusun || '';
                document.getElementById('edit_desa_kel').value = data.desa_kel || 'Sukolilo Timur';
                document.getElementById('edit_kecamatan').value = data.kecamatan || 'Sukolilo';
                document.getElementById('edit_kabupaten_kota').value = data.kabupaten_kota || 'Bangkalan';
                document.getElementById('edit_provinsi').value = data.provinsi || 'Jawa Timur';
                document.getElementById('edit_kode_pos').value = data.kode_pos || '69162';
                document.getElementById('edit_from_detail').value = fromDetail ? 'true' : 'false';
                
                // Tampilkan modal
                if (modalEditKKInstance) {
                    modalEditKKInstance.show();
                }
            } else {
                alert('Gagal mengambil data: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            button.innerHTML = originalHTML;
            button.disabled = false;
            alert('Terjadi kesalahan saat mengambil data');
        });
}

// ===== FUNGSI HAPUS KK =====
function hapusKK(no_kk) {
    const btn = document.getElementById('btnConfirmHapusKK');
    if (!btn) return;
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
    btn.disabled = true;
    
    fetch('keluarga.php?ajax_hapus_kk=' + encodeURIComponent(no_kk))
        .then(response => response.json())
        .then(data => {
            if (modalHapusKKInstance) {
                modalHapusKKInstance.hide();
            }
            
            if (data.success) {
                showSuccess('Data KK berhasil dihapus');
                setTimeout(() => {
                    window.location.href = 'keluarga.php';
                }, 1000);
            } else {
                showErrorMessage('Gagal menghapus data: ' + (data.message || 'Unknown error'));
            }
            
            btn.innerHTML = originalText;
            btn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            if (modalHapusKKInstance) {
                modalHapusKKInstance.hide();
            }
            showErrorMessage('Terjadi kesalahan saat menghapus data');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

// ===== FUNGSI HAPUS ANGGOTA =====
function hapusAnggota(id_anggota, no_kk, nama) {
    const btn = document.getElementById('btnConfirmHapusAnggota');
    if (!btn) return;
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
    btn.disabled = true;
    
    fetch('keluarga.php?ajax_hapus_anggota=' + encodeURIComponent(id_anggota))
        .then(response => response.json())
        .then(data => {
            if (modalHapusAnggotaInstance) {
                modalHapusAnggotaInstance.hide();
            }
            
            if (data.success) {
                showSuccess('Anggota keluarga berhasil dihapus');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showErrorMessage('Gagal menghapus anggota: ' + (data.message || 'Unknown error'));
            }
            
            btn.innerHTML = originalText;
            btn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            if (modalHapusAnggotaInstance) {
                modalHapusAnggotaInstance.hide();
            }
            showErrorMessage('Terjadi kesalahan saat menghapus anggota');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

// ===== FUNGSI LIHAT DETAIL ANGGOTA =====
function lihatDetailAnggota(nik) {
    const modalDetail = document.getElementById('detailAnggotaModal');
    if (!modalDetail) return;
    
    if (!modalDetailAnggotaInstance) {
        modalDetailAnggotaInstance = new bootstrap.Modal(modalDetail);
    }
    
    const contentDiv = document.getElementById('detailAnggotaContent');
    showLoading('detailAnggotaContent', 'Mengambil data penduduk...');
    modalDetailAnggotaInstance.show();
    
    fetch('penduduk.php?ajax_get_penduduk=' + encodeURIComponent(nik))
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;
                const tglLahir = data.tanggal_lahir ? formatTanggal(data.tanggal_lahir) : '-';
                
                contentDiv.innerHTML = `
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12 mb-4 text-center">
                                <i class="fas fa-user-circle fa-5x text-info mb-3"></i>
                                <h4 class="mb-0">${data.nama_penduduk || '-'}</h4>
                                <p class="text-muted">${data.nik || '-'}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">Tempat Lahir</div>
                                    <div class="detail-value">${data.tempat_lahir || '-'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">Tanggal Lahir</div>
                                    <div class="detail-value">${tglLahir}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">Jenis Kelamin</div>
                                    <div class="detail-value">${data.jenis_kelamin || '-'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">Agama</div>
                                    <div class="detail-value">${data.agama || '-'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">Status Kawin</div>
                                    <div class="detail-value">${data.status_kawin || '-'}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">Pendidikan</div>
                                    <div class="detail-value">${data.pendidikan || '-'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">Pekerjaan</div>
                                    <div class="detail-value">${data.pekerjaan || '-'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">Nama Ayah</div>
                                    <div class="detail-value">${data.nama_ayah || '-'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">Nama Ibu</div>
                                    <div class="detail-value">${data.nama_ibu || '-'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">Alamat</div>
                                    <div class="detail-value">${data.alamat || '-'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="detail-label fw-bold">RT/RW</div>
                                    <div class="detail-value">${data.rt_rw || '-'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                showError('detailAnggotaContent', 'Gagal mengambil data: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('detailAnggotaContent', 'Terjadi kesalahan saat mengambil data');
        });
}

// ===== FUNGSI TAMBAH TOMBOL VIEW DETAIL =====
function tambahTombolViewDetail() {
    const tabelAnggota = document.getElementById('tabelAnggota');
    if (!tabelAnggota) return;
    
    const rows = tabelAnggota.querySelectorAll('tbody tr:not(.table-primary)');
    rows.forEach(row => {
        const aksiCell = row.querySelector('td:last-child');
        const nikCell = row.querySelector('td:nth-child(2)');
        
        if (aksiCell && nikCell) {
            const nik = nikCell.textContent.trim();
            
            // Cek apakah tombol view sudah ada
            if (!aksiCell.querySelector('.btn-view-detail')) {
                const viewBtn = document.createElement('button');
                viewBtn.className = 'btn btn-sm btn-info me-1 btn-view-detail';
                viewBtn.innerHTML = '<i class="fas fa-eye"></i>';
                viewBtn.title = 'Lihat Detail';
                viewBtn.onclick = function(e) {
                    e.preventDefault();
                    lihatDetailAnggota(nik);
                };
                
                const hapusBtn = aksiCell.querySelector('.btn-hapus-anggota');
                if (hapusBtn) {
                    aksiCell.insertBefore(viewBtn, hapusBtn);
                } else {
                    aksiCell.appendChild(viewBtn);
                }
            }
        }
    });
}

// ===== INISIALISASI =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Loaded - Initializing...');
    
    // Inisialisasi modal
    const tambahModal = document.getElementById('tambahKKModal');
    const editModal = document.getElementById('editKKModal');
    const tambahAnggotaModal = document.getElementById('tambahAnggotaModal');
    const hapusModal = document.getElementById('confirmHapusKKModal');
    const hapusAnggotaModal = document.getElementById('confirmHapusAnggotaModal');
    const detailModal = document.getElementById('detailAnggotaModal');
    
    if (tambahModal) modalTambahKKInstance = new bootstrap.Modal(tambahModal);
    if (editModal) modalEditKKInstance = new bootstrap.Modal(editModal);
    if (tambahAnggotaModal) modalTambahAnggotaInstance = new bootstrap.Modal(tambahAnggotaModal);
    if (hapusModal) modalHapusKKInstance = new bootstrap.Modal(hapusModal);
    if (hapusAnggotaModal) modalHapusAnggotaInstance = new bootstrap.Modal(hapusAnggotaModal);
    if (detailModal) modalDetailAnggotaInstance = new bootstrap.Modal(detailModal);
    
    // Inisialisasi search kepala keluarga
    initSearchKepala();
    
    // Tombol Tambah KK
    const btnTambah = document.getElementById('btnTambahKK');
    if (btnTambah) {
        btnTambah.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Reset form
            const form = document.getElementById('formTambahKK');
            if (form) form.reset();
            
            // Reset search
            const searchInput = document.getElementById('search_kepala');
            if (searchInput) searchInput.value = '';
            
            // Reset hidden input
            document.getElementById('nik_kepala').value = '';
            
            // Reset validasi
            const kkInput = document.getElementById('no_kk');
            const feedback = document.getElementById('kkFeedback');
            const kepalaFeedback = document.getElementById('kepalaFeedback');
            
            if (kkInput) kkInput.classList.remove('is-invalid', 'is-valid');
            if (feedback) {
                feedback.innerHTML = '';
                feedback.className = 'mt-1 small';
            }
            if (kepalaFeedback) {
                kepalaFeedback.innerHTML = '';
            }
            
            // Set default values
            const rt = document.getElementById('rt');
            const rw = document.getElementById('rw');
            const desa = document.getElementById('desa_kel');
            const kec = document.getElementById('kecamatan');
            const kab = document.getElementById('kabupaten_kota');
            const prov = document.getElementById('provinsi');
            const kodepos = document.getElementById('kode_pos');
            
            if (rt) rt.value = '001';
            if (rw) rw.value = '002';
            if (desa) desa.value = 'Sukolilo Timur';
            if (kec) kec.value = 'Sukolilo';
            if (kab) kab.value = 'Bangkalan';
            if (prov) prov.value = 'Jawa Timur';
            if (kodepos) kodepos.value = '69162';
            
            if (modalTambahKKInstance) {
                modalTambahKKInstance.show();
            }
        });
    }
    
    // Tombol Edit KK (dari list)
    document.querySelectorAll('.btn-edit-kk').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const no_kk = this.getAttribute('data-no-kk');
            loadEditData(no_kk, this, false);
        });
    });
    
    // Tombol Edit KK (dari detail)
    document.querySelectorAll('.btn-edit-kk-detail').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const no_kk = this.getAttribute('data-no-kk');
            loadEditData(no_kk, this, true);
        });
    });
    
    // Tombol Tambah Anggota
    const btnTambahAnggota = document.getElementById('btnTambahAnggota');
    if (btnTambahAnggota) {
        btnTambahAnggota.addEventListener('click', function(e) {
            e.preventDefault();
            const no_kk = this.getAttribute('data-no-kk');
            
            // Reset form
            const form = document.getElementById('formTambahAnggota');
            if (form) form.reset();
            
            // Reset search
            const searchInput = document.getElementById('search_anggota');
            if (searchInput) searchInput.value = '';
            
            // Reset hidden input
            document.getElementById('nik_anggota').value = '';
            
            // Reset hubungan
            document.getElementById('hubungan_keluarga').value = '';
            
            // Set no_kk
            document.getElementById('tambah_anggota_no_kk').value = no_kk;
            
            // Reset feedback
            const anggotaFeedback = document.getElementById('anggotaFeedback');
            const hubunganFeedback = document.getElementById('hubunganFeedback');
            if (anggotaFeedback) anggotaFeedback.innerHTML = '';
            if (hubunganFeedback) hubunganFeedback.innerHTML = '';
            
            // Reset submit button
            const submitBtn = document.getElementById('submitAnggotaBtn');
            if (submitBtn) submitBtn.disabled = true;
            
            // Inisialisasi search anggota dengan no_kk
            initSearchAnggota(no_kk);
            
            if (modalTambahAnggotaInstance) {
                modalTambahAnggotaInstance.show();
            }
        });
    }
    
    // Tombol Hapus KK (dari list)
    document.querySelectorAll('.btn-hapus-kk').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const no_kk = this.getAttribute('data-no-kk');
            const kepala = this.getAttribute('data-kepala');
            
            const infoDiv = document.getElementById('confirmHapusKKInfo');
            if (infoDiv) {
                infoDiv.innerHTML = `
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td width="40%" class="text-muted">Nomor KK</td>
                            <td><strong>${no_kk}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kepala Keluarga</td>
                            <td><strong>${kepala}</strong></td>
                        </tr>
                    </table>
                `;
            }
            
            const confirmBtn = document.getElementById('btnConfirmHapusKK');
            if (confirmBtn) {
                confirmBtn.setAttribute('data-no-kk', no_kk);
            }
            
            if (modalHapusKKInstance) {
                modalHapusKKInstance.show();
            }
        });
    });
    
    // Tombol Hapus KK (dari detail)
    document.querySelectorAll('.btn-hapus-kk-detail').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const no_kk = this.getAttribute('data-no-kk');
            const kepala = this.getAttribute('data-kepala');
            
            const infoDiv = document.getElementById('confirmHapusKKInfo');
            if (infoDiv) {
                infoDiv.innerHTML = `
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td width="40%" class="text-muted">Nomor KK</td>
                            <td><strong>${no_kk}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kepala Keluarga</td>
                            <td><strong>${kepala}</strong></td>
                        </tr>
                    </table>
                `;
            }
            
            const confirmBtn = document.getElementById('btnConfirmHapusKK');
            if (confirmBtn) {
                confirmBtn.setAttribute('data-no-kk', no_kk);
            }
            
            if (modalHapusKKInstance) {
                modalHapusKKInstance.show();
            }
        });
    });
    
    // Tombol Konfirmasi Hapus KK
    const btnConfirmHapusKK = document.getElementById('btnConfirmHapusKK');
    if (btnConfirmHapusKK) {
        btnConfirmHapusKK.addEventListener('click', function(e) {
            e.preventDefault();
            const no_kk = this.getAttribute('data-no-kk');
            if (no_kk) {
                hapusKK(no_kk);
            }
        });
    }
    
    // Tombol Hapus Anggota
    document.querySelectorAll('.btn-hapus-anggota').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id_anggota = this.getAttribute('data-id');
            const no_kk = this.getAttribute('data-no-kk');
            const nik = this.getAttribute('data-nik');
            const nama = this.getAttribute('data-nama');
            
            const infoDiv = document.getElementById('confirmHapusAnggotaInfo');
            if (infoDiv) {
                infoDiv.innerHTML = `
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td width="40%" class="text-muted">NIK</td>
                            <td><strong>${nik}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama</td>
                            <td><strong>${nama}</strong></td>
                        </tr>
                    </table>
                `;
            }
            
            const confirmBtn = document.getElementById('btnConfirmHapusAnggota');
            if (confirmBtn) {
                confirmBtn.setAttribute('data-id', id_anggota);
                confirmBtn.setAttribute('data-no-kk', no_kk);
                confirmBtn.setAttribute('data-nama', nama);
            }
            
            if (modalHapusAnggotaInstance) {
                modalHapusAnggotaInstance.show();
            }
        });
    });
    
    // Tombol Konfirmasi Hapus Anggota
    const btnConfirmHapusAnggota = document.getElementById('btnConfirmHapusAnggota');
    if (btnConfirmHapusAnggota) {
        btnConfirmHapusAnggota.addEventListener('click', function(e) {
            e.preventDefault();
            const id_anggota = this.getAttribute('data-id');
            const no_kk = this.getAttribute('data-no-kk');
            const nama = this.getAttribute('data-nama');
            
            if (id_anggota) {
                hapusAnggota(id_anggota, no_kk, nama);
            }
        });
    }
    
    // Validasi form tambah KK
    const formTambah = document.getElementById('formTambahKK');
    if (formTambah) {
        formTambah.addEventListener('submit', function(e) {
            if (!validateFormTambahKK()) {
                e.preventDefault();
                alert('Harap lengkapi semua field yang wajib diisi!');
            }
        });
    }
    
    // Validasi form tambah anggota
    const formTambahAnggota = document.getElementById('formTambahAnggota');
    if (formTambahAnggota) {
        formTambahAnggota.addEventListener('submit', function(e) {
            if (!validateFormAnggota()) {
                e.preventDefault();
                alert('Harap pilih penduduk dan hubungan keluarga!');
            }
        });
    }
    
    // Hubungan keluarga change listener
    const hubunganSelect = document.getElementById('hubungan_keluarga');
    if (hubunganSelect) {
        hubunganSelect.addEventListener('change', function() {
            const feedback = document.getElementById('hubunganFeedback');
            if (this.value) {
                feedback.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Hubungan dipilih</span>';
            } else {
                feedback.innerHTML = '';
            }
            validateFormAnggota();
        });
    }
    
    // Tambahkan tombol view detail untuk setiap anggota
    tambahTombolViewDetail();
    
    // Cleanup backdrop saat modal ditutup
    document.querySelectorAll('.modal').forEach(modalEl => {
        modalEl.addEventListener('hidden.bs.modal', function() {
            cleanupBackdrop();
        });
    });
    
    // Auto dismiss alert
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('alert-dismissible')) {
                alert.remove();
            }
        });
    }, 5000);
    
    // Search form enter key
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });
    }
    
    // Input listener untuk no_kk
    const noKKInput = document.getElementById('no_kk');
    if (noKKInput) {
        noKKInput.addEventListener('input', checkNoKK);
    }
    
    console.log('Initialization complete!');
});

// ===== FUNGSI GLOBAL UNTUK DIPANGGIL DARI HTML =====
window.checkNoKK = checkNoKK;
window.validateFormTambahKK = validateFormTambahKK;
window.validateFormAnggota = validateFormAnggota;
window.closeModalTambahKK = closeModalTambahKK;
window.closeModalEditKK = closeModalEditKK;
window.closeModalTambahAnggota = closeModalTambahAnggota;
window.closeModalHapusKK = closeModalHapusKK;
window.closeModalHapusAnggota = closeModalHapusAnggota;
window.closeModalDetail = closeModalDetail;
window.closeAlert = closeAlert;
window.pilihKepala = pilihKepala;
window.pilihAnggota = pilihAnggota;
window.lihatDetailAnggota = lihatDetailAnggota;
</script>

<?php
$content = ob_get_clean();
include 'template1/base.php';
?>