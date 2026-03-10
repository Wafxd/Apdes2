<?php
session_start();
if (!isset($_SESSION['id_admin'])) { header("Location: ../../login.php"); exit(); }
include "../../db/koneksi.php";

$pageTitle = "Pesan Masuk Warga";

if (isset($_POST['read_pesan'])) {
    $id = intval($_POST['id']);
    mysqli_query($conn, "UPDATE pesan_kontak SET status_baca = 1 WHERE id = $id");
    $_SESSION['success_message'] = "Pesan ditandai telah dibaca.";
    header("Location: kontak.php"); exit();
}

if (isset($_POST['delete_pesan'])) {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM pesan_kontak WHERE id = $id");
    $_SESSION['success_message'] = "Pesan berhasil dihapus.";
    header("Location: kontak.php"); exit();
}

$pesan_masuk = mysqli_query($conn, "SELECT * FROM pesan_kontak ORDER BY tanggal DESC");
$total_unread = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM pesan_kontak WHERE status_baca = 0"))['jml'];

ob_start();
?>

<style>
.btn-delete { background: #dc3545; color: white; border: none; padding: 5px 15px; border-radius: 5px; transition: 0.3s; }
.btn-delete:hover { background: #c82333; }
.btn-view { background: #0d6efd; color: white; border: none; padding: 5px 15px; border-radius: 5px; margin-right: 5px; transition: 0.3s; }
.btn-view:hover { background: #0b5ed7; }
.unread-row { background-color: rgba(78, 115, 223, 0.08); font-weight: bold; }
</style>

<div class="container-fluid">
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
        <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4 border-bottom-primary">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-envelope-open-text me-2"></i>Kotak Masuk (Inbox) Warga</h6>
                    <?php if($total_unread > 0): ?>
                        <span class="badge bg-danger rounded-pill shadow-sm px-3 py-2"><?php echo $total_unread; ?> Pesan Baru</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="20%">Pengirim</th>
                                    <th width="20%">Email</th>
                                    <th width="25%">Subjek</th>
                                    <th width="20%">Tanggal Masuk</th>
                                    <th width="15%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($pesan_masuk) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($pesan_masuk)): ?>
                                    <tr class="<?php echo $row['status_baca'] == 0 ? 'unread-row' : ''; ?>">
                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td><a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($row['email']); ?></a></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['subjek']); ?>
                                            <?php if($row['status_baca'] == 0): ?><span class="badge bg-danger ms-1" style="font-size: 0.6rem;">Baru</span><?php endif; ?>
                                        </td>
                                        <td><small><?php echo date('d M Y, H:i', strtotime($row['tanggal'])); ?></small></td>
                                        <td class="text-center">
                                            <button type="button" class="btn-view btn-sm shadow-sm" onclick='lihatPesan(<?php echo json_encode($row); ?>)'><i class="fas fa-eye"></i></button>
                                            <button type="button" class="btn-delete btn-sm shadow-sm" onclick="confirmDeletePesan(<?php echo $row['id']; ?>)"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 text-gray-300"></i><br>Tidak ada pesan masuk.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pesanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-envelope-open-text me-2"></i>Detail Pesan Warga</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModal('pesanModal')"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-3 pb-3 border-bottom">
                    <div class="col-sm-3 text-muted fw-bold">Pengirim</div>
                    <div class="col-sm-9">: <span id="v_nama" class="fw-bold text-dark"></span> (<span id="v_email" class="text-primary"></span>)</div>
                </div>
                <div class="row mb-3 pb-3 border-bottom">
                    <div class="col-sm-3 text-muted fw-bold">Waktu Masuk</div>
                    <div class="col-sm-9">: <span id="v_tanggal" class="text-dark"></span></div>
                </div>
                <div class="row mb-3 pb-3 border-bottom">
                    <div class="col-sm-3 text-muted fw-bold">Subjek Pesan</div>
                    <div class="col-sm-9">: <span id="v_subjek" class="fw-bold text-dark"></span></div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="text-muted fw-bold mb-2">Isi Pesan:</div>
                        <div class="p-3 bg-light rounded border text-dark" id="v_pesan" style="white-space: pre-wrap; line-height: 1.8;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <form method="POST">
                    <input type="hidden" name="id" id="read_id">
                    <button type="submit" name="read_pesan" class="btn btn-success shadow-sm" id="btn_tandai_baca"><i class="fas fa-check-double me-2"></i>Tandai Sudah Dibaca</button>
                </form>
                <button type="button" class="btn btn-secondary shadow-sm" onclick="closeModal('pesanModal')"><i class="fas fa-times me-2"></i>Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModal('deleteModal')"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <i class="fas fa-exclamation-triangle fa-4x text-danger mb-3"></i>
                <p class="fs-5 text-dark mb-0">Apakah Anda yakin ingin menghapus pesan ini?</p>
                <small class="text-muted">Pesan yang dihapus tidak dapat dikembalikan.</small>
            </div>
            <div class="modal-footer bg-light justify-content-center">
                <form method="POST">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary px-4" onclick="closeModal('deleteModal')">Batal</button>
                    <button type="submit" class="btn btn-danger px-4" name="delete_pesan">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let modals = {};

document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Modal ke dalam object 'modals'
    if (document.getElementById('pesanModal')) {
        modals.pesanModal = new bootstrap.Modal(document.getElementById('pesanModal'));
    }
    if (document.getElementById('deleteModal')) {
        modals.deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    }
});

// Fungsi menutup alert
function closeAlert(element) {
    const alert = element.closest('.alert');
    if (alert) alert.remove();
}

// Fungsi menutup modal
function closeModal(modalId) {
    if (modals[modalId]) {
        modals[modalId].hide();
    }
}

function lihatPesan(data) {
    let date = new Date(data.tanggal);
    let dateString = date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

    document.getElementById('v_nama').textContent = data.nama;
    document.getElementById('v_email').textContent = data.email;
    document.getElementById('v_tanggal').textContent = dateString + ' WIB';
    document.getElementById('v_subjek').textContent = data.subjek;
    document.getElementById('v_pesan').textContent = data.pesan;
    
    document.getElementById('read_id').value = data.id;
    document.getElementById('btn_tandai_baca').style.display = (data.status_baca == 1) ? 'none' : 'inline-block';
    
    modals.pesanModal.show();
}

function confirmDeletePesan(id) {
    document.getElementById('delete_id').value = id;
    modals.deleteModal.show();
}
</script>

<?php
$content = ob_get_clean();
include '../../includes/base.php';
?>