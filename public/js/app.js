// ============================================================
//  public/js/app.js — Script Global Admin Panel
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    // ── Sidebar toggle (mobile) ───────────────────────────────
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        if (sidebar) sidebar.classList.toggle('show');
        if (overlay) overlay.classList.toggle('show');
    }

    if (toggle) {
        toggle.addEventListener('click', toggleSidebar);
    }
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
});
