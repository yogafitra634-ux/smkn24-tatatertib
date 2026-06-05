<?php
// siswa/dashboard.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('siswa');
$user        = current_user();
$active_page = 'dashboard';

// ── Ambil ringkasan pelanggaran siswa ─────────────────────────
$ringkasan = supabase_first(
    'v_ringkasan_siswa?siswa_id=eq.' . $user['id'] . '&select=*'
);

$total_pelanggaran  = (int)($ringkasan['total_pelanggaran'] ?? 0);
$jumlah_ringan      = (int)($ringkasan['jumlah_ringan']     ?? 0);
$jumlah_sedang      = (int)($ringkasan['jumlah_sedang']     ?? 0);
$jumlah_berat       = (int)($ringkasan['jumlah_berat']      ?? 0);
$total_poin         = (int)($ringkasan['total_poin']        ?? 0);
$pelanggaran_terakhir = $ringkasan['pelanggaran_terakhir']  ?? null;

// Status kedisiplinan berdasarkan total poin
function get_status(int $poin): array {
    if ($poin === 0)      return ['label' => 'Sempurna', 'class' => 'baik',   'desc' => 'Tidak ada pelanggaran!'];
    if ($poin <= 20)      return ['label' => 'Baik',     'class' => 'baik',   'desc' => 'Jaga terus kedisiplinanmu!'];
    if ($poin <= 50)      return ['label' => 'Cukup',    'class' => 'sedang', 'desc' => 'Perhatikan aturan sekolah.'];
    return                       ['label' => 'Buruk',    'class' => 'buruk',  'desc' => 'Segera perbaiki perilakumu!'];
}
$status = get_status($total_poin);

// Status Surat Peringatan
$surat_peringatan = null;

if ($total_poin >= 100) {
    $surat_peringatan = [
        'label' => 'SP 2',
        'class' => 'danger',
        'pesan' => 'Total poin pelanggaran Anda telah mencapai 100 poin. Silakan menemui wali kelas atau guru BK untuk tindak lanjut.'
    ];
} elseif ($total_poin >= 50) {
    $surat_peringatan = [
        'label' => 'SP 1',
        'class' => 'warning',
        'pesan' => 'Total poin pelanggaran Anda telah mencapai 50 poin. Harap meningkatkan kedisiplinan agar tidak mendapatkan SP 2.'
    ];
}

// Status Surat Peringatan
$surat_peringatan = null;

if ($total_poin >= 100) {
    $surat_peringatan = [
        'label' => 'SP 2',
        'class' => 'danger',
        'pesan' => 'Total poin pelanggaran Anda telah mencapai 100 poin. Silakan menemui wali kelas atau guru BK untuk tindak lanjut.'
    ];
} elseif ($total_poin >= 50) {
    $surat_peringatan = [
        'label' => 'SP 1',
        'class' => 'warning',
        'pesan' => 'Total poin pelanggaran Anda telah mencapai 50 poin. Harap meningkatkan kedisiplinan agar tidak mendapatkan SP 2.'
    ];
}

// ── Ambil 3 pelanggaran terbaru ────────────────────────────────
$riwayat_res = supabase(
    'pelanggaran?siswa_id=eq.' . $user['id']
    . '&select=tanggal,sanksi,status,tata_tertib_id(aturan,tingkat)'
    . '&order=tanggal.desc&limit=3'
);
$riwayat_terbaru = $riwayat_res['data'] ?? [];

// ── Ambil 4 tata tertib aktif (preview) ───────────────────────
$tt_res = supabase('tata_tertib?is_active=eq.true&select=aturan,tingkat&limit=4&order=created_at.asc');
$tata_tertib_preview = $tt_res['data'] ?? [];

// ── Format tanggal Indonesia — sudah tersedia di includes/functions.php ──
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Sistem Tata Tertib SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Hamburger tampil di mobile */
        @media (max-width:768px) { .hamburger-btn { display:block !important; } }

        .dashboard-top {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width:900px) {
            .dashboard-top { grid-template-columns: 1fr 1fr; }
            .two-col        { grid-template-columns: 1fr; }
        }
        .sp-alert{
            display:flex;
            gap:14px;
            align-items:flex-start;
            padding:18px;
            border-radius:12px;
            margin-bottom:20px;
            border-left:5px solid;
        }

        .sp-alert-icon{
            font-size:28px;
            line-height:1;
        }

        .sp-alert strong{
            display:block;
            margin-bottom:6px;
            font-size:18px;
            font-weight:700;
        }

        .sp-alert p{
            margin:0;
            line-height:1.6;
            font-size:13px;
        }

        .sp-alert.warning{
            background:#fff7ed;
            border-color:#f97316;
            color:#9a3412;
        }

        .sp-alert.danger{
            background:#fef2f2;
            border-color:#dc2626;
            color:#991b1b;
        }
        @media (max-width:540px) {
            .dashboard-top { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="app-layout">

    <?php require_once __DIR__ . '/../components/sidebar-siswa.php'; ?>

    <div class="main-content">
        <?php
            $page_title = 'Dashboard';
            require_once __DIR__ . '/../components/navbar.php';
        ?>

        <div class="page-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Dashboard Siswa</h1>
                <p>Selamat datang, <?= htmlspecialchars($user['nama_lengkap']) ?>!</p>
            </div>

            <!-- Top Cards -->
            <div class="dashboard-top">

                <!-- Status Kedisiplinan -->
                <div class="stat-card">
                    <div class="status-icon <?= $status['class'] ?>">
                        <?php if ($status['class'] === 'baik'): ?>
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <?php elseif ($status['class'] === 'sedang'): ?>
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <?php else: ?>
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <div class="stat-info">
                        <div class="label">Status Anda</div>
                        <div class="value" style="font-size:20px;color:<?= $status['class'] === 'baik' ? '#16a34a' : ($status['class'] === 'sedang' ? '#f97316' : '#ef4444') ?>">
                            <?= $status['label'] ?>
                        </div>
                        <div class="sub"><?= $status['desc'] ?></div>
                    </div>
                </div>

                <!-- Jumlah Pelanggaran -->
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="label">Jumlah Pelanggaran</div>
                        <div class="value"><?= $total_pelanggaran ?></div>
                        <div class="sub">Kejadian</div>
                    </div>
                </div>

                <!-- Pelanggaran Terakhir -->
                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="label">Pelanggaran Terakhir</div>
                        <div class="value" style="font-size:15px">
                            <?= $pelanggaran_terakhir ? tgl_indo($pelanggaran_terakhir) : '-' ?>
                        </div>
                        <div class="sub">
                            <?= $riwayat_terbaru[0]['tata_tertib_id']['aturan'] ?? '-' ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Two Column -->
            <div class="two-col">

                <!-- Riwayat Terbaru -->
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">Riwayat Pelanggaran Anda</div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jenis Pelanggaran</th>
                                        <th>Tingkat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($riwayat_terbaru)): ?>
                                    <tr>
                                        <td colspan="3">
                                            <div class="empty-state" style="padding:24px">
                                                <p>Belum ada pelanggaran 🎉</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($riwayat_terbaru as $row):
                                        $tt  = $row['tata_tertib_id'] ?? [];
                                        $lvl = strtolower($tt['tingkat'] ?? '');
                                    ?>
                                    <tr>
                                        <td><?= tgl_indo($row['tanggal']) ?></td>
                                        <td><?= htmlspecialchars($tt['aturan'] ?? '-') ?></td>
                                        <td><span class="badge badge-<?= $lvl ?>"><?= htmlspecialchars($tt['tingkat'] ?? '-') ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer">
                            <a href="riwayat-pelanggaran.php" class="link">Lihat Semua</a>
                        </div>
                    </div>
                </div>

                <!-- Informasi Tata Tertib -->
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">Informasi Tata Tertib</div>
                        <?php if (empty($tata_tertib_preview)): ?>
                            <p style="font-size:13px;color:var(--text-light)">Belum ada data.</p>
                        <?php else: ?>
                            <?php foreach ($tata_tertib_preview as $tt): ?>
                            <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid var(--gray-border);font-size:13px">
                                <span style="width:8px;height:8px;border-radius:50%;background:var(--blue-main);flex-shrink:0;display:inline-block"></span>
                                <?= htmlspecialchars($tt['aturan']) ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="table-footer">
                            <a href="informasi-tata-tertib.php" class="link">Lihat Semua</a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Quote Banner -->
            <div class="quote-banner">
                "Disiplin adalah jembatan antara tujuan dan pencapaian"
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
