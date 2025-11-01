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
    $nik = $data["nik"];
    $nama = $data["nama_penduduk"];
    $tempat = $data["tempat"];
    $tanggal = $data["tanggal"];
    $alamat = $data["alamat"];
    $pekerjaan = $data["pekerjaan"];
    $status_kawin = $data["status_kawin"];
    $pendidikan = $data["pendidikan"];

    // ... baris sebelumnya ...
    $query = "INSERT INTO penduduk(nik, nama_penduduk, tempat_lahir, `tanggal_lahir`, alamat, pekerjaan, status_kawin, pendidikan) VALUES('$nik', '$nama', '$tempat', '$tanggal', '$alamat', '$pekerjaan', '$status_kawin', '$pendidikan')";
    mysqli_query($conn, $query); // <== Ini baris 29 (yang sudah diperbaiki)
    // ...
    return mysqli_affected_rows($conn);
}



?>