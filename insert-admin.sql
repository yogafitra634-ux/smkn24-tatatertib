-- ============================================================
-- INSERT ADMIN DEFAULT
-- Sistem Tata Tertib SMKN 24
--
-- Password default: Admin@smkn24
-- Hash bcrypt di bawah sudah di-generate untuk password tersebut
-- Jalankan di: Supabase → SQL Editor → New Query
-- ============================================================

INSERT INTO users (
    nis_nip,
    nama_lengkap,
    email,
    password,
    role,
    is_active
) VALUES (
    '198501012010011001',
    'Administrator SMKN 24',
    'admin@smkn24.sch.id',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    true
);

-- ============================================================
-- CATATAN PENTING:
-- Hash di atas adalah hash bcrypt untuk password: "password"
-- (hash default Laravel/testing)
--
-- SEBELUM PRODUCTION, ganti dengan hash asli menggunakan:
--
-- Opsi 1 - PHP (jalankan di terminal):
--   php -r "echo password_hash('Admin@smkn24', PASSWORD_BCRYPT);"
--
-- Opsi 2 - Online bcrypt generator:
--   https://bcrypt-generator.com
--   Masukkan: Admin@smkn24 → cost factor: 10
--
-- Lalu UPDATE hashnya:
--   UPDATE users
--   SET password = 'HASH_BARU_DISINI'
--   WHERE email = 'admin@smkn24.sch.id';
-- ============================================================


-- ============================================================
-- CARA GENERATE HASH YANG BENAR DI PHP:
-- ============================================================
-- Buat file hash.php di htdocs:
--
--   <?php
--   echo password_hash('Admin@smkn24', PASSWORD_BCRYPT);
--   ?>
--
-- Akses: http://localhost/hash.php
-- Copy hasilnya, paste ke UPDATE query di atas
-- Hapus hash.php setelah selesai!
-- ============================================================


-- ============================================================
-- CEK APAKAH ADMIN SUDAH MASUK:
-- ============================================================
SELECT id, nis_nip, nama_lengkap, email, role, is_active
FROM users
WHERE role = 'admin';

-- ============================================================
-- LOGIN DENGAN:
--   NIS/NIP  : 198501012010011001
--   Email    : admin@smkn24.sch.id
--   Password : Admin@smkn24  (setelah hash diganti)
-- ============================================================
