<?php
// PERBAIKAN: Mundur 1 folder (../) untuk memanggil koneksi karena file ini ada di folder /landing
include "../db/koneksi.php";

// ==================== AMBIL DATA DARI DATABASE ====================
// Pengaturan Umum & Kontak (Untuk Navbar & Footer)
$pengaturan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_pengaturan LIMIT 1"));
$kontak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_kontak LIMIT 1"));

// Data Struktur & Keterangan SK
$keterangan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM struktur_keterangan LIMIT 1"));

// FORMAT DATA GARIS YANG AMAN
$raw_lines = $keterangan['grid_lines'] ?? '[]';
$dec_lines = json_decode($raw_lines, true);
if(!is_array($dec_lines)) { $dec_lines = []; }
$safe_lines_json = json_encode($dec_lines);

$query_perangkat = mysqli_query($conn, "SELECT * FROM struktur_pemerintahan ORDER BY urutan ASC, id ASC");

// Susun data ke dalam array berdasarkan posisi grid (1 - 70)
$grid_data = [];
$semua_perangkat = [];

while($row = mysqli_fetch_assoc($query_perangkat)) {
    $semua_perangkat[] = $row; // Simpan semua untuk bagian detail card di bawah
    
    $pos = (int)$row['urutan'];
    if($pos < 1 || $pos > 70) $pos = 1; 
    
    if(!isset($grid_data[$pos])) {
        $grid_data[$pos] = [];
    }
    $grid_data[$pos][] = $row;
}

$nama_desa = $pengaturan['nama_desa'] ?? 'Desa Sukolilo Timur';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struktur Pemerintahan - <?php echo htmlspecialchars($nama_desa); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #1e3c72;
            --accent: #2a5298;
            --line-color: #2c3e50; /* Warna garis konektor */
        }
        
        /* PERBAIKAN: Path Hero Background menggunakan ../ */
        .page-hero { position: relative; min-height: 40vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(30,60,114,0.9) 0%, rgba(42,82,152,0.9) 100%), url('../assets/images/<?php echo $pengaturan['hero_bg'] ?? 'hero-bg.jpg'; ?>') center/cover; color: white; padding-top: 80px; text-align: center; }
        .page-hero h1 { font-size: 3rem; font-weight: 700; margin-bottom: 15px; }

        /* ================= BAGAN ORGANISASI (VIEW ONLY) ================= */
        .org-chart-container {
            width: 100%;
            overflow-x: auto;
            padding: 40px 20px;
            margin-bottom: 30px;
            background: #f8f9fa; /* Background lembut agar bagan lebih menonjol */
            border-radius: 15px;
            box-shadow: inset 0 0 15px rgba(0,0,0,0.02);
        }

        .org-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 40px 20px; /* Harus sama persis dengan Admin agar presisi */
            min-width: 1200px;
            margin: 0 auto;
            position: relative; /* Penting untuk Kanvas SVG */
        }

        /* SVG Kanvas untuk Garis */
        #svgCanvas {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none; /* Tidak bisa diklik */
            z-index: 1;
        }
        
        .svg-connection {
            stroke: var(--line-color);
            stroke-width: 3px;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .org-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 140px;
            position: relative;
            z-index: 5;
        }

        /* ==================== KARTU PERSONEL RAPI ==================== */
        .org-box { 
            background: white; 
            border: 2px solid var(--primary); 
            border-radius: 8px; 
            padding: 15px 10px 10px 10px; 
            width: 100%; 
            max-width: 155px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            text-align: center;
            position: relative;
            z-index: 10; /* Menutupi garis bawahnya */
            margin: auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        /* Efek hover elegan untuk pengunjung */
        .org-box:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 15px 30px rgba(0,0,0,0.12); 
            border-color: var(--accent); 
        }
        
        .kategori-badge { 
            font-size: 0.6rem; background: var(--primary); color: white; 
            padding: 3px 6px; border-radius: 4px; display: block; 
            margin-bottom: 8px; font-weight: 700; text-transform: uppercase;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .org-box img { width: 65px; height: 65px; object-fit: cover; border-radius: 50%; margin-bottom: 10px; border: 2px solid #eaecf4; background: white; padding: 2px;}
        .org-box i.fa-user-tie { font-size: 55px; color: #d1d3e2; margin-bottom: 10px; }
        .org-box h5 { font-size: 0.8rem; font-weight: 800; color: var(--primary); margin-bottom: 3px; line-height: 1.2; text-transform: uppercase;}
        .org-box p { font-size: 0.65rem; color: #5a5c69; margin-bottom: 0; font-weight: 700; line-height: 1.2; text-transform: uppercase;}

        /* ================= DETAIL CARDS & SK BOX ================= */
        .detail-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: 100%; border: 1px solid rgba(0,0,0,0.05); transition: 0.3s;}
        .detail-card:hover { box-shadow: 0 10px 30px rgba(30,60,114,0.1); }
        .detail-header { display: flex; align-items: center; padding: 20px; background: rgba(30,60,114,0.03); border-bottom: 1px solid rgba(0,0,0,0.05); }
        .detail-photo { width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 3px solid white; margin-right: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);}
        .detail-photo-icon { width: 80px; height: 80px; border-radius: 50%; background: white; margin-right: 20px; display: flex; align-items: center; justify-content: center; font-size: 40px; color: var(--text-light); box-shadow: 0 3px 10px rgba(0,0,0,0.1);}
        .detail-title h4 { color: var(--primary); font-weight: 800; margin-bottom: 5px; font-size: 1.1rem; text-transform: uppercase;}
        .detail-body { padding: 20px; }
        .task-list { padding-left: 0; list-style: none; margin-bottom: 0; }
        .task-list li { position: relative; padding-left: 25px; margin-bottom: 10px; font-size: 0.9rem; color: #5a5c69; line-height: 1.5;}
        .task-list li::before { content: '\f058'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; left: 0; top: 2px; color: #1cc88a; }

        .sk-box { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; padding: 40px; border-radius: 20px; text-align: center; box-shadow: 0 15px 30px rgba(30,60,114,0.2); }
        
        .scroll-hint { display: none; text-align: center; color: #858796; font-size: 0.85rem; margin-bottom: 10px;}
        @media (max-width: 992px) { .scroll-hint { display: block; } }
    </style>
</head>
<body>

    <?php 
    $current_page = 'struktur';
    $is_solid_nav = true; 
    include 'navbar.php'; 
    ?>

    <div class="page-hero">
        <div class="container" data-aos="zoom-in">
            <h1>Struktur Pemerintahan</h1>
            <p class="lead text-white-50"><?php echo htmlspecialchars($nama_desa); ?></p>
        </div>
    </div>

    <section class="bg-white pb-5 pt-5">
        <div class="container">
            <div class="section-title text-center mb-4" data-aos="fade-up">
                <h2>Bagan Organisasi</h2>
                <p>Struktur hierarki Pemerintahan <?php echo htmlspecialchars($nama_desa); ?></p>
            </div>

            <div class="scroll-hint"><i class="fas fa-arrows-alt-h me-1"></i> Geser bagan ke kanan/kiri untuk melihat struktur penuh</div>

            <div class="org-chart-container" data-aos="fade-up" data-aos-delay="100">
                <div class="org-grid" id="gridContainer">
                    
                    <svg id="svgCanvas"></svg>
                    
                    <?php 
                    // Render 70 Kotak (Hanya Viewer)
                    for ($i = 1; $i <= 70; $i++): 
                    ?>
                        <div class="org-cell" data-cell-id="<?php echo $i; ?>">
                            <?php 
                            if(isset($grid_data[$i])): 
                                foreach($grid_data[$i] as $p):
                            ?>
                                <div class="org-box" data-node-id="<?php echo $p['id']; ?>">
                                    <span class="kategori-badge"><?php echo htmlspecialchars($p['kategori']); ?></span>
                                    <?php if(!empty($p['gambar'])): ?>
                                        <img src="../assets/images/<?php echo $p['gambar']; ?>" alt="Foto">
                                    <?php else: ?>
                                        <i class="fas fa-user-tie"></i>
                                    <?php endif; ?>
                                    <h5><?php echo htmlspecialchars($p['nama']); ?></h5>
                                    <p><?php echo htmlspecialchars($p['jabatan']); ?></p>
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
    </section>

    <section class="bg-light-custom pt-5 pb-5">
        <div class="container">
            <div class="section-title mb-5" data-aos="fade-up">
                <h2>Detail Tugas Perangkat</h2>
                <p>Tugas pokok dan fungsi masing-masing elemen pemerintahan desa</p>
            </div>
            
            <div class="row g-4">
                <?php 
                if(!empty($semua_perangkat)): 
                    $delay = 0;
                    foreach($semua_perangkat as $p): 
                ?>
                <div class="col-lg-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                    <div class="detail-card">
                        <div class="detail-header">
                            <?php if(!empty($p['gambar'])): ?>
                                <img src="../assets/images/<?php echo $p['gambar']; ?>" class="detail-photo" alt="Foto">
                            <?php else: ?>
                                <div class="detail-photo-icon"><i class="fas fa-user-tie"></i></div>
                            <?php endif; ?>
                            <div class="detail-title">
                                <h4><?php echo htmlspecialchars($p['nama']); ?></h4>
                                <span class="badge bg-primary text-white px-3 py-2 rounded-pill"><?php echo htmlspecialchars($p['jabatan']); ?></span>
                            </div>
                        </div>
                        <div class="detail-body">
                            <h6 style="color:var(--primary); font-weight:bold;"><i class="fas fa-tasks text-warning me-2"></i>Tugas Pokok & Fungsi:</h6>
                            <?php if(!empty($p['tugas_pokok'])): ?>
                                <ul class="task-list mt-3">
                                    <?php 
                                    $tugas_array = explode("\n", $p['tugas_pokok']);
                                    foreach($tugas_array as $tugas) {
                                        $tugas = trim(str_replace('-', '', $tugas));
                                        if(!empty($tugas)) echo "<li>" . htmlspecialchars($tugas) . "</li>";
                                    }
                                    ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted fst-italic ps-4 mt-3">Belum ada deskripsi tugas yang dicatat.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php 
                    $delay += 100;
                    if($delay > 300) $delay = 0;
                    endforeach; 
                else: 
                ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa-sitemap fa-3x mb-3 text-gray-300"></i>
                    <p>Data perangkat desa belum tersedia.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if(!empty($keterangan['sk_nomor'])): ?>
    <section class="bg-white pt-5 pb-5">
        <div class="container" data-aos="zoom-in">
            <div class="sk-box">
                <i class="fas fa-file-signature"></i>
                <h4>Berdasarkan Keputusan Resmi</h4>
                <p class="fs-5 fw-medium mb-3 mt-4"><?php echo htmlspecialchars($keterangan['sk_tentang']); ?></p>
                <div class="d-inline-block bg-white text-dark px-4 py-2 rounded-pill fw-bold shadow-sm mb-4">
                    NOMOR SK : <?php echo htmlspecialchars($keterangan['sk_nomor']); ?>
                </div>
                <p class="mt-3 text-white-50 text-uppercase letter-spacing-1" style="font-size: 0.85rem;">Disahkan Oleh Kepala Desa:<br><strong class="text-white fs-5 mt-1 d-inline-block"><?php echo htmlspecialchars($keterangan['sk_ttd']); ?></strong></p>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <script>
        const rawDbData = <?php echo $safe_lines_json ?: '[]'; ?>;
        const connections = Array.isArray(rawDbData) ? rawDbData : [];
        
        function drawConnections() {
            const svgCanvas = document.getElementById('svgCanvas');
            const gridContainer = document.getElementById('gridContainer');
            
            if(!svgCanvas || !gridContainer) return;
            svgCanvas.innerHTML = ''; 

            connections.forEach((conn) => {
                const fromBox = document.querySelector(`.org-box[data-node-id="${conn.from}"]`);
                const toBox = document.querySelector(`.org-box[data-node-id="${conn.to}"]`);

                if (fromBox && toBox) {
                    const containerRect = gridContainer.getBoundingClientRect();
                    const fromRect = fromBox.getBoundingClientRect();
                    const toRect = toBox.getBoundingClientRect();

                    // Hitung koordinat (Titik Awal: Tengah Bawah)
                    const startX = (fromRect.left - containerRect.left) + (fromRect.width / 2);
                    const startY = (fromRect.bottom - containerRect.top);

                    // Hitung koordinat (Titik Akhir: Tengah Atas)
                    const endX = (toRect.left - containerRect.left) + (toRect.width / 2);
                    const endY = (toRect.top - containerRect.top);

                    // Ambil ratio siku yang sebelumnya digeser/disimpan oleh Admin
                    let elbowRatio = conn.elbowRatio !== undefined ? conn.elbowRatio : 0.5;
                    let midY = startY + ((endY - startY) * elbowRatio);

                    // Bentuk Path SVG Menyiku
                    const pathString = `M ${startX} ${startY} L ${startX} ${midY} L ${endX} ${midY} L ${endX} ${endY}`;

                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    path.setAttribute('d', pathString);
                    path.setAttribute('class', 'svg-connection');

                    svgCanvas.appendChild(path);
                }
            });
        }

        // Render Garis saat halaman selesai dimuat dan saat layar di-resize
        window.addEventListener('load', () => { setTimeout(drawConnections, 300); });
        window.addEventListener('resize', drawConnections);
    </script>
</body>
</html>