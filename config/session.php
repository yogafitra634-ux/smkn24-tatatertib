<?php
// ============================================================
// config/session.php
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_name('smkn24_session');
    session_start();
}

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function require_role($allowed): void
{
    require_login();

    $role    = $_SESSION['user']['role'] ?? '';
    $allowed = (array) $allowed;

    // Kalau role session tidak sesuai halaman yang diakses,
    // cek dulu ke DB — mungkin admin sudah ubah role
    if (!in_array($role, $allowed)) {
        $role = refresh_role_from_db();
    }

    // Setelah refresh, masih tidak sesuai → redirect ke halaman yang benar
    if (!in_array($role, $allowed)) {
        redirect_by_role($role);
        exit;
    }

    // Khusus siswa: cek profil lengkap
    if ($role === 'siswa') {
        $skip    = ['lengkapi-profil.php', 'logout.php'];
        $current = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
        if (!in_array($current, $skip)) {
            cek_profil_siswa();
        }
    }
}

/**
 * Ambil role terbaru dari DB dan update session.
 * Hanya dipanggil saat role tidak cocok dengan halaman yang diakses.
 * TIDAK dipanggil setiap request — agar tidak mengganggu session admin.
 */
function refresh_role_from_db(): string
{
    if (!function_exists('supabase')) {
        require_once __DIR__ . '/../config/supabase.php';
    }

    $user_id = $_SESSION['user']['id'] ?? '';
    if (!$user_id) return $_SESSION['user']['role'] ?? 'siswa';

    $res = supabase('users?id=eq.' . $user_id . '&select=role,is_active&limit=1');

    if (empty($res['data'][0])) return $_SESSION['user']['role'] ?? 'siswa';

    $db_role      = $res['data'][0]['role']      ?? 'siswa';
    $db_is_active = $res['data'][0]['is_active'] ?? true;

    // Kalau dinonaktifkan, logout
    if (!$db_is_active) {
        session_destroy();
        header('Location: /smkn24-tatatertib/login.php?msg=nonaktif');
        exit;
    }

    // Update session dengan role terbaru
    $_SESSION['user']['role'] = $db_role;
    return $db_role;
}

function cek_profil_siswa(): void
{
    if (!function_exists('supabase')) {
        require_once __DIR__ . '/../config/supabase.php';
    }

    $current = basename($_SERVER['SCRIPT_NAME']);

    // Halaman yang boleh diakses walaupun profil belum lengkap
    $allowed_pages = [
        'lengkapi-profil.php',
        'logout.php',
        'logout-temp.php'
    ];

    if (in_array($current, $allowed_pages, true)) {
        return;
    }

    $user = current_user();

    $res = supabase(
        'profil_siswa?user_id=eq.' . $user['id'] . '&select=*&limit=1'
    );

    $p = $res['data'][0] ?? [];

    $lengkap = !empty($p['kelas_id'])
        && !empty($p['tempat_lahir'])
        && !empty($p['tanggal_lahir'])
        && !empty($p['jenis_kelamin'])
        && !empty($p['agama'])
        && !empty($p['alamat'])
        && !empty($p['no_telepon'])
        && !empty($p['nama_orang_tua'])
        && !empty($p['no_telepon_orang_tua']);

    if (!$lengkap) {
        header('Location: /siswa/lengkapi-profil.php');
        exit;
    }
}

function redirect_by_role(string $role): void
{
    $map = [
    'siswa'    => base_url('siswa/dashboard.php'),
    'operator' => base_url('operator/dashboard.php'),
    'admin'    => base_url('admin/dashboard.php'),
];
    header('Location: ' . ($map[$role] ?? '/smkn24-tatatertib/login.php'));
    exit;
}

function redirect_if_logged_in(): void
{
    if (!empty($_SESSION['user'])) {
        if (isset($_GET['force'])) return;
        redirect_by_role($_SESSION['user']['role']);
    }
}

function base_url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}
