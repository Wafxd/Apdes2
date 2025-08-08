<?php
$pageTitle = "Dashboard";
$pageHeaderButton = '<a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
</a>';

ob_start();
?>
<!-- Content Row -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <a href="#" class="btn btn-primary btn-icon-split">
                        <span class="icon text-white-50">
                            <i class="fas fa-flag"></i>
                        </span>
                        <span class="text">+ Tambah Penduduk</span>
                    </a>
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
                                                <a href="#" class="btn btn-info btn-icon-split">
                                                    <span class="icon text-white-50">
                                                        <i class="fas fa-info-circle fa-sm"></i>
                                                    </span>
                                                </a>
                                                <a href="#" class="btn btn-danger btn-icon-split">
                                                    <span class="icon text-white-50">
                                                        <i class="fas fa-trash fa-sm"></i>
                                                    </span>
                                                </a>
                                                <a href="#" class="btn btn-light btn-icon-split">
                                                    <span class="icon text-gray-600">
                                                        <i class="fas fa-edit fa-sm"></i>
                                                    </span>
                                                </a>
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