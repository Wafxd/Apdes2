<?php
$pageTitle = "Editor Surat Keterangan";
$pageHeaderButton = '<a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
    <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
</a>';

ob_start();
?>
<!-- CSS Kustom -->
<style>
    .card-surat {
        border: 2px solid #4e73df;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    .surat-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #4e73df;
        padding: 15px;
    }
    .surat-body {
        min-height: 500px;
        padding: 30px;
    }
    .surat-footer {
        background-color: #f8f9fa;
        border-top: 2px solid #4e73df;
        padding: 15px;
    }
    .toolbar {
        background: #f8f9fa;
        padding: 10px;
        border: 1px solid #ddd;
        margin-bottom: 10px;
    }
    .editor-container {
        border: 1px solid #ddd;
        min-height: 500px;
        padding: 20px;
        background: white;
    }
    .kop-surat {
        text-align: center;
        margin-bottom: 20px;
    }
    .ttd-area {
        margin-top: 100px;
    }
    .table-borderless td, .table-borderless th {
        border: none;
    }
    .underline {
        text-decoration: underline;
    }
    .text-right {
        text-align: right;
    }
    .text-center {
        text-align: center;
    }
    .font-weight-bold {
        font-weight: bold;
    }
</style>

<!-- Include libraries -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<div class="container-fluid">
    <!-- Form Pencarian dan Pemilihan Surat -->
    <div class="card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Buat Surat Keterangan</h6>
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nik">Masukkan NIK Penduduk</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="nik" name="nik" placeholder="Contoh: 3201234567890123" required>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="jenis_surat">Jenis Surat</label>
                            <select class="form-control" id="jenis_surat" name="jenis_surat" required>
                                <option value="">-- Pilih Jenis Surat --</option>
                                <option value="domisili">Surat Keterangan Domisili</option>
                                <option value="usaha">Surat Keterangan Usaha</option>
                                <option value="nikah_sirih">Surat Keterangan Nikah Sirih</option>
                                <option value="kehilangan">Surat Keterangan Kehilangan</option>
                                <option value="telah_menikah">Surat Keterangan Telah Menikah</option>
                                <option value="pernyataan">Surat Pernyataan</option>
                                <option value="kepemilikan_tanah">Surat Keterangan Kepemilikan Tanah</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($_POST['nik']) && isset($_POST['jenis_surat'])): ?>
    <!-- Data Penduduk -->
    <div class="card mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Data Penduduk</h6>
            <button class="btn btn-sm btn-success" id="isiOtomatis">
                <i class="fas fa-magic"></i> Isi Otomatis
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="row mb-2">
                        <div class="col-md-4 font-weight-bold">NIK</div>
                        <div class="col-md-8">3201234567890123</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 font-weight-bold">Nama</div>
                        <div class="col-md-8">JOHN DOE</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 font-weight-bold">TTL</div>
                        <div class="col-md-8">Jakarta, 01-01-1980</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row mb-2">
                        <div class="col-md-4 font-weight-bold">Alamat</div>
                        <div class="col-md-8">Jl. Merdeka No. 123, RT 001/RW 002</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 font-weight-bold">No. KK</div>
                        <div class="col-md-8">3201234567890124</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 font-weight-bold">Jenis Kelamin</div>
                        <div class="col-md-8">LAKI-LAKI</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Editor Surat -->
    <form id="formBuatSurat" action="proses_surat.php" method="POST">
        <div class="card card-surat">
            <div class="surat-header">
                <div class="form-group">
                    <label for="no_surat">Nomor Surat</label>
                    <input type="text" class="form-control" id="no_surat" name="no_surat" value="123/<?php echo strtoupper(str_replace('_', ' ', $_POST['jenis_surat'])); ?>/<?= date('m/Y') ?>">
                </div>
            </div>
            
            <div class="surat-body">
                <!-- Toolbar Editor -->
                <div class="toolbar">
                    <select class="form-control d-inline-block" id="font-size" style="width: auto;">
                        <option value="10px">10px</option>
                        <option value="12px" selected>12px</option>
                        <option value="14px">14px</option>
                        <option value="16px">16px</option>
                        <option value="18px">18px</option>
                        <option value="20px">20px</option>
                    </select>
                    
                    <button type="button" class="btn btn-sm btn-light" data-command="bold" title="Bold">
                        <i class="fas fa-bold"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" data-command="italic" title="Italic">
                        <i class="fas fa-italic"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" data-command="underline" title="Underline">
                        <i class="fas fa-underline"></i>
                    </button>
                    
                    <button type="button" class="btn btn-sm btn-light" data-command="insertUnorderedList" title="Bullet List">
                        <i class="fas fa-list-ul"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" data-command="insertOrderedList" title="Numbered List">
                        <i class="fas fa-list-ol"></i>
                    </button>
                    
                    <button type="button" class="btn btn-sm btn-light" data-command="justifyLeft" title="Align Left">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" data-command="justifyCenter" title="Center">
                        <i class="fas fa-align-center"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" data-command="justifyRight" title="Align Right">
                        <i class="fas fa-align-right"></i>
                    </button>
                </div>
                
                <!-- Editor Area -->
                <div class="editor-container" id="editor" contenteditable="true">
                    <?php
                    $jenis_surat = $_POST['jenis_surat'];
                    $tanggal_sekarang = date('d F Y');
                    $bulan_romawi = array("", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII");
                    $bulan_sekarang = $bulan_romawi[date('n')];
                    
                    switch($jenis_surat) {
                        case 'domisili':
                            echo '
                            <div class="kop-surat">
                                <h4 class="font-weight-bold">PEMERINTAH KABUPATEN BANGKALAN</h4>
                                <h4 class="font-weight-bold">KECAMATAN LABANG</h4>
                                <h4 class="font-weight-bold">KANTOR DESA SUKOLILO TIMUR</h4>
                                <p>Labang 69163</p>
                            </div>
                            
                            <div class="text-center">
                                <h4 class="font-weight-bold underline">SURAT KETERANGAN DOMISILI</h4>
                                <p>NO : <span id="nomor-surat-text">123/SKD/'.date('m/Y').'</span></p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Yang bertanda tangan di bawah ini :</p>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="30%">Nama</td>
                                        <td width="5%">:</td>
                                        <td>H. ZAINAL ABIDIN</td>
                                    </tr>
                                    <tr>
                                        <td>Jabatan</td>
                                        <td>:</td>
                                        <td>Kepala Desa Sukolilo Timur</td>
                                    </tr>
                                    <tr>
                                        <td>Kecamatan</td>
                                        <td>:</td>
                                        <td>Labang</td>
                                    </tr>
                                    <tr>
                                        <td>Kabupaten</td>
                                        <td>:</td>
                                        <td>Bangkalan</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="mb-4">
                                <p>Menerangkan bahwa nama-nama yang tersebut dibawah ini :</p>
                                <table class="table" style="width:100%; border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <th style="border:1px solid #000; padding:5px; text-align:center;">NO</th>
                                            <th style="border:1px solid #000; padding:5px; text-align:center;">NAMA</th>
                                            <th style="border:1px solid #000; padding:5px; text-align:center;">ALAMAT</th>
                                            <th style="border:1px solid #000; padding:5px; text-align:center;">KET</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="border:1px solid #000; padding:5px; text-align:center;">1.</td>
                                            <td style="border:1px solid #000; padding:5px;">JOHN DOE</td>
                                            <td style="border:1px solid #000; padding:5px;">Jl. Merdeka No. 123</td>
                                            <td style="border:1px solid #000; padding:5px;">KAYA (MAMPU)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mb-4">
                                <p>Demikian surat keterangan ini kami buat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
                            </div>
                            
                            <div class="ttd-area">
                                <div class="row">
                                    <div class="col-md-8"></div>
                                    <div class="col-md-4 text-center">
                                        <p>Sukolilo Timur, '.$tanggal_sekarang.'</p>
                                        <p>Kepala Desa Sukolilo Timur</p>
                                        <br><br><br>
                                        <p class="font-weight-bold underline">H. ZAINAL ABIDIN</p>
                                    </div>
                                </div>
                            </div>';
                            break;
                            
                        case 'usaha':
                            echo '
                            <div class="kop-surat">
                                <h4 class="font-weight-bold">PEMERINTAH KABUPATEN BANGKALAN</h4>
                                <h4 class="font-weight-bold">KECAMATAN LABANG</h4>
                                <h4 class="font-weight-bold">KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                                <p>Labang 69163</p>
                            </div>
                            
                            <div class="text-center">
                                <h4 class="font-weight-bold underline">SURAT KETERANGAN MEMILIKI USAHA</h4>
                                <p>Nomor: <span id="nomor-surat-text">123/SKU/'.date('m/Y').'</span></p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Yang bertanda tangan di bawah ini kami Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan, menerangkan bahwa:</p>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="30%">Nama</td>
                                        <td width="5%">:</td>
                                        <td>JOHN DOE</td>
                                    </tr>
                                    <tr>
                                        <td>NIK</td>
                                        <td>:</td>
                                        <td>3201234567890123</td>
                                    </tr>
                                    <tr>
                                        <td>Tempat, Tgl Lahir</td>
                                        <td>:</td>
                                        <td>Jakarta, 01-01-1980</td>
                                    </tr>
                                    <tr>
                                        <td>Jenis Kelamin</td>
                                        <td>:</td>
                                        <td>Laki-laki</td>
                                    </tr>
                                    <tr>
                                        <td>Agama</td>
                                        <td>:</td>
                                        <td>Islam</td>
                                    </tr>
                                    <tr>
                                        <td>Status</td>
                                        <td>:</td>
                                        <td>Kawin</td>
                                    </tr>
                                    <tr>
                                        <td>Pekerjaan</td>
                                        <td>:</td>
                                        <td>Wiraswasta</td>
                                    </tr>
                                    <tr>
                                        <td>Alamat</td>
                                        <td>:</td>
                                        <td>Jl. Merdeka No. 123, RT 001/RW 002</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="mb-4">
                                <p>Orang tersebut diatas benar-benar mempunyai usaha Toko Klontong dan Bengkel Motor yang berlokasi di Jl. Merdeka No. 123, RT 001/RW 002.</p>
                                <p>Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat Mengajukan Pinjaman Modal di Bank BRI.</p>
                            </div>
                            
                            <div class="ttd-area">
                                <div class="row">
                                    <div class="col-md-8"></div>
                                    <div class="col-md-4 text-center">
                                        <p>Sukolilo Timur, '.$tanggal_sekarang.'</p>
                                        <p>Plh. Kepala Desa Sukolilo Timur</p>
                                        <br><br><br>
                                        <p class="font-weight-bold underline">H. ZAINAL ABIDIN</p>
                                    </div>
                                </div>
                            </div>';
                            break;
                            
                        case 'nikah_sirih':
                            echo '
                            <div class="kop-surat">
                                <h4 class="font-weight-bold">PEMERINTAH KABUPATEN BANGKALAN</h4>
                                <h4 class="font-weight-bold">KECAMATAN LABANG</h4>
                                <h4 class="font-weight-bold">KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                            </div>
                            
                            <div class="text-center">
                                <h4 class="font-weight-bold underline">SURAT KETERANGAN NIKAH SIRIH</h4>
                                <p>NO : <span id="nomor-surat-text">123/SKNS/'.date('m/Y').'</span></p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Yang bertanda tangan di bawah ini :</p>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="30%">Nama</td>
                                        <td width="5%">:</td>
                                        <td>H. ZAINAL ABIDIN</td>
                                    </tr>
                                    <tr>
                                        <td>Jabatan</td>
                                        <td>:</td>
                                        <td>Kepala Desa / Lurah</td>
                                    </tr>
                                    <tr>
                                        <td>Kecamatan</td>
                                        <td>:</td>
                                        <td>Labang</td>
                                    </tr>
                                    <tr>
                                        <td>Kabupaten</td>
                                        <td>:</td>
                                        <td>Bangkalan</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="mb-4">
                                <p>Menerangkan dengan sebenar benarnya bahwa nama <span class="font-weight-bold">JOHN DOE</span> dan <span class="font-weight-bold">JANE DOE</span> telah melakukan pernikahan di Desa kami pada tanggal <span class="font-weight-bold">'.$tanggal_sekarang.'</span> dan disaksikan oleh 2 (dua) orang saksi :</p>
                                <ol>
                                    <li>SAKSI PERTAMA (Alamat)</li>
                                    <li>SAKSI KEDUA (Alamat)</li>
                                </ol>
                            </div>
                            
                            <div class="mb-4">
                                <p>Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
                            </div>
                            
                            <div class="ttd-area">
                                <div class="row">
                                    <div class="col-md-8"></div>
                                    <div class="col-md-4 text-center">
                                        <p>Sukolilo Timur, '.$tanggal_sekarang.'</p>
                                        <p>Pjs. KEPALA DESA SUKOLILO TIMUR</p>
                                        <br><br><br>
                                        <p class="font-weight-bold underline">NASIR RIFA\'I</p>
                                    </div>
                                </div>
                            </div>';
                            break;
                            
                        case 'kehilangan':
                            echo '
                            <div class="kop-surat">
                                <h4 class="font-weight-bold">PEMERINTAH KABUPATEN BANGKALAN</h4>
                                <h4 class="font-weight-bold">KECAMATAN LABANG</h4>
                                <h4 class="font-weight-bold">KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                                <p>Labang 69163</p>
                            </div>
                            
                            <div class="text-center">
                                <h4 class="font-weight-bold underline">SURAT KETERANGAN KEHILANGAN</h4>
                                <p>NO : <span id="nomor-surat-text">123/SKK/'.date('m/Y').'</span></p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Yang bertanda tangan dibawah ini. Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan Menerangkan dengan sebenarnya bahwa:</p>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="30%">Nama</td>
                                        <td width="5%">:</td>
                                        <td>JOHN DOE</td>
                                    </tr>
                                    <tr>
                                        <td>Tempat / Tgl Lahir</td>
                                        <td>:</td>
                                        <td>Jakarta, 01-01-1980</td>
                                    </tr>
                                    <tr>
                                        <td>NIK</td>
                                        <td>:</td>
                                        <td>3201234567890123</td>
                                    </tr>
                                    <tr>
                                        <td>Jenis Kelamin</td>
                                        <td>:</td>
                                        <td>Laki-Laki</td>
                                    </tr>
                                    <tr>
                                        <td>Agama</td>
                                        <td>:</td>
                                        <td>Islam</td>
                                    </tr>
                                    <tr>
                                        <td>Pekerjaan</td>
                                        <td>:</td>
                                        <td>Karyawan Swasta</td>
                                    </tr>
                                    <tr>
                                        <td>Alamat</td>
                                        <td>:</td>
                                        <td>Jl. Merdeka No. 123, RT 001/RW 002</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="mb-4">
                                <p>Menerangkan bahwa orang tersebut diatas pada hari '.date('l').' tanggal '.date('d').' '.date('F').' sekitar pukul 9 pagi telah kehilangan Dompet yang berisi Dokumen :</p>
                                <ol>
                                    <li>SIM A</li>
                                    <li>e-KTP</li>
                                    <li>ATM</li>
                                </ol>
                                <p>Diantara Jalan Sukolilo Sampai Jalan Pasar Labang.</p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Demikian surat keterangan Kehilangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
                            </div>
                            
                            <div class="ttd-area">
                                <div class="row">
                                    <div class="col-md-8"></div>
                                    <div class="col-md-4 text-center">
                                        <p>Sukolilo Timur, '.$tanggal_sekarang.'</p>
                                        <p>Kepala Desa Sukolilo Timur</p>
                                        <br><br><br>
                                        <p class="font-weight-bold underline">H. ZAINAL ABIDIN</p>
                                    </div>
                                </div>
                            </div>';
                            break;
                            
                        case 'telah_menikah':
                            echo '
                            <div class="kop-surat">
                                <h4 class="font-weight-bold">PEMERINTAH KABUPATEN BANGKALAN</h4>
                                <h4 class="font-weight-bold">KECAMATAN LABANG</h4>
                                <h4 class="font-weight-bold">KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                                <p>Labang 69163</p>
                            </div>
                            
                            <div class="text-center">
                                <h4 class="font-weight-bold">SURAT KETERANGAN TELAH MENIKAH</h4>
                                <h4 class="font-weight-bold">SECARA AGAMA ISLAM (SIRIH)</h4>
                                <p>Reg. No : <span id="nomor-surat-text">123/SKTM/'.date('m/Y').'</span></p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Yang bertanda tangan dibawah ini. Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan Menerangkan dengan sebenarnya bahwa:</p>
                                
                                <p>1. N a m a : JOHN DOE</p>
                                <p>Tempat / Tgl Lahir : Jakarta, 01-01-1980</p>
                                <p>Jenis Kelamin : Laki-laki</p>
                                <p>Pekerjaan : Karyawan Swasta</p>
                                <p>Alamat : Jl. Merdeka No. 123, RT 001/RW 002</p>
                                
                                <p>2. N a m a : JANE DOE</p>
                                <p>Tempat / Tgl Lahir : Surabaya, 15-05-1985</p>
                                <p>Jenis Kelamin : Perempuan</p>
                                <p>Pekerjaan : Ibu Rumah Tangga</p>
                                <p>Alamat : Jl. Merdeka No. 123, RT 001/RW 002</p>
                                
                                <p>Disaksikan oleh 2 (dua) orang saksi, Kedua nama tersebut di atas telah melaksanakan Akad Nikah secara Agama Islam (Sirih) pada :</p>
                                <p>Hari : '.date('l').'</p>
                                <p>Tanggal : '.$tanggal_sekarang.'</p>
                                <p>Tempat : Jl. Merdeka No. 123, RT 001/RW 002</p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Demikian surat Pernyataan ini kami buat dengan sebenarnya.</p>
                                <p class="font-weight-bold">Saksi-saksi</p>
                                <ol>
                                    <li>SAKSI PERTAMA (.................................)</li>
                                    <li>SAKSI KEDUA (.................................)</li>
                                </ol>
                            </div>
                            
                            <div class="ttd-area">
                                <div class="row">
                                    <div class="col-md-8"></div>
                                    <div class="col-md-4 text-center">
                                        <p>Sukolilo Timur, '.$tanggal_sekarang.'</p>
                                        <p>Kepala Desa Sukolilo Timur</p>
                                        <br><br><br>
                                        <p class="font-weight-bold underline">H. ZAINAL ABIDIN</p>
                                    </div>
                                </div>
                            </div>';
                            break;
                            
                        case 'pernyataan':
                            echo '
                            <div class="kop-surat">
                                <h4 class="font-weight-bold">PEMERINTAH KABUPATEN BANGKALAN</h4>
                                <h4 class="font-weight-bold">KECAMATAN LABANG</h4>
                                <h4 class="font-weight-bold">KANTOR KEPALA DESA SUKOLILO TIMUR</h4>
                                <p>Labang 69163</p>
                            </div>
                            
                            <div class="text-center">
                                <h4 class="font-weight-bold underline">SURAT PERNYATAAN</h4>
                                <p>NO : <span id="nomor-surat-text">123/SP/'.date('m/Y').'</span></p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Yang bertanda tangan di bawah ini Kepala Desa Sukolilo Timur Kecamatan Labang Kabupaten Bangkalan menyatakan bahwa :</p>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="30%">Nama</td>
                                        <td width="5%">:</td>
                                        <td>JOHN DOE</td>
                                    </tr>
                                    <tr>
                                        <td>Tempat/Tgl Lahir</td>
                                        <td>:</td>
                                        <td>Jakarta, 01-01-1980</td>
                                    </tr>
                                    <tr>
                                        <td>Jenis Kelamin</td>
                                        <td>:</td>
                                        <td>Laki-laki</td>
                                    </tr>
                                    <tr>
                                        <td>Status</td>
                                        <td>:</td>
                                        <td>Kawin</td>
                                    </tr>
                                    <tr>
                                        <td>Pekerjaan</td>
                                        <td>:</td>
                                        <td>Wiraswasta</td>
                                    </tr>
                                    <tr>
                                        <td>Alamat</td>
                                        <td>:</td>
                                        <td>Jl. Merdeka No. 123, RT 001/RW 002</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="mb-4">
                                <p>Pada hari '.date('l').', tanggal '.$tanggal_sekarang.' kira-kira sekitar pukul 10.00 Wib, dirumah Bapak SYAIROJI alamat Jl. Merdeka No. 123, RT 001/RW 002 telah terjadi peristiwa Tindak Pidana: pencurian 1 (satu) unit Sepeda Motor Merk HONDA.</p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Demikian Surat Pernyataan ini saya buat dengan sesungguhnya dan sebenar benarnya untuk dapat digunakan sebagaimana mestinya. Apabila dikemudian hari pernyataan ini ternyata tidak benar, saya bersedia dituntut dimuka pengadilan.</p>
                            </div>
                            
                            <div class="ttd-area">
                                <div class="row">
                                    <div class="col-md-6 text-center">
                                        <p>Yang bersangkutan</p>
                                        <br><br><br>
                                        <p class="font-weight-bold underline">JOHN DOE</p>
                                    </div>
                                    <div class="col-md-6 text-center">
                                        <p>Sukolilo Timur, '.$tanggal_sekarang.'</p>
                                        <p>Kepala Desa Sukolilo Timur</p>
                                        <br><br><br>
                                        <p class="font-weight-bold underline">H. ZAINAL ABIDIN</p>
                                    </div>
                                </div>
                            </div>';
                            break;
                            
                        case 'kepemilikan_tanah':
                            echo '
                            <div class="kop-surat">
                                <h4 class="font-weight-bold">PEMERINTAH KABUPATEN BANGKALAN</h4>
                                <h4 class="font-weight-bold">KECAMATAN LABANG</h4>
                                <h4 class="font-weight-bold">KANTOR KEPALA DESA BRINGEN</h4>
                            </div>
                            
                            <div class="text-center">
                                <h4 class="font-weight-bold underline">SURAT KETERANGAN KEPEMILIKAN TANAH</h4>
                                <p>NO : <span id="nomor-surat-text">123/SKKT/'.date('m/Y').'</span></p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Yang bertanda tangan di bawah ini Kepala Desa Bringen Kecamatan Labang Kabupaten Bangkalan, menerangkan :</p>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="30%">N a m a</td>
                                        <td width="5%">:</td>
                                        <td>JOHN DOE</td>
                                    </tr>
                                    <tr>
                                        <td>Tempat / Tgl Lahir</td>
                                        <td>:</td>
                                        <td>Jakarta, 01-01-1980</td>
                                    </tr>
                                    <tr>
                                        <td>Jenis Kelamin</td>
                                        <td>:</td>
                                        <td>Laki-laki</td>
                                    </tr>
                                    <tr>
                                        <td>Kewarganegaraaan</td>
                                        <td>:</td>
                                        <td>Indonesia</td>
                                    </tr>
                                    <tr>
                                        <td>Agama</td>
                                        <td>:</td>
                                        <td>Islam</td>
                                    </tr>
                                    <tr>
                                        <td>Pekerjaan</td>
                                        <td>:</td>
                                        <td>Wiraswasta</td>
                                    </tr>
                                    <tr>
                                        <td>Alamat</td>
                                        <td>:</td>
                                        <td>Jl. Merdeka No. 123, RT 001/RW 002</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="mb-4">
                                <p>Orang tersebut benar-benar penduduk Desa Bringen Kecamatan Labang Kabupaten Bangkalan, dan yang bersangkutan benar-benar memiliki sebidang tanah yang terletak di Desa Bringen dengan Luas 500 M², No. SPPT 1234567890, Persil : 123 Kelas, A.</p>
                            </div>
                            
                            <div class="mb-4">
                                <p>Demikian surat keterangan ini kami buat dengan sebenarnya untuk dapat dipergunakan sebagai mana mestinya.</p>
                            </div>
                            
                            <div class="ttd-area">
                                <div class="row">
                                    <div class="col-md-8"></div>
                                    <div class="col-md-4 text-center">
                                        <p>Bringen, '.$tanggal_sekarang.'</p>
                                        <p>Kepala Desa Bringen</p>
                                        <br><br><br>
                                        <p class="font-weight-bold underline">H. MOH. ALI</p>
                                    </div>
                                </div>
                            </div>';
                            break;
                    }
                    ?>
                </div>
                
                <!-- Hidden input untuk menyimpan konten HTML -->
                <input type="hidden" id="suratContent" name="suratContent">
                <input type="hidden" name="jenis_surat" value="<?php echo $jenis_surat; ?>">
            </div>
        </div>
        
        <!-- Tombol Aksi -->
        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <button type="submit" class="btn btn-primary mr-2" name="action" value="cetak">
                    <i class="fas fa-print"></i> Cetak Surat
                </button>
                <button type="submit" class="btn btn-success mr-2" name="action" value="pdf">
                    <i class="fas fa-download"></i> Download PDF
                </button>
                <button type="submit" class="btn btn-info mr-2" name="action" value="word">
                    <i class="fas fa-file-word"></i> Download Word
                </button>
                <a href="surat.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Buat Baru
                </a>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<!-- Script untuk Editor -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update nomor surat text ketika input berubah
    document.getElementById('no_surat').addEventListener('input', function() {
        document.getElementById('nomor-surat-text').textContent = this.value;
    });
    
    // Fungsi isi otomatis
    document.getElementById('isiOtomatis').addEventListener('click', function() {
        // Ini hanya contoh, bisa disesuaikan dengan data dari database
        const editor = document.getElementById('editor');
        // ... logika pengisian otomatis ...
        alert('Data telah diisi otomatis!');
    });
    
    // Simpan konten editor sebelum submit
    document.getElementById('formBuatSurat').addEventListener('submit', function() {
        document.getElementById('suratContent').value = document.getElementById('editor').innerHTML;
    });
    
    // Fungsi toolbar sederhana
    document.querySelectorAll('.toolbar button[data-command]').forEach(button => {
        button.addEventListener('click', function() {
            const command = this.getAttribute('data-command');
            document.execCommand(command, false, null);
        });
    });
    
    // Fungsi font size
    document.getElementById('font-size').addEventListener('change', function() {
        document.execCommand('fontSize', false, this.value);
    });
});
</script>

<?php
$content = ob_get_clean();
include 'template1/base.php';
?>