<?php
$pageTitle = "Dashboard Statistik Dusun";
$pageHeaderButton = '<a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
</a>';

ob_start();
?>
<style>
    .card-chart {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    .card-header {
        background: #4e73df;
        color: white;
        border-radius: 10px 10px 0 0 !important;
        padding: 1rem;
        font-weight: 600;
    }
    .chart-container {
        position: relative;
        height: 300px;
        padding: 15px;
    }
    .info-box {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        border-left: 4px solid #4e73df;
    }
    .info-box h5 {
        color: #5a5c69;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .info-box .value {
        font-size: 24px;
        font-weight: 700;
        color: #2c3e50;
    }
</style>

<div class="container-fluid">
    <!-- Info Boxes -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="info-box">
                <h5>Total Penduduk</h5>
                <div class="value" id="totalPenduduk">0</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="info-box">
                <h5>Total KK</h5>
                <div class="value" id="totalKKBox">0</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="info-box">
                <h5>Laki-laki</h5>
                <div class="value" id="totalLaki">0</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="info-box">
                <h5>Perempuan</h5>
                <div class="value" id="totalPerempuan">0</div>
            </div>
        </div>
    </div>

    <!-- Chart 1: Perbandingan Penduduk per Dusun -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card card-chart">
                <div class="card-header">
                    Perbandingan Jumlah Penduduk per Dusun (Laki-laki vs Perempuan)
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="pendudukChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Row 2 -->
    <div class="row">
        <!-- Rasio Gender -->
        <div class="col-lg-6">
            <div class="card card-chart">
                <div class="card-header">
                    Rasio Gender Seluruh Dusun
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="genderRatioChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- KK per Dusun -->
        <div class="col-lg-6">
            <div class="card card-chart">
                <div class="card-header">
                    Kartu Keluarga per Dusun
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="kkChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart Total Surat Keluar -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-chart">
                <div class="card-header">
                    Total Surat Keluar
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <h1 class="display-4 font-weight-bold text-primary" id="totalSurat">0</h1>
                        <p class="text-muted">Total surat keluar seluruh dusun</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data untuk semua dusun
    const dusun = ['Kejawan', 'Buddan', 'Paserean', 'Langgar', 'Morleke', 'Pregih', 'Karang pandan'];
    
    // Data penduduk
    const lakiLaki = [620, 480, 410, 570, 360, 440, 310];
    const perempuan = [580, 470, 390, 530, 340, 410, 290];
    const totalPenduduk = lakiLaki.map((val, idx) => val + perempuan[idx]);
    
    // Data KK dan Surat
    const jumlahKK = [320, 250, 210, 290, 180, 220, 160];
    const totalSuratKeluar = 245;
    
    // Hitung total
    const totalAllPenduduk = totalPenduduk.reduce((a, b) => a + b, 0);
    const totalAllKK = jumlahKK.reduce((a, b) => a + b, 0);
    const totalAllLaki = lakiLaki.reduce((a, b) => a + b, 0);
    const totalAllPerempuan = perempuan.reduce((a, b) => a + b, 0);
    
    // Update info boxes
    document.getElementById('totalPenduduk').textContent = totalAllPenduduk.toLocaleString();
    document.getElementById('totalKKBox').textContent = totalAllKK.toLocaleString();
    document.getElementById('totalLaki').textContent = totalAllLaki.toLocaleString();
    document.getElementById('totalPerempuan').textContent = totalAllPerempuan.toLocaleString();
    document.getElementById('totalSurat').textContent = totalSuratKeluar.toLocaleString();
    
    // 1. Chart Perbandingan Penduduk per Dusun
    const ctx1 = document.getElementById('pendudukChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: dusun,
                datasets: [
                    {
                        label: 'Laki-laki',
                        data: lakiLaki,
                        backgroundColor: '#36b9cc',
                        borderColor: '#2c9faf',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Perempuan',
                        data: perempuan,
                        backgroundColor: '#f6c23e',
                        borderColor: '#dda20a',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Penduduk'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Dusun'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const total = totalPenduduk[context.dataIndex];
                                return `Total: ${total.toLocaleString()}`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 2. Doughnut Chart - Rasio Gender
    const ctx2 = document.getElementById('genderRatioChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [totalAllLaki, totalAllPerempuan],
                    backgroundColor: ['#36b9cc', '#f6c23e'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const percentage = Math.round((value / totalAllPenduduk) * 100);
                                return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 3. Bar Chart - KK per Dusun
    const ctx3 = document.getElementById('kkChart');
    if (ctx3) {
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: dusun,
                datasets: [{
                    label: 'Jumlah KK',
                    data: jumlahKK,
                    backgroundColor: '#4e73df',
                    borderColor: '#3a56b0',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
</script>
<?php
$content = ob_get_clean();
include 'template1/base.php';
?>