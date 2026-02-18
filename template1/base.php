<?php

// Cek session untuk halaman yang menggunakan template ini
if (!isset($_SESSION['id_admin']) || !isset($_SESSION['nama_admin'])) {
    // Deteksi posisi file untuk menentukan path login yang benar
    $current_file = $_SERVER['SCRIPT_NAME'];
    
    if (strpos($current_file, '/surat/') !== false || strpos($current_file, '/admin/') !== false) {
        header("Location: ../login.php");
    } else {
        header("Location: login.php");
    }
    exit();
}

// Deteksi posisi file untuk menentukan path yang benar
$current_file = $_SERVER['SCRIPT_NAME'];

// Tentukan path ke folder includes berdasarkan lokasi file
if (strpos($current_file, '/surat/') !== false) {
    // File berada di folder surat/
    $include_path = '../includes/';
    $root_path = '../';
    $login_path = '../login.php';
} elseif (strpos($current_file, '/admin/') !== false) {
    // File berada di folder admin/
    $include_path = '../includes/';
    $root_path = '../';
    $login_path = '../login.php';
} else {
    // File berada di root folder
    $include_path = 'includes/';
    $root_path = '';
    $login_path = 'login.php';
}

// Pass root_path ke file include
$GLOBALS['root_path'] = $root_path;

include $include_path . 'head.php'; 
?>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include $include_path . 'sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">
            <?php include $include_path . 'navbar.php'; ?>

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                    <?php if(isset($pageHeaderButton)): ?>
                        <?php echo $pageHeaderButton; ?>
                    <?php endif; ?>
                </div>

                <!-- Content Row -->
                <?php echo $content ?? ''; ?>

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content -->

        <?php include $include_path . 'footer.php'; ?>

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<?php include $include_path . 'scripts.php'; ?>