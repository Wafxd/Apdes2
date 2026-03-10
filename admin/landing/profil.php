<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../../login.php");
    exit();
}

include "../../db/koneksi.php";

$pageTitle = "Kelola Profil Desa";

// ==================== FUNGSI UPLOAD GAMBAR ====================
function uploadGambar($file, $old_file = '') {
    $target_dir = "../../assets/images/"; 
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($file["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'File bukan gambar'];
    }
    
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($imageFileType, $allowed)) {
        return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & WEBP yang diizinkan'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        if (!empty($old_file) && file_exists($target_dir . $old_file)) {
            unlink($target_dir . $old_file);
        }
        return ['success' => true, 'file_name' => $file_name];
    } else {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }
}

// ==================== HANDLE CRUD OPERATIONS ====================

// TENTANG KAMI
if (isset($_POST['save_tentang'])) {
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $gambar = $_POST['existing_gambar'] ?? '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar'], $gambar);
        if ($upload['success']) {
            $gambar = $upload['file_name'];
        } else {
            $_SESSION['error_message'] = $upload['message'];
            header("Location: profil.php");
            exit();
        }
    }

    $check = mysqli_query($conn, "SELECT id FROM profil_tentang LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $query = "UPDATE profil_tentang SET deskripsi='$deskripsi', gambar='$gambar' WHERE id={$row['id']}";
    } else {
        $query = "INSERT INTO profil_tentang (deskripsi, gambar) VALUES ('$deskripsi', '$gambar')";
    }
    
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Tentang Kami berhasil disimpan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: profil.php");
    exit();
}

// VISI MISI
if (isset($_POST['save_visi_misi'])) {
    $visi = mysqli_real_escape_string($conn, $_POST['visi']);
    $misi = mysqli_real_escape_string($conn, $_POST['misi']);
    
    $check = mysqli_query($conn, "SELECT id FROM profil_visi_misi LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $query = "UPDATE profil_visi_misi SET visi='$visi', misi='$misi' WHERE id={$row['id']}";
    } else {
        $query = "INSERT INTO profil_visi_misi (visi, misi) VALUES ('$visi', '$misi')";
    }
    
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Visi Misi berhasil disimpan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: profil.php");
    exit();
}

// DUSUN (Update: Menambahkan Deskripsi, Menghapus RT/RW)
if (isset($_POST['add_dusun'])) {
    $nama_dusun = mysqli_real_escape_string($conn, $_POST['nama_dusun']);
    $kepala_dusun = mysqli_real_escape_string($conn, $_POST['kepala_dusun']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    $query = "INSERT INTO profil_dusun (nama_dusun, kepala_dusun, deskripsi) VALUES ('$nama_dusun', '$kepala_dusun', '$deskripsi')";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Data Dusun ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: profil.php");
    exit();
}

if (isset($_POST['edit_dusun'])) {
    $id = intval($_POST['id']);
    $nama_dusun = mysqli_real_escape_string($conn, $_POST['nama_dusun']);
    $kepala_dusun = mysqli_real_escape_string($conn, $_POST['kepala_dusun']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    $query = "UPDATE profil_dusun SET nama_dusun='$nama_dusun', kepala_dusun='$kepala_dusun', deskripsi='$deskripsi' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Data Dusun diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: profil.php");
    exit();
}

if (isset($_POST['delete_dusun'])) {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM profil_dusun WHERE id=$id");
    $_SESSION['success_message'] = "Data Dusun dihapus";
    header("Location: profil.php");
    exit();
}

// ==================== AMBIL DATA ====================
$tentang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profil_tentang LIMIT 1"));
$visimisi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profil_visi_misi LIMIT 1"));
$dusuns = mysqli_query($conn, "SELECT * FROM profil_dusun ORDER BY nama_dusun ASC");

// ==================== TEMPLATE ====================
ob_start();
?>

<style>
    :root { 
        --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%); 
    }
    
    /* Nav Pills (Sidebar Tab) */
    .nav-pills .nav-link { 
        color: #4e73df; 
        font-weight: 600; 
        border-radius: 10px; 
        padding: 12px 20px; 
        transition: 0.3s; 
        margin-bottom: 10px; 
        border: 1px solid transparent; 
    }
    .nav-pills .nav-link.active { 
        background: var(--primary-gradient); 
        border-color: transparent; 
        box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3); 
        color: white;
    }
    .nav-pills .nav-link:hover:not(.active) { 
        background: #f8f9fc; 
        border-color: #d1d3e2; 
    }
    
    /* Card Styles */
    .content-card { 
        border: none; 
        border-radius: 15px; 
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); 
        overflow: hidden; 
        margin-bottom: 25px;
    }
    .card-header-modern { 
        background: white; 
        border-bottom: 1px solid #f1f1f1; 
        padding: 1.25rem 1.5rem; 
    }
    
    /* Image Styling */
    .preview-image-large {
        width: 100%;
        max-height: 250px;
        object-fit: cover;
        border-radius: 12px;
        border: 2px solid #eaecf4;
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    
    /* Action Buttons */
    .btn-action { 
        border-radius: 8px; 
        padding: 6px 12px; 
        font-weight: 600; 
        transition: 0.3s; 
        border: none; 
    }
    .btn-edit-modern { background: #f6c23e; color: #fff; }
    .btn-delete-modern { background: #e74a3b; color: #fff; }
    .btn-edit-modern:hover, .btn-delete-modern:hover { 
        transform: scale(1.05); 
        filter: brightness(90%); 
        color: #fff; 
    }
</style>

<div class="container-fluid py-3">
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
        <button type="button" class="btn-close" onclick="closeAlert(this)" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
        <button type="button" class="btn-close" onclick="closeAlert(this)" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card content-card sticky-top" style="top: 20px; z-index: 10;">
                <div class="card-body p-3">
                    <h6 class="text-xs font-weight-bold text-primary text-uppercase mb-3 px-2">Data Profil Desa</h6>
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                        <button class="nav-link active text-left" data-bs-toggle="pill" data-bs-target="#tab-tentang" type="button">
                            <i class="fas fa-info-circle fa-fw me-2"></i> Tentang Kami
                        </button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-visimisi" type="button">
                            <i class="fas fa-bullseye fa-fw me-2"></i> Visi & Misi
                        </button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-dusun" type="button">
                            <i class="fas fa-map-marked-alt fa-fw me-2"></i> Wilayah Dusun
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">
                
                <div class="tab-pane fade show active" id="tab-tentang">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex align-items-center">
                            <i class="fas fa-info-circle text-primary me-3"></i>
                            <h6 class="m-0 font-weight-bold text-dark">Profil Singkat Desa</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-7 mb-4">
                                        <label class="form-label small font-weight-bold">Deskripsi Tentang Desa</label>
                                        <textarea class="form-control" name="deskripsi" rows="9" required><?php echo $tentang['deskripsi'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="col-md-5 mb-4">
                                        <label class="form-label small font-weight-bold">Gambar Representasi</label>
                                        <input type="file" class="form-control mb-3" name="gambar" accept="image/*">
                                        <input type="hidden" name="existing_gambar" value="<?php echo $tentang['gambar'] ?? ''; ?>">
                                        
                                        <div class="text-center bg-light p-3 rounded border">
                                            <?php if(!empty($tentang['gambar'])): ?>
                                                <img src="../../assets/images/<?php echo $tentang['gambar']; ?>" class="preview-image-large mb-2">
                                                <small class="text-muted d-block">Gambar yang digunakan saat ini</small>
                                            <?php else: ?>
                                                <i class="fas fa-image fa-4x text-muted mb-2 opacity-50"></i>
                                                <small class="text-muted d-block">Belum ada gambar yang diunggah</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="save_tentang" class="btn btn-primary px-4 rounded-pill">
                                        <i class="fas fa-save me-2"></i>Simpan Tentang Kami
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-visimisi">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex align-items-center">
                            <i class="fas fa-bullseye text-primary me-3"></i>
                            <h6 class="m-0 font-weight-bold text-dark">Visi & Misi Desa</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-12 mb-4">
                                        <label class="form-label small font-weight-bold text-primary"><i class="fas fa-eye me-2"></i>Visi</label>
                                        <textarea class="form-control" name="visi" rows="3" placeholder="Masukkan Visi Utama Desa..." required><?php echo $visimisi['visi'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="col-12 mb-4">
                                        <label class="form-label small font-weight-bold text-success"><i class="fas fa-tasks me-2"></i>Misi</label>
                                        <textarea class="form-control" name="misi" rows="6" placeholder="Masukkan Misi Desa (Gunakan enter untuk memisahkan setiap poin)..." required><?php echo $visimisi['misi'] ?? ''; ?></textarea>
                                        <small class="text-muted mt-2 d-block">* Tips: Gunakan tombol Enter untuk membuat daftar poin misi.</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="save_visi_misi" class="btn btn-primary px-4 rounded-pill">
                                        <i class="fas fa-save me-2"></i>Simpan Visi & Misi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-dusun">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marked-alt text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Wilayah Dusun</h6>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModalDusun()">
                                <i class="fas fa-plus me-1"></i> Tambah Dusun
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr class="text-gray-500 small text-uppercase font-weight-bold border-bottom">
                                            <th width="20%">Nama Dusun</th>
                                            <th width="20%">Kepala Dusun</th>
                                            <th width="45%">Deskripsi / Penjelasan</th>
                                            <th width="15%" class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($dusuns) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($dusuns)): ?>
                                            <tr>
                                                <td><span class="font-weight-bold text-dark"><?php echo htmlspecialchars($row['nama_dusun']); ?></span></td>
                                                <td><span class="badge bg-light text-primary border border-primary"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($row['kepala_dusun']); ?></span></td>
                                                <td class="small text-muted">
                                                    <?php echo nl2br(htmlspecialchars(substr($row['deskripsi'] ?? '', 0, 100))); ?><?php echo strlen($row['deskripsi'] ?? '') > 100 ? '...' : ''; ?>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-action btn-edit-modern btn-sm" onclick='editDusun(<?php echo json_encode($row); ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-action btn-delete-modern btn-sm" onclick="confirmDeleteDusun(<?php echo $row['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="fas fa-map text-gray-300 fa-3x mb-3"></i>
                                                    <br>Belum ada data dusun yang ditambahkan.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div> </div> </div> </div> <div class="modal fade" id="dusunModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <form method="POST" id="dusunForm">
                <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title font-weight-bold" id="dusunModalTitle"><i class="fas fa-plus me-2"></i>Tambah Dusun</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModalDusun()" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="dusun_id">
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Nama Dusun</label>
                        <input type="text" class="form-control" name="nama_dusun" id="dusun_nama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Nama Kepala Dusun</label>
                        <input type="text" class="form-control" name="kepala_dusun" id="dusun_kepala" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Deskripsi / Penjelasan Dusun</label>
                        <textarea class="form-control" name="deskripsi" id="dusun_deskripsi" rows="4" placeholder="Jelaskan potensi, kegiatan, atau sejarah dusun ini..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeModalDusun()">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="dusunSubmitBtn" name="add_dusun"><i class="fas fa-save me-2"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <div class="modal-header bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-trash me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeDeleteModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 px-4 text-center">
                <i class="fas fa-exclamation-circle fa-4x text-danger mb-3"></i>
                <h5 class="mb-2 text-dark font-weight-bold">Apakah Anda yakin?</h5>
                <p class="text-muted mb-0">Data dusun yang dihapus tidak dapat dikembalikan lagi.</p>
            </div>
            <div class="modal-footer bg-light justify-content-center" style="border-radius: 0 0 15px 15px;">
                <form method="POST" id="deleteForm" class="w-100 d-flex justify-content-between px-3">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeDeleteModal()">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" name="delete_dusun">Ya, Hapus Data</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let dusunModal;
let deleteModal;

function closeAlert(element) {
    const alert = element.closest('.alert');
    if (alert) alert.remove();
}

function closeModalDusun() {
    if (dusunModal) dusunModal.hide();
}

function closeDeleteModal() {
    if (deleteModal) deleteModal.hide();
}

document.addEventListener('DOMContentLoaded', function() {
    const dusunModalEl = document.getElementById('dusunModal');
    const deleteModalEl = document.getElementById('deleteModal');
    
    if (dusunModalEl) dusunModal = new bootstrap.Modal(dusunModalEl);
    if (deleteModalEl) deleteModal = new bootstrap.Modal(deleteModalEl);
});

function openModalDusun() {
    document.getElementById('dusunModalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Tambah Dusun';
    document.getElementById('dusun_id').value = '';
    document.getElementById('dusun_nama').value = '';
    document.getElementById('dusun_kepala').value = '';
    document.getElementById('dusun_deskripsi').value = '';
    document.getElementById('dusunSubmitBtn').name = 'add_dusun';
    
    if (dusunModal) dusunModal.show();
}

function editDusun(data) {
    document.getElementById('dusunModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Dusun';
    document.getElementById('dusun_id').value = data.id;
    document.getElementById('dusun_nama').value = data.nama_dusun;
    document.getElementById('dusun_kepala').value = data.kepala_dusun;
    document.getElementById('dusun_deskripsi').value = data.deskripsi || '';
    document.getElementById('dusunSubmitBtn').name = 'edit_dusun';
    
    if (dusunModal) dusunModal.show();
}

function confirmDeleteDusun(id) {
    document.getElementById('delete_id').value = id;
    if (deleteModal) deleteModal.show();
}
</script>

<?php
$content = ob_get_clean();
include '../../includes/base.php';
?>