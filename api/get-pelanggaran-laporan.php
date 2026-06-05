<?php
// ============================================================
// api/get-pelanggaran-laporan.php
// API khusus untuk halaman laporan
// Params: periode (YYYY-MM), kelas_id, tingkat
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';

require_role(['operator', 'admin']);

$periode = $_GET['periode'] ?? date('Y-m');
$kelas   = $_GET['kelas_id'] ?? '';
$tingkat = $_GET['tingkat']  ?? '';

// Hitung range tanggal dari periode YYYY-MM
[$y, $m] = explode('-', $periode . '-01');
$dari    = "$y-" . str_pad($m, 2, '0', STR_PAD_LEFT) . "-01";
$sampai  = date('Y-m-t', strtotime($dari));

// Build query ke tabel pelanggaran dengan join
$select = 'pelanggaran?select=id,tanggal,lokasi,sanksi,'
        . 'siswa_id(nama_lengkap,nis_nip,profil_siswa(kelas_id(id,nama_kelas,jurusan))),'
        . 'tata_tertib_id(aturan,tingkat,poin)'
        . '&tanggal=gte.' . $dari
        . '&tanggal=lte.' . $sampai
        . '&order=tanggal.desc&limit=500';

$res  = supabase($select);
$raw  = $res['data'] ?? [];

if ($res['status'] !== 200) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data.']);
    exit;
}

// Normalize & filter
$data = [];
foreach ($raw as $r) {
    $siswa  = is_array($r['siswa_id'])       ? $r['siswa_id']       : [];
    $tt     = is_array($r['tata_tertib_id']) ? $r['tata_tertib_id'] : [];
    $profil = is_array($siswa['profil_siswa'][0] ?? null) ? ($siswa['profil_siswa'][0]) : [];
    $kls    = is_array($profil['kelas_id']   ?? null) ? $profil['kelas_id'] : [];

    $kelas_id   = $kls['id']        ?? '';
    $nama_kelas = $kls['nama_kelas'] ?? '-';
    $tingkat_r  = $tt['tingkat']    ?? '';

    // Filter kelas
    if ($kelas && $kelas_id !== $kelas) continue;

    // Filter tingkat
    if ($tingkat && $tingkat !== 'semua' && $tingkat_r !== $tingkat) continue;

    $data[] = [
        'id'                => $r['id']               ?? '',
        'tanggal'           => $r['tanggal']          ?? '',
        'lokasi'            => $r['lokasi']            ?? '',
        'sanksi'            => $r['sanksi']            ?? '',
        'nama_siswa'        => $siswa['nama_lengkap']  ?? '-',
        'nis_siswa'         => $siswa['nis_nip']       ?? '-',
        'nama_kelas'        => $nama_kelas,
        'jenis_pelanggaran' => $tt['aturan']           ?? '-',
        'tingkat'           => $tingkat_r,
        'poin'              => $tt['poin']             ?? 0,
    ];
}

echo json_encode(['success' => true, 'data' => $data, 'total' => count($data)]);
