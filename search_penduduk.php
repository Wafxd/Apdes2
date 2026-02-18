<?php
session_start();
include "db/koneksi.php";
include "db/funct.php";

if (!isset($_SESSION['id_admin'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// ==================== FUNGSI HITUNG UMUR ====================
function hitungUmur($tanggal_lahir) {
    if (empty($tanggal_lahir)) return 0;
    $birthDate = new DateTime($tanggal_lahir);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    return $age;
}

// Ambil data dari POST
$dusun = isset($_POST['dusun']) ? $_POST['dusun'] : 'all';
$umur = isset($_POST['umur']) ? (int)$_POST['umur'] : 0;
$jk = isset($_POST['jk']) ? $_POST['jk'] : 'all';
$status = isset($_POST['status']) ? $_POST['status'] : 'all';
$pekerjaan = isset($_POST['pekerjaan']) ? $_POST['pekerjaan'] : 'all';

// Query dasar
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM kartu_keluarga WHERE nik_kepala = p.nik) as is_kepala,
          (SELECT COUNT(*) FROM anggota_keluarga WHERE nik = p.nik AND hubungan_keluarga != 'Kepala Keluarga') as is_anggota
          FROM penduduk p 
          WHERE 1=1";

$params = [];
$types = "";

// Filter dusun
if ($dusun != 'all') {
    $query .= " AND p.dusun = ?";
    $params[] = $dusun;
    $types .= "s";
}

// Filter jenis kelamin
if ($jk != 'all') {
    $query .= " AND p.jenis_kelamin = ?";
    $params[] = $jk;
    $types .= "s";
}

// Filter status kawin
if ($status != 'all') {
    $query .= " AND p.status_kawin = ?";
    $params[] = $status;
    $types .= "s";
}

// Filter pekerjaan
if ($pekerjaan != 'all') {
    if ($pekerjaan == 'Lainnya') {
        $pekerjaan_list = ['PNS', 'TNI', 'POLRI', 'PEGAWAI SWASTA', 'WIRASWASTA', 'PETANI', 'BURUH', 'PELAJAR/MAHASISWA', 'IRT', 'PENSIUNAN'];
        $placeholders = implode(',', array_fill(0, count($pekerjaan_list), '?'));
        $query .= " AND (p.pekerjaan NOT IN ($placeholders) OR p.pekerjaan IS NULL OR p.pekerjaan = '')";
        foreach ($pekerjaan_list as $p) {
            $params[] = $p;
            $types .= "s";
        }
    } else {
        $query .= " AND p.pekerjaan = ?";
        $params[] = $pekerjaan;
        $types .= "s";
    }
}

$query .= " ORDER BY p.nama_penduduk ASC";

// Eksekusi query dengan prepared statement
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Filter berdasarkan umur di PHP dan kumpulkan data
$filtered_data = [];
$total_filtered = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $umur_penduduk = hitungUmur($row['tanggal_lahir']);
    
    // Filter umur
    if ($umur > 0) {
        if ($umur_penduduk != $umur) {
            continue;
        }
    }
    
    // Tambahkan umur ke data
    $row['umur'] = $umur_penduduk;
    $filtered_data[] = $row;
    $total_filtered++;
}

// Kirim response JSON
echo json_encode([
    'total' => $total_filtered,
    'data' => $filtered_data
]);
?>