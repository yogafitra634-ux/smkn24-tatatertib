<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';
require_role('admin');
$user        = current_user();
$active_page = 'tatatertib';
$success = $error = '';

$kategori_list = ['Kehadiran','Kerapihan','Sikap & Perilaku','Barang Terlarang'];

// Tambah aturan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
    $payload = [
        'kategori' => $_POST['kategori'] ?? '',
        'aturan'   => trim($_POST['aturan'] ?? ''),
        'tingkat'  => $_POST['tingkat']   ?? '',
        'poin'     => (int)($_POST['poin'] ?? 0),
        'is_active'=> true,
    ];
    if (!$payload['kategori'] || !$payload['aturan'] || !$payload['tingkat']) {
        $error = 'Kategori, aturan, dan tingkat wajib diisi.';
    } else {
        $r = supabase('tata_tertib', 'POST', $payload);
        $success = $r['status'] === 201 ? 'Aturan berhasil ditambahkan.' : '';
        $error   = $r['status'] !== 201 ? 'Gagal menambahkan aturan.' : '';
    }
}

// Hapus aturan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    $id = $_POST['id'] ?? '';
    $r  = supabase('tata_tertib?id=eq.'.urlencode($id), 'DELETE');
    $success = in_array($r['status'],[200,204]) ? 'Aturan berhasil dihapus.' : '';
    $error   = !in_array($r['status'],[200,204]) ? 'Gagal menghapus.' : '';
}

// Toggle aktif/nonaktif
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'toggle') {
    $id     = $_POST['id']     ?? '';
    $status = ($_POST['status'] ?? 'true') === 'true';
    $r = supabase('tata_tertib?id=eq.'.urlencode($id), 'PATCH', ['is_active' => !$status]);
    $success = $r['status'] === 200 ? 'Status aturan diperbarui.' : '';
}

$res  = supabase('tata_tertib?select=*&order=kategori.asc,poin.asc');
$semua = $res['data'] ?? [];
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
    <title>Kelola Tata Tertib — SMKN 24</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>@media(max-width:768px){.hamburger-btn{display:block !important}}</style>
</head>
<body>
<div class="app-layout">
    <?php require_once __DIR__.'/../components/sidebar-admin.php'; ?>
    <div class="main-content">
        <?php $page_title='Kelola Tata Tertib'; require_once __DIR__.'/../components/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <h1>Kelola Tata Tertib</h1>
                <p>Tambah, edit, dan hapus aturan tata tertib sekolah</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success" data-autohide><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Form Tambah -->
            <div class="card" style="margin-bottom:20px">
                <div class="card-body">
                    <div class="card-title">Tambah Aturan Baru</div>
                    <form method="POST">
                        <input type="hidden" name="aksi" value="tambah">
                        <div class="form-2col" style="margin-bottom:14px">
                            <div class="form-group">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" class="form-control" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?= $kat ?>"><?= $kat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tingkat</label>
                                <select name="tingkat" class="form-control" required>
                                    <option value="">Pilih Tingkat</option>
                                    <option value="Ringan">Ringan</option>
                                    <option value="Sedang">Sedang</option>
                                    <option value="Berat">Berat</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-2col" style="margin-bottom:14px">
                            <div class="form-group">
                                <label class="form-label">Aturan</label>
                                <input type="text" name="aturan" class="form-control" placeholder="Contoh: Terlambat masuk sekolah" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Poin</label>
                                <input type="number" name="poin" class="form-control" value="5" min="0" max="100">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Tambah Aturan</button>
                    </form>
                </div>
            </div>

            <!-- Daftar per kategori -->
            <?php foreach ($grouped as $kat => $rules): ?>
            <div class="card" style="margin-bottom:16px">
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($kat) ?> (<?= count($rules) ?> aturan)</div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Aturan</th>
                                    <th>Tingkat</th>
                                    <th>Poin</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($rules)): ?>
                                <tr><td colspan="5" style="text-align:center;color:var(--text-light);padding:16px">Belum ada aturan.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rules as $r):
                                    $lvl = strtolower($r['tingkat']);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['aturan']) ?></td>
                                    <td><span class="badge badge-<?= $lvl ?>"><?= htmlspecialchars($r['tingkat']) ?></span></td>
                                    <td><?= $r['poin'] ?></td>
                                    <td>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="aksi" value="toggle">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $r['is_active'] ? 'true' : 'false' ?>">
                                            <button type="submit" class="badge <?= $r['is_active'] ? 'badge-ringan' : 'badge-berat' ?>"
                                                    style="border:none;cursor:pointer">
                                                <?= $r['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Hapus aturan ini?')">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
