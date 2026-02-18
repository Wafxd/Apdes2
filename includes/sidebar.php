<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo $root_path; ?>index.php">
        <div class="sidebar-brand-icon">
            <img src="<?php echo $root_path; ?>img/labang.png" alt="Logo Desa" style="height: 40px;">
        </div>
        <div class="sidebar-brand-text mx-3">APDES <sup>Sukolilo Timur</sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo $root_path; ?>dashboard.php">
            <i class="fas fa-fw fa-home"></i>
            <span>DASHBOARD</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        ADMINISTRASI DESA
    </div>

    <!-- Nav Item - Data Penduduk -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePenduduk" 
           aria-expanded="true" aria-controls="collapsePenduduk">
            <i class="fas fa-fw fa-users"></i>
            <span>DATA PENDUDUK</span>
        </a>
        <div id="collapsePenduduk" class="collapse" aria-labelledby="headingPenduduk" 
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Menu Kependudukan</h6>
                <a class="collapse-item" href="<?php echo $root_path; ?>penduduk.php">PENDUDUK</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>keluarga.php">KELUARGA</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Layanan -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLayanan" 
           aria-expanded="true" aria-controls="collapseLayanan">
            <i class="fas fa-fw fa-hands-helping"></i>
            <span>LAYANAN DESA</span>
        </a>
        <div id="collapseLayanan" class="collapse" aria-labelledby="headingLayanan" 
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Jenis Surat</h6>
                <a class="collapse-item" href="<?php echo $root_path; ?>surat/domisili.php">DOMISILI</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>surat/usaha.php">USAHA</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>surat/kehilangan.php">KEHILANGAN</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>surat/keterangan.php">KETERANGAN</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Data Surat -->
    <li class="nav-item">
        <a class="nav-link" href="<?php echo $root_path; ?>surat_keluar.php">
            <i class="fas fa-fw fa-envelope"></i>
            <span>DATA SURAT</span>
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
        <a class="nav-link" href="<?php echo $root_path; ?>pengaturan.php">
            <i class="fas fa-fw fa-cog"></i>
            <span>Pengaturan Sistem</span>
        </a>
    </li>

    <!-- Nav Item - Pengguna -->
    <li class="nav-item">
        <a class="nav-link" href="<?php echo $root_path; ?>pengguna.php">
            <i class="fas fa-fw fa-user-shield"></i>
            <span>Manajemen Pengguna</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

    <!-- Info Desa -->
    <div class="sidebar-card d-none d-lg-flex">
        <div class="card-body p-0">
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
                    loading="lazy">
                </iframe>
            </div>
            
            <div class="px-3 pb-3 pt-2 text-center">
                <a class="btn btn-sm btn-outline-primary" href="<?php echo $root_path; ?>profil-desa.php">
                    <i class="fas fa-info-circle"></i> Profil Desa
                </a>
            </div>
        </div>
    </div>
</ul>
<!-- End of Sidebar -->