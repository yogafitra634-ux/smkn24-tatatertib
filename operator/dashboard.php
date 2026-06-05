<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('operator');

$user        = current_user();
$active_page = 'dashboard';

$today     = date('Y-m-d');
$bulan_ini = date('Y-m') . '-01';

// Statistik
$total_siswa  = supabase('users?role=eq.siswa&is_active=eq.true&select=id');
$siswa_aktif  = supabase('users?role=eq.siswa&is_active=eq.true&select=id');
$pel_hari     = supabase('pelanggaran?tanggal=eq.'.$today.'&select=id');
$pel_bulan    = supabase('pelanggaran?tanggal=gte.'.$bulan_ini.'&select=id');

$jml_siswa   = count($total_siswa['data']  ?? []);
$jml_aktif   = count($siswa_aktif['data']  ?? []);
$jml_hari    = count($pel_hari['data']     ?? []);
$jml_bulan   = count($pel_bulan['data']    ?? []);

// Pelanggaran terbaru (5 data) — pakai tabel langsung bukan view
$terbaru_res = supabase(
    'pelanggaran?select=tanggal,lokasi,'
    . 'siswa_id(nama_lengkap,nis_nip,profil_siswa(kelas_id(nama_kelas))),'
    . 'tata_tertib_id(aturan,tingkat)'
    . '&order=tanggal.desc&limit=5'
);
$terbaru_raw = $terbaru_res['data'] ?? [];

// Normalize data agar mudah dipakai di template
$terbaru = array_map(function($r) {
    $siswa  = is_array($r['siswa_id'])       ? $r['siswa_id']       : [];
    $tt     = is_array($r['tata_tertib_id']) ? $r['tata_tertib_id'] : [];
    $profil = is_array($siswa['profil_siswa'][0] ?? null) ? $siswa['profil_siswa'][0] : [];
    $kelas  = is_array($profil['kelas_id']   ?? null) ? $profil['kelas_id']   : [];
    return [
        'tanggal'           => $r['tanggal']          ?? '-',
        'lokasi'            => $r['lokasi']           ?? '-',
        'nama_siswa'        => $siswa['nama_lengkap'] ?? '-',
        'nama_kelas'        => $kelas['nama_kelas']   ?? '-',
        'jenis_pelanggaran' => $tt['aturan']          ?? '-',
        'tingkat'           => $tt['tingkat']         ?? '-',
    ];
}, $terbaru_raw);

// tgl_indo() tersedia dari includes/functions.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Operator — SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>@media(max-width:768px){.hamburger-btn{display:block !important}}</style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__.'/../components/sidebar-operator.php'; ?>
    <div class="main-content">
        <?php $page_title='Dashboard'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Dashboard Operator</h1>
                <p>Selamat datang, <?= htmlspecialchars($user['nama_lengkap']) ?>!</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid" style="margin-bottom:24px">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="label">Total Siswa</div>
                        <div class="value"><?= $jml_siswa ?></div>
                        <div class="sub">Siswa Terdaftar</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="label">Pelanggaran Hari Ini</div>
                        <div class="value"><?= $jml_hari ?></div>
                        <div class="sub">Kejadian</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="label">Pelanggaran Bulan Ini</div>
                        <div class="value"><?= $jml_bulan ?></div>
                        <div class="sub">Kejadian</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="label">Siswa Aktif</div>
                        <div class="value"><?= $jml_aktif ?></div>
                        <div class="sub">Siswa</div>
                    </div>
                </div>
            </div>

            <!-- Tabel pelanggaran terbaru -->
            <div class="card">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                        <div class="card-title" style="margin-bottom:0">Pelanggaran Terbaru</div>
                        <a href="riwayat-pelanggaran.php" class="link">Lihat Semua</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th>Tingkat</th>
                                    <th>Lokasi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($terbaru)): ?>
                                <tr><td colspan="6">
                                    <div class="empty-state"><p>Belum ada pelanggaran tercatat.</p></div>
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($terbaru as $r):
                                    $lvl = strtolower($r['tingkat'] ?? '');
                                ?>
                                <tr>
                                    <td><?= tgl_indo($r['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($r['nama_siswa'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['nama_kelas'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['jenis_pelanggaran'] ?? '-') ?></td>
                                    <td><span class="badge badge-<?= $lvl ?>"><?= htmlspecialchars($r['tingkat'] ?? '-') ?></span></td>
                                    <td><?= htmlspecialchars($r['lokasi'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
