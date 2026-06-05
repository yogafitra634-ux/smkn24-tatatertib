<?php
// components/navbar.php
if (!function_exists('foto_url')) {
    require_once __DIR__ . '/../includes/functions.php';
}
$page_title = $page_title ?? '';
$user       = $user ?? current_user();
$nama       = $user['nama_lengkap'] ?? 'User';
$role_label = match($user['role'] ?? '') {
    'siswa'    => 'Siswa',
    'operator' => 'Operator',
    'admin'    => 'Admin',
    default    => 'User',
};
$foto = $user['foto'] ?? '';
?>
<header class="navbar">
    <div style="display:flex;align-items:center;gap:12px">
        <button class="hamburger-btn" id="hamburger" aria-label="Menu"
                style="background:none;border:none;cursor:pointer;color:var(--text-mid);padding:4px;display:none">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <?php if ($page_title): ?>
        <span class="navbar-title"><?= htmlspecialchars($page_title) ?></span>
        <?php endif; ?>
    </div>

    <div class="navbar-right">
        <!-- Notifikasi -->
        <div style="position:relative">
            <button class="notif-btn" aria-label="Notifikasi">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="notif-badge" style="display:none">0</span>
            </button>
            <div class="notif-dropdown">
                <div class="notif-header">
                    <span>Notifikasi</span>
                    <button class="notif-mark-all link" style="font-size:12px">Tandai semua dibaca</button>
                </div>
                <div class="notif-list">
                    <div style="padding:16px;text-align:center;font-size:13px;color:var(--text-light)">Memuat...</div>
                </div>
            </div>
        </div>

        <!-- User info -->
        <div class="user-info" style="position:relative">
            <div class="user-avatar">
                <?php if ($foto): ?>
                    <img src="<?= foto_url($foto) ?>"
                         alt="<?= htmlspecialchars($nama) ?>"
                         onerror="this.parentElement.innerHTML='<?= addslashes(avatar_placeholder(20)) ?>'">
                <?php else: ?>
                    <?= avatar_placeholder(20) ?>
                <?php endif; ?>
            </div>
            <div class="user-text">
                <div class="name"><?= htmlspecialchars($nama) ?></div>
                <div class="role"><?= $role_label ?></div>
            </div>
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
            <div class="user-dropdown">
                <a href="profil.php" class="dropdown-item">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profil Saya
                </a>
                <a href="../logout.php" class="dropdown-item danger"
                   onclick="return confirm('Yakin ingin logout?')">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </div>
</header>
<script>
document.getElementById('hamburger')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.toggle('open');
});
</script>
