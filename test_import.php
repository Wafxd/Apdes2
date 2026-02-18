<?php
session_start();
include "db/funct.php";
include "db/koneksi.php";

echo "<h2>TEST FUNGSI IMPORT</h2>";

// 1. CEK KONEKSI DATABASE
echo "<h3>1. Cek Koneksi Database</h3>";
if ($conn) {
    echo "✅ Koneksi database berhasil<br>";
} else {
    echo "❌ Koneksi database gagal: " . mysqli_connect_error() . "<br>";
    exit();
}

// 2. CEK TABEL penduduk
echo "<h3>2. Cek Tabel penduduk</h3>";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'penduduk'");
if (mysqli_num_rows($result) > 0) {
    echo "✅ Tabel penduduk ditemukan<br>";
    
    // Tampilkan struktur
    $result = mysqli_query($conn, "DESCRIBE penduduk");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Tabel penduduk TIDAK ditemukan!<br>";
    exit();
}

// 3. TEST FUNGSI is_nik_exist
echo "<h3>3. Test Fungsi is_nik_exist()</h3>";
$test_nik = "3273010101010001";
if (function_exists('is_nik_exist')) {
    echo "✅ Fungsi is_nik_exist() ada<br>";
    $exists = is_nik_exist($test_nik);
    echo "NIK $test_nik: " . ($exists ? "SUDAH ADA" : "BELUM ADA") . "<br>";
} else {
    echo "❌ Fungsi is_nik_exist() TIDAK ADA!<br>";
}

// 4. TEST FUNGSI add_penduduk
echo "<h3>4. Test Fungsi add_penduduk()</h3>";
if (function_exists('add_penduduk')) {
    echo "✅ Fungsi add_penduduk() ada<br>";
    
    // Data test
    $test_data = [
        'nik' => '9999999999999999',
        'nama_penduduk' => 'TEST FUNGSI',
        'nama_ayah' => 'AYAH TEST',
        'nama_ibu' => 'IBU TEST',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'LAKI-LAKI',
        'agama' => 'ISLAM',
        'pendidikan' => 'S1',
        'pekerjaan' => 'TEST',
        'status_kawin' => 'Belum Kawin',
        'alamat' => 'Alamat Test',
        'rt_rw' => '001/002',
        'kel_des' => 'Desa Test',
        'kecamatan' => 'Kec Test',
        'kabupaten_kota' => 'Kota Test',
        'provinsi' => 'Prov Test',
        'kodepos' => '12345'
    ];
    
    $result = add_penduduk($test_data);
    
    if ($result > 0) {
        echo "✅ Data test BERHASIL ditambahkan (affected rows: $result)<br>";
        
        // Hapus data test
        mysqli_query($conn, "DELETE FROM penduduk WHERE nik = '9999999999999999'");
        echo "✅ Data test berhasil dihapus<br>";
    } elseif ($result == -1) {
        echo "⚠️ Data test gagal: NIK sudah ada<br>";
    } else {
        echo "❌ Data test GAGAL ditambahkan (result: $result)<br>";
        
        // Tampilkan error MySQL
        echo "Error MySQL: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "❌ Fungsi add_penduduk() TIDAK ADA!<br>";
}

// 5. TEST BACA FILE CSV YANG DIUPLOAD
echo "<h3>5. Test Baca File CSV</h3>";

// Cek apakah ada file yang diupload
if (isset($_FILES['test_file'])) {
    $file = $_FILES['test_file']['tmp_name'];
    $content = file_get_contents($file);
    
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
    
    $lines = explode("\n", $content);
    echo "Jumlah baris: " . count($lines) . "<br>";
    
    $row_number = 0;
    foreach ($lines as $line) {
        $row_number++;
        $line = trim($line);
        if (empty($line)) continue;
        
        $row = str_getcsv($line);
        echo "Baris $row_number: " . print_r($row, true) . "<br>";
    }
} else {
    echo "Upload file CSV untuk test:<br>";
    echo '<form method="POST" enctype="multipart/form-data">';
    echo '<input type="file" name="test_file" accept=".csv">';
    echo '<input type="submit" value="Test Upload">';
    echo '</form>';
}

echo "<h3>6. Test Import Manual</h3>";
echo "Buat file CSV dengan format:<br>";
echo "<pre>
NIK,Nama Lengkap,Nama Ayah,Nama Ibu,Tempat Lahir,Tanggal Lahir,Jenis Kelamin,Agama,Pendidikan,Pekerjaan,Status Kawin,Alamat,RT/RW,Desa/Kel,Kecamatan,Kabupaten/Kota,Provinsi,Kode Pos
3273010101010001,AHMAD FAUZI,BUDI SANTOSO,SITI AMINAH,Jakarta,1990-05-15,LAKI-LAKI,ISLAM,S1,PNS,Kawin,Jl. Merdeka No. 123,001/002,Sukolilo Timur,Sukolilo,Bangkalan,Jawa Timur,69162
</pre>";
?>