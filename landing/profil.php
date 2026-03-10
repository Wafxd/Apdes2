<?php
include "../db/koneksi.php";

$pengaturan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_pengaturan LIMIT 1"));
$tentang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profil_tentang LIMIT 1"));
$visimisi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profil_visi_misi LIMIT 1"));
$dusuns = mysqli_query($conn, "SELECT * FROM profil_dusun ORDER BY nama_dusun ASC");

$nama_desa = $pengaturan['nama_desa'] ?? 'Desa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo $nama_desa; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* CSS Spesifik untuk Halaman Profil */
        .page-hero { position: relative; min-height: 50vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(30,60,114,0.9) 0%, rgba(42,82,152,0.9) 100%), url('../assets/images/<?php echo $pengaturan['hero_bg'] ?? 'hero-bg.jpg'; ?>') center/cover; color: white; padding-top: 80px; text-align: center; }
        .page-hero h1 { font-size: 3.5rem; font-weight: 700; margin-bottom: 15px; }
        .breadcrumb-custom { background: transparent; padding: 0; justify-content: center; margin-bottom: 0; }
        .breadcrumb-custom li { font-size: 1.1rem; }
        .breadcrumb-custom li a { color: var(--accent); text-decoration: none; }
        .breadcrumb-item+.breadcrumb-item::before { color: rgba(255,255,255,0.5); }

        .about-img { border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); position: relative; z-index: 1; }
        .about-text { font-size: 1.1rem; line-height: 1.8; color: var(--text-light); text-align: justify; }
        
        .vm-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); height: 100%; transition: 0.3s; position: relative; overflow: hidden; z-index: 1; border: 1px solid rgba(0,0,0,0.05); }
        .vm-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); }
        .vm-icon { width: 70px; height: 70px; background: rgba(30,60,114,0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; margin-bottom: 25px; }
        .vm-card h3 { color: var(--primary); font-weight: 700; margin-bottom: 20px; }
        .vm-card p { line-height: 1.8; color: var(--text-light); font-size: 1.05rem; }
        .vm-bg-icon { position: absolute; right: -20px; bottom: -20px; font-size: 150px; color: rgba(0,0,0,0.02); z-index: -1; transform: rotate(-15deg); }

        .dusun-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: 100%; transition: 0.3s; border-top: 5px solid var(--primary); }
        .dusun-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-top-color: var(--accent); }
        .dusun-card h4 { color: var(--primary); font-weight: 700; margin-bottom: 15px; }
        .kepala-dusun { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #eee; font-weight: 500; color: #555; }
        .kepala-dusun i { color: var(--accent); font-size: 1.2rem; }
        .dusun-desc { color: var(--text-light); line-height: 1.7; font-size: 0.95rem; }
    </style>
</head>
<body>

    <?php 
    $current_page = 'profil';
    $is_solid_nav = true; // Navbar solid karena ada background statis
    include 'navbar.php'; 
    ?>

    <div class="page-hero">
        <div class="container" data-aos="zoom-in">
            <h1>Profil Desa</h1>
            <ol class="breadcrumb breadcrumb-custom">
                <li class="breadcrumb-item"><a href="dashboard.php">Beranda</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Profil</li>
            </ol>
        </div>
    </div>

    <section>
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <?php if(!empty($tentang['gambar'])): ?>
                        <img src="../assets/images/<?php echo $tentang['gambar']; ?>" class="img-fluid about-img w-100" alt="Tentang Desa">
                    <?php else: ?>
                        <img src="../assets/images/d4.jpg" class="img-fluid about-img w-100" alt="Tentang Desa">
                    <?php endif; ?>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <h2 class="fw-bold mb-4" style="color: var(--primary);">Sejarah & Tentang Kami</h2>
                    <div class="about-text">
                        <?php 
                        if (!empty($tentang['deskripsi'])) {
                            echo nl2br($tentang['deskripsi']); 
                        } else {
                            echo "Belum ada informasi sejarah atau tentang desa yang ditambahkan oleh admin.";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-light-custom">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Visi & Misi</h2>
                <p>Arah dan tujuan pembangunan <?php echo $nama_desa; ?></p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="vm-card">
                        <i class="fas fa-eye vm-bg-icon"></i>
                        <div class="vm-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3>Visi</h3>
                        <p class="fw-medium text-dark fs-5 fst-italic">
                            "<?php echo !empty($visimisi['visi']) ? nl2br($visimisi['visi']) : 'Belum ada data visi.'; ?>"
                        </p>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="vm-card">
                        <i class="fas fa-tasks vm-bg-icon"></i>
                        <div class="vm-icon" style="background: rgba(255,215,0,0.2); color: #d4af37;">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <h3>Misi</h3>
                        <div class="text-muted">
                            <?php 
                            if (!empty($visimisi['misi'])) {
                                $misi_array = explode("\n", $visimisi['misi']);
                                echo '<ul class="list-unstyled">';
                                foreach($misi_array as $m) {
                                    if(trim($m) != '') {
                                        echo '<li class="mb-3 d-flex"><i class="fas fa-check-circle text-success mt-1 me-3"></i> <span>' . trim($m) . '</span></li>';
                                    }
                                }
                                echo '</ul>';
                            } else {
                                echo '<p>Belum ada data misi.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Wilayah Dusun</h2>
                <p>Pembagian wilayah administratif dan potensi dusun di <?php echo $nama_desa; ?></p>
            </div>
            
            <div class="row g-4">
                <?php if (mysqli_num_rows($dusuns) > 0): ?>
                    <?php $delay=0; while($dusun = mysqli_fetch_assoc($dusuns)): ?>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                        <div class="dusun-card">
                            <h4><?php echo htmlspecialchars($dusun['nama_dusun']); ?></h4>
                            <div class="kepala-dusun">
                                <i class="fas fa-user-tie"></i>
                                <span>Kepala Dusun: <strong><?php echo htmlspecialchars($dusun['kepala_dusun']); ?></strong></span>
                            </div>
                            <div class="dusun-desc">
                                <?php echo nl2br(htmlspecialchars($dusun['deskripsi'] ?? '')); ?>
                            </div>
                        </div>
                    </div>
                    <?php $delay+=100; endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center" data-aos="fade-up">
                        <div class="p-5 bg-light rounded-4 border">
                            <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada data dusun yang ditambahkan.</h5>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

</body>
</html>