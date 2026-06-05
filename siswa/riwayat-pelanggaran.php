<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('siswa');

$user        = current_user();
$active_page = 'riwayat';

// Ambil semua pelanggaran siswa
$res = supabase(
    'pelanggaran?siswa_id=eq.' . $user['id']
    . '&select=id,tanggal,sanksi,status,tata_tertib_id(aturan,tingkat,poin)'
    . '&order=tanggal.desc'
);
$pelanggaran = $res['data'] ?? [];

$total   = count($pelanggaran);
$ringan  = count(array_filter($pelanggaran, fn($r) => ($r['tata_tertib_id']['tingkat'] ?? '') === 'Ringan'));
$sedang  = count(array_filter($pelanggaran, fn($r) => ($r['tata_tertib_id']['tingkat'] ?? '') === 'Sedang'));
$berat   = count(array_filter($pelanggaran, fn($r) => ($r['tata_tertib_id']['tingkat'] ?? '') === 'Berat'));
$last    = $pelanggaran[0] ?? null;

// tgl_indo() tersedia dari includes/functions.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pelanggaran — SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        @media(max-width:768px){.hamburger-btn{display:block !important}}
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__ . '/../components/sidebar-siswa.php'; ?>
    <div class="main-content">
        <?php $page_title='Riwayat Pelanggaran'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Riwayat Pelanggaran</h1>
                <p>Laporan riwayat pelanggaran</p>
            </div>

            <!-- Tabel -->
            <div class="card" style="margin-bottom:20px">
                <div class="card-body">
                    <div class="card-title">Riwayat Pelanggaran Anda</div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th>Tingkat</th>
                                    <th>Poin</th>
                                    <th>Sanksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($pelanggaran)): ?>
                                <tr><td colspan="5">
                                    <div class="empty-state">
                                        <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <p>Belum ada pelanggaran tercatat 🎉</p>
                                    </div>
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($pelanggaran as $row):
                                    $tt  = $row['tata_tertib_id'] ?? [];
                                    $lvl = strtolower($tt['tingkat'] ?? '');
                                ?>
                                <tr>
                                    <td><?= tgl_indo($row['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($tt['aturan'] ?? '-') ?></td>
                                    <td><span class="badge badge-<?= $lvl ?>"><?= htmlspecialchars($tt['tingkat'] ?? '-') ?></span></td>
                                    <td><?= (int)($tt['poin'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($row['sanksi'] ?? 'Tidak ada') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Stat bawah -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="label">Jumlah Pelanggaran</div>
                        <div class="value"><?= $total ?></div>
                        <div class="sub">Kejadian</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="label">Pelanggaran Terakhir</div>
                        <div class="value" style="font-size:14px"><?= $last ? tgl_indo($last['tanggal']) : '-' ?></div>
                        <div class="sub"><?= htmlspecialchars($last['tata_tertib_id']['aturan'] ?? '-') ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="label">Ringan / Sedang / Berat</div>
                        <div class="value" style="font-size:16px"><?= $ringan ?> / <?= $sedang ?> / <?= $berat ?></div>
                        <div class="sub">Berdasarkan tingkat</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
