<?php
// Ambil data kontak jika belum ada
if (!isset($kontak)) { $kontak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM home_kontak LIMIT 1")); }
$alamat_lengkap = ($pengaturan['nama_desa'] ?? '') . ', Kec. ' . ($pengaturan['kecamatan'] ?? '') . ', Kab. ' . ($pengaturan['kabupaten'] ?? '') . ', ' . ($pengaturan['provinsi'] ?? '');
?>

<footer class="footer">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <div class="d-flex align-items-center mb-4">
                    <img src="../assets/images/<?php echo $logo; ?>" alt="Logo" style="height: 50px; margin-right: 15px; border-radius:8px; background:white; padding:2px;">
                    <h4 class="mb-0 border-0 p-0"><?php echo $nama_desa; ?></h4>
                </div>
                <p class="mb-4" style="line-height: 1.8;">
                    Website resmi pelayanan dan informasi desa. Dikelola oleh Pemerintah <?php echo $nama_desa; ?>.
                </p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div class="col-lg-4">
                <h4>Hubungi Kami</h4>
                <ul class="list-unstyled mt-4" style="line-height: 2;">
                    <li class="mb-3 d-flex">
                        <i class="fas fa-map-marker-alt mt-1 me-3 text-warning"></i> 
                        <span><?php echo !empty($kontak['alamat']) ? $kontak['alamat'] : $alamat_lengkap; ?></span>
                    </li>
                    <li class="mb-3 d-flex">
                        <i class="fab fa-whatsapp mt-1 me-3 text-warning"></i> 
                        <a href="<?php echo $kontak['link_whatsapp'] ?? '#'; ?>" target="_blank">
                            <?php echo $kontak['nomor_whatsapp'] ?? '-'; ?>
                        </a>
                    </li>
                    <li class="mb-3 d-flex">
                        <i class="fas fa-envelope mt-1 me-3 text-warning"></i> 
                        <a href="mailto:<?php echo $kontak['email'] ?? ''; ?>">
                            <?php echo $kontak['email'] ?? '-'; ?>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-4">
                <h4>Pintasan</h4>
                <ul class="list-unstyled mt-4" style="line-height: 2;">
                    <li><a href="dashboard.php"><i class="fas fa-chevron-right me-2 fs-6"></i>Beranda</a></li>
                    <li><a href="profil.php"><i class="fas fa-chevron-right me-2 fs-6"></i>Profil Desa</a></li>
                    <li><a href="layanan.php"><i class="fas fa-chevron-right me-2 fs-6"></i>Layanan Publik</a></li>
                    <li><a href="kontak.php"><i class="fas fa-chevron-right me-2 fs-6"></i>Kontak Kami</a></li>
                </ul>
            </div>
        </div>

        <hr class="mt-5 mb-4" style="border-color: rgba(255,255,255,0.1);">
        <div class="text-center text-white-50">
            <p class="mb-0">© <?php echo date('Y'); ?> <?php echo $nama_desa; ?>. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

<script>
    // Inisialisasi AOS (Animasi on Scroll)
    AOS.init({ duration: 1000, once: true, offset: 100 });

    // Efek Navbar transparan ke solid saat di-scroll
    window.addEventListener('scroll', function() {
        const nav = document.querySelector('.navbar');
        if (nav && !nav.classList.contains('solid-nav')) {
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        }
    });

    // Inisialisasi Swiper (Berjalan jika elemen ada di halaman)
    if(document.querySelector('.main-swiper')) {
        new Swiper('.main-swiper', {
            loop: true, autoplay: { delay: 5000, disableOnInteraction: false },
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
            effect: 'fade', fadeEffect: { crossFade: true }
        });
    }

    if(document.querySelector('.kegiatan-swiper')) {
        new Swiper('.kegiatan-swiper', {
            slidesPerView: 1, spaceBetween: 30, loop: true,
            autoplay: { delay: 4000, disableOnInteraction: false },
            pagination: { el: '.kegiatan-swiper .swiper-pagination', clickable: true },
            breakpoints: { 768: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }
        });
    }
</script>