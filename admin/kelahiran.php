<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

include "../db/koneksi.php";

// ==================== AJAX: PENCARIAN PENDUDUK (AUTOCOMPLETE) ====================
if (isset($_GET['ajax_cari_penduduk'])) {
    header('Content-Type: application/json');
    $keyword = mysqli_real_escape_string($conn, $_GET['ajax_cari_penduduk']);
    $jk = isset($_GET['jk']) ? mysqli_real_escape_string($conn, $_GET['jk']) : '';
    
    $query = "SELECT nik, nama_penduduk FROM penduduk WHERE nama_penduduk LIKE '%$keyword%'";
    if (!empty($jk)) {
        $query .= " AND jenis_kelamin = '$jk'";
    }
    $query .= " ORDER BY nama_penduduk ASC LIMIT 8";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    echo json_encode($data);
    exit();
}

// ==================== PROSES TAMBAH (CREATE) ====================
if (isset($_POST["submit_tambah"])) {
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama_bayi = mysqli_real_escape_string($conn, $_POST['nama_bayi']);
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $agama = mysqli_real_escape_string($conn, $_POST['agama']);
    $nama_ayah = mysqli_real_escape_string($conn, $_POST['nama_ayah']);
    $nama_ibu = mysqli_real_escape_string($conn, $_POST['nama_ibu']);
    $anak_ke = (int)$_POST['anak_ke'];
    $berat_bayi = (float)$_POST['berat_bayi'];
    $panjang_bayi = (float)$_POST['panjang_bayi'];
    
    $dusun = mysqli_real_escape_string($conn, $_POST['dusun_option'] == 'pilih' ? $_POST['dusun_select'] : $_POST['dusun_custom']);
    $rt_rw = mysqli_real_escape_string($conn, $_POST['rt_rw']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    
    $cek_nik = mysqli_query($conn, "SELECT nik FROM penduduk WHERE nik = '$nik'");
    if (mysqli_num_rows($cek_nik) > 0) {
        $_SESSION['error_message'] = "Gagal! NIK Bayi sudah terdaftar di database penduduk.";
        header("Location: kelahiran.php");
        exit();
    }

    mysqli_begin_transaction($conn);

    try {
        $query_penduduk = "INSERT INTO penduduk 
            (nik, nama_penduduk, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, pendidikan, pekerjaan, status_kawin, alamat, rt_rw, dusun, kel_des, kecamatan, kabupaten_kota, kodepos, provinsi, nama_ayah, nama_ibu) 
            VALUES 
            ('$nik', '$nama_bayi', '$tempat_lahir', '$tanggal_lahir', '$jenis_kelamin', '$agama', 'BELUM SEKOLAH', 'BELUM/TIDAK BEKERJA', 'Belum Kawin', '$alamat', '$rt_rw', '$dusun', 'SUKOLILO TIMUR', 'LABANG', 'BANGKALAN', '69162', 'JAWA TIMUR', '$nama_ayah', '$nama_ibu')";
        mysqli_query($conn, $query_penduduk);

        $query_kelahiran = "INSERT INTO kelahiran 
            (nik_bayi, nama_bayi, tanggal_lahir, tempat_lahir, jenis_kelamin, nama_ayah, nama_ibu, anak_ke, berat_bayi, panjang_bayi) 
            VALUES 
            ('$nik', '$nama_bayi', '$tanggal_lahir', '$tempat_lahir', '$jenis_kelamin', '$nama_ayah', '$nama_ibu', '$anak_ke', '$berat_bayi', '$panjang_bayi')";
        mysqli_query($conn, $query_kelahiran);

        mysqli_commit($conn);
        $_SESSION['success_message'] = "Data Kelahiran berhasil disimpan dan otomatis masuk ke Master Penduduk!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
    
    header("Location: kelahiran.php");
    exit();
}

// ==================== PROSES EDIT (UPDATE) ====================
if (isset($_POST['submit_edit'])) {
    $id_kelahiran = (int)$_POST['id_kelahiran'];
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama_bayi = mysqli_real_escape_string($conn, $_POST['nama_bayi']);
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $nama_ayah = mysqli_real_escape_string($conn, $_POST['nama_ayah']);
    $nama_ibu = mysqli_real_escape_string($conn, $_POST['nama_ibu']);
    $anak_ke = (int)$_POST['anak_ke'];
    $berat_bayi = (float)$_POST['berat_bayi'];
    $panjang_bayi = (float)$_POST['panjang_bayi'];

    $agama = mysqli_real_escape_string($conn, $_POST['agama']);
    $dusun = mysqli_real_escape_string($conn, $_POST['dusun']);
    $rt_rw = mysqli_real_escape_string($conn, $_POST['rt_rw']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

    mysqli_begin_transaction($conn);

    try {
        $q_upd_kelahiran = "UPDATE kelahiran SET 
            nama_bayi='$nama_bayi', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir', 
            jenis_kelamin='$jenis_kelamin', nama_ayah='$nama_ayah', nama_ibu='$nama_ibu', 
            anak_ke='$anak_ke', berat_bayi='$berat_bayi', panjang_bayi='$panjang_bayi' 
            WHERE id_kelahiran=$id_kelahiran";
        mysqli_query($conn, $q_upd_kelahiran);

        $q_upd_penduduk = "UPDATE penduduk SET 
            nama_penduduk='$nama_bayi', tempat_lahir='$tempat_lahir', tanggal_lahir='$tanggal_lahir', 
            jenis_kelamin='$jenis_kelamin', agama='$agama', nama_ayah='$nama_ayah', nama_ibu='$nama_ibu',
            dusun='$dusun', rt_rw='$rt_rw', alamat='$alamat'
            WHERE nik='$nik'";
        mysqli_query($conn, $q_upd_penduduk);

        mysqli_commit($conn);
        $_SESSION['success_message'] = "Data Kelahiran dan Penduduk berhasil diperbarui!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Gagal memperbarui: " . $e->getMessage();
    }
    header("Location: kelahiran.php");
    exit();
}

// ==================== PROSES HAPUS (DELETE) ====================
if (isset($_POST['delete_kelahiran'])) {
    $id = (int)$_POST['id_hapus'];
    $query = "DELETE FROM kelahiran WHERE id_kelahiran = $id";
    if(mysqli_query($conn, $query)){
        $_SESSION['success_message'] = "Log riwayat kelahiran berhasil dihapus!";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus data kelahiran.";
    }
    header("Location: kelahiran.php");
    exit();
}

// ==================== QUERY DATA ====================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "WHERE 1=1";
if (!empty($search)) {
    $where .= " AND (k.nama_bayi LIKE '%$search%' OR k.nama_ayah LIKE '%$search%' OR k.nama_ibu LIKE '%$search%')";
}

$query = "SELECT k.*, p.agama, p.dusun, p.rt_rw, p.alamat 
          FROM kelahiran k 
          LEFT JOIN penduduk p ON k.nik_bayi = p.nik 
          $where 
          ORDER BY k.tanggal_lahir DESC";
$result = mysqli_query($conn, $query);

$stat_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kelahiran"))['total'];
$stat_lk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kelahiran WHERE jenis_kelamin = 'LAKI-LAKI'"))['total'];
$stat_pr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kelahiran WHERE jenis_kelamin = 'PEREMPUAN'"))['total'];
$stat_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kelahiran WHERE MONTH(tanggal_lahir) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_lahir) = YEAR(CURRENT_DATE())"))['total'];

$daftar_dusun = ['KEJAWAN', 'SEPURAN', 'BUDDAN', 'PASEREAN', 'LANGGAR', 'MORLEKE', 'PREGIH', 'KARANG PANDAN', 'PONG BARU', 'KRASAK', 'PERUM BASMALAH'];

$pageTitle = "Data Kelahiran Penduduk";
ob_start();
?>

<style>
body { background-color: #f8f9fc; }
.statistik-card { transition: all 0.3s; border: none; border-radius: 1rem; box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.04); background: white;}
.statistik-card:hover { transform: translateY(-5px); box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.1); }
.border-left-primary { border-left: 5px solid #4e73df !important; }
.border-left-success { border-left: 5px solid #1cc88a !important; }
.border-left-info { border-left: 5px solid #36b9cc !important; }
.border-left-danger { border-left: 5px solid #e74a3b !important; }

.main-card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); }
.main-card > .card-header { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); border-bottom: none; border-radius: 1rem 1rem 0 0; color: white;}

.table-container { background: white; border-radius: 0 0 1rem 1rem; padding: 0 10px 15px 10px; }
.table thead th { background-color: #f8f9fc; border-bottom: 2px solid #eaecf4; color: #1cc88a; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 15px; }
.table tbody tr { transition: background 0.2s; }
.table tbody tr:hover { background-color: #f1f3f9; }
.table td { vertical-align: middle; color: #5a5c69; border-bottom: 1px solid #eaecf4; padding: 12px 15px; }

.gender-icon { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.8rem; margin-right: 8px;}
.icon-L { background-color: #e3f2fd; color: #36b9cc; }
.icon-P { background-color: #fce4e4; color: #e74a3b; }

.btn-group-action { display: flex; gap: 6px; justify-content: center; }
.action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; color: white; transition: all 0.2s; border: none; cursor: pointer; }
.action-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); filter: brightness(1.1); }
.icon-view { background: #36b9cc; color: #fff; }
.icon-print { background: #858796; color: #fff; }
.icon-edit { background: #f6c23e; color: #fff; }
.icon-delete { background: #e74a3b; color: #fff; }

/* FIX MODAL SCROLLING SAMA PERSIS SEPERTI PENDUDUK.PHP */
.modal-content { border: none; border-radius: 1rem; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow: hidden; display: flex; flex-direction: column; max-height: 100%;}
.modal-header { border-bottom: none; padding: 1.25rem 1.5rem; flex-shrink: 0;}
.modal-body { padding: 1.5rem; overflow-y: auto; flex-grow: 1; }
.modal-footer { border-top: 1px solid #eaecf4; background: #f8f9fc; padding: 1rem 1.5rem; flex-shrink: 0;}

/* Scrollbar Modals */
.modal-body::-webkit-scrollbar { width: 8px; }
.modal-body::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.modal-body::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
.modal-body::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

.form-section-title { font-size: 0.85rem; font-weight: 700; color: #1cc88a; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #eaecf4; padding-bottom: 8px; margin-bottom: 15px; margin-top: 10px;}
.form-control, .form-select { border-radius: 8px; border: 1px solid #d1d3e2; padding: 0.6rem 1rem;}
.form-control:focus, .form-select:focus { border-color: #1cc88a; box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25);}
.form-label { font-weight: 600; color: #5a5c69; font-size: 0.85rem; margin-bottom: 4px;}

/* CSS KHUSUS AUTOCOMPLETE */
.autocomplete-dropdown {
    position: absolute; top: 100%; left: 0; z-index: 1000; display: none; width: 100%; max-height: 250px; overflow-y: auto; background-color: #fff; border: 1px solid #e3e6f0; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); margin-top: 5px;
}
.autocomplete-item {
    padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f8f9fc; transition: background-color 0.2s; text-align: left; width: 100%; background: none; border: none; display: block;
}
.autocomplete-item:hover, .autocomplete-item.active { background-color: #f1f3f9; color: #1cc88a; }

/* CSS View Biodata */
.biodata-table td { padding: 8px 5px; border-bottom: 1px dashed #eaecf4; vertical-align: top; }
.biodata-table tr:last-child td { border-bottom: none; }
.biodata-label { font-weight: 700; color: #5a5c69; width: 35%; }
</style>

<div class="container-fluid px-0">

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-lg" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-lg" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card statistik-card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Kelahiran</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_total); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-baby fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card statistik-card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Bayi Laki-Laki</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_lk); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-mars fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4 mb-md-0">
            <div class="card statistik-card border-left-danger h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Bayi Perempuan</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_pr); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-venus fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card statistik-card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Lahir Bulan Ini</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_bulan_ini); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-calendar-plus fa-2x text-gray-200"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card mb-5">
        <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-baby-carriage me-2"></i>Log Data Kelahiran Desa</h6>
            <div class="d-flex gap-2">
                <a href="export/kelahiran_excel.php" class="btn btn-warning font-weight-bold shadow-sm rounded-pill px-3 text-dark">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </a>
                <button type="button" class="btn btn-light text-success font-weight-bold shadow-sm rounded-pill px-3" onclick="openModalTambah()">
                    <i class="fas fa-plus-circle me-1"></i> Lapor Kelahiran Baru
                </button>
            </div>
        </div>
        
        <div class="card-body bg-white pb-0">
            <form method="GET" class="mb-4 bg-light p-3 rounded-lg border">
                <div class="row g-2 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label small text-muted">Pencarian Data</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-success"></i></span>
                            <input type="text" class="form-control border-start-0 border-left-0" name="search" placeholder="Cari Nama Bayi atau Nama Orang Tua..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-search"></i> Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="kelahiran.php" class="btn btn-secondary btn-sm" title="Reset"><i class="fas fa-redo"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="25%">Nama Bayi</th>
                            <th width="20%">TTL</th>
                            <th width="25%">Orang Tua (Ayah / Ibu)</th>
                            <th width="10%">Fisik</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="text-center text-muted"><?php echo $no++; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="gender-icon <?php echo $row['jenis_kelamin'] == 'LAKI-LAKI' ? 'icon-L' : 'icon-P'; ?>" title="<?php echo $row['jenis_kelamin']; ?>">
                                            <i class="fas <?php echo $row['jenis_kelamin'] == 'LAKI-LAKI' ? 'fa-mars' : 'fa-venus'; ?>"></i>
                                        </div>
                                        <div>
                                            <span class="font-weight-bold text-gray-800 text-uppercase d-block"><?php echo htmlspecialchars($row['nama_bayi']); ?></span>
                                            <small class="text-muted">Anak ke: <?php echo $row['anak_ke'] ?: '-'; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-uppercase"><?php echo htmlspecialchars($row['tempat_lahir']); ?></div>
                                    <div class="small font-weight-bold text-primary"><?php echo date('d M Y', strtotime($row['tanggal_lahir'])); ?></div>
                                </td>
                                <td>
                                    <div class="small text-dark mb-1"><i class="fas fa-male text-muted me-1"></i> <?php echo htmlspecialchars($row['nama_ayah'] ?: '-'); ?></div>
                                    <div class="small text-dark"><i class="fas fa-female text-muted me-1"></i> <?php echo htmlspecialchars($row['nama_ibu'] ?: '-'); ?></div>
                                </td>
                                <td>
                                    <div class="small text-muted mb-1"><?php echo $row['berat_bayi'] ? $row['berat_bayi'].' Kg' : '-'; ?></div>
                                    <div class="small text-muted"><?php echo $row['panjang_bayi'] ? $row['panjang_bayi'].' cm' : '-'; ?></div>
                                </td>
                                <td>
                                    <div class="btn-group-action">
                                        <button class="action-icon icon-view btn-view" onclick='viewData(<?php echo json_encode($row); ?>)' title="Lihat Detail"><i class="fas fa-eye"></i></button>
                                        <a href="surat/kelahiran.php?id=<?php echo $row['id_kelahiran']; ?>" class="action-icon icon-print" title="Cetak Surat Kelahiran"><i class="fas fa-print"></i></a>
                                        <button class="action-icon icon-edit btn-edit" onclick='editData(<?php echo json_encode($row); ?>)' title="Edit Kelahiran"><i class="fas fa-edit"></i></button>
                                        <button class="action-icon icon-delete btn-hapus" onclick='deleteData(<?php echo $row['id_kelahiran']; ?>, "<?php echo htmlspecialchars($row['nama_bayi']); ?>")' title="Hapus Log"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-clipboard-list fa-3x text-gray-300 mb-3"></i>
                                    <h5 class="text-gray-500">Log Kelahiran Kosong</h5>
                                    <p class="text-muted">Tidak ada data bayi yang ditemukan.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewKelahiranModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-id-card me-2"></i>Biodata Kelahiran Bayi</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalView()"></button>
            </div>
            <div class="modal-body bg-white p-4">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless biodata-table text-dark m-0">
                        <tbody>
                            <tr><td class="biodata-label">NIK Bayi</td><td width="2%">:</td><td id="view_nik" class="font-monospace text-primary font-weight-bold"></td></tr>
                            <tr><td class="biodata-label">Nama Lengkap</td><td>:</td><td id="view_nama_bayi" class="text-uppercase font-weight-bold"></td></tr>
                            <tr><td class="biodata-label">Tempat, Tgl Lahir</td><td>:</td><td id="view_ttl" class="text-uppercase"></td></tr>
                            <tr><td class="biodata-label">Jenis Kelamin</td><td>:</td><td id="view_jk"></td></tr>
                            <tr><td class="biodata-label">Agama</td><td>:</td><td id="view_agama" class="text-uppercase"></td></tr>
                            <tr><td class="biodata-label">Anak Ke-</td><td>:</td><td id="view_anak"></td></tr>
                            <tr><td class="biodata-label">Berat / Panjang</td><td>:</td><td id="view_fisik"></td></tr>
                            <tr><td colspan="3"><hr class="my-2"></td></tr>
                            <tr><td class="biodata-label">Nama Ayah</td><td>:</td><td id="view_ayah" class="text-uppercase font-weight-bold text-secondary"></td></tr>
                            <tr><td class="biodata-label">Nama Ibu</td><td>:</td><td id="view_ibu" class="text-uppercase font-weight-bold text-secondary"></td></tr>
                            <tr><td class="biodata-label">Alamat Domisili</td><td>:</td><td id="view_alamat_lengkap" class="text-uppercase"></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeModalView()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="tambahKelahiranModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="kelahiran.php" style="display:flex; flex-direction:column; height:100%;">
                <div class="modal-header bg-gradient-success text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle me-2"></i>Form Laporan Kelahiran Baru</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModalTambah()"></button>
                </div>
                
                <div class="modal-body bg-light p-4">
                    <div class="alert alert-info shadow-sm mb-4">
                        <i class="fas fa-info-circle me-2"></i> <strong>Pemberitahuan:</strong> Data yang diinput pada form ini akan otomatis tersimpan sebagai <b>Log Kelahiran</b> sekaligus ditambahkan otomatis ke <b>Master Penduduk</b>.
                    </div>

                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <div class="form-section-title"><i class="fas fa-baby me-2"></i>Identitas Bayi</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">NIK Bayi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nik" required maxlength="16" pattern="[0-9]{16}" placeholder="Masukkan 16 digit angka NIK">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap Bayi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="nama_bayi" required placeholder="Nama sesuai keterangan lahir">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="tempat_lahir" required placeholder="Contoh: BANGKALAN">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_lahir" required max="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Jenis Kelamin</label>
                                <select class="form-select form-control" name="jenis_kelamin">
                                    <option value="LAKI-LAKI">Laki-Laki</option>
                                    <option value="PEREMPUAN">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Agama</label>
                                <select class="form-select form-control" name="agama">
                                    <option value="ISLAM">Islam</option>
                                    <option value="KRISTEN">Kristen Protestan</option>
                                    <option value="KATOLIK">Katolik</option>
                                    <option value="HINDU">Hindu</option>
                                    <option value="BUDDHA">Buddha</option>
                                    <option value="KONGHUCU">Konghucu</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Anak Ke-</label>
                                <input type="number" class="form-control" name="anak_ke" placeholder="Contoh: 1, 2, 3">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Berat Bayi (Kg)</label>
                                <input type="number" step="0.01" class="form-control" name="berat_bayi" placeholder="Contoh: 3.2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Panjang Bayi (Cm)</label>
                                <input type="number" step="0.1" class="form-control" name="panjang_bayi" placeholder="Contoh: 48.5">
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <div class="form-section-title"><i class="fas fa-users me-2"></i>Data Orang Tua (Live Search)</div>
                        <div class="row g-3">
                            <div class="col-md-6 position-relative">
                                <label class="form-label">Nama Ayah Kandung <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="nama_ayah" id="input_ayah_add" required autocomplete="off" placeholder="Ketik min 2 huruf...">
                                <div id="drop_ayah_add" class="autocomplete-dropdown"></div>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label class="form-label">Nama Ibu Kandung <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="nama_ibu" id="input_ibu_add" required autocomplete="off" placeholder="Ketik min 2 huruf...">
                                <div id="drop_ibu_add" class="autocomplete-dropdown"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4">
                        <div class="form-section-title"><i class="fas fa-home me-2"></i>Alamat Domisili</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Dusun</label>
                                <div class="d-flex mb-2 gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="dusun_option" id="dusun_pilih" value="pilih" checked onclick="toggleDusun('pilih')">
                                        <label class="form-check-label text-muted" for="dusun_pilih" style="margin-left:5px;">Pilih Daftar</label>
                                    </div>
                                    <div class="form-check" style="margin-left: 15px;">
                                        <input class="form-check-input" type="radio" name="dusun_option" id="dusun_tulis" value="tulis" onclick="toggleDusun('tulis')">
                                        <label class="form-check-label text-muted" for="dusun_tulis" style="margin-left:5px;">Ketik Manual</label>
                                    </div>
                                </div>
                                <div id="dusun_select_container">
                                    <select class="form-select form-control" name="dusun_select" id="dusun_select">
                                        <?php foreach ($daftar_dusun as $dusun): ?>
                                            <option value="<?php echo $dusun; ?>"><?php echo $dusun; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="dusun_input_container" style="display: none;">
                                    <input type="text" class="form-control text-uppercase" name="dusun_custom" id="dusun_custom" placeholder="Ketik nama dusun...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">RT/RW <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="rt_rw" value="001/002" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea class="form-control text-uppercase" name="alamat" rows="2" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalTambah()">Batal</button>
                    <button type="submit" name="submit_tambah" class="btn btn-success px-5 rounded-pill shadow-sm"><i class="fas fa-save me-2"></i>Simpan Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editKelahiranModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="kelahiran.php" style="display:flex; flex-direction:column; height:100%;">
                <div class="modal-header bg-gradient-warning text-white">
                    <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-edit me-2"></i>Edit Data Kelahiran</h5>
                    <button type="button" class="btn-close" onclick="closeModalEdit()"></button>
                </div>
                
                <div class="modal-body bg-light p-4">
                    <input type="hidden" name="id_kelahiran" id="edit_id">
                    
                    <div class="alert alert-warning shadow-sm mb-4 text-dark">
                        <i class="fas fa-exclamation-triangle me-2"></i> <strong>Perhatian:</strong> Perubahan pada form ini akan otomatis diperbarui di <b>Master Penduduk</b>.
                    </div>

                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <div class="form-section-title text-warning"><i class="fas fa-baby me-2"></i>Identitas Bayi</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">NIK Bayi</label>
                                <input type="text" class="form-control bg-light" name="nik" id="edit_nik" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap Bayi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="nama_bayi" id="edit_nama_bayi" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="tempat_lahir" id="edit_tempat_lahir" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_lahir" id="edit_tanggal_lahir" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Jenis Kelamin</label>
                                <select class="form-select form-control" name="jenis_kelamin" id="edit_jenis_kelamin">
                                    <option value="LAKI-LAKI">Laki-Laki</option>
                                    <option value="PEREMPUAN">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Agama</label>
                                <select class="form-select form-control" name="agama" id="edit_agama">
                                    <option value="ISLAM">Islam</option>
                                    <option value="KRISTEN">Kristen Protestan</option>
                                    <option value="KATOLIK">Katolik</option>
                                    <option value="HINDU">Hindu</option>
                                    <option value="BUDDHA">Buddha</option>
                                    <option value="KONGHUCU">Konghucu</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Anak Ke-</label>
                                <input type="number" class="form-control" name="anak_ke" id="edit_anak_ke">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Berat Bayi (Kg)</label>
                                <input type="number" step="0.01" class="form-control" name="berat_bayi" id="edit_berat_bayi">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Panjang Bayi (Cm)</label>
                                <input type="number" step="0.1" class="form-control" name="panjang_bayi" id="edit_panjang_bayi">
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <div class="form-section-title text-warning"><i class="fas fa-users me-2"></i>Data Orang Tua</div>
                        <div class="row g-3">
                            <div class="col-md-6 position-relative">
                                <label class="form-label">Nama Ayah Kandung <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="nama_ayah" id="input_ayah_edit" required autocomplete="off">
                                <div id="drop_ayah_edit" class="autocomplete-dropdown"></div>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label class="form-label">Nama Ibu Kandung <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="nama_ibu" id="input_ibu_edit" required autocomplete="off">
                                <div id="drop_ibu_edit" class="autocomplete-dropdown"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4">
                        <div class="form-section-title text-warning"><i class="fas fa-home me-2"></i>Alamat Domisili</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Dusun <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="dusun" id="edit_dusun" list="list_dusun" required>
                                <datalist id="list_dusun">
                                    <?php foreach ($daftar_dusun as $dsn): ?>
                                        <option value="<?php echo $dsn; ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <small class="text-muted" style="font-size:11px;">Pilih dari daftar atau ketik manual.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">RT/RW <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="rt_rw" id="edit_rtrw" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea class="form-control text-uppercase" name="alamat" id="edit_alamat" rows="2" required></textarea>
                            </div>
                        </div>
                    </div>

                </div>
                
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalEdit()">Batal</button>
                    <button type="submit" name="submit_edit" class="btn btn-warning px-5 rounded-pill shadow-sm text-dark font-weight-bold"><i class="fas fa-save me-2"></i>Update Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmHapusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form method="POST" action="kelahiran.php" style="display:flex; flex-direction:column; height:100%;">
                <div class="modal-header bg-gradient-danger text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle me-2"></i>Hapus Log Kelahiran</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModalHapus()"></button>
                </div>
                <div class="modal-body py-4 text-center">
                    <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 70px; height: 70px;">
                        <i class="fas fa-trash-alt fa-2x"></i>
                    </div>
                    <h5 class="text-gray-800 font-weight-bold mb-2" id="namaHapusBayi">Nama Bayi</h5>
                    <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus data riwayat kelahiran ini?<br><b>Catatan:</b> Tindakan ini <u>tidak akan menghapus</u> data anak dari menu Master Penduduk.</p>
                    <input type="hidden" name="id_hapus" id="inputIdHapus">
                </div>
                <div class="modal-footer bg-light d-flex justify-content-center">
                    <button type="button" class="btn btn-light border px-4 rounded-pill me-2" onclick="closeModalHapus()">Batal</button>
                    <button type="submit" name="delete_kelahiran" class="btn btn-danger px-4 rounded-pill shadow-sm"><i class="fas fa-trash me-1"></i> Ya, Hapus Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ==================== INISIALISASI MODAL & JAVASCRIPT ====================
let modalTambahKelahiran = null;
let modalEditKelahiran = null;
let modalViewKelahiran = null;
let modalHapusKelahiran = null;

document.addEventListener('DOMContentLoaded', function() {
    if(document.getElementById('tambahKelahiranModal')) modalTambahKelahiran = new bootstrap.Modal(document.getElementById('tambahKelahiranModal'));
    if(document.getElementById('editKelahiranModal')) modalEditKelahiran = new bootstrap.Modal(document.getElementById('editKelahiranModal'));
    if(document.getElementById('viewKelahiranModal')) modalViewKelahiran = new bootstrap.Modal(document.getElementById('viewKelahiranModal'));
    if(document.getElementById('confirmHapusModal')) modalHapusKelahiran = new bootstrap.Modal(document.getElementById('confirmHapusModal'));

    setupAutocomplete('input_ayah_add', 'drop_ayah_add', 'LAKI-LAKI');
    setupAutocomplete('input_ibu_add', 'drop_ibu_add', 'PEREMPUAN');
    setupAutocomplete('input_ayah_edit', 'drop_ayah_edit', 'LAKI-LAKI');
    setupAutocomplete('input_ibu_edit', 'drop_ibu_edit', 'PEREMPUAN');
});

function cleanupBackdrop() { 
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove()); 
    document.body.classList.remove('modal-open'); 
    document.body.style.overflow=''; 
    document.body.style.paddingRight=''; 
}

function openModalTambah() { if(modalTambahKelahiran) modalTambahKelahiran.show(); }
function closeModalTambah() { if(modalTambahKelahiran) modalTambahKelahiran.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalEdit() { if(modalEditKelahiran) modalEditKelahiran.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalView() { if(modalViewKelahiran) modalViewKelahiran.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalHapus() { if(modalHapusKelahiran) modalHapusKelahiran.hide(); setTimeout(cleanupBackdrop, 100); }

// ==================== FUNGSI DATA TABEL ====================
function formatDateId(dateString) {
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

function viewData(data) {
    document.getElementById('view_nik').innerText = data.nik_bayi;
    document.getElementById('view_nama_bayi').innerText = data.nama_bayi;
    document.getElementById('view_ttl').innerText = data.tempat_lahir + ', ' + formatDateId(data.tanggal_lahir);
    document.getElementById('view_jk').innerText = data.jenis_kelamin;
    document.getElementById('view_agama').innerText = data.agama || '-';
    document.getElementById('view_ayah').innerText = data.nama_ayah || '-';
    document.getElementById('view_ibu').innerText = data.nama_ibu || '-';
    document.getElementById('view_anak').innerText = data.anak_ke || '-';
    
    let fisik = (data.berat_bayi ? data.berat_bayi + ' Kg' : '-') + ' / ' + (data.panjang_bayi ? data.panjang_bayi + ' cm' : '-');
    document.getElementById('view_fisik').innerText = fisik;
    
    let alamat_lengkap = (data.alamat || '') + ' RT/RW ' + (data.rt_rw || '') + ' DSN. ' + (data.dusun || '');
    document.getElementById('view_alamat_lengkap').innerText = alamat_lengkap;
    
    if(modalViewKelahiran) modalViewKelahiran.show();
}

function editData(data) {
    document.getElementById('edit_id').value = data.id_kelahiran;
    document.getElementById('edit_nik').value = data.nik_bayi;
    document.getElementById('edit_nama_bayi').value = data.nama_bayi;
    document.getElementById('edit_tempat_lahir').value = data.tempat_lahir;
    document.getElementById('edit_tanggal_lahir').value = data.tanggal_lahir;
    document.getElementById('edit_jenis_kelamin').value = data.jenis_kelamin;
    document.getElementById('edit_agama').value = data.agama || 'ISLAM';
    document.getElementById('input_ayah_edit').value = data.nama_ayah;
    document.getElementById('input_ibu_edit').value = data.nama_ibu;
    document.getElementById('edit_anak_ke').value = data.anak_ke;
    document.getElementById('edit_berat_bayi').value = data.berat_bayi;
    document.getElementById('edit_panjang_bayi').value = data.panjang_bayi;
    document.getElementById('edit_dusun').value = data.dusun || '';
    document.getElementById('edit_rtrw').value = data.rt_rw || '001/002';
    document.getElementById('edit_alamat').value = data.alamat || '';
    
    if(modalEditKelahiran) modalEditKelahiran.show();
}

function deleteData(id, nama) {
    document.getElementById('inputIdHapus').value = id;
    document.getElementById('namaHapusBayi').innerText = nama;
    if(modalHapusKelahiran) modalHapusKelahiran.show();
}

// ==================== AUTOCOMPLETE & LAINNYA ====================
function setupAutocomplete(inputId, dropdownId, jenisKelamin) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    let timeoutId;

    input.addEventListener('input', function() {
        clearTimeout(timeoutId);
        const keyword = this.value.trim();
        if (keyword.length >= 2) {
            dropdown.innerHTML = '<div class="p-2 text-center text-muted small"><i class="fas fa-spinner fa-spin"></i> Mencari...</div>';
            dropdown.style.display = 'block';
            timeoutId = setTimeout(() => {
                fetch(`kelahiran.php?ajax_cari_penduduk=${encodeURIComponent(keyword)}&jk=${jenisKelamin}`)
                .then(res => res.json())
                .then(data => {
                    dropdown.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const btn = document.createElement('button');
                            btn.className = 'autocomplete-item';
                            btn.innerHTML = `<span class="fw-bold d-block text-uppercase">${item.nama_penduduk}</span><small class="text-muted">NIK: ${item.nik}</small>`;
                            btn.onclick = function(e) {
                                e.preventDefault();
                                input.value = item.nama_penduduk; 
                                dropdown.style.display = 'none';
                            };
                            dropdown.appendChild(btn);
                        });
                    } else { dropdown.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fas fa-info-circle mb-1"></i><br>Tidak ditemukan</div>'; }
                }).catch(() => { dropdown.innerHTML = '<div class="p-2 text-danger small">Gagal memuat data</div>'; });
            }, 300);
        } else { dropdown.style.display = 'none'; }
    });
    document.addEventListener('click', function(e) { if (e.target !== input && e.target !== dropdown) dropdown.style.display = 'none'; });
}

function toggleDusun(mode) {
    if (mode === 'pilih') { 
        document.getElementById('dusun_select_container').style.display = 'block'; 
        document.getElementById('dusun_input_container').style.display = 'none'; 
        document.getElementById('dusun_custom').removeAttribute('required');
    } else { 
        document.getElementById('dusun_select_container').style.display = 'none'; 
        document.getElementById('dusun_input_container').style.display = 'block'; 
        document.getElementById('dusun_custom').setAttribute('required', 'true');
    }
}
</script>

<?php
$content = ob_get_clean();
include '../includes/base.php';
?>