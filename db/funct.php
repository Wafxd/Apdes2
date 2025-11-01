<?php

include "koneksi.php";

function query($query){
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}



function add_penduduk($data) {
    global $conn;

    // Ambil semua data dari $data (yaitu $_POST)
    $nik = $data["nik"];
    $nama_penduduk = $data["nama_penduduk"];
    $tempat_lahir = $data["tempat_lahir"];
    $tanggal_lahir = $data["tanggal_lahir"];
    $jenis_kelamin = $data["jenis_kelamin"];
    $agama = $data["agama"];
    $pendidikan = $data["pendidikan"];
    $pekerjaan = $data["pekerjaan"];
    $status_kawin = $data["status_kawin"];
    $alamat = $data["alamat"];
    
    // Gunakan nama yang sesuai dengan 'name' di form
    $rt_rw = $data["rt/rw"];
    $kel_des = $data["kel/des"];
    $kecamatan = $data["kecamatan"];
    $kabupaten_kota = $data["kabupaten/kota"];
    $kodepos = $data["kodepos"];
    $provinsi = $data["provinsi"];


    // Query SQL yang lengkap - PERHATIKAN PENGGUNAAN BACKTICK (`)
    // Backtick (`) wajib untuk nama kolom yang mengandung spasi atau karakter /
    $query = "INSERT INTO penduduk (
                nik, nama_penduduk, tempat_lahir, `tanggal_lahir`, jenis_kelamin, agama, 
                pendidikan, pekerjaan, status_kawin, alamat, `rt/rw`, `kel/des`, 
                kecamatan, `kabupaten/kota`, kodepos, provinsi
            ) 
            VALUES (
                '$nik', '$nama_penduduk', '$tempat_lahir', '$tanggal_lahir', '$jenis_kelamin', '$agama',
                '$pendidikan', '$pekerjaan', '$status_kawin', '$alamat', '$rt_rw', '$kel_des',
                '$kecamatan', '$kabupaten_kota', '$kodepos', '$provinsi'
            )";

    mysqli_query($conn, $query);
    
    // Cek apakah ada error
    if(mysqli_error($conn)) {
        // Jika ada error, tampilkan (ini bagus untuk debugging)
        echo "Error: " . mysqli_error($conn);
        return 0; // Kembalikan 0 jika gagal
    }

    return mysqli_affected_rows($conn);
}


function edit_penduduk($data) {
    global $conn;

    // Ambil semua data dari $data (yaitu $_POST)
    // NIK adalah kunci utama untuk klausa WHERE
    $nik = $data["nik"]; 
    
    // Data yang akan di-update
    $nama_penduduk = $data["nama_penduduk"];
    $tempat_lahir = $data["tempat_lahir"];
    $tanggal_lahir = $data["tanggal_lahir"];
    $jenis_kelamin = $data["jenis_kelamin"];
    $agama = $data["agama"];
    $pendidikan = $data["pendidikan"];
    $pekerjaan = $data["pekerjaan"];
    $status_kawin = $data["status_kawin"];
    $alamat = $data["alamat"];
    $rt_rw = $data["rt/rw"];
    $kel_des = $data["kel/des"];
    $kecamatan = $data["kecamatan"];
    $kabupaten_kota = $data["kabupaten/kota"];
    $kodepos = $data["kodepos"];
    $provinsi = $data["provinsi"];

    // Query UPDATE
    // Gunakan backtick (`) untuk nama kolom yang non-standar
    $query = "UPDATE penduduk SET 
                nama_penduduk = '$nama_penduduk',
                tempat_lahir = '$tempat_lahir',
                `tanggal_lahir` = '$tanggal_lahir',
                jenis_kelamin = '$jenis_kelamin',
                agama = '$agama',
                pendidikan = '$pendidikan',
                pekerjaan = '$pekerjaan',
                status_kawin = '$status_kawin',
                alamat = '$alamat',
                `rt/rw` = '$rt_rw',
                `kel/des` = '$kel_des',
                kecamatan = '$kecamatan',
                `kabupaten/kota` = '$kabupaten_kota',
                kodepos = '$kodepos',
                provinsi = '$provinsi'
              WHERE 
                nik = '$nik'"; // NIK sebagai penentu baris mana yang di-update

    mysqli_query($conn, $query);

    return mysqli_affected_rows($conn);
}

function hapus_penduduk($nik) {
    global $conn;

    // Untuk keamanan, bersihkan NIK sebelum dipakai di query
    $nik_aman = mysqli_real_escape_string($conn, $nik);
    
    $query = "DELETE FROM penduduk WHERE nik = '$nik_aman'";
    mysqli_query($conn, $query);
    
    return mysqli_affected_rows($conn);
}


?>