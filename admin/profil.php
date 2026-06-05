<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$user        = current_user();
$active_page = 'profil';
$success = $error = '';

// Update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'update_profil') {
    $nama  = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email']        ?? '');

    if (empty($nama) || empty($email)) {
        $error = 'Nama dan email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $payload = ['nama_lengkap' => $nama, 'email' => $email];

        // Upload foto baru kalau ada
        if (!empty($_FILES['foto']['name'])) {
            $upload = upload_foto($_FILES['foto']);
            if (!$upload['success']) {
                $error = $upload['message'];
            } else {
                $payload['foto'] = $upload['path'];
            }
        }

        if (!$error) {
            $r = supabase('users?id=eq.' . $user['id'], 'PATCH', $payload);
            if ($r['status'] === 200) {
                // Update session
                $_SESSION['user']['nama_lengkap'] = $nama;
                $_SESSION['user']['email']        = $email;
                if (isset($payload['foto'])) $_SESSION['user']['foto'] = $payload['foto'];
                $user    = current_user();
                $success = 'Profil berhasil diperbarui.';
            } else {
                $error = 'Gagal memperbarui profil.';
            }
        }
    }
}

// Ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ganti_password') {
    $pass_lama  = $_POST['password_lama']  ?? '';
    $pass_baru  = $_POST['password_baru']  ?? '';
    $pass_konfirm = $_POST['password_konfirm'] ?? '';

    if (empty($pass_lama) || empty($pass_baru)) {
        $error = 'Semua field password wajib diisi.';
    } elseif ($pass_baru !== $pass_konfirm) {
        $error = 'Password baru dan konfirmasi tidak sama.';
    } elseif (strlen($pass_baru) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        // Ambil password lama dari DB
        $res = supabase('users?id=eq.' . $user['id'] . '&select=password&limit=1');
        $db_pass = $res['data'][0]['password'] ?? '';

        if (!password_verify($pass_lama, $db_pass)) {
            $error = 'Password lama salah.';
        } else {
            $r = supabase('users?id=eq.' . $user['id'], 'PATCH', [
                'password' => password_hash($pass_baru, PASSWORD_BCRYPT)
            ]);
            $success = $r['status'] === 200 ? 'Password berhasil diubah.' : 'Gagal mengubah password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin — SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        @media(max-width:768px){.hamburger-btn{display:block !important}}
        .profil-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        @media(max-width:860px){ .profil-grid{grid-template-columns:1fr} }
        .foto-edit-wrap {
            position:relative; width:90px; height:90px;
            border-radius:50%; overflow:hidden;
            cursor:pointer; flex-shrink:0;
        }
        .foto-edit-wrap img,
        .foto-edit-wrap .placeholder {
            width:100%; height:100%; object-fit:cover;
            border-radius:50%;
        }
        .foto-edit-overlay {
            position:absolute; inset:0;
            background:rgba(0,0,0,.45);
            border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            opacity:0; transition:opacity .2s;
        }
        .foto-edit-wrap:hover .foto-edit-overlay { opacity:1; }
        .foto-edit-overlay span { color:#fff; font-size:11px; font-weight:600; text-align:center; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__.'/../components/sidebar-admin.php'; ?>
    <div class="main-content">
        <?php $page_title='Profil Saya'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Profil Saya</h1>
                <p>Kelola informasi akun administrator</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success" data-autohide><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="profil-grid">

                <!-- CARD: Edit Profil -->
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">Informasi Akun</div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="aksi" value="update_profil">
                            <input type="file"   name="foto" id="foto-input" accept="image/*" style="display:none"
                                   onchange="previewFoto(this)">

                            <!-- Avatar + info atas -->
                            <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--gray-border)">
                                <div class="foto-edit-wrap" onclick="document.getElementById('foto-input').click()">
                                    <?php if ($user['foto']): ?>
                                        <img id="foto-preview" src="<?= foto_url($user['foto']) ?>"
                                             alt="Foto"
                                             onerror="this.src=''; this.style.display='none'">
                                    <?php else: ?>
                                        <div class="placeholder" id="foto-preview"
                                             style="background:var(--blue-main);display:flex;align-items:center;justify-content:center">
                                            <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#fff">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="foto-edit-overlay">
                                        <span>Ganti<br>Foto</span>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size:18px;font-weight:800;color:var(--text-dark)"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                                    <div style="font-size:13px;color:var(--text-light);margin-top:2px"><?= htmlspecialchars($user['email']) ?></div>
                                    <span class="badge badge-ringan" style="margin-top:6px;display:inline-flex">Admin</span>
                                </div>
                            </div>

                            <!-- Form fields -->
                            <div class="form-group" style="margin-bottom:14px">
                                <label class="form-label">NIS/NIP</label>
                                <input type="text" class="form-control"
                                       value="<?= htmlspecialchars($user['nis_nip']) ?>" readonly
                                       style="background:var(--gray-bg);cursor:default">
                            </div>

                            <div class="form-group" style="margin-bottom:14px">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control"
                                       value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                            </div>

                            <div class="form-group" style="margin-bottom:20px">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-full">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>

                <!-- CARD: Ganti Password -->
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">Ganti Password</div>

                        <form method="POST">
                            <input type="hidden" name="aksi" value="ganti_password">

                            <div class="form-group" style="margin-bottom:14px">
                                <label class="form-label">Password Lama</label>
                                <input type="password" name="password_lama" class="form-control"
                                       placeholder="Masukkan password lama" required>
                            </div>

                            <div class="form-group" style="margin-bottom:14px">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password_baru" class="form-control"
                                       placeholder="Minimal 6 karakter" required id="pass-baru">
                            </div>

                            <div class="form-group" style="margin-bottom:20px">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" name="password_konfirm" class="form-control"
                                       placeholder="Ulangi password baru" required id="pass-konfirm">
                                <span id="match-msg" style="font-size:12px;margin-top:4px;display:none"></span>
                            </div>

                            <button type="submit" class="btn btn-blue btn-full">Ganti Password</button>
                        </form>

                        <!-- Info akun -->
                        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--gray-border)">
                            <div style="font-size:13px;font-weight:700;color:var(--text-dark);margin-bottom:12px">Info Akun</div>
                            <table class="info-table">
                                <tr><td>Role</td>      <td>Administrator</td></tr>
                                <tr><td>Status</td>    <td><span class="badge badge-ringan">Aktif</span></td></tr>
                                <tr><td>Bergabung</td> <td><?= tgl_indo($user['created_at'] ?? null) ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
// Preview foto sebelum upload
function previewFoto(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('foto-preview');
        if (preview.tagName === 'IMG') {
            preview.src = e.target.result;
            preview.style.display = 'block';
        } else {
            // Replace div placeholder dengan img
            const img = document.createElement('img');
            img.id  = 'foto-preview';
            img.src = e.target.result;
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%';
            preview.replaceWith(img);
        }
    };
    reader.readAsDataURL(input.files[0]);
}

// Cek kesamaan password
const passBaru    = document.getElementById('pass-baru');
const passKonfirm = document.getElementById('pass-konfirm');
const matchMsg    = document.getElementById('match-msg');

[passBaru, passKonfirm].forEach(el => {
    el?.addEventListener('input', () => {
        if (!passKonfirm.value) { matchMsg.style.display = 'none'; return; }
        const match = passBaru.value === passKonfirm.value;
        matchMsg.style.display = 'block';
        matchMsg.style.color   = match ? '#16a34a' : '#dc2626';
        matchMsg.textContent   = match ? '✓ Password cocok' : '✗ Password tidak cocok';
    });
});
</script>
</body>
</html>
