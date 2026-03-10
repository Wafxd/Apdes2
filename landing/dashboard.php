<?php
include "../db/koneksi.php";

$pengaturan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_pengaturan LIMIT 1"));
$hero = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_hero LIMIT 1"));
$profil = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_profil LIMIT 1"));

$sliders = mysqli_query($conn, "SELECT * FROM home_slider WHERE status = 1 ORDER BY urutan ASC");
$slide_kegiatans = mysqli_query($conn, "SELECT * FROM home_slide_kegiatan WHERE status = 1 ORDER BY urutan ASC");
$aktivitas = mysqli_query($conn, "SELECT * FROM home_aktivitas WHERE status = 1 ORDER BY urutan ASC");
$faqs = mysqli_query($conn, "SELECT * FROM home_faq WHERE status = 1 ORDER BY urutan ASC");
$statistiks = mysqli_query($conn, "SELECT * FROM home_statistik WHERE status = 1 ORDER BY urutan ASC");
$galeris = mysqli_query($conn, "SELECT * FROM home_galeri WHERE status = 1 ORDER BY urutan ASC");

$nama_desa = $pengaturan['nama_desa'] ?? 'Desa Sukolilo Timur';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - <?php echo $nama_desa; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    
    <style>
        /* CSS Spesifik untuk Halaman Dashboard */
        .hero { position: relative; min-height: 100vh; display: flex; align-items: center; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; padding-top: 80px; }
        .hero-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url('../assets/images/<?php echo $hero['gambar_latar'] ?? 'hero-bg.jpg'; ?>') center/cover; opacity: 0.2; }
        .btn-custom { padding: 12px 30px; border-radius: 50px; font-weight: 600; text-transform: uppercase; transition: 0.3s; }
        .btn-primary-custom { background: var(--accent); color: var(--primary); border: 2px solid var(--accent); }
        .btn-primary-custom:hover { background: transparent; color: var(--accent); transform: translateY(-3px); }
        .btn-outline-custom { background: transparent; color: white; border: 2px solid white; }
        .btn-outline-custom:hover { background: white; color: var(--primary); transform: translateY(-3px); }

        .hero-slider-wrap { margin-top: 50px; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .swiper-slide img { width: 100%; height: 500px; object-fit: cover; }
        .swiper-caption { position: absolute; bottom: 0; left: 0; width: 100%; padding: 40px 30px 20px; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); color: white; }
        
        .kegiatan-swiper .swiper-slide { border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .kegiatan-swiper .swiper-slide img { height: 400px; }

        .hover-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: 0.3s; height: 100%; border-bottom: 4px solid transparent; text-align: center; }
        .hover-card:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); border-bottom-color: var(--accent); }
        .icon-box { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; background: rgba(30, 60, 114, 0.1); color: var(--primary); font-size: 35px; }
        .stat-number { font-size: 2.8rem; font-weight: 700; color: var(--primary); margin-bottom: 5px; }

        .gallery-item { position: relative; border-radius: 15px; overflow: hidden; cursor: pointer; height: 280px; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .gallery-item:hover img { transform: scale(1.1); }
        .gallery-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to top, rgba(30,60,114,0.9), transparent); opacity: 0; transition: 0.3s; display: flex; align-items: flex-end; padding: 25px; color: white; }
        .gallery-item:hover .gallery-overlay { opacity: 1; }

        .accordion-item { border: none; border-radius: 10px !important; margin-bottom: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; }
        .accordion-button { font-weight: 600; color: var(--primary); padding: 20px; }
        .accordion-button:not(.collapsed) { background-color: rgba(30, 60, 114, 0.05); color: var(--primary); box-shadow: none; }
    </style>
</head>
<body>

    <?php 
    $current_page = 'dashboard';
    $is_solid_nav = false; 
    include 'navbar.php'; 
    ?>

    <section class="hero">
        <div class="hero-bg"></div>
        <div class="container position-relative z-2">
            <div class="row justify-content-center text-center">
                <div class="col-lg-10">
                    <h1 class="display-3 fw-bold mb-3" data-aos="fade-up"><?php echo $hero['judul'] ?? 'Selamat Datang'; ?></h1>
                    <h3 class="fw-light mb-4 text-white-50" data-aos="fade-up" data-aos-delay="100"><?php echo $hero['sub_judul'] ?? ''; ?></h3>
                    <p class="lead mb-5 mx-auto" style="max-width: 800px;" data-aos="fade-up" data-aos-delay="200">
                        <?php echo $hero['deskripsi'] ?? ''; ?>
                    </p>
                    <div data-aos="fade-up" data-aos-delay="300">
                        <a href="<?php echo $hero['tombol1_link'] ?? '#'; ?>" class="btn btn-custom btn-primary-custom mx-2 mb-3"><?php echo $hero['tombol1_teks'] ?? 'Peta Desa'; ?></a>
                        <a href="<?php echo $hero['tombol2_link'] ?? '#'; ?>" class="btn btn-custom btn-outline-custom mx-2 mb-3"><?php echo $hero['tombol2_teks'] ?? 'Hubungi Kami'; ?></a>
                    </div>

                    <?php if(mysqli_num_rows($sliders) > 0): ?>
                    <div class="hero-slider-wrap" data-aos="zoom-in" data-aos-delay="500">
                        <div class="swiper main-swiper">
                            <div class="swiper-wrapper">
                                <?php while($slide = mysqli_fetch_assoc($sliders)): ?>
                                <div class="swiper-slide position-relative">
                                    <img src="../assets/images/<?php echo $slide['gambar']; ?>">
                                    <?php if(!empty($slide['judul'])): ?>
                                    <div class="swiper-caption text-start">
                                        <h4><?php echo $slide['judul']; ?></h4>
                                        <p class="mb-0 text-white-50"><?php echo $slide['deskripsi']; ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="swiper-pagination"></div>
                            <div class="swiper-button-next text-white"></div>
                            <div class="swiper-button-prev text-white"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-light-custom">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Statistik Desa</h2>
                <p>Data kependudukan dan informasi terkini <?php echo $nama_desa; ?></p>
            </div>
            <div class="row g-4 justify-content-center">
                <?php if(mysqli_num_rows($statistiks) > 0): ?>
                    <?php $delay=0; while($stat = mysqli_fetch_assoc($statistiks)): ?>
                    <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                        <div class="hover-card">
                            <div class="icon-box">
                                <?php 
                                $icon_name = !empty($stat['icon']) ? str_replace('fa-', '', $stat['icon']) : 'chart-bar';
                                if($icon_name == 'people') $icon_name = 'users'; 
                                ?>
                                <i class="fas fa-<?php echo $icon_name; ?>"></i>
                            </div>
                            <h3 class="stat-number"><?php echo $stat['nilai']; ?></h3>
                            <p class="text-muted fw-bold mb-0"><?php echo $stat['label']; ?></p>
                        </div>
                    </div>
                    <?php $delay+=100; endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <img src="../assets/images/d4.jpg" class="img-fluid rounded-4 shadow-lg" alt="Profil">
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <h2 class="fw-bold mb-4" style="color: var(--primary);"><?php echo $profil['judul'] ?? 'Profil Desa'; ?></h2>
                    <div style="line-height: 1.8; color: var(--text-light); margin-bottom: 25px;">
                        <?php echo nl2br($profil['deskripsi'] ?? ''); ?>
                    </div>
                    <a href="profil.php" class="btn btn-primary rounded-pill px-4 py-2" style="background: var(--primary); border-color: var(--primary);">Lebih Lengkap <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
            </div>
        </div>
    </section>

    <?php if(mysqli_num_rows($aktivitas) > 0): ?>
    <section class="bg-light-custom">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Aktivitas Masyarakat</h2>
                <p>Potensi dan kegiatan utama yang menggerakkan roda ekonomi desa</p>
            </div>
            <div class="row g-4 text-center">
                <?php $delay=0; while($akt = mysqli_fetch_assoc($aktivitas)): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                    <div class="hover-card">
                        <div class="icon-box" style="background: var(--primary); color: white;">
                            <?php if(!empty($akt['gambar'])): ?>
                                <img src="../assets/images/<?php echo $akt['gambar']; ?>" style="border-radius:50%; object-fit:cover; width:100%; height:100%;">
                            <?php elseif(!empty($akt['icon'])): ?>
                                <img src="https://img.icons8.com/?size=80&id=<?php echo $akt['icon']; ?>&format=png" style="filter: brightness(0) invert(1); width: 45px;">
                            <?php else: ?>
                                <i class="fas fa-leaf"></i>
                            <?php endif; ?>
                        </div>
                        <h4 class="fw-bold mb-3" style="color: var(--primary);"><?php echo strtoupper($akt['judul']); ?></h4>
                        <p class="text-muted"><?php echo $akt['deskripsi']; ?></p>
                    </div>
                </div>
                <?php $delay+=100; endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if(mysqli_num_rows($slide_kegiatans) > 0): ?>
    <section style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white;">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2 style="color: white;">Kegiatan Unggulan</h2>
                <p style="color: rgba(255,255,255,0.8);">Program dan dokumentasi kegiatan terbaik desa kami</p>
                <style>.section-title h2::after { background: white; }</style>
            </div>
            
            <div class="swiper kegiatan-swiper" data-aos="zoom-in">
                <div class="swiper-wrapper">
                    <?php while($slide = mysqli_fetch_assoc($slide_kegiatans)): ?>
                    <div class="swiper-slide position-relative">
                        <img src="../assets/images/<?php echo $slide['gambar']; ?>">
                        <div class="swiper-caption">
                            <h3 class="fw-bold"><?php echo $slide['judul']; ?></h3>
                            <p class="mb-0 text-white-50"><?php echo $slide['deskripsi']; ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <div class="swiper-pagination mt-4 position-relative"></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if(mysqli_num_rows($galeris) > 0): ?>
    <section class="bg-light-custom">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Galeri Desa</h2>
                <p>Potret keindahan dan momen berharga di <?php echo $nama_desa; ?></p>
            </div>
            <div class="row g-4">
                <?php $delay=0; while($gal = mysqli_fetch_assoc($galeris)): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                    <div class="gallery-item shadow-sm">
                        <img src="../assets/images/<?php echo $gal['gambar']; ?>">
                        <div class="gallery-overlay flex-column justify-content-end align-items-start">
                            <?php if(!empty($gal['kategori'])): ?>
                                <span class="badge bg-warning text-dark mb-2"><?php echo $gal['kategori']; ?></span>
                            <?php endif; ?>
                            <h5 class="fw-bold mb-0"><?php echo $gal['judul']; ?></h5>
                        </div>
                    </div>
                </div>
                <?php $delay+=100; endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if(mysqli_num_rows($faqs) > 0): ?>
    <section>
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Tanya Jawab (FAQ)</h2>
                <p>Informasi yang sering ditanyakan seputar pelayanan dan desa</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
                    <div class="accordion" id="accordionFAQ">
                        <?php $i=1; while($faq = mysqli_fetch_assoc($faqs)): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                                <button class="accordion-button <?php echo $i==1?'':'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $i; ?>">
                                    <?php echo $faq['pertanyaan']; ?>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $i; ?>" class="accordion-collapse collapse <?php echo $i==1?'show':''; ?>" data-bs-parent="#accordionFAQ">
                                <div class="accordion-body text-muted" style="line-height: 1.8;">
                                    <?php echo nl2br($faq['jawaban']); ?>
                                </div>
                            </div>
                        </div>
                        <?php $i++; endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

</body>
</html>