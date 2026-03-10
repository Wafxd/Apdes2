<?php
session_start();
if (!isset($_SESSION['id_admin'])) { header("Location: ../../login.php"); exit(); }
include "../../db/koneksi.php";

$pageTitle = "Pengaturan Data Kontak Publik";

// ==================== HANDLE SIMPAN DATA ====================
if (isset($_POST['save_kontak'])) {
    $judul_halaman = mysqli_real_escape_string($conn, $_POST['judul_halaman']);
    $sub_judul = mysqli_real_escape_string($conn, $_POST['sub_judul']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $nomor_whatsapp = mysqli_real_escape_string($conn, $_POST['nomor_whatsapp']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $jam_kerja = mysqli_real_escape_string($conn, $_POST['jam_kerja']);
    $deskripsi_lokasi = mysqli_real_escape_string($conn, $_POST['deskripsi_lokasi']);
    $maps_embed = mysqli_real_escape_string($conn, $_POST['maps_embed']);
    
    // Format link WA otomatis
    $wa_clean = preg_replace('/[^0-9]/', '', $nomor_whatsapp);
    if (substr($wa_clean, 0, 1) == '0') { $wa_clean = '62' . substr($wa_clean, 1); }
    $link_whatsapp = "https://wa.me/" . $wa_clean;

    $check = mysqli_query($conn, "SELECT id FROM home_kontak LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $query = "UPDATE home_kontak SET judul_halaman='$judul_halaman', sub_judul='$sub_judul', alamat='$alamat', nomor_whatsapp='$nomor_whatsapp', link_whatsapp='$link_whatsapp', email='$email', jam_kerja='$jam_kerja', deskripsi_lokasi='$deskripsi_lokasi', maps_embed='$maps_embed' WHERE id={$row['id']}";
    } else {
        $query = "INSERT INTO home_kontak (judul_halaman, sub_judul, alamat, nomor_whatsapp, link_whatsapp, email, jam_kerja, deskripsi_lokasi, maps_embed) VALUES ('$judul_halaman', '$sub_judul', '$alamat', '$nomor_whatsapp', '$link_whatsapp', '$email', '$jam_kerja', '$deskripsi_lokasi', '$maps_embed')";
    }
    
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Informasi Kontak berhasil disimpan!" : $_SESSION['error_message'] = "Gagal menyimpan data.";
    header("Location: kontak.php");
    exit();
}

$kontak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_kontak LIMIT 1"));
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
                    <h6 class="text-xs font-weight-bold text-primary text-uppercase mb-3 px-2">Kategori Kontak</h6>
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                        <button class="nav-link active text-left" data-bs-toggle="pill" data-bs-target="#tab-header" type="button">
                            <i class="fas fa-heading fa-fw me-2"></i> Teks Header
                        </button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-kontak" type="button">
                            <i class="fas fa-address-book fa-fw me-2"></i> Detail Kontak
                        </button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-peta" type="button">
                            <i class="fas fa-map-marked-alt fa-fw me-2"></i> Lokasi & Maps
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <form method="POST">
                <div class="tab-content" id="v-pills-tabContent">

                    <div class="tab-pane fade show active" id="tab-header">
                        <div class="card content-card">
                            <div class="card-header-modern d-flex align-items-center">
                                <i class="fas fa-heading text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Judul Halaman Hubungi Kami</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label small font-weight-bold">Judul Halaman</label>
                                    <input type="text" class="form-control" name="judul_halaman" value="<?php echo htmlspecialchars($kontak['judul_halaman'] ?? 'HUBUNGI KAMI'); ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small font-weight-bold">Sub Judul / Deskripsi Singkat</label>
                                    <textarea class="form-control" name="sub_judul" rows="3" placeholder="Contoh: Silakan hubungi kami untuk informasi lebih lanjut..." required><?php echo htmlspecialchars($kontak['sub_judul'] ?? ''); ?></textarea>
                                </div>
                                <div class="text-end mt-2">
                                    <button type="submit" name="save_kontak" class="btn btn-primary rounded-pill px-4">
                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-kontak">
                        <div class="card content-card">
                            <div class="card-header-modern d-flex align-items-center">
                                <i class="fas fa-address-book text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Informasi Kontak & Waktu Layanan</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label small font-weight-bold">Nomor WhatsApp</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="fab fa-whatsapp text-success"></i></span>
                                            <input type="text" class="form-control" name="nomor_whatsapp" value="<?php echo htmlspecialchars($kontak['nomor_whatsapp'] ?? ''); ?>" placeholder="0812-xxxx-xxxx" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label small font-weight-bold">Email Resmi Desa</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-danger"></i></span>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($kontak['email'] ?? ''); ?>" placeholder="desa@email.com" required>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-4">
                                        <label class="form-label small font-weight-bold">Jam Operasional</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="fas fa-clock text-primary"></i></span>
                                            <input type="text" class="form-control" name="jam_kerja" value="<?php echo htmlspecialchars($kontak['jam_kerja'] ?? ''); ?>" placeholder="Senin-Jumat: 08.00 - 14.00 WIB" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-2">
                                    <button type="submit" name="save_kontak" class="btn btn-primary rounded-pill px-4">
                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-peta">
                        <div class="card content-card">
                            <div class="card-header-modern d-flex align-items-center">
                                <i class="fas fa-map-marked-alt text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Alamat & Koordinat Peta</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label small font-weight-bold">Alamat Lengkap</label>
                                    <textarea class="form-control" name="alamat" rows="2" placeholder="Masukkan alamat jalan balai desa..." required><?php echo htmlspecialchars($kontak['alamat'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small font-weight-bold">Keterangan Tambahan Lokasi</label>
                                    <textarea class="form-control" name="deskripsi_lokasi" rows="2" placeholder="Contoh: Terletak di depan pasar desa..."><?php echo htmlspecialchars($kontak['deskripsi_lokasi'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small font-weight-bold">Google Maps (Iframe)</label>
                                    <textarea class="form-control font-monospace small" name="maps_embed" rows="4" style="font-size: 12px;" placeholder='<iframe src="..."></iframe>'><?php echo $kontak['maps_embed'] ?? ''; ?></textarea>
                                    <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i>Salin tautan "Embed a map / Sematkan Peta" dari Google Maps.</small>
                                </div>
                                <div class="text-end mt-2">
                                    <button type="submit" name="save_kontak" class="btn btn-primary rounded-pill px-4">
                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> </form>
        </div> </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Fungsi manual untuk menutup alert
function closeAlert(element) {
    const alert = element.closest('.alert');
    if (alert) alert.remove();
}
</script>

<?php
$content = ob_get_clean();
// Memanggil template base (Navbar, Sidebar, dan Footer dikelola di sini)
include '../../includes/base.php';
?>