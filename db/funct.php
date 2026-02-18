<?php
// db/funct.php
include "koneksi.php";

// ==================== FUNGSI UMUM ====================
function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function is_nik_exist($nik) {
    global $conn;
    $nik = mysqli_real_escape_string($conn, $nik);
    $result = mysqli_query($conn, "SELECT nik FROM penduduk WHERE nik = '$nik'");
    return mysqli_num_rows($result) > 0;
}

// ==================== FUNGSI PENDUDUK ====================

function add_penduduk($data) {
    global $conn;
    
    // Validasi NIK
    if (!isset($data["nik"]) || empty($data["nik"])) {
        return 0;
    }
    
    $nik = trim($data["nik"]);
    $nik = preg_replace('/[^0-9]/', '', $nik);
    
    if (strlen($nik) != 16) {
        return -4; 
    }
    
    // Cek duplikasi
    if (is_nik_exist($nik)) {
        return -1;
    }
    
    // Escape semua input
    $nik = mysqli_real_escape_string($conn, $nik);
    $nama_penduduk = mysqli_real_escape_string($conn, isset($data["nama_penduduk"]) ? trim(strtoupper($data["nama_penduduk"])) : '');
    $nama_ayah = mysqli_real_escape_string($conn, isset($data["nama_ayah"]) ? trim(strtoupper($data["nama_ayah"])) : '');
    $nama_ibu = mysqli_real_escape_string($conn, isset($data["nama_ibu"]) ? trim(strtoupper($data["nama_ibu"])) : '');
    $tempat_lahir = mysqli_real_escape_string($conn, isset($data["tempat_lahir"]) ? trim(strtoupper($data["tempat_lahir"])) : '');
    
    // Tanggal lahir - khusus penanganan NULL
    $tanggal_lahir = isset($data["tanggal_lahir"]) && !empty($data["tanggal_lahir"]) ? "'" . mysqli_real_escape_string($conn, $data["tanggal_lahir"]) . "'" : "NULL";
    
    $jenis_kelamin = mysqli_real_escape_string($conn, isset($data["jenis_kelamin"]) ? trim($data["jenis_kelamin"]) : 'LAKI-LAKI');
    $agama = mysqli_real_escape_string($conn, isset($data["agama"]) ? trim(strtoupper($data["agama"])) : 'ISLAM');
    $pendidikan = mysqli_real_escape_string($conn, isset($data["pendidikan"]) ? trim(strtoupper($data["pendidikan"])) : 'TIDAK SEKOLAH');
    $pekerjaan = mysqli_real_escape_string($conn, isset($data["pekerjaan"]) ? trim(strtoupper($data["pekerjaan"])) : '');
    $status_kawin = mysqli_real_escape_string($conn, isset($data["status_kawin"]) ? trim($data["status_kawin"]) : 'Belum Kawin');
    $alamat = mysqli_real_escape_string($conn, isset($data["alamat"]) ? trim(strtoupper($data["alamat"])) : '');
    $rt_rw = mysqli_real_escape_string($conn, isset($data["rt_rw"]) ? trim($data["rt_rw"]) : '001/002');
    $dusun = mysqli_real_escape_string($conn, isset($data["dusun"]) ? trim(strtoupper($data["dusun"])) : '');
    $kel_des = mysqli_real_escape_string($conn, isset($data["kel_des"]) ? trim(strtoupper($data["kel_des"])) : 'SUKOLILO TIMUR');
    $kecamatan = mysqli_real_escape_string($conn, isset($data["kecamatan"]) ? trim(strtoupper($data["kecamatan"])) : 'SUKOLILO');
    $kabupaten_kota = mysqli_real_escape_string($conn, isset($data["kabupaten_kota"]) ? trim(strtoupper($data["kabupaten_kota"])) : 'BANGKALAN');
    $kodepos = mysqli_real_escape_string($conn, isset($data["kodepos"]) ? trim($data["kodepos"]) : '69162');
    $provinsi = mysqli_real_escape_string($conn, isset($data["provinsi"]) ? trim(strtoupper($data["provinsi"])) : 'JAWA TIMUR');
    
    // Query INSERT dengan field dusun
    $query = "INSERT INTO penduduk (
        nik, nama_penduduk, nama_ayah, nama_ibu, tempat_lahir, tanggal_lahir,
        jenis_kelamin, agama, pendidikan, pekerjaan,
        status_kawin, alamat, rt_rw, dusun, kel_des,
        kecamatan, kabupaten_kota, kodepos, provinsi
    ) VALUES (
        '$nik',
        '$nama_penduduk',
        '$nama_ayah',
        '$nama_ibu',
        '$tempat_lahir',
        $tanggal_lahir,
        '$jenis_kelamin',
        '$agama',
        '$pendidikan',
        '$pekerjaan',
        '$status_kawin',
        '$alamat',
        '$rt_rw',
        '$dusun',
        '$kel_des',
        '$kecamatan',
        '$kabupaten_kota',
        '$kodepos',
        '$provinsi'
    )";
    
    if (mysqli_query($conn, $query)) {
        return mysqli_affected_rows($conn);
    } else {
        error_log("Error add_penduduk: " . mysqli_error($conn));
        return 0;
    }
}

function edit_penduduk($data) {
    global $conn;
    
    if (!isset($data["nik"]) || empty($data["nik"])) {
        return 0;
    }
    
    $nik = mysqli_real_escape_string($conn, $data["nik"]);
    
    $nama_penduduk = mysqli_real_escape_string($conn, isset($data["nama_penduduk"]) ? trim(strtoupper($data["nama_penduduk"])) : '');
    $nama_ayah = mysqli_real_escape_string($conn, isset($data["nama_ayah"]) ? trim(strtoupper($data["nama_ayah"])) : '');
    $nama_ibu = mysqli_real_escape_string($conn, isset($data["nama_ibu"]) ? trim(strtoupper($data["nama_ibu"])) : '');
    $tempat_lahir = mysqli_real_escape_string($conn, isset($data["tempat_lahir"]) ? trim(strtoupper($data["tempat_lahir"])) : '');
    $tanggal_lahir = !empty($data["tanggal_lahir"]) ? "'" . mysqli_real_escape_string($conn, $data["tanggal_lahir"]) . "'" : "NULL";
    $jenis_kelamin = mysqli_real_escape_string($conn, isset($data["jenis_kelamin"]) ? trim($data["jenis_kelamin"]) : 'LAKI-LAKI');
    $agama = mysqli_real_escape_string($conn, isset($data["agama"]) ? trim(strtoupper($data["agama"])) : 'ISLAM');
    $pendidikan = mysqli_real_escape_string($conn, isset($data["pendidikan"]) ? trim(strtoupper($data["pendidikan"])) : 'TIDAK SEKOLAH');
    $pekerjaan = mysqli_real_escape_string($conn, isset($data["pekerjaan"]) ? trim(strtoupper($data["pekerjaan"])) : '');
    $status_kawin = mysqli_real_escape_string($conn, isset($data["status_kawin"]) ? trim($data["status_kawin"]) : 'Belum Kawin');
    $alamat = mysqli_real_escape_string($conn, isset($data["alamat"]) ? trim(strtoupper($data["alamat"])) : '');
    $rt_rw = mysqli_real_escape_string($conn, isset($data["rt_rw"]) ? trim($data["rt_rw"]) : '001/002');
    $dusun = mysqli_real_escape_string($conn, isset($data["dusun"]) ? trim(strtoupper($data["dusun"])) : '');
    $kel_des = mysqli_real_escape_string($conn, isset($data["kel_des"]) ? trim(strtoupper($data["kel_des"])) : 'SUKOLILO TIMUR');
    $kecamatan = mysqli_real_escape_string($conn, isset($data["kecamatan"]) ? trim(strtoupper($data["kecamatan"])) : 'SUKOLILO');
    $kabupaten_kota = mysqli_real_escape_string($conn, isset($data["kabupaten_kota"]) ? trim(strtoupper($data["kabupaten_kota"])) : 'BANGKALAN');
    $kodepos = mysqli_real_escape_string($conn, isset($data["kodepos"]) ? trim($data["kodepos"]) : '69162');
    $provinsi = mysqli_real_escape_string($conn, isset($data["provinsi"]) ? trim(strtoupper($data["provinsi"])) : 'JAWA TIMUR');
    
    $query = "UPDATE penduduk SET 
                nama_penduduk = '$nama_penduduk',
                nama_ayah = '$nama_ayah',
                nama_ibu = '$nama_ibu',
                tempat_lahir = '$tempat_lahir',
                tanggal_lahir = $tanggal_lahir,
                jenis_kelamin = '$jenis_kelamin',
                agama = '$agama',
                pendidikan = '$pendidikan',
                pekerjaan = '$pekerjaan',
                status_kawin = '$status_kawin',
                alamat = '$alamat',
                rt_rw = '$rt_rw',
                dusun = '$dusun',
                kel_des = '$kel_des',
                kecamatan = '$kecamatan',
                kabupaten_kota = '$kabupaten_kota',
                kodepos = '$kodepos',
                provinsi = '$provinsi'
              WHERE nik = '$nik'";
    
    if (mysqli_query($conn, $query)) {
        return mysqli_affected_rows($conn);
    } else {
        error_log("Error edit_penduduk: " . mysqli_error($conn));
        return 0;
    }
}

function hapus_penduduk($nik) {
    global $conn;
    
    $nik = mysqli_real_escape_string($conn, $nik);
    
    // Cek apakah NIK digunakan sebagai kepala keluarga
    $cek_kepala = mysqli_query($conn, "SELECT COUNT(*) as count FROM kartu_keluarga WHERE nik_kepala = '$nik'");
    $row_kepala = mysqli_fetch_assoc($cek_kepala);
    if ($row_kepala['count'] > 0) {
        return -2;
    }
    
    // Cek apakah NIK digunakan sebagai anggota keluarga
    $cek_anggota = mysqli_query($conn, "SELECT COUNT(*) as count FROM anggota_keluarga WHERE nik = '$nik'");
    $row_anggota = mysqli_fetch_assoc($cek_anggota);
    if ($row_anggota['count'] > 0) {
        return -3;
    }
    
    $query = "DELETE FROM penduduk WHERE nik = '$nik'";
    if (mysqli_query($conn, $query)) {
        return mysqli_affected_rows($conn);
    } else {
        error_log("Error hapus_penduduk: " . mysqli_error($conn));
        return 0;
    }
}

// ==================== FUNGSI KARTU KELUARGA ====================

function tambah_kk($data) {
    global $conn;
    
    // Validasi input
    if (!isset($data['no_kk']) || empty($data['no_kk'])) {
        return 0;
    }
    
    if (!isset($data['nik_kepala']) || empty($data['nik_kepala'])) {
        return 0;
    }
    
    $no_kk = mysqli_real_escape_string($conn, $data['no_kk']);
    $nik_kepala = mysqli_real_escape_string($conn, $data['nik_kepala']);
    $alamat_kk = mysqli_real_escape_string($conn, $data['alamat_kk']);
    $rt = mysqli_real_escape_string($conn, isset($data['rt']) ? $data['rt'] : '001');
    $rw = mysqli_real_escape_string($conn, isset($data['rw']) ? $data['rw'] : '002');
    
    // PERBAIKAN: Proses dusun
    if (isset($data['dusun_option']) && $data['dusun_option'] == 'pilih') {
        $dusun = mysqli_real_escape_string($conn, isset($data['dusun_select']) ? $data['dusun_select'] : '');
    } else {
        $dusun = mysqli_real_escape_string($conn, isset($data['dusun_custom']) ? strtoupper($data['dusun_custom']) : '');
    }
    
    $desa_kel = mysqli_real_escape_string($conn, $data['desa_kel']);
    $kecamatan = mysqli_real_escape_string($conn, $data['kecamatan']);
    $kabupaten_kota = mysqli_real_escape_string($conn, $data['kabupaten_kota']);
    $provinsi = mysqli_real_escape_string($conn, $data['provinsi']);
    $kode_pos = mysqli_real_escape_string($conn, $data['kode_pos']);
    
    // Cek duplikat KK
    $cek_kk = mysqli_query($conn, "SELECT no_kk FROM kartu_keluarga WHERE no_kk = '$no_kk'");
    if (mysqli_num_rows($cek_kk) > 0) {
        return -1;
    }
    
    // Cek apakah NIK sudah jadi kepala keluarga
    $cek_kepala = mysqli_query($conn, "SELECT nik_kepala FROM kartu_keluarga WHERE nik_kepala = '$nik_kepala'");
    if (mysqli_num_rows($cek_kepala) > 0) {
        return -2;
    }
    
    // Cek apakah NIK sudah jadi anggota
    $cek_anggota = mysqli_query($conn, "SELECT nik FROM anggota_keluarga WHERE nik = '$nik_kepala'");
    if (mysqli_num_rows($cek_anggota) > 0) {
        return -3;
    }
    
    // Insert KK
    $query = "INSERT INTO kartu_keluarga (
        no_kk, nik_kepala, alamat_kk, rt, rw, dusun, 
        desa_kel, kecamatan, kabupaten_kota, provinsi, kode_pos
    ) VALUES (
        '$no_kk', '$nik_kepala', '$alamat_kk', '$rt', '$rw', '$dusun',
        '$desa_kel', '$kecamatan', '$kabupaten_kota', '$provinsi', '$kode_pos'
    )";
    
    if (mysqli_query($conn, $query)) {
        // Insert kepala keluarga sebagai anggota
        $query_anggota = "INSERT INTO anggota_keluarga (no_kk, nik, hubungan_keluarga) 
                         VALUES ('$no_kk', '$nik_kepala', 'Kepala Keluarga')";
        mysqli_query($conn, $query_anggota);
        return mysqli_affected_rows($conn);
    }
    
    error_log("Error tambah_kk: " . mysqli_error($conn));
    return 0;
}

function edit_kk($data) {
    global $conn;
    
    if (!isset($data['no_kk']) || empty($data['no_kk'])) {
        return 0;
    }
    
    $no_kk = mysqli_real_escape_string($conn, $data['no_kk']);
    $alamat_kk = mysqli_real_escape_string($conn, $data['alamat_kk']);
    $rt = mysqli_real_escape_string($conn, isset($data['rt']) ? $data['rt'] : '001');
    $rw = mysqli_real_escape_string($conn, isset($data['rw']) ? $data['rw'] : '002');
    
    // PERBAIKAN: Proses dusun
    if (isset($data['edit_dusun_option']) && $data['edit_dusun_option'] == 'pilih') {
        $dusun = mysqli_real_escape_string($conn, isset($data['edit_dusun_select']) ? $data['edit_dusun_select'] : '');
    } else {
        $dusun = mysqli_real_escape_string($conn, isset($data['edit_dusun_custom']) ? strtoupper($data['edit_dusun_custom']) : '');
    }
    
    $desa_kel = mysqli_real_escape_string($conn, $data['desa_kel']);
    $kecamatan = mysqli_real_escape_string($conn, $data['kecamatan']);
    $kabupaten_kota = mysqli_real_escape_string($conn, $data['kabupaten_kota']);
    $provinsi = mysqli_real_escape_string($conn, $data['provinsi']);
    $kode_pos = mysqli_real_escape_string($conn, $data['kode_pos']);
    
    $query = "UPDATE kartu_keluarga SET 
                alamat_kk = '$alamat_kk',
                rt = '$rt',
                rw = '$rw',
                dusun = '$dusun',
                desa_kel = '$desa_kel',
                kecamatan = '$kecamatan',
                kabupaten_kota = '$kabupaten_kota',
                provinsi = '$provinsi',
                kode_pos = '$kode_pos'
              WHERE no_kk = '$no_kk'";
    
    if (mysqli_query($conn, $query)) {
        return mysqli_affected_rows($conn);
    }
    
    error_log("Error edit_kk: " . mysqli_error($conn));
    return 0;
}

function hapus_kk($no_kk) {
    global $conn;
    
    $no_kk = mysqli_real_escape_string($conn, $no_kk);
    
    // Hapus anggota keluarga dulu
    mysqli_query($conn, "DELETE FROM anggota_keluarga WHERE no_kk = '$no_kk'");
    
    // Hapus KK
    $query = "DELETE FROM kartu_keluarga WHERE no_kk = '$no_kk'";
    if (mysqli_query($conn, $query)) {
        return mysqli_affected_rows($conn);
    }
    
    error_log("Error hapus_kk: " . mysqli_error($conn));
    return 0;
}

function tambah_anggota_keluarga($data) {
    global $conn;
    
    if (!isset($data['no_kk']) || empty($data['no_kk']) || !isset($data['nik']) || empty($data['nik'])) {
        return 0;
    }
    
    $no_kk = mysqli_real_escape_string($conn, $data['no_kk']);
    $nik = mysqli_real_escape_string($conn, $data['nik']);
    $hubungan_keluarga = mysqli_real_escape_string($conn, $data['hubungan_keluarga']);
    
    // Cek apakah sudah terdaftar
    $cek = mysqli_query($conn, "SELECT * FROM anggota_keluarga WHERE no_kk = '$no_kk' AND nik = '$nik'");
    if (mysqli_num_rows($cek) > 0) {
        return 0;
    }
    
    // Cek apakah penduduk ini adalah kepala keluarga di KK lain
    $cek_kepala_lain = mysqli_query($conn, "SELECT no_kk FROM kartu_keluarga WHERE nik_kepala = '$nik' AND no_kk != '$no_kk'");
    if (mysqli_num_rows($cek_kepala_lain) > 0) {
        return -2;
    }
    
    // Cek apakah penduduk ini sudah menjadi anggota di KK lain
    $cek_anggota_lain = mysqli_query($conn, "SELECT no_kk FROM anggota_keluarga WHERE nik = '$nik' AND no_kk != '$no_kk'");
    if (mysqli_num_rows($cek_anggota_lain) > 0) {
        return -3;
    }
    
    $query = "INSERT INTO anggota_keluarga (no_kk, nik, hubungan_keluarga) 
              VALUES ('$no_kk', '$nik', '$hubungan_keluarga')";
    
    if (mysqli_query($conn, $query)) {
        return mysqli_affected_rows($conn);
    }
    
    error_log("Error tambah_anggota_keluarga: " . mysqli_error($conn));
    return 0;
}

function hapus_anggota_keluarga($id_anggota) {
    global $conn;
    
    $id_anggota = mysqli_real_escape_string($conn, $id_anggota);
    
    $query = "DELETE FROM anggota_keluarga WHERE id_anggota = '$id_anggota'";
    if (mysqli_query($conn, $query)) {
        return mysqli_affected_rows($conn);
    }
    
    error_log("Error hapus_anggota_keluarga: " . mysqli_error($conn));
    return 0;
}

// ==================== FUNGSI STATISTIK ====================

function get_total_penduduk() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM penduduk");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function get_total_kk() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM kartu_keluarga");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function get_total_laki() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'LAKI-LAKI'");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function get_total_perempuan() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'PEREMPUAN'");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function get_total_surat() {
    global $conn;
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM arsip_surat");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function get_penduduk_per_dusun() {
    global $conn;
    $result = mysqli_query($conn, "SELECT dusun, COUNT(*) as total FROM penduduk GROUP BY dusun ORDER BY dusun");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['dusun']] = $row['total'];
    }
    return $data;
}

function get_kk_per_dusun() {
    global $conn;
    $result = mysqli_query($conn, "SELECT dusun, COUNT(*) as total FROM kartu_keluarga GROUP BY dusun ORDER BY dusun");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['dusun']] = $row['total'];
    }
    return $data;
}

function get_laki_per_dusun() {
    global $conn;
    $result = mysqli_query($conn, "SELECT dusun, COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'LAKI-LAKI' GROUP BY dusun ORDER BY dusun");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['dusun']] = $row['total'];
    }
    return $data;
}

function get_perempuan_per_dusun() {
    global $conn;
    $result = mysqli_query($conn, "SELECT dusun, COUNT(*) as total FROM penduduk WHERE jenis_kelamin = 'PEREMPUAN' GROUP BY dusun ORDER BY dusun");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['dusun']] = $row['total'];
    }
    return $data;
}

// ==================== FUNGSI VALIDASI ====================

function is_kk_exist($no_kk) {
    global $conn;
    $no_kk = mysqli_real_escape_string($conn, $no_kk);
    $result = mysqli_query($conn, "SELECT no_kk FROM kartu_keluarga WHERE no_kk = '$no_kk'");
    return mysqli_num_rows($result) > 0;
}

function is_penduduk_tersedia($nik) {
    global $conn;
    $nik = mysqli_real_escape_string($conn, $nik);
    
    // Cek apakah penduduk sudah menjadi kepala keluarga atau anggota
    $query = "SELECT nik FROM penduduk p 
              WHERE p.nik = '$nik' 
              AND p.nik NOT IN (
                  SELECT nik_kepala FROM kartu_keluarga
                  UNION
                  SELECT nik FROM anggota_keluarga
              )";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

function get_penduduk_tersedia() {
    global $conn;
    $query = "SELECT nik, nama_penduduk FROM penduduk 
              WHERE nik NOT IN (
                  SELECT nik_kepala FROM kartu_keluarga
                  UNION
                  SELECT nik FROM anggota_keluarga
              )
              ORDER BY nama_penduduk";
    $result = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// ==================== FUNGSI FORMAT DATA ====================

function format_tanggal_indonesia($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $tgl = date('d', strtotime($tanggal));
    $bln = $bulan[(int)date('m', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    return $tgl . ' ' . $bln . ' ' . $thn;
}

function hitung_umur($tanggal_lahir) {
    if (empty($tanggal_lahir)) return 0;
    $birthDate = new DateTime($tanggal_lahir);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    return $age;
}

// ==================== FUNGSI DEBUG ====================

function debug_query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo "Error: " . mysqli_error($conn) . "<br>";
        echo "Query: " . $query . "<br>";
        return false;
    }
    return $result;
}

function get_last_error() {
    global $conn;
    return mysqli_error($conn);
}
?>