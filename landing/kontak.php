<?php
session_start();
include "../db/koneksi.php";

// ==================== HANDLE PENGIRIMAN PESAN ====================
$pesan_notifikasi = "";
if (isset($_POST['kirim_pesan'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $subjek = mysqli_real_escape_string($conn, $_POST['subjek']);
    $pesan = mysqli_real_escape_string($conn, $_POST['pesan']);

    $query_insert = "INSERT INTO pesan_kontak (nama, email, subjek, pesan, tanggal, status_baca) 
                     VALUES ('$nama', '$email', '$subjek', '$pesan', CURRENT_TIMESTAMP, 0)";
                     
    if (mysqli_query($conn, $query_insert)) {
        $pesan_notifikasi = '<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                                <i class="fas fa-check-circle me-2"></i>Pesan Anda berhasil dikirim! Kami akan segera merespons.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                             </div>';
    } else {
        $pesan_notifikasi = '<div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>Maaf, terjadi kesalahan. Pesan gagal dikirim.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                             </div>';
    }
}

// ==================== AMBIL DATA DARI DATABASE ====================
$pengaturan_web = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_pengaturan LIMIT 1"));
$kontak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_kontak LIMIT 1"));

// Fallback Data
$nama_desa = $pengaturan_web['nama_desa'] ?? 'Desa Sukolilo Timur';
$judul_halaman = $kontak['judul_halaman'] ?? 'HUBUNGI KAMI';
$sub_judul = $kontak['sub_judul'] ?? 'Kantor Desa siap melayani kebutuhan informasi dan administrasi warga';

// Menghapus karakter non-angka untuk link WhatsApp
$no_wa = !empty($kontak['nomor_whatsapp']) ? preg_replace('/[^0-9]/', '', $kontak['nomor_whatsapp']) : '';
if (substr($no_wa, 0, 1) == '0') {
    $no_wa = '62' . substr($no_wa, 1);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($judul_halaman); ?> - <?php echo htmlspecialchars($nama_desa); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* ================= PAGE HERO ================= */
        .page-hero { position: relative; min-height: 40vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(30,60,114,0.9) 0%, rgba(42,82,152,0.9) 100%), url('../assets/images/<?php echo $pengaturan_web['hero_bg'] ?? 'hero-bg.jpg'; ?>') center/cover; color: white; padding-top: 80px; text-align: center; }
        .page-hero h1 { font-size: 3rem; font-weight: 700; margin-bottom: 15px; text-transform: uppercase; }
        .page-hero p.lead { max-width: 800px; margin: 0 auto; font-size: 1.1rem; opacity: 0.9; }
        .breadcrumb-custom { background: transparent; padding: 0; justify-content: center; margin-top: 20px; margin-bottom: 0; }
        .breadcrumb-custom li { font-size: 1rem; }
        .breadcrumb-custom li a { color: var(--accent); text-decoration: none; }
        .breadcrumb-item+.breadcrumb-item::before { color: rgba(255,255,255,0.5); }

        /* ================= INFO KONTAK CARDS ================= */
        .info-card { background: white; border-radius: 15px; padding: 30px 20px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: 0.3s; height: 100%; border: 1px solid rgba(0,0,0,0.02); }
        .info-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); border-color: rgba(30,60,114,0.1); }
        .info-icon { width: 70px; height: 70px; background: rgba(30,60,114,0.05); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 20px; transition: 0.3s; }
        .info-card:hover .info-icon { background: var(--primary); color: white; }
        .info-card h4 { color: var(--primary); font-weight: 700; font-size: 1.2rem; margin-bottom: 15px; }
        .info-card p { color: var(--text-light); line-height: 1.6; margin-bottom: 0; }
        .info-card a { color: var(--text-light); text-decoration: none; transition: 0.3s; }
        .info-card a:hover { color: var(--accent); }

        /* ================= KOTAK FORM PESAN ================= */
        .form-container { background: white; border-radius: 20px; padding: 50px; box-shadow: 0 15px 40px rgba(0,0,0,0.08); position: relative; z-index: 2; margin-top: -50px; }
        .form-title { color: var(--primary); font-weight: 700; margin-bottom: 30px; position: relative; padding-bottom: 15px; }
        .form-title::after { content: ''; position: absolute; bottom: 0; left: 0; width: 60px; height: 4px; background: var(--accent); border-radius: 2px; }
        
        .form-control { border-radius: 10px; padding: 15px; border: 1px solid #e0e0e0; font-size: 0.95rem; transition: 0.3s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 0.25rem rgba(30,60,114,0.1); }
        .form-label { font-weight: 600; color: var(--primary); margin-bottom: 8px; }
        
        .btn-submit { background: var(--primary); color: white; border: none; padding: 15px 30px; border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s; width: 100%; margin-top: 10px; }
        .btn-submit:hover { background: var(--secondary); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(30,60,114,0.2); }

        /* ================= MAPS ================= */
        .maps-wrapper { border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); height: 100%; min-height: 400px; }
        .maps-wrapper iframe { width: 100%; height: 100%; min-height: 400px; border: none; }
    </style>
</head>
<body>

    <?php 
    $current_page = 'kontak';
    $is_solid_nav = true; 
    include 'navbar.php'; 
    ?>

    <div class="page-hero">
        <div class="container" data-aos="zoom-in">
            <h1><?php echo htmlspecialchars($judul_halaman); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($sub_judul); ?></p>
            <ol class="breadcrumb breadcrumb-custom">
                <li class="breadcrumb-item"><a href="dashboard.php">Beranda</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Kontak</li>
            </ol>
        </div>
    </div>

    <section class="bg-light-custom pt-5 pb-5">
        <div class="container">
            <div class="row justify-content-center g-4 position-relative" style="z-index: 3; margin-top: -80px;">
                
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="0">
                    <div class="info-card">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <h4>Alamat</h4>
                        <p><?php echo !empty($kontak['alamat']) ? nl2br(htmlspecialchars($kontak['alamat'])) : 'Alamat belum diatur'; ?></p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-card">
                        <div class="info-icon"><i class="fab fa-whatsapp"></i></div>
                        <h4>Kontak</h4>
                        <p>
                            <a href="https://wa.me/<?php echo $no_wa; ?>" target="_blank" class="fw-bold text-success fs-5">
                                <?php echo htmlspecialchars($kontak['nomor_whatsapp'] ?? '-'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <h4>Email</h4>
                        <p>
                            <a href="mailto:<?php echo htmlspecialchars($kontak['email'] ?? ''); ?>">
                                <?php echo htmlspecialchars($kontak['email'] ?? '-'); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card">
                        <div class="info-icon"><i class="fas fa-clock"></i></div>
                        <h4>Jam Kerja</h4>
                        <p><?php echo htmlspecialchars($kontak['jam_kerja'] ?? '-'); ?></p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            
            <div id="notifikasi">
                <?php echo $pesan_notifikasi; ?>
            </div>

            <div class="row g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="form-container">
                        <h3 class="form-title">Kirim Pesan Langsung</h3>
                        <p class="text-muted mb-4">Punya pertanyaan, kritik, atau saran? Jangan ragu untuk mengisi form di bawah ini. Pesan Anda akan langsung masuk ke sistem admin kami.</p>
                        
                        <form method="POST" action="kontak.php#notifikasi">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama Anda..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Contoh: nama@email.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subjek / Keperluan</label>
                                <input type="text" name="subjek" class="form-control" placeholder="Contoh: Tanya Layanan KTP" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Isi Pesan</label>
                                <textarea name="pesan" class="form-control" rows="5" placeholder="Tuliskan pesan Anda secara detail..." required></textarea>
                            </div>
                            <button type="submit" name="kirim_pesan" class="btn btn-submit">
                                Kirim Pesan <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left">
                    <div class="h-100 d-flex flex-column">
                        <div class="mb-4">
                            <h3 class="fw-bold" style="color: var(--primary);">Lokasi <?php echo htmlspecialchars($nama_desa); ?></h3>
                            <p class="text-muted"><?php echo htmlspecialchars($kontak['deskripsi_lokasi'] ?? 'Akses mudah melalui jalan utama kecamatan.'); ?></p>
                        </div>
                        <div class="maps-wrapper flex-grow-1">
                            <?php 
                            if(!empty($kontak['maps_embed'])) {
                                $maps = $kontak['maps_embed'];
                                // Format ukuran iframe otomatis
                                $maps = preg_replace('/width="[0-9]+"/i', 'width="100%"', $maps);
                                $maps = preg_replace('/height="[0-9]+"/i', 'height="100%"', $maps);
                                echo $maps;
                            } else {
                                echo '<div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center text-muted border rounded">Peta belum diatur</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        // Script agar form tidak ke-submit ulang saat halaman di-refresh
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>
</body>
</html>