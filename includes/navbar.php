<?php
// Dapatkan username dari session
$username = isset($_SESSION['nama_admin']) ? htmlspecialchars($_SESSION['nama_admin']) : 'User';

// Pastikan koneksi database sudah include di file pemanggil (koneksi.php)
if (isset($conn)) {
    // ==================== QUERY NOTIFIKASI PESAN KONTAK ====================
    $q_msg = mysqli_query($conn, "SELECT id, nama, email, subjek, pesan, tanggal FROM pesan_kontak WHERE status_baca = 0 ORDER BY tanggal DESC LIMIT 5");
    $total_msg = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM pesan_kontak WHERE status_baca = 0"));

    // ==================== QUERY NOTIFIKASI PERMOHONAN SURAT ====================
    $q_alert = mysqli_query($conn, "SELECT id, nama, jenis_surat, tanggal_pengajuan FROM permohonan_surat WHERE status = 'Menunggu' ORDER BY tanggal_pengajuan DESC LIMIT 5");
    $total_alert = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM permohonan_surat WHERE status = 'Menunggu'"));
} else {
    $total_msg = 0; $total_alert = 0; $q_msg = false; $q_alert = false;
}

// Fungsi helper waktu berlalu (Time Ago)
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        $string = array('y' => 'thn', 'm' => 'bln', 'w' => 'mgg', 'd' => 'hari', 'h' => 'jam', 'i' => 'mnt', 's' => 'dtk');
        foreach ($string as $k => &$v) {
            if ($diff->$k) { $v = $diff->$k . ' ' . $v; } else { unset($string[$k]); }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' lalu' : 'baru saja';
    }
}

// Handler root path untuk link navigasi dinamis
$base_url = isset($root_path) ? $root_path : '../../';
?>

<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button">
                <i class="fas fa-bell fa-fw"></i>
                <span class="badge badge-danger badge-counter" id="badge-surat" style="display: <?php echo $total_alert > 0 ? 'inline-block' : 'none'; ?>;">
                    <?php echo $total_alert > 9 ? '9+' : $total_alert; ?>
                </span>
            </a>
            
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                <h6 class="dropdown-header bg-primary border-primary">ALERTS CENTER (PERMOHONAN SURAT)</h6>
                <?php if ($q_alert && mysqli_num_rows($q_alert) > 0): ?>
                    <?php while ($al = mysqli_fetch_assoc($q_alert)): ?>
                        <a class="dropdown-item d-flex align-items-center" href="<?php echo $base_url; ?>admin/layanan_surat/permohonan_surat.php">
                            <div class="mr-3"><div class="icon-circle bg-warning text-white"><i class="fas fa-file-alt"></i></div></div>
                            <div>
                                <div class="small text-gray-500"><?php echo date('d M Y', strtotime($al['tanggal_pengajuan'])); ?> &bull; <?php echo time_elapsed_string($al['tanggal_pengajuan']); ?></div>
                                <span class="font-weight-bold text-dark">Pengajuan: <?php echo htmlspecialchars($al['jenis_surat']); ?></span><br>
                                <span class="small text-muted">Pemohon: <?php echo htmlspecialchars($al['nama']); ?></span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                    <a class="dropdown-item text-center small text-primary fw-bold" href="<?php echo $base_url; ?>admin/informasi/permohonan_surat.php">Lihat Semua Permohonan</a>
                <?php else: ?>
                    <a class="dropdown-item text-center small text-gray-500 py-4" href="<?php echo $base_url; ?>admin/layanan_surat/permohonan_surat.php">
                        <i class="fas fa-check-circle text-success mb-2" style="font-size: 2.5rem;"></i><br>Tidak ada permohonan surat baru
                    </a>
                <?php endif; ?>
            </div>
        </li>

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button">
                <i class="fas fa-envelope fa-fw"></i>
                <span class="badge badge-danger badge-counter" id="badge-pesan" style="display: <?php echo $total_msg > 0 ? 'inline-block' : 'none'; ?>;">
                    <?php echo $total_msg > 9 ? '9+' : $total_msg; ?>
                </span>
            </a>
            
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="messagesDropdown">
                <h6 class="dropdown-header bg-info border-info">MESSAGE CENTER (PESAN WARGA)</h6>
                <?php if ($q_msg && mysqli_num_rows($q_msg) > 0): ?>
                    <?php while ($msg = mysqli_fetch_assoc($q_msg)): ?>
                        <a class="dropdown-item d-flex align-items-center" href="<?php echo $base_url; ?>admin/informasi/kontak.php">
                            <div class="dropdown-list-image mr-3">
                                <div class="rounded-circle bg-info d-flex align-items-center justify-content-center text-white font-weight-bold" style="width:40px; height:40px;">
                                    <?php echo strtoupper(substr($msg['nama'], 0, 1)); ?>
                                </div>
                                <div class="status-indicator bg-success"></div>
                            </div>
                            <div class="font-weight-bold">
                                <div class="text-truncate text-dark"><?php echo htmlspecialchars($msg['pesan']); ?></div>
                                <div class="small text-gray-500"><?php echo htmlspecialchars($msg['nama']); ?> &bull; <?php echo time_elapsed_string($msg['tanggal']); ?></div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                    <a class="dropdown-item text-center small text-info fw-bold" href="<?php echo $base_url; ?>admin/informasi/kontak.php">Lihat Semua Pesan</a>
                <?php else: ?>
                    <a class="dropdown-item text-center small text-gray-500 py-4" href="<?php echo $base_url; ?>admin/informasi/kontak.php">
                        <i class="fas fa-inbox text-gray-300 mb-2" style="font-size: 2.5rem;"></i><br>Tidak ada pesan baru
                    </a>
                <?php endif; ?>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small font-weight-bold">
                    <i class="fas fa-user-circle mr-1"></i> <?php echo $username; ?>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="<?php echo $base_url; ?>admin/profil.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profil Admin
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-danger"></i> Logout Sistem
                </a>
            </div>
        </li>
    </ul>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // =================================================================
    // 1. PENGAMAN DROPDOWN MANUAL (Mengatasi Konflik Bootstrap 4 vs 5)
    // =================================================================
    var navToggles = document.querySelectorAll('.topbar .dropdown-toggle');
    
    navToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            // Hanya cegah default untuk ikon notifikasi (agar profil user tetap pakai Bootstrap bawaan)
            if(this.id === 'alertsDropdown' || this.id === 'messagesDropdown') {
                e.preventDefault();
                e.stopPropagation();
                
                // Tutup dropdown lain
                var allMenus = document.querySelectorAll('.topbar .dropdown-menu');
                allMenus.forEach(function(menu) {
                    if (menu !== toggle.nextElementSibling) {
                        menu.classList.remove('show');
                    }
                });
                
                // Toggle dropdown ini
                var targetMenu = this.nextElementSibling;
                if (targetMenu) {
                    targetMenu.classList.toggle('show');
                }
            }
        });
    });
    
    // Tutup jika klik sembarang tempat
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.topbar .dropdown')) {
            var allMenus = document.querySelectorAll('.topbar .dropdown-menu');
            allMenus.forEach(function(menu) {
                // Pastikan bukan dropdown profil yang ditutup manual
                if(menu.getAttribute('aria-labelledby') === 'alertsDropdown' || menu.getAttribute('aria-labelledby') === 'messagesDropdown'){
                    menu.classList.remove('show');
                }
            });
        }
    });

    // =================================================================
    // 2. SISTEM AJAX REAL-TIME NOTIFIKASI
    // =================================================================
    function cekNotifikasiRealTime() {
        fetch('<?php echo $base_url; ?>admin/cek_notif.php')
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    // Update Angka Surat
                    let badgeSurat = document.getElementById('badge-surat');
                    if(badgeSurat) {
                        if(data.total_surat > 0) {
                            badgeSurat.style.display = 'inline-block';
                            badgeSurat.innerText = data.total_surat > 9 ? '9+' : data.total_surat;
                        } else {
                            badgeSurat.style.display = 'none';
                        }
                    }

                    // Update Angka Pesan
                    let badgePesan = document.getElementById('badge-pesan');
                    if(badgePesan) {
                        if(data.total_pesan > 0) {
                            badgePesan.style.display = 'inline-block';
                            badgePesan.innerText = data.total_pesan > 9 ? '9+' : data.total_pesan;
                        } else {
                            badgePesan.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => console.error('Error Cek Notifikasi:', error));
    }

    // Jalankan pengecekan setiap 10 detik
    setInterval(cekNotifikasiRealTime, 10000);
});
</script>