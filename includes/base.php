<?php
// Cek session untuk halaman yang menggunakan template ini
if (!isset($_SESSION['id_admin']) || !isset($_SESSION['nama_admin'])) {
    // Deteksi posisi file untuk menentukan path login yang benar
    $current_file = $_SERVER['SCRIPT_NAME'];
    
    // Tambahkan deteksi folder informasi
    if (strpos($current_file, '/surat/') !== false || 
        strpos($current_file, '/landing/') !== false || 
        strpos($current_file, '/informasi/') !== false) {
        header("Location: ../../login.php");
    } elseif (strpos($current_file, '/admin/') !== false) {
        header("Location: ../login.php");
    } else {
        header("Location: login.php");
    }
    exit();
}

// Deteksi posisi file untuk menentukan path yang benar
$current_file = $_SERVER['SCRIPT_NAME'];

// Tentukan path ke folder includes berdasarkan lokasi file
if (strpos($current_file, '/surat/') !== false || 
    strpos($current_file, '/landing/') !== false || 
    strpos($current_file, '/informasi/') !== false) {
    // File berada di folder admin/surat/, admin/landing/, atau admin/informasi/
    $include_path = '../../includes/';
    $root_path = '../../';
    $login_path = '../../login.php';
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

// Debug (hapus setelah selesai)
// echo "";
// echo "";
// echo "";

// Include file-file template
include $include_path . 'head.php'; 
?>

<div id="wrapper">
    <?php include $include_path . 'sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">

        <div id="content">
            <?php include $include_path . 'navbar.php'; ?>

            <div class="container-fluid">

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                    <?php if(isset($pageHeaderButton)): ?>
                        <?php echo $pageHeaderButton; ?>
                    <?php endif; ?>
                </div>

                <?php echo $content ?? ''; ?>

            </div>
            </div>
        <?php include $include_path . 'footer.php'; ?>

    </div>
    </div>
<?php include $include_path . 'scripts.php'; ?>