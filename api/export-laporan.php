<?php
// ============================================================
// api/export-laporan.php
// Export laporan pelanggaran ke Excel atau PDF
// Method: GET
// Params: format (excel|pdf), periode (YYYY-MM), kelas_id, tingkat
// ============================================================

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/supabase.php';

require_role(['operator', 'admin']);

$format  = $_GET['format']   ?? 'excel';
$periode = $_GET['periode']  ?? date('Y-m');
$kelas   = $_GET['kelas_id'] ?? '';
$tingkat = $_GET['tingkat']  ?? '';

// Hitung range tanggal dari periode (YYYY-MM)
$dari   = $periode . '-01';
$sampai = date('Y-m-t', strtotime($dari));

// ── Ambil data dari Supabase ────────────────────────────────
$filters = [
    'tanggal=gte.' . $dari,
    'tanggal=lte.' . $sampai,
];

if ($tingkat && $tingkat !== 'semua') {
    $filters[] = 'tata_tertib_id.tingkat=eq.' . urlencode($tingkat);
}

$select = 'select=tanggal,waktu,lokasi,keterangan,sanksi,'
        . 'siswa_id(nama_lengkap,nis_nip),'
        . 'tata_tertib_id(kategori,aturan,tingkat,poin)';

$query = 'pelanggaran?' . $select . '&' . implode('&', $filters)
       . '&order=tanggal.desc';

$result = supabase($query);

if ($result['status'] !== 200) {
    die('Gagal mengambil data.');
}

$data = $result['data'] ?? [];

// ── EXPORT EXCEL (CSV sederhana) ────────────────────────────
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="laporan-pelanggaran-' . $periode . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM untuk Excel agar karakter Indonesia tampil benar
    echo "\xEF\xBB\xBF";

    // Header tabel
    echo "No\tTanggal\tNama Siswa\tNIS\tKelas\tKategori\tJenis Pelanggaran\tTingkat\tPoin\tLokasi\tKeterangan\tSanksi\n";

    $no = 1;
    foreach ($data as $row) {
        $siswa    = $row['siswa_id']        ?? [];
        $tt       = $row['tata_tertib_id']  ?? [];

        echo implode("\t", [
            $no++,
            $row['tanggal']         ?? '',
            $siswa['nama_lengkap']  ?? '',
            $siswa['nis_nip']       ?? '',
            '',                           // kelas (perlu join profil_siswa — opsional)
            $tt['kategori']         ?? '',
            $tt['aturan']           ?? '',
            $tt['tingkat']          ?? '',
            $tt['poin']             ?? 0,
            $row['lokasi']          ?? '',
            $row['keterangan']      ?? '',
            $row['sanksi']          ?? '',
        ]) . "\n";
    }
    exit;
}

// ── EXPORT PDF (HTML print) ─────────────────────────────────
if ($format === 'pdf') {
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pelanggaran <?= htmlspecialchars($periode) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h2   { text-align: center; margin-bottom: 4px; }
        p.sub{ text-align: center; color: #555; margin-bottom: 16px; }
        table{ width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; }
        th { background: #1e3cbe; color: #fff; text-align: left; }
        tr:nth-child(even) { background: #f5f6fa; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px;
                 font-size: 11px; font-weight: bold; }
        .ringan { background: #c6f6d5; color: #276749; }
        .sedang { background: #fefcbf; color: #7b6000; }
        .berat  { background: #fed7d7; color: #9b2335; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <h2>Laporan Pelanggaran Siswa</h2>
    <p class="sub">SMKN 24 &mdash; Periode: <?= htmlspecialchars($periode) ?></p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Siswa</th>
                <th>NIS</th>
                <th>Jenis Pelanggaran</th>
                <th>Tingkat</th>
                <th>Poin</th>
                <th>Lokasi</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
        <?php $no = 1; foreach ($data as $row):
            $siswa = $row['siswa_id']       ?? [];
            $tt    = $row['tata_tertib_id'] ?? [];
            $lvl   = strtolower($tt['tingkat'] ?? '');
        ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['tanggal'] ?? '') ?></td>
                <td><?= htmlspecialchars($siswa['nama_lengkap'] ?? '') ?></td>
                <td><?= htmlspecialchars($siswa['nis_nip'] ?? '') ?></td>
                <td><?= htmlspecialchars($tt['aturan'] ?? '') ?></td>
                <td><span class="badge <?= $lvl ?>"><?= htmlspecialchars($tt['tingkat'] ?? '') ?></span></td>
                <td><?= (int)($tt['poin'] ?? 0) ?></td>
                <td><?= htmlspecialchars($row['lokasi'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <script>window.onload = () => window.print();</script>
</body>
</html>
<?php
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Format tidak valid. Gunakan excel atau pdf.']);
