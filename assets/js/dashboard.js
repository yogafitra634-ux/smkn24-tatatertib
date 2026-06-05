// ============================================================
// assets/js/dashboard.js
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

    // ── LAPORAN: TAMPILKAN ────────────────────────────────────
    const btnTampilkan = document.getElementById('btn-tampilkan');
    if (btnTampilkan) {
        btnTampilkan.addEventListener('click', loadLaporan);
    }

    function loadLaporan() {
        const periode = document.getElementById('filter-periode')?.value || '';
        const kelas   = document.getElementById('filter-kelas')?.value   || '';
        const tingkat = document.getElementById('filter-tingkat')?.value || '';

        const tbody = document.getElementById('laporan-tbody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:24px">
                <div class="spinner"></div>
            </td></tr>`;
        }

        const params = new URLSearchParams();
        if (periode) params.set('periode', periode);
        if (kelas)   params.set('kelas_id', kelas);
        if (tingkat && tingkat !== 'semua') params.set('tingkat', tingkat);

        fetch('../api/get-pelanggaran-laporan.php?' + params.toString())
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    if (tbody) tbody.innerHTML = `<tr><td colspan="6"
                        style="text-align:center;padding:24px;color:var(--red)">
                        Gagal memuat data.</td></tr>`;
                    return;
                }
                updateStats(res.data || []);
                renderTabel(res.data || [], tbody);
            })
            .catch(err => {
                console.error(err);
                if (tbody) tbody.innerHTML = `<tr><td colspan="6"
                    style="text-align:center;padding:24px;color:var(--red)">
                    Gagal memuat data.</td></tr>`;
            });
    }

    function updateStats(data) {
        const total  = data.length;
        const ringan = data.filter(d => d.tingkat === 'Ringan').length;
        const sedang = data.filter(d => d.tingkat === 'Sedang').length;
        const berat  = data.filter(d => d.tingkat === 'Berat').length;
        setEl('stat-total',  total);
        setEl('stat-ringan', ringan + ' (' + pct(ringan, total) + '%)');
        setEl('stat-sedang', sedang + ' (' + pct(sedang, total) + '%)');
        setEl('stat-berat',  berat  + ' (' + pct(berat,  total) + '%)');
    }

    function renderTabel(data, tbody) {
        if (!tbody) return;
        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="6"
                style="text-align:center;padding:24px;color:var(--text-light)">
                Tidak ada data untuk filter yang dipilih.</td></tr>`;
            return;
        }
        tbody.innerHTML = data.map((row, i) => {
            const lvl = (row.tingkat || '').toLowerCase();
            return `<tr>
                <td>${i + 1}</td>
                <td>${formatTgl(row.tanggal)}</td>
                <td>${esc(row.nama_siswa || '-')}</td>
                <td>${esc(row.nama_kelas || '-')}</td>
                <td>${esc(row.jenis_pelanggaran || '-')}</td>
                <td><span class="badge badge-${lvl}">${esc(row.tingkat || '-')}</span></td>
            </tr>`;
        }).join('');
    }

    // ── EXPORT ────────────────────────────────────────────────
    document.querySelectorAll('[data-export]').forEach(btn => {
        btn.addEventListener('click', () => {
            const format  = btn.dataset.export;
            const periode = document.getElementById('filter-periode')?.value || '';
            const kelas   = document.getElementById('filter-kelas')?.value   || '';
            const tingkat = document.getElementById('filter-tingkat')?.value || '';

            // Konversi periode YYYY-MM → dari & sampai
            let dari = '', sampai = '';
            if (periode) {
                dari = periode + '-01';
                const [y, m] = periode.split('-').map(Number);
                const lastDay = new Date(y, m, 0).getDate();
                sampai = periode + '-' + String(lastDay).padStart(2, '0');
            }

            const params = new URLSearchParams({ format });
            if (dari)   params.set('dari', dari);
            if (sampai) params.set('sampai', sampai);
            if (kelas)  params.set('kelas_id', kelas);
            if (tingkat && tingkat !== 'semua') params.set('tingkat', tingkat);

            window.open('../api/export-laporan.php?' + params.toString(), '_blank');
        });
    });

    // ── HAPUS PELANGGARAN ─────────────────────────────────────
    document.querySelectorAll('[data-delete-pelanggaran]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm('Yakin ingin menghapus pelanggaran ini?')) return;
            const id  = btn.dataset.deletePelanggaran;
            const row = btn.closest('tr');
            fetch(`../api/delete-pelanggaran.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' })
                .then(r => r.json())
                .then(res => {
                    if (res.success) row?.remove();
                    else alert(res.message || 'Gagal menghapus.');
                });
        });
    });

    // ── HELPERS ───────────────────────────────────────────────
    function setEl(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
    function pct(n, total)  { return total ? Math.round(n / total * 100) : 0; }
    function esc(str) {
        const d = document.createElement('div');
        d.textContent = String(str || '');
        return d.innerHTML;
    }
    function formatTgl(iso) {
        if (!iso) return '-';
        const [y, m, d] = iso.split('-');
        const bln = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        return `${+d} ${bln[+m]} ${y}`;
    }
});