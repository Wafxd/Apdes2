<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

include "../db/koneksi.php";
include "../db/funct.php";

$id_user = $_SESSION['id_admin'];
$username = $_SESSION["nama_admin"];

// ==================== AJAX HANDLER ====================

// AJAX: Get data admin by ID
if (isset($_GET['ajax_get_admin'])) {
    header('Content-Type: application/json');
    $id_admin = mysqli_real_escape_string($conn, $_GET['ajax_get_admin']);
    
    $query = "SELECT id_admin, nama_admin, last_login, login_attempts, is_active, last_ip FROM tb_admin WHERE id_admin = '$id_admin'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    }
    exit();
}

// AJAX: Update profil (nama)
if (isset($_POST['ajax_update_profil'])) {
    header('Content-Type: application/json');
    
    $id_admin = mysqli_real_escape_string($conn, $_POST['id_admin']);
    $nama_admin = mysqli_real_escape_string($conn, trim($_POST['nama_admin']));
    
    if (empty($nama_admin)) {
        echo json_encode(['success' => false, 'message' => 'Nama tidak boleh kosong']);
        exit();
    }
    
    // Cek apakah username sudah digunakan oleh admin lain
    $check = mysqli_query($conn, "SELECT id_admin FROM tb_admin WHERE nama_admin = '$nama_admin' AND id_admin != '$id_admin'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'message' => 'Nama pengguna sudah digunakan']);
        exit();
    }
    
    $query = "UPDATE tb_admin SET nama_admin = '$nama_admin' WHERE id_admin = '$id_admin'";
    
    if (mysqli_query($conn, $query)) {
        // Update session
        $_SESSION['nama_admin'] = $nama_admin;
        
        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil: ' . mysqli_error($conn)]);
    }
    exit();
}

// AJAX: Update password
if (isset($_POST['ajax_update_password'])) {
    header('Content-Type: application/json');
    
    $id_admin = mysqli_real_escape_string($conn, $_POST['id_admin']);
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Validasi
    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        echo json_encode(['success' => false, 'message' => 'Semua field password harus diisi']);
        exit();
    }
    
    if ($password_baru !== $konfirmasi_password) {
        echo json_encode(['success' => false, 'message' => 'Konfirmasi password tidak cocok']);
        exit();
    }
    
    if (strlen($password_baru) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter']);
        exit();
    }
    
    // Ambil password lama dari database
    $result = mysqli_query($conn, "SELECT password FROM tb_admin WHERE id_admin = '$id_admin'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Data admin tidak ditemukan']);
        exit();
    }
    
    $row = mysqli_fetch_assoc($result);
    $password_db = $row['password'];
    
    // Verifikasi password lama
    $verifikasi = false;
    
    // Cek apakah password di database ter-hash atau plain text
    $is_hash = (strlen($password_db) == 60 && substr($password_db, 0, 4) == '$2y$');
    
    if ($is_hash) {
        // Password ter-hash
        $verifikasi = password_verify($password_lama, $password_db);
    } else {
        // Password plain text - bersihkan dari spasi
        $password_db_clean = trim($password_db);
        $password_lama_clean = trim($password_lama);
        $verifikasi = ($password_lama_clean === $password_db_clean);
        
        // Debug logging (hapus setelah selesai)
        error_log("Plain text verification: input='$password_lama_clean', db='$password_db_clean', result=" . ($verifikasi ? 'true' : 'false'));
    }
    
    if (!$verifikasi) {
        echo json_encode([
            'success' => false, 
            'message' => 'Password lama salah. Pastikan Anda mengetik password dengan benar.'
        ]);
        exit();
    }
    
    // Hash password baru
    $password_baru_hash = password_hash($password_baru, PASSWORD_DEFAULT);
    
    $query = "UPDATE tb_admin SET password = '$password_baru_hash' WHERE id_admin = '$id_admin'";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah password: ' . mysqli_error($conn)]);
    }
    exit();
}

// ==================== DATA STATISTIK ====================
// Data login terakhir
$last_logins = [];
$query_last_login = "SELECT timestamp, ip_address FROM log_aktivitas 
                     WHERE id_admin = '$id_user' AND action = 'login' AND status = 'success' 
                     ORDER BY timestamp DESC LIMIT 5";
$result_last_login = mysqli_query($conn, $query_last_login);
if ($result_last_login) {
    while ($row = mysqli_fetch_assoc($result_last_login)) {
        $last_logins[] = $row;
    }
}

// Total aktivitas
$total_aktivitas = 0;
$query_total_aktivitas = "SELECT COUNT(*) as total FROM log_aktivitas WHERE id_admin = '$id_user'";
$result_total_aktivitas = mysqli_query($conn, $query_total_aktivitas);
if ($result_total_aktivitas) {
    $total_aktivitas = mysqli_fetch_assoc($result_total_aktivitas)['total'];
}

// Aktivitas sukses dan gagal
$total_sukses = 0;
$query_sukses = "SELECT COUNT(*) as total FROM log_aktivitas WHERE id_admin = '$id_user' AND status = 'success'";
$result_sukses = mysqli_query($conn, $query_sukses);
if ($result_sukses) {
    $total_sukses = mysqli_fetch_assoc($result_sukses)['total'];
}

$total_gagal = 0;
$query_gagal = "SELECT COUNT(*) as total FROM log_aktivitas WHERE id_admin = '$id_user' AND status = 'failed'";
$result_gagal = mysqli_query($conn, $query_gagal);
if ($result_gagal) {
    $total_gagal = mysqli_fetch_assoc($result_gagal)['total'];
}

// Aktivitas per action
$actions = [];
$query_actions = "SELECT action, COUNT(*) as total FROM log_aktivitas WHERE id_admin = '$id_user' GROUP BY action ORDER BY total DESC LIMIT 5";
$result_actions = mysqli_query($conn, $query_actions);
if ($result_actions) {
    while ($row = mysqli_fetch_assoc($result_actions)) {
        $actions[] = $row;
    }
}

// Ambil data admin untuk ditampilkan
$query_admin = "SELECT * FROM tb_admin WHERE id_admin = '$id_user'";
$result_admin = mysqli_query($conn, $query_admin);
$admin_data = mysqli_fetch_assoc($result_admin);

// ==================== TEMPLATE CONTENT ====================
$pageTitle = "Profil Pengguna";
ob_start();
?>

<style>
/* Style sama dengan penduduk.php */
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
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

/* Card styling */
.profile-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
    overflow: hidden;
}
.profile-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    padding: 15px 20px;
    font-weight: 600;
}
.profile-header i {
    margin-right: 10px;
}
.profile-body {
    padding: 20px;
}

/* Form styling */
.form-label {
    font-weight: 600;
    color: #5a5c69;
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
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
.form-control.is-invalid {
    border-color: #e74a3b;
}
.form-control.is-valid {
    border-color: #1cc88a;
}

/* Button styling */
.btn {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 500;
}
.btn-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    border: none;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #2e59d9 0%, #1a3a9e 100%);
}
.btn-success {
    background: linear-gradient(135deg, #1cc88a 0%, #169b6b 100%);
    border: none;
}
.btn-success:hover {
    background: linear-gradient(135deg, #17a673 0%, #12805a 100%);
}
.btn-info {
    background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
    border: none;
    color: white;
}
.btn-info:hover {
    background: linear-gradient(135deg, #2c9faf 0%, #1e6f7c 100%);
}

/* Table styling */
.table-custom {
    width: 100%;
    border-collapse: collapse;
}
.table-custom th {
    background-color: #f8f9fc;
    color: #4e73df;
    font-weight: 600;
    padding: 12px 10px;
    border-bottom: 2px solid #4e73df;
}
.table-custom td {
    padding: 10px;
    border-bottom: 1px solid #e3e6f0;
    vertical-align: middle;
}
.table-custom tr:last-child td {
    border-bottom: none;
}
.table-custom tr:hover td {
    background-color: #f8f9fc;
}

/* Info box */
.info-box {
    background-color: #f8f9fc;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    border-left: 4px solid #36b9cc;
}
.info-box i {
    color: #36b9cc;
    margin-right: 8px;
}

/* Alert styling */
.alert {
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    border: none;
    font-weight: 500;
}
.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border-left: 4px solid #28a745;
}
.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border-left: 4px solid #dc3545;
}
.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
    color: #856404;
    border-left: 4px solid #ffc107;
}
.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    color: #0c5460;
    border-left: 4px solid #17a2b8;
}

/* Password strength meter */
.password-strength {
    margin-top: 5px;
    font-size: 0.85rem;
}
.strength-meter {
    height: 5px;
    background-color: #e9ecef;
    border-radius: 10px;
    margin-top: 5px;
    overflow: hidden;
}
.strength-meter-bar {
    height: 100%;
    width: 0%;
    transition: width 0.3s ease;
}
.strength-weak .strength-meter-bar {
    background-color: #e74a3b;
    width: 33%;
}
.strength-medium .strength-meter-bar {
    background-color: #f6c23e;
    width: 66%;
}
.strength-strong .strength-meter-bar {
    background-color: #1cc88a;
    width: 100%;
}

/* Badge */
.badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.badge-active {
    background-color: #d4edda;
    color: #155724;
}
.badge-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

/* Avatar */
.profile-avatar {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    color: white;
    font-size: 40px;
    font-weight: 600;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 32px;
    }
}
</style>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-circle me-2"></i>Profil Pengguna
        </h1>
        <div>
            <button type="button" class="btn btn-info btn-sm" id="btnRefresh">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alertContainer"></div>

    <!-- Statistik Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <h6>Total Aktivitas</h6>
                <div class="value"><?php echo number_format($total_aktivitas); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card border-left-success">
                <h6>Aktivitas Sukses</h6>
                <div class="value text-success"><?php echo number_format($total_sukses); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card border-left-warning">
                <h6>Aktivitas Gagal</h6>
                <div class="value text-warning"><?php echo number_format($total_gagal); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card border-left-info">
                <h6>ID Pengguna</h6>
                <div class="value text-info">#<?php echo $id_user; ?></div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Kolom Kiri: Profil & Ubah Nama -->
        <div class="col-lg-4">
            <div class="profile-card">
                <div class="profile-header">
                    <i class="fas fa-id-card"></i> Informasi Profil
                </div>
                <div class="profile-body text-center">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($username); ?></h4>
                    <p class="text-muted mb-3">Administrator</p>
                    
                    <div class="info-box text-start">
                        <p class="mb-2"><i class="fas fa-id-badge"></i> <strong>ID Admin:</strong> <?php echo $id_user; ?></p>
                        <p class="mb-2"><i class="fas fa-power-off"></i> <strong>Status:</strong> 
                            <span class="badge badge-active">Active</span>
                        </p>
                        <p class="mb-2"><i class="fas fa-calendar-alt"></i> <strong>Terdaftar:</strong> 
                            <?php echo isset($admin_data['created_at']) ? date('d/m/Y', strtotime($admin_data['created_at'])) : '-'; ?>
                        </p>
                        <p class="mb-0"><i class="fas fa-clock"></i> <strong>Login Terakhir:</strong> 
                            <?php echo isset($last_logins[0]['timestamp']) ? date('d/m/Y H:i:s', strtotime($last_logins[0]['timestamp'])) : '-'; ?>
                        </p>
                    </div>

                    <hr>

                    <h5 class="mb-3 text-start">Ubah Nama Pengguna</h5>
                    <form id="formUbahNama">
                        <input type="hidden" name="id_admin" value="<?php echo $id_user; ?>">
                        <div class="mb-3">
                            <label class="form-label">Nama Pengguna</label>
                            <input type="text" class="form-control" name="nama_admin" id="nama_admin" 
                                   value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="btnUbahNama">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Ubah Password & Aktivitas -->
        <div class="col-lg-8">
            <!-- Card Ubah Password -->
            <div class="profile-card">
                <div class="profile-header">
                    <i class="fas fa-lock"></i> Ubah Password
                </div>
                <div class="profile-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Password minimal 6 karakter. Gunakan kombinasi huruf dan angka untuk keamanan lebih baik.
                    </div>

                    <form id="formUbahPassword">
                        <input type="hidden" name="id_admin" value="<?php echo $id_user; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password Lama</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password_lama" id="password_lama" 
                                           placeholder="Masukkan password lama" required autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_lama')">
                                        <i class="fas fa-eye" id="toggle_lama"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Masukkan password Anda saat ini</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password_baru" id="password_baru" 
                                           placeholder="Minimal 6 karakter" required autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_baru')">
                                        <i class="fas fa-eye" id="toggle_baru"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength">
                                    <small class="text-muted">Kekuatan password</small>
                                    <div class="strength-meter">
                                        <div class="strength-meter-bar"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="konfirmasi_password" id="konfirmasi_password" 
                                           placeholder="Ulangi password baru" required autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('konfirmasi_password')">
                                        <i class="fas fa-eye" id="toggle_konfirmasi"></i>
                                    </button>
                                </div>
                                <div class="mt-1" id="passwordMatch"></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success" id="btnUbahPassword">
                            <i class="fas fa-key me-2"></i>Ubah Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Card Aktivitas Terakhir -->
            <div class="profile-card mt-4">
                <div class="profile-header">
                    <i class="fas fa-history"></i> Aktivitas Terakhir
                </div>
                <div class="profile-body">
                    <ul class="nav nav-tabs mb-3" id="aktivitasTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">
                                Login Terakhir
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="aktivitas-tab" data-bs-toggle="tab" data-bs-target="#aktivitas" type="button">
                                Aktivitas Terbanyak
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="aktivitasTabContent">
                        <!-- Tab Login Terakhir -->
                        <div class="tab-pane fade show active" id="login" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table-custom">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal & Waktu</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($last_logins) > 0): ?>
                                            <?php $no = 1; ?>
                                            <?php foreach ($last_logins as $login): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo date('d/m/Y H:i:s', strtotime($login['timestamp'])); ?></td>
                                                <td><?php echo htmlspecialchars($login['ip_address']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">Belum ada aktivitas login</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Aktivitas Terbanyak -->
                        <div class="tab-pane fade" id="aktivitas" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table-custom">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Jenis Aktivitas</th>
                                            <th>Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($actions) > 0): ?>
                                            <?php $no = 1; ?>
                                            <?php foreach ($actions as $action): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo ucfirst(htmlspecialchars($action['action'])); ?></td>
                                                <td><?php echo number_format($action['total']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">Belum ada data aktivitas</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Tips Keamanan:</strong> 
                        Ganti password secara berkala dan jangan gunakan password yang sama dengan akun lain.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>Berhasil
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                <p class="fs-5 mt-3" id="confirmMessage">Perubahan berhasil disimpan</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// ========== FUNGSI UTILITAS ==========
function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    const alertClass = type === 'success' ? 'alert-success' : 
                       type === 'danger' ? 'alert-danger' : 
                       type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                               type === 'danger' ? 'exclamation-circle' : 
                               type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
        </div>
    `;
    
    alertContainer.innerHTML = alertHtml;
    
    setTimeout(() => {
        const alert = alertContainer.querySelector('.alert');
        if (alert) alert.remove();
    }, 5000);
}

function closeAlert(element) {
    const alert = element.closest('.alert');
    if (alert) alert.remove();
}

function showConfirmModal(message) {
    document.getElementById('confirmMessage').innerHTML = message;
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
    setTimeout(() => modal.hide(), 2000);
}

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    let toggleId = '';
    
    if (fieldId === 'password_lama') toggleId = 'toggle_lama';
    else if (fieldId === 'password_baru') toggleId = 'toggle_baru';
    else if (fieldId === 'konfirmasi_password') toggleId = 'toggle_konfirmasi';
    
    const toggle = document.getElementById(toggleId);
    
    if (field.type === 'password') {
        field.type = 'text';
        if (toggle) toggle.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        if (toggle) toggle.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Check password strength
function checkPasswordStrength(password) {
    const strengthDiv = document.getElementById('passwordStrength');
    if (!strengthDiv) return;
    
    strengthDiv.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
    
    if (!password) {
        document.querySelector('.password-strength small').textContent = 'Kekuatan password';
        return;
    }
    
    let strength = 0;
    
    // Panjang minimal 6
    if (password.length >= 6) strength += 1;
    if (password.length >= 8) strength += 1;
    
    // Mengandung angka
    if (/\d/.test(password)) strength += 1;
    
    // Mengandung huruf besar
    if (/[A-Z]/.test(password)) strength += 1;
    
    // Mengandung karakter khusus
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;
    
    if (strength <= 2) {
        strengthDiv.classList.add('strength-weak');
        document.querySelector('.password-strength small').textContent = 'Kekuatan: Lemah';
    } else if (strength <= 4) {
        strengthDiv.classList.add('strength-medium');
        document.querySelector('.password-strength small').textContent = 'Kekuatan: Sedang';
    } else {
        strengthDiv.classList.add('strength-strong');
        document.querySelector('.password-strength small').textContent = 'Kekuatan: Kuat';
    }
}

// Check password match
function checkPasswordMatch() {
    const password = document.getElementById('password_baru').value;
    const confirm = document.getElementById('konfirmasi_password').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (!confirm) {
        matchDiv.innerHTML = '';
        document.getElementById('konfirmasi_password').classList.remove('is-invalid', 'is-valid');
        return;
    }
    
    if (password === confirm) {
        matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>Password cocok</small>';
        document.getElementById('konfirmasi_password').classList.remove('is-invalid');
        document.getElementById('konfirmasi_password').classList.add('is-valid');
    } else {
        matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle me-1"></i>Password tidak cocok</small>';
        document.getElementById('konfirmasi_password').classList.remove('is-valid');
        document.getElementById('konfirmasi_password').classList.add('is-invalid');
    }
}

// ========== FORM UBAH NAMA ==========
document.getElementById('formUbahNama')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('btnUbahNama');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
    btn.disabled = true;
    
    const formData = new FormData(this);
    formData.append('ajax_update_profil', '1');
    
    fetch('profil.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        if (data.success) {
            showAlert('success', data.message);
            showConfirmModal('Profil berhasil diperbarui');
            
            // Update avatar
            const avatar = document.querySelector('.profile-avatar');
            if (avatar) {
                avatar.textContent = document.getElementById('nama_admin').value.charAt(0).toUpperCase();
            }
            
            // Update heading
            const namaProfil = document.querySelector('.profile-body h4');
            if (namaProfil) {
                namaProfil.textContent = document.getElementById('nama_admin').value;
            }
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalText;
        btn.disabled = false;
        showAlert('danger', 'Terjadi kesalahan saat menyimpan data');
    });
});

// ========== FORM UBAH PASSWORD ==========
document.getElementById('formUbahPassword')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validasi
    const passwordLama = document.getElementById('password_lama').value.trim();
    const passwordBaru = document.getElementById('password_baru').value;
    const konfirmasi = document.getElementById('konfirmasi_password').value;
    
    if (!passwordLama) {
        showAlert('warning', 'Password lama harus diisi');
        document.getElementById('password_lama').focus();
        return;
    }
    
    if (passwordBaru.length < 6) {
        showAlert('warning', 'Password baru minimal 6 karakter');
        document.getElementById('password_baru').focus();
        return;
    }
    
    if (passwordBaru !== konfirmasi) {
        showAlert('danger', 'Konfirmasi password tidak cocok');
        document.getElementById('konfirmasi_password').focus();
        return;
    }
    
    const btn = document.getElementById('btnUbahPassword');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengubah...';
    btn.disabled = true;
    
    const formData = new FormData(this);
    formData.append('ajax_update_password', '1');
    
    fetch('profil.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        if (data.success) {
            showAlert('success', data.message);
            showConfirmModal('Password berhasil diubah');
            
            // Reset form
            document.getElementById('formUbahPassword').reset();
            document.getElementById('passwordMatch').innerHTML = '';
            document.getElementById('passwordStrength').classList.remove('strength-weak', 'strength-medium', 'strength-strong');
            
            // Reset strength meter
            document.querySelector('.password-strength small').textContent = 'Kekuatan password';
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalText;
        btn.disabled = false;
        showAlert('danger', 'Terjadi kesalahan saat mengubah password');
    });
});

// ========== EVENT LISTENER PASSWORD ==========
document.getElementById('password_baru')?.addEventListener('input', function() {
    checkPasswordStrength(this.value);
    checkPasswordMatch();
});

document.getElementById('konfirmasi_password')?.addEventListener('input', checkPasswordMatch);

// ========== TOMBOL REFRESH ==========
document.getElementById('btnRefresh')?.addEventListener('click', function() {
    location.reload();
});

// ========== PREVENT DEFAULT ENTER PADA FORM ==========
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
        }
    });
});

// Expose functions to global scope
window.togglePassword = togglePassword;
window.closeAlert = closeAlert;
</script>

<?php
$content = ob_get_clean();
include '../includes/base.php';
?>