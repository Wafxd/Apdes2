<?php
$pageTitle = "Surat Menyurat - Arsip Surat Keluar";
$pageHeaderButton = '<a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
</a>';

ob_start();
?>
<!-- Content Row -->
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Arsip Surat Keluar</h1>
    <p class="mb-4">Daftar surat-surat yang telah dikeluarkan oleh kelurahan.</p>

    <!-- Filter Section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Filter Surat</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
            </div>
        </div>
        <div class="card-body">
            <form>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="jenisSurat">Jenis Surat</label>
                            <select class="form-control" id="jenisSurat">
                                <option value="">Semua Jenis Surat</option>
                                <option value="SKTM">Surat Keterangan Tidak Mampu (SKTM)</option>
                                <option value="SKU">Surat Keterangan Usaha (SKU)</option>
                                <option value="SKD">Surat Keterangan Domisili (SKD)</option>
                                <option value="SKBM">Surat Keterangan Belum Menikah</option>
                                <option value="SKK">Surat Keterangan Kematian</option>
                                <option value="SKP">Surat Keterangan Penghasilan</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="bulan">Bulan</label>
                            <select class="form-control" id="bulan">
                                <option value="">Semua Bulan</option>
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="tahun">Tahun</label>
                            <select class="form-control" id="tahun">
                                <option value="">Semua Tahun</option>
                                <option value="2023">2023</option>
                                <option value="2024">2024</option>
                                <option value="2025" selected>2025</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="pencarian">Pencarian</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="pencarian" placeholder="Cari...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Surat Keluar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">1,248</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Surat Bulan Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">48</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                SKTM (Tahun Ini)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">215</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Surat Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">3</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Surat Keluar</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Ekspor Data:</div>
                    <a class="dropdown-item" href="#"><i class="fas fa-file-excel text-success mr-2"></i>Excel</a>
                    <a class="dropdown-item" href="#"><i class="fas fa-file-pdf text-danger mr-2"></i>PDF</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#"><i class="fas fa-print text-primary mr-2"></i>Cetak</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th>Nomor Surat</th>
                            <th>Jenis Surat</th>
                            <th>Nama Pemohon</th>
                            <th>NIK</th>
                            <th>Keperluan</th>
                            <th>Petugas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th>Nomor Surat</th>
                            <th>Jenis Surat</th>
                            <th>Nama Pemohon</th>
                            <th>NIK</th>
                            <th>Keperluan</th>
                            <th>Petugas</th>
                            <th>Aksi</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>10/08/2025</td>
                            <td>140/08-SKTM/2025</td>
                            <td>SKTM</td>
                            <td>Budi Santoso</td>
                            <td>220411100001</td>
                            <td>Pendaftaran Sekolah</td>
                            <td>Agus Setiawan</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="#"><i class="fas fa-eye text-primary mr-2"></i>Lihat</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-print text-info mr-2"></i>Cetak Ulang</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-download text-success mr-2"></i>Unduh</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><i class="fas fa-trash text-danger mr-2"></i>Hapus</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>09/08/2025</td>
                            <td>139/08-SKU/2025</td>
                            <td>SKU</td>
                            <td>Siti Aminah</td>
                            <td>220411100002</td>
                            <td>Perpanjangan Izin Usaha</td>
                            <td>Agus Setiawan</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="#"><i class="fas fa-eye text-primary mr-2"></i>Lihat</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-print text-info mr-2"></i>Cetak Ulang</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-download text-success mr-2"></i>Unduh</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><i class="fas fa-trash text-danger mr-2"></i>Hapus</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>08/08/2025</td>
                            <td>138/08-SKD/2025</td>
                            <td>SKD</td>
                            <td>Andi Wijaya</td>
                            <td>220411100003</td>
                            <td>Pembuatan Rekening Bank</td>
                            <td>Dewi Lestari</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="#"><i class="fas fa-eye text-primary mr-2"></i>Lihat</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-print text-info mr-2"></i>Cetak Ulang</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-download text-success mr-2"></i>Unduh</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><i class="fas fa-trash text-danger mr-2"></i>Hapus</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>05/08/2025</td>
                            <td>137/08-SKBM/2025</td>
                            <td>SKBM</td>
                            <td>Rina Fitriani</td>
                            <td>220411100004</td>
                            <td>Persyaratan Melamar Kerja</td>
                            <td>Dewi Lestari</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="#"><i class="fas fa-eye text-primary mr-2"></i>Lihat</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-print text-info mr-2"></i>Cetak Ulang</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-download text-success mr-2"></i>Unduh</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><i class="fas fa-trash text-danger mr-2"></i>Hapus</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>03/08/2025</td>
                            <td>136/08-SKK/2025</td>
                            <td>SKK</td>
                            <td>Herman Susanto</td>
                            <td>220411100005</td>
                            <td>Klaim Asuransi</td>
                            <td>Agus Setiawan</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="#"><i class="fas fa-eye text-primary mr-2"></i>Lihat</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-print text-info mr-2"></i>Cetak Ulang</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-download text-success mr-2"></i>Unduh</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><i class="fas fa-trash text-danger mr-2"></i>Hapus</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td>01/08/2025</td>
                            <td>135/08-SKP/2025</td>
                            <td>SKP</td>
                            <td>Fajar Nugraha</td>
                            <td>220411100006</td>
                            <td>Pengajuan Kredit</td>
                            <td>Dewi Lestari</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="#"><i class="fas fa-eye text-primary mr-2"></i>Lihat</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-print text-info mr-2"></i>Cetak Ulang</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-download text-success mr-2"></i>Unduh</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><i class="fas fa-trash text-danger mr-2"></i>Hapus</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td>28/07/2025</td>
                            <td>134/07-SKTM/2025</td>
                            <td>SKTM</td>
                            <td>Linda Permata</td>
                            <td>220411100007</td>
                            <td>Beasiswa Pendidikan</td>
                            <td>Agus Setiawan</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="#"><i class="fas fa-eye text-primary mr-2"></i>Lihat</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-print text-info mr-2"></i>Cetak Ulang</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-download text-success mr-2"></i>Unduh</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><i class="fas fa-trash text-danger mr-2"></i>Hapus</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td>25/07/2025</td>
                            <td>133/07-SKU/2025</td>
                            <td>SKU</td>
                            <td>Rudi Hartono</td>
                            <td>220411100008</td>
                            <td>Pembuatan NPWP</td>
                            <td>Dewi Lestari</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="#"><i class="fas fa-eye text-primary mr-2"></i>Lihat</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-print text-info mr-2"></i>Cetak Ulang</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-download text-success mr-2"></i>Unduh</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><i class="fas fa-trash text-danger mr-2"></i>Hapus</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php
$content = ob_get_clean();
include 'template1/base.php';
?>