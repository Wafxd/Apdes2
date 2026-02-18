<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit();
}

// Include file fungsi - PATH YANG BENAR
include "db/koneksi.php";
include "db/funct.php";

// ==================== AJAX HANDLER - HARUS DI ATAS SEBELUM OUTPUT HTML ====================

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
    // Validasi field wajib
    if (empty($_POST["nik"]) || empty($_POST["nama_penduduk"]) || empty($_POST["tanggal_lahir"])) {
        $_SESSION['error_message'] = "NIK, Nama, dan Tanggal Lahir wajib diisi!";
        header("Location: penduduk.php");
        exit();
    }
    
    $result = add_penduduk($_POST);
    
    if ($result > 0) {
        $_SESSION['success_message'] = "Data penduduk berhasil ditambahkan!";
    } elseif ($result == -1) {
        $_SESSION['error_message'] = "NIK sudah terdaftar!";
    } elseif ($result == -4) {
        $_SESSION['error_message'] = "NIK harus 16 digit!";
    } else {
        $_SESSION['error_message'] = "Gagal menambahkan data penduduk!";
    }
    
    header("Location: penduduk.php");
    exit();
}

// ==================== PROSES FORM EDIT PENDUDUK ====================
if (isset($_POST["submit_edit"])) {
    // Validasi field wajib
    if (empty($_POST["nik"]) || empty($_POST["nama_penduduk"]) || empty($_POST["tanggal_lahir"])) {
        $_SESSION['error_message'] = "NIK, Nama, dan Tanggal Lahir wajib diisi!";
        header("Location: penduduk.php");
        exit();
    }
    
    $result = edit_penduduk($_POST);
    
    if ($result >= 0) {
        $_SESSION['success_message'] = "Data penduduk berhasil diupdate!";
    } elseif ($result == -4) {
        $_SESSION['error_message'] = "NIK harus 16 digit!";
    } else {
        $_SESSION['error_message'] = "Gagal mengupdate data penduduk!";
    }
    
    header("Location: penduduk.php");
    exit();
}

// ==================== QUERY DATA PENDUDUK ====================
// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "";
if (!empty($search)) {
    $where = "WHERE nik LIKE '%$search%' OR nama_penduduk LIKE '%$search%' OR alamat LIKE '%$search%'";
}

// Query total
$query_total = "SELECT COUNT(*) as total FROM penduduk $where";
$result_total = mysqli_query($conn, $query_total);
$total_data = mysqli_fetch_assoc($result_total)['total'];
$total_pages = ceil($total_data / $limit);

// Query data dengan join untuk cek penggunaan
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM kartu_keluarga WHERE nik_kepala = p.nik) as is_kepala,
          (SELECT COUNT(*) FROM anggota_keluarga WHERE nik = p.nik) as is_anggota
          FROM penduduk p 
          $where 
          ORDER BY p.nama_penduduk ASC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// ==================== TEMPLATE & CONTENT ====================
$pageTitle = "Data Penduduk";
ob_start();
?>

<!-- Alert Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<!-- Card Statistik -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Penduduk</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_data; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Data Penduduk -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Penduduk</h6>
        <div>
            <button type="button" class="btn btn-primary btn-sm" id="btnTambahPenduduk">
                <i class="fas fa-plus me-1"></i> Tambah Penduduk
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Search Form -->
        <form method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Cari NIK, Nama, atau Alamat..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i> Cari
                </button>
                <?php if (!empty($search)): ?>
                <a href="penduduk.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th width="50">No</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Tempat, Tgl Lahir</th>
                        <th>JK</th>
                        <th>Alamat</th>
                        <th>Status</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['nik']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_penduduk']); ?></td>
                            <td><?php echo htmlspecialchars($row['tempat_lahir']) . ', ' . date('d-m-Y', strtotime($row['tanggal_lahir'])); ?></td>
                            <td><?php echo $row['jenis_kelamin'] == 'LAKI-LAKI' ? 'L' : 'P'; ?></td>
                            <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                            <td>
                                <?php if ($row['is_kepala'] > 0): ?>
                                    <span class="badge bg-warning text-dark">Kepala KK</span>
                                <?php endif; ?>
                                <?php if ($row['is_anggota'] > 0): ?>
                                    <span class="badge bg-info">Anggota KK</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-info btn-view" 
                                            data-nik="<?php echo htmlspecialchars($row['nik']); ?>"
                                            title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning btn-edit" 
                                            data-nik="<?php echo htmlspecialchars($row['nik']); ?>"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($row['is_kepala'] > 0 || $row['is_anggota'] > 0): ?>
                                        <button class="btn btn-sm btn-secondary btn-info-penggunaan" 
                                                data-nik="<?php echo htmlspecialchars($row['nik']); ?>"
                                                data-nama="<?php echo htmlspecialchars($row['nama_penduduk']); ?>"
                                                data-is-kepala="<?php echo $row['is_kepala'] > 0 ? 'true' : 'false'; ?>"
                                                data-is-anggota="<?php echo $row['is_anggota'] > 0 ? 'true' : 'false'; ?>"
                                                title="Info Penggunaan">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-danger btn-hapus" 
                                                data-nik="<?php echo htmlspecialchars($row['nik']); ?>"
                                                title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- ========== MODAL TAMBAH PENDUDUK ========== -->
<div class="modal fade" id="tambahPendudukModal" tabindex="-1" aria-labelledby="tambahPendudukModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title" id="tambahPendudukModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Tambah Data Penduduk
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalTambah()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="penduduk.php" id="formTambahPenduduk">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="container-fluid p-3">
                        <!-- Table layout tanpa border yang terlihat -->
                        <table class="table table-borderless" style="width: 100%;">
                            <!-- Baris 1: NIK dan Nama Lengkap -->
                            <tr>
                                <td style="width: 15%; vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">NIK <span class="text-danger">*</span></label>
                                </td>
                                <td style="width: 35%;">
                                    <input type="text" class="form-control" name="nik" id="tambah_nik" required maxlength="16" pattern="[0-9]{16}" placeholder="16 digit angka" oninput="checkNIKTambah(this.value)">
                                    <div id="tambah_nik_feedback" class="mt-1 small"></div>
                                    <small class="text-muted">Harus 16 digit angka</small>
                                </td>
                                <td style="width: 15%; vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Nama Lengkap <span class="text-danger">*</span></label>
                                </td>
                                <td style="width: 35%;">
                                    <input type="text" class="form-control" name="nama_penduduk" id="tambah_nama" required placeholder="Masukkan nama lengkap">
                                </td>
                            </tr>
                            
                            <!-- Baris 2: Nama Ayah dan Nama Ibu -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Nama Ayah</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nama_ayah" id="tambah_nama_ayah" placeholder="Masukkan nama ayah">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Nama Ibu</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nama_ibu" id="tambah_nama_ibu" placeholder="Masukkan nama ibu">
                                </td>
                            </tr>
                            
                            <!-- Baris 3: Tempat Lahir, Tanggal Lahir, Jenis Kelamin -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Tempat Lahir</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="tempat_lahir" id="tambah_tempat_lahir" placeholder="Contoh: Jakarta">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Tanggal Lahir <span class="text-danger">*</span></label>
                                </td>
                                <td>
                                    <input type="date" class="form-control" name="tanggal_lahir" id="tambah_tanggal_lahir" required max="<?php echo date('Y-m-d'); ?>">
                                </td>
                            </tr>
                            
                            <!-- Baris 4: Jenis Kelamin dan Agama -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Jenis Kelamin</label>
                                </td>
                                <td>
                                    <select class="form-select" name="jenis_kelamin" id="tambah_jenis_kelamin">
                                        <option value="LAKI-LAKI">LAKI-LAKI</option>
                                        <option value="PEREMPUAN">PEREMPUAN</option>
                                    </select>
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Agama</label>
                                </td>
                                <td>
                                    <select class="form-select" name="agama" id="tambah_agama">
                                        <option value="ISLAM">ISLAM</option>
                                        <option value="KRISTEN">KRISTEN</option>
                                        <option value="KATOLIK">KATOLIK</option>
                                        <option value="HINDU">HINDU</option>
                                        <option value="BUDDHA">BUDDHA</option>
                                        <option value="KONGHUCU">KONGHUCU</option>
                                    </select>
                                </td>
                            </tr>
                            
                            <!-- Baris 5: Pendidikan dan Pekerjaan -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Pendidikan</label>
                                </td>
                                <td>
                                    <select class="form-select" name="pendidikan" id="tambah_pendidikan">
                                        <option value="">Pilih Pendidikan</option>
                                        <option value="TIDAK SEKOLAH">TIDAK SEKOLAH</option>
                                        <option value="SD">SD</option>
                                        <option value="SMP">SMP</option>
                                        <option value="SMA">SMA</option>
                                        <option value="SMK">SMK</option>
                                        <option value="D1">D1</option>
                                        <option value="D2">D2</option>
                                        <option value="D3">D3</option>
                                        <option value="S1">S1</option>
                                        <option value="S2">S2</option>
                                        <option value="S3">S3</option>
                                    </select>
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Pekerjaan</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="pekerjaan" id="tambah_pekerjaan" placeholder="Contoh: PNS, Swasta, Petani">
                                </td>
                            </tr>
                            
                            <!-- Baris 6: Status Kawin dan RT/RW -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Status Kawin</label>
                                </td>
                                <td>
                                    <select class="form-select" name="status_kawin" id="tambah_status_kawin">
                                        <option value="Belum Kawin">Belum Kawin</option>
                                        <option value="Kawin">Kawin</option>
                                        <option value="Cerai Hidup">Cerai Hidup</option>
                                        <option value="Cerai Mati">Cerai Mati</option>
                                    </select>
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">RT/RW</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="rt_rw" id="tambah_rt_rw" value="001/002" placeholder="001/002">
                                </td>
                            </tr>
                            
                            <!-- Baris 7: Alamat (full width) -->
                            <tr>
                                <td style="vertical-align: top;">
                                    <label class="form-label fw-bold mb-0">Alamat</label>
                                </td>
                                <td colspan="3">
                                    <textarea class="form-control" name="alamat" id="tambah_alamat" rows="2" placeholder="Masukkan alamat lengkap"></textarea>
                                </td>
                            </tr>
                            
                            <!-- Baris 8: Kelurahan/Desa, Kecamatan, Kabupaten/Kota -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kelurahan/Desa</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kel_des" id="tambah_kel_des" value="Sukolilo Timur">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kecamatan</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kecamatan" id="tambah_kecamatan" value="Sukolilo">
                                </td>
                            </tr>
                            
                            <!-- Baris 9: Kabupaten/Kota, Provinsi, Kode Pos -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kabupaten/Kota</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kabupaten_kota" id="tambah_kabupaten_kota" value="Bangkalan">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Provinsi</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="provinsi" id="tambah_provinsi" value="Jawa Timur">
                                </td>
                            </tr>
                            
                            <!-- Baris 10: Kode Pos (separuh) -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kode Pos</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kodepos" id="tambah_kodepos" value="69162" maxlength="5" placeholder="5 digit">
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                        
                        <div class="text-muted small mt-3 p-2 bg-light rounded">
                            <span class="text-danger">*</span> Field wajib diisi
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-lg" onclick="closeModalTambah()">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" name="submit_tambah" class="btn btn-primary btn-lg" id="submitTambahBtn">
                        <i class="fas fa-save me-2"></i>Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Penduduk -->
<div class="modal fade" id="editPendudukModal" tabindex="-1" aria-labelledby="editPendudukModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning py-3">
                <h5 class="modal-title" id="editPendudukModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Data Penduduk
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalEdit()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="penduduk.php" id="formEditPenduduk">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="container-fluid p-3">
                        <!-- Table layout tanpa border yang terlihat -->
                        <table class="table table-borderless" style="width: 100%;">
                            <!-- Baris 1: NIK dan Nama Lengkap -->
                            <tr>
                                <td style="width: 15%; vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">NIK <span class="text-danger">*</span></label>
                                </td>
                                <td style="width: 35%;">
                                    <input type="text" class="form-control bg-light" name="nik" id="edit_nik" readonly>
                                    <small class="text-muted">NIK tidak dapat diubah</small>
                                </td>
                                <td style="width: 15%; vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Nama Lengkap <span class="text-danger">*</span></label>
                                </td>
                                <td style="width: 35%;">
                                    <input type="text" class="form-control" name="nama_penduduk" id="edit_nama_penduduk" required>
                                </td>
                            </tr>
                            
                            <!-- Baris 2: Nama Ayah dan Nama Ibu -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Nama Ayah</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nama_ayah" id="edit_nama_ayah">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Nama Ibu</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="nama_ibu" id="edit_nama_ibu">
                                </td>
                            </tr>
                            
                            <!-- Baris 3: Tempat Lahir dan Tanggal Lahir -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Tempat Lahir</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="tempat_lahir" id="edit_tempat_lahir">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Tanggal Lahir <span class="text-danger">*</span></label>
                                </td>
                                <td>
                                    <input type="date" class="form-control" name="tanggal_lahir" id="edit_tanggal_lahir" required>
                                </td>
                            </tr>
                            
                            <!-- Baris 4: Jenis Kelamin dan Agama -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Jenis Kelamin</label>
                                </td>
                                <td>
                                    <select class="form-select" name="jenis_kelamin" id="edit_jenis_kelamin">
                                        <option value="LAKI-LAKI">LAKI-LAKI</option>
                                        <option value="PEREMPUAN">PEREMPUAN</option>
                                    </select>
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Agama</label>
                                </td>
                                <td>
                                    <select class="form-select" name="agama" id="edit_agama">
                                        <option value="ISLAM">ISLAM</option>
                                        <option value="KRISTEN">KRISTEN</option>
                                        <option value="KATOLIK">KATOLIK</option>
                                        <option value="HINDU">HINDU</option>
                                        <option value="BUDDHA">BUDDHA</option>
                                        <option value="KONGHUCU">KONGHUCU</option>
                                    </select>
                                </td>
                            </tr>
                            
                            <!-- Baris 5: Pendidikan dan Pekerjaan -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Pendidikan</label>
                                </td>
                                <td>
                                    <select class="form-select" name="pendidikan" id="edit_pendidikan">
                                        <option value="">Pilih Pendidikan</option>
                                        <option value="TIDAK SEKOLAH">TIDAK SEKOLAH</option>
                                        <option value="SD">SD</option>
                                        <option value="SMP">SMP</option>
                                        <option value="SMA">SMA</option>
                                        <option value="SMK">SMK</option>
                                        <option value="D1">D1</option>
                                        <option value="D2">D2</option>
                                        <option value="D3">D3</option>
                                        <option value="S1">S1</option>
                                        <option value="S2">S2</option>
                                        <option value="S3">S3</option>
                                    </select>
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Pekerjaan</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="pekerjaan" id="edit_pekerjaan">
                                </td>
                            </tr>
                            
                            <!-- Baris 6: Status Kawin dan RT/RW -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Status Kawin</label>
                                </td>
                                <td>
                                    <select class="form-select" name="status_kawin" id="edit_status_kawin">
                                        <option value="Belum Kawin">Belum Kawin</option>
                                        <option value="Kawin">Kawin</option>
                                        <option value="Cerai Hidup">Cerai Hidup</option>
                                        <option value="Cerai Mati">Cerai Mati</option>
                                    </select>
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">RT/RW</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="rt_rw" id="edit_rt_rw">
                                </td>
                            </tr>
                            
                            <!-- Baris 7: Alamat (full width) -->
                            <tr>
                                <td style="vertical-align: top;">
                                    <label class="form-label fw-bold mb-0">Alamat</label>
                                </td>
                                <td colspan="3">
                                    <textarea class="form-control" name="alamat" id="edit_alamat" rows="2"></textarea>
                                </td>
                            </tr>
                            
                            <!-- Baris 8: Kelurahan/Desa, Kecamatan, Kabupaten/Kota -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kelurahan/Desa</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kel_des" id="edit_kel_des">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kecamatan</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kecamatan" id="edit_kecamatan">
                                </td>
                            </tr>
                            
                            <!-- Baris 9: Kabupaten/Kota, Provinsi, Kode Pos -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kabupaten/Kota</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kabupaten_kota" id="edit_kabupaten_kota">
                                </td>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Provinsi</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="provinsi" id="edit_provinsi">
                                </td>
                            </tr>
                            
                            <!-- Baris 10: Kode Pos (separuh) -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <label class="form-label fw-bold mb-0">Kode Pos</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="kodepos" id="edit_kodepos" maxlength="5">
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-lg" onclick="closeModalEdit()">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" name="submit_edit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Update Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal View Detail Penduduk -->
<div class="modal fade" id="viewPendudukModal" tabindex="-1" aria-labelledby="viewPendudukModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-3">
                <h5 class="modal-title" id="viewPendudukModalLabel">
                    <i class="fas fa-user me-2"></i>Detail Data Penduduk
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalView()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewPendudukContent" style="min-height: 300px;">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 fs-5">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-lg" onclick="closeModalView()">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="confirmHapusModal" tabindex="-1" aria-labelledby="confirmHapusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title" id="confirmHapusModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalHapus()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-4x text-danger"></i>
                </div>
                <p class="fs-5 text-center">Apakah Anda yakin ingin menghapus data penduduk ini?</p>
                <div id="confirmHapusInfo" class="alert alert-warning py-2">
                    <small>Memuat data...</small>
                </div>
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Data yang dihapus tidak dapat dikembalikan!</strong>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" onclick="closeModalHapus()">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmHapus">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Info Penggunaan -->
<div class="modal fade" id="infoPenggunaanModal" tabindex="-1" aria-labelledby="infoPenggunaanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white py-3">
                <h5 class="modal-title" id="infoPenggunaanModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Informasi Penggunaan Data
                </h5>
                <button type="button" class="btn btn-sm btn-light" onclick="closeModalInfo()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body py-4" id="infoPenggunaanContent">
                <div class="text-center py-3">
                    <div class="spinner-border text-secondary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat informasi...</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" onclick="closeModalInfo()">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles untuk modal dan form */
.border-left-primary {
    border-left: 4px solid #4e73df !important;
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

/* Custom scrollbar untuk modal body */
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

.form-control.form-control-lg {
    font-size: 1rem;
    padding: 0.75rem 1rem;
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

/* Styling untuk info penggunaan */
.usage-info {
    padding: 15px;
    border-radius: 8px;
    margin: 10px 0;
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}

.usage-info i {
    color: #856404;
}

/* Styling untuk tombol close */
.btn-close-white {
    filter: brightness(0) invert(1);
    opacity: 0.8;
}

.btn-close-white:hover {
    opacity: 1;
}

/* Styling untuk detail view */
.detail-row {
    border-bottom: 1px solid #e3e6f0;
    padding: 12px 0;
    transition: background-color 0.2s;
}

.detail-row:hover {
    background-color: #f8f9fc;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #5a5c69;
    font-size: 0.9rem;
}

.detail-value {
    color: #3a3b45;
    font-size: 1rem;
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

/* Responsive adjustments */
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

<script>
// ========== VARIABEL GLOBAL ==========
let modalTambahInstance = null;
let modalEditInstance = null;
let modalViewInstance = null;
let modalHapusInstance = null;
let modalInfoInstance = null;

// ========== FUNGSI UTILITAS ==========
function formatTanggal(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
}

function hitungUmur(tanggalLahir) {
    if (!tanggalLahir) return '-';
    const birthDate = new Date(tanggalLahir);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age + ' tahun';
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
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
    }
    
    // Auto dismiss after 3 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert-success');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 3000);
}

function showErrorMessage(message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
    }
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert-danger');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

// ========== FUNGSI UNTUK MENUTUP MODAL ==========
function closeModalTambah() {
    if (modalTambahInstance) {
        modalTambahInstance.hide();
    }
    // Bersihkan backdrop jika masih ada
    setTimeout(cleanupBackdrop, 100);
}

function closeModalEdit() {
    if (modalEditInstance) {
        modalEditInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeModalView() {
    if (modalViewInstance) {
        modalViewInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeModalHapus() {
    if (modalHapusInstance) {
        modalHapusInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function closeModalInfo() {
    if (modalInfoInstance) {
        modalInfoInstance.hide();
    }
    setTimeout(cleanupBackdrop, 100);
}

function cleanupBackdrop() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

// ========== FUNGSI VALIDASI NIK ==========
function checkNIKTambah(nik) {
    const input = document.getElementById('tambah_nik');
    const feedback = document.getElementById('tambah_nik_feedback');
    const submitBtn = document.getElementById('submitTambahBtn');
    
    if (!input || !feedback) return;
    
    // Reset classes
    input.classList.remove('is-invalid', 'is-valid');
    feedback.innerHTML = '';
    feedback.className = 'mt-1 small';
    
    if (!nik) {
        feedback.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Masukkan NIK 16 digit</span>';
        return;
    }
    
    if (!/^\d+$/.test(nik)) {
        input.classList.add('is-invalid');
        feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>NIK harus angka</span>';
        feedback.classList.add('invalid-feedback');
        return;
    }
    
    if (nik.length !== 16) {
        input.classList.add('is-invalid');
        feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>NIK harus 16 digit (sekarang ' + nik.length + ' digit)</span>';
        feedback.classList.add('invalid-feedback');
        return;
    }
    
    if (nik.length === 16 && /^\d+$/.test(nik)) {
        feedback.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Memeriksa NIK...</span>';
        
        fetch('penduduk.php?check_nik=' + encodeURIComponent(nik))
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    input.classList.add('is-invalid');
                    feedback.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>NIK sudah terdaftar</span>';
                    feedback.classList.add('invalid-feedback');
                    if (submitBtn) submitBtn.disabled = true;
                } else {
                    input.classList.add('is-valid');
                    feedback.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>NIK tersedia</span>';
                    feedback.classList.add('valid-feedback');
                    if (submitBtn) submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                feedback.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Gagal memeriksa NIK</span>';
                if (submitBtn) submitBtn.disabled = false;
            });
    }
}

function validateFormTambah() {
    const nik = document.getElementById('tambah_nik')?.value;
    const nama = document.getElementById('tambah_nama')?.value;
    const tanggal = document.getElementById('tambah_tanggal_lahir')?.value;
    
    if (!nik || nik.length !== 16) {
        alert('NIK harus 16 digit!');
        document.getElementById('tambah_nik')?.focus();
        return false;
    }
    
    if (!nama) {
        alert('Nama harus diisi!');
        document.getElementById('tambah_nama')?.focus();
        return false;
    }
    
    if (!tanggal) {
        alert('Tanggal lahir harus diisi!');
        document.getElementById('tambah_tanggal_lahir')?.focus();
        return false;
    }
    
    return true;
}

// ========== FUNGSI RESET FORM ==========
function resetTambahForm() {
    const form = document.getElementById('formTambahPenduduk');
    if (form) {
        form.reset();
        
        // Reset validasi
        const nikInput = document.getElementById('tambah_nik');
        const nikFeedback = document.getElementById('tambah_nik_feedback');
        if (nikInput) {
            nikInput.classList.remove('is-invalid', 'is-valid');
        }
        if (nikFeedback) {
            nikFeedback.innerHTML = '';
            nikFeedback.className = 'mt-1 small';
        }
        
        // Set default values
        const rt_rw = document.getElementById('tambah_rt_rw');
        const kel_des = document.getElementById('tambah_kel_des');
        const kecamatan = document.getElementById('tambah_kecamatan');
        const kabupaten = document.getElementById('tambah_kabupaten_kota');
        const provinsi = document.getElementById('tambah_provinsi');
        const kodepos = document.getElementById('tambah_kodepos');
        
        if (rt_rw) rt_rw.value = '001/002';
        if (kel_des) kel_des.value = 'Sukolilo Timur';
        if (kecamatan) kecamatan.value = 'Sukolilo';
        if (kabupaten) kabupaten.value = 'Bangkalan';
        if (provinsi) provinsi.value = 'Jawa Timur';
        if (kodepos) kodepos.value = '69162';
    }
}

// ========== FUNGSI UNTUK MODAL VIEW ==========
function loadViewData(nik) {
    const contentDiv = document.getElementById('viewPendudukContent');
    if (!contentDiv) return;
    
    showLoading('viewPendudukContent', 'Mengambil data penduduk...');
    
    fetch('penduduk.php?ajax_get_penduduk=' + encodeURIComponent(nik))
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;
                
                // Format data
                const tglLahir = formatTanggal(data.tanggal_lahir);
                const umur = hitungUmur(data.tanggal_lahir);
                
                // Tampilkan detail
                contentDiv.innerHTML = `
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12 mb-4 text-center">
                                <div class="position-relative d-inline-block">
                                    <i class="fas fa-user-circle fa-6x text-info"></i>
                                    ${data.is_kepala ? '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">Kepala KK</span>' : ''}
                                    ${data.is_anggota ? '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">Anggota KK</span>' : ''}
                                </div>
                                <h4 class="mb-0 mt-3">${data.nama_penduduk || '-'}</h4>
                                <p class="text-muted">${data.nik || '-'}</p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-address-card me-2"></i>Data Pribadi</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td width="40%" class="text-muted">Tempat Lahir</td>
                                                <td>: <strong>${data.tempat_lahir || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Tanggal Lahir</td>
                                                <td>: <strong>${tglLahir}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Umur</td>
                                                <td>: <strong>${umur}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Jenis Kelamin</td>
                                                <td>: <strong>${data.jenis_kelamin || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Agama</td>
                                                <td>: <strong>${data.agama || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Status Kawin</td>
                                                <td>: <strong>${data.status_kawin || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Pendidikan</td>
                                                <td>: <strong>${data.pendidikan || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Pekerjaan</td>
                                                <td>: <strong>${data.pekerjaan || '-'}</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Data Orang Tua</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td width="40%" class="text-muted">Nama Ayah</td>
                                                <td>: <strong>${data.nama_ayah || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Nama Ibu</td>
                                                <td>: <strong>${data.nama_ibu || '-'}</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Data Alamat</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td width="40%" class="text-muted">Alamat</td>
                                                <td>: <strong>${data.alamat || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">RT/RW</td>
                                                <td>: <strong>${data.rt_rw || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Kelurahan/Desa</td>
                                                <td>: <strong>${data.kel_des || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Kecamatan</td>
                                                <td>: <strong>${data.kecamatan || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Kabupaten/Kota</td>
                                                <td>: <strong>${data.kabupaten_kota || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Provinsi</td>
                                                <td>: <strong>${data.provinsi || '-'}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Kode Pos</td>
                                                <td>: <strong>${data.kodepos || '-'}</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                showError('viewPendudukContent', 'Gagal mengambil data: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('viewPendudukContent', 'Terjadi kesalahan saat mengambil data');
        });
}

// ========== FUNGSI UNTUK MODAL EDIT ==========
function loadEditData(nik, button) {
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    fetch('penduduk.php?ajax_get_penduduk=' + encodeURIComponent(nik))
        .then(response => response.json())
        .then(result => {
            button.innerHTML = originalHTML;
            button.disabled = false;
            
            if (result.success) {
                const data = result.data;
                
                // Isi form edit
                const fields = {
                    'edit_nik': data.nik,
                    'edit_nama_penduduk': data.nama_penduduk,
                    'edit_nama_ayah': data.nama_ayah,
                    'edit_nama_ibu': data.nama_ibu,
                    'edit_tempat_lahir': data.tempat_lahir,
                    'edit_tanggal_lahir': data.tanggal_lahir,
                    'edit_jenis_kelamin': data.jenis_kelamin || 'LAKI-LAKI',
                    'edit_agama': data.agama || 'ISLAM',
                    'edit_pendidikan': data.pendidikan,
                    'edit_pekerjaan': data.pekerjaan,
                    'edit_status_kawin': data.status_kawin || 'Belum Kawin',
                    'edit_alamat': data.alamat,
                    'edit_rt_rw': data.rt_rw || '001/002',
                    'edit_kel_des': data.kel_des || 'Sukolilo Timur',
                    'edit_kecamatan': data.kecamatan || 'Sukolilo',
                    'edit_kabupaten_kota': data.kabupaten_kota || 'Bangkalan',
                    'edit_provinsi': data.provinsi || 'Jawa Timur',
                    'edit_kodepos': data.kodepos || '69162'
                };
                
                Object.keys(fields).forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.value = fields[id] || '';
                    }
                });
                
                // Tampilkan modal
                if (modalEditInstance) {
                    modalEditInstance.show();
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

// ========== FUNGSI UNTUK INFO PENGGUNAAN ==========
function showInfoPenggunaan(nik, nama, isKepala, isAnggota) {
    const contentDiv = document.getElementById('infoPenggunaanContent');
    if (!contentDiv) return;
    
    let content = `
        <div class="text-center mb-4">
            <i class="fas fa-info-circle text-secondary" style="font-size: 48px;"></i>
            <h5 class="mt-3">Informasi Penggunaan Data</h5>
        </div>
        <table class="table table-sm table-borderless">
            <tr>
                <td width="40%" class="text-muted">NIK</td>
                <td><strong>${nik}</strong></td>
            </tr>
            <tr>
                <td class="text-muted">Nama</td>
                <td><strong>${nama}</strong></td>
            </tr>
        </table>
        <hr>
    `;
    
    if (isKepala) {
        content += `
            <div class="usage-info">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Status: Kepala Keluarga</strong><br>
                <small>Data ini terdaftar sebagai Kepala Keluarga dan tidak dapat dihapus.</small>
            </div>
        `;
    }
    
    if (isAnggota) {
        content += `
            <div class="usage-info">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Status: Anggota Keluarga</strong><br>
                <small>Data ini terdaftar sebagai Anggota Keluarga dan tidak dapat dihapus.</small>
            </div>
        `;
    }
    
    if (!isKepala && !isAnggota) {
        content += `
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Status: Mandiri</strong><br>
                <small>Data ini tidak terdaftar dalam keluarga manapun dan dapat dihapus.</small>
            </div>
        `;
    }
    
    content += `
        <div class="alert alert-info mt-3">
            <i class="fas fa-lightbulb me-2"></i>
            <small>Untuk menghapus data yang terdaftar dalam keluarga, hapus terlebih dahulu dari data Kartu Keluarga.</small>
        </div>
    `;
    
    contentDiv.innerHTML = content;
    
    if (modalInfoInstance) {
        modalInfoInstance.show();
    }
}

// ========== FUNGSI UNTUK HAPUS DATA ==========
function hapusData(nik) {
    const btn = document.getElementById('btnConfirmHapus');
    if (!btn) return;
    
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menghapus...';
    btn.disabled = true;
    
    fetch('penduduk.php?ajax_hapus=' + encodeURIComponent(nik))
        .then(response => response.json())
        .then(data => {
            if (modalHapusInstance) {
                modalHapusInstance.hide();
            }
            
            if (data.success) {
                showSuccess('Data penduduk berhasil dihapus');
                setTimeout(() => window.location.reload(), 1000);
            } else if (data.error_code === -2) {
                showErrorMessage('Data ini adalah Kepala Keluarga dan tidak dapat dihapus.');
            } else if (data.error_code === -3) {
                showErrorMessage('Data ini adalah Anggota Keluarga. Hapus dari data keluarga terlebih dahulu.');
            } else {
                showErrorMessage('Gagal menghapus data: ' + (data.message || 'Unknown error'));
            }
            
            btn.innerHTML = originalText;
            btn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            if (modalHapusInstance) {
                modalHapusInstance.hide();
            }
            showErrorMessage('Terjadi kesalahan saat menghapus data');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}

// ========== INISIALISASI SAAT DOKUMEN SIAP ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Loaded - Initializing...');
    
    // ========== INISIALISASI MODAL ==========
    const tambahModalEl = document.getElementById('tambahPendudukModal');
    const editModalEl = document.getElementById('editPendudukModal');
    const viewModalEl = document.getElementById('viewPendudukModal');
    const hapusModalEl = document.getElementById('confirmHapusModal');
    const infoModalEl = document.getElementById('infoPenggunaanModal');
    
    if (tambahModalEl) modalTambahInstance = new bootstrap.Modal(tambahModalEl);
    if (editModalEl) modalEditInstance = new bootstrap.Modal(editModalEl);
    if (viewModalEl) modalViewInstance = new bootstrap.Modal(viewModalEl);
    if (hapusModalEl) modalHapusInstance = new bootstrap.Modal(hapusModalEl);
    if (infoModalEl) modalInfoInstance = new bootstrap.Modal(infoModalEl);
    
    // ========== TOMBOL TAMBAH PENDUDUK ==========
    const btnTambah = document.getElementById('btnTambahPenduduk');
    if (btnTambah) {
        btnTambah.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Tombol tambah diklik');
            
            resetTambahForm();
            
            if (modalTambahInstance) {
                modalTambahInstance.show();
            }
        });
    }
    
    // ========== TOMBOL VIEW ==========
    document.querySelectorAll('.btn-view').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const nik = this.getAttribute('data-nik');
            console.log('View data NIK:', nik);
            
            if (modalViewInstance) {
                modalViewInstance.show();
            }
            
            loadViewData(nik);
        });
    });
    
    // ========== TOMBOL EDIT ==========
    document.querySelectorAll('.btn-edit').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const nik = this.getAttribute('data-nik');
            console.log('Edit data NIK:', nik);
            
            loadEditData(nik, this);
        });
    });
    
    // ========== TOMBOL INFO PENGGUNAAN ==========
    document.querySelectorAll('.btn-info-penggunaan').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const nik = this.getAttribute('data-nik');
            const nama = this.getAttribute('data-nama');
            const isKepala = this.getAttribute('data-is-kepala') === 'true';
            const isAnggota = this.getAttribute('data-is-anggota') === 'true';
            
            showInfoPenggunaan(nik, nama, isKepala, isAnggota);
        });
    });
    
    // ========== TOMBOL HAPUS ==========
    document.querySelectorAll('.btn-hapus').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const nik = this.getAttribute('data-nik');
            
            const infoDiv = document.getElementById('confirmHapusInfo');
            if (infoDiv) {
                infoDiv.innerHTML = `
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td width="40%" class="text-muted">NIK</td>
                            <td><strong>${nik}</strong></td>
                        </tr>
                    </table>
                    <small class="text-muted">Pastikan data ini tidak sedang digunakan</small>
                `;
            }
            
            const confirmBtn = document.getElementById('btnConfirmHapus');
            if (confirmBtn) {
                confirmBtn.setAttribute('data-nik', nik);
            }
            
            if (modalHapusInstance) {
                modalHapusInstance.show();
            }
        });
    });
    
    // ========== TOMBOL KONFIRMASI HAPUS ==========
    const btnConfirmHapus = document.getElementById('btnConfirmHapus');
    if (btnConfirmHapus) {
        btnConfirmHapus.addEventListener('click', function(e) {
            e.preventDefault();
            const nik = this.getAttribute('data-nik');
            if (nik) {
                hapusData(nik);
            }
        });
    }
    
    // ========== VALIDASI FORM TAMBAH ==========
    const formTambah = document.getElementById('formTambahPenduduk');
    if (formTambah) {
        formTambah.addEventListener('submit', function(e) {
            if (!validateFormTambah()) {
                e.preventDefault();
            }
        });
    }
    
    // ========== EVENT UNTUK MODAL ==========
    // Cleanup backdrop saat modal ditutup
    document.querySelectorAll('.modal').forEach(function(modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function() {
            cleanupBackdrop();
            
            // Reset form tambah jika modal tambah ditutup
            if (this.id === 'tambahPendudukModal') {
                resetTambahForm();
            }
        });
    });
    
    // ========== AUTO DISMISS ALERT ==========
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            try {
                const bsAlert = bootstrap.Alert.getInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                } else {
                    const newAlert = new bootstrap.Alert(alert);
                    newAlert.close();
                }
            } catch (e) {
                console.warn('Error closing alert:', e);
            }
        });
    }, 5000);
    
    // ========== SEARCH FORM ENTER KEY ==========
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });
    }
    
    // ========== INITIAL CHECK FOR NIK INPUT ==========
    const tambahNik = document.getElementById('tambah_nik');
    if (tambahNik) {
        tambahNik.addEventListener('input', function() {
            checkNIKTambah(this.value);
        });
    }
    
    console.log('Initialization complete!');
});

// ========== FUNGSI GLOBAL UNTUK DIPANGGIL DARI HTML ==========
window.checkNIKTambah = checkNIKTambah;
window.validateFormTambah = validateFormTambah;
window.resetTambahForm = resetTambahForm;
window.closeModalTambah = closeModalTambah;
window.closeModalEdit = closeModalEdit;
window.closeModalView = closeModalView;
window.closeModalHapus = closeModalHapus;
window.closeModalInfo = closeModalInfo;
</script>

<?php
$content = ob_get_clean();
include 'template1/base.php';
?>