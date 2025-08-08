<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
        <div class="sidebar-brand-icon">
            <img src="img/labang.png" alt="Logo Desa" style="height: 40px;">
        </div>
        <div class="sidebar-brand-text mx-3">APDES <sup>Sukolilo Timur</sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item active">
        <a class="nav-link" href="index.html">
            <i class="fas fa-fw fa-home"></i>
            <span>Beranda</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Administrasi Desa
    </div>

    <!-- Nav Item - Data Penduduk -->
    <li class="nav-item">
        <a class="nav-link" href="penduduk.html">
            <i class="fas fa-fw fa-users"></i>
            <span>Data Penduduk</span>
        </a>
    </li>

    <!-- Nav Item - Surat Menyurat -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSuratMenyurat" 
        aria-expanded="true" aria-controls="collapseSuratMenyurat">
            <i class="fas fa-fw fa-envelope"></i>
            <span>Surat Menyurat</span>
        </a>
        <div id="collapseSuratMenyurat" class="collapse" aria-labelledby="headingSuratMenyurat" 
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Menu Surat Menyurat:</h6>
                <a class="collapse-item" href="penduduk.php">Penduduk</a>
                <a class="collapse-item" href="keluarga.php">Keluarga</a>
            </div>
        </div>
    </li>


    <!-- Nav Item - Keuangan -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseKeuangan" 
           aria-expanded="true" aria-controls="collapseKeuangan">
            <i class="fas fa-fw fa-money-bill-wave"></i>
            <span>Keuangan Desa</span>
        </a>
        <div id="collapseKeuangan" class="collapse" aria-labelledby="headingKeuangan" 
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Menu Keuangan:</h6>
                <a class="collapse-item" href="apbdes.html">APBDes</a>
                <a class="collapse-item" href="pajak.html">Pajak & Retribusi</a>
                <a class="collapse-item" href="laporan-keuangan.html">Laporan Keuangan</a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Pelayanan Publik
    </div>

    <!-- Nav Item - Layanan -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLayanan" 
           aria-expanded="true" aria-controls="collapseLayanan">
            <i class="fas fa-fw fa-hands-helping"></i>
            <span>Layanan Desa</span>
        </a>
        <div id="collapseLayanan" class="collapse" aria-labelledby="headingLayanan" 
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Jenis Layanan:</h6>
                <a class="collapse-item" href="skck.html">SKCK</a>
                <a class="collapse-item" href="sktm.html">SKTM</a>
                <a class="collapse-item" href="domisili.html">Surat Domisili</a>
                <a class="collapse-item" href="usaha.html">Surat Izin Usaha</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Pengaduan -->
    <li class="nav-item">
        <a class="nav-link" href="pengaduan.html">
            <i class="fas fa-fw fa-comments"></i>
            <span>Pengaduan Masyarakat</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Sistem
    </div>

    <!-- Nav Item - Pengaturan -->
    <li class="nav-item">
        <a class="nav-link" href="pengaturan.html">
            <i class="fas fa-fw fa-cog"></i>
            <span>Pengaturan Sistem</span>
        </a>
    </li>

    <!-- Nav Item - Pengguna -->
    <li class="nav-item">
        <a class="nav-link" href="pengguna.html">
            <i class="fas fa-fw fa-user-shield"></i>
            <span>Manajemen Pengguna</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

    <!-- Info Desa -->
    <div class="sidebar-card d-none d-lg-flex">
        <div class="card-body p-0">
            <!-- Judul dan Alamat -->
            <div class="px-3 pt-3 pb-2 text-center">
                <h6 class="font-weight-bold text-primary mb-1">Lokasi Desa Sukolilo Timur</h6>
                <p class="small mb-2">
                    Kec. Labang, Kab. Bangkalan<br>
                    Jawa Timur
                </p>
            </div>
            
            <!-- Google Maps Embed -->
            <div class="map-container" style="height: 150px; overflow: hidden;">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15835.049713271783!2d112.80982245!3d-7.153444!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd802cd4ffeebe5%3A0x2b7448f8b6785230!2sSukolilo%20Tim.%2C%20Kec.%20Labang%2C%20Kabupaten%20Bangkalan%2C%20Jawa%20Timur!5e0!3m2!1sid!2sid!4v1754634221967!5m2!1sid!2sid" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            
            <!-- Tombol Aksi -->
            <div class="px-3 pb-3 pt-2 text-center">
                <a class="btn btn-sm btn-outline-primary mr-1" href="profil-desa.html">
                    <i class="fas fa-info-circle"></i> Profil Desa
                </a>
            </div>
        </div>
    </div>
</ul>
<!-- End of Sidebar -->