<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$user        = current_user();
$active_page = 'pelanggaran';
$success = $error = '';

// Hapus pelanggaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    $id = $_POST['id'] ?? '';
    if ($id) {
        $r       = supabase('pelanggaran?id=eq.' . urlencode($id), 'DELETE');
        $success = in_array($r['status'], [200,204]) ? 'Pelanggaran berhasil dihapus.' : '';
        $error   = !in_array($r['status'], [200,204]) ? 'Gagal menghapus.' : '';
    }
}

// Ambil kelas untuk filter dropdown
$kelas_res  = supabase('kelas?select=id,nama_kelas&order=tingkat.asc,nama_kelas.asc');
$kelas_list = $kelas_res['data'] ?? [];

// Ambil data pelanggaran langsung pakai join (bukan view)
$res = supabase(
    'pelanggaran?select=id,tanggal,lokasi,sanksi,status,'
    . 'siswa_id(id,nama_lengkap,nis_nip,profil_siswa(kelas_id(id,nama_kelas))),'
    . 'operator_id(nama_lengkap),'
    . 'tata_tertib_id(aturan,tingkat,poin)'
    . '&order=tanggal.desc&limit=200'
);
$raw = $res['data'] ?? [];

// Normalize data
$data = array_map(function($r) {
    $siswa  = is_array($r['siswa_id'])       ? $r['siswa_id']       : [];
    $op     = is_array($r['operator_id'])    ? $r['operator_id']    : [];
    $tt     = is_array($r['tata_tertib_id']) ? $r['tata_tertib_id'] : [];
    $profil = is_array($siswa['profil_siswa'][0] ?? null) ? ($siswa['profil_siswa'][0]) : [];
    $kls    = is_array($profil['kelas_id']   ?? null) ? $profil['kelas_id'] : [];
    return [
        'id'                => $r['id']               ?? '',
        'tanggal'           => $r['tanggal']          ?? '',
        'nama_siswa'        => $siswa['nama_lengkap'] ?? '-',
        'siswa_id'          => $siswa['id']           ?? '',
        'nama_kelas'        => $kls['nama_kelas']     ?? '-',
        'kelas_id'          => $kls['id']             ?? '',
        'jenis_pelanggaran' => $tt['aturan']          ?? '-',
        'tingkat'           => $tt['tingkat']         ?? '-',
        'poin'              => $tt['poin']            ?? 0,
        'nama_operator'     => $op['nama_lengkap']    ?? '-',
        'lokasi'            => $r['lokasi']           ?? '-',
        'sanksi'            => $r['sanksi']           ?? '-',
    ];
}, $raw);

// tgl_indo() tersedia dari includes/functions.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelanggaran — SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>@media(max-width:768px){.hamburger-btn{display:block !important}}</style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__.'/../components/sidebar-admin.php'; ?>
    <div class="main-content">
        <?php $page_title='Kelola Pelanggaran'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Kelola Pelanggaran</h1>
                <p>Daftar semua pelanggaran yang tercatat</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success" data-autohide><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Filter bar -->
            <div class="filter-bar">
                <input type="text" id="cari" placeholder="Cari nama siswa..." style="flex:1;min-width:160px">
                <select id="fil-kelas">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="fil-tingkat">
                    <option value="">Semua Tingkat</option>
                    <option value="Ringan">Ringan</option>
                    <option value="Sedang">Sedang</option>
                    <option value="Berat">Berat</option>
                </select>
                <button onclick="resetFilter()" class="btn btn-outline btn-sm">Reset</button>
            </div>

            <div class="card">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                        <div class="card-title" style="margin-bottom:0">
                            Semua Pelanggaran
                            <span id="count-label" style="font-size:13px;font-weight:500;color:var(--text-light)">
                                (<?= count($data) ?>)
                            </span>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table id="tabel-pel">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th>Tingkat</th>
                                    <th>Operator</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-pel">
                            <?php if (empty($data)): ?>
                                <tr><td colspan="8">
                                    <div class="empty-state"><p>Belum ada data pelanggaran.</p></div>
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($data as $i => $r):
                                    $lvl = strtolower($r['tingkat']);
                                ?>
                                <tr data-nama="<?= strtolower(htmlspecialchars($r['nama_siswa'])) ?>"
                                    data-kelas-id="<?= htmlspecialchars($r['kelas_id']) ?>"
                                    data-tingkat="<?= htmlspecialchars($r['tingkat']) ?>">
                                    <td class="no-col"><?= $i+1 ?></td>
                                    <td><?= tgl_indo($r['tanggal']) ?></td>
                                    <td>
                                        <div style="font-weight:600"><?= htmlspecialchars($r['nama_siswa']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($r['nama_kelas']) ?></td>
                                    <td><?= htmlspecialchars($r['jenis_pelanggaran']) ?></td>
                                    <td><span class="badge badge-<?= $lvl ?>"><?= htmlspecialchars($r['tingkat']) ?></span></td>
                                    <td><?= htmlspecialchars($r['nama_operator']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Hapus pelanggaran ini?')">Hapus</button>
                                        </form>
                                    </td>
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
<script>
function filterTabel() {
    const q       = document.getElementById('cari').value.toLowerCase().trim();
    const kelasId = document.getElementById('fil-kelas').value;
    const tingkat = document.getElementById('fil-tingkat').value;

    let visible = 0;
    const rows  = document.querySelectorAll('#tbody-pel tr[data-nama]');

    rows.forEach(tr => {
        const matchNama   = !q       || tr.dataset.nama.includes(q);
        const matchKelas  = !kelasId || tr.dataset.kelasId === kelasId;
        const matchTingkat= !tingkat || tr.dataset.tingkat === tingkat;
        const show        = matchNama && matchKelas && matchTingkat;
        tr.style.display  = show ? '' : 'none';
        if (show) visible++;
    });

    // Update nomor urut
    let no = 1;
    rows.forEach(tr => {
        if (tr.style.display !== 'none') {
            tr.querySelector('.no-col').textContent = no++;
        }
    });

    document.getElementById('count-label').textContent = '(' + visible + ')';
}

function resetFilter() {
    document.getElementById('cari').value        = '';
    document.getElementById('fil-kelas').value   = '';
    document.getElementById('fil-tingkat').value = '';
    filterTabel();
}

document.getElementById('cari').addEventListener('input', filterTabel);
document.getElementById('fil-kelas').addEventListener('change', filterTabel);
document.getElementById('fil-tingkat').addEventListener('change', filterTabel);
</script>
</body>
</html>
