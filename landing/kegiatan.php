<?php
include "../db/koneksi.php";

// ==================== AMBIL DATA DARI DATABASE ====================
// Pengaturan Umum (Untuk Navbar & Footer)
$pengaturan_web = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_pengaturan LIMIT 1"));

// Data Kegiatan
$pengaturan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM kegiatan_pengaturan LIMIT 1"));
$kegiatan_utama = mysqli_query($conn, "SELECT * FROM kegiatan_item WHERE is_utama = 1 ORDER BY urutan ASC, id DESC");
$kegiatan_biasa = mysqli_query($conn, "SELECT * FROM kegiatan_item WHERE is_utama = 0 ORDER BY urutan ASC, id DESC");
$testimonis = mysqli_query($conn, "SELECT * FROM kegiatan_testimoni ORDER BY urutan ASC, id DESC");

// Fallback Data
$nama_desa = $pengaturan_web['nama_desa'] ?? 'Desa Sukolilo Timur';
$judul_halaman = $pengaturan['judul_halaman'] ?? 'Kegiatan Desa';
$deskripsi_halaman = $pengaturan['deskripsi_halaman'] ?? 'Berbagai program kerja dan aktivitas yang telah dilaksanakan di desa kami.';
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
        .page-hero { position: relative; min-height: 45vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(30,60,114,0.9) 0%, rgba(42,82,152,0.9) 100%), url('../assets/images/<?php echo $pengaturan_web['hero_bg'] ?? 'hero-bg.jpg'; ?>') center/cover; color: white; padding-top: 80px; text-align: center; }
        .page-hero h1 { font-size: 3rem; font-weight: 700; margin-bottom: 15px; }
        .page-hero p.lead { max-width: 800px; margin: 0 auto; font-size: 1.1rem; opacity: 0.9; }
        .breadcrumb-custom { background: transparent; padding: 0; justify-content: center; margin-top: 20px; margin-bottom: 0; }
        .breadcrumb-custom li { font-size: 1rem; }
        .breadcrumb-custom li a { color: var(--accent); text-decoration: none; }
        .breadcrumb-item+.breadcrumb-item::before { color: rgba(255,255,255,0.5); }

        /* ================= KEGIATAN UTAMA ================= */
        .kegiatan-utama-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 40px; border: 1px solid rgba(0,0,0,0.02); transition: 0.3s; }
        .kegiatan-utama-card:hover { box-shadow: 0 15px 40px rgba(0,0,0,0.1); transform: translateY(-5px); }
        .ku-img-wrapper { height: 100%; min-height: 300px; position: relative; overflow: hidden; }
        .ku-img-wrapper img { width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; transition: 0.5s; }
        .kegiatan-utama-card:hover .ku-img-wrapper img { transform: scale(1.05); }
        .ku-content { padding: 40px; }
        .ku-title { color: var(--primary); font-weight: 700; font-size: 1.8rem; margin-bottom: 15px; }
        .ku-desc { color: var(--text-light); line-height: 1.8; font-size: 1.05rem; margin-bottom: 25px; text-align: justify; }
        
        .ku-meta { background: var(--bg-light); border-radius: 10px; padding: 20px; margin-bottom: 25px; }
        .ku-meta-item { display: flex; align-items: flex-start; margin-bottom: 10px; }
        .ku-meta-item:last-child { margin-bottom: 0; }
        .ku-meta-item i { color: var(--accent); font-size: 1.2rem; margin-right: 15px; margin-top: 3px; width: 20px; text-align: center; }
        .ku-meta-item span { color: var(--text-dark); font-weight: 500; display: block; }
        .ku-meta-item small { color: var(--text-light); display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }

        .ku-detail-list { padding-left: 0; list-style: none; margin-bottom: 0; }
        .ku-detail-list li { position: relative; padding-left: 25px; margin-bottom: 8px; color: var(--text-light); }
        .ku-detail-list li::before { content: '\f058'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; left: 0; top: 3px; color: #28a745; }

        /* ================= GALERI KEGIATAN BIASA ================= */
        .galeri-card { position: relative; border-radius: 15px; overflow: hidden; height: 250px; cursor: pointer; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .galeri-card img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .galeri-card:hover img { transform: scale(1.1); }
        .galeri-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to top, rgba(30,60,114,0.95) 0%, rgba(30,60,114,0.4) 50%, transparent 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 25px; color: white; opacity: 1; transition: 0.3s; }
        .galeri-card:hover .galeri-overlay { background: linear-gradient(to top, rgba(30,60,114,0.95) 0%, rgba(30,60,114,0.6) 100%); }
        .galeri-title { font-weight: 600; font-size: 1.1rem; margin-bottom: 0; transform: translateY(0); transition: 0.3s; }
        .galeri-card:hover .galeri-title { color: var(--accent); }

        /* ================= TESTIMONI ================= */
        .testi-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); height: 100%; position: relative; z-index: 1; border: 1px solid rgba(0,0,0,0.02); transition: 0.3s; }
        .testi-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.08); }
        .testi-icon { position: absolute; top: 20px; right: 30px; font-size: 3rem; color: rgba(30,60,114,0.05); z-index: -1; }
        .testi-quote { font-style: italic; color: var(--text-light); line-height: 1.8; font-size: 1rem; margin-bottom: 20px; position: relative; }
        .testi-author { border-top: 1px dashed #eee; padding-top: 15px; }
        .testi-name { color: var(--primary); font-weight: 700; font-size: 1.1rem; margin-bottom: 2px; }
        .testi-job { color: var(--accent); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

    <?php 
    $current_page = 'kegiatan';
    $is_solid_nav = true; 
    include 'navbar.php'; 
    ?>

    <div class="page-hero">
        <div class="container" data-aos="zoom-in">
            <h1><?php echo htmlspecialchars($judul_halaman); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($deskripsi_halaman); ?></p>
            <ol class="breadcrumb breadcrumb-custom">
                <li class="breadcrumb-item"><a href="dashboard.php">Beranda</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Kegiatan</li>
            </ol>
        </div>
    </div>

    <?php if (mysqli_num_rows($kegiatan_utama) > 0): ?>
    <section id="program-unggulan">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Program Unggulan</h2>
                <p>Detail pelaksanaan kegiatan utama yang berdampak langsung pada masyarakat</p>
            </div>

            <?php 
            $delay = 0; 
            $index = 0;
            while($utama = mysqli_fetch_assoc($kegiatan_utama)): 
                // Logika agar posisi gambar selang-seling (Kiri - Kanan)
                $is_even = ($index % 2 == 0);
            ?>
            <div class="kegiatan-utama-card" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                <div class="row g-0 <?php echo $is_even ? '' : 'flex-row-reverse'; ?>">
                    <div class="col-lg-5">
                        <div class="ku-img-wrapper">
                            <?php if(!empty($utama['gambar'])): ?>
                                <img src="../assets/images/<?php echo $utama['gambar']; ?>" alt="<?php echo htmlspecialchars($utama['judul']); ?>">
                            <?php else: ?>
                                <img src="../assets/images/d4.jpg" alt="Default Image">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="ku-content">
                            <h3 class="ku-title"><?php echo htmlspecialchars($utama['judul']); ?></h3>
                            
                            <?php if(!empty($utama['deskripsi'])): ?>
                            <p class="ku-desc">
                                <?php echo nl2br(htmlspecialchars($utama['deskripsi'])); ?>
                            </p>
                            <?php endif; ?>

                            <div class="ku-meta">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="ku-meta-item">
                                            <i class="far fa-calendar-alt"></i>
                                            <div>
                                                <small>Tanggal</small>
                                                <span><?php echo !empty($utama['tanggal']) ? htmlspecialchars($utama['tanggal']) : '-'; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ku-meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <div>
                                                <small>Lokasi</small>
                                                <span><?php echo !empty($utama['lokasi']) ? htmlspecialchars($utama['lokasi']) : '-'; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <div class="ku-meta-item">
                                            <i class="fas fa-users"></i>
                                            <div>
                                                <small>Peserta / Sasaran</small>
                                                <span><?php echo !empty($utama['peserta']) ? htmlspecialchars($utama['peserta']) : '-'; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if(!empty($utama['detail_kegiatan'])): ?>
                            <h6 class="fw-bold mb-3"><i class="fas fa-clipboard-list text-primary me-2"></i>Rangkaian Kegiatan:</h6>
                            <ul class="ku-detail-list">
                                <?php 
                                // Pisahkan berdasarkan koma atau enter
                                $detail_raw = str_replace("\n", ",", $utama['detail_kegiatan']);
                                $details = explode(",", $detail_raw);
                                foreach($details as $d) {
                                    $d = trim(str_replace('-', '', $d));
                                    if(!empty($d)) {
                                        echo "<li>" . htmlspecialchars($d) . "</li>";
                                    }
                                }
                                ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            $index++;
            endwhile; 
            ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (mysqli_num_rows($kegiatan_biasa) > 0): ?>
    <section id="galeri-kegiatan" class="<?php echo mysqli_num_rows($kegiatan_utama) > 0 ? 'bg-light-custom' : ''; ?>">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Galeri Kegiatan</h2>
                <p>Dokumentasi aktivitas dan program kerja harian di lapangan</p>
            </div>

            <div class="row g-4">
                <?php $delay=0; while($biasa = mysqli_fetch_assoc($kegiatan_biasa)): ?>
                <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="<?php echo $delay; ?>">
                    <div class="galeri-card">
                        <?php if(!empty($biasa['gambar'])): ?>
                            <img src="../assets/images/<?php echo $biasa['gambar']; ?>" alt="<?php echo htmlspecialchars($biasa['judul']); ?>">
                        <?php else: ?>
                            <img src="../assets/images/d4.jpg" alt="Default Image">
                        <?php endif; ?>
                        <div class="galeri-overlay">
                            <h5 class="galeri-title"><?php echo htmlspecialchars($biasa['judul']); ?></h5>
                        </div>
                    </div>
                </div>
                <?php $delay+=100; if($delay > 200) $delay = 0; endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (mysqli_num_rows($testimonis) > 0): ?>
    <section id="testimoni" class="<?php echo mysqli_num_rows($kegiatan_biasa) == 0 && mysqli_num_rows($kegiatan_utama) > 0 ? 'bg-light-custom' : ''; ?>">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Kesan Peserta KKN & Warga</h2>
                <p>Apa kata mereka tentang program kegiatan yang telah berjalan?</p>
            </div>

            <div class="row justify-content-center g-4">
                <?php $delay=0; while($testi = mysqli_fetch_assoc($testimonis)): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                    <div class="testi-card">
                        <i class="fas fa-quote-right testi-icon"></i>
                        <p class="testi-quote">"<?php echo nl2br(htmlspecialchars($testi['kutipan'])); ?>"</p>
                        <div class="testi-author">
                            <h5 class="testi-name"><?php echo htmlspecialchars($testi['nama']); ?></h5>
                            <span class="testi-job"><?php echo htmlspecialchars($testi['jabatan']); ?></span>
                        </div>
                    </div>
                </div>
                <?php $delay+=100; if($delay > 200) $delay = 0; endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

</body>
</html>