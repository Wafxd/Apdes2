<?php
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
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="tambahPendudukModalLabel">Tambah Data Penduduk</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form>
                                        <div class="mb-3">
                                            <label for="nik" class="form-label">NIK</label>
                                            <input type="text" class="form-control" id="nik" placeholder="Enter NIK" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nama" class="form-label">Nama</label>
                                            <input type="text" class="form-control" id="nama" placeholder="Enter Nama" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="ttl" class="form-label">Tempat, Tanggal Lahir</label>
                                            <input type="text" class="form-control" id="ttl" placeholder="Enter Tempat, Tanggal Lahir" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="alamat" class="form-label">Alamat</label>
                                            <input type="text" class="form-control" id="alamat" placeholder="Enter Alamat" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="pekerjaan" class="form-label">Pekerjaan</label>
                                            <input type="text" class="form-control" id="pekerjaan" placeholder="Enter Pekerjaan" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="statusKawin" class="form-label">Status Kawin</label>
                                            <select class="form-select" id="statusKawin" required>
                                                <option value="">Pilih Status Kawin</option>
                                                <option value="belumKawin">Belum Kawin</option>
                                                <option value="kawin">Kawin</option>
                                                <option value="cerai">Cerai</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="pendidikan" class="form-label">Pendidikan</label>
                                            <select class="form-select" id="pendidikan" required>
                                                <option value="">Pilih Pendidikan</option>
                                                <option value="sd">SD</option>
                                                <option value="smp">SMP</option>
                                                <option value="sma">SMA</option>
                                                <option value="sarjana">Sarjana</option>
                                                <option value="pascaSarjana">Pasca Sarjana</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </div>
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
                                            <th>Desa</th>
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
                                            <th>Desa</th>
                                            <th>Alamat</th>
                                            <th>Pendidikan</th>
                                            <th>Umur</th>
                                            <th>Pekerjaan</th>
                                            <th>Status Kawin</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
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
                                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#detailModal">
                                                            <i class="fas fa-info-circle fa-sm me-2 text-primary"></i> 
                                                            <span>| Detail</span>
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editModal">
                                                            <i class="fas fa-edit fa-sm me-2 text-success"></i>
                                                            <span>| Edit</span>
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#hapusModal">
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
                                                <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content border border-3 border-primary">
                                                            <!-- Header dengan desain mirip KK -->
                                                            <div class="modal-header bg-primary text-white py-3">
                                                                <div class="w-100 text-center">
                                                                    <h5 class="modal-title mb-0 fw-bold" id="detailModalLabel">KARTU TANDA PENDUDUK</h5>
                                                                    <p class="mb-0 small">Republik Indonesia</p>
                                                                </div>
                                                                <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            
                                                            <div class="modal-body p-4">
                                                                <!-- Bagian Foto dan Data Utama -->
                                                                <div class="row mb-4">
                                                                    <div class="col-md-9">
                                                                        <!-- Baris data dengan grid lebih rapi -->
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-4 fw-bold">NIK</div>
                                                                            <div class="col-md-8 font-monospace">220411100039</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-4 fw-bold">Nama</div>
                                                                            <div class="col-md-8 text-uppercase fw-bold">MONKEY D. JEKI</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-4 fw-bold">Tempat/Tgl Lahir</div>
                                                                            <div class="col-md-8">RED LINES, 01-01-1923</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-4 fw-bold">Jenis Kelamin</div>
                                                                            <div class="col-md-8">LAKI-LAKI</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-4 fw-bold">Alamat</div>
                                                                            <div class="col-md-8">JL. BAJAK LAUT NO. 10</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-4 fw-bold">RT/RW</div>
                                                                            <div class="col-md-8">001/002</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-4 fw-bold">Kel/Desa</div>
                                                                            <div class="col-md-8">RED LINES</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-4 fw-bold">Kecamatan</div>
                                                                            <div class="col-md-8">GRAND LINE</div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Garis pemisah seperti KK -->
                                                                <hr class="border border-primary border-2 opacity-50">

                                                                <!-- Bagian Data Orang Tua -->
                                                                <div class="row mb-4">
                                                                    <div class="col-md-6">
                                                                        <h6 class="fw-bold text-primary mb-3">DATA AYAH</h6>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">NIK</div>
                                                                            <div class="col-md-7 font-monospace">220411100001</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">Nama</div>
                                                                            <div class="col-md-7 text-uppercase">MONKEY D. DRAGON</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">Tempat/Tgl Lahir</div>
                                                                            <div class="col-md-7">LOGUETOWN, 01-01-1900</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6 class="fw-bold text-primary mb-3">DATA IBU</h6>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">NIK</div>
                                                                            <div class="col-md-7 font-monospace">220411100002</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">Nama</div>
                                                                            <div class="col-md-7 text-uppercase">MONKEY D. ROUGE</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">Tempat/Tgl Lahir</div>
                                                                            <div class="col-md-7">EAST BLUE, 01-01-1905</div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Garis pemisah seperti KK -->
                                                                <hr class="border border-primary border-2 opacity-50">

                                                                <!-- Bagian Data Tambahan -->
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">Agama</div>
                                                                            <div class="col-md-7">BAJAK LAUT</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">Pekerjaan</div>
                                                                            <div class="col-md-7">KAPTEN BAJAK LAUT</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">Kewarganegaraan</div>
                                                                            <div class="col-md-7">WNI</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">Status Perkawinan</div>
                                                                            <div class="col-md-7">BELUM KAWIN</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">Pendidikan</div>
                                                                            <div class="col-md-7">SD</div>
                                                                        </div>
                                                                        <div class="row g-2 mb-2">
                                                                            <div class="col-md-5 fw-bold">No. KK</div>
                                                                            <div class="col-md-7 font-monospace">22200002220</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
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
                                                <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content border border-3 border-primary">
                                                            <!-- Header -->
                                                            <div class="modal-header bg-primary text-white py-3">
                                                                <div class="w-100 text-center">
                                                                    <h5 class="modal-title mb-0 fw-bold" id="editModalLabel">EDIT DATA PENDUDUK</h5>
                                                                    <p class="mb-0 small">Republik Indonesia</p>
                                                                </div>
                                                                <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            
                                                            <div class="modal-body p-4">
                                                                <form id="formEditPenduduk">
                                                                    <!-- Bagian Data Utama -->
                                                                    <div class="row mb-4">
                                                                        <div class="col-md-12">
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">NIK</div>
                                                                                <div class="col-md-4">
                                                                                    <input type="text" class="form-control font-monospace" id="inputNIK" value="220411100039" readonly>
                                                                                </div>
                                                                                <div class="col-md-2 fw-bold align-self-center">No. KK</div>
                                                                                <div class="col-md-4">
                                                                                    <input type="text" class="form-control font-monospace" id="inputNoKK" value="22200002220">
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">Nama</div>
                                                                                <div class="col-md-10">
                                                                                    <input type="text" class="form-control text-uppercase" id="inputNama" value="MONKEY D. JEKI">
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">Tempat/Tgl Lahir</div>
                                                                                <div class="col-md-4">
                                                                                    <input type="text" class="form-control" id="inputTempatLahir" value="RED LINES">
                                                                                </div>
                                                                                <div class="col-md-2">
                                                                                    <input type="date" class="form-control" id="inputTanggalLahir" value="1923-01-01">
                                                                                </div>
                                                                                <div class="col-md-2 fw-bold align-self-center">Jenis Kelamin</div>
                                                                                <div class="col-md-2">
                                                                                    <select class="form-select" id="inputJenisKelamin">
                                                                                        <option value="LAKI-LAKI" selected>Laki-laki</option>
                                                                                        <option value="PEREMPUAN">Perempuan</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">Alamat</div>
                                                                                <div class="col-md-10">
                                                                                    <textarea class="form-control" id="inputAlamat" rows="2">JL. BAJAK LAUT NO. 10</textarea>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">RT/RW</div>
                                                                                <div class="col-md-2">
                                                                                    <input type="text" class="form-control" id="inputRT" value="001">
                                                                                </div>
                                                                                <div class="col-md-2">
                                                                                    <input type="text" class="form-control" id="inputRW" value="002">
                                                                                </div>
                                                                                <div class="col-md-2 fw-bold align-self-center">Desa/Kel</div>
                                                                                <div class="col-md-4">
                                                                                    <input type="text" class="form-control" id="inputDesa" value="RED LINES">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Garis pemisah -->
                                                                    <hr class="border border-primary border-2 opacity-50">

                                                                    <!-- Bagian Data Orang Tua -->
                                                                    <div class="row mb-4">
                                                                        <div class="col-md-6">
                                                                            <h6 class="fw-bold text-primary mb-3">DATA AYAH</h6>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-4 fw-bold align-self-center">NIK</div>
                                                                                <div class="col-md-8">
                                                                                    <input type="text" class="form-control font-monospace" id="inputNIKAyah" value="220411100001">
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-4 fw-bold align-self-center">Nama</div>
                                                                                <div class="col-md-8">
                                                                                    <input type="text" class="form-control text-uppercase" id="inputNamaAyah" value="MONKEY D. DRAGON">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <h6 class="fw-bold text-primary mb-3">DATA IBU</h6>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-4 fw-bold align-self-center">NIK</div>
                                                                                <div class="col-md-8">
                                                                                    <input type="text" class="form-control font-monospace" id="inputNIKIbu" value="220411100002">
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-4 fw-bold align-self-center">Nama</div>
                                                                                <div class="col-md-8">
                                                                                    <input type="text" class="form-control text-uppercase" id="inputNamaIbu" value="MONKEY D. ROUGE">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Garis pemisah -->
                                                                    <hr class="border border-primary border-2 opacity-50">

                                                                    <!-- Bagian Data Tambahan -->
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-4 fw-bold align-self-center">Agama</div>
                                                                                <div class="col-md-8">
                                                                                    <select class="form-select" id="inputAgama">
                                                                                        <option value="ISLAM">Islam</option>
                                                                                        <option value="KRISTEN">Kristen</option>
                                                                                        <option value="KATHOLIK">Katholik</option>
                                                                                        <option value="HINDU">Hindu</option>
                                                                                        <option value="BUDHA">Budha</option>
                                                                                        <option value="KONGHUCU">Konghucu</option>
                                                                                        <option value="BAJAK LAUT" selected>Bajak Laut</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-4 fw-bold align-self-center">Pekerjaan</div>
                                                                                <div class="col-md-8">
                                                                                    <input type="text" class="form-control" id="inputPekerjaan" value="KAPTEN BAJAK LAUT">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-4 fw-bold align-self-center">Status Perkawinan</div>
                                                                                <div class="col-md-8">
                                                                                    <select class="form-select" id="inputStatusKawin">
                                                                                        <option value="BELUM KAWIN" selected>Belum Kawin</option>
                                                                                        <option value="KAWIN">Kawin</option>
                                                                                        <option value="CERAI HIDUP">Cerai Hidup</option>
                                                                                        <option value="CERAI MATI">Cerai Mati</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-4 fw-bold align-self-center">Pendidikan</div>
                                                                                <div class="col-md-8">
                                                                                    <select class="form-select" id="inputPendidikan">
                                                                                        <option value="TIDAK/BELUM SEKOLAH">Tidak/Belum Sekolah</option>
                                                                                        <option value="SD" selected>SD</option>
                                                                                        <option value="SMP">SMP</option>
                                                                                        <option value="SMA">SMA</option>
                                                                                        <option value="DIPLOMA">Diploma</option>
                                                                                        <option value="SARJANA">Sarjana</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                            <!-- Footer -->
                                                            <div class="modal-footer bg-light py-2">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                    <i class="fas fa-times me-1"></i> Batal
                                                                </button>
                                                                <button type="button" class="btn btn-primary" id="btnSimpanPerubahan">
                                                                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                                                                </button>
                                                            </div>
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
                                            <td>220411100039</td>
                                            <td>Monkey D Jeki</td>
                                            <td>22200002220</td>
                                            <td>Red Lines</td>
                                            <td>Red Lines</td>
                                            <td>SD</td>
                                            <td>99</td>
                                            <td>Bajak laut</td>
                                            <td>LC</td>
                                        </tr>
                                        
                                        
                                    </tbody>
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