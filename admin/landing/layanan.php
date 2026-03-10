<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../../login.php");
    exit();
}

include "../../db/koneksi.php";

$pageTitle = "Kelola Layanan Desa";

// ==================== HANDLE CRUD LAYANAN SURAT ====================

// TAMBAH LAYANAN
if (isset($_POST['add_layanan'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $persyaratan = mysqli_real_escape_string($conn, $_POST['persyaratan']);
    $urutan = intval($_POST['urutan']);
    
    $query = "INSERT INTO layanan_surat (judul, icon, persyaratan, urutan) VALUES ('$judul', '$icon', '$persyaratan', '$urutan')";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Layanan berhasil ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: layanan.php");
    exit();
}

// EDIT LAYANAN
if (isset($_POST['edit_layanan'])) {
    $id = intval($_POST['id']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $persyaratan = mysqli_real_escape_string($conn, $_POST['persyaratan']);
    $urutan = intval($_POST['urutan']);
    
    $query = "UPDATE layanan_surat SET judul='$judul', icon='$icon', persyaratan='$persyaratan', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Layanan berhasil diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: layanan.php");
    exit();
}

// HAPUS LAYANAN
if (isset($_POST['delete_layanan'])) {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM layanan_surat WHERE id=$id");
    $_SESSION['success_message'] = "Layanan berhasil dihapus";
    header("Location: layanan.php");
    exit();
}

// ==================== HANDLE CRUD JAM OPERASIONAL ====================

// TAMBAH JAM
if (isset($_POST['add_jam'])) {
    $hari = mysqli_real_escape_string($conn, $_POST['hari']);
    $jam = mysqli_real_escape_string($conn, $_POST['jam']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $urutan = intval($_POST['urutan']);
    
    $query = "INSERT INTO layanan_jam (hari, jam, icon, urutan) VALUES ('$hari', '$jam', '$icon', '$urutan')";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Jam operasional ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: layanan.php");
    exit();
}

// EDIT JAM
if (isset($_POST['edit_jam'])) {
    $id = intval($_POST['id']);
    $hari = mysqli_real_escape_string($conn, $_POST['hari']);
    $jam = mysqli_real_escape_string($conn, $_POST['jam']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $urutan = intval($_POST['urutan']);
    
    $query = "UPDATE layanan_jam SET hari='$hari', jam='$jam', icon='$icon', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Jam operasional diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: layanan.php");
    exit();
}

// HAPUS JAM
if (isset($_POST['delete_jam'])) {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM layanan_jam WHERE id=$id");
    $_SESSION['success_message'] = "Jam operasional dihapus";
    header("Location: layanan.php");
    exit();
}

// ==================== AMBIL DATA ====================
$layanans = mysqli_query($conn, "SELECT * FROM layanan_surat ORDER BY urutan ASC, id ASC");
$jams = mysqli_query($conn, "SELECT * FROM layanan_jam ORDER BY urutan ASC, id ASC");

ob_start();
?>

<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

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

    /* Icon Box */
    .icon-preview-box {
        width: 45px; 
        height: 45px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        background-color: #f8f9fa; 
        border: 1px solid #ced4da; 
        border-radius: 8px;
        flex-shrink: 0;
    }
    .icon-preview-box ion-icon {
        font-size: 24px;
        color: #4e73df;
    }
    
    /* Table Icon Box */
    .table-icon-box {
        width: 45px;
        height: 45px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #f8f9fc;
        border: 1px solid #eaecf4;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
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
                    <h6 class="text-xs font-weight-bold text-primary text-uppercase mb-3 px-2">Manajemen Layanan</h6>
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                        <button class="nav-link active text-left" data-bs-toggle="pill" data-bs-target="#tab-surat" type="button">
                            <i class="fas fa-file-alt fa-fw me-2"></i> Layanan Surat
                        </button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-jam" type="button">
                            <i class="fas fa-clock fa-fw me-2"></i> Jam Operasional
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">

                <div class="tab-pane fade show active" id="tab-surat">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-alt text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Daftar Layanan Surat</h6>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModalLayanan()">
                                <i class="fas fa-plus me-1"></i> Tambah Layanan
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr class="text-gray-500 small text-uppercase font-weight-bold border-bottom">
                                            <th width="12%" class="text-center">Icon</th>
                                            <th width="30%">Jenis Layanan</th>
                                            <th width="43%">Persyaratan</th>
                                            <th width="15%" class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($layanans) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($layanans)): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <div class="table-icon-box">
                                                        <ion-icon name="<?php echo htmlspecialchars($row['icon']); ?>" style="font-size: 24px; color: #4e73df;"></ion-icon>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="font-weight-bold text-dark d-block"><?php echo htmlspecialchars($row['judul']); ?></span>
                                                    <span class="badge bg-light text-secondary border mt-1">Urutan: <?php echo $row['urutan']; ?></span>
                                                </td>
                                                <td class="small text-muted">
                                                    <?php echo nl2br(htmlspecialchars(substr($row['persyaratan'], 0, 150))); ?><?php echo strlen($row['persyaratan']) > 150 ? '...' : ''; ?>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-action btn-edit-modern btn-sm" onclick='editLayanan(<?php echo json_encode($row); ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-action btn-delete-modern btn-sm" onclick="confirmDelete('layanan', <?php echo $row['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="fas fa-file-signature text-gray-300 fa-3x mb-3"></i>
                                                    <br>Belum ada data layanan surat.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-jam">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Jam Operasional Pelayanan</h6>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModalJam()">
                                <i class="fas fa-plus me-1"></i> Tambah Jadwal
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr class="text-gray-500 small text-uppercase font-weight-bold border-bottom">
                                            <th width="12%" class="text-center">Icon</th>
                                            <th width="28%">Hari / Kategori</th>
                                            <th width="45%">Jam Pelayanan</th>
                                            <th width="15%" class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($jams) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($jams)): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <div class="table-icon-box">
                                                        <ion-icon name="<?php echo htmlspecialchars($row['icon']); ?>" style="font-size: 24px; color: #ff7e5f;"></ion-icon>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="font-weight-bold text-dark d-block"><?php echo htmlspecialchars($row['hari']); ?></span>
                                                    <span class="badge bg-light text-secondary border mt-1">Urutan: <?php echo $row['urutan']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success bg-opacity-10 text-white border border-success border-opacity-25 px-3 py-2 fw-bold" style="font-size: 13px;">
                                                        <i class="far fa-clock me-1"></i> <?php echo htmlspecialchars($row['jam']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-action btn-edit-modern btn-sm" onclick='editJam(<?php echo json_encode($row); ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-action btn-delete-modern btn-sm" onclick="confirmDelete('jam', <?php echo $row['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="far fa-calendar-times text-gray-300 fa-3x mb-3"></i>
                                                    <br>Belum ada data jadwal pelayanan.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div> </div> </div> </div> <div class="modal fade" id="layananModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <form method="POST" id="layananForm">
                <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title font-weight-bold" id="layananModalTitle"><i class="fas fa-plus me-2"></i>Tambah Layanan Surat</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModal('layananModal')" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="layanan_id">
                    
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label small font-weight-bold">Judul Layanan Surat</label>
                            <input type="text" class="form-control" name="judul" id="layanan_judul" placeholder="Contoh: SURAT KETERANGAN USAHA (SKU)" required>
                        </div>
                        
                        <div class="col-md-5 mb-3">
                            <label class="form-label small font-weight-bold">Pilih Icon</label>
                            <div class="d-flex align-items-center gap-2">
                                <select class="form-select bg-light" name="icon" id="layanan_icon" onchange="updatePreview('layanan_icon', 'preview_layanan')" required>
                                    <option value="document-text-outline">Surat / Dokumen</option>
                                    <option value="people-outline">Penduduk / Group</option>
                                    <option value="person-outline">Personal / Individu</option>
                                    <option value="heart-outline">Pernikahan / Hati</option>
                                    <option value="business-outline">Usaha / Bisnis</option>
                                    <option value="skull-outline">Meninggal Dunia</option>
                                    <option value="git-network-outline">Ahli Waris / Jaringan</option>
                                    <option value="happy-outline">Kelahiran / Bahagia</option>
                                    <option value="earth-outline">Tanah / Bumi</option>
                                    <option value="home-outline">Kartu Keluarga / Rumah</option>
                                    <option value="document-attach-outline">Akta / Lampiran</option>
                                    <option value="id-card-outline">KTP / Identitas</option>
                                    <option value="hammer-outline">Bangunan / IMB</option>
                                    <option value="briefcase-outline">Pekerjaan / SITU</option>
                                    <option value="cash-outline">Penghasilan / Uang</option>
                                    <option value="construct-outline">Operasional / Teknis</option>
                                    <option value="help-circle-outline">SKTM / Bantuan</option>
                                    <option value="map-outline">Sporadik / Peta</option>
                                </select>
                                <div class="icon-preview-box shadow-sm">
                                    <ion-icon id="preview_layanan" name="document-text-outline"></ion-icon>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Persyaratan</label>
                        <textarea class="form-control" name="persyaratan" id="layanan_persyaratan" rows="5" placeholder="Gunakan Enter untuk baris baru. Contoh:&#10;Foto Copy KTP&#10;Surat Pengantar RT/RW" required></textarea>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label small font-weight-bold">Urutan Tampil</label>
                        <input type="number" class="form-control" name="urutan" id="layanan_urutan" value="1" min="1">
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeModal('layananModal')">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="layananSubmitBtn" name="add_layanan"><i class="fas fa-save me-2"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="jamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <form method="POST" id="jamForm">
                <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title font-weight-bold" id="jamModalTitle"><i class="fas fa-plus me-2"></i>Tambah Jam Operasional</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModal('jamModal')" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="jam_id">
                    
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Hari / Kategori</label>
                        <input type="text" class="form-control" name="hari" id="jam_hari" placeholder="Contoh: Hari Kerja atau Senin - Jumat" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Jam Keterangan</label>
                        <input type="text" class="form-control" name="jam" id="jam_jam" placeholder="Contoh: 08.00 - 15.00 WIB" required>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label small font-weight-bold">Pilih Icon</label>
                            <div class="d-flex align-items-center gap-2">
                                <select class="form-select bg-light" name="icon" id="jam_icon" onchange="updatePreview('jam_icon', 'preview_jam')">
                                    <option value="time-outline">Waktu / Jam</option>
                                    <option value="calendar-outline">Kalender / Hari</option>
                                    <option value="alert-circle-outline">Peringatan / Tutup</option>
                                    <option value="checkmark-circle-outline">Buka / Centang</option>
                                    <option value="close-circle-outline">Tutup / Silang</option>
                                </select>
                                <div class="icon-preview-box shadow-sm">
                                    <ion-icon id="preview_jam" name="time-outline"></ion-icon>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small font-weight-bold">Urutan</label>
                            <input type="number" class="form-control" name="urutan" id="jam_urutan" value="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeModal('jamModal')">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="jamSubmitBtn" name="add_jam"><i class="fas fa-save me-2"></i>Simpan</button>
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
    const layananEl = document.getElementById('layananModal');
    const jamEl = document.getElementById('jamModal');
    const deleteEl = document.getElementById('deleteModal');

    if (layananEl) modals.layananModal = new bootstrap.Modal(layananEl);
    if (jamEl) modals.jamModal = new bootstrap.Modal(jamEl);
    if (deleteEl) modals.deleteModal = new bootstrap.Modal(deleteEl);
});

function closeAlert(element) {
    const alert = element.closest('.alert');
    if (alert) alert.remove();
}

function closeModal(modalId) {
    if (modals[modalId]) modals[modalId].hide();
}

// ==== SCRIPT UPDATE PREVIEW ICON ====
function updatePreview(selectId, previewId) {
    const select = document.getElementById(selectId);
    const preview = document.getElementById(previewId);
    if (select && preview) {
        preview.setAttribute('name', select.value);
    }
}

// ==== LAYANAN SURAT ====
function openModalLayanan() {
    document.getElementById('layananModalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Tambah Layanan Surat';
    document.getElementById('layanan_id').value = '';
    document.getElementById('layanan_judul').value = '';
    document.getElementById('layanan_icon').value = 'document-text-outline';
    document.getElementById('layanan_persyaratan').value = '';
    document.getElementById('layanan_urutan').value = '1';
    document.getElementById('layananSubmitBtn').name = 'add_layanan';
    
    updatePreview('layanan_icon', 'preview_layanan'); // Reset preview
    if(modals.layananModal) modals.layananModal.show();
}

function editLayanan(data) {
    document.getElementById('layananModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Layanan Surat';
    document.getElementById('layanan_id').value = data.id;
    document.getElementById('layanan_judul').value = data.judul;
    
    // Set value dropdown & update preview
    let iconSelect = document.getElementById('layanan_icon');
    if([...iconSelect.options].some(opt => opt.value === data.icon)) {
        iconSelect.value = data.icon;
    } else {
        iconSelect.value = 'document-text-outline'; // fallback if icon not in list
    }
    updatePreview('layanan_icon', 'preview_layanan'); 

    document.getElementById('layanan_persyaratan').value = data.persyaratan;
    document.getElementById('layanan_urutan').value = data.urutan;
    document.getElementById('layananSubmitBtn').name = 'edit_layanan';
    if(modals.layananModal) modals.layananModal.show();
}

// ==== JAM OPERASIONAL ====
function openModalJam() {
    document.getElementById('jamModalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Tambah Jam Operasional';
    document.getElementById('jam_id').value = '';
    document.getElementById('jam_hari').value = '';
    document.getElementById('jam_jam').value = '';
    document.getElementById('jam_icon').value = 'time-outline';
    document.getElementById('jam_urutan').value = '1';
    document.getElementById('jamSubmitBtn').name = 'add_jam';
    
    updatePreview('jam_icon', 'preview_jam'); // Reset preview
    if(modals.jamModal) modals.jamModal.show();
}

function editJam(data) {
    document.getElementById('jamModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Jam Operasional';
    document.getElementById('jam_id').value = data.id;
    document.getElementById('jam_hari').value = data.hari;
    document.getElementById('jam_jam').value = data.jam;
    
    // Set value dropdown & update preview
    let iconSelect = document.getElementById('jam_icon');
    if([...iconSelect.options].some(opt => opt.value === data.icon)) {
        iconSelect.value = data.icon;
    } else {
        iconSelect.value = 'time-outline'; // fallback
    }
    updatePreview('jam_icon', 'preview_jam');

    document.getElementById('jam_urutan').value = data.urutan;
    document.getElementById('jamSubmitBtn').name = 'edit_jam';
    if(modals.jamModal) modals.jamModal.show();
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