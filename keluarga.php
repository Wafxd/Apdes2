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
                    <a href="#" class="btn btn-success btn-icon-split" data-bs-toggle="modal" data-bs-target="#tambahKKModal">
                        <span class="icon text-white-50">
                            <i class="fas fa-flag"></i>
                        </span>
                        <span class="text">+ Tambah KK</span>
                    </a>

                    <!-- Modal Tambah KK -->
                    <div class="modal fade" id="tambahKKModal" tabindex="-1" aria-labelledby="tambahKKModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="tambahKKModalLabel">Tambah Data Kartu Keluarga (KK)</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form>
                                        <div class="mb-3">
                                            <label for="nik" class="form-label">NIK</label>
                                            <input type="text" class="form-control" id="nik" placeholder="Enter NIK" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="noKK" class="form-label">No KK Sementara</label>
                                            <input type="text" class="form-control" id="noKK" placeholder="Enter No KK Sementara" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nama" class="form-label">Nama</label>
                                            <input type="text" class="form-control" id="nama" placeholder="Enter Nama" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="hubunganKeluarga" class="form-label">Hubungan dalam Keluarga</label>
                                            <select class="form-select" id="hubunganKeluarga" required>
                                                <option value="">Pilih Hubungan</option>
                                                <option value="kepalaKeluarga">Kepala Keluarga</option>
                                                <option value="istri">Istri</option>
                                                <option value="anak">Anak</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="jenisKelamin" class="form-label">Jenis Kelamin</label>
                                            <select class="form-select" id="jenisKelamin" required>
                                                <option value="">Pilih Jenis Kelamin</option>
                                                <option value="lakiLaki">Laki-laki</option>
                                                <option value="perempuan">Perempuan</option>
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
                                                        <li><a class="dropdown-item" href="#">
                                                            <i class="fas fa-id-card fa-sm me-2 text-info"></i>
                                                            <span>| Lihat KK</span>
                                                        </a></li>
                                                    </ul>
                                                </div>

                                                <!-- Modal Detail -->
                                                <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="detailModalLabel">Detail Data Penduduk</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="nik" class="form-label">NIK</label>
                                                                    <input type="text" class="form-control" id="nik" placeholder="Enter NIK">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="nama" class="form-label">Nama</label>
                                                                    <input type="text" class="form-control" id="nama" placeholder="Enter Nama">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="ttl" class="form-label">Tempat, Tanggal Lahir</label>
                                                                    <input type="text" class="form-control" id="ttl" placeholder="Enter Tempat, Tanggal Lahir">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="alamat" class="form-label">Alamat</label>
                                                                    <input type="text" class="form-control" id="alamat" placeholder="Enter Alamat">
                                                                </div>
                                                                <!-- Tambahkan lebih banyak isian sesuai kebutuhan -->
                                                            </div>
                                                            
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Modal Edit -->
                                                <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editModalLabel">Edit Data Penduduk</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form>
                                                                    <!-- Edit form fields similar to the Detail form -->
                                                                    <div class="mb-3">
                                                                        <label for="nikEdit" class="form-label">NIK</label>
                                                                        <input type="text" class="form-control" id="nikEdit" placeholder="Edit NIK">
                                                                    </div>
                                                                    <!-- Add more fields as needed -->
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