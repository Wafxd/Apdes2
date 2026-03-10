<?php
session_start();
if (!isset($_SESSION['id_admin'])) { header("Location: ../../login.php"); exit(); }
include "../../db/koneksi.php";

$pageTitle = "Layanan Surat Digital";

// ==================== PROSES UPDATE STATUS ====================
if (isset($_POST['update_status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    $catatan = mysqli_real_escape_string($conn, $_POST['keterangan_admin']);
    mysqli_query($conn, "UPDATE permohonan_surat SET status='$status', keterangan_admin='$catatan' WHERE id=$id");
    $_SESSION['msg'] = "Status permohonan #$id telah diperbarui.";
    header("Location: permohonan_surat.php"); exit();
}

// ==================== PROSES HAPUS ====================
if (isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    $q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT berkas_syarat FROM permohonan_surat WHERE id=$id"));
    if(!empty($q['berkas_syarat'])) {
        foreach(explode(',', $q['berkas_syarat']) as $f) { @unlink("../../assets/berkas/".$f); }
    }
    mysqli_query($conn, "DELETE FROM permohonan_surat WHERE id=$id");
    $_SESSION['msg'] = "Permohonan berhasil dihapus.";
    header("Location: permohonan_surat.php"); exit();
}

// Mengambil Data & Statistik
$permohonan = mysqli_query($conn, "SELECT * FROM permohonan_surat ORDER BY id DESC");
$total = mysqli_num_rows($permohonan);
$pending = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM permohonan_surat WHERE status='Menunggu'"));
$done = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM permohonan_surat WHERE status='Selesai'"));

ob_start();
?>

<style>
    /* Global Dashboard Style */
    .dashboard-container { background: #f8f9fc; padding: 20px; border-radius: 20px; }
    
    /* Stats Cards */
    .stat-card { border: none; border-radius: 18px; transition: 0.3s; overflow: hidden; position: relative; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .stat-icon { position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.1; transform: rotate(-15deg); }
    
    /* Premium Table */
    .table-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); background: white; }
    .table thead th { border: none; background: #fcfcfc; color: #4e73df; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 20px; }
    .table tbody td { padding: 20px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
    .table tbody tr:last-child td { border-bottom: none; }
    
    /* Status Badges */
    .badge-custom { padding: 8px 16px; border-radius: 10px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .bg-waiting { background: #fff8e1; color: #ffa000; }
    .bg-process { background: #e3f2fd; color: #1976d2; }
    .bg-success-soft { background: #e8f5e9; color: #2e7d32; }
    .bg-danger-soft { background: #ffebee; color: #c62828; }

    /* Action Buttons */
    .btn-circle-action { width: 38px; height: 38px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; transition: 0.3s; border: none; }
    .btn-view { background: #4e73df; color: white; }
    .btn-view:hover { background: #224abe; box-shadow: 0 5px 15px rgba(78,115,223,0.3); }
    
    /* Modal Styling */
    .modal-content { border-radius: 25px; border: none; }
    .modal-header { border-bottom: none; padding: 30px 30px 10px; }
    .modal-body { padding: 30px; }
    .info-group { background: #fbfbfb; border-radius: 15px; padding: 15px; border: 1px solid #f1f1f1; }
    .file-item-card { background: white; border: 1px solid #eee; padding: 12px; border-radius: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 10px; text-decoration: none !important; color: #333; transition: 0.2s; }
    .file-item-card:hover { border-color: #4e73df; background: #f8faff; }
</style>

<div class="dashboard-container">
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-primary text-white shadow-sm p-3">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold opacity-75">Total Masuk</h6>
                    <h2 class="fw-bold mb-0"><?php echo $total; ?></h2>
                    <i class="fas fa-envelope stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-warning text-white shadow-sm p-3">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold opacity-75">Perlu Tindakan</h6>
                    <h2 class="fw-bold mb-0"><?php echo $pending; ?></h2>
                    <i class="fas fa-clock stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-success text-white shadow-sm p-3">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold opacity-75">Selesai</h6>
                    <h2 class="fw-bold mb-0"><?php echo $done; ?></h2>
                    <i class="fas fa-check-double stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th>Pemohon</th>
                            <th>Layanan</th>
                            <th>Berkas</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $n=1; while($r = mysqli_fetch_assoc($permohonan)): 
                            $wa = preg_replace('/[^0-9]/', '', $r['no_wa']);
                            if(substr($wa,0,1)=='0') $wa='62'.substr($wa,1);
                            
                            $status_class = "bg-waiting";
                            if($r['status'] == 'Diproses') $status_class = "bg-process";
                            if($r['status'] == 'Selesai') $status_class = "bg-success-soft";
                            if($r['status'] == 'Ditolak') $status_class = "bg-danger-soft";
                        ?>
                        <tr>
                            <td class="text-center text-muted small"><?php echo $n++; ?></td>
                            <td>
                                <div class="fw-bold text-dark mb-1"><?php echo $r['nama']; ?></div>
                                <div class="text-muted small"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y, H:i', strtotime($r['tanggal_pengajuan'])); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border px-3 py-2 rounded-pill"><?php echo $r['jenis_surat']; ?></span>
                            </td>
                            <td>
                                <?php if(!empty($r['berkas_syarat'])): ?>
                                    <div class="d-flex align-items-center gap-1 text-info">
                                        <i class="fas fa-paperclip small"></i>
                                        <span class="small fw-bold"><?php echo count(explode(',',$r['berkas_syarat'])); ?> Berkas</span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">Kosong</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-custom <?php echo $status_class; ?>">
                                    <i class="fas fa-dot-circle" style="font-size: 8px;"></i> <?php echo $r['status']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn-circle-action btn-view shadow-sm" onclick='bukaDetail(<?php echo json_encode($r); ?>)' title="Buka Detail">
                                        <i class="fas fa-expand-arrows-alt"></i>
                                    </button>
                                    <a href="https://wa.me/<?php echo $wa; ?>" target="_blank" class="btn-circle-action bg-success text-white shadow-sm" title="Kirim Pesan WA">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                    <button class="btn-circle-action bg-danger text-white shadow-sm" onclick="bukaDel(<?php echo $r['id']; ?>)" title="Hapus">
                                        <i class="fas fa-trash-alt text-white"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" class="modal-content shadow-lg">
            <div class="modal-header">
                <h5 class="fw-bold text-primary mb-0"><i class="fas fa-shield-alt me-2"></i>Verifikasi Permohonan</h5>
                <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="mid">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="text-muted small fw-bold text-uppercase d-block mb-2">Data Pemohon</label>
                            <p class="mb-1"><strong>NIK:</strong> <span id="vnik"></span></p>
                            <p class="mb-1"><strong>Nama:</strong> <span id="vnama"></span></p>
                            <p class="mb-1"><strong>TTL:</strong> <span id="vttl"></span></p>
                            <p class="mb-0"><strong>WA:</strong> <span class="text-success fw-bold" id="vwa"></span></p>
                            <hr class="my-2">
                            <label class="text-muted small fw-bold text-uppercase d-block mb-1">Alamat</label>
                            <p class="small text-dark mb-0" id="valamat"></p>
                        </div>
                        <div class="info-group mt-3 bg-white border-primary shadow-sm">
                            <label class="text-muted small fw-bold text-uppercase d-block mb-2">Alasan / Keperluan</label>
                            <p class="small mb-0" id="vkeperluan"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold text-uppercase d-block mb-3"><i class="fas fa-folder-open me-1"></i> Lampiran Berkas (Klik untuk buka)</label>
                        <div id="vfiles" class="pe-2" style="max-height: 250px; overflow-y: auto;"></div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="fw-bold small text-primary mb-2 text-uppercase">Tindakan Admin:</label>
                            <select name="status" id="mstat" class="form-select border-primary fw-bold">
                                <option value="Menunggu">Menunggu</option>
                                <option value="Diproses">Diproses</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Ditolak">Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="fw-bold small text-muted mb-2 text-uppercase">Catatan Tambahan:</label>
                            <textarea name="keterangan_admin" id="mcat" class="form-control" rows="2" placeholder="Contoh: Berkas kurang lengkap..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" name="update_status" class="btn btn-primary px-5 py-2 rounded-pill fw-bold">SIMPAN PERUBAHAN</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form method="POST" class="modal-content text-center">
            <input type="hidden" name="id" id="hid">
            <div class="modal-body p-4">
                <div class="text-danger mb-3"><i class="fas fa-trash-alt fa-3x"></i></div>
                <h5 class="fw-bold">Hapus Data?</h5>
                <p class="small text-muted">Aksi ini akan menghapus data dan file dari server selamanya.</p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-100 rounded-pill" data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="delete" class="btn btn-danger w-100 rounded-pill">Ya, Hapus</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function bukaDetail(d) {
    document.getElementById('mid').value = d.id;
    document.getElementById('vnik').innerText = d.nik;
    document.getElementById('vnama').innerText = d.nama;
    document.getElementById('vttl').innerText = d.tempat_tanggal_lahir;
    document.getElementById('vwa').innerText = d.no_wa;
    document.getElementById('valamat').innerText = d.alamat;
    document.getElementById('vkeperluan').innerText = d.keperluan;
    document.getElementById('mstat').value = d.status;
    document.getElementById('mcat').value = d.keterangan_admin || '';

    let box = document.getElementById('vfiles');
    box.innerHTML = "";
    if(d.berkas_syarat) {
        d.berkas_syarat.split(',').forEach((f, i) => {
            box.innerHTML += `
                <a href="../../assets/berkas/${f}" target="_blank" class="file-item-card">
                    <div class="file-icon"><i class="fas fa-file-download"></i></div>
                    <div class="overflow-hidden">
                        <div class="small fw-bold">Berkas ${i+1}</div>
                        <div class="text-muted small text-truncate" style="max-width: 200px;">${f}</div>
                    </div>
                </a>`;
        });
    } else {
        box.innerHTML = "<div class='text-center p-4 bg-light rounded-4 small text-muted fst-italic'>Tidak ada lampiran berkas</div>";
    }
    new bootstrap.Modal(document.getElementById('modalDetail')).show();
}

function bukaDel(id) {
    document.getElementById('hid').value = id;
    new bootstrap.Modal(document.getElementById('modalDel')).show();
}
</script>

<?php $content = ob_get_clean(); include '../../includes/base.php'; ?>