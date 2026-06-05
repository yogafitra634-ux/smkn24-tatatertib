<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_role('siswa');

$user        = current_user();
$active_page = 'tatatertib';

$res = supabase('tata_tertib?is_active=eq.true&select=id,kategori,aturan,tingkat,poin&order=kategori.asc,poin.asc');
$semua = $res['data'] ?? [];

$kategori_list = ['Kehadiran','Kerapihan','Sikap & Perilaku','Barang Terlarang'];
$grouped = [];
foreach ($kategori_list as $kat) {
    $grouped[$kat] = array_values(array_filter($semua, fn($r) => $r['kategori'] === $kat));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Tata Tertib — SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        @media(max-width:768px){.hamburger-btn{display:block !important}}
    </style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__ . '/../components/sidebar-siswa.php'; ?>
    <div class="main-content">
        <?php $page_title='Informasi Tata Tertib'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Informasi Tata Tertib</h1>
                <p>Pahami aturan sekolah untuk menciptakan lingkungan yang disiplin dan nyaman</p>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="card-title">Peraturan SMKN 24 yang tidak boleh dilanggar :</div>

                    <?php foreach ($grouped as $kat => $rules): ?>
                    <div class="accordion-item">
                        <div class="accordion-header">
                            <span><?= htmlspecialchars($kat) ?></span>
                            <svg class="accordion-arrow" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <div class="accordion-body">
                            <?php if (empty($rules)): ?>
                                <p style="font-size:13px;color:var(--text-light);padding:8px 0">Belum ada aturan.</p>
                            <?php else: ?>
                                <div style="display:flex;justify-content:space-between;padding:8px 0 4px;font-size:12px;font-weight:700;color:var(--text-light)">
                                    <span>Aturan</span>
                                    <span>Jika Melanggar :</span>
                                </div>
                                <?php foreach ($rules as $rule):
                                    $lvl = strtolower($rule['tingkat']);
                                ?>
                                <div class="accordion-rule">
                                    <div class="accordion-rule-left">
                                        <span class="dot"></span>
                                        <?= htmlspecialchars($rule['aturan']) ?>
                                    </div>
                                    <span class="badge badge-<?= $lvl ?>"><?= htmlspecialchars($rule['tingkat']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
