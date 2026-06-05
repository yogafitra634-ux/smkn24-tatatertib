<?php
// ============================================================
// api/get-pelanggaran.php
// Ambil data pelanggaran (dengan filter)
// Method: GET
// Params: siswa_id, kelas_id, tingkat, dari, sampai, limit, offset
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';

require_login();

$user  = current_user();
$role  = $user['role'];

// ── Build query string ──────────────────────────────────────
$select = 'select=id,tanggal,waktu,lokasi,keterangan,sanksi,status,'
        . 'siswa_id(id,nama_lengkap,nis_nip),'
        . 'operator_id(id,nama_lengkap),'
        . 'tata_tertib_id(id,kategori,aturan,tingkat,poin),'
        . 'profil_siswa_id:siswa_id(kelas_id(nama_kelas,jurusan))';

$filters = [];

// Siswa hanya lihat miliknya sendiri
if ($role === 'siswa') {
    $filters[] = 'siswa_id=eq.' . $user['id'];
}

// Filter opsional dari query param
if (!empty($_GET['siswa_id'])) {
    $filters[] = 'siswa_id=eq.' . urlencode($_GET['siswa_id']);
}

if (!empty($_GET['tingkat']) && $_GET['tingkat'] !== 'semua') {
    $filters[] = 'tata_tertib_id.tingkat=eq.' . urlencode($_GET['tingkat']);
}

if (!empty($_GET['dari'])) {
    $filters[] = 'tanggal=gte.' . urlencode($_GET['dari']);
}

if (!empty($_GET['sampai'])) {
    $filters[] = 'tanggal=lte.' . urlencode($_GET['sampai']);
}

$limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$query = 'pelanggaran?' . $select;
if ($filters) $query .= '&' . implode('&', $filters);
$query .= '&order=tanggal.desc&limit=' . $limit . '&offset=' . $offset;

$result = supabase($query);

if ($result['status'] === 200) {
    echo json_encode(['success' => true, 'data' => $result['data']]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data.']);
}
