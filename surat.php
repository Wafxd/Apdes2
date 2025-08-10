<?php
$pageTitle = "Editor Surat Domisili";
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
</style>

<!-- Include libraries -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<div class="container-fluid">
    <!-- Form Pencarian -->
    <div class="card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Cari Data Penduduk</h6>
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-8">
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
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($_POST['nik'])): ?>
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
                    <input type="text" class="form-control" id="no_surat" name="no_surat" value="123/SKD/<?= date('m/Y') ?>">
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
                    <div class="kop-surat">
                        <h4 class="font-weight-bold">PEMERINTAH KABUPATEN CONTOH</h4>
                        <h4 class="font-weight-bold">KECAMATAN SAMPEL</h4>
                        <h4 class="font-weight-bold">DESA CONTOH</h4>
                        <p>Alamat: Jl. Desa Contoh No. 1, Telp. (021) 123456</p>
                    </div>
                    
                    <div class="text-center">
                        <h4 class="font-weight-bold">SURAT KETERANGAN DOMISILI</h4>
                        <p>Nomor: <span id="nomor-surat-text">123/SKD/<?= date('m/Y') ?></span></p>
                    </div>
                    
                    <div class="text-right mb-4">
                        <p>Contoh, <?= date('d F Y') ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <p>Yang bertanda tangan di bawah ini:</p>
                        <table class="table table-borderless">
                            <tr>
                                <td width="30%">Nama</td>
                                <td width="5%">:</td>
                                <td>JOHN DOE</td>
                            </tr>
                            <tr>
                                <td>Jabatan</td>
                                <td>:</td>
                                <td>Kepala Desa Contoh</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="mb-4">
                        <p>Menerangkan dengan sebenarnya bahwa:</p>
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
                                <td>Tempat/Tgl Lahir</td>
                                <td>:</td>
                                <td>Jakarta, 01-01-1980</td>
                            </tr>
                            <tr>
                                <td>Alamat</td>
                                <td>:</td>
                                <td>Jl. Merdeka No. 123, RT 001/RW 002</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="mb-4">
                        <p>Benar bahwa yang bersangkutan adalah penduduk yang berdomisili di wilayah Desa Contoh, Kecamatan Sampel, Kabupaten Contoh.</p>
                        <p>Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
                    </div>
                    
                    <div class="ttd-area">
                        <div class="row">
                            <div class="col-md-8"></div>
                            <div class="col-md-4 text-center">
                                <p>Kepala Desa Contoh</p>
                                <br><br><br>
                                <p class="font-weight-bold">JOHN DOE</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden input untuk menyimpan konten HTML -->
                <input type="hidden" id="suratContent" name="suratContent">
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
                <a href="surat_domisili.php" class="btn btn-secondary">
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