<?php
// Pastikan koneksi dan pengaturan sudah dipanggil
if (!isset($conn)) { include "../db/koneksi.php"; }
if (!isset($pengaturan)) { $pengaturan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_pengaturan LIMIT 1")); }

$nama_desa = $pengaturan['nama_desa'] ?? 'Desa';
$logo = $pengaturan['logo'] ?? 'logo.png';
$current_page = $current_page ?? 'dashboard'; // Default aktif
$is_solid_nav = $is_solid_nav ?? false; // Default transparan
?>

<style>
/* ================= GLOBAL & SHARED CSS ================= */
:root {
    --primary: #1e3c72;
    --secondary: #2a5298;
    --accent: #ffd700;
    --text-dark: #333;
    --text-light: #666;
    --bg-light: #f8f9fa;
}
body { font-family: 'Poppins', sans-serif; color: var(--text-dark); overflow-x: hidden; background: #fff; display: flex; flex-direction: column; min-height: 100vh; }
section { padding: 80px 0; }
.bg-light-custom { background-color: var(--bg-light); }

.section-title { text-align: center; margin-bottom: 50px; }
.section-title h2 { font-weight: 700; color: var(--primary); display: inline-block; position: relative; padding-bottom: 15px; }
.section-title h2::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 60px; height: 4px; background: var(--accent); border-radius: 2px; }
.section-title p { color: var(--text-light); margin-top: 15px; font-size: 1.1rem; }

/* ================= NAVBAR CSS ================= */
.navbar { transition: all 0.4s ease; padding: 20px 0; background: transparent; position: fixed; width: 100%; top: 0; z-index: 1000; }
.navbar.scrolled, .navbar.solid-nav { background: var(--primary); padding: 12px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.navbar-brand { font-weight: 700; color: white !important; font-size: 1.4rem; }
.navbar-brand img { height: 40px; margin-right: 10px; border-radius: 8px; }
.nav-link { color: rgba(255,255,255,0.9) !important; font-weight: 500; margin: 0 5px; position: relative; }
.nav-link.active { color: var(--accent) !important; }
.nav-link::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 0; height: 2px; background: var(--accent); transition: 0.3s; }
.nav-link:hover::after, .nav-link.active::after { width: 100%; }

/* ================= FOOTER CSS ================= */
.footer { background: var(--primary); color: rgba(255,255,255,0.8); padding: 60px 0 20px; margin-top: auto; }
.footer h4 { color: white; font-weight: 600; margin-bottom: 25px; position: relative; padding-bottom: 10px; }
.footer h4::after { content: ''; position: absolute; bottom: 0; left: 0; width: 40px; height: 3px; background: var(--accent); }
.footer a { color: rgba(255,255,255,0.8); text-decoration: none; transition: 0.3s; }
.footer a:hover { color: var(--accent); padding-left: 5px; }
.social-links a { display: inline-block; width: 40px; height: 40px; background: rgba(255,255,255,0.1); text-align: center; line-height: 40px; border-radius: 50%; margin-right: 10px; transition: 0.3s; }
.social-links a:hover { background: var(--accent); color: var(--primary); padding-left: 0; }
.maps-iframe iframe { width: 100%; height: 250px; border-radius: 15px; border: none; }
</style>

<nav class="navbar navbar-expand-lg navbar-dark <?php echo $is_solid_nav ? 'solid-nav' : ''; ?>">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <img src="../assets/images/<?php echo $logo; ?>" alt="Logo">
            <?php echo $nama_desa; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'profil') ? 'active' : ''; ?>" href="profil.php">Profil</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'struktur') ? 'active' : ''; ?>" href="struktur.php">Struktur</a></li>
                 <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'kegiatan') ? 'active' : ''; ?>" href="kegiatan.php">Kegiatan</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'layanan') ? 'active' : ''; ?>" href="layanan.php">Layanan</a></li>
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'permohonan_surat') ? 'active' : ''; ?>" href="permohonan_surat.php">Permohonan Surat</a></li>     
                <li class="nav-item"><a class="nav-link <?php echo ($current_page == 'kontak') ? 'active' : ''; ?>" href="kontak.php">Kontak</a></li>
            </ul>
        </div>
    </div>
</nav>