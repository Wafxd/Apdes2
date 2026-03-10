<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../../login.php");
    exit();
}

include "../../db/koneksi.php";

// ==================== AUTO-PATCH DATABASE ====================
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM struktur_keterangan LIKE 'grid_lines'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE struktur_keterangan ADD grid_lines TEXT NULL");
}
$check_row = mysqli_query($conn, "SELECT id FROM struktur_keterangan LIMIT 1");
if(mysqli_num_rows($check_row) == 0) {
    mysqli_query($conn, "INSERT INTO struktur_keterangan (sk_nomor, sk_tentang, sk_ttd, grid_lines) VALUES ('-', '-', '-', '[]')");
}

// ==================== AJAX: UPDATE POSISI KOTAK ====================
if (isset($_POST['ajax_update_posisi'])) {
    header('Content-Type: application/json');
    $id = (int)$_POST['id'];
    $new_urutan = (int)$_POST['urutan'];
    $query = "UPDATE struktur_pemerintahan SET urutan = $new_urutan WHERE id = $id";
    if(mysqli_query($conn, $query)) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit();
}

// ==================== AJAX: UPDATE KONEKSI GARIS ====================
if (isset($_POST['ajax_save_lines'])) {
    header('Content-Type: application/json');
    $lines_data = mysqli_real_escape_string($conn, $_POST['lines_data']); 
    $query = "UPDATE struktur_keterangan SET grid_lines = '$lines_data'";
    if(mysqli_query($conn, $query)) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit();
}

$pageTitle = "Visual Builder Struktur Pemerintahan";

function uploadGambar($file, $old_file = '') {
    $target_dir = "../../assets/images/"; 
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_name = time() . '_' . basename($file["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) return ['success' => false, 'message' => 'File bukan gambar'];
    if ($file["size"] > 5000000) return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
    
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($imageFileType, $allowed)) return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & WEBP yang diizinkan'];
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        if (!empty($old_file) && file_exists($target_dir . $old_file)) unlink($target_dir . $old_file);
        return ['success' => true, 'file_name' => $file_name];
    } else {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }
}

// HANDLE CRUD
if (isset($_POST['save_keterangan'])) {
    $sk_nomor = mysqli_real_escape_string($conn, $_POST['sk_nomor']);
    $sk_tentang = mysqli_real_escape_string($conn, $_POST['sk_tentang']);
    $sk_ttd = mysqli_real_escape_string($conn, $_POST['sk_ttd']);
    $query = "UPDATE struktur_keterangan SET sk_nomor='$sk_nomor', sk_tentang='$sk_tentang', sk_ttd='$sk_ttd'";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Keterangan SK disimpan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: struktur.php"); exit();
}

if (isset($_POST['add_perangkat'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $periode = mysqli_real_escape_string($conn, $_POST['periode']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $tugas_pokok = mysqli_real_escape_string($conn, $_POST['tugas_pokok']);
    $urutan = intval($_POST['urutan']); 
    $gambar = '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar']);
        if ($upload['success']) $gambar = $upload['file_name'];
        else { $_SESSION['error_message'] = $upload['message']; header("Location: struktur.php"); exit(); }
    }
    
    $query = "INSERT INTO struktur_pemerintahan (nama, jabatan, periode, kategori, tugas_pokok, gambar, urutan) VALUES ('$nama', '$jabatan', '$periode', '$kategori', '$tugas_pokok', '$gambar', '$urutan')";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Data ditambahkan" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: struktur.php"); exit();
}

if (isset($_POST['edit_perangkat'])) {
    $id = intval($_POST['id']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $periode = mysqli_real_escape_string($conn, $_POST['periode']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $tugas_pokok = mysqli_real_escape_string($conn, $_POST['tugas_pokok']);
    $urutan = intval($_POST['urutan']);
    $gambar = $_POST['existing_gambar'] ?? '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $upload = uploadGambar($_FILES['gambar'], $gambar);
        if ($upload['success']) $gambar = $upload['file_name'];
        else { $_SESSION['error_message'] = $upload['message']; header("Location: struktur.php"); exit(); }
    }
    
    $query = "UPDATE struktur_pemerintahan SET nama='$nama', jabatan='$jabatan', periode='$periode', kategori='$kategori', tugas_pokok='$tugas_pokok', gambar='$gambar', urutan='$urutan' WHERE id=$id";
    mysqli_query($conn, $query) ? $_SESSION['success_message'] = "Data diupdate" : $_SESSION['error_message'] = "Gagal: " . mysqli_error($conn);
    header("Location: struktur.php"); exit();
}

if (isset($_POST['delete_perangkat'])) {
    $id = intval($_POST['id']);
    $result = mysqli_query($conn, "SELECT gambar FROM struktur_pemerintahan WHERE id=$id");
    $row = mysqli_fetch_assoc($result);
    if ($row && !empty($row['gambar'])) uploadGambar(['name' => ''], $row['gambar']);
    mysqli_query($conn, "DELETE FROM struktur_pemerintahan WHERE id=$id");
    
    // Bersihkan garis otomatis
    $ket = mysqli_fetch_assoc(mysqli_query($conn, "SELECT grid_lines FROM struktur_keterangan LIMIT 1"));
    $lines = json_decode($ket['grid_lines'], true) ?: [];
    $filtered_lines = array_filter($lines, function($line) use ($id) {
        return $line['from'] != $id && $line['to'] != $id;
    });
    $new_json = mysqli_real_escape_string($conn, json_encode(array_values($filtered_lines)));
    mysqli_query($conn, "UPDATE struktur_keterangan SET grid_lines = '$new_json'");

    $_SESSION['success_message'] = "Data dihapus";
    header("Location: struktur.php"); exit();
}

// ==================== AMBIL DATA DENGAN AMAN ====================
$keterangan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM struktur_keterangan LIMIT 1"));

// FORMAT DATA GARIS
$raw_lines = $keterangan['grid_lines'] ?? '[]';
$dec_lines = json_decode($raw_lines, true);
// Validasi ketat: pastikan ini array format baru [{from, to, elbowRatio}]
if(!is_array($dec_lines) || (count($dec_lines) > 0 && !isset($dec_lines[0]['from']))) {
    $dec_lines = []; // Reset jika format lama yang bikin error
}
$safe_lines_json = json_encode($dec_lines);

$perangkats_table = mysqli_query($conn, "SELECT * FROM struktur_pemerintahan ORDER BY urutan ASC, id ASC");
$grid_data = [];
$semua_perangkat = [];

$perangkats_grid = mysqli_query($conn, "SELECT * FROM struktur_pemerintahan");
while($row = mysqli_fetch_assoc($perangkats_grid)) {
    $semua_perangkat[] = $row;
    $pos = (int)$row['urutan'];
    if($pos < 1 || $pos > 70) $pos = 1; 
    if(!isset($grid_data[$pos])) $grid_data[$pos] = [];
    $grid_data[$pos][] = $row;
}
// FORMAT DATA PERSONEL AMAN
$safe_nodes_json = json_encode($semua_perangkat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

ob_start();
?>

<style>
    :root { 
        --primary: #1e3c72;
        --accent: #2a5298;
        --line-color: #2c3e50; 
    }
    
    .nav-pills .nav-link { color: var(--primary); font-weight: 600; border-radius: 10px; padding: 12px 20px; transition: 0.3s; margin-bottom: 10px; border: 1px solid transparent;}
    .nav-pills .nav-link.active { background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border-color: transparent;}
    .nav-pills .nav-link:hover:not(.active) { background: #f8f9fc; }
    
    .content-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }

    /* ==================== PANEL KIRI KONEKSI ==================== */
    #panelKoneksi { display: none; border: 2px solid #1cc88a; overflow: hidden; }
    .checklist-container { max-height: 45vh; overflow-y: auto; padding-right: 5px; }
    .cl-item { background: white; border: 1px solid #eaecf4; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: 0.2s;}
    .cl-item:hover { border-color: #1cc88a; background: #e8f5e9; }
    .cl-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; flex-shrink:0;}
    
    /* ==================== CSS GRID BUILDER ==================== */
    .org-builder-wrapper { width: 100%; overflow-x: auto; padding: 40px; background: #eef2f7; border-radius: 10px; }
    .org-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 40px 20px; min-width: 1200px; position: relative; }

    #svgCanvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; }

    .org-cell {
        min-height: 140px; position: relative;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        border: 2px dashed rgba(0,0,0,0.05); border-radius: 10px; z-index: 5; transition: 0.2s;
    }
    .org-cell::before {
        content: attr(data-cell-id); position: absolute; top: -10px; left: -10px;
        font-size: 10px; font-weight: bold; color: #b7b9cc; z-index: 20;
        background: white; border-radius: 50%; width: 22px; height: 22px;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid #eaecf4;
    }
    .org-cell.drag-over { background-color: rgba(30,60,114,0.1); border-style: solid; border-color: var(--primary); }

    /* ==================== KARTU PERSONEL ==================== */
    .org-item {
        width: 100%; max-width: 155px;
        background: white; border: 2px solid var(--primary); border-radius: 8px;
        padding: 15px 10px 10px 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        cursor: grab; position: relative; z-index: 10; margin: auto; transition: 0.2s;
    }
    .org-item:active { cursor: grabbing; transform: scale(0.95); }
    .org-item.dragging { opacity: 0.5; }
    
    .org-item.connecting-active { border: 3px solid #1cc88a; box-shadow: 0 0 15px rgba(28,200,138,0.5); transform: scale(1.05); }

    /* TOMBOL LINK (AMAN DARI KLIK DRAG) */
    .btn-link-node {
        position: absolute; top: -12px; right: -12px;
        background: #1cc88a; color: white; width: 32px; height: 32px;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 50;
        border: 2px solid white; transition: 0.2s; padding: 0; outline: none;
    }
    .btn-link-node:hover { transform: scale(1.15) rotate(15deg); background: #13855c; }

    .org-item-badge { font-size: 0.6rem; background: var(--primary); color: white; padding: 3px 6px; border-radius: 4px; display: block; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
    .org-item-img { width: 60px; height: 60px; border-radius: 6px; object-fit: cover; margin-bottom: 8px; border: 1px solid #ccc; background: #f8f9fc; }
    .org-item-icon { font-size: 50px; color: #d1d3e2; margin-bottom: 8px; }
    .org-item-name { font-size: 0.8rem; font-weight: 800; color: #000; line-height: 1.1; margin-bottom: 3px; text-transform: uppercase;}
    .org-item-role { font-size: 0.65rem; font-weight: 700; color: #555; text-transform: uppercase; line-height: 1.1;}

    /* ==================== MODE EDIT GARIS ==================== */
    body.mode-garis .btn-link-node { display: none !important; } 
    body.mode-garis .org-item { pointer-events: none; border-color: #b7b9cc; }
    
    .btn-mode-garis { background: white; border: 2px solid #f6c23e; color: #f6c23e; font-weight: bold; border-radius: 50px; padding: 8px 20px; transition: 0.3s; cursor: pointer; }
    body.mode-garis .btn-mode-garis { background: #f6c23e; color: white; box-shadow: 0 5px 15px rgba(246,194,62, 0.3); }

    /* SVG INTERACTIVITY */
    .svg-connection { stroke: var(--line-color); stroke-width: 3px; fill: none; transition: 0.2s; pointer-events: none;}
    .elbow-handle { display: none; cursor: ns-resize; pointer-events: auto; transition: 0.2s; }
    
    body.mode-garis .svg-connection { pointer-events: auto; cursor: pointer;}
    body.mode-garis .svg-connection:hover { stroke: #e74a3b; stroke-width: 5px; }
    body.mode-garis .elbow-handle { display: block; }
    body.mode-garis .elbow-handle:hover { r: 8; fill: #4e73df !important; stroke: white; stroke-width: 2px;}

    #instructionPanel { display: none; background: #fffdf5; border:1px solid #f6c23e; color: #856404; padding: 12px 20px; border-radius: 8px; font-size: 0.9rem; margin-top: 15px; }
    body.mode-garis #instructionPanel { display: block; animation: fadeIn 0.3s; }
    @keyframes fadeIn { from{opacity:0; transform:translateY(-10px);} to{opacity:1; transform:translateY(0);} }
</style>

<div class="container-fluid py-3">
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="this.parentElement.remove()"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>
    
    <div class="row">
        <div class="col-lg-3 mb-4">
            
            <div class="card content-card sticky-top" style="top: 20px; z-index: 100;" id="menuSidebar">
                <div class="card-body p-3">
                    <h6 class="text-xs font-weight-bold text-primary text-uppercase mb-3 px-2">Manajemen Struktur</h6>
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                        <button class="nav-link active text-left border-0 bg-transparent w-100" data-toggle="pill" data-target="#tab-grid" type="button">
                            <i class="fas fa-sitemap fa-fw me-2"></i> Visual Builder (Draw.io)
                        </button>
                        <button class="nav-link text-left border-0 bg-transparent w-100" data-toggle="pill" data-target="#tab-perangkat" type="button">
                            <i class="fas fa-list fa-fw me-2"></i> Mode Tabel Data
                        </button>
                        <button class="nav-link text-left border-0 bg-transparent w-100" data-toggle="pill" data-target="#tab-sk" type="button">
                            <i class="fas fa-file-signature fa-fw me-2"></i> Keterangan SK
                        </button>
                    </div>
                </div>
            </div>

            <div class="card content-card sticky-top mt-3" style="top: 20px; z-index: 100;" id="panelKoneksi">
                <div class="card-header bg-success text-white py-3">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-network-wired me-2"></i> Atur Bawahan</h6>
                </div>
                <div class="card-body p-3 bg-light">
                    <div class="alert alert-warning p-2 small mb-3 border-0 shadow-sm text-dark">
                        Garis ditarik dari Atasan:<br>
                        <strong id="pkNama" class="d-block mt-1 text-uppercase" style="font-size: 0.95rem;"></strong>
                    </div>
                    
                    <p class="small text-muted mb-2 font-weight-bold">Pilih siapa saja bawahannya:</p>
                    
                    <div class="checklist-container mb-3" id="pkList"></div>
                    
                    <button class="btn btn-success btn-sm w-100 py-2 font-weight-bold shadow-sm" onclick="simpanKoneksiPanel()">
                        <i class="fas fa-save me-1"></i> Simpan Garis
                    </button>
                    <button class="btn btn-light border btn-sm w-100 mt-2 text-muted" onclick="tutupPanelKoneksi()">
                        Batal & Tutup
                    </button>
                </div>
            </div>

        </div>

        <div class="col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">

                <div class="tab-pane fade show active" id="tab-grid">
                    <div class="card content-card">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
                            <div>
                                <h6 class="m-0 font-weight-bold text-primary mb-1"><i class="fas fa-project-diagram me-2"></i>Canvas Bagan Organisasi</h6>
                                <small class="text-muted">
                                    - Pindah posisi: <b>Drag & Drop</b> kartu.<br>
                                    - Buat garis: Klik <b>🔗 Ikon Link Hijau</b> pada kartu.<br>
                                </small>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span id="saveStatus" class="badge badge-success text-white px-2 py-1" style="display:none;"><i class="fas fa-check"></i> Tersimpan</span>
                                <button class="btn-mode-garis shadow-sm" onclick="toggleEditMode()" id="btnEditMode">
                                    <i class="fas fa-arrows-alt me-1"></i> Mode Edit Garis
                                </button>
                            </div>
                        </div>
                        
                        <div id="instructionPanel">
                            <h6 class="font-weight-bold mb-1"><i class="fas fa-mouse-pointer me-1"></i> MODE EDIT GARIS AKTIF:</h6>
                            <ul class="mb-0 pl-4 small text-dark">
                                <li>Jika garis menabrak kartu, <b>Tarik (Drag) titik merah</b> untuk menggeser jalurnya.</li>
                                <li>Untuk menghapus garis, klik langsung pada garis tersebut.</li>
                            </ul>
                        </div>

                        <div class="card-body p-0">
                            <div class="org-builder-wrapper">
                                <div class="org-grid" id="gridContainer">
                                    <svg id="svgCanvas"></svg>

                                    <?php for ($i = 1; $i <= 70; $i++): ?>
                                        <div class="org-cell" data-cell-id="<?php echo $i; ?>">
                                            <?php 
                                            if(isset($grid_data[$i])): 
                                                foreach($grid_data[$i] as $p):
                                            ?>
                                                <div class="org-item" draggable="true" data-node-id="<?php echo $p['id']; ?>">
                                                    <button type="button" class="btn-link-node" 
                                                            onmousedown="event.stopPropagation();" 
                                                            onclick="event.stopPropagation(); bukaPanelKoneksi('<?php echo $p['id']; ?>', '<?php echo addslashes($p['nama']); ?>')" 
                                                            title="Atur Garis Bawahan">
                                                        <i class="fas fa-link fa-sm"></i>
                                                    </button>
                                                    
                                                    <span class="org-item-badge"><?php echo htmlspecialchars($p['kategori']); ?></span>
                                                    <?php if(!empty($p['gambar'])): ?>
                                                        <img src="../../assets/images/<?php echo $p['gambar']; ?>" class="org-item-img" alt="Foto">
                                                    <?php else: ?>
                                                        <i class="fas fa-user-tie org-item-icon"></i>
                                                    <?php endif; ?>
                                                    <div class="org-item-name"><?php echo htmlspecialchars($p['nama']); ?></div>
                                                    <div class="org-item-role"><?php echo htmlspecialchars($p['jabatan']); ?></div>
                                                </div>
                                            <?php 
                                                endforeach;
                                            endif; 
                                            ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-perangkat">
                    <div class="card content-card">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-users text-primary me-2"></i>Daftar Personel</h6>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="tampilkanModalStandar('perangkatModal')">
                                <i class="fas fa-plus me-1"></i> Tambah Personel
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr class="text-muted small text-uppercase">
                                            <th>Foto</th><th>Nama Lengkap</th><th>Jabatan</th><th>Kotak Grid</th><th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($perangkats_table) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($perangkats_table)): ?>
                                            <tr>
                                                <td>
                                                    <?php if(!empty($row['gambar'])): ?>
                                                        <img src="../../assets/images/<?php echo $row['gambar']; ?>" style="width:50px; height:50px; border-radius:5px; object-fit:cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light rounded d-inline-flex align-items-center justify-content-center text-muted border" style="width:50px; height:50px;"><i class="fas fa-user"></i></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="font-weight-bold text-dark d-block text-uppercase"><?php echo htmlspecialchars($row['nama']); ?></span></td>
                                                <td><span class="badge badge-light text-primary border border-primary text-uppercase"><?php echo htmlspecialchars($row['jabatan']); ?></span></td>
                                                <td><span class="badge badge-secondary text-white">Kotak Ke-<?php echo $row['urutan']; ?></span></td>
                                                <td class="text-end">
                                                    <button class="btn btn-warning btn-sm text-white" onclick='editPerangkatStandar(<?php echo json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-danger btn-sm" onclick="hapusPerangkatStandar(<?php echo $row['id']; ?>)"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center py-4">Belum ada data.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-sk">
                    <div class="card content-card">
                        <div class="card-header bg-white py-3"><h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-file-signature text-primary me-2"></i>Keterangan SK Dasar Hukum</h6></div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label font-weight-bold">Nomor Keputusan / SK</label>
                                        <input type="text" class="form-control" name="sk_nomor" value="<?php echo htmlspecialchars($keterangan['sk_nomor'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label font-weight-bold">Penandatangan (Kades)</label>
                                        <input type="text" class="form-control" name="sk_ttd" value="<?php echo htmlspecialchars($keterangan['sk_ttd'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-12 mb-4">
                                        <label class="form-label font-weight-bold">Tentang (Isi SK)</label>
                                        <textarea class="form-control" name="sk_tentang" rows="3" required><?php echo htmlspecialchars($keterangan['sk_tentang'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <button type="submit" name="save_keterangan" class="btn btn-primary px-4 rounded-pill"><i class="fas fa-save me-2"></i>Simpan SK</button>
                            </form>
                        </div>
                    </div>
                </div>

            </div> 
        </div> 
    </div> 
</div> 

<div class="modal fade" id="perangkatModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="perangkatModalTitle">Personel</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="perangkat_id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" name="nama" id="perangkat_nama" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Hierarki (Kategori)</label><input type="text" class="form-control" name="kategori" id="perangkat_kategori" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Jabatan Spesifik</label><input type="text" class="form-control" name="jabatan" id="perangkat_jabatan" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Periode</label><input type="text" class="form-control" name="periode" id="perangkat_periode"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Tugas Pokok (Enter per tugas)</label><textarea class="form-control" name="tugas_pokok" id="perangkat_tugas" rows="3"></textarea></div>
                    <div class="row mt-3">
                        <div class="col-md-8 mb-3"><label class="form-label">Foto</label><input type="file" class="form-control" name="gambar"><input type="hidden" name="existing_gambar" id="perangkat_existing"><div id="perangkat_preview" class="mt-2"></div></div>
                        <div class="col-md-4 mb-3"><label class="form-label text-primary">Posisi Kotak (1-70)</label><input type="number" class="form-control" name="urutan" id="perangkat_urutan" value="1" min="1" max="70"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light"><button type="button" class="btn btn-secondary rounded-pill" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary rounded-pill px-4" id="perangkatSubmitBtn" name="add_perangkat">Simpan</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Hapus Data</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center p-5">
                <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                <h4 class="text-dark">Hapus personel ini?</h4>
            </div>
            <div class="modal-footer bg-light justify-content-center">
                <form method="POST">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" name="delete_perangkat">Hapus Permanen</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// INJEKSI DATA PHP LANGSUNG (ANTI-ERROR)
const SERVER_NODES = <?php echo $safe_nodes_json ?: '[]'; ?>;
let connections = <?php echo $safe_lines_json ?: '[]'; ?>;

// ==================== FITUR PANEL KONEKSI KIRI (PENGGANTI MODAL) ====================
let currentConnectFrom = null;
const cards = document.querySelectorAll('.org-item');

function bukaPanelKoneksi(id, nama) {
    document.getElementById('menuSidebar').style.display = 'none';
    document.getElementById('panelKoneksi').style.display = 'block';
    
    currentConnectFrom = id.toString();
    document.getElementById('pkNama').innerText = nama;
    
    cards.forEach(c => c.classList.remove('connecting-active'));
    let activeCard = document.querySelector(`.org-item[data-node-id="${id}"]`);
    if(activeCard) activeCard.classList.add('connecting-active');
    
    let html = '';
    
    if(SERVER_NODES && SERVER_NODES.length > 0) {
        SERVER_NODES.forEach(node => {
            if (node.id.toString() === currentConnectFrom) return; 
            
            let isConnected = connections.some(c => c.from.toString() === currentConnectFrom && c.to.toString() === node.id.toString());
            let checked = isConnected ? 'checked' : '';
            
            html += `
            <label class="cl-item">
                <input type="checkbox" class="pk-checkbox" value="${node.id}" ${checked}>
                <div>
                    <strong class="text-primary" style="font-size:0.85rem;">${node.nama}</strong><br>
                    <span class="text-muted" style="font-size:0.7rem;">${node.jabatan}</span>
                </div>
            </label>`;
        });
    }

    if(html === '') {
        html = '<div class="alert alert-info small m-0 p-2">Belum ada perangkat desa lain untuk dihubungkan.</div>';
    }
    
    document.getElementById('pkList').innerHTML = html;
}

function tutupPanelKoneksi() {
    document.getElementById('panelKoneksi').style.display = 'none';
    document.getElementById('menuSidebar').style.display = 'block';
    cards.forEach(c => c.classList.remove('connecting-active'));
    currentConnectFrom = null;
}

function simpanKoneksiPanel() {
    let newTargets = [];
    document.querySelectorAll('.pk-checkbox:checked').forEach(cb => { newTargets.push(cb.value); });
    
    let oldConnectionsFromThis = connections.filter(c => c.from.toString() === currentConnectFrom);
    connections = connections.filter(c => c.from.toString() !== currentConnectFrom);
    
    newTargets.forEach(targetId => {
        let oldConn = oldConnectionsFromThis.find(c => c.to.toString() === targetId);
        let eRatio = oldConn ? (oldConn.elbowRatio || 0.5) : 0.5;
        connections.push({ from: currentConnectFrom, to: targetId, elbowRatio: eRatio });
    });
    
    saveConnectionsToDatabase();
    drawConnections();
    tutupPanelKoneksi();
}

function saveConnectionsToDatabase() {
    const formData = new FormData();
    formData.append('ajax_save_lines', '1');
    formData.append('lines_data', JSON.stringify(connections));
    
    fetch('struktur.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            const sb = document.getElementById('saveStatus');
            sb.style.display = 'inline-block';
            setTimeout(() => { sb.style.display = 'none'; }, 2000);
        } else alert('Gagal menyimpan: ' + d.message);
    });
}

// ==================== ENGINE SVG AUTO-ROUTING ====================
const gridContainer = document.getElementById('gridContainer');
const svgCanvas = document.getElementById('svgCanvas');

function drawConnections() {
    if(!svgCanvas || !gridContainer) return;
    svgCanvas.innerHTML = ''; 

    connections.forEach((conn, index) => {
        const fromCard = document.querySelector(`.org-item[data-node-id="${conn.from}"]`);
        const toCard = document.querySelector(`.org-item[data-node-id="${conn.to}"]`);

        if (fromCard && toCard) {
            const containerRect = gridContainer.getBoundingClientRect();
            const fromRect = fromCard.getBoundingClientRect();
            const toRect = toCard.getBoundingClientRect();

            const startX = (fromRect.left - containerRect.left) + (fromRect.width / 2);
            const startY = (fromRect.bottom - containerRect.top);

            const endX = (toRect.left - containerRect.left) + (toRect.width / 2);
            const endY = (toRect.top - containerRect.top);

            let elbowRatio = conn.elbowRatio !== undefined ? conn.elbowRatio : 0.5;
            let totalDist = endY - startY;
            let midY = startY + (totalDist * elbowRatio);

            const pathString = `M ${startX} ${startY} L ${startX} ${midY} L ${endX} ${midY} L ${endX} ${endY}`;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', pathString);
            path.setAttribute('class', 'svg-connection');

            const handle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            handle.setAttribute('cx', startX + (endX - startX)/2);
            handle.setAttribute('cy', midY);
            handle.setAttribute('r', 6);
            handle.setAttribute('fill', '#e74a3b');
            handle.setAttribute('class', 'elbow-handle');

            const startDrag = (e) => {
                if(!isEditMode) return; 
                e.preventDefault(); e.stopPropagation(); 
                let startMouseY = e.clientY;
                let initialRatio = elbowRatio;

                const onMove = (ev) => {
                    let dy = ev.clientY - startMouseY;
                    if(totalDist === 0) return;
                    let dr = dy / totalDist;
                    let newRatio = Math.max(0.1, Math.min(0.9, initialRatio + dr));

                    let newMidY = startY + (totalDist * newRatio);
                    path.setAttribute('d', `M ${startX} ${startY} L ${startX} ${newMidY} L ${endX} ${newMidY} L ${endX} ${endY}`);
                    handle.setAttribute('cy', newMidY);
                    connections[index].elbowRatio = newRatio;
                };

                const onUp = () => {
                    window.removeEventListener('mousemove', onMove);
                    window.removeEventListener('mouseup', onUp);
                    saveConnectionsToDatabase(); 
                };

                window.addEventListener('mousemove', onMove);
                window.addEventListener('mouseup', onUp);
            };

            handle.addEventListener('mousedown', startDrag);

            path.addEventListener('click', function(e) {
                if (isEditMode) {
                    e.stopPropagation();
                    if(confirm('Hapus garis penghubung ini?')) {
                        connections.splice(index, 1);
                        saveConnectionsToDatabase();
                        drawConnections();
                    }
                }
            });

            svgCanvas.appendChild(path);
            svgCanvas.appendChild(handle);
        }
    });
}

// ==================== MODE EDIT GARIS ====================
let isEditMode = false;
function toggleEditMode() {
    isEditMode = !isEditMode;
    const body = document.body;
    const btn = document.getElementById('btnEditMode');
    
    if(isEditMode) {
        body.classList.add('mode-garis');
        btn.innerHTML = '<i class="fas fa-times me-1"></i> Matikan Mode Edit';
    } else {
        body.classList.remove('mode-garis');
        btn.innerHTML = '<i class="fas fa-arrows-alt me-1"></i> Mode Edit Garis';
    }
}

// ==================== DRAG AND DROP KARTU ====================
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(drawConnections, 300); 

    const cells = document.querySelectorAll('.org-cell');

    cards.forEach(draggable => {
        draggable.addEventListener('dragstart', () => { if(!isEditMode) draggable.classList.add('dragging'); });
        draggable.addEventListener('dragend', () => {
            if(isEditMode) return;
            draggable.classList.remove('dragging');
            const item_id = draggable.getAttribute('data-node-id');
            const parentCell = draggable.closest('.org-cell');
            if(parentCell) {
                simpanPosisiKeDatabase(item_id, parentCell.getAttribute('data-cell-id'));
            }
        });
    });

    cells.forEach(cell => {
        cell.addEventListener('dragover', e => { if(!isEditMode) { e.preventDefault(); cell.classList.add('drag-over'); } });
        cell.addEventListener('dragleave', () => { cell.classList.remove('drag-over'); });
        cell.addEventListener('drop', e => {
            if(isEditMode) return;
            e.preventDefault(); cell.classList.remove('drag-over');
            
            if (cell.querySelectorAll('.org-item').length > 0) {
                alert('Kotak ini sudah terisi! Silakan kosongkan atau geser kartu yang lama terlebih dahulu.');
                return;
            }

            const draggable = document.querySelector('.dragging');
            if(draggable) {
                cell.appendChild(draggable);
                drawConnections(); 
                simpanPosisiKeDatabase(draggable.getAttribute('data-node-id'), cell.getAttribute('data-cell-id'));
            }
        });
    });
});

window.addEventListener('resize', drawConnections);

// ==================== MODAL STANDAR (JQUERY BS4) ====================
function tampilkanModalStandar(modalId) {
    if (typeof window.jQuery !== 'undefined') { $('#' + modalId).modal('show'); } 
    else { alert('Gagal memuat jQuery. Refresh halaman.'); }
}

function bukaModalForm(mode, d = null) {
    if (mode === 'tambah') {
        document.getElementById('perangkatModalTitle').innerText = 'Tambah Personel';
        document.getElementById('perangkat_id').value = '';
        document.getElementById('perangkat_nama').value = '';
        document.getElementById('perangkat_jabatan').value = '';
        document.getElementById('perangkat_kategori').value = ''; 
        document.getElementById('perangkat_periode').value = '';
        document.getElementById('perangkat_tugas').value = '';
        document.getElementById('perangkat_urutan').value = '1';
        document.getElementById('perangkat_existing').value = '';
        document.getElementById('perangkat_preview').innerHTML = '';
        document.getElementById('perangkatSubmitBtn').name = 'add_perangkat';
    } else if (mode === 'edit' && d) {
        document.getElementById('perangkatModalTitle').innerText = 'Edit Personel';
        document.getElementById('perangkat_id').value = d.id;
        document.getElementById('perangkat_nama').value = d.nama;
        document.getElementById('perangkat_jabatan').value = d.jabatan;
        document.getElementById('perangkat_kategori').value = d.kategori;
        document.getElementById('perangkat_periode').value = d.periode || '';
        document.getElementById('perangkat_tugas').value = d.tugas_pokok || '';
        document.getElementById('perangkat_urutan').value = d.urutan || 1;
        document.getElementById('perangkat_existing').value = d.gambar || '';
        if(d.gambar) document.getElementById('perangkat_preview').innerHTML = '<img src="../../assets/images/'+d.gambar+'" style="width:60px; border-radius:5px;">';
        document.getElementById('perangkatSubmitBtn').name = 'edit_perangkat';
    }
    tampilkanModalStandar('perangkatModal');
}

function hapusPerangkatStandar(id) {
    document.getElementById('delete_id').value = id;
    tampilkanModalStandar('deleteModal');
}
</script>

<?php
$content = ob_get_clean();
include '../../includes/base.php';
?>