<?php
// siswa/lengkapi-profil.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_role('siswa');

$user    = current_user();
$error   = '';
$success = '';

// Ambil data kelas untuk dropdown
$kelas_res  = supabase('kelas?select=id,nama_kelas,jurusan,tingkat&order=tingkat.asc,nama_kelas.asc');
$kelas_list = $kelas_res['data'] ?? [];

// Ambil profil yang sudah ada
$profil = supabase_first('profil_siswa?user_id=eq.' . $user['id'] . '&select=*');

// Cek apakah profil sudah lengkap — kalau sudah, langsung redirect dashboard
if (profil_lengkap($profil)) {
    header('Location: /smkn24-tatatertib/siswa/dashboard.php');
    exit;
}

// Cek role terbaru dari DB — mungkin sudah di-approve jadi operator oleh admin
$cek_role = supabase('users?id=eq.' . $user['id'] . '&select=role&limit=1');
$role_db  = $cek_role['data'][0]['role'] ?? 'siswa';
if ($role_db === 'operator') {
    // Update session dan redirect ke operator
    $_SESSION['user']['role'] = 'operator';
    header('Location: /smkn24-tatatertib/operator/dashboard.php');
    exit;
}

function profil_lengkap(?array $p): bool {
    if (!$p) return false;
    return !empty($p['kelas_id'])
        && !empty($p['tempat_lahir'])
        && !empty($p['tanggal_lahir'])
        && !empty($p['jenis_kelamin'])
        && !empty($p['agama'])
        && !empty($p['alamat'])
        && !empty($p['no_telepon'])
        && !empty($p['nama_orang_tua'])
        && !empty($p['no_telepon_orang_tua']);
}

// Proses submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas_id             = $_POST['kelas_id']             ?? '';
    $tempat_lahir         = trim($_POST['tempat_lahir']     ?? '');
    $tanggal_lahir        = $_POST['tanggal_lahir']         ?? '';
    $jenis_kelamin        = $_POST['jenis_kelamin']         ?? '';
    $agama                = trim($_POST['agama']            ?? '');
    $alamat               = trim($_POST['alamat']           ?? '');
    $no_telepon           = trim($_POST['no_telepon']       ?? '');
    $nama_orang_tua       = trim($_POST['nama_orang_tua']   ?? '');
    $no_telepon_orang_tua = trim($_POST['no_telepon_orang_tua'] ?? '');

    // Validasi
    if (!$kelas_id || !$tempat_lahir || !$tanggal_lahir || !$jenis_kelamin
        || !$agama || !$alamat || !$no_telepon || !$nama_orang_tua || !$no_telepon_orang_tua) {
        $error = 'Semua field wajib diisi.';
    } else {
        $payload = [
            'kelas_id'             => $kelas_id,
            'tempat_lahir'         => $tempat_lahir,
            'tanggal_lahir'        => $tanggal_lahir,
            'jenis_kelamin'        => $jenis_kelamin,
            'agama'                => $agama,
            'alamat'               => $alamat,
            'no_telepon'           => $no_telepon,
            'nama_orang_tua'       => $nama_orang_tua,
            'no_telepon_orang_tua' => $no_telepon_orang_tua,
        ];

        // Update profil_siswa
        $r = supabase(
            'profil_siswa?user_id=eq.' . $user['id'],
            'PATCH',
            $payload
        );

        if ($r['status'] === 200) {
            // Redirect ke dashboard
            header('Location: /smkn24-tatatertib/siswa/dashboard.php');
            exit;
        } else {
            $error = 'Gagal menyimpan profil. Coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lengkapi Profil — SMKN 24</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --blue-main : #1e3cbe;
            --blue-dark : #1a2fa0;
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
            padding: 32px 16px;
        }
        .card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(30,60,190,.12);
            width: 100%;
            max-width: 680px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, var(--blue-dark), var(--blue-main));
            padding: 28px 36px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .logo {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: var(--yellow);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 800;
            color: var(--blue-dark);
            flex-shrink: 0;
        }
        .logo img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
        .header-text .title { font-size: 18px; font-weight: 800; color: #fff; }
        .header-text .sub   { font-size: 13px; color: rgba(255,255,255,.7); margin-top: 2px; }
        .card-body { padding: 32px 36px; }
        .heading { text-align: center; margin-bottom: 24px; }
        .heading h2 { font-size: 20px; font-weight: 800; color: var(--text-dark); }
        .heading p  { font-size: 13px; color: var(--text-mid); margin-top: 4px; }
        .step-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: var(--blue-main);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-error {
            background: #fff5f5;
            border: 1px solid #fca5a5;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            color: var(--red);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 18px;
        }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            font-size: 13px; font-weight: 600; color: var(--text-dark);
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 13px;
            border: 1.5px solid var(--gray-border);
            border-radius: 10px;
            font-size: 13px;
            font-family: var(--font);
            color: var(--text-dark);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            width: 100%;
            background: var(--white);
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder { color: var(--text-light); }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--blue-main);
            box-shadow: 0 0 0 3px rgba(30,60,190,.1);
        }
        textarea { resize: vertical; min-height: 80px; }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--blue-main);
            margin: 20px 0 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--blue-main);
            grid-column: 1 / -1;
        }
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--yellow);
            color: var(--text-dark);
            font-family: var(--font);
            font-size: 15px;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 20px;
            transition: background .2s, transform .1s;
        }
        .btn-submit:hover  { background: var(--yellow-hover); }
        .btn-submit:active { transform: scale(.98); }
        @media (max-width: 560px) {
            .card-body { padding: 24px 20px; }
            .card-header { padding: 20px 24px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="card">
    <!-- Header -->
    <div class="card-header">
        <div class="logo">
            <img src="../assets/img/logodupat.png" alt="Logo" onerror="this.parentElement.textContent='24'">
        </div>
        <div class="header-text">
            <div class="title">SISTEM TATA TERTIB SMKN 24</div>
            <div class="sub">Lengkapi data diri sebelum melanjutkan</div>
        </div>
    </div>

    <!-- Body -->
    <div class="card-body">
        <div class="heading">
            <h2>Lengkapi Profil Anda</h2>
            <p>Halo, <strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong>! Isi data berikut untuk melanjutkan.</p>
        </div>

        <div class="step-info">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Data ini hanya perlu diisi sekali dan dapat diedit di halaman Profil nanti.
        </div>

        <!-- Info khusus guru -->
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;font-size:13px;color:#92400e;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="flex-shrink:0;margin-top:1px">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <strong>Jika Anda adalah Guru/Operator:</strong> Halaman ini muncul karena akun Anda belum diaktivasi sebagai operator.
                Hubungi admin untuk mengubah role Anda. Setelah diaktivasi, <strong>refresh halaman ini</strong> dan Anda akan otomatis diarahkan ke halaman operator.
                <br><br>
                <a href="/smkn24-tatatertib/logout-temp.php"
                   style="color:#92400e;font-weight:700;text-decoration:underline">
                   → Atau klik di sini untuk logout dan login ulang setelah diaktivasi
                </a>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert-error">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="lengkapi-profil.php">
            <div class="form-grid">

                <!-- Data Sekolah -->
                <div class="section-title">📚 Data Sekolah</div>

                <div class="form-group full">
                    <label for="kelas_id">Kelas</label>
                    <select name="kelas_id" id="kelas_id" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelas_list as $k): ?>
                        <option value="<?= $k['id'] ?>"
                            <?= (($_POST['kelas_id'] ?? '') === $k['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kelas']) ?> — <?= htmlspecialchars($k['jurusan']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Data Pribadi -->
                <div class="section-title">👤 Data Pribadi</div>

                <div class="form-group">
                    <label for="tempat_lahir">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" id="tempat_lahir"
                           placeholder="Contoh: Jakarta"
                           value="<?= htmlspecialchars($_POST['tempat_lahir'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="tanggal_lahir"
                           value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="jenis_kelamin" required>
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki"  <?= (($_POST['jenis_kelamin'] ?? '') === 'Laki-laki')  ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="Perempuan"  <?= (($_POST['jenis_kelamin'] ?? '') === 'Perempuan')  ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="agama">Agama</label>
                    <select name="agama" id="agama" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                        <option value="<?= $ag ?>" <?= (($_POST['agama'] ?? '') === $ag) ? 'selected' : '' ?>><?= $ag ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="no_telepon">No. Telepon</label>
                    <input type="tel" name="no_telepon" id="no_telepon"
                           placeholder="Contoh: 08123456789"
                           value="<?= htmlspecialchars($_POST['no_telepon'] ?? '') ?>" required>
                </div>

                <div class="form-group full">
                    <label for="alamat">Alamat Lengkap</label>
                    <textarea name="alamat" id="alamat"
                              placeholder="Masukkan alamat lengkap Anda"
                              required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                </div>

                <!-- Data Orang Tua -->
                <div class="section-title">👨‍👩‍👧 Data Orang Tua</div>

                <div class="form-group">
                    <label for="nama_orang_tua">Nama Orang Tua / Wali</label>
                    <input type="text" name="nama_orang_tua" id="nama_orang_tua"
                           placeholder="Nama lengkap orang tua"
                           value="<?= htmlspecialchars($_POST['nama_orang_tua'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="no_telepon_orang_tua">No. Telepon Orang Tua</label>
                    <input type="tel" name="no_telepon_orang_tua" id="no_telepon_orang_tua"
                           placeholder="Contoh: 08123456789"
                           value="<?= htmlspecialchars($_POST['no_telepon_orang_tua'] ?? '') ?>" required>
                </div>

            </div>

            <button type="submit" class="btn-submit">Simpan &amp; Lanjutkan →</button>
        </form>
    </div>
</div>
</body>
</html>
