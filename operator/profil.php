<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('operator');

$user        = current_user();
$active_page = 'profil';
$success = $error = '';

// ── Refresh data user terbaru dari DB ─────────────────────────
$user_db = supabase_first('users?id=eq.' . $user['id'] . '&select=id,nis_nip,nama_lengkap,email,foto,role,is_active');
if ($user_db) {
    $user = array_merge($user, $user_db);
    $_SESSION['user']['nama_lengkap'] = $user_db['nama_lengkap'];
    $_SESSION['user']['email']        = $user_db['email'];
    $_SESSION['user']['foto']         = $user_db['foto'];
}

// ── Proses form submit ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // ── Update profil (nama & email) ──────────────────────────
    if ($aksi === 'update_profil') {
        $nama_baru  = trim($_POST['nama_lengkap'] ?? '');
        $email_baru = trim($_POST['email']        ?? '');

        if (empty($nama_baru) || empty($email_baru)) {
            $error = 'Nama lengkap dan email tidak boleh kosong.';
        } elseif (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Cek email duplikat (kecuali milik sendiri)
            $cek = supabase('users?email=eq.' . urlencode($email_baru) . '&id=neq.' . $user['id'] . '&select=id&limit=1');
            if (!empty($cek['data'])) {
                $error = 'Email sudah digunakan akun lain.';
            } else {
                $r = supabase('users?id=eq.' . $user['id'], 'PATCH', [
                    'nama_lengkap' => $nama_baru,
                    'email'        => $email_baru,
                ]);
                if ($r['status'] === 200) {
                    $_SESSION['user']['nama_lengkap'] = $nama_baru;
                    $_SESSION['user']['email']        = $email_baru;
                    $user['nama_lengkap'] = $nama_baru;
                    $user['email']        = $email_baru;
                    $success = 'Profil berhasil diperbarui.';
                } else {
                    $error = 'Gagal memperbarui profil. Coba lagi.';
                }
            }
        }
    }

    // ── Ganti password ────────────────────────────────────────
    if ($aksi === 'ganti_password') {
        $pw_lama    = $_POST['password_lama']    ?? '';
        $pw_baru    = $_POST['password_baru']    ?? '';
        $pw_konfirm = $_POST['password_konfirm'] ?? '';

        if (empty($pw_lama) || empty($pw_baru) || empty($pw_konfirm)) {
            $error = 'Semua field password wajib diisi.';
        } elseif (strlen($pw_baru) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($pw_baru !== $pw_konfirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            // Ambil password hash dari DB
            $db_user = supabase_first('users?id=eq.' . $user['id'] . '&select=password');
            if (!$db_user || !password_verify($pw_lama, $db_user['password'])) {
                $error = 'Password lama salah.';
            } else {
                $r = supabase('users?id=eq.' . $user['id'], 'PATCH', [
                    'password' => password_hash($pw_baru, PASSWORD_BCRYPT),
                ]);
                if ($r['status'] === 200) {
                    $success = 'Password berhasil diperbarui.';
                } else {
                    $error = 'Gagal memperbarui password. Coba lagi.';
                }
            }
        }
    }
}

function eye_icon(): string {
    return '<svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                 -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
    </svg>';
}
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

        .profil-header {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 24px;
            border-bottom: 1px solid var(--gray-border);
            flex-wrap: wrap;
        }
        .profil-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: var(--blue-main);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; overflow: hidden;
        }
        .profil-avatar img { width:100%; height:100%; object-fit:cover; }
        .profil-meta-name  { font-size:20px; font-weight:800; color:var(--text-dark); }
        .profil-meta-role  { font-size:13px; color:var(--text-light); margin-top:3px; }
        .profil-meta-nip   { font-size:13px; color:var(--text-mid); font-weight:600; margin-top:2px; }

        .profil-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:20px; }
        @media(max-width:860px){ .profil-grid{grid-template-columns:1fr} }

        .form-section-title {
            font-size:13px; font-weight:700; color:var(--blue-main);
            padding-bottom:8px;
            border-bottom:2px solid var(--blue-main);
            margin-bottom:16px;
        }
        .form-group { display:flex; flex-direction:column; gap:5px; margin-bottom:14px; }
        .form-group label { font-size:13px; font-weight:600; color:var(--text-dark); }
        .form-group input {
            padding:10px 13px;
            border:1.5px solid var(--gray-border);
            border-radius:var(--radius-sm);
            font-size:13px; font-family:var(--font);
            color:var(--text-dark); outline:none;
            transition:border-color .2s, box-shadow .2s;
            background:var(--white); width:100%;
        }
        .form-group input:focus {
            border-color:var(--blue-main);
            box-shadow:0 0 0 3px rgba(30,60,190,.08);
        }
        .form-group input[readonly] {
            background:var(--gray-bg);
            color:var(--text-light);
            cursor:not-allowed;
        }
        .form-group .hint { font-size:11px; color:var(--text-light); margin-top:2px; }

        .pw-wrap { position:relative; }
        .pw-wrap input { padding-right:42px; }
        .pw-toggle {
            position:absolute; right:12px; top:50%; transform:translateY(-50%);
            background:none; border:none; cursor:pointer; padding:0;
            color:var(--text-light); line-height:1; transition:color .2s;
        }
        .pw-toggle:hover { color:var(--blue-main); }

        .strength-bar {
            height:4px; border-radius:2px;
            background:var(--gray-border);
            margin-top:6px; overflow:hidden;
        }
        .strength-bar-fill { height:100%; border-radius:2px; transition:width .3s, background .3s; width:0; }
        .strength-label { font-size:11px; margin-top:3px; min-height:16px; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__.'/../components/sidebar-operator.php'; ?>
    <div class="main-content">
        <?php $page_title='Profil Saya'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">

            <div class="page-header">
                <h1>Profil Saya</h1>
                <p>Kelola informasi akun Anda</p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success" data-autohide><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Card header profil -->
            <div class="card" style="margin-bottom:20px">
                <div class="profil-header">
                    <div class="profil-avatar">
                        <?php $foto_src = !empty($user['foto']) ? foto_url($user['foto'], 1) : ''; ?>
                        <?php if ($foto_src): ?>
                            <img src="<?= htmlspecialchars($foto_src) ?>" alt="Foto"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <svg style="display:none;width:36px;height:36px" fill="none" viewBox="0 0 24 24" stroke="#fff">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        <?php else: ?>
                            <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="#fff">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="profil-meta-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                        <div class="profil-meta-role">Operator / Guru</div>
                        <div class="profil-meta-nip">NIP: <?= htmlspecialchars($user['nis_nip']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Grid dua kolom -->
            <div class="profil-grid">

                <!-- Kolom kiri: Edit Profil -->
                <div class="card">
                    <div class="card-body">
                        <div class="form-section-title">✏️ Edit Profil</div>
                        <form method="POST" action="profil.php">
                            <input type="hidden" name="aksi" value="update_profil">

                            <div class="form-group">
                                <label>NIP (tidak dapat diubah)</label>
                                <input type="text" value="<?= htmlspecialchars($user['nis_nip']) ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label for="nama_lengkap">Nama Lengkap <span style="color:#ef4444">*</span></label>
                                <input type="text" name="nama_lengkap" id="nama_lengkap"
                                       value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? $user['nama_lengkap']) ?>"
                                       placeholder="Nama lengkap Anda" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email / Gmail <span style="color:#ef4444">*</span></label>
                                <input type="email" name="email" id="email"
                                       value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                                       placeholder="contoh@gmail.com" required>
                                <span class="hint">Email digunakan untuk login. Pastikan aktif.</span>
                            </div>

                            <button type="submit" class="btn btn-blue" style="width:100%;margin-top:4px">
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Kolom kanan: Ganti Password -->
                <div class="card">
                    <div class="card-body">
                        <div class="form-section-title">🔒 Ganti Password</div>
                        <form method="POST" action="profil.php" id="form-password">
                            <input type="hidden" name="aksi" value="ganti_password">

                            <div class="form-group">
                                <label for="password_lama">Password Lama <span style="color:#ef4444">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" name="password_lama" id="password_lama"
                                           placeholder="Masukkan password lama" required>
                                    <button type="button" class="pw-toggle" onclick="togglePw('password_lama',this)">
                                        <?= eye_icon() ?>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password_baru">Password Baru <span style="color:#ef4444">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" name="password_baru" id="password_baru"
                                           placeholder="Minimal 6 karakter" required
                                           oninput="cekKekuatan(this.value)">
                                    <button type="button" class="pw-toggle" onclick="togglePw('password_baru',this)">
                                        <?= eye_icon() ?>
                                    </button>
                                </div>
                                <div class="strength-bar"><div class="strength-bar-fill" id="strength-fill"></div></div>
                                <div class="strength-label" id="strength-label"></div>
                            </div>

                            <div class="form-group">
                                <label for="password_konfirm">Konfirmasi Password <span style="color:#ef4444">*</span></label>
                                <div class="pw-wrap">
                                    <input type="password" name="password_konfirm" id="password_konfirm"
                                           placeholder="Ulangi password baru" required
                                           oninput="cekKonfirm(this.value)">
                                    <button type="button" class="pw-toggle" onclick="togglePw('password_konfirm',this)">
                                        <?= eye_icon() ?>
                                    </button>
                                </div>
                                <div class="hint" id="konfirm-hint"></div>
                            </div>

                            <button type="submit" class="btn btn-blue" style="width:100%;margin-top:4px">
                                Perbarui Password
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    input.type  = input.type === 'password' ? 'text' : 'password';
    btn.style.color = input.type === 'text' ? 'var(--blue-main)' : '';
}

function cekKekuatan(val) {
    const fill  = document.getElementById('strength-fill');
    const label = document.getElementById('strength-label');
    let score   = 0;
    if (val.length >= 6)                         score++;
    if (val.length >= 10)                        score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val))                       score++;
    if (/[^A-Za-z0-9]/.test(val))               score++;
    const map = [
        {w:'0%',   c:'',         t:''},
        {w:'25%',  c:'#ef4444',  t:'Lemah'},
        {w:'50%',  c:'#f97316',  t:'Cukup'},
        {w:'75%',  c:'#eab308',  t:'Baik'},
        {w:'100%', c:'#22c55e',  t:'Kuat'},
    ];
    const s = Math.min(score, 4);
    fill.style.width      = map[s].w;
    fill.style.background = map[s].c;
    label.textContent     = map[s].t;
    label.style.color     = map[s].c;
}

function cekKonfirm(val) {
    const pw   = document.getElementById('password_baru').value;
    const hint = document.getElementById('konfirm-hint');
    if (!val) { hint.textContent = ''; return; }
    if (val === pw) {
        hint.textContent = '✓ Password cocok';
        hint.style.color = '#16a34a';
    } else {
        hint.textContent = '✗ Password tidak cocok';
        hint.style.color = '#ef4444';
    }
}

document.getElementById('form-password').addEventListener('submit', function(e) {
    const baru    = document.getElementById('password_baru').value;
    const konfirm = document.getElementById('password_konfirm').value;
    if (baru !== konfirm) {
        e.preventDefault();
        document.getElementById('konfirm-hint').textContent = '✗ Password tidak cocok';
        document.getElementById('konfirm-hint').style.color = '#ef4444';
        document.getElementById('password_konfirm').focus();
    }
});
</script>
</body>
</html>
