// ============================================================
// assets/js/main.js
// JS Global — Sistem Tata Tertib SMKN 24
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

    // ── USER DROPDOWN ──────────────────────────────────────────
    const userInfo     = document.querySelector('.user-info');
    const userDropdown = document.querySelector('.user-dropdown');

    if (userInfo && userDropdown) {
        userInfo.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
            notifDropdown?.classList.remove('open');
        });
    }

    // ── NOTIFIKASI DROPDOWN ────────────────────────────────────
    const notifBtn      = document.querySelector('.notif-btn');
    const notifDropdown = document.querySelector('.notif-dropdown');

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('open');
            userDropdown?.classList.remove('open');

            // Load notifikasi saat dibuka
            if (notifDropdown.classList.contains('open')) {
                loadNotifikasi();
            }
        });
    }

    // Tutup dropdown saat klik di luar
    document.addEventListener('click', () => {
        userDropdown?.classList.remove('open');
        notifDropdown?.classList.remove('open');
    });

    // ── LOAD NOTIFIKASI ────────────────────────────────────────
    function loadNotifikasi() {
        const list = document.querySelector('.notif-list');
        if (!list) return;

        list.innerHTML = '<div style="padding:16px;text-align:center"><div class="spinner"></div></div>';

        fetch('../api/notifikasi.php')
            .then(r => r.json())
            .then(res => {
                if (!res.success) { list.innerHTML = '<p style="padding:14px;font-size:13px;color:#9399b2">Gagal memuat.</p>'; return; }

                // Update badge
                const badge = document.querySelector('.notif-badge');
                if (badge) {
                    badge.textContent = res.unread || '';
                    badge.style.display = res.unread ? 'flex' : 'none';
                }

                if (!res.data.length) {
                    list.innerHTML = '<p style="padding:16px;text-align:center;font-size:13px;color:#9399b2">Tidak ada notifikasi.</p>';
                    return;
                }

                list.innerHTML = res.data.map(n => `
                    <div class="notif-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}">
                        <div class="ntitle">${escHtml(n.judul)}</div>
                        <div class="npesan">${escHtml(n.pesan)}</div>
                        <div class="ntime">${formatDate(n.created_at)}</div>
                    </div>
                `).join('');

                // Klik item → mark read
                list.querySelectorAll('.notif-item').forEach(item => {
                    item.addEventListener('click', () => {
                        const id = item.dataset.id;
                        item.classList.remove('unread');
                        markRead(id);
                    });
                });
            })
            .catch(() => {
                list.innerHTML = '<p style="padding:14px;font-size:13px;color:#9399b2">Gagal memuat.</p>';
            });
    }

    function markRead(id) {
        fetch('../api/notifikasi.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ id }),
        }).then(() => {
            // Update badge count
            const badge    = document.querySelector('.notif-badge');
            const unreadEl = document.querySelectorAll('.notif-item.unread');
            if (badge) {
                const count = unreadEl.length;
                badge.textContent    = count || '';
                badge.style.display  = count ? 'flex' : 'none';
            }
        });
    }

    // Tandai semua dibaca
    const markAllBtn = document.querySelector('.notif-mark-all');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', () => {
            fetch('../api/notifikasi.php', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ id: 'all' }),
            }).then(() => {
                document.querySelectorAll('.notif-item.unread')
                    .forEach(el => el.classList.remove('unread'));
                const badge = document.querySelector('.notif-badge');
                if (badge) { badge.textContent = ''; badge.style.display = 'none'; }
            });
        });
    }

    // ── ACCORDION (Tata Tertib) ────────────────────────────────
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', () => {
            const item = header.closest('.accordion-item');
            const isOpen = item.classList.contains('open');

            // Tutup semua
            document.querySelectorAll('.accordion-item').forEach(i => i.classList.remove('open'));

            // Toggle yang diklik
            if (!isOpen) item.classList.add('open');
        });
    });

    // ── MODAL ──────────────────────────────────────────────────
    // Buka modal
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id      = btn.dataset.modalOpen;
            const overlay = document.getElementById(id);
            if (overlay) overlay.classList.add('open');
        });
    });

    // Tutup modal via button
    document.querySelectorAll('[data-modal-close], .modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay')?.classList.remove('open');
        });
    });

    // Tutup modal klik overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    // ── KONFIRMASI HAPUS ───────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const msg = btn.dataset.confirm || 'Yakin ingin menghapus?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ── FLASH MESSAGE AUTO HIDE ────────────────────────────────
    const flash = document.querySelector('.alert[data-autohide]');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity .4s';
            flash.style.opacity    = '0';
            setTimeout(() => flash.remove(), 400);
        }, 3000);
    }

    // ── HELPER: escape HTML ────────────────────────────────────
    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ── HELPER: format tanggal ────────────────────────────────
    function formatDate(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    }

});
