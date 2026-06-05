<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_role('admin');

$user        = current_user();
$active_page = 'siswa';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id   = $_POST['id']   ?? '';

    if ($aksi === 'hapus' && $id) {

    error_log("=== HAPUS SISWA ===");
    error_log("ID: " . $id);

    // hapus riwayat SP siswa
    supabase(
        'riwayat_peringatan?siswa_id=eq.' . urlencode($id),
        'DELETE'
    );

// baru hapus user

    $r = supabase(
        'users?id=eq.' . urlencode($id) . '&role=eq.siswa',
        'DELETE'
    );

    error_log("STATUS: " . ($r['status'] ?? 'NULL'));
    error_log("RESPONSE: " . print_r($r, true));

    if (in_array($r['status'], [200, 204])) {
        $success = 'Siswa berhasil dihapus.';
    } else {
        $error = 'Gagal menghapus siswa.';

        if (!empty($r['data'])) {
            $error .= '<br><pre>' .
                htmlspecialchars(json_encode($r['data'], JSON_PRETTY_PRINT)) .
                '</pre>';
        }
    }
}
}

$res        = supabase('users?role=eq.siswa&select=id,nis_nip,nama_lengkap,email,foto,is_active,created_at,profil_siswa(kelas_id(nama_kelas,jurusan))&order=nama_lengkap.asc');
$siswa_list = $res['data'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa — SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        @media(max-width:768px){.hamburger-btn{display:block !important}}

        /* Lookup modal */
        .lookup-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 500;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .lookup-overlay.open { display: flex; }
        .lookup-modal {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 24px 80px rgba(0,0,0,.2);
            width: 100%; max-width: 560px;
            max-height: 80vh;
            display: flex; flex-direction: column;
            overflow: hidden;
        }
        .lookup-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-border);
        }
        .lookup-header h3 { font-size:15px; font-weight:700; }
        .lookup-search { padding: 12px 16px; border-bottom: 1px solid var(--gray-border); }
        .lookup-search input {
            width: 100%; padding: 9px 13px;
            border: 1.5px solid var(--gray-border);
            border-radius: var(--radius-sm);
            font-size: 13px; font-family: var(--font);
            outline: none; transition: border-color .2s;
        }
        .lookup-search input:focus { border-color: var(--blue-main); }
        .lookup-list { overflow-y: auto; flex: 1; }
        .lookup-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-border);
            cursor: pointer; transition: background .15s;
        }
        .lookup-item:last-child { border-bottom: none; }
        .lookup-item:hover { background: var(--blue-light); }
        .lookup-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: var(--blue-main);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; overflow: hidden;
        }
        .lookup-avatar img { width:100%; height:100%; object-fit:cover; }
        .lookup-avatar svg { color: #fff; }
        .lookup-nama { font-size:13px; font-weight:600; color: var(--text-dark); }
        .lookup-info { font-size:12px; color: var(--text-light); margin-top:1px; }
        .lookup-empty { padding: 32px; text-align: center; color: var(--text-light); font-size:13px; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__.'/../components/sidebar-admin.php'; ?>
    <div class="main-content">
        <?php $page_title='Kelola Siswa'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Kelola Siswa</h1>
                <p>Manajemen data siswa</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success" data-autohide><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Filter bar -->
            <div class="filter-bar">
                <input type="text" id="cari-siswa" placeholder="Cari nama atau NIS..." style="flex:1" readonly
                       onclick="document.getElementById('lookup-overlay').classList.add('open');setTimeout(()=>document.getElementById('lookup-input').focus(),100)">
                <button class="btn btn-blue"
                        onclick="document.getElementById('lookup-overlay').classList.add('open');setTimeout(()=>document.getElementById('lookup-input').focus(),100)">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right:4px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Lookup Siswa
                </button>
                <button class="btn btn-outline" id="btn-reset-filter" style="display:none" onclick="resetFilter()">Reset</button>
            </div>

            <div class="card">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                        <div class="card-title" style="margin-bottom:0">
                            Daftar Semua Siswa
                            <span id="count-siswa" style="font-size:13px;font-weight:500;color:var(--text-light)">(<?= count($siswa_list) ?>)</span>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>NIS/NIP</th>
                                    <th>Kelas</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-siswa">
                            <?php if (empty($siswa_list)): ?>
                                <tr><td colspan="7"><div class="empty-state"><p>Belum ada siswa terdaftar.</p></div></td></tr>
                            <?php else: ?>
                                <?php foreach ($siswa_list as $i => $s):
                                    $profil = $s['profil_siswa'][0] ?? [];
                                    $kelas  = $profil['kelas_id']['nama_kelas'] ?? '-';
                                    $search = strtolower($s['nama_lengkap'] . ' ' . $s['nis_nip']);
                                ?>
                                <tr data-search="<?= htmlspecialchars($search) ?>">
                                    <td class="no-col"><?= $i+1 ?></td>
                                    <td>
                                        <div class="user-row-info">
                                            <div class="user-row-avatar">
                                                <?php if ($s['foto']): ?>
                                                    <img src="../<?= htmlspecialchars($s['foto']) ?>" alt="">
                                                <?php else: ?>
                                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#fff"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-row-name"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($s['nis_nip']) ?></td>
                                    <td><?= htmlspecialchars($kelas) ?></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><span class="badge <?= $s['is_active'] ? 'badge-ringan' : 'badge-berat' ?>"><?= $s['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                                    <td>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Hapus siswa <?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>?')">
                                                Hapus
                                            </button>
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

<!-- LOOKUP MODAL -->
<div class="lookup-overlay" id="lookup-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="lookup-modal">
        <div class="lookup-header">
            <h3>Lookup Siswa</h3>
            <button onclick="document.getElementById('lookup-overlay').classList.remove('open')"
                    style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--text-light)">&times;</button>
        </div>
        <div class="lookup-search">
            <input type="text" id="lookup-input" placeholder="Ketik nama atau NIS siswa..." oninput="filterLookup(this.value)">
        </div>
        <div class="lookup-list" id="lookup-list">
            <?php if (empty($siswa_list)): ?>
            <div class="lookup-empty">Belum ada siswa terdaftar.</div>
            <?php else: ?>
            <?php foreach ($siswa_list as $s):
                $profil = $s['profil_siswa'][0] ?? [];
                $kelas  = $profil['kelas_id']['nama_kelas'] ?? 'Belum isi profil';
            ?>
            <div class="lookup-item"
                 data-search="<?= strtolower(htmlspecialchars($s['nama_lengkap'] . ' ' . $s['nis_nip'])) ?>"
                 onclick="pilihDariLookup('<?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>', '<?= htmlspecialchars($s['nis_nip']) ?>')">
                <div class="lookup-avatar">
                    <?php if ($s['foto']): ?>
                        <img src="../<?= htmlspecialchars($s['foto']) ?>" alt="">
                    <?php else: ?>
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="lookup-nama"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                    <div class="lookup-info"><?= htmlspecialchars($s['nis_nip']) ?> &bull; <?= htmlspecialchars($kelas) ?> &bull; <?= htmlspecialchars($s['email']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
// Filter tabel dari lookup
function filterSiswa(q) {
    q = q.toLowerCase();
    let visible = 0;
    document.querySelectorAll('#tbody-siswa tr[data-search]').forEach(tr => {
        const show = !q || tr.dataset.search.includes(q);
        tr.style.display = show ? '' : 'none';
        if (show) tr.querySelector('.no-col').textContent = ++visible;
    });
    document.getElementById('count-siswa').textContent = '(' + visible + ')';
    document.getElementById('btn-reset-filter').style.display = q ? '' : 'none';
}

function resetFilter() {
    document.getElementById('cari-siswa').value = '';
    filterSiswa('');
}

// Filter lookup modal
function filterLookup(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.lookup-item').forEach(item => {
        item.style.display = item.dataset.search.includes(q) ? 'flex' : 'none';
    });
}

// Pilih dari lookup → filter tabel & tutup modal
function pilihDariLookup(nama, nis) {
    const input = document.getElementById('cari-siswa');
    input.value = nama;
    filterSiswa(nama.toLowerCase());
    document.getElementById('lookup-overlay').classList.remove('open');
}
</script>
</body>
</html>
