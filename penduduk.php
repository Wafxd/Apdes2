<?php

include "db/funct.php";

if (isset($_POST["submit_penduduk"])) {

    if (!empty($_POST["nik"]) && !empty($_POST["nama_penduduk"])) {

        if (add_penduduk($_POST) > 0) {
            echo "<script>
                    alert('Data penduduk baru berhasil ditambahkan');
                    window.location.href = 'penduduk.php'; // Ganti ini ke halaman data penduduk Anda
                  </script>";
        } else {
            echo "<script>
                    alert('Gagal menambahkan data penduduk');
                    history.back(); // Kembali ke halaman form
                  </script>";
        }
    } else {
        echo "<script>
                alert('NIK dan Nama wajib diisi. Silakan periksa kembali.');
                history.back(); // Kembali ke halaman form
              </script>";
    }
}

// --- LOGIKA UNTUK EDIT DATA (BARU) ---
if (isset($_POST["submit_edit_penduduk"])) {
    // Validasi sederhana (NIK tidak boleh kosong)
    if (!empty($_POST["nik"])) {
        
        // Panggil fungsi edit_penduduk()
        if (edit_penduduk($_POST) > 0) {
            echo "<script>
                    alert('Data penduduk berhasil diubah');
                    window.location.href = 'penduduk.php'; 
                  </script>";
        } else {
            // Jika tidak ada baris yang terpengaruh (mungkin data tidak berubah)
            echo "<script>
                    alert('Data berhasil diproses (tidak ada perubahan data terdeteksi)');
                    window.location.href = 'penduduk.php';
                  </script>";
        }
    } else {
        // Jika NIK kosong (seharusnya tidak mungkin karena readonly)
        echo "<script>
                alert('Terjadi kesalahan: NIK tidak ditemukan.');
                history.back();
              </script>";
    }
}


$pageTitle = "Surat Menyurat";
$pageHeaderButton = '<a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
</a>';

ob_start();
?>
<!-- Content Row -->
<div class="container-fluid">
    <!-- Page Heading -->
    <a href="#" class="btn btn-primary btn-icon-split" data-bs-toggle="modal" data-bs-target="#tambahPendudukModal">
        <span class="icon text-white-50">
            <i class="fas fa-flag"></i>
        </span>
        <span class="text">+ Tambah Penduduk</span>
    </a>
    <!-- Modal Tambah Penduduk -->
    <div class="modal fade" id="tambahPendudukModal" tabindex="-1" aria-labelledby="tambahPendudukModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahPendudukModalLabel">Tambah Data Penduduk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="row g-2 mb-2">
                            <div class="col-md-2 fw-bold align-self-center">NIK</div>
                            <div class="col-md-4">
                                <input type="text" class="form-control font-monospace" id="inputNIK" name="nik" required>
                            </div>
                            <!-- <div class="col-md-2 fw-bold align-self-center">No. KK Sementara</div>
                            <div class="col-md-4">
                                <input type="text" class="form-control font-monospace" id="inputNoKK">
                            </div> -->
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-2 fw-bold align-self-center">Nama</div>
                            <div class="col-md-10">
                                <input type="text" class="form-control text-uppercase" id="inputNama" name="nama_penduduk" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-2 fw-bold align-self-center">Tempat/Tgl Lahir</div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="inputTempatLahir" name="tempat_lahir" required>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" id="inputTanggalLahir" name="tanggal_lahir" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-2 fw-bold align-self-center">Jenis Kelamin</div>
                            <div class="col-md-4">
                                <select class="form-select" id="inputJenisKelamin" name="jenis_kelamin" required>
                                    <option value="LAKI-LAKI" selected>Laki-laki</option>
                                    <option value="PEREMPUAN">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-2 fw-bold align-self-center">Agama</div>
                            <div class="col-md-4">
                                <select class="form-select" id="inputAgama" name="agama" required>
                                    <option value="ISLAM" selected>Islam</option>
                                    <option value="KRISTEN_PROTESTAN">Kristen Protestan</option>
                                    <option value="KRISTEN_KATOLIK">Kristen Katolik</option>
                                    <option value="HINDU">Hindu</option>
                                    <option value="BUDDHA">Buddha</option>
                                    <option value="KONGHUCU">Konghucu</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-2 fw-bold align-self-center">Pendidikan</div>
                            <div class="col-md-4">
                                <select class="form-select" id="inputPendidikan" name="pendidikan" required>
                                    <option value="TIDAK_SEKOLAH" selected>Tidak/Belum Sekolah</option>
                                    <option value="SD">SD</option>
                                    <option value="SMP">SMP</option>
                                    <option value="SMA">SMA</option>
                                    <option value="SMK">SMK</option>
                                    <option value="D1_D2_D3">D1/D2/D3</option>
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                    <option value="S3">S3</option>
                                </select>
                            </div>
                            <div class="col-md-2 fw-bold align-self-center">Pekerjaan</div>
                            <div class="col-md-4">
                                <select class="form-select" id="inputPekerjaan" name="pekerjaan" required>
                                    <option value="PNS" selected>PNS</option>
                                    <option value="TNI">TNI</option>
                                    <option value="POLRI">POLRI</option>
                                    <option value="PEGAWAI SWASTA">Pegawai Swasta</option>
                                    <option value="WIRASWASTA">Wiraswasta</option>
                                    <option value="PETANI">Petani</option>
                                    <option value="BURUH">Buruh</option>
                                    <option value="PELAJAR/MAHASISWA">Pelajar/Mahasiswa</option>
                                    <option value="IRT">Ibu Rumah Tangga</option>
                                    <option value="PENSIUNAN">Pensiunan</option>
                                    <option value="LAINNYA">Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-2 fw-bold align-self-center">Status Kawin</div>
                            <div class="col-md-4">
                                <select class="form-select" id="statusKawin" name="status_kawin" required>
                                    <option value="">Pilih Status Kawin</option>
                                    <option value="Belum Kawin">Belum Kawin</option>
                                    <option value="Kawin">Kawin</option>
                                    <option value="Cerai">Cerai</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-2 fw-bold align-self-center">Alamat</div>
                            <div class="col-md-10">
                                <textarea class="form-control" id="inputAlamat" name="alamat" rows="2" required></textarea>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-2 fw-bold align-self-center">RT/RW</div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="inputRtRw" name="rt/rw" value="001/002" required>
                            </div>
                            <div class="col-md-2 fw-bold align-self-center">Desa/Kel</div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="inputDesa" name="kel/des" value="Sukolilo Timur" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-2 fw-bold align-self-center">Kecamatan</div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="inputKecamatan" name="kecamatan" value="Sukolilo" required>
                            </div>
                            <div class="col-md-2 fw-bold align-self-center">Kabupaten/Kota</div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="inputKab" name="kabupaten/kota" value="Bangkalan" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-2 fw-bold align-self-center">Kode Pos</div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="inputPos" name="kodepos" value="69162" required>
                            </div>
                            <div class="col-md-2 fw-bold align-self-center">Provinsi</div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="inputProv" name="provinsi" value="Jawa Timur" required>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" name="submit_penduduk" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <p></p>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">DataTables Penduduk</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Aksi</th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>No. KK</th>
                            <th>Alamat</th>
                            <th>Pendidikan</th>
                            <th>Umur</th>
                            <th>Pekerjaan</th>
                            <th>Status Kawin</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Aksi</th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>No. KK</th>
                            <th>Alamat</th>
                            <th>Pendidikan</th>
                            <th>Umur</th>
                            <th>Pekerjaan</th>
                            <th>Status Kawin</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        // 1. Ambil semua data penduduk dari database
                        // (Asumsi fungsi query() sudah tersedia dari funct.php)
                        $data_penduduk = query("SELECT * FROM penduduk");

                        // 2. Ambil tahun sekarang (cukup sekali di luar loop untuk efisiensi)
                        $tahun_sekarang = date('Y');

                        // 3. Mulai loop foreach untuk setiap baris data
                        foreach ($data_penduduk as $penduduk) :

                            // 4. Proses perhitungan umur
                            $umur = "N/A"; // Nilai default jika tanggal lahir kosong atau tidak valid

                            // Pastikan kolom 'tanggal lahir' tidak kosong
                            if (!empty($penduduk['tanggal_lahir'])) {
                                // Ubah string tanggal lahir menjadi timestamp
                                $timestamp_lahir = strtotime($penduduk['tanggal_lahir']);

                                // Jika timestamp valid
                                if ($timestamp_lahir) {
                                    // Ambil tahun kelahiran dari timestamp
                                    $tahun_lahir = date('Y', $timestamp_lahir);
                                    // Hitung umurnya
                                    $umur = $tahun_sekarang - $tahun_lahir;
                                }
                            }

                        ?>
                            <tr>
                                <td>
                                    <!-- Dropdown Button -->
                                    <div class="dropdown">
                                        <a href="#" class="btn btn-info dropdown-toggle" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="icon text-white-50">
                                                <i class="fas fa-info-circle fa-sm"></i>
                                            </span>
                                            <span>Pilih Aksi</span>
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#detailModal-<?php echo $penduduk['nik']; ?>">
                                                    <i class="fas fa-info-circle fa-sm me-2 text-primary"></i>
                                                    <span>| Detail</span>
                                                </a></li>
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editModal-<?php echo $penduduk['nik']; ?>">
                                                    <i class="fas fa-edit fa-sm me-2 text-success"></i>
                                                    <span>| Edit</span>
                                                </a></li>
                                            <li><a class="dropdown-item" href="hapus_penduduk.php?nik=<?php echo $penduduk['nik']; ?>" onclick="return confirm('Yakin ingin menghapus data ini?');">
                                                    <i class="fas fa-trash fa-sm me-2 text-danger"></i>
                                                    <span>| Hapus</span>
                                                </a></li>
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#lihatKKModal">
                                                    <i class="fas fa-id-card fa-sm me-2 text-info"></i>
                                                    <span>| Lihat KK</span>
                                                </a></li>
                                        </ul>
                                    </div>

                                    <!-- Modal Detail -->
                                    <div class="modal fade" id="detailModal-<?php echo $penduduk['nik']; ?>" tabindex="-1" aria-labelledby="detailModalLabel-<?php echo $penduduk['nik']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content border border-3 border-primary">
                                                <!-- Header dengan desain mirip KK -->
                                                <div class="modal-header bg-primary text-white py-3">
                                                    <div class="w-100 text-center">
                                                        <h5 class="modal-title" id="detailModalLabel-<?php echo $penduduk['nik']; ?>">Detail Penduduk</h5>
                                                        <p class="mb-0 small">Republik Indonesia</p>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">

                                                    <div class="row mb-4">
                                                        <div class="col-md-12">
                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">NIK</div>
                                                                <div class="col-md-8 font-monospace">
                                                                    <?php echo $penduduk['nik']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Nama</div>
                                                                <div class="col-md-8 text-uppercase fw-bold">
                                                                    <?php echo $penduduk['nama_penduduk']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Tempat/Tgl Lahir</div>
                                                                <div class="col-md-8">
                                                                    <?php
                                                                    // Format tanggal agar lebih rapi (cth: 01-01-1923)
                                                                    $tanggal_formatted = date('d-m-Y', strtotime($penduduk['tanggal_lahir']));
                                                                    echo $penduduk['tempat_lahir'] . ', ' . $tanggal_formatted;
                                                                    ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Jenis Kelamin</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['jenis_kelamin']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Agama</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['agama']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Pendidikan</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['pendidikan']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Pekerjaan</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['pekerjaan']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Status Kawin</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['status_kawin']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Alamat</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['alamat']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">RT/RW</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['rt/rw']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Kel/Desa</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['kel/des']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Kecamatan</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['kecamatan']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Kabupaten/Kota</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['kabupaten/kota']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Provinsi</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['provinsi']; ?>
                                                                </div>
                                                            </div>

                                                            <div class="row g-2 mb-2">
                                                                <div class="col-md-4 fw-bold">Kode Pos</div>
                                                                <div class="col-md-8">
                                                                    <?php echo $penduduk['kodepos']; ?>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                </div>

                                                <!-- Footer dengan desain KK -->
                                                <div class="modal-footer bg-light py-2">
                                                    <div class="small text-muted">Dokumen ini sah dan diterbitkan secara elektronik</div>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i> Tutup
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-print me-1"></i> Cetak Kartu
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Edit -->
                                    <div class="modal fade" id="editModal-<?php echo $penduduk['nik']; ?>" tabindex="-1" aria-labelledby="editModalLabel-<?php echo $penduduk['nik']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content border border-3 border-primary">
                                                <form method="post">
                                                    <div class="modal-header bg-primary text-white py-3">
                                                        <div class="w-100 text-center">
                                                            <h5 class="modal-title mb-0 fw-bold" id="editModalLabel-<?php echo $penduduk['nik']; ?>">Edit Data Penduduk</h5>
                                                            <p class="mb-0 small">Republik Indonesia</p>
                                                        </div>
                                                        <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="nik-<?php echo $penduduk['nik']; ?>" class="form-label">NIK</label>
                                                            <input type="text" class="form-control" id="nik-<?php echo $penduduk['nik']; ?>" name="nik" value="<?php echo $penduduk['nik']; ?>" readonly>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="nama-<?php echo $penduduk['nik']; ?>" class="form-label">Nama</label>
                                                            <input type="text" class="form-control" id="nama-<?php echo $penduduk['nik']; ?>" name="nama_penduduk" value="<?php echo $penduduk['nama_penduduk']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="tempat-<?php echo $penduduk['nik']; ?>" class="form-label">Tempat Lahir</label>
                                                            <input type="text" class="form-control" id="tempat-<?php echo $penduduk['nik']; ?>" name="tempat_lahir" value="<?php echo $penduduk['tempat_lahir']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="tanggal-<?php echo $penduduk['nik']; ?>" class="form-label">Tanggal Lahir</label>
                                                            <input type="date" class="form-control" id="tanggal-<?php echo $penduduk['nik']; ?>" name="tanggal_lahir" value="<?php echo $penduduk['tanggal_lahir']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="jk-<?php echo $penduduk['nik']; ?>" class="form-label">Jenis Kelamin</label>
                                                            <select class="form-select" id="jk-<?php echo $penduduk['nik']; ?>" name="jenis_kelamin" required>
                                                                <option value="LAKI-LAKI" <?php echo ($penduduk['jenis_kelamin'] == 'LAKI-LAKI') ? 'selected' : ''; ?>>Laki-laki</option>
                                                                <option value="PEREMPUAN" <?php echo ($penduduk['jenis_kelamin'] == 'PEREMPUAN') ? 'selected' : ''; ?>>Perempuan</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="agama-<?php echo $penduduk['nik']; ?>" class="form-label">Agama</label>
                                                            <select class="form-select" id="agama-<?php echo $penduduk['nik']; ?>" name="agama" required>
                                                                <option value="ISLAM" <?php echo ($penduduk['agama'] == 'ISLAM') ? 'selected' : ''; ?>>Islam</option>
                                                                <option value="KRISTEN_PROTESTAN" <?php echo ($penduduk['agama'] == 'KRISTEN_PROTESTAN') ? 'selected' : ''; ?>>Kristen Protestan</option>
                                                                <option value="KRISTEN_KATOLIK" <?php echo ($penduduk['agama'] == 'KRISTEN_KATOLIK') ? 'selected' : ''; ?>>Kristen Katolik</option>
                                                                <option value="HINDU" <?php echo ($penduduk['agama'] == 'HINDU') ? 'selected' : ''; ?>>Hindu</option>
                                                                <option value="BUDDHA" <?php echo ($penduduk['agama'] == 'BUDDHA') ? 'selected' : ''; ?>>Buddha</option>
                                                                <option value="KONGHUCU" <?php echo ($penduduk['agama'] == 'KONGHUCU') ? 'selected' : ''; ?>>Konghucu</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="alamat-<?php echo $penduduk['nik']; ?>" class="form-label">Alamat</label>
                                                            <input type="text" class="form-control" id="alamat-<?php echo $penduduk['nik']; ?>" name="alamat" value="<?php echo $penduduk['alamat']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="rt_rw-<?php echo $penduduk['nik']; ?>" class="form-label">RT/RW</label>
                                                            <input type="text" class="form-control" id="rt_rw-<?php echo $penduduk['nik']; ?>" name="rt/rw" value="<?php echo $penduduk['rt/rw']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="desa-<?php echo $penduduk['nik']; ?>" class="form-label">Kel/Desa</label>
                                                            <input type="text" class="form-control" id="desa-<?php echo $penduduk['nik']; ?>" name="kel/des" value="<?php echo $penduduk['kel/des']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="kecamatan-<?php echo $penduduk['nik']; ?>" class="form-label">Kecamatan</label>
                                                            <input type="text" class="form-control" id="kecamatan-<?php echo $penduduk['nik']; ?>" name="kecamatan" value="<?php echo $penduduk['kecamatan']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="kabupaten-<?php echo $penduduk['nik']; ?>" class="form-label">Kabupaten/Kota</label>
                                                            <input type="text" class="form-control" id="kabupaten-<?php echo $penduduk['nik']; ?>" name="kabupaten/kota" value="<?php echo $penduduk['kabupaten/kota']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="kodepos-<?php echo $penduduk['nik']; ?>" class="form-label">Kodepos</label>
                                                            <input type="text" class="form-control" id="kodepos-<?php echo $penduduk['nik']; ?>" name="kodepos" value="<?php echo $penduduk['kodepos']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="provinsi-<?php echo $penduduk['nik']; ?>" class="form-label">Provinsi</label>
                                                            <input type="text" class="form-control" id="provinsi-<?php echo $penduduk['nik']; ?>" name="provinsi" value="<?php echo $penduduk['provinsi']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="pekerjaan-<?php echo $penduduk['nik']; ?>" class="form-label">Pekerjaan</label>
                                                            <select class="form-select" id="pekerjaan-<?php echo $penduduk['nik']; ?>" name="pekerjaan" required>
                                                                <option value="PNS" <?php echo ($penduduk['pekerjaan'] == 'PNS') ? 'selected' : ''; ?>>PNS</option>
                                                                <option value="TNI" <?php echo ($penduduk['pekerjaan'] == 'TNI') ? 'selected' : ''; ?>>TNI</option>
                                                                <option value="POLRI" <?php echo ($penduduk['pekerjaan'] == 'POLRI') ? 'selected' : ''; ?>>POLRI</option>
                                                                <option value="PEG_ SWASTA" <?php echo ($penduduk['pekerjaan'] == 'PEG_ SWASTA') ? 'selected' : ''; ?>>Pegawai Swasta</option>
                                                                <option value="WIRASWASTA" <?php echo ($penduduk['pekerjaan'] == 'WIRASWASTA') ? 'selected' : ''; ?>>Wiraswasta</option>
                                                                <option value="PETANI" <?php echo ($penduduk['pekerjaan'] == 'PETANI') ? 'selected' : ''; ?>>Petani</option>
                                                                <option value="BURUH" <?php echo ($penduduk['pekerjaan'] == 'BURUH') ? 'selected' : ''; ?>>Buruh</option>
                                                                <option value="PEL_MHS" <?php echo ($penduduk['pekerjaan'] == 'PEL_MHS') ? 'selected' : ''; ?>>Pelajar/Mahasiswa</option>
                                                                <option value="IRT" <?php echo ($penduduk['pekerjaan'] == 'IRT') ? 'selected' : ''; ?>>Ibu Rumah Tangga</option>
                                                                <option value="PENSIUNAN" <?php echo ($penduduk['pekerjaan'] == 'PENSIUNAN') ? 'selected' : ''; ?>>Pensiunan</option>
                                                                <option value="LAINNYA" <?php echo ($penduduk['pekerjaan'] == 'LAINNYA') ? 'selected' : ''; ?>>Lainnya</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="status_kawin-<?php echo $penduduk['nik']; ?>" class="form-label">Status Kawin</label>
                                                            <select class="form-select" id="status_kawin-<?php echo $penduduk['nik']; ?>" name="status_kawin" required>
                                                                <option value="Belum Kawin" <?php echo ($penduduk['status_kawin'] == 'Belum Kawin') ? 'selected' : ''; ?>>Belum Kawin</option>
                                                                <option value="Kawin" <?php echo ($penduduk['status_kawin'] == 'Kawin') ? 'selected' : ''; ?>>Kawin</option>
                                                                <option value="Cerai" <?php echo ($penduduk['status_kawin'] == 'Cerai') ? 'selected' : ''; ?>>Cerai</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="pendidikan-<?php echo $penduduk['nik']; ?>" class="form-label">Pendidikan</label>
                                                            <select class="form-select" id="pendidikan-<?php echo $penduduk['nik']; ?>" name="pendidikan" required>
                                                                <option value="TIDAK_SEKOLAH" <?php echo ($penduduk['pendidikan'] == 'TIDAK_SEKOLAH') ? 'selected' : ''; ?>>Tidak/Belum Sekolah</option>
                                                                <option value="SD" <?php echo ($penduduk['pendidikan'] == 'SD') ? 'selected' : ''; ?>>SD</option>
                                                                <option value="SMP" <?php echo ($penduduk['pendidikan'] == 'SMP') ? 'selected' : ''; ?>>SMP</option>
                                                                <option value="SMA" <?php echo ($penduduk['pendidikan'] == 'SMA') ? 'selected' : ''; ?>>SMA</option>
                                                                <option value="SMK" <?php echo ($penduduk['pendidikan'] == 'SMK') ? 'selected' : ''; ?>>SMK</option>
                                                                <option value="D1_D2_D3" <?php echo ($penduduk['pendidikan'] == 'D1_D2_D3') ? 'selected' : ''; ?>>D1/D2/D3</option>
                                                                <option value="S1" <?php echo ($penduduk['pendidikan'] == 'S1') ? 'selected' : ''; ?>>S1</option>
                                                                <option value="S2" <?php echo ($penduduk['pendidikan'] == 'S2') ? 'selected' : ''; ?>>S2</option>
                                                                <option value="S3" <?php echo ($penduduk['pendidikan'] == 'S3') ? 'selected' : ''; ?>>S3</option>
                                                            </select>
                                                        </div>

                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="submit_edit_penduduk" class="btn btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                                <!-- Header -->



                                            </div>
                                        </div>
                                    </div>


                                    <!-- Modal Hapus -->
                                    <div class="modal fade" id="hapusModal" tabindex="-1" aria-labelledby="hapusModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="hapusModalLabel">Hapus Data Penduduk</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Apakah Anda yakin ingin menghapus data ini?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-danger">Hapus</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Lihat KK -->
                                    <div class="modal fade" id="lihatKKModal" tabindex="-1" aria-labelledby="lihatKKModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-xl">
                                            <div class="modal-content border border-4 border-primary">
                                                <!-- Header KK -->
                                                <div class="modal-header bg-primary text-white py-3">
                                                    <div class="w-100 text-center">
                                                        <h4 class="modal-title mb-0 fw-bold">KARTU KELUARGA</h4>
                                                        <p class="mb-0">No. 22200002220</p>
                                                    </div>
                                                    <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body p-4">
                                                    <!-- Data Keluarga -->
                                                    <div class="card border-primary mb-4">
                                                        <div class="card-header bg-light">
                                                            <h6 class="m-0 font-weight-bold text-primary">DATA KELUARGA</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <div class="row g-2 mb-2">
                                                                        <div class="col-md-4 fw-bold">Nama Kepala Keluarga</div>
                                                                        <div class="col-md-8 text-uppercase">MONKEY D. DRAGON</div>
                                                                    </div>
                                                                    <div class="row g-2 mb-2">
                                                                        <div class="col-md-4 fw-bold">Alamat</div>
                                                                        <div class="col-md-8">JL. BAJAK LAUT NO. 10, RT 001/RW 002</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="row g-2 mb-2">
                                                                        <div class="col-md-4 fw-bold">Desa/Kelurahan</div>
                                                                        <div class="col-md-8">RED LINES</div>
                                                                    </div>
                                                                    <div class="row g-2 mb-2">
                                                                        <div class="col-md-4 fw-bold">Kecamatan</div>
                                                                        <div class="col-md-8">GRAND LINE</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Daftar Anggota Keluarga -->
                                                    <div class="card border-primary mb-4">
                                                        <div class="card-header bg-light">
                                                            <h6 class="m-0 font-weight-bold text-primary">ANGGOTA KELUARGA</h6>
                                                        </div>
                                                        <div class="card-body p-0">
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered mb-0">
                                                                    <thead class="bg-primary text-white">
                                                                        <tr>
                                                                            <th rowspan="2" class="align-middle text-center">No</th>
                                                                            <th rowspan="2" class="align-middle">Nama Lengkap</th>
                                                                            <th rowspan="2" class="align-middle text-center">NIK</th>
                                                                            <th rowspan="2" class="align-middle text-center">Jenis Kelamin</th>
                                                                            <th rowspan="2" class="align-middle text-center">Tempat/Tgl Lahir</th>
                                                                            <th rowspan="2" class="align-middle text-center">Agama</th>
                                                                            <th rowspan="2" class="align-middle text-center">Pendidikan</th>
                                                                            <th rowspan="2" class="align-middle text-center">Pekerjaan</th>
                                                                            <th colspan="2" class="text-center">Status</th>
                                                                        </tr>
                                                                        <tr>
                                                                            <th class="text-center">Perkawinan</th>
                                                                            <th class="text-center">Hubungan</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <!-- Anggota 1 (Kepala Keluarga) -->
                                                                        <tr>
                                                                            <td class="text-center">1</td>
                                                                            <td class="text-uppercase">MONKEY D. DRAGON</td>
                                                                            <td class="font-monospace">220411100001</td>
                                                                            <td class="text-center">LAKI-LAKI</td>
                                                                            <td>LOGUETOWN, 01-01-1900</td>
                                                                            <td class="text-center">BAJAK LAUT</td>
                                                                            <td class="text-center">SMA</td>
                                                                            <td class="text-center">REVOLUTIONARY</td>
                                                                            <td class="text-center">KAWIN</td>
                                                                            <td class="text-center">KEPALA KELUARGA</td>
                                                                        </tr>
                                                                        <!-- Anggota 2 (Istri) -->
                                                                        <tr>
                                                                            <td class="text-center">2</td>
                                                                            <td class="text-uppercase">MONKEY D. ROUGE</td>
                                                                            <td class="font-monospace">220411100002</td>
                                                                            <td class="text-center">PEREMPUAN</td>
                                                                            <td>EAST BLUE, 01-01-1905</td>
                                                                            <td class="text-center">BAJAK LAUT</td>
                                                                            <td class="text-center">SMA</td>
                                                                            <td class="text-center">PENELITI</td>
                                                                            <td class="text-center">KAWIN</td>
                                                                            <td class="text-center">ISTRI</td>
                                                                        </tr>
                                                                        <!-- Anggota 3 (Anak) -->
                                                                        <tr>
                                                                            <td class="text-center">3</td>
                                                                            <td class="text-uppercase">MONKEY D. JEKI</td>
                                                                            <td class="font-monospace">220411100039</td>
                                                                            <td class="text-center">LAKI-LAKI</td>
                                                                            <td>RED LINES, 01-01-1923</td>
                                                                            <td class="text-center">BAJAK LAUT</td>
                                                                            <td class="text-center">SD</td>
                                                                            <td class="text-center">KAPTEN BAJAK LAUT</td>
                                                                            <td class="text-center">BELUM KAWIN</td>
                                                                            <td class="text-center">ANAK</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Keterangan Tambahan -->
                                                    <div class="card border-primary">
                                                        <div class="card-header bg-light">
                                                            <h6 class="m-0 font-weight-bold text-primary">KETERANGAN</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="row g-2 mb-2">
                                                                        <div class="col-md-4 fw-bold">Tanggal Cetak</div>
                                                                        <div class="col-md-8"><?= date('d-m-Y') ?></div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="row g-2 mb-2">
                                                                        <div class="col-md-4 fw-bold">Ditandatangani oleh</div>
                                                                        <div class="col-md-8">Kepala Dinas Kependudukan</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Footer -->
                                                <div class="modal-footer bg-light py-2">
                                                    <div class="small text-muted">Dokumen ini sah dan diterbitkan secara elektronik</div>
                                                    <div>
                                                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i> Tutup
                                                        </button>
                                                        <button type="button" class="btn btn-primary">
                                                            <i class="fas fa-print me-1"></i> Cetak KK
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                                <td><?php echo $penduduk['nik']; ?></td>
                                <td><?php echo $penduduk['nama_penduduk']; ?></td>
                                <td></td>
                                <td><?php echo $penduduk['alamat']; ?></td>
                                <td><?php echo $penduduk['pendidikan']; ?></td>
                                <td><?php echo $umur; ?></td>
                                <td><?php echo $penduduk['pekerjaan']; ?></td>
                                <td><?php echo $penduduk['status_kawin']; ?></td>
                            </tr>
                            <tr>

                            </tr>

                    </tbody>
                <?php endforeach; // Akhir dari loop foreach 
                ?>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- More content rows... -->
<?php
$content = ob_get_clean();
include 'template1/base.php';
?>