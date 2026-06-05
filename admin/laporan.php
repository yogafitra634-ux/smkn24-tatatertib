<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_role('admin');

$user        = current_user();
$active_page = 'laporan';

$kelas_res  = supabase('kelas?select=id,nama_kelas&order=tingkat.asc,nama_kelas.asc');
$kelas_list = $kelas_res['data'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan — SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        @media(max-width:768px){.hamburger-btn{display:block !important}}
        .filter-card {
            background: var(--white);
            border: 1px solid var(--gray-border);
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-bottom: 20px;
        }
        .filter-card-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-mid);
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }
        @media(max-width:700px){ .filter-row { grid-template-columns: 1fr 1fr; } }
        @media(max-width:480px){ .filter-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__.'/../components/sidebar-admin.php'; ?>
    <div class="main-content">
        <?php $page_title='Laporan'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Laporan</h1>
                <p>Cetak dan unduh laporan pelanggaran</p>
            </div>

            <!-- Filter -->
            <div class="filter-card">
                <div class="filter-card-title">Filter Laporan</div>
                <div class="filter-row">
                    <div class="form-group">
                        <label class="form-label">Periode</label>
                        <input type="month" id="filter-periode" class="form-control" value="<?= date('Y-m') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kelas</label>
                        <select id="filter-kelas" class="form-control">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tingkat</label>
                        <select id="filter-tingkat" class="form-control">
                            <option value="semua">Semua Tingkat</option>
                            <option value="Ringan">Ringan</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Berat">Berat</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button id="btn-tampilkan" class="btn btn-blue" style="width:100%">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right:4px">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Tampilkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="laporan-stats">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="label">Total Pelanggaran</div>
                        <div class="value" id="stat-total">—</div>
                        <div class="sub">Kejadian</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="stat-info"><div class="label">Ringan</div><div class="value" id="stat-ringan">—</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div class="stat-info"><div class="label">Sedang</div><div class="value" id="stat-sedang">—</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div class="stat-info"><div class="label">Berat</div><div class="value" id="stat-berat">—</div></div>
                </div>
            </div>

            <!-- Preview tabel -->
            <div class="card">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                        <div class="card-title" style="margin-bottom:0">Preview Laporan</div>
                        <span id="label-filter" style="font-size:12px;color:var(--text-light)"></span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th>Tingkat</th>
                                </tr>
                            </thead>
                            <tbody id="laporan-tbody">
                                <tr><td colspan="6" style="text-align:center;padding:28px;color:var(--text-light)">
                                    Pilih filter lalu klik "Tampilkan".
                                </td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Export -->
                    <div class="export-actions">
                        <button class="btn btn-outline" data-export="excel">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export Excel
                        </button>
                        <button class="btn btn-outline" style="color:var(--red);border-color:#fca5a5" data-export="pdf">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Export PDF
                        </button>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/dashboard.js"></script>
<script>
document.getElementById('btn-tampilkan').addEventListener('click', function() {
    const periode = document.getElementById('filter-periode').value;
    const kelas   = document.getElementById('filter-kelas');
    const tingkat = document.getElementById('filter-tingkat').value;
    const kelasNama = kelas.options[kelas.selectedIndex].text;
    const parts = [];
    if (periode) parts.push(periode);
    if (kelas.value) parts.push(kelasNama);
    if (tingkat && tingkat !== 'semua') parts.push(tingkat);
    document.getElementById('label-filter').textContent = parts.length ? 'Filter: ' + parts.join(' · ') : '';
});
</script>
</body>
</html>
