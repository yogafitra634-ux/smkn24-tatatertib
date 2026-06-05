<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('siswa');

$user        = current_user();
$active_page = 'profil';
$success = $error = '';

// Refresh data user terbaru dari DB (agar foto terbaru tampil, bukan dari session lama)
$user_db = supabase_first('users?id=eq.' . $user['id'] . '&select=id,nis_nip,nama_lengkap,email,foto,role,is_active');
if ($user_db) {
    // Merge: data DB menimpa session untuk field yang penting
    $user = array_merge($user, $user_db);
    // Update session supaya konsisten
    $_SESSION['user']['foto']        = $user_db['foto'];
    $_SESSION['user']['nama_lengkap'] = $user_db['nama_lengkap'];
}

// Ambil profil siswa beserta data kelas
$profil_res = supabase_first(
    'profil_siswa?user_id=eq.' . $user['id']
    . '&select=*,kelas_id(nama_kelas,jurusan,tingkat,wali_kelas)'
);
$profil = $profil_res ?? [];
$kelas  = $profil['kelas_id'] ?? [];

// Ringkasan pelanggaran
$rk = supabase_first('v_ringkasan_siswa?siswa_id=eq.' . $user['id'] . '&select=*');
$ringan = (int)($rk['jumlah_ringan'] ?? 0);
$sedang = (int)($rk['jumlah_sedang'] ?? 0);
$berat  = (int)($rk['jumlah_berat']  ?? 0);
$poin   = (int)($rk['total_poin']    ?? 0);

function status_label(int $p): array {
    if ($p === 0)  return ['Sempurna','baik'];
    if ($p <= 20)  return ['Baik','baik'];
    if ($p <= 50)  return ['Cukup','sedang'];
    return              ['Buruk','buruk'];
}
[$st_label, $st_class] = status_label($poin);

// tgl_indo() tersedia dari includes/functions.php

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya — SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        @media(max-width:768px){.hamburger-btn{display:block !important}}
        .profil-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        @media(max-width:860px){ .profil-grid{grid-template-columns:1fr} }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__ . '/../components/sidebar-siswa.php'; ?>
    <div class="main-content">
        <?php $page_title='Profil Saya'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Profil Saya</h1>
                <p>Informasi profil dan status kedisiplinan Anda</p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success" data-autohide><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Card atas: avatar + status -->
            <div class="card" style="margin-bottom:20px">
                <div style="display:flex;align-items:center;gap:24px;padding:24px;flex-wrap:wrap;border-bottom:1px solid var(--gray-border)">
                    <!-- Avatar -->
                    <div class="profil-avatar" style="width:90px;height:90px">
                        <?php $foto_src = !empty($user['foto']) ? foto_url($user['foto'], 1) : ''; ?>
                        <?php if ($foto_src): ?>
                            <img src="<?= htmlspecialchars($foto_src) ?>"
                                 alt="Foto Profil"
                                 style="width:100%;height:100%;object-fit:cover;border-radius:50%"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <svg style="display:none;width:44px;height:44px" fill="none" viewBox="0 0 24 24" stroke="#fff">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        <?php else: ?>
                            <svg width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="#fff">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <!-- Info dasar -->
                    <div style="flex:1">
                        <div style="font-size:20px;font-weight:800;color:var(--text-dark)"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                        <div style="font-size:13px;color:var(--text-light);margin-top:2px">NIS: <?= htmlspecialchars($user['nis_nip']) ?></div>
                        <div style="font-size:13px;color:var(--text-mid);font-weight:600;margin-top:4px">
                            Kelas: <?= htmlspecialchars($kelas['nama_kelas'] ?? '-') ?> &nbsp;|&nbsp;
                            Jurusan: <?= htmlspecialchars($kelas['jurusan'] ?? '-') ?>
                        </div>
                        <div style="font-size:12px;color:var(--text-light);margin-top:2px">
                            Wali Kelas: <?= htmlspecialchars($kelas['wali_kelas'] ?? '-') ?>
                        </div>
                    </div>
                    <!-- Status -->
                    <div style="text-align:center;padding:14px 24px;background:var(--green-light);border-radius:var(--radius);border:1px solid #86efac;min-width:160px">
                        <div style="font-size:12px;color:var(--text-mid);font-weight:600">Status Kedisiplinan Anda</div>
                        <div style="font-size:22px;font-weight:800;color:<?= $st_class==='baik'?'#16a34a':($st_class==='sedang'?'#f97316':'#ef4444') ?>;margin-top:4px"><?= $st_label ?></div>
                        <div style="font-size:12px;color:var(--text-mid);margin-top:2px"><?= $poin ?> poin pelanggaran</div>
                    </div>
                </div>
                <!-- Ringkasan -->
                <div style="padding:16px 24px">
                    <div style="font-size:13px;font-weight:700;color:var(--text-dark);margin-bottom:12px">Ringkasan Pelanggaran</div>
                    <div class="ringkasan-grid">
                        <div class="ringkasan-item ringan">
                            <div class="r-label">Ringan</div>
                            <div class="r-value"><?= $ringan ?></div>
                        </div>
                        <div class="ringkasan-item sedang">
                            <div class="r-label">Sedang</div>
                            <div class="r-value"><?= $sedang ?></div>
                        </div>
                        <div class="ringkasan-item berat">
                            <div class="r-label">Berat</div>
                            <div class="r-value"><?= $berat ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Pribadi -->
            <div class="card">
                <div class="card-body">
                    <div style="color:var(--blue-main);font-size:15px;font-weight:700;margin-bottom:16px">Informasi Pribadi</div>
                    <table class="info-table">
                        <tr><td>Nama Lengkap</td>     <td><?= htmlspecialchars($user['nama_lengkap']) ?></td></tr>
                        <tr><td>Tempat, Tanggal Lahir</td><td><?= htmlspecialchars($profil['tempat_lahir'] ?? '-') ?>, <?= tgl_indo($profil['tanggal_lahir'] ?? null) ?></td></tr>
                        <tr><td>Jenis Kelamin</td>    <td><?= htmlspecialchars($profil['jenis_kelamin'] ?? '-') ?></td></tr>
                        <tr><td>Agama</td>            <td><?= htmlspecialchars($profil['agama'] ?? '-') ?></td></tr>
                        <tr><td>Alamat</td>           <td><?= htmlspecialchars($profil['alamat'] ?? '-') ?></td></tr>
                        <tr><td>No. Telepon</td>      <td><?= htmlspecialchars($profil['no_telepon'] ?? '-') ?></td></tr>
                        <tr><td>Email</td>            <td><?= htmlspecialchars($user['email']) ?></td></tr>
                        <tr><td>Nama Orang Tua</td>   <td><?= htmlspecialchars($profil['nama_orang_tua'] ?? '-') ?></td></tr>
                        <tr><td>No. Telepon Orang Tua</td><td><?= htmlspecialchars($profil['no_telepon_orang_tua'] ?? '-') ?></td></tr>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
