<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../config/whatsapp.php';
require_role('operator');

$user        = current_user();
$active_page = 'input';
$success = $error = '';

// Ambil tata tertib
$tt_res = supabase('tata_tertib?is_active=eq.true&select=id,kategori,aturan,tingkat,poin&order=kategori.asc,aturan.asc');
$tt_all = $tt_res['data'] ?? [];
$kategori_list = ['Kehadiran','Kerapihan','Sikap & Perilaku','Barang Terlarang'];
$tt_grouped = [];
foreach ($kategori_list as $kat) {
    $tt_grouped[$kat] = array_values(array_filter($tt_all, fn($r) => $r['kategori'] === $kat));
}

$lokasi_list = ['Gerbang Sekolah','Kelas','Kantin','Lapangan','Toilet','Koridor','Parkiran','Luar Sekolah'];

// Ambil semua siswa untuk modal lookup
$siswa_res  = supabase('users?role=eq.siswa&is_active=eq.true&select=id,nis_nip,nama_lengkap,foto,profil_siswa(kelas_id(nama_kelas))&order=nama_lengkap.asc');
$siswa_all  = $siswa_res['data'] ?? [];

// Proses submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa_id       = $_POST['siswa_id']       ?? '';
    $tata_tertib_id = $_POST['tata_tertib_id'] ?? '';
    $tanggal        = $_POST['tanggal']        ?? '';
    $waktu          = $_POST['waktu']          ?? null;
    $lokasi         = $_POST['lokasi']         ?? null;
    $keterangan     = trim($_POST['keterangan'] ?? '');
    $sanksi         = trim($_POST['sanksi']     ?? '');

    if (!$siswa_id || !$tata_tertib_id || !$tanggal) {
        $error = 'Siswa, jenis pelanggaran, dan tanggal wajib diisi.';
    } else {
        $r = supabase('pelanggaran', 'POST', [
            'siswa_id'       => $siswa_id,
            'operator_id'    => $user['id'],
            'tata_tertib_id' => $tata_tertib_id,
            'tanggal'        => $tanggal,
            'waktu'          => $waktu      ?: null,
            'lokasi'         => $lokasi     ?: null,
            'keterangan'     => $keterangan ?: null,
            'sanksi'         => $sanksi     ?: null,
            'status'         => 'aktif',
        ]);

        if ($r['status'] === 201) {

    // Notifikasi siswa
    supabase('notifikasi', 'POST', [
        'user_id' => $siswa_id,
        'judul'   => 'Pelanggaran Baru Dicatat',
        'pesan'   => 'Kamu memiliki catatan pelanggaran baru pada tanggal '
                    . date('d/m/Y', strtotime($tanggal)) . '.',
    ]);

    

    // Ambil data siswa
    $siswa = supabase_first(
        'users?id=eq.' . $siswa_id .
        '&select=nama_lengkap,nis_nip'
    );

    // Ambil profil siswa
    $profil = supabase_first(
        'profil_siswa?user_id=eq.' . $siswa_id .
        '&select=no_telepon_orang_tua'
    );

    $nama_siswa = $siswa['nama_lengkap'] ?? 'Siswa';
    $nis_siswa = $siswa['nis_nip'] ?? '-';
    $no_ortu    = $profil['no_telepon_orang_tua'] ?? '';

    // Hitung total poin siswa
    $pelRes = supabase(
        'pelanggaran?siswa_id=eq.' . $siswa_id .
        '&select=tata_tertib_id(poin)'
    );

    $totalPoin = 0;

    foreach (($pelRes['data'] ?? []) as $p) {
        $totalPoin += (int)($p['tata_tertib_id']['poin'] ?? 0);
    }

   error_log("TOTAL POIN = " . $totalPoin);
error_log("NO ORTU = " . $no_ortu);

error_log("SEBELUM IF SP1");

if ($totalPoin >= 50 && $no_ortu) {

    error_log("MASUK IF SP1");

    $cekSP1 = supabase_first(
        'riwayat_peringatan?siswa_id=eq.' . $siswa_id .
        '&jenis_sp=eq.SP1'
    );

    error_log("HASIL CEK SP1:");
    error_log(print_r($cekSP1, true));

    if (!$cekSP1) {

        error_log("AKAN KIRIM WA SP1");

        $pesan = "TEST WA";

        $wa = kirimWA($no_ortu, $pesan);

        error_log("WA RESULT:");
        error_log(print_r($wa, true));
    }
}

    // ====================
// CEK SP1
// ====================

if ($totalPoin >= 50 && $no_ortu) {

    $cekSP1 = supabase_first(
        'riwayat_peringatan?siswa_id=eq.' . $siswa_id .
        '&jenis_sp=eq.SP1'
    );

    if (!$cekSP1) {

        $pesan = "📢 SURAT PERINGATAN 1 (SP1)

Yth. Bapak/Ibu Orang Tua/Wali,

Dengan hormat,

Kami menginformasikan bahwa siswa berikut:

Nama : {$nama_siswa}
NIS  : {$nis_siswa}

telah mencapai total {$totalPoin} poin pelanggaran tata tertib sekolah dan memperoleh Surat Peringatan 1 (SP1).

Kami mengharapkan dukungan Bapak/Ibu untuk memberikan pembinaan dan pengawasan agar siswa dapat meningkatkan kedisiplinan dan tidak mengulangi pelanggaran di kemudian hari.

Terima kasih atas perhatian dan kerja samanya.

Hormat kami,

SMKN 24 Jakarta
Sistem Tata Tertib Sekolah";

        $wa = kirimWA($no_ortu, $pesan);

        error_log('WA SP1: ' . print_r($wa, true));

        supabase('riwayat_peringatan', 'POST', [
            'siswa_id'   => $siswa_id,
            'jenis_sp'   => 'SP1',
            'total_poin' => $totalPoin
        ]);
    }
}

// ====================
// CEK SP2
// ====================

error_log("SEBELUM IF SP2");
error_log("TOTAL POIN SP2 = " . $totalPoin);

if ($totalPoin >= 100 && $no_ortu) {

    $cekSP2 = supabase_first(
        'riwayat_peringatan?siswa_id=eq.' . $siswa_id .
        '&jenis_sp=eq.SP2'
    );

    if (!$cekSP2) {

        $pesan = "🚨 SURAT PERINGATAN 2 (SP2)

Yth. Bapak/Ibu Orang Tua/Wali,

Dengan hormat,

Kami menginformasikan bahwa siswa berikut:

Nama : {$nama_siswa}
NIS  : {$nis_siswa}

telah mencapai total {$totalPoin} poin pelanggaran tata tertib sekolah dan memperoleh Surat Peringatan 2 (SP2).

Sehubungan dengan hal tersebut, kami memohon Bapak/Ibu untuk segera berkoordinasi dengan wali kelas atau guru BK guna melakukan pembinaan dan tindak lanjut yang diperlukan.

Terima kasih atas perhatian dan kerja samanya.

Hormat kami,

SMKN 24 Jakarta
Sistem Tata Tertib Sekolah";

        $resultWA = kirimWA($no_ortu, $pesan);

error_log('WA RESULT: ' . print_r($resultWA, true));

        supabase('riwayat_peringatan', 'POST', [
            'siswa_id'   => $siswa_id,
            'jenis_sp'   => 'SP2',
            'total_poin' => $totalPoin
        ]);
    }
}

                $success = 'Pelanggaran berhasil disimpan!';

    } else {

        $error = 'Gagal menyimpan pelanggaran. Coba lagi.';

    }
}
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pelanggaran — SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        @media(max-width:768px){.hamburger-btn{display:block !important}}

        .input-wrap {
            display: grid;
            grid-template-columns: 1fr 260px;
            gap: 20px;
            align-items: start;
        }
        @media(max-width:860px){ .input-wrap{grid-template-columns:1fr} }

        /* Siswa picker button */
        .siswa-picker {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 13px;
            border: 1.5px solid var(--gray-border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            background: var(--white);
            transition: border-color .2s, box-shadow .2s;
            width: 100%;
            text-align: left;
        }
        .siswa-picker:hover { border-color: var(--blue-main); }
        .siswa-picker.selected { border-color: var(--blue-main); background: var(--blue-light); }
        .siswa-picker .placeholder { font-size:13px; color:var(--text-light); }
        .siswa-picker .nama-terpilih { font-size:13px; font-weight:600; color:var(--text-dark); }
        .siswa-picker .nis-terpilih  { font-size:11px; color:var(--text-light); }
        .siswa-picker-avatar {
            width:32px; height:32px; border-radius:50%;
            background:var(--blue-main);
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; overflow:hidden;
        }
        .siswa-picker-avatar img { width:100%; height:100%; object-fit:cover; }
        .siswa-picker-avatar svg { color:#fff; }

        /* Modal lookup */
        .lookup-overlay {
            position:fixed; inset:0;
            background:rgba(0,0,0,.45);
            z-index:500;
            display:flex; align-items:center; justify-content:center;
            padding:20px;
        }
        .lookup-modal {
            background:var(--white);
            border-radius:var(--radius);
            box-shadow:0 24px 80px rgba(0,0,0,.2);
            width:100%; max-width:560px;
            max-height:80vh;
            display:flex; flex-direction:column;
            overflow:hidden;
        }
        .lookup-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:16px 20px;
            border-bottom:1px solid var(--gray-border);
        }
        .lookup-header h3 { font-size:15px; font-weight:700; }
        .lookup-search {
            padding:12px 16px;
            border-bottom:1px solid var(--gray-border);
        }
        .lookup-search input {
            width:100%; padding:9px 13px;
            border:1.5px solid var(--gray-border);
            border-radius:var(--radius-sm);
            font-size:13px; font-family:var(--font);
            outline:none;
            transition:border-color .2s;
        }
        .lookup-search input:focus { border-color:var(--blue-main); }
        .lookup-list {
            overflow-y:auto; flex:1;
        }
        .lookup-item {
            display:flex; align-items:center; gap:12px;
            padding:12px 16px;
            border-bottom:1px solid var(--gray-border);
            cursor:pointer;
            transition:background .15s;
        }
        .lookup-item:last-child { border-bottom:none; }
        .lookup-item:hover { background:var(--blue-light); }
        .lookup-item.active { background:var(--blue-light); }
        .lookup-avatar {
            width:38px; height:38px; border-radius:50%;
            background:var(--blue-main);
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; overflow:hidden;
        }
        .lookup-avatar img { width:100%; height:100%; object-fit:cover; }
        .lookup-avatar svg { color:#fff; }
        .lookup-nama  { font-size:13px; font-weight:600; color:var(--text-dark); }
        .lookup-info  { font-size:12px; color:var(--text-light); margin-top:1px; }
        .lookup-empty { padding:32px; text-align:center; color:var(--text-light); font-size:13px; }

        /* Info panel */
        .siswa-panel { background:var(--white); border:1px solid var(--gray-border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; position:sticky; top:calc(var(--navbar-h) + 20px); }
        .siswa-panel-header { background:var(--yellow-light); border-bottom:1px solid #fde68a; padding:12px 16px; font-size:13px; font-weight:700; }
        .siswa-panel-body { padding:20px; text-align:center; }
        .siswa-panel-avatar { width:64px; height:64px; border-radius:50%; background:var(--blue-main); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; overflow:hidden; }
        .siswa-panel-avatar img { width:100%; height:100%; object-fit:cover; }
        .siswa-panel-avatar svg { color:#fff; }
        .panel-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--gray-border); font-size:13px; }
        .panel-row:last-child { border-bottom:none; }
        .panel-row .lbl { color:var(--text-light); }
        .panel-row .val { font-weight:600; color:var(--text-dark); }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__.'/../components/sidebar-operator.php'; ?>
    <div class="main-content">
        <?php $page_title='Input Pelanggaran'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Input Pelanggaran</h1>
                <p>Catat pelanggaran siswa</p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success" data-autohide><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="input-wrap">
                <!-- FORM -->
                <div class="card">
                    <div class="card-body">
                        <form method="POST" id="form-pel">
                            <input type="hidden" name="siswa_id" id="siswa_id">

                            <div class="form-2col" style="margin-bottom:16px">

                                <!-- Pilih Siswa -->
                                <div class="form-group">
                                    <label class="form-label">Pilih Siswa <span style="color:var(--red)">*</span></label>
                                    <div class="siswa-picker" id="siswa-picker" onclick="bukaLookup()">
                                        <div class="siswa-picker-avatar" id="picker-avatar">
                                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="placeholder" id="picker-placeholder">Klik untuk cari siswa...</div>
                                            <div class="nama-terpilih" id="picker-nama" style="display:none"></div>
                                            <div class="nis-terpilih"  id="picker-nis"  style="display:none"></div>
                                        </div>
                                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-left:auto;color:var(--text-light)">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Tanggal -->
                                <div class="form-group">
                                    <label class="form-label">Tanggal <span style="color:var(--red)">*</span></label>
                                    <input type="date" name="tanggal" class="form-control"
                                           value="<?= $_POST['tanggal'] ?? date('Y-m-d') ?>" required>
                                </div>

                                <!-- Jenis Pelanggaran -->
                                <div class="form-group">
                                    <label class="form-label">Jenis Pelanggaran <span style="color:var(--red)">*</span></label>
                                    <select name="tata_tertib_id" id="jenis_pel" class="form-control" required>
                                        <option value="">-- Pilih Jenis Pelanggaran --</option>
                                        <?php foreach ($tt_grouped as $kat => $rules): ?>
                                        <?php if (empty($rules)) continue; ?>
                                        <optgroup label="<?= htmlspecialchars($kat) ?>">
                                            <?php foreach ($rules as $tt): ?>
                                            <option value="<?= $tt['id'] ?>"
                                                    data-tingkat="<?= htmlspecialchars($tt['tingkat']) ?>"
                                                    data-poin="<?= $tt['poin'] ?>">
                                                <?= htmlspecialchars($tt['aturan']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Waktu -->
                                <div class="form-group">
                                    <label class="form-label">Waktu</label>
                                    <input type="time" name="waktu" class="form-control"
                                           value="<?= $_POST['waktu'] ?? date('H:i') ?>">
                                </div>

                                <!-- Tingkat auto fill -->
                                <div class="form-group">
                                    <label class="form-label">Tingkat Pelanggaran</label>
                                    <input type="text" id="tingkat-display" class="form-control"
                                           placeholder="Otomatis terisi" readonly
                                           style="background:var(--gray-bg);cursor:default">
                                </div>

                                <!-- Lokasi -->
                                <div class="form-group">
                                    <label class="form-label">Lokasi</label>
                                    <select name="lokasi" class="form-control">
                                        <option value="">-- Pilih Lokasi --</option>
                                        <?php foreach ($lokasi_list as $lok): ?>
                                        <option value="<?= $lok ?>"><?= $lok ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom:14px">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3"
                                          placeholder="Tambahkan keterangan pelanggaran..."></textarea>
                            </div>

                            <div class="form-group" style="margin-bottom:16px">
                                <label class="form-label">Sanksi</label>
                                <input type="text" name="sanksi" class="form-control"
                                       placeholder="Contoh: Teguran lisan, Membersihkan kelas, dll">
                            </div>

                            <div class="form-hint">
                                Pastikan data yang diinput sudah benar sebelum disimpan.
                            </div>

                            <div class="form-actions" style="margin-top:16px">
                                <button type="button" class="btn btn-outline" onclick="resetForm()">Reset</button>
                                <button type="submit" class="btn btn-primary" style="flex:1">Simpan Pelanggaran</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- PANEL INFO SISWA -->
                <div class="siswa-panel">
                    <div class="siswa-panel-header">Informasi Siswa</div>
                    <div class="siswa-panel-body">
                        <div class="siswa-panel-avatar" id="panel-avatar">
                            <svg width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div id="panel-empty" style="color:var(--text-light);font-size:13px;padding:8px 0">
                            Pilih siswa untuk melihat informasi
                        </div>
                        <div id="panel-info" style="display:none;text-align:left">
                            <div class="panel-row"><span class="lbl">Nama</span>  <span class="val" id="p-nama">-</span></div>
                            <div class="panel-row"><span class="lbl">NIS</span>   <span class="val" id="p-nis">-</span></div>
                            <div class="panel-row"><span class="lbl">Kelas</span> <span class="val" id="p-kelas">-</span></div>
                            <div class="panel-row"><span class="lbl">Status</span><span class="val">Pelajar</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LOOKUP SISWA -->
<div class="lookup-overlay" id="lookup-overlay" style="display:none" onclick="tutupLookup(event)">
    <div class="lookup-modal">
        <div class="lookup-header">
            <h3>Cari Siswa</h3>
            <button onclick="tutupLookup()" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--text-light)">&times;</button>
        </div>
        <div class="lookup-search">
            <input type="text" id="lookup-input" placeholder="Ketik nama atau NIS siswa..." oninput="filterSiswa(this.value)">
        </div>
        <div class="lookup-list" id="lookup-list">
            <?php if (empty($siswa_all)): ?>
            <div class="lookup-empty">Belum ada data siswa.</div>
            <?php else: ?>
            <?php foreach ($siswa_all as $s):
                $profil = $s['profil_siswa'][0] ?? [];
                $kelas  = is_array($profil['kelas_id'] ?? null) ? $profil['kelas_id']['nama_kelas'] : '-';
            ?>
            <div class="lookup-item" 
                 data-id="<?= $s['id'] ?>"
                 data-nama="<?= htmlspecialchars($s['nama_lengkap']) ?>"
                 data-nis="<?= htmlspecialchars($s['nis_nip']) ?>"
                 data-kelas="<?= htmlspecialchars($kelas) ?>"
                 data-foto="<?= htmlspecialchars($s['foto'] ?? '') ?>"
                 onclick="pilihSiswa(this)">
                <div class="lookup-avatar">
                    <?php if ($s['foto']): ?>
                        <img src="../<?= htmlspecialchars($s['foto']) ?>" alt="">
                    <?php else: ?>
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="lookup-nama"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                    <div class="lookup-info"><?= htmlspecialchars($s['nis_nip']) ?> &bull; <?= htmlspecialchars($kelas) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
// ── Lookup modal ──────────────────────────────────────────────
function bukaLookup() {
    document.getElementById('lookup-overlay').style.display = 'flex';
    setTimeout(() => document.getElementById('lookup-input').focus(), 100);
}

function tutupLookup(e) {
    if (!e || e.target === document.getElementById('lookup-overlay')) {
        document.getElementById('lookup-overlay').style.display = 'none';
        document.getElementById('lookup-input').value = '';
        filterSiswa('');
    }
}

function filterSiswa(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.lookup-item').forEach(item => {
        const match = item.dataset.nama.toLowerCase().includes(q)
                   || item.dataset.nis.toLowerCase().includes(q)
                   || item.dataset.kelas.toLowerCase().includes(q);
        item.style.display = match ? 'flex' : 'none';
    });
}

function pilihSiswa(el) {
    const id    = el.dataset.id;
    const nama  = el.dataset.nama;
    const nis   = el.dataset.nis;
    const kelas = el.dataset.kelas;
    const foto  = el.dataset.foto;

    // Set hidden input
    document.getElementById('siswa_id').value = id;

    // Update picker
    const picker = document.getElementById('siswa-picker');
    picker.classList.add('selected');
    document.getElementById('picker-placeholder').style.display = 'none';
    document.getElementById('picker-nama').style.display = 'block';
    document.getElementById('picker-nis').style.display  = 'block';
    document.getElementById('picker-nama').textContent = nama;
    document.getElementById('picker-nis').textContent  = nis + ' · ' + kelas;

    const pickerAvatar = document.getElementById('picker-avatar');
    if (foto) {
        pickerAvatar.innerHTML = `<img src="../${foto}" alt="">`;
    }

    // Update panel info
    document.getElementById('panel-empty').style.display = 'none';
    document.getElementById('panel-info').style.display  = 'block';
    document.getElementById('p-nama').textContent  = nama;
    document.getElementById('p-nis').textContent   = nis;
    document.getElementById('p-kelas').textContent = kelas;

    const panelAvatar = document.getElementById('panel-avatar');
    if (foto) {
        panelAvatar.innerHTML = `<img src="../${foto}" alt="" style="width:100%;height:100%;object-fit:cover">`;
    }

    // Tutup modal
    document.getElementById('lookup-overlay').style.display = 'none';
    document.getElementById('lookup-input').value = '';
    filterSiswa('');
}

// ── Auto-fill tingkat ────────────────────────────────────────
document.getElementById('jenis_pel').addEventListener('change', function () {
    const opt     = this.options[this.selectedIndex];
    const tingkat = opt?.dataset?.tingkat || '';
    const poin    = opt?.dataset?.poin    || '';
    document.getElementById('tingkat-display').value = tingkat ? `${tingkat} (${poin} poin)` : '';
});

// ── Reset form ────────────────────────────────────────────────
function resetForm() {
    document.getElementById('form-pel').reset();
    document.getElementById('siswa_id').value = '';
    document.getElementById('tingkat-display').value = '';

    // Reset picker
    document.getElementById('siswa-picker').classList.remove('selected');
    document.getElementById('picker-placeholder').style.display = 'block';
    document.getElementById('picker-nama').style.display = 'none';
    document.getElementById('picker-nis').style.display  = 'none';
    document.getElementById('picker-avatar').innerHTML = `
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>`;

    // Reset panel
    document.getElementById('panel-empty').style.display = 'block';
    document.getElementById('panel-info').style.display  = 'none';
    document.getElementById('panel-avatar').innerHTML = `
        <svg width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>`;
}

// Validasi sebelum submit
document.getElementById('form-pel').addEventListener('submit', function(e) {
    if (!document.getElementById('siswa_id').value) {
        e.preventDefault();
        alert('Pilih siswa terlebih dahulu!');
    }
});
</script>
</body>
</html>
