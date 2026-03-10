<?php
include "../db/koneksi.php";

// ==================== AMBIL DATA DARI DATABASE ====================
// Pengaturan Umum & Kontak (Untuk Navbar, Footer, & Tombol WA)
$pengaturan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_pengaturan LIMIT 1"));
$kontak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_kontak LIMIT 1"));

// Data Layanan
$layanans = mysqli_query($conn, "SELECT * FROM layanan_surat ORDER BY urutan ASC, id ASC");
$jams = mysqli_query($conn, "SELECT * FROM layanan_jam ORDER BY urutan ASC, id ASC");

// Fallback Data
$nama_desa = $pengaturan['nama_desa'] ?? 'Desa Sukolilo Timur';
$logo = $pengaturan['logo'] ?? 'logo.png';
$no_wa = !empty($kontak['nomor_whatsapp']) ? preg_replace('/[^0-9]/', '', $kontak['nomor_whatsapp']) : '';
// Jika nomor WA dimulai dengan 0, ganti dengan 62 untuk link API WhatsApp
if (substr($no_wa, 0, 1) == '0') {
    $no_wa = '62' . substr($no_wa, 1);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelayanan Administrasi - <?php echo htmlspecialchars($nama_desa); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

    <style>
        /* ================= PAGE HERO ================= */
        .page-hero { position: relative; min-height: 40vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(30,60,114,0.9) 0%, rgba(42,82,152,0.9) 100%), url('../assets/images/<?php echo $pengaturan['hero_bg'] ?? 'hero-bg.jpg'; ?>') center/cover; color: white; padding-top: 80px; text-align: center; }
        .page-hero h1 { font-size: 3rem; font-weight: 700; margin-bottom: 15px; }
        .breadcrumb-custom { background: transparent; padding: 0; justify-content: center; margin-bottom: 0; }
        .breadcrumb-custom li { font-size: 1.1rem; }
        .breadcrumb-custom li a { color: var(--accent); text-decoration: none; }
        .breadcrumb-item+.breadcrumb-item::before { color: rgba(255,255,255,0.5); }

        /* ================= KARTU LAYANAN SURAT ================= */
        .service-card { background: white; border-radius: 15px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 5px 20px rgba(0,0,0,0.03); transition: 0.3s; position: relative; overflow: hidden; }
        .service-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); border-color: rgba(30,60,114,0.1); }
        .service-icon-wrapper { width: 80px; height: 80px; background: rgba(30,60,114,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; transition: 0.3s; }
        .service-card:hover .service-icon-wrapper { background: var(--primary); }
        .service-icon-wrapper ion-icon { font-size: 35px; color: var(--primary); transition: 0.3s; }
        .service-card:hover .service-icon-wrapper ion-icon { color: white; }
        .service-title { color: var(--primary); font-weight: 700; font-size: 1.1rem; line-height: 1.5; min-height: 50px; }
        
        /* List Persyaratan */
        .req-box { background: var(--bg-light); border-radius: 10px; border: 1px solid #eee; margin-top: 15px; }
        .requirement-list { padding-left: 0; list-style: none; margin-bottom: 0; text-align: left; }
        .requirement-list li { position: relative; padding-left: 25px; margin-bottom: 8px; color: var(--text-light); font-size: 0.9rem; line-height: 1.5; }
        .requirement-list li::before { content: '\f058'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; left: 0; top: 2px; color: var(--accent); }

        /* Tombol Collapse */
        .btn-toggle-req { font-weight: 600; font-size: 0.9rem; border-width: 2px; color: var(--primary); border-color: var(--primary); }
        .btn-toggle-req:hover, .btn-toggle-req:focus, .btn-toggle-req:active { background: var(--primary); color: white; box-shadow: none; }

        /* ================= JAM OPERASIONAL ================= */
        .feature-banner img { border-radius: 20px; box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .time-card { display: flex; align-items: center; padding: 20px; background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.04); margin-bottom: 15px; border-left: 4px solid var(--primary); transition: 0.3s; }
        .time-card:hover { transform: translateX(5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); border-left-color: var(--accent); }
        .time-icon { width: 50px; height: 50px; background: var(--bg-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-right: 20px; flex-shrink: 0; }
        .time-card h5 { color: var(--primary); font-weight: 700; margin-bottom: 5px; font-size: 1.1rem; }
        .time-card p { color: var(--text-light); margin-bottom: 0; font-size: 0.95rem; }
        
        .btn-wa { background: #25D366; color: white; border: none; padding: 12px 30px; border-radius: 50px; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3); }
        .btn-wa:hover { background: #128C7E; color: white; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(37, 211, 102, 0.4); }
    </style>
</head>
<body>

    <?php 
    $current_page = 'layanan';
    $is_solid_nav = true; 
    include 'navbar.php'; 
    ?>

    <div class="page-hero">
        <div class="container" data-aos="zoom-in">
            <h1>Pelayanan Desa</h1>
            <p class="lead text-white-50">Informasi Lengkap Layanan Administrasi <?php echo htmlspecialchars($nama_desa); ?></p>
            <ol class="breadcrumb breadcrumb-custom mt-3">
                <li class="breadcrumb-item"><a href="dashboard.php">Beranda</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Layanan</li>
            </ol>
        </div>
    </div>

    <section class="bg-light-custom">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Layanan Administratif</h2>
                <p>Persyaratan dan prosedur pembuatan surat menyurat di balai desa</p>
            </div>

            <div class="row g-4">
                <?php if (mysqli_num_rows($layanans) > 0): ?>
                    <?php $delay=0; while($layanan = mysqli_fetch_assoc($layanans)): ?>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                        <div class="service-card h-100 d-flex flex-column">
                            <div class="card-body text-center p-4">
                                <div class="service-icon-wrapper">
                                    <ion-icon name="<?php echo htmlspecialchars($layanan['icon']); ?>"></ion-icon>
                                </div>
                                <h3 class="service-title"><?php echo htmlspecialchars($layanan['judul']); ?></h3>
                                
                                <button class="btn btn-outline-primary btn-toggle-req rounded-pill w-100 mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#req_<?php echo $layanan['id']; ?>" aria-expanded="false">
                                    Lihat Persyaratan <i class="fas fa-chevron-down ms-1"></i>
                                </button>
                                
                                <div class="collapse mt-3" id="req_<?php echo $layanan['id']; ?>">
                                    <div class="req-box p-3 text-start">
                                        <div class="fw-bold mb-2 text-dark small">Syarat yang harus dibawa:</div>
                                        <ul class="requirement-list">
                                            <?php 
                                            // Memecah teks persyaratan berdasarkan baris baru (enter)
                                            $syarat_array = explode("\n", $layanan['persyaratan']);
                                            foreach($syarat_array as $syarat) {
                                                $syarat = trim(str_replace('-', '', $syarat));
                                                if(!empty($syarat)) {
                                                    echo "<li>" . htmlspecialchars($syarat) . "</li>";
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $delay+=100; if($delay > 200) $delay = 0; endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center text-muted">
                        <i class="fas fa-folder-open fa-3x mb-3 text-secondary"></i>
                        <h5>Belum ada daftar layanan yang ditambahkan.</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="feature-banner position-relative">
                        <img src="../assets/images/d4.jpg" class="img-fluid w-100" alt="Kantor Desa">
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="mb-4">
                        <span class="text-uppercase fw-bold text-primary" style="letter-spacing: 1px; font-size: 0.9rem;">Informasi Pelayanan</span>
                        <h2 class="fw-bold mt-2" style="color: var(--primary);">Jam Operasional Desa</h2>
                        <p class="text-muted mt-3">Kantor <?php echo htmlspecialchars($nama_desa); ?> melayani berbagai kebutuhan administrasi warga dengan jadwal operasional berikut:</p>
                    </div>

                    <div class="jam-list mt-4">
                        <?php if (mysqli_num_rows($jams) > 0): ?>
                            <?php while($jam = mysqli_fetch_assoc($jams)): ?>
                            <div class="time-card">
                                <div class="time-icon">
                                    <ion-icon name="<?php echo htmlspecialchars($jam['icon']); ?>"></ion-icon>
                                </div>
                                <div>
                                    <h5><?php echo htmlspecialchars($jam['hari']); ?></h5>
                                    <p><?php echo htmlspecialchars($jam['jam']); ?></p>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted fst-italic">Informasi jam kerja belum tersedia.</p>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 pt-2">
                        <p class="text-muted mb-3 small">Butuh informasi lebih lanjut atau ingin membuat janji?</p>
                        <a href="https://wa.me/<?php echo $no_wa; ?>" target="_blank" class="btn-wa">
                            <i class="fab fa-whatsapp" style="font-size: 1.2rem;"></i> Konfirmasi via WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if(!empty($kontak['maps_embed'])): ?>
    <section class="bg-light-custom py-0">
        <div class="container-fluid p-0">
            <div style="width: 100%; height: 400px;">
                <?php 
                // Memodifikasi iframe agar lebarnya 100% dan tingginya pas
                $maps = $kontak['maps_embed'];
                $maps = preg_replace('/width="[0-9]+"/i', 'width="100%"', $maps);
                $maps = preg_replace('/height="[0-9]+"/i', 'height="100%"', $maps);
                echo $maps;
                ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

</body>
</html>