<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../../login.php");
    exit();
}

include "../../db/koneksi.php";
include "../../db/funct.php"; // Pastikan file ini ada jika diperlukan

$pageTitle = "Kelola Landing Page";

// ==================== FUNGSI UPLOAD GAMBAR ====================
function uploadGambar($file, $old_file = '') {
    $target_dir = "../../assets/images/"; 
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($file["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'File bukan gambar'];
    }
    
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($imageFileType, $allowed)) {
        return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG, GIF & WEBP yang diizinkan'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        $default_files = ['logo.png', 'hero-bg.jpg', 'stats-bg.jpg', 'about-banner.png', 'd4.jpg', 'h1.jpg', 'h2.jpg', 'h4.jpg', 'b1.png', 'b2.webp', 'B3.jpg'];
        if (!empty($old_file) && file_exists($target_dir . $old_file) && !in_array($old_file, $default_files)) {
            unlink($target_dir . $old_file);
        }
        return ['success' => true, 'file_name' => $file_name];
    } else {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }
}

// ==================== HANDLE CRUD OPERATIONS ====================

// HERO SECTION
if (isset($_POST['save_hero'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $sub_judul = mysqli_real_escape_string($conn, $_POST['sub_judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $tombol1_teks = mysqli_real_escape_string($conn, $_POST['tombol1_teks']);
    $tombol1_link = mysqli_real_escape_string($conn, $_POST['tombol1_link']);
    $tombol2_teks = mysqli_real_escape_string($conn, $_POST['tombol2_teks']);
    $tombol2_link = mysqli_real_escape_string($conn, $_POST['tombol2_link']);
    
    $check = mysqli_query($conn, "SELECT id FROM home_hero LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $query = "UPDATE home_hero SET 
                  judul='$judul', sub_judul='$sub_judul', deskripsi='$deskripsi',
                  tombol1_teks='$tombol1_teks', tombol1_link='$tombol1_link',
                  tombol2_teks='$tombol2_teks', tombol2_link='$tombol2_link'
                  WHERE id={$row['id']}";
    } else {
        $query = "INSERT INTO home_hero (judul, sub_judul, deskripsi, tombol1_teks, tombol1_link, tombol2_teks, tombol2_link) 
                  VALUES ('$judul', '$sub_judul', '$deskripsi', '$tombol1_teks', '$tombol1_link', '$tombol2_teks', '$tombol2_link')";
    }
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success_message'] = "Hero section berhasil disimpan";
    } else {
        $_SESSION['error_message'] = "Gagal menyimpan: " . mysqli_error($conn);
    }
    header("Location: home.php");
    exit();
}

// SLIDER
if (isset($_POST['add_slider'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $urutan = intval($_POST['urutan']);
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar']);
        if ($upload['success']) {
            $gambar = $upload['file_name'];
            $query = "INSERT INTO home_slider (judul, deskripsi, gambar, urutan) VALUES ('$judul', '$deskripsi', '$gambar', '$urutan')";
            mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Slider ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
        } else {
            $_SESSION['error_message'] = $upload['message'];
        }
    } else {
        $_SESSION['error_message'] = "Gambar wajib diisi";
    }
    header("Location: home.php");
    exit();
}

if (isset($_POST['edit_slider'])) {
    $id = intval($_POST['id']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $urutan = intval($_POST['urutan']);
    $gambar = $_POST['existing_gambar'] ?? '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar'], $gambar);
        if ($upload['success']) {
            $gambar = $upload['file_name'];
        } else {
            $_SESSION['error_message'] = $upload['message'];
            header("Location: home.php");
            exit();
        }
    }
    
    $query = "UPDATE home_slider SET judul='$judul', deskripsi='$deskripsi', gambar='$gambar', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Slider diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

if (isset($_POST['delete_slider'])) {
    $id = intval($_POST['id']);
    $result = mysqli_query($conn, "SELECT gambar FROM home_slider WHERE id=$id");
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        uploadGambar(['name' => ''], $row['gambar']);
    }
    mysqli_query($conn, "DELETE FROM home_slider WHERE id=$id");
    $_SESSION['success_message'] = "Slider dihapus";
    header("Location: home.php");
    exit();
}

// SLIDE KEGIATAN
if (isset($_POST['add_slide_kegiatan'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $urutan = intval($_POST['urutan']);
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar']);
        if ($upload['success']) {
            $gambar = $upload['file_name'];
            $query = "INSERT INTO home_slide_kegiatan (judul, deskripsi, gambar, urutan) VALUES ('$judul', '$deskripsi', '$gambar', '$urutan')";
            mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Slide kegiatan ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
        } else {
            $_SESSION['error_message'] = $upload['message'];
        }
    } else {
        $_SESSION['error_message'] = "Gambar wajib diisi";
    }
    header("Location: home.php");
    exit();
}

if (isset($_POST['edit_slide_kegiatan'])) {
    $id = intval($_POST['id']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $urutan = intval($_POST['urutan']);
    $gambar = $_POST['existing_gambar'] ?? '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar'], $gambar);
        if ($upload['success']) {
            $gambar = $upload['file_name'];
        } else {
            $_SESSION['error_message'] = $upload['message'];
            header("Location: home.php");
            exit();
        }
    }
    
    $query = "UPDATE home_slide_kegiatan SET judul='$judul', deskripsi='$deskripsi', gambar='$gambar', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Slide kegiatan diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

if (isset($_POST['delete_slide_kegiatan'])) {
    $id = intval($_POST['id']);
    $result = mysqli_query($conn, "SELECT gambar FROM home_slide_kegiatan WHERE id=$id");
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        uploadGambar(['name' => ''], $row['gambar']);
    }
    mysqli_query($conn, "DELETE FROM home_slide_kegiatan WHERE id=$id");
    $_SESSION['success_message'] = "Slide kegiatan dihapus";
    header("Location: home.php");
    exit();
}

// AKTIVITAS
if (isset($_POST['add_aktivitas'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $urutan = intval($_POST['urutan']);
    $gambar = '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar']);
        if ($upload['success']) {
            $gambar = $upload['file_name'];
        }
    }
    
    $query = "INSERT INTO home_aktivitas (judul, deskripsi, icon, gambar, urutan) VALUES ('$judul', '$deskripsi', '$icon', '$gambar', '$urutan')";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Aktivitas ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

if (isset($_POST['edit_aktivitas'])) {
    $id = intval($_POST['id']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $urutan = intval($_POST['urutan']);
    $gambar = $_POST['existing_gambar'] ?? '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar'], $gambar);
        if ($upload['success']) {
            $gambar = $upload['file_name'];
        }
    }
    
    $query = "UPDATE home_aktivitas SET judul='$judul', deskripsi='$deskripsi', icon='$icon', gambar='$gambar', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Aktivitas diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

if (isset($_POST['delete_aktivitas'])) {
    $id = intval($_POST['id']);
    $result = mysqli_query($conn, "SELECT gambar FROM home_aktivitas WHERE id=$id");
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        uploadGambar(['name' => ''], $row['gambar']);
    }
    mysqli_query($conn, "DELETE FROM home_aktivitas WHERE id=$id");
    $_SESSION['success_message'] = "Aktivitas dihapus";
    header("Location: home.php");
    exit();
}

// FAQ
if (isset($_POST['add_faq'])) {
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $jawaban = mysqli_real_escape_string($conn, $_POST['jawaban']);
    $urutan = intval($_POST['urutan']);
    
    $query = "INSERT INTO home_faq (pertanyaan, jawaban, urutan) VALUES ('$pertanyaan', '$jawaban', '$urutan')";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "FAQ ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

if (isset($_POST['edit_faq'])) {
    $id = intval($_POST['id']);
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $jawaban = mysqli_real_escape_string($conn, $_POST['jawaban']);
    $urutan = intval($_POST['urutan']);
    
    $query = "UPDATE home_faq SET pertanyaan='$pertanyaan', jawaban='$jawaban', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "FAQ diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

if (isset($_POST['delete_faq'])) {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM home_faq WHERE id=$id");
    $_SESSION['success_message'] = "FAQ dihapus";
    header("Location: home.php");
    exit();
}

// PROFIL
if (isset($_POST['save_profil'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    $check = mysqli_query($conn, "SELECT id FROM home_profil LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $query = "UPDATE home_profil SET judul='$judul', deskripsi='$deskripsi' WHERE id={$row['id']}";
    } else {
        $query = "INSERT INTO home_profil (judul, deskripsi) VALUES ('$judul', '$deskripsi')";
    }
    
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Profil desa disimpan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

// STATISTIK
if (isset($_POST['add_statistik'])) {
    $label = mysqli_real_escape_string($conn, $_POST['label']);
    $nilai = mysqli_real_escape_string($conn, $_POST['nilai']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $urutan = intval($_POST['urutan']);
    
    $query = "INSERT INTO home_statistik (label, nilai, icon, urutan) VALUES ('$label', '$nilai', '$icon', '$urutan')";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Statistik ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

if (isset($_POST['edit_statistik'])) {
    $id = intval($_POST['id']);
    $label = mysqli_real_escape_string($conn, $_POST['label']);
    $nilai = mysqli_real_escape_string($conn, $_POST['nilai']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $urutan = intval($_POST['urutan']);
    
    $query = "UPDATE home_statistik SET label='$label', nilai='$nilai', icon='$icon', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Statistik diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

if (isset($_POST['delete_statistik'])) {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM home_statistik WHERE id=$id");
    $_SESSION['success_message'] = "Statistik dihapus";
    header("Location: home.php");
    exit();
}

// GALERI
if (isset($_POST['add_galeri'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $urutan = intval($_POST['urutan']);
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar']);
        if ($upload['success']) {
            $gambar = $upload['file_name'];
            $query = "INSERT INTO home_galeri (judul, gambar, kategori, urutan) VALUES ('$judul', '$gambar', '$kategori', '$urutan')";
            mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Galeri ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
        } else {
            $_SESSION['error_message'] = $upload['message'];
        }
    } else {
        $_SESSION['error_message'] = "Gambar wajib diisi";
    }
    header("Location: home.php");
    exit();
}

if (isset($_POST['edit_galeri'])) {
    $id = intval($_POST['id']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $urutan = intval($_POST['urutan']);
    $gambar = $_POST['existing_gambar'] ?? '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar'], $gambar);
        if ($upload['success']) {
            $gambar = $upload['file_name'];
        } else {
            $_SESSION['error_message'] = $upload['message'];
            header("Location: home.php");
            exit();
        }
    }
    
    $query = "UPDATE home_galeri SET judul='$judul', gambar='$gambar', kategori='$kategori', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Galeri diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

if (isset($_POST['delete_galeri'])) {
    $id = intval($_POST['id']);
    $result = mysqli_query($conn, "SELECT gambar FROM home_galeri WHERE id=$id");
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        uploadGambar(['name' => ''], $row['gambar']);
    }
    mysqli_query($conn, "DELETE FROM home_galeri WHERE id=$id");
    $_SESSION['success_message'] = "Galeri dihapus";
    header("Location: home.php");
    exit();
}

// KONTAK
if (isset($_POST['save_kontak'])) {
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $nomor_whatsapp = mysqli_real_escape_string($conn, $_POST['nomor_whatsapp']);
    $link_whatsapp = mysqli_real_escape_string($conn, $_POST['link_whatsapp']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $maps_embed = mysqli_real_escape_string($conn, $_POST['maps_embed']);
    
    $check = mysqli_query($conn, "SELECT id FROM home_kontak LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $query = "UPDATE home_kontak SET alamat='$alamat', nomor_whatsapp='$nomor_whatsapp', link_whatsapp='$link_whatsapp', email='$email', maps_embed='$maps_embed' WHERE id={$row['id']}";
    } else {
        $query = "INSERT INTO home_kontak (alamat, nomor_whatsapp, link_whatsapp, email, maps_embed) VALUES ('$alamat', '$nomor_whatsapp', '$link_whatsapp', '$email', '$maps_embed')";
    }
    
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Kontak disimpan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

// PENGATURAN
if (isset($_POST['save_pengaturan'])) {
    $nama_desa = mysqli_real_escape_string($conn, $_POST['nama_desa']);
    $kecamatan = mysqli_real_escape_string($conn, $_POST['kecamatan']);
    $kabupaten = mysqli_real_escape_string($conn, $_POST['kabupaten']);
    $provinsi = mysqli_real_escape_string($conn, $_POST['provinsi']);
    
    $check = mysqli_query($conn, "SELECT id FROM home_pengaturan LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $query = "UPDATE home_pengaturan SET nama_desa='$nama_desa', kecamatan='$kecamatan', kabupaten='$kabupaten', provinsi='$provinsi' WHERE id={$row['id']}";
    } else {
        $query = "INSERT INTO home_pengaturan (nama_desa, kecamatan, kabupaten, provinsi) VALUES ('$nama_desa', '$kecamatan', '$kabupaten', '$provinsi')";
    }
    
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Pengaturan disimpan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: home.php");
    exit();
}

// ==================== AMBIL DATA ====================
$hero = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_hero LIMIT 1"));
$profil = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_profil LIMIT 1"));
$kontak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_kontak LIMIT 1"));
$pengaturan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_pengaturan LIMIT 1"));

$sliders = mysqli_query($conn, "SELECT * FROM home_slider WHERE status=1 ORDER BY urutan");
$slide_kegiatans = mysqli_query($conn, "SELECT * FROM home_slide_kegiatan WHERE status=1 ORDER BY urutan");
$aktivitas = mysqli_query($conn, "SELECT * FROM home_aktivitas WHERE status=1 ORDER BY urutan");
$faqs = mysqli_query($conn, "SELECT * FROM home_faq WHERE status=1 ORDER BY urutan");
$statistiks = mysqli_query($conn, "SELECT * FROM home_statistik WHERE status=1 ORDER BY urutan");
$galeris = mysqli_query($conn, "SELECT * FROM home_galeri WHERE status=1 ORDER BY urutan");

ob_start();
?>

<style>
    :root { 
        --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%); 
    }
    
    /* Nav Pills (Sidebar Tab) */
    .nav-pills .nav-link { 
        color: #4e73df; 
        font-weight: 600; 
        border-radius: 10px; 
        padding: 12px 20px; 
        transition: 0.3s; 
        margin-bottom: 10px; 
        border: 1px solid transparent; 
    }
    .nav-pills .nav-link.active { 
        background: var(--primary-gradient); 
        border-color: transparent; 
        box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3); 
        color: white;
    }
    .nav-pills .nav-link:hover:not(.active) { 
        background: #f8f9fc; 
        border-color: #d1d3e2; 
    }
    
    /* Card Styles */
    .content-card { 
        border: none; 
        border-radius: 15px; 
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); 
        overflow: hidden; 
        margin-bottom: 25px;
    }
    .card-header-modern { 
        background: white; 
        border-bottom: 1px solid #f1f1f1; 
        padding: 1.25rem 1.5rem; 
    }
    
    /* Table & Image Styling */
    .img-preview-container { 
        position: relative; 
        width: 100px; 
        height: 100px; 
        border-radius: 12px; 
        overflow: hidden; 
        border: 2px solid #eaecf4; 
    }
    .img-preview-container img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
    }
    .preview-image {
        max-width: 100px;
        max-height: 100px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #d1d3e2;
    }
    
    /* Grid List for Kegiatan/Aktivitas/Galeri */
    .grid-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }
    .grid-item {
        background: white;
        border-radius: 15px;
        padding: 15px;
        border: 1px solid #e3e6f0;
        transition: 0.3s;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .grid-item:hover {
        transform: translateY(-5px); 
        box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    }
    .grid-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 15px;
    }

    /* Stats Box */
    .stat-box { 
        background: white; 
        border-radius: 15px; 
        border-left: 5px solid #4e73df; 
        padding: 20px; 
        transition: 0.3s; 
    }
    .stat-box:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 10px 20px rgba(0,0,0,0.05); 
    }
    
    /* Action Buttons */
    .btn-action { 
        border-radius: 8px; 
        padding: 6px 12px; 
        font-weight: 600; 
        transition: 0.3s; 
        border: none; 
    }
    .btn-edit-modern { background: #f6c23e; color: #fff; }
    .btn-delete-modern { background: #e74a3b; color: #fff; }
    .btn-edit-modern:hover, .btn-delete-modern:hover { 
        transform: scale(1.05); 
        filter: brightness(90%); 
        color: #fff; 
    }
</style>

<div class="container-fluid py-3">
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
        <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
        <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card content-card sticky-top" style="top: 20px; z-index: 10;">
                <div class="card-body p-3">
                    <h6 class="text-xs font-weight-bold text-primary text-uppercase mb-3 px-2">Menu Kelola</h6>
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                        <button class="nav-link active text-left" data-bs-toggle="pill" data-bs-target="#tab-hero" type="button"><i class="fas fa-rocket fa-fw me-2"></i> Hero Section</button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-slider" type="button"><i class="fas fa-images fa-fw me-2"></i> Banner Slider</button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-kegiatan" type="button"><i class="fas fa-play-circle fa-fw me-2"></i> Slide Kegiatan</button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-aktivitas" type="button"><i class="fas fa-users fa-fw me-2"></i> Aktivitas</button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-faq" type="button"><i class="fas fa-question-circle fa-fw me-2"></i> FAQ</button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-profil" type="button"><i class="fas fa-building fa-fw me-2"></i> Profil Desa</button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-stats" type="button"><i class="fas fa-chart-pie fa-fw me-2"></i> Statistik</button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-galeri" type="button"><i class="fas fa-camera-retro fa-fw me-2"></i> Galeri Foto</button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-kontak" type="button"><i class="fas fa-phone-alt fa-fw me-2"></i> Kontak & Maps</button>
                        <button class="nav-link text-left" data-bs-toggle="pill" data-bs-target="#tab-pengaturan" type="button"><i class="fas fa-cog fa-fw me-2"></i> Pengaturan Umum</button>
                    </div>
                    <hr>
                    <a href="../../landing/index.php" target="_blank" class="btn btn-outline-primary w-100 btn-sm rounded-pill">
                        <i class="fas fa-external-link-alt me-1"></i> Lihat Live Website
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">
                
                <div class="tab-pane fade show active" id="tab-hero">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex align-items-center">
                            <i class="fas fa-rocket text-primary me-3"></i>
                            <h6 class="m-0 font-weight-bold text-dark">Konfigurasi Hero Section</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small font-weight-bold">Judul Utama</label>
                                        <input type="text" class="form-control" name="judul" value="<?php echo $hero['judul'] ?? 'Selamat Datang di Desa Kami'; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small font-weight-bold">Sub Judul</label>
                                        <input type="text" class="form-control" name="sub_judul" value="<?php echo $hero['sub_judul'] ?? 'Desa yang Asri dan Maju'; ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label small font-weight-bold">Deskripsi</label>
                                        <textarea class="form-control" name="deskripsi" rows="3"><?php echo $hero['deskripsi'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label small font-weight-bold">Tombol 1 Teks</label>
                                        <input type="text" class="form-control" name="tombol1_teks" value="<?php echo $hero['tombol1_teks'] ?? 'Peta Desa'; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label small font-weight-bold">Tombol 1 Link</label>
                                        <input type="text" class="form-control" name="tombol1_link" value="<?php echo $hero['tombol1_link'] ?? '#'; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label small font-weight-bold">Tombol 2 Teks</label>
                                        <input type="text" class="form-control" name="tombol2_teks" value="<?php echo $hero['tombol2_teks'] ?? 'Hubungi Kami'; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label small font-weight-bold">Tombol 2 Link</label>
                                        <input type="text" class="form-control" name="tombol2_link" value="<?php echo $hero['tombol2_link'] ?? '#'; ?>">
                                    </div>
                                </div>
                                <div class="text-end mt-2">
                                    <button type="submit" name="save_hero" class="btn btn-primary px-4 rounded-pill">
                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-slider">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-images text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Banner Slider</h6>
                            </div>
                            <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModal('slider')">
                                <i class="fas fa-plus me-1"></i> Tambah Slide
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr class="text-gray-500 small text-uppercase font-weight-bold">
                                            <th width="15%">Preview</th>
                                            <th width="50%">Informasi</th>
                                            <th width="15%" class="text-center">Urutan</th>
                                            <th width="20%" class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($sliders) > 0): ?>
                                            <?php while($s = mysqli_fetch_assoc($sliders)): ?>
                                            <tr>
                                                <td>
                                                    <div class="img-preview-container shadow-sm">
                                                        <img src="../../assets/images/<?= $s['gambar']; ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="font-weight-bold text-dark"><?= $s['judul'] ?: '-'; ?></div>
                                                    <div class="small text-muted text-truncate" style="max-width: 300px;"><?= $s['deskripsi']; ?></div>
                                                </td>
                                                <td class="text-center"><span class="badge bg-light text-primary border"><?= $s['urutan']; ?></span></td>
                                                <td class="text-end">
                                                    <button class="btn btn-action btn-edit-modern btn-sm" onclick='editSlider(<?= json_encode($s); ?>)'><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-action btn-delete-modern btn-sm" onclick="confirmDelete('slider', <?= $s['id']; ?>)"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada data slider</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-kegiatan">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-play-circle text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Slide Kegiatan</h6>
                            </div>
                            <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModal('slide_kegiatan')">
                                <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="grid-list">
                                <?php if (mysqli_num_rows($slide_kegiatans) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($slide_kegiatans)): ?>
                                    <div class="grid-item position-relative">
                                        <img src="../../assets/images/<?php echo $row['gambar']; ?>">
                                        <h6 class="font-weight-bold text-dark mb-1"><?php echo $row['judul']; ?></h6>
                                        <p class="text-muted small mb-2 text-truncate" style="max-width: 100%;"><?php echo $row['deskripsi']; ?></p>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                            <span class="badge bg-light text-primary border">Urutan: <?php echo $row['urutan']; ?></span>
                                            <div>
                                                <button class="btn btn-action btn-edit-modern btn-sm py-1 px-2" onclick='editSlideKegiatan(<?= json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-action btn-delete-modern btn-sm py-1 px-2" onclick="confirmDelete('slide_kegiatan', <?= $row['id']; ?>)"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-center py-4 w-100 text-muted">Belum ada data slide kegiatan</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-aktivitas">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Aktivitas Masyarakat</h6>
                            </div>
                            <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModal('aktivitas')">
                                <i class="fas fa-plus me-1"></i> Tambah Aktivitas
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="grid-list">
                                <?php if (mysqli_num_rows($aktivitas) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($aktivitas)): ?>
                                    <div class="grid-item">
                                        <?php if(!empty($row['gambar'])): ?>
                                            <img src="../../assets/images/<?php echo $row['gambar']; ?>">
                                        <?php elseif(!empty($row['icon'])): ?>
                                            <div class="text-center py-4 bg-light rounded mb-3 border">
                                                <img src="https://img.icons8.com/?size=80&id=<?php echo $row['icon']; ?>&format=png" style="width:60px; height:auto;">
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-4 bg-light rounded mb-3 border">
                                                <i class="fas fa-leaf fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <h6 class="font-weight-bold text-dark mb-1"><?php echo strtoupper($row['judul']); ?></h6>
                                        <p class="text-muted small mb-2 text-truncate" style="max-width: 100%;"><?php echo $row['deskripsi']; ?></p>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                            <span class="badge bg-light text-primary border">Urutan: <?php echo $row['urutan']; ?></span>
                                            <div>
                                                <button class="btn btn-action btn-edit-modern btn-sm py-1 px-2" onclick='editAktivitas(<?= json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-action btn-delete-modern btn-sm py-1 px-2" onclick="confirmDelete('aktivitas', <?= $row['id']; ?>)"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-center py-4 w-100 text-muted">Belum ada data aktivitas</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-faq">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-question-circle text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Kelola FAQ</h6>
                            </div>
                            <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModal('faq')">
                                <i class="fas fa-plus me-1"></i> Tambah FAQ
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr class="text-gray-500 small text-uppercase font-weight-bold">
                                            <th width="30%">Pertanyaan</th>
                                            <th width="45%">Jawaban</th>
                                            <th width="10%" class="text-center">Urutan</th>
                                            <th width="15%" class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($faqs) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($faqs)): ?>
                                            <tr>
                                                <td class="font-weight-bold text-dark"><?php echo $row['pertanyaan']; ?></td>
                                                <td class="small text-muted"><?php echo substr($row['jawaban'], 0, 100); ?>...</td>
                                                <td class="text-center"><span class="badge bg-light text-primary border"><?php echo $row['urutan']; ?></span></td>
                                                <td class="text-end">
                                                    <button class="btn btn-action btn-edit-modern btn-sm" onclick='editFaq(<?= json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-action btn-delete-modern btn-sm" onclick="confirmDelete('faq', <?= $row['id']; ?>)"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada data FAQ</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-profil">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex align-items-center">
                            <i class="fas fa-building text-primary me-3"></i>
                            <h6 class="m-0 font-weight-bold text-dark">Profil Desa</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label small font-weight-bold">Judul Profil</label>
                                    <input type="text" class="form-control" name="judul" value="<?php echo $profil['judul'] ?? 'Profil Desa Kami'; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small font-weight-bold">Deskripsi Profil</label>
                                    <textarea class="form-control" name="deskripsi" rows="10" required><?php echo $profil['deskripsi'] ?? ''; ?></textarea>
                                </div>
                                <div class="text-end mt-4">
                                    <button type="submit" name="save_profil" class="btn btn-primary px-4 rounded-pill">
                                        <i class="fas fa-save me-2"></i>Simpan Profil
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-stats">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-chart-pie text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Statistik Desa</h6>
                            </div>
                            <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModal('statistik')">
                                <i class="fas fa-plus me-1"></i> Tambah Statistik
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php if (mysqli_num_rows($statistiks) > 0): ?>
                                    <?php while($st = mysqli_fetch_assoc($statistiks)): ?>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="stat-box shadow-sm border h-100 position-relative">
                                            <div class="d-flex justify-content-between mb-3">
                                                <div class="badge bg-primary rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-<?= $st['icon'] ?: 'database'; ?> text-white fs-5"></i>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light p-1 border rounded" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v fa-fw text-muted"></i></button>
                                                    <ul class="dropdown-menu shadow border-0">
                                                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick='editStatistik(<?= json_encode($st); ?>)'><i class="fas fa-edit me-2 text-warning"></i>Edit</a></li>
                                                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="confirmDelete('statistik', <?= $st['id']; ?>)"><i class="fas fa-trash me-2 text-danger"></i>Hapus</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <h3 class="font-weight-bold text-dark mb-0"><?= $st['nilai']; ?></h3>
                                            <p class="text-xs text-uppercase font-weight-bold text-muted mb-2"><?= $st['label']; ?></p>
                                            <span class="badge bg-light text-secondary border mt-2">Urutan: <?= $st['urutan']; ?></span>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-center py-4 w-100 text-muted">Belum ada data statistik</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-galeri">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-camera-retro text-primary me-3"></i>
                                <h6 class="m-0 font-weight-bold text-dark">Galeri Foto</h6>
                            </div>
                            <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openModal('galeri')">
                                <i class="fas fa-plus me-1"></i> Tambah Foto
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="grid-list">
                                <?php if (mysqli_num_rows($galeris) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($galeris)): ?>
                                    <div class="grid-item">
                                        <img src="../../assets/images/<?php echo $row['gambar']; ?>">
                                        <h6 class="font-weight-bold text-dark mb-1"><?php echo $row['judul']; ?></h6>
                                        <p class="text-muted small mb-2"><i class="fas fa-tag me-1 text-primary"></i><?php echo $row['kategori'] ?: '-'; ?></p>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                            <span class="badge bg-light text-primary border">Urutan: <?php echo $row['urutan']; ?></span>
                                            <div>
                                                <button class="btn btn-action btn-edit-modern btn-sm py-1 px-2" onclick='editGaleri(<?= json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-action btn-delete-modern btn-sm py-1 px-2" onclick="confirmDelete('galeri', <?= $row['id']; ?>)"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-center py-4 w-100 text-muted">Belum ada foto galeri</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-kontak">
                    <div class="card content-card">
                        <div class="card-header-modern d-flex align-items-center">
                            <i class="fas fa-phone-alt text-primary me-3"></i>
                            <h6 class="m-0 font-weight-bold text-dark">Kontak & Peta Lokasi</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold form-label">Nomor WhatsApp</label>
                                        <input type="text" class="form-control" name="nomor_whatsapp" value="<?= $kontak['nomor_whatsapp'] ?? '+628123456789'; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold form-label">Link WhatsApp (wa.me/...)</label>
                                        <input type="text" class="form-control" name="link_whatsapp" value="<?= $kontak['link_whatsapp'] ?? '#'; ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="small font-weight-bold form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?= $kontak['email'] ?? ''; ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="small font-weight-bold form-label">Alamat Lengkap</label>
                                        <textarea class="form-control" name="alamat" rows="2"><?= $kontak['alamat'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="small font-weight-bold form-label">Embed Google Maps (Iframe)</label>
                                        <textarea class="form-control font-monospace small" name="maps_embed" rows="4" style="font-size: 13px;"><?= $kontak['maps_embed'] ?? ''; ?></textarea>
                                    </div>
                                </div>
                                <div class="text-end mt-4">
                                    <button type="submit" name="save_kontak" class="btn btn-primary rounded-pill px-4">
                                        <i class="fas fa-save me-2"></i>Simpan Kontak
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-pengaturan">
                    <div class="card content-card mb-5">
                        <div class="card-header-modern d-flex align-items-center">
                            <i class="fas fa-cog text-primary me-3"></i>
                            <h6 class="m-0 font-weight-bold text-dark">Pengaturan Umum</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small font-weight-bold">Nama Desa</label>
                                        <input type="text" class="form-control" name="nama_desa" value="<?php echo $pengaturan['nama_desa'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small font-weight-bold">Kecamatan</label>
                                        <input type="text" class="form-control" name="kecamatan" value="<?php echo $pengaturan['kecamatan'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small font-weight-bold">Kabupaten</label>
                                        <input type="text" class="form-control" name="kabupaten" value="<?php echo $pengaturan['kabupaten'] ?? ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small font-weight-bold">Provinsi</label>
                                        <input type="text" class="form-control" name="provinsi" value="<?php echo $pengaturan['provinsi'] ?? ''; ?>" required>
                                    </div>
                                </div>
                                <div class="text-end mt-2">
                                    <button type="submit" name="save_pengaturan" class="btn btn-primary rounded-pill px-4">
                                        <i class="fas fa-save me-2"></i>Simpan Pengaturan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div> </div> </div> </div> <div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <form method="POST" enctype="multipart/form-data" id="modalForm">
                <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title font-weight-bold" id="modalTitle"><i class="fas fa-plus me-2"></i>Tambah Data</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModal()" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="modalBody">
                    </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="modalSubmitBtn"><i class="fas fa-save me-2"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <div class="modal-header bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-trash me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeDeleteModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 px-4 text-center">
                <i class="fas fa-exclamation-circle fa-4x text-danger mb-3"></i>
                <h5 class="mb-2 text-dark font-weight-bold">Apakah Anda yakin?</h5>
                <p class="text-muted mb-0">Data yang dihapus tidak dapat dikembalikan lagi.</p>
            </div>
            <div class="modal-footer bg-light justify-content-center" style="border-radius: 0 0 15px 15px;">
                <form method="POST" id="deleteForm" class="w-100 d-flex justify-content-between px-3">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeDeleteModal()">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" id="deleteSubmitBtn">Ya, Hapus Data</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Fungsi Modal, Tutup Alert, dan Generasi Form tetap diambil dari script aslimu.
let actionModalInstance = null;
let deleteModalInstance = null;

function closeModal() { if (actionModalInstance) actionModalInstance.hide(); }
function closeDeleteModal() { if (deleteModalInstance) deleteModalInstance.hide(); }
function closeAlert(element) { const alert = element.closest('.alert'); if (alert) alert.remove(); }

document.addEventListener('DOMContentLoaded', function() {
    const actionModalEl = document.getElementById('actionModal');
    const deleteModalEl = document.getElementById('deleteModal');
    if (actionModalEl) actionModalInstance = new bootstrap.Modal(actionModalEl);
    if (deleteModalEl) deleteModalInstance = new bootstrap.Modal(deleteModalEl);
});

function openModal(type) {
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalForm = document.getElementById('modalForm');
    const modalSubmitBtn = document.getElementById('modalSubmitBtn');
    
    modalTitle.innerHTML = '<i class="fas fa-plus me-2"></i>Tambah ' + getTypeName(type);
    modalForm.action = window.location.href;
    modalSubmitBtn.name = 'add_' + type;
    
    let html = '';
    switch(type) {
        case 'slider':
            html = `<input type="hidden" name="id" id="slider_id">
                <div class="mb-3"><label class="form-label fw-bold">Gambar</label><input type="file" class="form-control" name="gambar" accept="image/*" required><input type="hidden" name="existing_gambar" id="slider_existing"><div id="slider_preview" class="mt-2"></div></div>
                <div class="mb-3"><label class="form-label fw-bold">Judul</label><input type="text" class="form-control" name="judul" id="slider_judul"></div>
                <div class="mb-3"><label class="form-label fw-bold">Deskripsi</label><textarea class="form-control" name="deskripsi" id="slider_deskripsi" rows="3"></textarea></div>
                <div class="mb-3"><label class="form-label fw-bold">Urutan</label><input type="number" class="form-control" name="urutan" id="slider_urutan" value="0"></div>`;
            break;
        case 'slide_kegiatan':
            html = `<input type="hidden" name="id" id="slide_id">
                <div class="mb-3"><label class="form-label fw-bold">Gambar</label><input type="file" class="form-control" name="gambar" accept="image/*" required><input type="hidden" name="existing_gambar" id="slide_existing"><div id="slide_preview" class="mt-2"></div></div>
                <div class="mb-3"><label class="form-label fw-bold">Judul</label><input type="text" class="form-control" name="judul" id="slide_judul" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Deskripsi</label><textarea class="form-control" name="deskripsi" id="slide_deskripsi" rows="3"></textarea></div>
                <div class="mb-3"><label class="form-label fw-bold">Urutan</label><input type="number" class="form-control" name="urutan" id="slide_urutan" value="0"></div>`;
            break;
        case 'aktivitas':
            html = `<input type="hidden" name="id" id="aktivitas_id">
                <div class="mb-3"><label class="form-label fw-bold">Judul</label><input type="text" class="form-control" name="judul" id="aktivitas_judul" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Deskripsi</label><textarea class="form-control" name="deskripsi" id="aktivitas_deskripsi" rows="3"></textarea></div>
                <div class="mb-3"><label class="form-label fw-bold">Icon (ID Icons8)</label><input type="text" class="form-control" name="icon" id="aktivitas_icon" placeholder="Contoh: 47584"><small class="text-muted d-block">Kosongkan jika pakai gambar</small></div>
                <div class="mb-3"><label class="form-label fw-bold">Gambar (opsional)</label><input type="file" class="form-control" name="gambar" accept="image/*"><input type="hidden" name="existing_gambar" id="aktivitas_existing"><div id="aktivitas_preview" class="mt-2"></div></div>
                <div class="mb-3"><label class="form-label fw-bold">Urutan</label><input type="number" class="form-control" name="urutan" id="aktivitas_urutan" value="0"></div>`;
            break;
        case 'faq':
            html = `<input type="hidden" name="id" id="faq_id">
                <div class="mb-3"><label class="form-label fw-bold">Pertanyaan</label><input type="text" class="form-control" name="pertanyaan" id="faq_pertanyaan" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Jawaban</label><textarea class="form-control" name="jawaban" id="faq_jawaban" rows="5" required></textarea></div>
                <div class="mb-3"><label class="form-label fw-bold">Urutan</label><input type="number" class="form-control" name="urutan" id="faq_urutan" value="0"></div>`;
            break;
        case 'statistik':
            html = `<input type="hidden" name="id" id="statistik_id">
                <div class="mb-3"><label class="form-label fw-bold">Label</label><input type="text" class="form-control" name="label" id="statistik_label" placeholder="Contoh: Jiwa Penduduk" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Nilai</label><input type="text" class="form-control" name="nilai" id="statistik_nilai" placeholder="Contoh: 3.500" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Icon FontAwesome</label><input type="text" class="form-control" name="icon" id="statistik_icon" placeholder="Contoh: users"></div>
                <div class="mb-3"><label class="form-label fw-bold">Urutan</label><input type="number" class="form-control" name="urutan" id="statistik_urutan" value="0"></div>`;
            break;
        case 'galeri':
            html = `<input type="hidden" name="id" id="galeri_id">
                <div class="mb-3"><label class="form-label fw-bold">Gambar</label><input type="file" class="form-control" name="gambar" accept="image/*" required><input type="hidden" name="existing_gambar" id="galeri_existing"><div id="galeri_preview" class="mt-2"></div></div>
                <div class="mb-3"><label class="form-label fw-bold">Judul</label><input type="text" class="form-control" name="judul" id="galeri_judul" required></div>
                <div class="mb-3"><label class="form-label fw-bold">Kategori</label><input type="text" class="form-control" name="kategori" id="galeri_kategori" placeholder="Contoh: Pertanian"></div>
                <div class="mb-3"><label class="form-label fw-bold">Urutan</label><input type="number" class="form-control" name="urutan" id="galeri_urutan" value="0"></div>`;
            break;
    }
    
    modalBody.innerHTML = html;
    if (actionModalInstance) actionModalInstance.show();
}

function getTypeName(type) {
    const names = { 'slider': 'Slider', 'slide_kegiatan': 'Slide Kegiatan', 'aktivitas': 'Aktivitas', 'faq': 'FAQ', 'statistik': 'Statistik', 'galeri': 'Galeri' };
    return names[type] || type;
}

function editSlider(data) {
    openModal('slider');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Slider';
    document.getElementById('slider_id').value = data.id;
    document.getElementById('slider_judul').value = data.judul || '';
    document.getElementById('slider_deskripsi').value = data.deskripsi || '';
    document.getElementById('slider_urutan').value = data.urutan || 0;
    document.getElementById('slider_existing').value = data.gambar || '';
    if(data.gambar) document.getElementById('slider_preview').innerHTML = '<img src="../../assets/images/' + data.gambar + '" class="preview-image mt-2">';
    document.getElementById('modalSubmitBtn').name = 'edit_slider';
}

function editSlideKegiatan(data) {
    openModal('slide_kegiatan');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Slide Kegiatan';
    document.getElementById('slide_id').value = data.id;
    document.getElementById('slide_judul').value = data.judul || '';
    document.getElementById('slide_deskripsi').value = data.deskripsi || '';
    document.getElementById('slide_urutan').value = data.urutan || 0;
    document.getElementById('slide_existing').value = data.gambar || '';
    if(data.gambar) document.getElementById('slide_preview').innerHTML = '<img src="../../assets/images/' + data.gambar + '" class="preview-image mt-2">';
    document.getElementById('modalSubmitBtn').name = 'edit_slide_kegiatan';
}

function editAktivitas(data) {
    openModal('aktivitas');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Aktivitas';
    document.getElementById('aktivitas_id').value = data.id;
    document.getElementById('aktivitas_judul').value = data.judul || '';
    document.getElementById('aktivitas_deskripsi').value = data.deskripsi || '';
    document.getElementById('aktivitas_icon').value = data.icon || '';
    document.getElementById('aktivitas_urutan').value = data.urutan || 0;
    document.getElementById('aktivitas_existing').value = data.gambar || '';
    if(data.gambar) document.getElementById('aktivitas_preview').innerHTML = '<img src="../../assets/images/' + data.gambar + '" class="preview-image mt-2">';
    document.getElementById('modalSubmitBtn').name = 'edit_aktivitas';
}

function editFaq(data) {
    openModal('faq');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit FAQ';
    document.getElementById('faq_id').value = data.id;
    document.getElementById('faq_pertanyaan').value = data.pertanyaan || '';
    document.getElementById('faq_jawaban').value = data.jawaban || '';
    document.getElementById('faq_urutan').value = data.urutan || 0;
    document.getElementById('modalSubmitBtn').name = 'edit_faq';
}

function editStatistik(data) {
    openModal('statistik');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Statistik';
    document.getElementById('statistik_id').value = data.id;
    document.getElementById('statistik_label').value = data.label || '';
    document.getElementById('statistik_nilai').value = data.nilai || '';
    document.getElementById('statistik_icon').value = data.icon || '';
    document.getElementById('statistik_urutan').value = data.urutan || 0;
    document.getElementById('modalSubmitBtn').name = 'edit_statistik';
}

function editGaleri(data) {
    openModal('galeri');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Galeri';
    document.getElementById('galeri_id').value = data.id;
    document.getElementById('galeri_judul').value = data.judul || '';
    document.getElementById('galeri_kategori').value = data.kategori || '';
    document.getElementById('galeri_urutan').value = data.urutan || 0;
    document.getElementById('galeri_existing').value = data.gambar || '';
    if(data.gambar) document.getElementById('galeri_preview').innerHTML = '<img src="../../assets/images/' + data.gambar + '" class="preview-image mt-2">';
    document.getElementById('modalSubmitBtn').name = 'edit_galeri';
}

function confirmDelete(type, id) {
    const deleteForm = document.getElementById('deleteForm');
    const deleteSubmitBtn = document.getElementById('deleteSubmitBtn');
    
    deleteForm.action = window.location.href;
    deleteSubmitBtn.name = 'delete_' + type;
    document.getElementById('delete_id').value = id;
    
    if (deleteModalInstance) deleteModalInstance.show();
}
</script>

<?php
$content = ob_get_clean();
include '../../includes/base.php';
?>