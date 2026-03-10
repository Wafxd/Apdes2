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
    
    // Tarik data penduduk yang akan pindah
    $query = "SELECT * FROM penduduk WHERE nama_penduduk LIKE '%$keyword%' OR nik LIKE '%$keyword%' ORDER BY nama_penduduk ASC LIMIT 8";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Gabungkan alamat asal untuk arsip
            $row['alamat_lengkap'] = $row['alamat'] . ' RT/RW ' . $row['rt_rw'] . ' DSN. ' . $row['dusun'];
            $data[] = $row;
        }
    }
    echo json_encode($data);
    exit();
}

// ==================== PROSES TAMBAH & PINDAH DATA (CREATE & DELETE) ====================
if (isset($_POST["submit_tambah"])) {
    // Data Arsip Biodata
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama_pindah = mysqli_real_escape_string($conn, $_POST['nama_pindah']);
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $agama = mysqli_real_escape_string($conn, $_POST['agama']);
    $alamat_asal = mysqli_real_escape_string($conn, $_POST['alamat_asal']);
    
    // Data Kepindahan
    $tanggal_pindah = mysqli_real_escape_string($conn, $_POST['tanggal_pindah']);
    $alamat_tujuan = mysqli_real_escape_string($conn, $_POST['alamat_tujuan']);
    $alasan_pindah = mysqli_real_escape_string($conn, $_POST['alasan_pindah']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // CEK KEAMANAN: Apakah warga ini KEPALA KELUARGA?
    $cek_kk = mysqli_query($conn, "SELECT no_kk FROM kartu_keluarga WHERE nik_kepala = '$nik'");
    if (mysqli_num_rows($cek_kk) > 0) {
        $_SESSION['error_message'] = "Peringatan: Warga ini berstatus sebagai <b>Kepala Keluarga</b>! Silakan ganti struktur Kepala Keluarga di menu Data Keluarga terlebih dahulu sebelum memindahkan data.";
        header("Location: pindah.php");
        exit();
    }

    // Mulai Transaksi Database (Aman)
    mysqli_begin_transaction($conn);

    try {
        // 1. Simpan Arsip ke Tabel Pindah
        $query_insert = "INSERT INTO pindah 
            (nik_pindah, nama_pindah, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, alamat_asal, tanggal_pindah, alamat_tujuan, alasan_pindah, keterangan) 
            VALUES 
            ('$nik', '$nama_pindah', '$tempat_lahir', '$tanggal_lahir', '$jenis_kelamin', '$agama', '$alamat_asal', '$tanggal_pindah', '$alamat_tujuan', '$alasan_pindah', '$keterangan')";
        mysqli_query($conn, $query_insert);

        // 2. Hapus relasi di tabel Anggota Keluarga (jika dia berstatus anggota/istri/anak)
        mysqli_query($conn, "DELETE FROM anggota_keluarga WHERE nik = '$nik'");

        // 3. Hapus Warga dari Master Penduduk (Karena sudah pindah keluar)
        mysqli_query($conn, "DELETE FROM penduduk WHERE nik = '$nik'");

        mysqli_commit($conn);
        $_SESSION['success_message'] = "Mutasi berhasil! Data kepindahan telah diarsipkan dan warga dihapus dari Master Penduduk.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
    
    header("Location: pindah.php");
    exit();
}

// ==================== PROSES EDIT (UPDATE LOG) ====================
if (isset($_POST['submit_edit'])) {
    $id = (int)$_POST['id_pindah'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal_pindah']);
    $tujuan = mysqli_real_escape_string($conn, $_POST['alamat_tujuan']);
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan_pindah']);
    $ket = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // Hanya update log kejadian pindahnya
    $query = "UPDATE pindah SET 
              tanggal_pindah='$tanggal', alamat_tujuan='$tujuan', alasan_pindah='$alasan', keterangan='$ket' 
              WHERE id_pindah=$id";

    if(mysqli_query($conn, $query)){
        $_SESSION['success_message'] = "Arsip kepindahan berhasil diperbarui!";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui data.";
    }
    header("Location: pindah.php");
    exit();
}

// ==================== PROSES HAPUS LOG (DELETE) ====================
if (isset($_POST['delete_pindah'])) {
    $id = (int)$_POST['id_hapus'];
    if(mysqli_query($conn, "DELETE FROM pindah WHERE id_pindah = $id")){
        $_SESSION['success_message'] = "Log riwayat pindah berhasil dihapus dari arsip!";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus arsip pindah.";
    }
    header("Location: pindah.php");
    exit();
}

// ==================== QUERY TAMPIL DATA ====================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "WHERE 1=1";
if (!empty($search)) {
    $where .= " AND (nama_pindah LIKE '%$search%' OR nik_pindah LIKE '%$search%')";
}

$query = "SELECT * FROM pindah $where ORDER BY tanggal_pindah DESC";
$result = mysqli_query($conn, $query);

// Hitung Statistik
$stat_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pindah"))['total'];
$stat_lk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pindah WHERE jenis_kelamin = 'LAKI-LAKI'"))['total'];
$stat_pr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pindah WHERE jenis_kelamin = 'PEREMPUAN'"))['total'];
$stat_bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pindah WHERE MONTH(tanggal_pindah) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_pindah) = YEAR(CURRENT_DATE())"))['total'];

$pageTitle = "Data Penduduk Pindah Keluar";
ob_start();
?>

<style>
body { background-color: #f8f9fc; }
.statistik-card { transition: all 0.3s; border: none; border-radius: 1rem; box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.04); background: white;}
.statistik-card:hover { transform: translateY(-5px); box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.1); }
.border-left-info { border-left: 5px solid #36b9cc !important; }
.border-left-primary { border-left: 5px solid #4e73df !important; }
.border-left-danger { border-left: 5px solid #e74a3b !important; }
.border-left-warning { border-left: 5px solid #f6c23e !important; }

.main-card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); }
.main-card > .card-header { background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%); border-bottom: none; border-radius: 1rem 1rem 0 0; color: white;}

.table-container { background: white; border-radius: 0 0 1rem 1rem; padding: 0 10px 15px 10px; }
.table thead th { background-color: #f8f9fc; border-bottom: 2px solid #eaecf4; color: #36b9cc; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 15px; }
.table tbody tr { transition: background 0.2s; }
.table tbody tr:hover { background-color: #f1f3f9; }
.table td { vertical-align: middle; color: #5a5c69; border-bottom: 1px solid #eaecf4; padding: 12px 15px; }

.btn-group-action { display: flex; gap: 6px; justify-content: center; }
.action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; color: white; transition: all 0.2s; border: none; cursor: pointer; }
.action-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); filter: brightness(1.1); }
.icon-view { background: #36b9cc; color: #fff; }
.icon-print { background: #858796; color: #fff; }
.icon-edit { background: #f6c23e; color: #fff; }
.icon-delete { background: #e74a3b; color: #fff; }

/* MODAL FIX SCROLLING */
.modal-content { border: none; border-radius: 1rem; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow: hidden; display: flex; flex-direction: column; max-height: 100%;}
.modal-header { border-bottom: none; padding: 1.25rem 1.5rem; flex-shrink: 0;}
.modal-body { padding: 1.5rem; overflow-y: auto; flex-grow: 1; }
.modal-footer { border-top: 1px solid #eaecf4; background: #f8f9fc; padding: 1rem 1.5rem; flex-shrink: 0;}

.form-section-title { font-size: 0.85rem; font-weight: 700; color: #36b9cc; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #eaecf4; padding-bottom: 8px; margin-bottom: 15px; margin-top: 10px;}
.form-control, .form-select { border-radius: 8px; border: 1px solid #d1d3e2; padding: 0.6rem 1rem;}
.form-control:focus, .form-select:focus { border-color: #36b9cc; box-shadow: 0 0 0 0.2rem rgba(54, 185, 204, 0.25);}
.form-label { font-weight: 600; color: #5a5c69; font-size: 0.85rem; margin-bottom: 4px;}

/* AUTOCOMPLETE */
.autocomplete-dropdown { position: absolute; top: 100%; left: 0; z-index: 1000; display: none; width: 100%; max-height: 250px; overflow-y: auto; background-color: #fff; border: 1px solid #e3e6f0; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); margin-top: 5px;}
.autocomplete-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f8f9fc; transition: background-color 0.2s; text-align: left; width: 100%; background: none; border: none; display: block;}
.autocomplete-item:hover, .autocomplete-item.active { background-color: #f1f3f9; color: #36b9cc; }

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
            <div class="card statistik-card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Pindah Keluar</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_total); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-truck-moving fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
            <div class="card statistik-card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Laki-Laki</div>
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
            <div class="card statistik-card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Bulan Ini</div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stat_bulan_ini); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-calendar-minus fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card mb-5">
        <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-sign-out-alt me-2"></i>Arsip Data Pindah Keluar</h6>
            <div class="d-flex gap-2">
                <a href="export/pindah_excel.php" class="btn btn-warning font-weight-bold shadow-sm rounded-pill px-3 text-dark">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </a>
                <button type="button" class="btn btn-light text-info font-weight-bold shadow-sm rounded-pill px-3" onclick="openModalTambah()">
                    <i class="fas fa-plus-circle me-1"></i> Lapor Mutasi Pindah
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
                                <span class="input-group-text bg-white"><i class="fas fa-search text-info"></i></span>
                            </div>
                            <input type="text" class="form-control border-left-0" name="search" placeholder="Cari Nama Warga Pindah atau NIK..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-info text-white btn-sm w-100"><i class="fas fa-search"></i> Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="pindah.php" class="btn btn-secondary btn-sm" title="Reset"><i class="fas fa-redo"></i></a>
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
                            <th width="30%">Identitas Warga</th>
                            <th width="20%">Tanggal Pindah</th>
                            <th width="30%">Alamat Tujuan & Alasan</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="text-center text-muted"><?php echo $no++; ?></td>
                                <td>
                                    <span class="font-weight-bold text-gray-800 text-uppercase d-block"><?php echo htmlspecialchars($row['nama_pindah']); ?></span>
                                    <small class="text-muted font-monospace d-block">NIK: <?php echo htmlspecialchars($row['nik_pindah']); ?></small>
                                    <small class="badge badge-light border border-secondary mt-1"><?php echo $row['jenis_kelamin']; ?></small>
                                </td>
                                <td>
                                    <div class="small font-weight-bold text-info"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($row['tanggal_pindah'])); ?></div>
                                </td>
                                <td>
                                    <div class="small text-dark mb-1 text-uppercase text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($row['alamat_tujuan']); ?>">
                                        <i class="fas fa-map-marker-alt text-danger me-1"></i> <?php echo htmlspecialchars($row['alamat_tujuan']); ?>
                                    </div>
                                    <div class="small text-muted"><i class="fas fa-comment-dots text-secondary me-1"></i> <?php echo htmlspecialchars($row['alasan_pindah']); ?></div>
                                </td>
                                <td>
                                    <div class="btn-group-action">
                                        <button class="action-icon icon-view" onclick='viewData(<?php echo json_encode($row); ?>)' title="Lihat Detail"><i class="fas fa-eye"></i></button>
                                        <button class="action-icon icon-edit" onclick='editData(<?php echo json_encode($row); ?>)' title="Edit Log"><i class="fas fa-edit"></i></button>
                                        <button class="action-icon icon-delete" onclick="bukaModalHapus(<?php echo $row['id_pindah']; ?>, '<?php echo htmlspecialchars($row['nama_pindah']); ?>')" title="Hapus Arsip"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-truck-moving fa-3x text-gray-300 mb-3"></i>
                                    <h5 class="text-gray-500">Arsip Pindah Kosong</h5>
                                    <p class="text-muted">Belum ada data warga pindah yang dicatat.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewPindahModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-id-card me-2"></i>Arsip Mutasi Pindah Warga</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalView()"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-sm table-borderless biodata-table text-dark m-0">
                        <tbody>
                            <tr><td class="biodata-label">NIK Warga</td><td width="2%">:</td><td id="view_nik" class="font-monospace text-info font-weight-bold"></td></tr>
                            <tr><td class="biodata-label">Nama Lengkap</td><td>:</td><td id="view_nama" class="text-uppercase font-weight-bold"></td></tr>
                            <tr><td class="biodata-label">Tempat, Tgl Lahir</td><td>:</td><td id="view_ttl" class="text-uppercase"></td></tr>
                            <tr><td class="biodata-label">Jenis Kelamin</td><td>:</td><td id="view_jk"></td></tr>
                            <tr><td class="biodata-label">Agama</td><td>:</td><td id="view_agama" class="text-uppercase"></td></tr>
                            <tr><td class="biodata-label">Alamat Asal</td><td>:</td><td id="view_alamat_asal" class="text-uppercase"></td></tr>
                            <tr><td colspan="3"><hr class="my-2"></td></tr>
                            <tr><td class="biodata-label text-info">Tanggal Pindah</td><td>:</td><td id="view_tgl_pindah" class="font-weight-bold text-info"></td></tr>
                            <tr><td class="biodata-label text-danger">Alamat Tujuan</td><td>:</td><td id="view_alamat_tujuan" class="text-uppercase font-weight-bold"></td></tr>
                            <tr><td class="biodata-label text-info">Alasan Pindah</td><td>:</td><td id="view_alasan" class="text-uppercase"></td></tr>
                            <tr><td class="biodata-label text-info">Keterangan Lain</td><td>:</td><td id="view_keterangan" class="text-muted"></td></tr>
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

<div class="modal fade" id="tambahPindahModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" action="pindah.php" class="modal-content" style="display:flex; flex-direction:column; height:100%;">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-sign-out-alt me-2"></i>Laporan Mutasi Keluar Desa</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalTambah()"></button>
            </div>
            
            <div class="modal-body bg-light p-4">
                <div class="alert alert-warning shadow-sm mb-4 text-dark">
                    <i class="fas fa-exclamation-triangle me-2"></i> <strong>PENTING:</strong> Melaporkan pindah keluar akan <u>menghapus data warga ini</u> dari tabel Master Penduduk dan memindahkannya ke dalam tabel Arsip Pindah Keluar.
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4">
                    <div class="form-section-title"><i class="fas fa-search me-2"></i>Pencarian Warga Yang Pindah</div>
                    <div class="row g-3 position-relative">
                        <div class="col-md-12">
                            <label class="form-label">Cari Warga (Dari Master Penduduk) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-info bg-white" id="input_cari_warga" required autocomplete="off" placeholder="Ketik minimal 2 huruf nama atau NIK...">
                            <div id="drop_cari_warga" class="autocomplete-dropdown border-info"></div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-4 mb-4 bg-white">
                    <div class="form-section-title text-muted"><i class="fas fa-lock me-2"></i>Data Arsip (Terisi Otomatis)</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">NIK Pindah</label>
                            <input type="text" class="form-control bg-light font-weight-bold" name="nik" id="form_nik" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control bg-light font-weight-bold text-uppercase" name="nama_pindah" id="form_nama" readonly required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <input type="text" class="form-control bg-light" name="jenis_kelamin" id="form_jk" readonly required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Agama</label>
                            <input type="text" class="form-control bg-light" name="agama" id="form_agama" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alamat Asal (Desa Ini)</label>
                            <input type="text" class="form-control bg-light text-uppercase" name="alamat_asal" id="form_alamat" readonly>
                        </div>
                    </div>
                    <input type="hidden" name="tempat_lahir" id="form_tmpt_lahir">
                    <input type="hidden" name="tanggal_lahir" id="form_tgl_lahir">
                </div>

                <div class="card border-0 shadow-sm p-4">
                    <div class="form-section-title text-info"><i class="fas fa-truck-loading me-2"></i>Keterangan Kepindahan</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Pindah <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_pindah" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Alasan Kepindahan <span class="text-danger">*</span></label>
                            <select class="form-control form-select" name="alasan_pindah" required>
                                <option value="PEKERJAAN">Pekerjaan / Dinas</option>
                                <option value="PENDIDIKAN">Pendidikan</option>
                                <option value="KEAMANAN">Keamanan</option>
                                <option value="KESEHATAN">Kesehatan</option>
                                <option value="PERUMAHAN">Perumahan / Tempat Tinggal</option>
                                <option value="KELUARGA">Keluarga (Ikut Suami/Istri/Ortu)</option>
                                <option value="LAINNYA">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Alamat Tujuan Pindah (Lengkap) <span class="text-danger">*</span></label>
                            <textarea class="form-control text-uppercase" name="alamat_tujuan" rows="2" placeholder="Contoh: JL. MELATI NO. 45, RT/RW 01/02, KEL. SUKA, KEC. MAJU, KAB. BANDUNG, JAWA BARAT" required></textarea>
                            <small class="text-muted">Sertakan nama Desa, Kecamatan, Kabupaten, dan Provinsi.</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Catatan Tambahan / Keterangan</label>
                            <input type="text" class="form-control" name="keterangan" placeholder="Opsional...">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalTambah()">Batal</button>
                <button type="submit" name="submit_tambah" class="btn btn-info text-white px-5 rounded-pill shadow-sm"><i class="fas fa-save me-2"></i>Simpan Laporan & Hapus Dari Penduduk</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editPindahModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" action="pindah.php" class="modal-content" style="display:flex; flex-direction:column; height:100%;">
            <div class="modal-header bg-gradient-warning text-white">
                <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-edit me-2"></i>Edit Arsip Pindah Keluar</h5>
                <button type="button" class="btn-close text-dark" onclick="closeModalEdit()"></button>
            </div>
            
            <div class="modal-body bg-light p-4">
                <input type="hidden" name="id_pindah" id="edit_id">
                
                <div class="alert alert-warning shadow-sm mb-4 text-dark">
                    <i class="fas fa-info-circle me-2"></i> Anda hanya dapat mengubah <strong>Detail Kepindahan</strong>. Identitas warga tidak dapat diubah karena sudah diarsipkan.
                </div>

                <div class="card border-0 shadow-sm p-4">
                    <div class="form-section-title text-warning"><i class="fas fa-truck-loading me-2"></i>Keterangan Kepindahan</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Pindah <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_pindah" id="edit_tgl_pindah" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Alasan Kepindahan <span class="text-danger">*</span></label>
                            <select class="form-control form-select" name="alasan_pindah" id="edit_alasan_pindah" required>
                                <option value="PEKERJAAN">Pekerjaan / Dinas</option>
                                <option value="PENDIDIKAN">Pendidikan</option>
                                <option value="KEAMANAN">Keamanan</option>
                                <option value="KESEHATAN">Kesehatan</option>
                                <option value="PERUMAHAN">Perumahan / Tempat Tinggal</option>
                                <option value="KELUARGA">Keluarga (Ikut Suami/Istri/Ortu)</option>
                                <option value="LAINNYA">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Alamat Tujuan Pindah (Lengkap) <span class="text-danger">*</span></label>
                            <textarea class="form-control text-uppercase" name="alamat_tujuan" id="edit_alamat_tujuan" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Catatan Tambahan / Keterangan</label>
                            <input type="text" class="form-control" name="keterangan" id="edit_keterangan">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-light border px-4 rounded-pill" onclick="closeModalEdit()">Batal</button>
                <button type="submit" name="submit_edit" class="btn btn-warning px-5 rounded-pill shadow-sm text-dark font-weight-bold"><i class="fas fa-save me-2"></i>Update Arsip Pindah</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="confirmHapusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="pindah.php" class="modal-content border-0" style="display:flex; flex-direction:column; height:100%;">
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle me-2"></i>Hapus Arsip Pindah</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModalHapus()"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 70px; height: 70px;">
                    <i class="fas fa-trash-alt fa-2x"></i>
                </div>
                <h5 class="text-gray-800 font-weight-bold mb-2" id="namaHapusWarga">Nama</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus data riwayat pindah ini secara permanen?</p>
                <input type="hidden" name="id_hapus" id="inputIdHapus">
            </div>
            <div class="modal-footer bg-light d-flex justify-content-center">
                <button type="button" class="btn btn-light border px-4 rounded-pill me-2" onclick="closeModalHapus()">Batal</button>
                <button type="submit" name="delete_pindah" class="btn btn-danger px-4 rounded-pill shadow-sm"><i class="fas fa-trash me-1"></i> Ya, Hapus Arsip</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ==================== INISIALISASI MODAL JAVASCRIPT MURNI ====================
let modalTambahPindah = null;
let modalEditPindah = null;
let modalViewPindah = null;
let modalHapusPindah = null;

document.addEventListener('DOMContentLoaded', function() {
    if(document.getElementById('tambahPindahModal')) modalTambahPindah = new bootstrap.Modal(document.getElementById('tambahPindahModal'));
    if(document.getElementById('editPindahModal')) modalEditPindah = new bootstrap.Modal(document.getElementById('editPindahModal'));
    if(document.getElementById('viewPindahModal')) modalViewPindah = new bootstrap.Modal(document.getElementById('viewPindahModal'));
    if(document.getElementById('confirmHapusModal')) modalHapusPindah = new bootstrap.Modal(document.getElementById('confirmHapusModal'));

    setupAutocompletePindah();
});

// FUNGSI PEMBERSIH BACKDROP & TUTUP MODAL
function cleanupBackdrop() { 
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove()); 
    document.body.classList.remove('modal-open'); 
    document.body.style.overflow=''; 
    document.body.style.paddingRight=''; 
}

function openModalTambah() { if(modalTambahPindah) modalTambahPindah.show(); }
function closeModalTambah() { if(modalTambahPindah) modalTambahPindah.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalEdit() { if(modalEditPindah) modalEditPindah.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalView() { if(modalViewPindah) modalViewPindah.hide(); setTimeout(cleanupBackdrop, 100); }
function closeModalHapus() { if(modalHapusPindah) modalHapusPindah.hide(); setTimeout(cleanupBackdrop, 100); }
function closeAlert(el) { const alert = el.closest('.alert'); if (alert) alert.remove(); }

// ==================== TAMPIL DATA KE MODAL ====================
function formatDateId(dateString) {
    if(!dateString) return '-';
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

function viewData(data) {
    document.getElementById('view_nik').innerText = data.nik_pindah;
    document.getElementById('view_nama').innerText = data.nama_pindah;
    document.getElementById('view_ttl').innerText = (data.tempat_lahir || '-') + ', ' + formatDateId(data.tanggal_lahir);
    document.getElementById('view_jk').innerText = data.jenis_kelamin;
    document.getElementById('view_agama').innerText = data.agama || '-';
    document.getElementById('view_alamat_asal').innerText = data.alamat_asal || '-';
    
    document.getElementById('view_tgl_pindah').innerText = formatDateId(data.tanggal_pindah);
    document.getElementById('view_alamat_tujuan').innerText = data.alamat_tujuan;
    document.getElementById('view_alasan').innerText = data.alasan_pindah;
    document.getElementById('view_keterangan').innerText = data.keterangan || '-';
    
    if(modalViewPindah) modalViewPindah.show();
}

function editData(data) {
    document.getElementById('edit_id').value = data.id_pindah;
    document.getElementById('edit_tgl_pindah').value = data.tanggal_pindah;
    document.getElementById('edit_alamat_tujuan').value = data.alamat_tujuan;
    document.getElementById('edit_alasan_pindah').value = data.alasan_pindah;
    document.getElementById('edit_keterangan').value = data.keterangan;
    
    if(modalEditPindah) modalEditPindah.show();
}

function bukaModalHapus(id, nama) {
    document.getElementById('inputIdHapus').value = id;
    document.getElementById('namaHapusWarga').innerText = nama;
    if(modalHapusPindah) modalHapusPindah.show();
}

// ==================== AUTOCOMPLETE LOGIC PENCARIAN PENDUDUK ====================
function setupAutocompletePindah() {
    const input = document.getElementById('input_cari_warga');
    const dropdown = document.getElementById('drop_cari_warga');
    let timeoutId;

    if(!input || !dropdown) return;

    input.addEventListener('input', function() {
        clearTimeout(timeoutId);
        const keyword = this.value.trim();
        
        if (keyword.length >= 2) {
            dropdown.innerHTML = '<div class="p-2 text-center text-info small"><i class="fas fa-spinner fa-spin"></i> Mencari Data...</div>';
            dropdown.style.display = 'block';
            
            timeoutId = setTimeout(() => {
                fetch(`pindah.php?ajax_cari_penduduk=${encodeURIComponent(keyword)}`)
                .then(res => res.json())
                .then(data => {
                    dropdown.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const btn = document.createElement('button');
                            btn.className = 'autocomplete-item';
                            btn.innerHTML = `
                                <span class="fw-bold d-block text-uppercase text-dark">${item.nama_penduduk}</span>
                                <small class="text-muted"><i class="fas fa-id-card"></i> ${item.nik} | <i class="fas fa-home"></i> DSN. ${item.dusun || '-'}</small>
                            `;
                            
                            btn.onclick = function(e) {
                                e.preventDefault();
                                // Mengisi form dengan arsip data warga
                                document.getElementById('form_nik').value = item.nik;
                                document.getElementById('form_nama').value = item.nama_penduduk;
                                document.getElementById('form_jk').value = item.jenis_kelamin;
                                document.getElementById('form_agama').value = item.agama;
                                document.getElementById('form_alamat').value = item.alamat_lengkap;
                                
                                document.getElementById('form_tmpt_lahir').value = item.tempat_lahir;
                                document.getElementById('form_tgl_lahir').value = item.tanggal_lahir;
                                
                                input.value = item.nama_penduduk + " (" + item.nik + ")"; 
                                dropdown.style.display = 'none';
                            };
                            dropdown.appendChild(btn);
                        });
                    } else {
                        dropdown.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fas fa-info-circle mb-1 text-danger"></i><br>Warga tidak ditemukan di database Master Penduduk!</div>';
                    }
                }).catch(() => {
                    dropdown.innerHTML = '<div class="p-2 text-danger small">Gagal memuat data</div>';
                });
            }, 300);
        } else {
            dropdown.style.display = 'none';
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target !== input && e.target !== dropdown) dropdown.style.display = 'none';
    });
}

setTimeout(() => { document.querySelectorAll('.alert-dismissible').forEach(a => a.remove()); }, 5000);
</script>

<?php
$content = ob_get_clean();
include '../includes/base.php';
?>