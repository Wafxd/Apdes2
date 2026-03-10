<?php
session_start();

// Pastikan path untuk koneksi database sesuai (mundur 2 folder)
include "../../db/koneksi.php";

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../../login.php");
    exit();
}

$pageTitle = "Dashboard Informasi Publik & Layanan";
$id_user = $_SESSION['id_admin'];
$username = $_SESSION["nama_admin"];

// ==================== 1. QUERY KOTAK MASUK (PESAN) ====================
$query_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesan_kontak");
$total_pesan = $query_total ? mysqli_fetch_assoc($query_total)['total'] : 0;

$query_baru = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesan_kontak WHERE status_baca = 0");
$pesan_baru = $query_baru ? mysqli_fetch_assoc($query_baru)['total'] : 0;

$pesan_dibaca = $total_pesan - $pesan_baru;

// ==================== 2. QUERY PERMOHONAN SURAT ====================
$query_total_surat = mysqli_query($conn, "SELECT COUNT(*) as total FROM permohonan_surat");
$total_surat = $query_total_surat ? mysqli_fetch_assoc($query_total_surat)['total'] : 0;

// Di database kamu, statusnya menggunakan enum: 'Menunggu','Diproses','Selesai','Ditolak'
$query_surat_baru = mysqli_query($conn, "SELECT COUNT(*) as total FROM permohonan_surat WHERE status = 'Menunggu'");
$surat_baru = $query_surat_baru ? mysqli_fetch_assoc($query_surat_baru)['total'] : 0;


// ==================== 3. STATUS PROFIL KONTAK ====================
$query_kontak = mysqli_query($conn, "SELECT * FROM home_kontak LIMIT 1");
$kontak_tersedia = ($query_kontak && mysqli_num_rows($query_kontak) > 0) ? true : false;
$data_kontak = $kontak_tersedia ? mysqli_fetch_assoc($query_kontak) : null;


// ==================== 4. TREN PESAN & SURAT (CHART 6 BULAN) ====================
$bulan_labels = [];
$pesan_per_bulan = [];
$surat_per_bulan = [];

for($i = 5; $i >= 0; $i--) {
    $m = date('m', strtotime("-$i month"));
    $y = date('Y', strtotime("-$i month"));
    $bulan_labels[] = date('M Y', strtotime("-$i month"));
    
    // Hitung Pesan per Bulan (kolomnya 'tanggal' di tabel pesan_kontak)
    $q_tren_pesan = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesan_kontak WHERE MONTH(tanggal)='$m' AND YEAR(tanggal)='$y'");
    $pesan_per_bulan[] = $q_tren_pesan ? mysqli_fetch_assoc($q_tren_pesan)['total'] : 0;

    // Hitung Surat per Bulan (kolomnya 'tanggal_pengajuan' di tabel permohonan_surat)
    $q_tren_surat = mysqli_query($conn, "SELECT COUNT(*) as total FROM permohonan_surat WHERE MONTH(tanggal_pengajuan)='$m' AND YEAR(tanggal_pengajuan)='$y'");
    $surat_per_bulan[] = $q_tren_surat ? mysqli_fetch_assoc($q_tren_surat)['total'] : 0;
}

// ==================== 5. DATA TERBARU (LOG) ====================
$recent_messages = [];
$q_recent_pesan = mysqli_query($conn, "SELECT * FROM pesan_kontak ORDER BY tanggal DESC LIMIT 5");
if($q_recent_pesan) {
    while($r = mysqli_fetch_assoc($q_recent_pesan)) {
        $recent_messages[] = $r;
    }
}

$recent_surats = [];
$q_recent_surat = mysqli_query($conn, "SELECT * FROM permohonan_surat ORDER BY tanggal_pengajuan DESC LIMIT 5");
if($q_recent_surat) {
    while($r = mysqli_fetch_assoc($q_recent_surat)) {
        $recent_surats[] = $r;
    }
}

ob_start();
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>

<style>
    body { background-color: #f4f7fc; }
    
    /* Animasi Masuk Halus */
    .fade-in-up { animation: fadeInUp 0.6s cubic-bezier(0.165, 0.84, 0.44, 1) forwards; opacity: 0; transform: translateY(20px); }
    .delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; } .delay-3 { animation-delay: 0.3s; }
    @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

    /* Premium Stat Cards */
    .stat-premium { border-radius: 15px; border: none; padding: 20px 25px; color: white; position: relative; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: transform 0.3s; }
    .stat-premium:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
    
    .bg-grad-primary { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
    .bg-grad-warning { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); }
    .bg-grad-info { background: linear-gradient(135deg, #36b9cc 0%, #1a8a9e 100%); }
    .bg-grad-danger { background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%); }
    
    .stat-premium .bg-icon { position: absolute; right: -10px; bottom: -15px; font-size: 90px; opacity: 0.15; transform: rotate(-15deg); transition: 0.3s;}
    .stat-premium:hover .bg-icon { transform: rotate(0deg) scale(1.1); }
    .stat-premium h6 { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; opacity: 0.9;}
    .stat-premium .value { font-size: 2.2rem; font-weight: 900; line-height: 1; margin-bottom: 0;}

    /* Chart Cards */
    .chart-card { background: white; border: none; border-radius: 15px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.08); margin-bottom: 25px; overflow: hidden; display: flex; flex-direction: column; height: calc(100% - 25px);}
    .chart-header { background: #fdfdfe; border-bottom: 1px solid #eaecf4; padding: 1.25rem; font-weight: 800; color: #4e73df; font-size: 0.9rem; text-transform: uppercase; display: flex; justify-content: space-between; align-items: center;}
    .chart-body { padding: 1.5rem; width: 100%; display: block;}
    .chart-container { position: relative; height: 300px; width: 100%; }

    /* Nav Pills untuk Tab di Widget Log */
    .nav-pills-custom .nav-link { color: #858796; font-size: 0.85rem; font-weight: 700; border-radius: 50px; padding: 8px 15px; transition: 0.3s; margin-right: 5px; }
    .nav-pills-custom .nav-link.active { background-color: #4e73df; color: white; box-shadow: 0 4px 6px rgba(78, 115, 223, 0.2); }

    /* Recent Lists */
    .recent-msg-list { list-style: none; padding: 0; margin: 0; }
    .recent-msg-list li { padding: 15px; border-bottom: 1px dashed #eaecf4; transition: 0.2s; border-radius: 10px;}
    .recent-msg-list li:hover { background: #f8f9fc; transform: translateX(5px); }
    .recent-msg-list li:last-child { border-bottom: none; }
    .msg-sender { font-weight: 800; color: #3a3b45; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;}
    .msg-subject { font-weight: 600; color: #4e73df; font-size: 0.8rem; margin-top: 3px;}
    .msg-date { font-size: 0.75rem; color: #858796; margin-top: 3px;}
    
    .badge-status { font-size: 0.65rem; padding: 4px 8px; border-radius: 50px; font-weight: bold; letter-spacing: 0.5px;}
    .badge-unread { background: #ffebee; color: #e74a3b; border: 1px solid #e74a3b;}
    .badge-read { background: #e8f5e9; color: #1cc88a; border: 1px solid #1cc88a;}
    .badge-pending { background: #fff3cd; color: #f6c23e; border: 1px solid #f6c23e;}
    .badge-proses { background: #cce5ff; color: #4e73df; border: 1px solid #4e73df;}
    .badge-selesai { background: #d4edda; color: #1cc88a; border: 1px solid #1cc88a;}

    /* Info Widget */
    .info-widget { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.08); border-left: 5px solid #1cc88a; margin-bottom: 25px;}
    .info-widget-title { font-size: 0.8rem; text-transform: uppercase; font-weight: bold; color: #858796; margin-bottom: 10px;}
    .info-widget-item { display: flex; align-items: center; margin-bottom: 10px; font-size: 0.9rem;}
    .info-widget-item i { width: 25px; color: #1cc88a; font-size: 1.1rem;}
</style>

<div class="container-fluid px-0">

    <div class="d-sm-flex align-items-center justify-content-between mb-4 fade-in-up">
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Dashboard Pelayanan</h1>
            <p class="text-muted small mb-0">Pantau tren permohonan surat dan pesan warga secara real-time.</p>
        </div>
        <div>
            <a href="permohonan_surat.php" class="btn btn-info shadow-sm rounded-pill px-3 me-2 text-white">
                <i class="fas fa-file-alt fa-sm me-1"></i> Data Surat
            </a>
            <a href="../landing/kontak.php" class="btn btn-primary shadow-sm rounded-pill px-3">
                <i class="fas fa-cog fa-sm text-white-50 me-1"></i> Seting Web
            </a>
        </div>
    </div>

    <div class="row mb-2 fade-in-up delay-1">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-info h-100">
                <i class="fas fa-file-signature bg-icon"></i>
                <h6>Total Surat Diminta</h6>
                <div class="value"><?php echo number_format($total_surat); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-danger h-100">
                <i class="fas fa-clock bg-icon"></i>
                <h6>Surat Menunggu</h6>
                <div class="value"><?php echo number_format($surat_baru); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-primary h-100">
                <i class="fas fa-inbox bg-icon"></i>
                <h6>Total Pesan Masuk</h6>
                <div class="value"><?php echo number_format($total_pesan); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-premium bg-grad-warning h-100">
                <i class="fas fa-envelope-open-text bg-icon"></i>
                <h6>Pesan Belum Dibaca</h6>
                <div class="value"><?php echo number_format($pesan_baru); ?></div>
            </div>
        </div>
    </div>

    <div class="row fade-in-up delay-2">
        
        <div class="col-lg-7 col-xl-8">
            <div class="chart-card">
                <div class="chart-header">
                    <span><i class="fas fa-chart-line me-2"></i> Tren Interaksi Warga (6 Bulan Terakhir)</span>
                </div>
                <div class="chart-body">
                    <div class="chart-container"><canvas id="trendInteraksiChart"></canvas></div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="chart-card">
                        <div class="chart-header">
                            <span><i class="fas fa-chart-pie me-2"></i> Rasio Respons Pesan</span>
                        </div>
                        <div class="chart-body">
                            <div class="chart-container" style="height: 220px;"><canvas id="statusPesanChart"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-widget h-100">
                        <div class="info-widget-title">Konfigurasi Kontak Web Saat Ini</div>
                        <?php if($kontak_tersedia): ?>
                            <div class="info-widget-item"><i class="fab fa-whatsapp"></i> <strong>WA:</strong> &nbsp; <?php echo htmlspecialchars($data_kontak['nomor_whatsapp']); ?></div>
                            <div class="info-widget-item"><i class="fas fa-envelope"></i> <strong>Email:</strong> &nbsp; <?php echo htmlspecialchars($data_kontak['email']); ?></div>
                            <div class="info-widget-item"><i class="fas fa-clock"></i> <strong>Jam:</strong> &nbsp; <?php echo htmlspecialchars($data_kontak['jam_kerja']); ?></div>
                            <hr>
                            <a href="../landing/kontak.php" class="btn btn-outline-success btn-sm w-100 rounded-pill fw-bold">Update Info Kontak</a>
                        <?php else: ?>
                            <div class="alert alert-warning small">
                                Informasi kontak website belum diatur. Warga tidak tahu kemana harus menghubungi Anda.
                            </div>
                            <a href="../landing/kontak.php" class="btn btn-warning btn-sm w-100 rounded-pill fw-bold text-dark">Atur Sekarang</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-xl-4">
            <div class="chart-card h-100">
                <div class="chart-header d-flex flex-column align-items-start pb-0 border-0">
                    <span class="mb-3"><i class="fas fa-history me-2"></i> Log Aktivitas Terkini</span>
                    <ul class="nav nav-pills nav-pills-custom w-100 border-bottom pb-2" id="logTab" role="tablist">
                        <li class="nav-item flex-fill text-center" role="presentation">
                            <button class="nav-link active w-100" id="tab-surat-btn" data-bs-toggle="pill" data-bs-target="#tab-surat" type="button" role="tab">Permohonan Surat</button>
                        </li>
                        <li class="nav-item flex-fill text-center" role="presentation">
                            <button class="nav-link w-100" id="tab-pesan-btn" data-bs-toggle="pill" data-bs-target="#tab-pesan" type="button" role="tab">Pesan Kontak</button>
                        </li>
                    </ul>
                </div>
                
                <div class="chart-body pt-2" style="overflow-y: auto; flex-grow: 1;">
                    <div class="tab-content" id="logTabContent">
                        
                        <div class="tab-pane fade show active" id="tab-surat" role="tabpanel">
                            <?php if(empty($recent_surats)): ?>
                                <div class="text-center text-muted mt-5">
                                    <i class="fas fa-file-signature fa-3x mb-3 text-gray-300"></i><br>
                                    Belum ada permohonan surat masuk.
                                </div>
                            <?php else: ?>
                                <ul class="recent-msg-list">
                                    <?php foreach($recent_surats as $surat): 
                                        $nama = htmlspecialchars($surat['nama']);
                                        $jenis = htmlspecialchars($surat['jenis_surat']);
                                        $status = $surat['status'];
                                        $tgl = date('d M Y - H:i', strtotime($surat['tanggal_pengajuan']));
                                        
                                        $badge_class = 'badge-pending';
                                        if ($status == 'Selesai') $badge_class = 'badge-selesai';
                                        if ($status == 'Diproses') $badge_class = 'badge-proses';
                                    ?>
                                        <li>
                                            <div class="msg-sender">
                                                <span class="text-truncate" style="max-width: 60%;"><i class="fas fa-user-circle text-gray-400 me-1"></i> <?php echo $nama; ?></span>
                                                <span class="badge-status <?php echo $badge_class; ?>"><?php echo strtoupper($status); ?></span>
                                            </div>
                                            <div class="msg-subject mt-2">
                                                <i class="fas fa-file-alt text-info me-1"></i> <?php echo $jenis; ?>
                                            </div>
                                            <div class="msg-date">
                                                <i class="far fa-clock me-1"></i> Masuk: <?php echo $tgl; ?> WIB
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="mt-3 text-center">
                                    <a href="permohonan_surat.php" class="btn btn-sm btn-light text-primary fw-bold rounded-pill w-100 border">Lihat Semua Surat <i class="fas fa-arrow-right ms-1"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="tab-pesan" role="tabpanel">
                            <?php if(empty($recent_messages)): ?>
                                <div class="text-center text-muted mt-5">
                                    <i class="fas fa-envelope-open fa-3x mb-3 text-gray-300"></i><br>
                                    Belum ada pesan masuk.
                                </div>
                            <?php else: ?>
                                <ul class="recent-msg-list">
                                    <?php foreach($recent_messages as $msg): ?>
                                        <li>
                                            <div class="msg-sender">
                                                <span class="text-truncate" style="max-width: 60%;"><i class="fas fa-comment-dots text-gray-400 me-1"></i> <?php echo htmlspecialchars($msg['nama']); ?></span>
                                                <?php if($msg['status_baca'] == 0): ?>
                                                    <span class="badge-status badge-unread">BARU</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-read">DIBACA</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="msg-subject text-truncate" style="max-width: 95%;" title="<?php echo htmlspecialchars($msg['subjek']); ?>">
                                                Sbj: <?php echo htmlspecialchars($msg['subjek']); ?>
                                            </div>
                                            <div class="msg-date mt-1">
                                                <i class="far fa-clock me-1"></i> <?php echo date('d M Y - H:i', strtotime($msg['tanggal'])); ?> WIB
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="mt-3 text-center">
                                    <a href="kontak.php" class="btn btn-sm btn-light text-primary fw-bold rounded-pill w-100 border">Buka Kotak Masuk <i class="fas fa-arrow-right ms-1"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div> </div>
            </div>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    if(typeof Chart === 'undefined') {
        console.error("Chart.js gagal dimuat!");
        return; 
    }

    Chart.defaults.global.defaultFontFamily = "'Nunito', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'";
    Chart.defaults.global.defaultFontColor = '#858796';

    const bulanLabels = <?php echo json_encode($bulan_labels); ?>;
    const pesanBulan = <?php echo json_encode($pesan_per_bulan); ?>;
    const suratBulan = <?php echo json_encode($surat_per_bulan); ?>; // Data Surat Masuk
    const totalBaca = <?php echo $pesan_dibaca; ?>;
    const totalBaru = <?php echo $pesan_baru; ?>;

    function renderChartSafely(id, config) {
        const canvas = document.getElementById(id);
        if(!canvas) return;
        try { new Chart(canvas, config); } catch(e) { console.error("Chart Error di " + id, e); }
    }

    // 1. Chart Line: Tren Gabungan (Surat & Pesan)
    renderChartSafely('trendInteraksiChart', {
        type: 'line', 
        data: { 
            labels: bulanLabels, 
            datasets: [
                {
                    label: 'Pesan Kontak', 
                    data: pesanBulan, 
                    borderColor: '#4e73df', 
                    backgroundColor: 'rgba(78, 115, 223, 0.05)', 
                    fill: true, 
                    pointBackgroundColor: '#4e73df', 
                    pointBorderWidth: 2, 
                    pointRadius: 4, 
                    lineTension: 0.3
                },
                {
                    label: 'Permohonan Surat', 
                    data: suratBulan, 
                    borderColor: '#1cc88a', 
                    backgroundColor: 'rgba(28, 200, 138, 0.05)', 
                    fill: true, 
                    pointBackgroundColor: '#1cc88a', 
                    pointBorderWidth: 2, 
                    pointRadius: 4, 
                    lineTension: 0.3
                }
            ] 
        },
        options: { 
            maintainAspectRatio: false, 
            legend: { 
                display: true,
                position: 'bottom',
                labels: { boxWidth: 12, padding: 15 }
            }, 
            scales: { 
                xAxes: [{ gridLines: {display:false} }], 
                yAxes: [{ ticks: {beginAtZero: true, precision: 0} }] 
            } 
        }
    });

    // 2. Chart Pie: Rasio Respons Pesan
    renderChartSafely('statusPesanChart', {
        type: 'doughnut', 
        data: { 
            labels: ['Sudah Direspons/Dibaca', 'Belum Dibaca'], 
            datasets: [{ 
                data: [totalBaca, totalBaru], 
                backgroundColor: ['#1cc88a', '#e74a3b'], 
                borderWidth: 2, 
                hoverBorderColor: '#fff' 
            }] 
        },
        options: { 
            maintainAspectRatio: false, 
            cutoutPercentage: 65, 
            legend: { position: 'bottom' } 
        }
    });

});
</script>

<?php
$content = ob_get_clean();
// Memanggil template base yang sama
include '../../includes/base.php';
?>