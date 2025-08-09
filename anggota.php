<?php
$pageTitle = "Data Anggota Keluarga";
$pageHeaderButton = '<a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
</a>';

ob_start();
?>
<!-- Content Row -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <a href="#" class="btn btn-success btn-icon-split" data-bs-toggle="modal" data-bs-target="#tambahKKModal">
                        <span class="icon text-white-50">
                            <i class="fas fa-flag"></i>
                        </span>
                        <span class="text">+ Tambah Anggota Keluarga</span>
                    </a>

                    <!-- Modal Tambah KK -->
                    <div class="modal fade" id="tambahKKModal" tabindex="-1" aria-labelledby="tambahKKModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="tambahKKModalLabel">Tambah Data Kartu Keluarga (KK)</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <form>
                                        <div class="row mb-4">
                                            <div class="col-md-12">
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-2 fw-bold align-self-center">NIK</div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control font-monospace" id="inputNIK">
                                                    </div>
                                                    <div class="col-md-2 fw-bold align-self-center">No. KK Sementara</div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control font-monospace" id="inputNoKK">
                                                    </div>
                                                </div>
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-2 fw-bold align-self-center">Nama</div>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control text-uppercase" id="inputNama">
                                                    </div>
                                                </div>
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-2 fw-bold align-self-center">Tempat/Tgl Lahir</div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" id="inputTempatLahir">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="date" class="form-control" id="inputTanggalLahir">
                                                    </div>
                                                </div>
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-2 fw-bold align-self-center">Jenis Kelamin</div>
                                                    <div class="col-md-3">
                                                        <select class="form-select" id="inputJenisKelamin">
                                                            <option value="LAKI-LAKI" selected>Laki-laki</option>
                                                            <option value="PEREMPUAN">Perempuan</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 fw-bold align-self-center">Agama</div>
                                                    <div class="col-md-2">
                                                        <select class="form-select" id="inputAgama">
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
                                                    <div class="col-md-2">
                                                        <select class="form-select" id="inputPendidikan">
                                                            <option value="TIDAK_SEKOLAH" selected>Tidak/Belum Sekolah</option>
                                                            <option value="SD">SD (Sekolah Dasar)</option>
                                                            <option value="SMP">SMP (Sekolah Menengah Pertama)</option>
                                                            <option value="SMA">SMA (Sekolah Menengah Atas)</option>
                                                            <option value="SMK">SMK (Sekolah Menengah Kejuruan)</option>
                                                            <option value="D1_D2_D3">D1/D2/D3 (Diploma)</option>
                                                            <option value="S1">S1 (Sarjana)</option>
                                                            <option value="S2">S2 (Magister)</option>
                                                            <option value="S3">S3 (Doktor)</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-2 fw-bold align-self-center">Pekerjaan</div>
                                                    <div class="col-md-2">
                                                        <select class="form-select" id="inputPekerjaan">
                                                            <option value="PNS" selected>PNS (Pegawai Negeri Sipil)</option>
                                                            <option value="TNI">TNI (Tentara Nasional Indonesia)</option>
                                                            <option value="POLRI">POLRI (Kepolisian Negara Republik Indonesia)</option>
                                                            <option value="PEG_ SWASTA">Pegawai Swasta</option>
                                                            <option value="WIRASWASTA">Wiraswasta</option>
                                                            <option value="PETANI">Petani</option>
                                                            <option value="BURUH">Buruh</option>
                                                            <option value="PEL_MHS">Pelajar/Mahasiswa</option>
                                                            <option value="IRT">Ibu Rumah Tangga</option>
                                                            <option value="PENSIUNAN">Pensiunan</option>
                                                            <option value="LAINNYA">Lainnya</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-2 fw-bold align-self-center">Alamat</div>
                                                    <div class="col-md-10">
                                                        <textarea class="form-control" id="inputAlamat" rows="2"></textarea>
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
                                                        <input type="text" class="form-control" id="inputDesa" value="Sukolilo Timur">
                                                    </div>
                                                </div>
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-2 fw-bold align-self-center">Kecamatan</div>
                                                    <div class="col-md-3">
                                                        <input type="text" class="form-control" id="inputKecamatan" value="Sukolilo">
                                                    </div>
                                                    <div class="col-md-2 fw-bold align-self-center">Kabupaten/Kota</div>
                                                    <div class="col-md-3">
                                                        <input type="text" class="form-control" id="inputkab" value="Bangkalan">
                                                    </div>
                                                </div>
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-2 fw-bold align-self-center">Kode Pos</div>
                                                    <div class="col-md-3">
                                                        <input type="number" class="form-control" id="inputpos" value="69162">
                                                    </div>
                                                    <div class="col-md-2 fw-bold align-self-center">Provinsi</div>
                                                    <div class="col-md-3">
                                                        <input type="text" class="form-control" id="inputprov" value="Jawa Timur">
                                                    </div>
                                                </div>
                                            </div>
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
                            <h6 class="m-0 font-weight-bold text-primary">Rincian Keluarga</h6>
                        </div>
                        <div class="card-body">
                            <div>
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <tbody>
                                        <tr>
                                            <th style="text-align: left; width: 15%;">Nomor KK</th>
                                            <td style="text-align: left; width: 1%;">:</td>
                                            <td style="text-align: left;">220411100039</td>
                                        </tr>
                                        <tr>
                                            <th style="text-align: left;">Nama Kepala Keluarga</th>
                                            <td style="text-align: left;">:</td>
                                            <td style="text-align: left;">monkey d luffy</td>
                                        </tr>
                                        <tr>
                                            <th style="text-align: left;">Alamat</th>
                                            <td style="text-align: left;">:</td>
                                            <td style="text-align: left;">RT. 03 RW. 04 / Dusun Sukolilo Timur</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="py-3 mt-5">
                                <h6 class="m-0 font-weight-bold" style="color:black">Daftar Anggota Keluarga</h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" style="margin-bottom:150px"  width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Aksi</th>
                                            <th>NIK</th>
                                            <th style="width: 30%">Nama</th>
                                            <th>Tanggal Lahir</th>
                                            <th>Jenis Kelamin</th>
                                            <th>Hubungan</th>
                                        </tr>
                                    </thead>
                                    
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
                                                        
                                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editModal">
                                                            <i class="fas fa-edit fa-sm me-2 text-success"></i>
                                                            <span>| Edit</span>
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#hapusModal">
                                                            <i class="fas fa-trash fa-sm me-2 text-danger"></i>
                                                            <span>| Hapus</span>
                                                        </a></li>
                                                    </ul>
                                                </div>
                                                <!-- Modal Edit -->
                                                <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editModalLabel">Edit Data Penduduk</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form>
                                                                    <div class="row mb-4">
                                                                        <div class="col-md-12">
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">NIK</div>
                                                                                <div class="col-md-4">
                                                                                    <input type="text" class="form-control font-monospace" id="inputNIK" value="220411100039" readonly>
                                                                                </div>
                                                                                <div class="col-md-2 fw-bold align-self-center">No. KK</div>
                                                                                <div class="col-md-4">
                                                                                    <input type="text" class="form-control font-monospace" id="inputNoKK" value="220411100039" readonly>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">Nama</div>
                                                                                <div class="col-md-10">
                                                                                    <input type="text" class="form-control text-uppercase" id="inputNama">
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">Tempat/Tgl Lahir</div>
                                                                                <div class="col-md-4">
                                                                                    <input type="text" class="form-control" id="inputTempatLahir">
                                                                                </div>
                                                                                <div class="col-md-2">
                                                                                    <input type="date" class="form-control" id="inputTanggalLahir">
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">Jenis Kelamin</div>
                                                                                <div class="col-md-3">
                                                                                    <select class="form-select" id="inputJenisKelamin">
                                                                                        <option value="LAKI-LAKI" selected>Laki-laki</option>
                                                                                        <option value="PEREMPUAN">Perempuan</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="col-md-2 fw-bold align-self-center">Agama</div>
                                                                                <div class="col-md-2">
                                                                                    <select class="form-select" id="inputAgama">
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
                                                                                <div class="col-md-2">
                                                                                    <select class="form-select" id="inputPendidikan">
                                                                                        <option value="TIDAK_SEKOLAH" selected>Tidak/Belum Sekolah</option>
                                                                                        <option value="SD">SD (Sekolah Dasar)</option>
                                                                                        <option value="SMP">SMP (Sekolah Menengah Pertama)</option>
                                                                                        <option value="SMA">SMA (Sekolah Menengah Atas)</option>
                                                                                        <option value="SMK">SMK (Sekolah Menengah Kejuruan)</option>
                                                                                        <option value="D1_D2_D3">D1/D2/D3 (Diploma)</option>
                                                                                        <option value="S1">S1 (Sarjana)</option>
                                                                                        <option value="S2">S2 (Magister)</option>
                                                                                        <option value="S3">S3 (Doktor)</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>

                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">Pekerjaan</div>
                                                                                <div class="col-md-2">
                                                                                    <select class="form-select" id="inputPekerjaan">
                                                                                        <option value="PNS" selected>PNS (Pegawai Negeri Sipil)</option>
                                                                                        <option value="TNI">TNI (Tentara Nasional Indonesia)</option>
                                                                                        <option value="POLRI">POLRI (Kepolisian Negara Republik Indonesia)</option>
                                                                                        <option value="PEG_ SWASTA">Pegawai Swasta</option>
                                                                                        <option value="WIRASWASTA">Wiraswasta</option>
                                                                                        <option value="PETANI">Petani</option>
                                                                                        <option value="BURUH">Buruh</option>
                                                                                        <option value="PEL_MHS">Pelajar/Mahasiswa</option>
                                                                                        <option value="IRT">Ibu Rumah Tangga</option>
                                                                                        <option value="PENSIUNAN">Pensiunan</option>
                                                                                        <option value="LAINNYA">Lainnya</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>

                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">Alamat</div>
                                                                                <div class="col-md-10">
                                                                                    <textarea class="form-control" id="inputAlamat" rows="2"></textarea>
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
                                                                                    <input type="text" class="form-control" id="inputDesa" value="Sukolilo Timur">
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">Kecamatan</div>
                                                                                <div class="col-md-3">
                                                                                    <input type="text" class="form-control" id="inputKecamatan" value="Sukolilo">
                                                                                </div>
                                                                                <div class="col-md-2 fw-bold align-self-center">Kabupaten/Kota</div>
                                                                                <div class="col-md-3">
                                                                                    <input type="text" class="form-control" id="inputkab" value="Bangkalan">
                                                                                </div>
                                                                            </div>
                                                                            <div class="row g-2 mb-2">
                                                                                <div class="col-md-2 fw-bold align-self-center">Kode Pos</div>
                                                                                <div class="col-md-3">
                                                                                    <input type="number" class="form-control" id="inputpos" value="69162">
                                                                                </div>
                                                                                <div class="col-md-2 fw-bold align-self-center">Provinsi</div>
                                                                                <div class="col-md-3">
                                                                                    <input type="text" class="form-control" id="inputprov" value="Jawa Timur">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                </form>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="button" class="btn btn-primary">Save changes</button>
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

                                                

                                            </td>
                                            <td>220411100039</td>
                                            <td>Monkey D Luffy</td>
                                            <td>17 Agustus 1945</td>
                                            <td>Laki Laki</td>
                                            <td>Kepala Keluarga</td>
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