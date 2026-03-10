<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../../login.php");
    exit();
}

include "../../db/koneksi.php";

$pageTitle = "Kelola Data Kegiatan";

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
    if ($check === false) return ['success' => false, 'message' => 'File bukan gambar'];
    if ($file["size"] > 5000000) return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
    
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($imageFileType, $allowed)) return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & WEBP yang diizinkan'];
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        if (!empty($old_file) && file_exists($target_dir . $old_file)) {
            unlink($target_dir . $old_file);
        }
        return ['success' => true, 'file_name' => $file_name];
    } else {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }
}

// ==================== HANDLE CRUD ====================

// 1. PENGATURAN HALAMAN
if (isset($_POST['save_pengaturan'])) {
    $judul_halaman = mysqli_real_escape_string($conn, $_POST['judul_halaman']);
    $deskripsi_halaman = mysqli_real_escape_string($conn, $_POST['deskripsi_halaman']);

    $check = mysqli_query($conn, "SELECT id FROM kegiatan_pengaturan LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $query = "UPDATE kegiatan_pengaturan SET judul_halaman='$judul_halaman', deskripsi_halaman='$deskripsi_halaman' WHERE id={$row['id']}";
    } else {
        $query = "INSERT INTO kegiatan_pengaturan (judul_halaman, deskripsi_halaman) VALUES ('$judul_halaman', '$deskripsi_halaman')";
    }
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Pengaturan berhasil disimpan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: kegiatan.php");
    exit();
}

// 2. ITEM KEGIATAN
if (isset($_POST['add_kegiatan'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $peserta = mysqli_real_escape_string($conn, $_POST['peserta']);
    $detail_kegiatan = mysqli_real_escape_string($conn, $_POST['detail_kegiatan']);
    $is_utama = intval($_POST['is_utama']);
    $urutan = intval($_POST['urutan']);
    $gambar = '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar']);
        if ($upload['success']) $gambar = $upload['file_name'];
        else { $_SESSION['error_message'] = $upload['message']; header("Location: kegiatan.php"); exit(); }
    }
    
    $query = "INSERT INTO kegiatan_item (judul, deskripsi, lokasi, tanggal, peserta, detail_kegiatan, gambar, is_utama, urutan) 
              VALUES ('$judul', '$deskripsi', '$lokasi', '$tanggal', '$peserta', '$detail_kegiatan', '$gambar', '$is_utama', '$urutan')";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Kegiatan ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: kegiatan.php");
    exit();
}

if (isset($_POST['edit_kegiatan'])) {
    $id = intval($_POST['id']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $peserta = mysqli_real_escape_string($conn, $_POST['peserta']);
    $detail_kegiatan = mysqli_real_escape_string($conn, $_POST['detail_kegiatan']);
    $is_utama = intval($_POST['is_utama']);
    $urutan = intval($_POST['urutan']);
    $gambar = $_POST['existing_gambar'] ?? '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar'], $gambar);
        if ($upload['success']) $gambar = $upload['file_name'];
        else { $_SESSION['error_message'] = $upload['message']; header("Location: kegiatan.php"); exit(); }
    }
    
    $query = "UPDATE kegiatan_item SET judul='$judul', deskripsi='$deskripsi', lokasi='$lokasi', tanggal='$tanggal', peserta='$peserta', detail_kegiatan='$detail_kegiatan', gambar='$gambar', is_utama='$is_utama', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Kegiatan diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: kegiatan.php");
    exit();
}

if (isset($_POST['delete_kegiatan'])) {
    $id = intval($_POST['id']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gambar FROM kegiatan_item WHERE id=$id"));
    if ($row && !empty($row['gambar'])) uploadGambar(['name'=>''], $row['gambar']);
    mysqli_query($conn, "DELETE FROM kegiatan_item WHERE id=$id");
    $_SESSION['success_message'] = "Kegiatan dihapus";
    header("Location: kegiatan.php");
    exit();
}

// 3. TESTIMONI
if (isset($_POST['add_testimoni'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $kutipan = mysqli_real_escape_string($conn, $_POST['kutipan']);
    $urutan = intval($_POST['urutan']);
    $query = "INSERT INTO kegiatan_testimoni (nama, jabatan, kutipan, urutan) VALUES ('$nama', '$jabatan', '$kutipan', '$urutan')";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Testimoni ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: kegiatan.php"); exit();
}

if (isset($_POST['edit_testimoni'])) {
    $id = intval($_POST['id']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $kutipan = mysqli_real_escape_string($conn, $_POST['kutipan']);
    $urutan = intval($_POST['urutan']);
    $query = "UPDATE kegiatan_testimoni SET nama='$nama', jabatan='$jabatan', kutipan='$kutipan', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Testimoni diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: kegiatan.php"); exit();
}

if (isset($_POST['delete_testimoni'])) {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM kegiatan_testimoni WHERE id=$id");
    $_SESSION['success_message'] = "Testimoni dihapus";
    header("Location: kegiatan.php"); exit();
}

// ==================== AMBIL DATA ====================
$pengaturan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM kegiatan_pengaturan LIMIT 1"));
$kegiatans = mysqli_query($conn, "SELECT * FROM kegiatan_item ORDER BY is_utama DESC, urutan ASC, id DESC");
$testimonis = mysqli_query($conn, "SELECT * FROM kegiatan_testimoni ORDER BY urutan ASC, id DESC");

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

    /* Images */
    .preview-img-table { 
        width: 80px; 
        height: 60px; 
        object-fit: cover; 
        border-radius: 8px; 
        border: 1px solid #eaecf4; 
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

    /* Info Area Box */
    .info-box-modern {
        background: #f8f9fc;
        padding: 20px;
        border-radius: 12px;
        border: 1px dashed #4e73df;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid py-3">
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
        <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
        <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card content-card sticky-top" style="top: 20px; z-index: 10;">
                <div class="card-body p-3">
                    <h6 class="text-xs font-weight-bold text-primary text-uppercase mb-3 px-2">Data Kegiatan</h6>
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                        <button class="nav-link active text-left" data-bs-toggle="pill" data-bs-target="#tab-pengaturan" type="button">
                            <i class="fas fa-heading fa-fw me-2"></i> Judul Halaman
                        </button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-kegiatan" type="button">
                            <i class="fas fa-camera-retro fa-fw me-2"></i> Daftar Kegiatan
                        </button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-testimoni" type="button">
                            <i class="fas fa-comments fa-fw me-2"></i> Testimoni
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">

                <div class="tab-pane fade show active" id="tab-pengaturan">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex align-items-center">
                            <i class="fas fa-heading text-primary me-3"></i>
                            <h6 class="m-0 font-weight-bold text-dark">Judul & Deskripsi Halaman Kegiatan</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-12 mb-4">
                                        <label class="form-label small font-weight-bold">Judul Halaman</label>
                                        <input type="text" class="form-control" name="judul_halaman" value="<?php echo htmlspecialchars($pengaturan['judul_halaman'] ?? ''); ?>" placeholder="Contoh: Kegiatan KKN Kelompok 40..." required>
                                    </div>
                                    <div class="col-md-12 mb-4">
                                        <label class="form-label small font-weight-bold">Deskripsi Halaman</label>
                                        <textarea class="form-control" name="deskripsi_halaman" rows="4" placeholder="Contoh: Berbagai program kerja yang telah dilaksanakan..." required><?php echo htmlspecialchars($pengaturan['deskripsi_halaman'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="save_pengaturan" class="btn btn-primary px-4 rounded-pill">
                                        <i class="fas fa-save me-2"></i>Simpan Pengaturan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-kegiatan">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-camera-retro text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Daftar Kegiatan & Galeri</h6>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModalKegiatan()">
                                <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr class="text-gray-500 small text-uppercase font-weight-bold border-bottom">
                                            <th width="12%" class="text-center">Foto</th>
                                            <th width="28%">Judul Kegiatan</th>
                                            <th width="20%" class="text-center">Tipe</th>
                                            <th width="25%">Info Singkat</th>
                                            <th width="15%" class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($kegiatans) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($kegiatans)): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <?php if(!empty($row['gambar'])): ?>
                                                        <div class="shadow-sm d-inline-block rounded-3 overflow-hidden">
                                                            <img src="../../assets/images/<?php echo $row['gambar']; ?>" class="preview-img-table m-0 border-0">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="bg-light rounded p-2 text-muted border"><i class="fas fa-image fa-2x"></i></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($row['judul']); ?></td>
                                                <td class="text-center">
                                                    <?php if($row['is_utama'] == 1): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-white border border-success border-opacity-25 px-2 py-1">Utama (Lengkap)</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary bg-opacity-10 text-white border border-secondary border-opacity-25 px-2 py-1">Biasa (Galeri)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($row['is_utama'] == 1): ?>
                                                        <div class="small text-muted">
                                                            <div class="text-truncate mb-1" style="max-width: 150px;"><i class="fas fa-map-marker-alt text-danger me-1"></i> <?php echo htmlspecialchars($row['lokasi']); ?></div>
                                                            <div class="text-truncate" style="max-width: 150px;"><i class="far fa-calendar-alt text-primary me-1"></i> <?php echo htmlspecialchars($row['tanggal']); ?></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small fst-italic">- Hanya foto galeri -</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-action btn-edit-modern btn-sm" onclick='editKegiatan(<?php echo json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                                    <button type="button" class="btn btn-action btn-delete-modern btn-sm" onclick="confirmDelete('kegiatan', <?php echo $row['id']; ?>)"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5 text-muted">
                                                    <i class="fas fa-camera text-gray-300 fa-3x mb-3"></i>
                                                    <br>Belum ada data kegiatan.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-testimoni">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-comments text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Kesan & Pesan (Testimoni)</h6>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModalTestimoni()">
                                <i class="fas fa-plus me-1"></i> Tambah Testimoni
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr class="text-gray-500 small text-uppercase font-weight-bold border-bottom">
                                            <th width="20%">Nama</th>
                                            <th width="20%">Jabatan / Status</th>
                                            <th width="45%">Kutipan / Kesan</th>
                                            <th width="15%" class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($testimonis) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($testimonis)): ?>
                                            <tr>
                                                <td>
                                                    <div class="font-weight-bold text-dark d-flex align-items-center">
                                                        <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 12px;">
                                                            <?php echo strtoupper(substr($row['nama'], 0, 1)); ?>
                                                        </div>
                                                        <?php echo htmlspecialchars($row['nama']); ?>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-light text-primary border"><?php echo htmlspecialchars($row['jabatan']); ?></span></td>
                                                <td class="small text-muted fst-italic">"<?php echo htmlspecialchars($row['kutipan']); ?>"</td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-action btn-edit-modern btn-sm" onclick='editTestimoni(<?php echo json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                                    <button type="button" class="btn btn-action btn-delete-modern btn-sm" onclick="confirmDelete('testimoni', <?php echo $row['id']; ?>)"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="fas fa-comment-dots text-gray-300 fa-3x mb-3"></i>
                                                    <br>Belum ada data testimoni.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div> </div> </div> </div> <div class="modal fade" id="kegiatanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <form method="POST" enctype="multipart/form-data" id="kegiatanForm">
                <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title font-weight-bold" id="kegiatanModalTitle"><i class="fas fa-plus me-2"></i>Tambah Kegiatan</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModal('kegiatanModal')" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="kegiatan_id">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label small font-weight-bold">Judul Kegiatan</label>
                            <input type="text" class="form-control" name="judul" id="kegiatan_judul" placeholder="Contoh: Pembukaan KKN" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small font-weight-bold">Tipe Tampilan</label>
                            <select class="form-select border-primary bg-light" name="is_utama" id="kegiatan_tipe" onchange="toggleLengkap(this.value)">
                                <option value="0">Biasa / Galeri (Kecil)</option>
                                <option value="1">Utama (Besar & Lengkap)</option>
                            </select>
                        </div>
                    </div>

                    <div id="area_lengkap" class="info-box-modern" style="display: none;">
                        <h6 class="text-primary fw-bold mb-3"><i class="fas fa-info-circle me-2"></i>Detail Informasi Kegiatan Utama</h6>
                        <div class="mb-3">
                            <label class="form-label small font-weight-bold">Deskripsi Paragraf</label>
                            <textarea class="form-control" name="deskripsi" id="kegiatan_deskripsi" rows="3" placeholder="Acara pembukaan resmi..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small font-weight-bold">Lokasi</label>
                                <input type="text" class="form-control" name="lokasi" id="kegiatan_lokasi" placeholder="Contoh: Balai Desa">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small font-weight-bold">Tanggal</label>
                                <input type="text" class="form-control" name="tanggal" id="kegiatan_tanggal" placeholder="Contoh: 5 Juli 2023">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small font-weight-bold">Peserta</label>
                                <input type="text" class="form-control" name="peserta" id="kegiatan_peserta" placeholder="Contoh: Warga & Perangkat">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small font-weight-bold">Detail Kegiatan (Gunakan Koma / Enter)</label>
                            <textarea class="form-control" name="detail_kegiatan" id="kegiatan_detail" rows="2" placeholder="Sambutan, pemaparan, ramah tamah"></textarea>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-8 mb-3">
                            <label class="form-label small font-weight-bold">Foto Kegiatan</label>
                            <input type="file" class="form-control" name="gambar" accept="image/*">
                            <input type="hidden" name="existing_gambar" id="kegiatan_existing">
                            <div id="kegiatan_preview" class="mt-2"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small font-weight-bold">Urutan Tampil</label>
                            <input type="number" class="form-control" name="urutan" id="kegiatan_urutan" value="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeModal('kegiatanModal')">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="kegiatanSubmitBtn" name="add_kegiatan"><i class="fas fa-save me-2"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="testimoniModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <form method="POST" id="testimoniForm">
                <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title font-weight-bold" id="testimoniModalTitle"><i class="fas fa-plus me-2"></i>Tambah Testimoni</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModal('testimoniModal')" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="testimoni_id">
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama" id="testimoni_nama" placeholder="Contoh: Bpk. Abdul" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Jabatan / Status Pekerjaan</label>
                        <input type="text" class="form-control" name="jabatan" id="testimoni_jabatan" placeholder="Contoh: Koordinator Desa / Warga" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Kutipan / Kesan</label>
                        <textarea class="form-control" name="kutipan" id="testimoni_kutipan" rows="4" placeholder="Contoh: Program ini sangat bermanfaat..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Urutan</label>
                        <input type="number" class="form-control" name="urutan" id="testimoni_urutan" value="1">
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeModal('testimoniModal')">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="testimoniSubmitBtn" name="add_testimoni"><i class="fas fa-save me-2"></i>Simpan</button>
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
                <button type="button" class="btn-close btn-close-white" onclick="closeModal('deleteModal')" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 px-4 text-center">
                <i class="fas fa-exclamation-circle fa-4x text-danger mb-3"></i>
                <h5 class="mb-2 text-dark font-weight-bold">Apakah Anda yakin?</h5>
                <p class="text-muted mb-0">Data yang dihapus tidak dapat dikembalikan lagi.</p>
            </div>
            <div class="modal-footer bg-light justify-content-center" style="border-radius: 0 0 15px 15px;">
                <form method="POST" id="deleteForm" class="w-100 d-flex justify-content-between px-3">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeModal('deleteModal')">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" id="deleteSubmitBtn">Ya, Hapus Data</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let modals = {};

document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi modal menggunakan ID HTML
    const kegiatanEl = document.getElementById('kegiatanModal');
    const testimoniEl = document.getElementById('testimoniModal');
    const deleteEl = document.getElementById('deleteModal');

    if (kegiatanEl) modals.kegiatanModal = new bootstrap.Modal(kegiatanEl);
    if (testimoniEl) modals.testimoniModal = new bootstrap.Modal(testimoniEl);
    if (deleteEl) modals.deleteModal = new bootstrap.Modal(deleteEl);
});

function closeAlert(element) {
    const alert = element.closest('.alert');
    if (alert) alert.remove();
}

function closeModal(modalId) {
    if (modals[modalId]) modals[modalId].hide();
}

// Script untuk Toggle Form Kegiatan Utama
function toggleLengkap(val) {
    const area = document.getElementById('area_lengkap');
    if (val == '1') {
        area.style.display = 'block';
    } else {
        area.style.display = 'none';
    }
}

// ==== KEGIATAN ====
function openModalKegiatan() {
    document.getElementById('kegiatanModalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Tambah Kegiatan';
    document.getElementById('kegiatan_id').value = '';
    document.getElementById('kegiatan_judul').value = '';
    document.getElementById('kegiatan_tipe').value = '0';
    document.getElementById('kegiatan_deskripsi').value = '';
    document.getElementById('kegiatan_lokasi').value = '';
    document.getElementById('kegiatan_tanggal').value = '';
    document.getElementById('kegiatan_peserta').value = '';
    document.getElementById('kegiatan_detail').value = '';
    document.getElementById('kegiatan_urutan').value = '1';
    document.getElementById('kegiatan_existing').value = '';
    document.getElementById('kegiatan_preview').innerHTML = '';
    document.getElementById('kegiatanSubmitBtn').name = 'add_kegiatan';
    
    toggleLengkap('0');
    if(modals.kegiatanModal) modals.kegiatanModal.show();
}

function editKegiatan(data) {
    document.getElementById('kegiatanModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Kegiatan';
    document.getElementById('kegiatan_id').value = data.id;
    document.getElementById('kegiatan_judul').value = data.judul;
    document.getElementById('kegiatan_tipe').value = data.is_utama;
    document.getElementById('kegiatan_deskripsi').value = data.deskripsi || '';
    document.getElementById('kegiatan_lokasi').value = data.lokasi || '';
    document.getElementById('kegiatan_tanggal').value = data.tanggal || '';
    document.getElementById('kegiatan_peserta').value = data.peserta || '';
    document.getElementById('kegiatan_detail').value = data.detail_kegiatan || '';
    document.getElementById('kegiatan_urutan').value = data.urutan;
    
    document.getElementById('kegiatan_existing').value = data.gambar || '';
    if(data.gambar) {
        document.getElementById('kegiatan_preview').innerHTML = '<img src="../../assets/images/' + data.gambar + '" class="preview-img-table mt-2 border-0 shadow-sm">';
    } else {
        document.getElementById('kegiatan_preview').innerHTML = '';
    }
    
    document.getElementById('kegiatanSubmitBtn').name = 'edit_kegiatan';
    
    toggleLengkap(data.is_utama);
    if(modals.kegiatanModal) modals.kegiatanModal.show();
}

// ==== TESTIMONI ====
function openModalTestimoni() {
    document.getElementById('testimoniModalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Tambah Testimoni';
    document.getElementById('testimoni_id').value = '';
    document.getElementById('testimoni_nama').value = '';
    document.getElementById('testimoni_jabatan').value = '';
    document.getElementById('testimoni_kutipan').value = '';
    document.getElementById('testimoni_urutan').value = '1';
    document.getElementById('testimoniSubmitBtn').name = 'add_testimoni';
    if(modals.testimoniModal) modals.testimoniModal.show();
}

function editTestimoni(data) {
    document.getElementById('testimoniModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Testimoni';
    document.getElementById('testimoni_id').value = data.id;
    document.getElementById('testimoni_nama').value = data.nama;
    document.getElementById('testimoni_jabatan').value = data.jabatan;
    document.getElementById('testimoni_kutipan').value = data.kutipan;
    document.getElementById('testimoni_urutan').value = data.urutan;
    document.getElementById('testimoniSubmitBtn').name = 'edit_testimoni';
    if(modals.testimoniModal) modals.testimoniModal.show();
}

// ==== DELETE CONFIRMATION ====
function confirmDelete(type, id) {
    document.getElementById('delete_id').value = id;
    document.getElementById('deleteSubmitBtn').name = 'delete_' + type;
    if(modals.deleteModal) modals.deleteModal.show();
}
</script>

<?php
$content = ob_get_clean();
// Memanggil template base (Navbar, Sidebar, dan Footer dikelola di sini)
include '../../includes/base.php';
?>