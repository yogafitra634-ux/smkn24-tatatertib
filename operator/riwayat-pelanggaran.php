<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('operator');

$user        = current_user();
$active_page = 'riwayat';

// Filter
$cari   = trim($_GET['cari']   ?? '');
$tingkat= $_GET['tingkat']     ?? '';
$dari   = $_GET['dari']        ?? date('Y-m-01');
$sampai = $_GET['sampai']      ?? date('Y-m-d');
$nis    = trim($_GET['nis']    ?? ''); // filter by NIS

// Ambil kelas untuk filter
$kelas_res  = supabase('kelas?select=id,nama_kelas&order=nama_kelas.asc');
$kelas_list = $kelas_res['data'] ?? [];

// Ambil daftar siswa untuk lookup
$siswa_res    = supabase('users?role=eq.siswa&is_active=eq.true&select=id,nis_nip,nama_lengkap,profil_siswa(kelas_id(nama_kelas))&order=nama_lengkap.asc');
$siswa_lookup = $siswa_res['data'] ?? [];

// ----------------------------------------------------------------
// Ambil data langsung dari tabel pelanggaran + join
// ----------------------------------------------------------------
$filters = [
    'tanggal=gte.' . $dari,
    'tanggal=lte.' . $sampai,
];

$select = implode(',', [
    'id',
    'tanggal',
    'lokasi',
    'sanksi',
    'keterangan',
    'status',
    'siswa:siswa_id(id,nis_nip,nama_lengkap,profil_siswa(kelas_id(nama_kelas)))',
    'tata_tertib:tata_tertib_id(aturan,tingkat,kategori,poin)',
]);

$query = 'pelanggaran?select=' . urlencode($select) . '&' . implode('&', $filters) . '&order=tanggal.desc&limit=500';
$res   = supabase($query);
$raw   = $res['data'] ?? [];

// Normalize ke format flat
$data = array_map(function ($r) {
    $siswa  = $r['siswa']              ?? [];
    $profil = $siswa['profil_siswa'][0] ?? [];
    $kelas  = $profil['kelas_id']       ?? [];
    $tt     = $r['tata_tertib']         ?? [];
    return [
        'tanggal'           => $r['tanggal']           ?? '-',
        'lokasi'            => $r['lokasi']             ?? '-',
        'sanksi'            => $r['sanksi']             ?? '-',
        'keterangan'        => $r['keterangan']         ?? '',
        'status'            => $r['status']             ?? 'aktif',
        'nama_siswa'        => $siswa['nama_lengkap']   ?? '-',
        'nis_siswa'         => $siswa['nis_nip']        ?? '-',
        'nama_kelas'        => $kelas['nama_kelas']     ?? '-',
        'jenis_pelanggaran' => $tt['aturan']            ?? '-',
        'tingkat'           => $tt['tingkat']           ?? '-',
        'kategori'          => $tt['kategori']          ?? '-',
        'poin'              => $tt['poin']              ?? 0,
    ];
}, $raw);

// Filter tingkat (nested — dilakukan di PHP)
if ($tingkat && $tingkat !== 'semua') {
    $data = array_values(array_filter($data, fn($r) => $r['tingkat'] === $tingkat));
}

// Filter NIS
if ($nis) {
    $data = array_values(array_filter($data, fn($r) => stripos($r['nis_siswa'] ?? '', $nis) !== false));
}

// Filter nama / NIS (pencarian bebas)
if ($cari) {
    $data = array_values(array_filter($data, fn($r) =>
        stripos($r['nama_siswa'] ?? '', $cari) !== false ||
        stripos($r['nis_siswa']  ?? '', $cari) !== false
    ));
}

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

        /* Lookup modal */
        .lookup-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:500;display:none;align-items:center;justify-content:center;padding:20px;}
        .lookup-overlay.open{display:flex;}
        .lookup-modal{background:var(--white);border-radius:var(--radius);box-shadow:0 24px 80px rgba(0,0,0,.2);width:100%;max-width:520px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden;}
        .lookup-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--gray-border);}
        .lookup-header h3{font-size:15px;font-weight:700;}
        .lookup-search{padding:12px 16px;border-bottom:1px solid var(--gray-border);}
        .lookup-search input{width:100%;padding:9px 13px;border:1.5px solid var(--gray-border);border-radius:var(--radius-sm);font-size:13px;font-family:var(--font);outline:none;transition:border-color .2s;}
        .lookup-search input:focus{border-color:var(--blue-main);}
        .lookup-list{overflow-y:auto;flex:1;}
        .lookup-item{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--gray-border);cursor:pointer;transition:background .15s;}
        .lookup-item:last-child{border-bottom:none;}
        .lookup-item:hover{background:var(--blue-light);}
        .lookup-avatar{width:36px;height:36px;border-radius:50%;background:var(--blue-main);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .lookup-avatar svg{color:#fff;}
        .lookup-nama{font-size:13px;font-weight:600;color:var(--text-dark);}
        .lookup-info{font-size:12px;color:var(--text-light);margin-top:1px;}
        .lookup-empty{padding:32px;text-align:center;color:var(--text-light);font-size:13px;}
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__.'/../components/sidebar-operator.php'; ?>
    <div class="main-content">
        <?php $page_title='Riwayat Pelanggaran'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Riwayat Pelanggaran</h1>
                <p>Daftar pelanggaran yang tercatat</p>
            </div>

            <!-- Filter -->
            <form method="GET" action="riwayat-pelanggaran.php" id="form-filter">
                <!-- hidden NIS (diisi via lookup) -->
                <input type="hidden" name="nis" id="filter-nis" value="<?= htmlspecialchars($nis) ?>">

                <div class="filter-bar">
                    <!-- Cari nama -->
                    <div style="position:relative;flex:1;min-width:180px;display:flex;gap:8px;align-items:center">
                        <input type="text" name="cari" id="cari-input"
                               placeholder="Cari nama siswa atau NIS..."
                               value="<?= htmlspecialchars($cari) ?>"
                               style="flex:1">
                        <button type="button" class="btn btn-outline"
                                onclick="document.getElementById('lookup-siswa').classList.add('open');setTimeout(()=>document.getElementById('lookup-siswa-input').focus(),100)"
                                title="Lookup Siswa">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Lookup
                        </button>
                    </div>

                    <input type="date" name="dari"   value="<?= $dari ?>">
                    <input type="date" name="sampai" value="<?= $sampai ?>">

                    <select name="tingkat">
                        <option value="semua" <?= $tingkat==='semua'||!$tingkat?'selected':'' ?>>Semua Tingkat</option>
                        <option value="Ringan" <?= $tingkat==='Ringan'?'selected':'' ?>>Ringan</option>
                        <option value="Sedang" <?= $tingkat==='Sedang'?'selected':'' ?>>Sedang</option>
                        <option value="Berat"  <?= $tingkat==='Berat' ?'selected':'' ?>>Berat</option>
                    </select>

                    <button type="submit" class="btn btn-blue">Filter</button>
                    <a href="riwayat-pelanggaran.php" class="btn btn-outline">Reset</a>

                    <!-- Ekspor: sertakan semua filter aktif termasuk NIS -->
                    
                </div>
            </form>

            <!-- Tabel -->
            <div class="card">
                <div class="card-body">
                    <div class="card-title">
                        Daftar Pelanggaran
                        <span style="font-size:13px;font-weight:500;color:var(--text-light)">
                            (<?= count($data) ?> data)
                        </span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
                                    <th>NIS</th>
                                    <th>Kelas</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th>Tingkat</th>
                                    <th>Poin</th>
                                    <th>Lokasi</th>
                                    <th>Sanksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($data)): ?>
                                <tr><td colspan="10">
                                    <div class="empty-state">
                                        <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
                                        </svg>
                                        <p>Tidak ada data pelanggaran.</p>
                                    </div>
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($data as $i => $r):
                                    $lvl = strtolower($r['tingkat'] ?? '');
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= tgl_indo($r['tanggal']) ?></td>
                                    <td>
                                        <div style="font-weight:600"><?= htmlspecialchars($r['nama_siswa'] ?? '-') ?></div>
                                    </td>
                                    <td>
                                        <span style="font-size:12px;color:var(--text-light);font-family:monospace">
                                            <?= htmlspecialchars($r['nis_siswa'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($r['nama_kelas'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['jenis_pelanggaran'] ?? '-') ?></td>
                                    <td><span class="badge badge-<?= $lvl ?>"><?= htmlspecialchars($r['tingkat'] ?? '-') ?></span></td>
                                    <td style="text-align:center"><?= (int)($r['poin'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($r['lokasi'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['sanksi'] ?? '-') ?></td>
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

<!-- LOOKUP SISWA MODAL -->
<div class="lookup-overlay" id="lookup-siswa"
     onclick="if(event.target===this)this.classList.remove('open')">
    <div class="lookup-modal">
        <div class="lookup-header">
            <h3>Lookup Siswa</h3>
            <button onclick="document.getElementById('lookup-siswa').classList.remove('open')"
                    style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--text-light)">&times;</button>
        </div>
        <div class="lookup-search">
            <input type="text" id="lookup-siswa-input"
                   placeholder="Ketik nama atau NIS siswa..."
                   oninput="filterLookupSiswa(this.value)">
        </div>
        <div class="lookup-list" id="lookup-siswa-list">
            <?php if (empty($siswa_lookup)): ?>
            <div class="lookup-empty">Belum ada siswa terdaftar.</div>
            <?php else: ?>
            <?php foreach ($siswa_lookup as $s):
                $profil = $s['profil_siswa'][0] ?? [];
                $kls    = $profil['kelas_id']['nama_kelas'] ?? 'Belum isi profil';
            ?>
            <div class="lookup-item"
                 data-search="<?= strtolower(htmlspecialchars($s['nama_lengkap'] . ' ' . $s['nis_nip'])) ?>"
                 data-nama="<?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>"
                 data-nis="<?= htmlspecialchars($s['nis_nip']) ?>"
                 onclick="pilihSiswaLookup(this)">
                <div class="lookup-avatar">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <div class="lookup-nama"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                    <div class="lookup-info">
                        <?= htmlspecialchars($s['nis_nip']) ?> &bull; <?= htmlspecialchars($kls) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
/* ── Lookup filter ── */
function filterLookupSiswa(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#lookup-siswa-list .lookup-item').forEach(item => {
        item.style.display = item.dataset.search.includes(q) ? 'flex' : 'none';
    });
}

/* ── Pilih siswa dari lookup: isi nama + NIS, submit ── */
function pilihSiswaLookup(el) {
    const nama = el.dataset.nama;
    const nis  = el.dataset.nis;

    document.getElementById('cari-input').value  = nama;
    document.getElementById('filter-nis').value  = nis;
    document.getElementById('lookup-siswa').classList.remove('open');

    // Update URL ekspor sebelum submit agar tombol Ekspor ikut ter-update
    updateExportLink();

    document.getElementById('form-filter').submit();
}

/* ── Hapus filter NIS ── */
function clearNisFilter() {
    document.getElementById('filter-nis').value = '';
    document.getElementById('cari-input').value = '';
    document.getElementById('form-filter').submit();
}

/* ── Sinkronisasi link ekspor dengan filter aktif (real-time) ── */
function updateExportLink() {
    const dari    = document.querySelector('[name=dari]').value;
    const sampai  = document.querySelector('[name=sampai]').value;
    const tingkat = document.querySelector('[name=tingkat]').value;
    const cari    = document.getElementById('cari-input').value;
    const nis     = document.getElementById('filter-nis').value;

    const params = new URLSearchParams({
        format : 'excel',
        dari, sampai, tingkat, nis, cari
    });
    document.getElementById('btn-export').href =
        '../api/export-laporan.php?' + params.toString();
}

// Update link ekspor setiap kali form berubah
document.getElementById('form-filter').addEventListener('change', updateExportLink);
document.getElementById('cari-input').addEventListener('input', updateExportLink);
</script>
</body>
</html>
