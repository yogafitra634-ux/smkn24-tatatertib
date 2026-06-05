<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth.php';

redirect_if_logged_in();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis_nip         = trim($_POST['nis_nip']         ?? '');
    $nama_lengkap    = trim($_POST['nama_lengkap']    ?? '');
    $email           = trim($_POST['email']           ?? '');
    $password        = trim($_POST['password']        ?? '');
    $konfirmasi_pass = trim($_POST['konfirmasi_pass'] ?? '');

    if ($password !== $konfirmasi_pass) {
        $error = 'Password dan konfirmasi password tidak sama.';
    } else {
        $foto_path = '';
        if (!empty($_FILES['foto']['name'])) {
            $upload = upload_foto($_FILES['foto']);
            if (!$upload['success']) {
                $error = $upload['message'];
            } else {
                $foto_path = $upload['path'];
            }
        }

        if (!$error) {
            $result = register($nis_nip, $nama_lengkap, $email, $password, $foto_path);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — Sistem Tata Tertib SMKN 24</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue-dark : #1a2fa0;
            --blue-main : #1e3cbe;
            --yellow    : #f5c800;
            --yellow-hover: #e0b600;
            --white     : #ffffff;
            --gray-bg   : #f5f6fa;
            --gray-border:#d8dae8;
            --text-dark : #1a1d2e;
            --text-mid  : #4a4f6a;
            --text-light: #9399b2;
            --red       : #ef4444;
            --green     : #22c55e;
            --font      : 'Plus Jakarta Sans', sans-serif;
        }

        body {
            font-family: var(--font);
            background: var(--gray-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .auth-card {
            display: flex;
            width: 980px;
            min-height: 580px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(30,60,190,.18);
        }

        /* LEFT */
        .panel-left {
            width: 40%;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .panel-left .bg-gedung {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover; object-position: center;
        }
        .panel-left .overlay {
            position: absolute; inset: 0;
            background: linear-gradient(
                180deg,
                rgba(20,40,160,.45) 0%,
                rgba(10,20,100,.75) 55%,
                rgba(5,10,70,.92)  100%
            );
        }
        .panel-left-content {
            position: relative; z-index: 2;
            padding: 0 28px 32px;
        }
        .school-logo {
            width: 56px; height: 56px;
            border-radius: 50%; overflow: hidden;
            background: var(--yellow);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 800; color: var(--blue-dark);
            box-shadow: 0 4px 20px rgba(245,200,0,.5);
            margin-bottom: 12px;
        }
        .school-logo img { width:100%; height:100%; object-fit:cover; }
        .school-title    { font-size: 15px; font-weight: 800; color: #fff; letter-spacing: .3px; line-height:1.3; }
        .school-subtitle { font-size: 18px; font-weight: 800; color: var(--yellow); letter-spacing: 1px; margin-top: 2px; }
        .school-desc     { font-size: 12px; color: rgba(255,255,255,.75); margin-top: 7px; line-height: 1.65; }
        .panel-left-badge {
            position: absolute; top: 20px; left: 20px; z-index: 2;
            background: rgba(255,255,255,.15); backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,.25); border-radius: 20px;
            padding: 5px 14px; font-size: 12px; font-weight: 600; color: #fff;
        }

        /* RIGHT */
        .panel-right {
            width: 60%;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 36px 44px;
        }

        .form-heading { text-align:center; margin-bottom:22px; }
        .form-heading h1 { font-size:24px; font-weight:800; color:var(--blue-dark); }
        .form-heading h1 span { color:var(--yellow); }
        .form-heading p { font-size:13px; color:var(--text-mid); margin-top:3px; }

        .alert {
            display:flex; align-items:center; gap:8px;
            padding:10px 14px; border-radius:10px;
            font-size:13px; margin-bottom:14px;
        }
        .alert-error   { background:#fff5f5; border:1px solid #fca5a5; color:var(--red); }
        .alert-success { background:#f0fff4; border:1px solid #86efac; color:#16a34a; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 16px;
        }
        .form-group { display:flex; flex-direction:column; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size:13px; font-weight:600; color:var(--text-dark); margin-bottom:5px; }
        .form-group input {
            padding: 10px 13px;
            border: 1.5px solid var(--gray-border);
            border-radius: 10px;
            font-size: 13px; font-family: var(--font);
            color: var(--text-dark); outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-group input::placeholder { color: var(--text-light); }
        .form-group input:focus {
            border-color: var(--blue-main);
            box-shadow: 0 0 0 3px rgba(30,60,190,.1);
        }

        /* Foto */
        .row-foto {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px; align-items: start;
        }
        .row-foto .fields { display:flex; flex-direction:column; gap:12px; }
        .foto-wrap { display:flex; flex-direction:column; align-items:center; gap:7px; }
        .foto-preview {
            width:80px; height:80px; border-radius:12px;
            background:#eef0f8;
            border:2px dashed var(--gray-border);
            display:flex; align-items:center; justify-content:center;
            overflow:hidden; cursor:pointer;
            transition:border-color .2s;
        }
        .foto-preview:hover { border-color:var(--blue-main); }
        .foto-preview img   { width:100%; height:100%; object-fit:cover; }
        .btn-upload {
            background:var(--yellow); color:var(--text-dark);
            font-family:var(--font); font-size:12px; font-weight:700;
            border:none; border-radius:8px;
            padding:6px 14px; cursor:pointer; transition:background .2s;
        }
        .btn-upload:hover { background:var(--yellow-hover); }
        #foto-input { display:none; }

        .btn-primary {
            width:100%; padding:12px;
            background:var(--yellow); color:var(--text-dark);
            font-family:var(--font); font-size:15px; font-weight:700;
            border:none; border-radius:10px;
            cursor:pointer; margin-top:14px;
            transition:background .2s, transform .1s;
        }
        .btn-primary:hover  { background:var(--yellow-hover); }
        .btn-primary:active { transform:scale(.98); }

        .form-footer { text-align:center; margin-top:12px; font-size:13px; color:var(--text-mid); }
        .form-footer a { color:var(--blue-main); font-weight:700; text-decoration:none; }
        .form-footer a:hover { text-decoration:underline; }

        @media(max-width:720px) {
            .auth-card   { flex-direction:column; width:96vw; border-radius:16px; }
            .panel-left  { width:100%; min-height:180px; }
            .panel-right { width:100%; padding:28px 20px; }
            .form-grid   { grid-template-columns:1fr; }
            .row-foto    { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<div class="auth-card">

    <!-- LEFT: Foto Gedung -->
    <div class="panel-left">
        <img src="assets/img/gedung.png" alt="Gedung SMKN 24" class="bg-gedung"
             onerror="this.onerror=null;this.src='assets/img/gedung.jpg'">
        <div class="overlay"></div>
        <div class="panel-left-badge">🏫 SMKN 24 Jakarta</div>
        <div class="panel-left-content">
            <div class="school-logo">
                <img src="assets/img/logodupat.png" alt="Logo"
                     onerror="this.parentElement.textContent='24'">
            </div>
            <div class="school-title">SISTEM TATA TERTIB</div>
            <div class="school-subtitle">SMKN 24</div>
            <p class="school-desc">
                Mewujudkan lingkungan sekolah yang disiplin,<br>
                bertanggung jawab, dan berkarakter.
            </p>
        </div>
    </div>

    <!-- RIGHT: Form -->
    <div class="panel-right">
        <div class="form-heading">
            <h1>Selamat <span>Datang</span></h1>
            <p>Lengkapi data berikut untuk membuat akun</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <?= htmlspecialchars($success) ?>
            <a href="login.php" style="margin-left:6px;color:#16a34a;font-weight:700;">Login →</a>
        </div>
        <?php endif; ?>

        <form method="POST" action="register.php" enctype="multipart/form-data" novalidate>
            <div class="form-grid">

                <div class="form-group full">
                    <label for="nis_nip">NIS/NIP</label>
                    <input type="text" id="nis_nip" name="nis_nip"
                           placeholder="Masukkan NIS/NIP Anda"
                           value="<?= htmlspecialchars($_POST['nis_nip'] ?? '') ?>" required>
                </div>

                <div class="row-foto">
                    <div class="fields">
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap"
                                   placeholder="Masukkan nama lengkap"
                                   value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email"
                                   placeholder="Masukkan Email Anda"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="foto-wrap">
                        <label style="font-size:13px;font-weight:600;color:var(--text-dark)">Foto</label>
                        <div class="foto-preview" onclick="document.getElementById('foto-input').click()">
                            <img id="foto-thumb" src="" style="display:none" alt="">
                            <svg id="foto-icon" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#9399b2">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input type="file" id="foto-input" name="foto" accept="image/*">
                        <button type="button" class="btn-upload"
                                onclick="document.getElementById('foto-input').click()">Upload</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                           placeholder="Masukkan Password" required>
                </div>

                <div class="form-group">
                    <label for="konfirmasi_pass">Konfirmasi Password</label>
                    <input type="password" id="konfirmasi_pass" name="konfirmasi_pass"
                           placeholder="Konfirmasi Password" required>
                </div>

            </div>

            <button type="submit" class="btn-primary">Daftar Akun</button>
        </form>

        <p class="form-footer">
            Sudah punya akun? <a href="login.php">Login</a>
        </p>
    </div>

</div>

<script>
document.getElementById('foto-input').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const thumb = document.getElementById('foto-thumb');
        const icon  = document.getElementById('foto-icon');
        thumb.src   = e.target.result;
        thumb.style.display = 'block';
        icon.style.display  = 'none';
    };
    reader.readAsDataURL(file);
});
</script>

</body>
</html>
