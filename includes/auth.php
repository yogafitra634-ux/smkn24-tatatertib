<?php
// ============================================================
// includes/auth.php
// ============================================================

require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../config/session.php';

// ============================================================
// REGISTER
// ============================================================
function register(string $nis_nip, string $nama_lengkap, string $email, string $password, string $foto_path = ''): array
{
    if (empty($nis_nip) || empty($nama_lengkap) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Semua field wajib diisi.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Format email tidak valid.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password minimal 6 karakter.'];
    }

    // Cek NIS/NIP duplikat
    $cekNis = supabase('users?nis_nip=eq.' . urlencode($nis_nip) . '&select=id&limit=1');
    if (!empty($cekNis['data'])) {
        return ['success' => false, 'message' => 'NIS/NIP sudah terdaftar.'];
    }

    // Cek email duplikat
    $cekEmail = supabase('users?email=eq.' . urlencode($email) . '&select=id&limit=1');
    if (!empty($cekEmail['data'])) {
        return ['success' => false, 'message' => 'Email sudah terdaftar.'];
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $r = supabase('users', 'POST', [
        'nis_nip'      => $nis_nip,
        'nama_lengkap' => $nama_lengkap,
        'email'        => $email,
        'password'     => $hashed,
        'role'         => 'siswa',
        'foto'         => $foto_path ?: null,
        'is_active'    => true,
    ]);

    if ($r['status'] === 201 && !empty($r['data'])) {
        $user_id = $r['data'][0]['id'];
        // Buat profil siswa kosong
        supabase('profil_siswa', 'POST', ['user_id' => $user_id]);
        return ['success' => true, 'message' => 'Akun berhasil dibuat! Silakan login.'];
    }

    return ['success' => false, 'message' => 'Gagal membuat akun. Coba lagi.'];
}

// ============================================================
// LOGIN
// ============================================================
function login(string $nis_nip, string $email, string $password): array
{
    if (empty($nis_nip) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Semua field wajib diisi.', 'role' => null];
    }

    // Cari user — query terpisah agar tidak bentrok separator
    $res = supabase(
        'users?nis_nip=eq.' . urlencode($nis_nip)
        . '&email=eq.'      . urlencode($email)
        . '&select=*&limit=1'
    );

    if (empty($res['data']) || $res['status'] !== 200) {
        return ['success' => false, 'message' => 'NIS/NIP atau email tidak ditemukan.', 'role' => null];
    }

    $user = $res['data'][0];

    if (!$user['is_active']) {
        return ['success' => false, 'message' => 'Akun ini sudah dinonaktifkan.', 'role' => null];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Password salah.', 'role' => null];
    }

    unset($user['password']);
    $_SESSION['user'] = $user;

    return ['success' => true, 'message' => 'Login berhasil!', 'role' => $user['role']];
}

// ============================================================
// UPLOAD FOTO
// ============================================================
function upload_foto(array $file): array
{
    $allowed    = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size   = 2 * 1024 * 1024;
    $upload_dir = __DIR__ . '/../assets/img/uploads/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => '', 'message' => 'Gagal upload foto.'];
    }
    if ($file['size'] > $max_size) {
        return ['success' => false, 'path' => '', 'message' => 'Ukuran foto maksimal 2MB.'];
    }
    if (!in_array($file['type'], $allowed)) {
        return ['success' => false, 'path' => '', 'message' => 'Format foto harus JPG, PNG, atau WEBP.'];
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('foto_', true) . '.' . strtolower($ext);
    $dest     = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'path' => '', 'message' => 'Gagal menyimpan foto.'];
    }

    return ['success' => true, 'path' => 'assets/img/uploads/' . $filename, 'message' => 'Foto berhasil diupload.'];
}
