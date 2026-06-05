<?php
// ============================================================
// index.php
// Entry point — redirect ke dashboard atau login
// ============================================================

require_once __DIR__ . '/config/session.php';

if (!empty($_SESSION['user'])) {
    redirect_by_role($_SESSION['user']['role']);
} else {
    header('Location: login.php');
    exit;
}
