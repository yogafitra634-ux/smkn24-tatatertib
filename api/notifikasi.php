<?php
// ============================================================
// api/notifikasi.php
// GET  → ambil notifikasi user yang login
// POST → tandai sudah dibaca (mark as read)
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';

require_login();

$user   = current_user();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: ambil notifikasi ───────────────────────────────────
if ($method === 'GET') {
    $query = 'notifikasi?user_id=eq.' . $user['id']
           . '&select=id,judul,pesan,is_read,created_at'
           . '&order=created_at.desc&limit=20';

    $result = supabase($query);

    if ($result['status'] === 200) {
        $data       = $result['data'] ?? [];
        $unread     = count(array_filter($data, fn($n) => !$n['is_read']));

        echo json_encode([
            'success' => true,
            'data'    => $data,
            'unread'  => $unread,
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil notifikasi.']);
    }
    exit;
}

// ── POST: mark as read ──────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $id   = $body['id'] ?? 'all';

    if ($id === 'all') {
        // Tandai semua notifikasi user sebagai sudah dibaca
        $endpoint = 'notifikasi?user_id=eq.' . $user['id'] . '&is_read=eq.false';
    } else {
        // Tandai satu notifikasi
        $endpoint = 'notifikasi?id=eq.' . urlencode($id) . '&user_id=eq.' . $user['id'];
    }

    $result = supabase($endpoint, 'PATCH', ['is_read' => true]);

    if (in_array($result['status'], [200, 204])) {
        echo json_encode(['success' => true, 'message' => 'Notifikasi ditandai dibaca.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal update notifikasi.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
