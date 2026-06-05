<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth.php';

redirect_if_logged_in();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis_nip  = trim($_POST['nis_nip']  ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    $result = login($nis_nip, $email, $password);

    if ($result['success']) {
        redirect_by_role($result['role']);
    } else {
        $error = $result['message'];
    }
}

$current_user_session = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistem Tata Tertib SMKN 24</title>
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

        /* ── LEFT PANEL ── */
        .panel-left {
            width: 46%;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        /* Foto gedung sebagai background penuh */
        .panel-left .bg-gedung {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        /* Overlay gradien agar teks terbaca */
        .panel-left .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg,
                rgba(20,40,160,.45) 0%,
                rgba(10,20,100,.75) 55%,
                rgba(5,10,70,.92)  100%
            );
        }

        /* Konten di atas overlay */
        .panel-left-content {
            position: relative;
            z-index: 2;
            padding: 0 32px 36px;
        }

        .school-logo {
            width: 64px; height: 64px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--yellow);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 800; color: var(--blue-dark);
            box-shadow: 0 4px 20px rgba(245,200,0,.5);
            margin-bottom: 14px;
        }
        .school-logo img { width:100%; height:100%; object-fit:cover; }

        .school-title    { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: .4px; line-height:1.3; }
        .school-subtitle { font-size: 20px; font-weight: 800; color: var(--yellow); letter-spacing: 1px; margin-top: 2px; }
        .school-desc     { font-size: 13px; color: rgba(255,255,255,.75); margin-top: 8px; line-height: 1.65; }

        /* Tag kecil di sudut kiri atas */
        .panel-left-badge {
            position: absolute;
            top: 20px; left: 20px;
            z-index: 2;
            background: rgba(255,255,255,.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
        }

        /* ── RIGHT PANEL ── */
        .panel-right {
            width: 54%;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 52px;
        }

        .form-heading { text-align: center; margin-bottom: 28px; }
        .form-heading h1 { font-size: 26px; font-weight: 800; color: var(--blue-dark); }
        .form-heading h1 span { color: var(--yellow); }
        .form-heading p { font-size: 13px; color: var(--text-mid); margin-top: 4px; }

        .alert {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 14px; border-radius: 10px;
            font-size: 13px; margin-bottom: 16px;
        }
        .alert-error   { background:#fff5f5; border:1px solid #fca5a5; color:var(--red); }
        .alert-info    { background:#eff6ff; border:1px solid #bfdbfe; color:var(--blue-main); }
        .alert-success { background:#f0fff4; border:1px solid #86efac; color:#16a34a; }

        .form-group { display:flex; flex-direction:column; margin-bottom:16px; }
        .form-group label { font-size:13px; font-weight:600; color:var(--text-dark); margin-bottom:5px; }
        .form-group input {
            padding: 11px 14px;
            border: 1.5px solid var(--gray-border);
            border-radius: 10px;
            font-size: 14px; font-family: var(--font);
            color: var(--text-dark); outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-group input::placeholder { color: var(--text-light); }
        .form-group input:focus {
            border-color: var(--blue-main);
            box-shadow: 0 0 0 3px rgba(30,60,190,.1);
        }

        .btn-primary {
            width: 100%; padding: 13px;
            background: var(--yellow); color: var(--text-dark);
            font-family: var(--font); font-size: 15px; font-weight: 700;
            border: none; border-radius: 10px;
            cursor: pointer; margin-top: 4px;
            transition: background .2s, transform .1s;
        }
        .btn-primary:hover  { background: var(--yellow-hover); }
        .btn-primary:active { transform: scale(.98); }

        .form-footer { text-align:center; margin-top:14px; font-size:13px; color:var(--text-mid); }
        .form-footer a { color:var(--blue-main); font-weight:700; text-decoration:none; }
        .form-footer a:hover { text-decoration:underline; }

        @media(max-width:720px) {
            .auth-card   { flex-direction:column; width:96vw; border-radius:16px; }
            .panel-left  { width:100%; min-height:200px; }
            .panel-right { width:100%; padding:32px 24px; }
            .panel-left-content { padding:0 24px 28px; }
        }
    </style>
</head>
<body>

<div class="auth-card">

    <!-- LEFT: Foto Gedung -->
    <div class="panel-left">
        <!-- Foto gedung -->
        <img src="assets/img/gedung.png" alt="Gedung SMKN 24" class="bg-gedung"
             onerror="this.onerror=null;this.src='assets/img/gedung.jpg'">

        <!-- Overlay -->
        <div class="overlay"></div>

        <!-- Badge atas -->
        <div class="panel-left-badge">🏫 SMKN 24 Jakarta</div>

        <!-- Konten bawah -->
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
            <p>Lengkapi data berikut untuk masuk</p>
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

        <?php if ($current_user_session): ?>
        <div class="alert alert-info">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Sudah login sebagai <strong><?= htmlspecialchars($current_user_session['nama_lengkap']) ?></strong>.
            Login di bawah untuk akun lain.
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php<?= isset($_GET['force']) ? '?force=1' : '' ?>" novalidate>
            <div class="form-group">
                <label for="nis_nip">NIS/NIP</label>
                <input type="text" id="nis_nip" name="nis_nip"
                       placeholder="Masukkan NIS/NIP Anda"
                       value="<?= htmlspecialchars($_POST['nis_nip'] ?? '') ?>"
                       required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       placeholder="Masukkan Email Anda"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Masukkan Password"
                       required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <p class="form-footer">
            Belum punya akun? <a href="register.php">Sign Up</a>
        </p>
        
    </div>

</div>

</body>
</html>
