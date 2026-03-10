<?php
session_start();
include "../db/koneksi.php";

$pesan_notifikasi = "";

if (isset($_POST['kirim_permohonan'])) {
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $ttl = mysqli_real_escape_string($conn, $_POST['tempat_tanggal_lahir']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $no_wa = mysqli_real_escape_string($conn, $_POST['no_wa']);
    $jenis_surat = mysqli_real_escape_string($conn, $_POST['jenis_surat']); // Akan menerima teks input/bebas
    $keperluan = mysqli_real_escape_string($conn, $_POST['keperluan']);
    
    $daftar_nama_file = [];
    $upload_errors = [];
    $target_dir = "../assets/berkas/";

    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    if (!empty($_FILES['berkas_syarat']['name'][0])) {
        foreach ($_FILES['berkas_syarat']['tmp_name'] as $key => $tmp_name) {
            $file_name_orig = $_FILES['berkas_syarat']['name'][$key];
            $file_size = $_FILES['berkas_syarat']['size'][$key];
            $file_tmp = $_FILES['berkas_syarat']['tmp_name'][$key];
            $file_type = strtolower(pathinfo($file_name_orig, PATHINFO_EXTENSION));
            
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            if (!in_array($file_type, $allowed)) {
                $upload_errors[] = "Format $file_name_orig ditolak.";
                continue;
            }
            if ($file_size > 5000000) {
                $upload_errors[] = "$file_name_orig terlalu besar.";
                continue;
            }

            $new_file_name = time() . '_' . rand(100,999) . '_' . str_replace(' ', '_', $file_name_orig);
            if (move_uploaded_file($file_tmp, $target_dir . $new_file_name)) {
                $daftar_nama_file[] = $new_file_name;
            }
        }
    }

    $berkas_final = implode(',', $daftar_nama_file);

    if (empty($upload_errors)) {
        $query = "INSERT INTO permohonan_surat (nik, nama, tempat_tanggal_lahir, alamat, no_wa, jenis_surat, keperluan, berkas_syarat, status) 
                  VALUES ('$nik', '$nama', '$ttl', '$alamat', '$no_wa', '$jenis_surat', '$keperluan', '$berkas_final', 'Menunggu')";
        
        if (mysqli_query($conn, $query)) {
            $pesan_notifikasi = '
            <div class="alert alert-success border-0 shadow-sm rounded-4 p-4" data-aos="zoom-in">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-3x me-3 text-success"></i>
                    <div>
                        <h5 class="fw-bold mb-1">Berhasil Terkirim!</h5>
                        <p class="mb-0 small">Permohonan surat <strong>'.$jenis_surat.'</strong> Anda sedang diverifikasi admin.</p>
                    </div>
                </div>
            </div>';
        }
    } else {
        $pesan_notifikasi = '<div class="alert alert-danger rounded-4">'.implode('<br>', $upload_errors).'</div>';
    }
}

$pengaturan_web = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_pengaturan LIMIT 1"));
$layanans = mysqli_query($conn, "SELECT judul FROM layanan_surat ORDER BY urutan ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permohonan Surat - <?php echo $pengaturan_web['nama_desa']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root { --primary: #1e3c72; --accent: #ffd700; --bg: #f3f6f9; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: #333; }
        .page-header { background: linear-gradient(135deg, var(--primary), #2a5298); color: white; padding: 100px 0 140px; text-align: center; clip-path: polygon(0 0, 100% 0, 100% 85%, 0% 100%); }
        .info-card { background: white; border-radius: 24px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); height: 100%; border: 1px solid rgba(0,0,0,0.05); }
        .step-item { position: relative; padding-left: 60px; margin-bottom: 35px; }
        .step-item::before { content: ""; position: absolute; left: 19px; top: 40px; width: 2px; height: calc(100% - 15px); background: #eee; }
        .step-item:last-child::before { display: none; }
        .step-dot { position: absolute; left: 0; top: 0; width: 40px; height: 40px; background: white; border: 3px solid var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--primary); z-index: 2; }
        .form-card { background: white; border-radius: 24px; padding: 45px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); margin-top: -100px; border: none; }
        .form-label { font-weight: 600; color: #444; font-size: 0.9rem; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 12px; padding: 12px 18px; border: 1.5px solid #eee; background-color: #fcfcfc; transition: 0.3s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(30, 60, 114, 0.05); background-color: white; }
        .upload-area { border: 2px dashed #d1d8e0; border-radius: 16px; padding: 30px; background: #fafafa; cursor: pointer; transition: 0.3s; }
        .upload-area:hover { border-color: var(--primary); background: #f0f4ff; }
        .file-badge { background: white; border: 1px solid #eee; padding: 10px 15px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; margin-top: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .btn-send { background: linear-gradient(to right, var(--primary), #2a5298); color: white; border: none; padding: 16px; border-radius: 14px; font-weight: 700; width: 100%; transition: 0.3s; box-shadow: 0 10px 20px rgba(30, 60, 114, 0.2); }
        .btn-send:hover { transform: translateY(-3px); color: white; }
    </style>
</head>
<body>
    <?php $current_page = 'permohonan_surat'; $is_solid_nav = false; include 'navbar.php'; ?>

    <div class="page-header">
        <div class="container" data-aos="fade-up">
            <h1 class="fw-bold display-5">Layanan Mandiri</h1>
            <p class="opacity-75 fs-5">Permohonan Surat Online Desa <?php echo $nama_desa; ?></p>
        </div>
    </div>

    <section class="container mb-5">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-4 d-none d-lg-block" data-aos="fade-right">
                <div class="info-card">
                    <h4 class="fw-bold mb-4 text-primary">Alur Permohonan</h4>
                    <div class="step-wrapper">
                        <div class="step-item"><div class="step-dot">1</div><h6 class="fw-bold mb-1">Isi Formulir</h6><p class="small text-muted">Input data diri sesuai KTP/KK.</p></div>
                        <div class="step-item"><div class="step-dot">2</div><h6 class="fw-bold mb-1">Lampirkan Berkas</h6><p class="small text-muted">Unggah foto persyaratan secara bertahap.</p></div>
                        <div class="step-item"><div class="step-dot">3</div><h6 class="fw-bold mb-1">Verifikasi</h6><p class="small text-muted">Admin akan memvalidasi data Anda.</p></div>
                        <div class="step-item"><div class="step-dot">4</div><h6 class="fw-bold mb-1">Penerimaan</h6><p class="small text-muted">File dikirim via WhatsApp.</p></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div id="notifikasi"><?php echo $pesan_notifikasi; ?></div>
                <div class="form-card" data-aos="fade-up">
                    <form id="permohonanForm" method="POST" enctype="multipart/form-data">
                        <h5 class="fw-bold mb-4"><i class="fas fa-user-edit me-2 text-primary"></i> Data Identitas</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">NIK Pemohon</label>
                                <input type="number" name="nik" class="form-control" placeholder="16 Digit NIK" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" placeholder="Sesuai KTP" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tempat, Tanggal Lahir</label>
                                <input type="text" name="tempat_tanggal_lahir" class="form-control" placeholder="Contoh: Bangkalan, 01-01-1990" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor WhatsApp Aktif</label>
                                <input type="tel" name="no_wa" class="form-control" placeholder="081234..." required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat Lengkap (Dusun/RT/RW)</label>
                                <textarea name="alamat" class="form-control" rows="2" required></textarea>
                            </div>
                        </div>

                        <h5 class="fw-bold mb-4 mt-5"><i class="fas fa-file-alt me-2 text-primary"></i> Detail Layanan</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Jenis Surat (Pilih dari daftar atau ketik sendiri)</label>
                                <input type="text" name="jenis_surat" list="list_surat" class="form-control" placeholder="Ketik jenis surat atau pilih..." required>
                                <datalist id="list_surat">
                                    <?php while($l = mysqli_fetch_assoc($layanans)) { 
                                        echo "<option value='".htmlspecialchars($l['judul'])."'>"; 
                                    } ?>
                                    <option value="Surat Keterangan Domisili">
                                    <option value="Surat Keterangan Usaha (SKU)">
                                    <option value="Surat Keterangan Tidak Mampu (SKTM)">
                                    <option value="Surat Keterangan Pindah">
                                </datalist>
                                <small class="text-muted">Contoh: Surat Keterangan Ahli Waris</small>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Unggah Berkas Persyaratan (Satu per satu)</label>
                                <div class="upload-area text-center" onclick="document.getElementById('picker').click()">
                                    <i class="fas fa-cloud-arrow-up fa-2x text-primary mb-2"></i>
                                    <p class="mb-0 fw-bold">Klik untuk memilih berkas</p>
                                    <p class="small text-muted mb-0">Format: JPG, PNG, PDF (Maks. 5MB)</p>
                                    <input type="file" id="picker" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                                <input type="file" name="berkas_syarat[]" id="hidden_input" multiple style="display:none">
                                <div id="file_list_ui" class="mt-3"></div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Keperluan Pengurusan Surat</label>
                                <textarea name="keperluan" class="form-control" rows="3" placeholder="Contoh: Untuk persyaratan pendaftaran sekolah..." required></textarea>
                            </div>
                        </div>

                        <div class="mt-5">
                            <button type="submit" name="kirim_permohonan" class="btn btn-send">
                                KIRIM PERMOHONAN SEKARANG <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });

        const picker = document.getElementById('picker');
        const hiddenInput = document.getElementById('hidden_input');
        const uiList = document.getElementById('file_list_ui');
        let keranjangFile = new DataTransfer();

        picker.addEventListener('change', function() {
            for (let file of this.files) { keranjangFile.items.add(file); }
            hiddenInput.files = keranjangFile.files;
            renderFiles();
            this.value = "";
        });

        function renderFiles() {
            uiList.innerHTML = "";
            Array.from(keranjangFile.files).forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'file-badge';
                div.innerHTML = `<span><i class="far fa-file-alt me-2 text-primary"></i>${file.name}</span>
                                 <span class="remove-file" onclick="hapusFile(${index})" style="cursor:pointer; color:red;"><i class="fas fa-times-circle"></i></span>`;
                uiList.appendChild(div);
            });
        }

        function hapusFile(index) {
            const temp = new DataTransfer();
            Array.from(keranjangFile.files).forEach((f, i) => { if (i !== index) temp.items.add(f); });
            keranjangFile = temp;
            hiddenInput.files = keranjangFile.files;
            renderFiles();
        }

        if (window.history.replaceState) { window.history.replaceState(null, null, window.location.href); }
    </script>
</body>
</html>