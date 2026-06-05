<?php
// ============================================================
// api/add-pelanggaran.php
// Input pelanggaran baru (hanya operator & admin)
// Method: POST (JSON body)
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';

require_role(['operator', 'admin']);

$user = current_user();

// Baca JSON body
$body = json_decode(file_get_contents('php://input'), true);

// Validasi field wajib
$required = ['siswa_id', 'tata_tertib_id', 'tanggal'];
foreach ($required as $field) {
    if (empty($body[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field '$field' wajib diisi."]);
        exit;
    }
}

// Susun payload
$payload = [
    'siswa_id'       => $body['siswa_id'],
    'operator_id'    => $user['id'],
    'tata_tertib_id' => $body['tata_tertib_id'],
    'tanggal'        => $body['tanggal'],
    'waktu'          => $body['waktu']      ?? null,
    'lokasi'         => $body['lokasi']     ?? null,
    'keterangan'     => $body['keterangan'] ?? null,
    'sanksi'         => $body['sanksi']     ?? null,
    'status'         => 'aktif',
];

$result = supabase('pelanggaran', 'POST', $payload);

if ($result['status'] === 201 && !empty($result['data'])) {
    // Kirim notifikasi ke siswa
    $notif = [
        'user_id' => $body['siswa_id'],
        'judul'   => 'Pelanggaran Baru Dicatat',
        'pesan'   => 'Kamu memiliki catatan pelanggaran baru pada tanggal ' . $body['tanggal'] . '.',
    ];
    supabase('notifikasi', 'POST', $notif);

    echo json_encode([
        'success' => true,
        'message' => 'Pelanggaran berhasil disimpan.',
        'data'    => $result['data'][0],
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pelanggaran.']);
}
