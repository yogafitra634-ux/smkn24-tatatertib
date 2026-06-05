<?php
// ============================================================
// logout-temp.php
// Logout sementara untuk ganti akun (misal siswa → admin)
// Taruh di root: htdocs/smkn24-tatatertib/logout-temp.php
// Akses: http://localhost/smkn24-tatatertib/logout-temp.php
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_name('smkn24_session');
    session_start();
}

session_destroy();

header('Location: /smkn24-tatatertib/login.php');
exit;
