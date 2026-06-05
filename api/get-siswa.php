<?php
// ============================================================
// api/get-siswa.php
// Ambil daftar siswa (untuk dropdown, tabel, dll)
// Method: GET
// Params: q (search nama/nis), kelas_id, limit, offset
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';

require_role(['operator', 'admin']);

$filters = ['role=eq.siswa', 'is_active=eq.true'];

// Search by nama atau NIS
if (!empty($_GET['q'])) {
    $q = urlencode($_GET['q']);
    // Supabase: OR filter
    $filters[] = 'or=(nama_lengkap.ilike.*' . $q . '*,nis_nip.ilike.*' . $q . '*)';
}

$select = 'select=id,nis_nip,nama_lengkap,foto,'
        . 'profil_siswa(kelas_id(id,nama_kelas,jurusan,wali_kelas))';

$limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : 100;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$query = 'users?' . $select . '&' . implode('&', $filters)
       . '&order=nama_lengkap.asc&limit=' . $limit . '&offset=' . $offset;

$result = supabase($query);

if ($result['status'] === 200) {
    echo json_encode(['success' => true, 'data' => $result['data']]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data siswa.']);
}
