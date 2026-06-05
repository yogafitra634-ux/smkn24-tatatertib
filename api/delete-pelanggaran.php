<?php
// ============================================================
// api/delete-pelanggaran.php
// Hapus pelanggaran (hanya admin)
// Method: DELETE
// Params: id (query string)
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';

require_role(['admin']);

$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID pelanggaran wajib diisi.']);
    exit;
}

$result = supabase('pelanggaran?id=eq.' . urlencode($id), 'DELETE');

if ($result['status'] === 200 || $result['status'] === 204) {
    echo json_encode(['success' => true, 'message' => 'Pelanggaran berhasil dihapus.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus pelanggaran.']);
}
