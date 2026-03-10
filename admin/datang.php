<?php
session_start();

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit();
}

include "../db/koneksi.php";

// ==================== PROSES TAMBAH KEDATANGAN (DUAL INSERT) ====================
if (isset($_POST["submit_tambah"])) {
    // 1. Data Kedatangan Khusus
    $tanggal_datang = mysqli_real_escape_string($conn, $_POST['tanggal_datang']);
    $alamat_asal = mysqli_real_escape_string($conn, $_POST['alamat_asal']);
    $alasan_datang = mysqli_real_escape_string($conn, $_POST['alasan_datang']);
    
    // 2. Data Master Penduduk (Full)
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $agama = mysqli_real_escape_string($conn, $_POST['agama']);
    $pendidikan = mysqli_real_escape_string($conn, $_POST['pendidikan']);
    $pekerjaan = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $status_kawin = mysqli_real_escape_string($conn, $_POST['status_kawin']);
    $nama_ayah = mysqli_real_escape_string($conn, $_POST['nama_ayah']);
    $nama_ibu = mysqli_real_escape_string($conn, $_POST['nama_ibu']);
    
    // 3. Data Alamat Baru di Desa
    $dusun = mysqli_real_escape_string($conn, $_POST['dusun_option'] == 'pilih' ? $_POST['dusun_select'] : $_POST['dusun_custom']);
    $rt_rw = mysqli_real_escape_string($conn, $_POST['rt_rw']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

    // Cek apakah NIK sudah terdaftar
    $cek_nik = mysqli_query($conn, "SELECT nik FROM penduduk WHERE nik = '$nik'");
    if (mysqli_num_rows($cek_nik) > 0) {
        $_SESSION['error_message'] = "Gagal! NIK tersebut sudah terdaftar di Master Penduduk.";
        header("Location: datang.php");
        exit();
    }

    // Mulai Transaksi (Jika salah satu gagal, dibatalkan semua)
    mysqli_begin_transaction($conn);

    try {
        // A. Insert ke Master Penduduk
        $query_penduduk = "INSERT INTO penduduk 
            (nik, nama_penduduk, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, pendidikan, pekerjaan, status_kawin, alamat, rt_rw, dusun, kel_des, kecamatan, kabupaten_kota, kodepos, provinsi, nama_ayah, nama_ibu) 
            VALUES 
            ('$nik', '$nama', '$tempat_lahir', '$tanggal_lahir', '$jenis_kelamin', '$agama', '$pendidikan', '$pekerjaan', '$status_kawin', '$alamat', '$rt_rw', '$dusun', 'SUKOLILO TIMUR', 'LABANG', 'BANGKALAN', '69162', 'JAWA TIMUR', '$nama_ayah', '$nama_ibu')";
        mysqli_query($conn, $query_penduduk);

        // B. Insert Log ke Tabel Kedatangan
        $query_datang = "INSERT INTO kedatangan 
            (nik_datang, nama_datang, jenis_kelamin, tanggal_datang, alamat_asal, alasan_datang) 
            VALUES 
            ('$nik', '$nama', '$jenis_kelamin', '$tanggal_datang', '$alamat_asal', '$alasan_datang')";
        mysqli_query($conn, $query_datang);

        mysqli_commit($conn);
        $_SESSION['success_message'] = "Warga pendatang berhasil didaftarkan ke Master Penduduk dan dicatat di Arsip Kedatangan!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
    
    header("Location: datang.php");
    exit();
}

// ==================== PROSES EDIT (UPDATE) ====================
if (isset($_POST['submit_edit'])) {
    $id_datang = (int)$_POST['id_datang'];
    $nik = mysqli_real_escape_string($conn, $_POST['nik']); // NIK readonly
    
    // Data Kedatangan
    $tanggal_datang = mysqli_real_escape_string($conn, $_POST['tanggal_datang']);
    $alamat_asal = mysqli_real_escape_string($conn, $_POST['alamat_asal']);
    $alasan_datang = mysqli_real_escape_string($conn, $_POST['alasan_datang']);
    
    // Data Penduduk
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $dusun = mysqli_real_escape_string($conn, $_POST['dusun']);
    $rt_rw = mysqli_real_escape_string($conn, $_POST['rt_rw']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

    mysqli_begin_transaction($conn);

    try {
        // Update Log Kedatangan
        $q_upd_datang = "UPDATE kedatangan SET 
            nama_datang='$nama', jenis_kelamin='$jenis_kelamin', tanggal_datang='$tanggal_datang', 
            alamat_asal='$alamat_asal', alasan_datang='$alasan_datang' 
            WHERE id_datang=$id_datang";
        mysqli_query($conn, $q_upd_datang);

        // Update Sinkron ke Master Penduduk
        $q_upd_penduduk = "UPDATE penduduk SET 
            nama_penduduk='$nama', jenis_kelamin='$jenis_kelamin', dusun='$dusun', rt_rw='$rt_rw', alamat='$alamat'
            WHERE nik='$nik'";
        mysqli_query($conn, $q_upd_penduduk);

        mysqli_commit($conn);
        $_SESSION['success_message'] = "Log kedatangan dan data penduduk berhasil diperbarui!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Gagal memperbarui: " . $e->getMessage();
    }
    header("Location: datang.php");
    exit();
}

// ==================== PROSES HAPUS (DELETE LOG) ====================
if (isset($_POST['delete_datang'])) {
    $id = (int)$_POST['id_hapus'];
    // Hapus Log Kedatangan Saja (Data Penduduk tetap utuh, kecuali dihapus dari Master Penduduk)
    if(mysqli_query($conn, "DELETE FROM kedatangan WHERE id_datang = $id")){
        $_SESSION['success_message'] = "Log riwayat kedatangan berhasil dihapus!";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus log kedatangan.";
    }
    header("Location: datang.php");
    exit();
}

// ==================== QUERY DATA TAMPIL ====================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "WHERE 1=1";
if (!empty($search)) {
    $where .= " AND (d.nama_datang LIKE '%$search%' OR d.nik_datang LIKE '%$search%')";
}

// JOIN dengan Penduduk agar Alamat Baru terlihat
$query = "SELECT d.*, p.dusun, p.rt_rw, p.alamat AS alamat_baru 
          FROM kedatangan d 
          LEFT JOIN penduduk p ON d.nik_datang = p.nik 
          $where 
          ORDER BY d.tanggal_datang DESC";
$result = mysqli_query($conn, $query);

// STATISTIK
$stat_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kedatangan"))['total'];
$stat_lk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kedatangan WHERE jenis_kelamin = 'LAKI-LAKI'"))['total'];
$stat_pr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kedatangan WHERE jenis_kelamin = 'PEREMPUAN'"))['total'];
$stat_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kedatangan WHERE MONTH(tanggal_datang) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_datang) = YEAR(CURRENT_DATE())"))['total'];

$daftar_dusun = ['KEJAWAN', 'SEPURAN', 'BUDDAN', 'PASEREAN', 'LANGGAR', 'MORLEKE', 'PREGIH', 'KARANG PANDAN', 'PONG BARU', 'KRASAK', 'PERUM BASMALAH'];

$pageTitle = "Data Penduduk Datang";
ob_start();
?>

<style>
body { background-color: #f8f9fc; }
.statistik-card { transition: all 0.3s; border: none; border-radius: 1rem; box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.04); background: white;}
.statistik-card:hover { transform: translateY(-5px); box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.1); }
.border-left-primary { border-left: 5px solid #4e73df !important; }
.border-left-info { border-left: 5px solid #36b9cc !important; }
.border-left-danger { border-left: 5px solid #e74a3b !important; }
.border-left-success { border-left: 5px solid #1cc88a !important; }

.main-card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); }
.main-card > .card-header { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); border-bottom: none; border-radius: 1rem 1rem 0 0; color: white;}

.table-container { background: white; border-radius: 0 0 1rem 1rem; padding: 0 10px 15px 10px; }
.table thead th { background-color: #f8f9fc; border-bottom: 2px solid #eaecf4; color: #4e73df; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 15px; }
.table tbody tr { transition: background 0.2s; }
.table tbody tr:hover { background-color: #f1f3f9; }
.table td { vertical-align: middle; color: #5a5c69; border-bottom: 1px solid #eaecf4; padding: 12px 15px; }

.btn-group-action { display: flex; gap: 6px; justify-content: center; }
.action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; color: white; transition: all 0.2s; border: none; cursor: pointer; }
.action-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); filter: brightness(1.1); }
.icon-view { background: #36b9cc; color: #fff; }
.icon-edit { background: #f6c23e; color: #fff; }
.icon-delete { background: #e74a3b; color: #fff; }

/* MODAL FIX SCROLLING PENDUDUK.PHP STYLE */
.modal-content { border: none; border-radius: 1rem; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow: hidden; display: flex; flex-direction: column; max-height: 100%;}
.modal-header { border-bottom: none; padding: 1.25rem 1.5rem; flex-shrink: 0;}
.modal-body { padding: 1.5rem; overflow-y: auto; flex-grow: 1; }
.modal-footer { border-top: 1px solid #eaecf4; background: #f8f9fc; padding: 1rem 1.5rem; flex-shrink: 0;}

/* Scrollbar Modals */
.modal-body::-webkit-scrollbar { width: 8px; }
.modal-body::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.modal-body::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }

.form-section-title { font-size: 0.85rem; font-weight: 700; color: #4e73df; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #eaecf4; padding-bottom: 8px; margin-bottom: 15px; margin-top: 10px;}
/* FIX TEKS KEPOTONG PADA INPUT & SELECT */
.form-control, .form-select { 
    border-radius: 8px; 
    border: 1px solid #d1d3e2; 
    padding: 0.5rem 1rem !important; /* Paksa padding lebih kecil di atas/bawah */
    font-size: 0.9rem !important; 
    line-height: 1.6 !important; /* Beri jarak baris lebih besar agar huruf (p, g) tidak tenggelam */
    height: auto !important; /* KUNCI UTAMA: Paksa tinggi otomatis menyesuaikan isi form */
    min-height: 40px !important; /* Jaga tinggi minimal agar tetap proporsional */
    background-color: #fdfdfd;
}
.form-select {
    padding-right: 2.5rem !important; /* Jaga jarak aman agar teks tidak nabrak ikon panah */
}
.form-control:focus, .form-select:focus { border-color: #4e73df; box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);}
.form-label { font-weight: 600; color: #5a5c69; font-size: 0.85rem; margin-bottom: 4px;}

/* BIODATA VIEW */
.biodata-table td { padding: 8px 5px; border-bottom: 1px dashed #eaecf4; vertical-align: top; }
.biodata-table tr:last-child td { border-bottom: none; }
.biodata-label { font-weight: 700; color: #5a5c69; width: 35%; }
</style>

<div class="container-fluid px-0">

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-lg" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
        <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-lg" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error_message']; ?>
        <button type="button" class="btn-close" onclick="closeAlert(this)"></button>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card statistik-card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pendatang</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_total); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-plane-arrival fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card statistik-card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Laki-Laki</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_lk); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-male fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4 mb-md-0">
            <div class="card statistik-card border-left-danger h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Perempuan</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_pr); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-female fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card statistik-card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Datang Bulan Ini</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_bulan_ini); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-calendar-plus fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card mb-5">
        <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-plane-arrival me-2"></i>Arsip Data Penduduk Datang</h6>
            <div class="d-flex gap-2">
                <a href="export/datang_excel.php" class="btn btn-warning font-weight-bold shadow-sm rounded-pill px-3 text-dark">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </a>
                <button type="button" class="btn btn-light text-primary font-weight-bold shadow-sm rounded-pill px-3" onclick="openModalTambah()">
                    <i class="fas fa-plus-circle me-1"></i> Lapor Kedatangan Baru
                </button>
            </div>
        </div>
        
        <div class="card-body bg-white pb-0">
            <form method="GET" class="mb-4 bg-light p-3 rounded-lg border">
                <div class="row g-2 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label small text-muted">Pencarian Data</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-primary"></i></span>
                            </div>
                            <input type="text" class="form-control border-left-0" name="search" placeholder="Cari Nama Pendatang atau NIK..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i> Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="datang.php" class="btn btn-secondary btn-sm" title="Reset"><i class="fas fa-redo"></i></a>
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
                            <th width="30%">Identitas Pendatang</th>
                            <th width="20%">Tanggal Datang</th>
                            <th width="30%">Alamat Asal & Tujuan</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="text-center text-muted"><?php echo $no++; ?></td>
                                <td>
                                    <span class="font-weight-bold text-gray-800 text-uppercase d-block"><?php echo htmlspecialchars($row['nama_datang']); ?></span>
                                    <small class="text-muted font-monospace d-block">NIK: <?php echo htmlspecialchars($row['nik_datang']); ?></small>
                                    <small class="badge badge-light border border-secondary mt-1"><?php echo $row['jenis_kelamin']; ?></small>
                                </td>
                                <td>
                                    <div class="small font-weight-bold text-primary"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($row['tanggal_datang'])); ?></div>
                                </td>
                                <td>
                                    <div class="small text-muted mb-1 text-uppercase text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($row['alamat_asal']); ?>">
                                        <i class="fas fa-plane-departure text-warning me-1"></i> <?php echo htmlspecialchars($row['alamat_asal']); ?>
                                    </div>
                                    <div class="small text-dark text-uppercase text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($row['alamat_baru'] . ' DSN. ' . $row['dusun']); ?>">
                                        <i class="fas fa-map-marker-alt text-success me-1"></i> <?php echo ($row['alamat_baru'] ?? '') . ' DSN ' . ($row['dusun'] ?? ''); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group-action">
                                        <button class="action-icon icon-view" onclick='viewData(<?php echo json_encode($row); ?>)' title="Lihat Detail"><i class="fas fa-eye"></i></button>
                                        <button class="action-icon icon-edit" onclick='editData(<?php echo json_encode($row); ?>)' title="Edit Log"><i class="fas fa-edit"></i></button>
                                        <button class="action-icon icon-delete" onclick="bukaModalHapus(<?php echo $row['id_datang']; ?>, '<?php echo htmlspecialchars($row['nama_datang']); ?>')" title="Hapus Arsip"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-suitcase-rolling fa-3x text-gray-300 mb-3"></i>
                                    <h5 class="text-gray-500">Arsip Kedatangan Kosong</h5>
                                    <p class="text-muted">Belum ada data warga pendatang yang dicatat.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewDatangModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-id-card me-2"></i>Detail Penduduk Masuk (Pendatang)</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalView()"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless biodata-table text-dark m-0">
                        <tbody>
                            <tr><td class="biodata-label">NIK Pendatang</td><td width="2%">:</td><td id="view_nik" class="font-monospace text-primary font-weight-bold"></td></tr>
                            <tr><td class="biodata-label">Nama Lengkap</td><td>:</td><td id="view_nama" class="text-uppercase font-weight-bold"></td></tr>
                            <tr><td class="biodata-label">Jenis Kelamin</td><td>:</td><td id="view_jk"></td></tr>
                            <tr><td colspan="3"><hr class="my-2"></td></tr>
                            <tr><td class="biodata-label text-warning">Tgl Kedatangan</td><td>:</td><td id="view_tgl_datang" class="font-weight-bold"></td></tr>
                            <tr><td class="biodata-label text-warning">Alamat Asal</td><td>:</td><td id="view_alamat_asal" class="text-uppercase"></td></tr>
                            <tr><td class="biodata-label text-warning">Alasan Pindah</td><td>:</td><td id="view_alasan" class="text-uppercase"></td></tr>
                            <tr><td colspan="3"><hr class="my-2"></td></tr>
                            <tr><td class="biodata-label text-success">Domisili Desa Saat Ini</td><td>:</td><td id="view_alamat_baru" class="text-uppercase font-weight-bold text-success"></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-end">
                <button type="button" class="btn btn-secondary rounded-pill px-4" onclick="closeModalView()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="tambahDatangModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" action="datang.php" class="modal-content" style="display:flex; flex-direction:column; height:100%;">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-plane-arrival me-2"></i>Laporan Kedatangan Penduduk Baru</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalTambah()"></button>
            </div>
            
            <div class="modal-body bg-light p-4">
                <div class="alert alert-info shadow-sm mb-4">
                    <i class="fas fa-info-circle me-2"></i> <strong>Pemberitahuan:</strong> Mengisi form ini akan secara otomatis mendaftarkan pendatang ini sebagai <u>Warga Desa Tetap</u> di tabel Master Penduduk, sekaligus menyimpan riwayat asal di tabel Kedatangan.
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <div class="form-section-title text-warning"><i class="fas fa-suitcase me-2"></i>Data Kedatangan Warga</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Datang / Lapor <span class="text-danger">*</span></label>
                            <input type="date" class="form-control border-warning" name="tanggal_datang" required max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Alasan Datang <span class="text-danger">*</span></label>
                            <select class="form-control form-select border-warning" name="alasan_datang" required>
                                <option value="PEKERJAAN">Pekerjaan / Dinas</option>
                                <option value="PENDIDIKAN">Pendidikan</option>
                                <option value="PERUMAHAN">Beli / Pindah Rumah</option>
                                <option value="KELUARGA">Keluarga (Ikut Suami/Istri/Ortu)</option>
                                <option value="LAINNYA">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Alamat Asal (Lengkap) <span class="text-danger">*</span></label>
                            <textarea class="form-control border-warning text-uppercase" name="alamat_asal" rows="2" placeholder="Contoh: JL. MELATI NO. 45, KEL. SUKA, KEC. MAJU, KAB. BANDUNG" required></textarea>
                            <small class="text-muted">Ketikkan daerah asal dengan lengkap.</small>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <div class="form-section-title text-primary"><i class="fas fa-id-card me-2"></i>Biodata Pendatang (Sesuai KTP)</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">NIK (16 Digit) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nik" required maxlength="16" pattern="[0-9]{16}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="nama" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="tempat_lahir" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_lahir" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select class="form-control form-select" name="jenis_kelamin">
                                <option value="LAKI-LAKI">Laki-Laki</option>
                                <option value="PEREMPUAN">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Agama</label>
                            <select class="form-control form-select" name="agama">
                                <option value="ISLAM">Islam</option>
                                <option value="KRISTEN">Kristen Protestan</option>
                                <option value="KATOLIK">Katolik</option>
                                <option value="HINDU">Hindu</option>
                                <option value="BUDDHA">Buddha</option>
                                <option value="KONGHUCU">Konghucu</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status Kawin</label>
                            <select class="form-control form-select" name="status_kawin">
                                <option value="Belum Kawin">Belum Kawin</option>
                                <option value="Kawin">Kawin</option>
                                <option value="Cerai Hidup">Cerai Hidup</option>
                                <option value="Cerai Mati">Cerai Mati</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Pendidikan</label>
                            <select class="form-control form-select" name="pendidikan">
                                <option value="TIDAK/BELUM SEKOLAH">Tidak/Belum Sekolah</option>
                                <option value="SD/SEDERAJAT">SD / Sederajat</option>
                                <option value="SLTP/SEDERAJAT">SMP / Sederajat</option>
                                <option value="SLTA/SEDERAJAT">SMA / Sederajat</option>
                                <option value="DIPLOMA I/II">Diploma I/II</option>
                                <option value="AKADEMI/DIPLOMA III/S.MUDA">Diploma III</option>
                                <option value="DIPLOMA IV/STRATA I">Strata I (S1)</option>
                                <option value="STRATA II">Strata II (S2)</option>
                                <option value="STRATA III">Strata III (S3)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Pekerjaan</label>
                            <input type="text" class="form-control text-uppercase" name="pekerjaan" value="BELUM/TIDAK BEKERJA" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nama Ayah</label>
                            <input type="text" class="form-control text-uppercase" name="nama_ayah" value="-">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nama Ibu</label>
                            <input type="text" class="form-control text-uppercase" name="nama_ibu" value="-">
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-4">
                    <div class="form-section-title text-success"><i class="fas fa-home me-2"></i>Domisili Tujuan (Di Desa Ini)</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Dusun <span class="text-danger">*</span></label>
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
                                <select class="form-select form-control border-success" name="dusun_select" id="dusun_select">
                                    <?php foreach ($daftar_dusun as $dsn): ?>
                                        <option value="<?php echo $dsn; ?>"><?php echo $dsn; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="dusun_input_container" style="display: none;">
                                <input type="text" class="form-control text-uppercase border-success" name="dusun_custom" id="dusun_custom" placeholder="Ketik nama dusun...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RT/RW <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-success" name="rt_rw" value="001/002" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Alamat Rumah Lengkap <span class="text-danger">*</span></label>
                            <textarea class="form-control border-success text-uppercase" name="alamat" rows="2" placeholder="Nama jalan, gang, nomer rumah..." required></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalTambah()">Batal</button>
                <button type="submit" name="submit_tambah" class="btn btn-primary px-5 rounded-pill shadow-sm"><i class="fas fa-save me-2"></i>Simpan Ke Master Penduduk</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editDatangModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" action="datang.php" class="modal-content" style="display:flex; flex-direction:column; height:100%;">
            <div class="modal-header bg-gradient-warning text-white">
                <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-edit me-2"></i>Edit Data Pendatang</h5>
                <button type="button" class="btn-close text-dark" onclick="closeModalEdit()"></button>
            </div>
            
            <div class="modal-body bg-light p-4">
                <input type="hidden" name="id_datang" id="edit_id">
                
                <div class="alert alert-warning shadow-sm mb-4 text-dark">
                    <i class="fas fa-info-circle me-2"></i> <strong>Perhatian:</strong> Perubahan nama dan alamat tujuan di sini akan otomatis mengubah data tersebut di tabel <b>Master Penduduk</b>.
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <div class="form-section-title text-warning"><i class="fas fa-user me-2"></i>Identitas Dasar</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">NIK</label>
                            <input type="text" class="form-control bg-light" name="nik" id="edit_nik" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control text-uppercase" name="nama" id="edit_nama" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jenis Kelamin</label>
                            <select class="form-control form-select" name="jenis_kelamin" id="edit_jk">
                                <option value="LAKI-LAKI">Laki-Laki</option>
                                <option value="PEREMPUAN">Perempuan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <div class="form-section-title text-warning"><i class="fas fa-suitcase me-2"></i>Keterangan Asal & Kedatangan</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Datang</label>
                            <input type="date" class="form-control" name="tanggal_datang" id="edit_tgl" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Alasan Datang</label>
                            <select class="form-control form-select" name="alasan_datang" id="edit_alasan" required>
                                <option value="PEKERJAAN">Pekerjaan / Dinas</option>
                                <option value="PENDIDIKAN">Pendidikan</option>
                                <option value="PERUMAHAN">Beli / Pindah Rumah</option>
                                <option value="KELUARGA">Keluarga (Ikut Suami/Istri/Ortu)</option>
                                <option value="LAINNYA">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Alamat Asal Sebelumnya</label>
                            <textarea class="form-control text-uppercase" name="alamat_asal" id="edit_asal" rows="2" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-4">
                    <div class="form-section-title text-warning"><i class="fas fa-home me-2"></i>Domisili Tujuan (Di Desa Ini)</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Dusun Tujuan</label>
                            <input type="text" class="form-control text-uppercase" name="dusun" id="edit_dusun" list="list_dusun" required>
                            <datalist id="list_dusun">
                                <?php foreach ($daftar_dusun as $dsn): ?>
                                    <option value="<?php echo $dsn; ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RT/RW Tujuan</label>
                            <input type="text" class="form-control" name="rt_rw" id="edit_rtrw" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Alamat Tujuan Lengkap</label>
                            <textarea class="form-control text-uppercase" name="alamat" id="edit_alamat_tujuan" rows="2" required></textarea>
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

<div class="modal fade" id="confirmHapusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="datang.php" class="modal-content border-0" style="display:flex; flex-direction:column; height:100%;">
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle me-2"></i>Hapus Log Kedatangan</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalHapus()"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 70px; height: 70px;">
                    <i class="fas fa-trash-alt fa-2x"></i>
                </div>
                <h5 class="text-gray-800 font-weight-bold mb-2" id="namaHapusWarga">Nama</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus data riwayat kedatangan ini?<br><b>Catatan:</b> Ini hanya menghapus arsip. Data orang ini akan tetap ada di Master Penduduk.</p>
                <input type="hidden" name="id_hapus" id="inputIdHapus">
            </div>
            <div class="modal-footer bg-light d-flex justify-content-center">
                <button type="button" class="btn btn-light border px-4 rounded-pill me-2" onclick="closeModalHapus()">Batal</button>
                <button type="submit" name="delete_datang" class="btn btn-danger px-4 rounded-pill shadow-sm"><i class="fas fa-trash me-1"></i> Ya, Hapus Log</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ==================== INISIALISASI MODAL JAVASCRIPT MURNI ====================
let modalTambahDatang = null;
let modalEditDatang = null;
let modalViewDatang = null;
let modalHapusDatang = null;

document.addEventListener('DOMContentLoaded', function() {
    if(document.getElementById('tambahDatangModal')) modalTambahDatang = new bootstrap.Modal(document.getElementById('tambahDatangModal'));
    if(document.getElementById('editDatangModal')) modalEditDatang = new bootstrap.Modal(document.getElementById('editDatangModal'));
    if(document.getElementById('viewDatangModal')) modalViewDatang = new bootstrap.Modal(document.getElementById('viewDatangModal'));
    if(document.getElementById('confirmHapusModal')) modalHapusDatang = new bootstrap.Modal(document.getElementById('confirmHapusModal'));
});

// FUNGSI PEMBERSIH BACKDROP & TUTUP MODAL
function cleanupBackdrop() { 
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove()); 
    document.body.classList.remove('modal-open'); 
    document.body.style.overflow=''; 
    document.body.style.paddingRight=''; 
}

function openModalTambah() { if(modalTambahDatang) modalTambahDatang.show(); }
function closeModalTambah() { if(modalTambahDatang) modalTambahDatang.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalEdit() { if(modalEditDatang) modalEditDatang.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalView() { if(modalViewDatang) modalViewDatang.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalHapus() { if(modalHapusDatang) modalHapusDatang.hide(); setTimeout(cleanupBackdrop, 100); }
function closeAlert(el) { const alert = el.closest('.alert'); if (alert) alert.remove(); }

// ==================== TAMPIL DATA KE MODAL ====================
function formatDateId(dateString) {
    if(!dateString) return '-';
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

function viewData(data) {
    document.getElementById('view_nik').innerText = data.nik_datang;
    document.getElementById('view_nama').innerText = data.nama_datang;
    document.getElementById('view_jk').innerText = data.jenis_kelamin;
    document.getElementById('view_tgl_datang').innerText = formatDateId(data.tanggal_datang);
    document.getElementById('view_alamat_asal').innerText = data.alamat_asal;
    document.getElementById('view_alasan').innerText = data.alasan_datang || '-';
    
    // Data Alamat dari Join Master Penduduk
    let domisili_baru = (data.alamat_baru || '') + ' RT/RW ' + (data.rt_rw || '') + ' DSN ' + (data.dusun || '');
    document.getElementById('view_alamat_baru').innerText = domisili_baru;
    
    if(modalViewDatang) modalViewDatang.show();
}

function editData(data) {
    document.getElementById('edit_id').value = data.id_datang;
    document.getElementById('edit_nik').value = data.nik_datang;
    document.getElementById('edit_nama').value = data.nama_datang;
    document.getElementById('edit_jk').value = data.jenis_kelamin;
    document.getElementById('edit_tgl').value = data.tanggal_datang;
    document.getElementById('edit_alasan').value = data.alasan_datang;
    document.getElementById('edit_asal').value = data.alamat_asal;
    
    document.getElementById('edit_dusun').value = data.dusun || '';
    document.getElementById('edit_rtrw').value = data.rt_rw || '001/002';
    document.getElementById('edit_alamat_tujuan').value = data.alamat_baru || '';
    
    if(modalEditDatang) modalEditDatang.show();
}

function bukaModalHapus(id, nama) {
    document.getElementById('inputIdHapus').value = id;
    document.getElementById('namaHapusWarga').innerText = nama;
    if(modalHapusDatang) modalHapusDatang.show();
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

setTimeout(() => { document.querySelectorAll('.alert-dismissible').forEach(a => a.remove()); }, 5000);
</script>

<?php
$content = ob_get_clean();
include '../includes/base.php';
?>