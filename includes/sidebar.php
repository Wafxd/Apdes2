<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo $root_path; ?>admin/dashboard.php">
        <div class="sidebar-brand-icon">
            <img src="<?php echo $root_path; ?>img/labang.png" alt="Logo Desa" style="height: 40px;">
        </div>
        <div class="sidebar-brand-text mx-3">APDES <sup>Sukolilo Timur</sup></div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo $root_path; ?>admin/dashboard.php">
            <i class="fas fa-fw fa-home"></i>
            <span>DASHBOARD</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        ADMINISTRASI DESA
    </div>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" id="headingPenduduk" data-toggle="collapse" data-target="#collapsePenduduk" data-bs-toggle="collapse" data-bs-target="#collapsePenduduk" aria-expanded="false" aria-controls="collapsePenduduk">
            <i class="fas fa-fw fa-users"></i>
            <span>DATA PENDUDUK</span>
        </a>
        <div id="collapsePenduduk" class="collapse" aria-labelledby="headingPenduduk" data-parent="#accordionSidebar" data-bs-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Menu Kependudukan</h6>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/penduduk.php">PENDUDUK</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/keluarga.php">KELUARGA</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/kelahiran.php">KELAHIRAN</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/kematian.php">KEMATIAN</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/pindah.php">PINDAH</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/datang.php">DATANG</a>
            </div>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" id="headingLayanan" data-toggle="collapse" data-target="#collapseLayanan" data-bs-toggle="collapse" data-bs-target="#collapseLayanan" aria-expanded="false" aria-controls="collapseLayanan">
            <i class="fas fa-fw fa-hands-helping"></i>
            <span>LAYANAN DESA</span>
        </a>
        <div id="collapseLayanan" class="collapse" aria-labelledby="headingLayanan" data-parent="#accordionSidebar" data-bs-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Jenis Surat</h6>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/surat/domisili.php">DOMISILI</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/surat/usaha.php">USAHA</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/surat/kehilangan.php">KEHILANGAN</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/surat/keterangan.php">KETERANGAN</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/surat/sktm.php">TIDAK MAMPU</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/surat/kelahiran.php">KELAHIRAN</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/surat/kematian.php">KEMATIAN</a>
            </div>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="<?php echo $root_path; ?>admin/surat_keluar.php">
            <i class="fas fa-fw fa-envelope"></i>
            <span>DATA SURAT</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        WEBSITE DESA
    </div>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" id="headingLanding" data-toggle="collapse" data-target="#collapseLanding" data-bs-toggle="collapse" data-bs-target="#collapseLanding" aria-expanded="false" aria-controls="collapseLanding">
            <i class="fas fa-fw fa-globe"></i>
            <span>LANDING PAGE</span>
        </a>
        <div id="collapseLanding" class="collapse" aria-labelledby="headingLanding" data-parent="#accordionSidebar" data-bs-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Kelola Konten</h6>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/landing/home.php">HOME</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/landing/profil.php">PROFIL</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/landing/struktur.php">STRUKTUR</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/landing/layanan.php">LAYANAN</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/landing/kegiatan.php">KEGIATAN</a>
                <a class="collapse-item" href="<?php echo $root_path; ?>admin/landing/kontak.php">KONTAK</a>
            </div>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseInformasi" aria-expanded="true" aria-controls="collapseInformasi">
            <i class="fas fa-fw fa-bullhorn"></i>
            <span>INFORMASI</span>
        </a>
        <div id="collapseInformasi" class="collapse" aria-labelledby="headingInformasi" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Manajemen Publik:</h6>
                <a class="collapse-item" href="/apdessukolilotimur/admin/informasi/dashboard.php">INFORMASI</a>
                <a class="collapse-item" href="/apdessukolilotimur/admin/informasi/permohonan_surat.php">PEMOHON SURAT</a>
                <a class="collapse-item" href="/apdessukolilotimur/admin/informasi/kontak.php">KOTAK MASUK</a>
            </div>
        </div>
    </li>
    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        Sistem
    </div>

    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="<?php echo $root_path; ?>admin/profil.php">
            <i class="fas fa-fw fa-user-circle"></i>
            <span>PROFIL SAYA</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="#" data-toggle="modal" data-target="#logoutModal" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="fas fa-fw fa-sign-out-alt"></i>
            <span>KELUAR</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>