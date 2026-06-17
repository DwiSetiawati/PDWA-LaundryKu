<?php
// ============================================================
//  app/views/components/sidebar.php — Komponen Sidebar Admin
// ============================================================
?>
<!-- ── Sidebar ──────────────────────────────────────────────── -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <iconify-icon icon="mdi:washing-machine" style="font-size: 24px;"></iconify-icon>
        </div>
        <div class="d-flex flex-column justify-content-center">
            <h5 class="brand-font" style="margin: 0; font-size: 20px; font-weight: 800; letter-spacing: -0.02em;">Laundry<span style="color: var(--mint);">Ku</span></h5>
            <small style="font-size: 10px; color: var(--muted); letter-spacing: 0.5px; font-weight: 700; text-transform: uppercase;">Portal Admin</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-label">Menu Utama</div>
        <a href="<?= $baseUrl ?>/app/views/admin/dashboard.php" class="nav-link <?= ($activePage==='dashboard') ? 'active' : '' ?>">
            <span class="nav-icon"><iconify-icon icon="mdi:view-dashboard-outline"></iconify-icon></span> Dashboard
        </a>
        <a href="<?= $baseUrl ?>/app/views/admin/orders.php" class="nav-link <?= ($activePage==='orders') ? 'active' : '' ?>">
            <span class="nav-icon"><iconify-icon icon="mdi:plus-circle-outline"></iconify-icon></span> Input Pesanan
        </a>
        <a href="<?= $baseUrl ?>/app/views/admin/manage_orders.php" class="nav-link <?= ($activePage==='manage_orders') ? 'active' : '' ?>">
            <span class="nav-icon"><iconify-icon icon="mdi:format-list-checks"></iconify-icon></span> Kelola Pesanan
        </a>
        <a href="<?= $baseUrl ?>/app/views/admin/payment.php" class="nav-link <?= ($activePage==='payment') ? 'active' : '' ?>">
            <span class="nav-icon"><iconify-icon icon="mdi:credit-card-outline"></iconify-icon></span> Pembayaran
        </a>

        <div class="sidebar-label">Laporan</div>
        <a href="<?= $baseUrl ?>/app/views/admin/history.php" class="nav-link <?= ($activePage==='history') ? 'active' : '' ?>">
            <span class="nav-icon"><iconify-icon icon="mdi:history"></iconify-icon></span> Riwayat Transaksi
        </a>
        <a href="<?= $baseUrl ?>/app/views/admin/report.php" class="nav-link <?= ($activePage==='report') ? 'active' : '' ?>">
            <span class="nav-icon"><iconify-icon icon="mdi:chart-bar"></iconify-icon></span> Laporan Bulanan
        </a>

        <div class="sidebar-label">Pengaturan</div>
        <a href="<?= $baseUrl ?>/app/views/admin/services.php" class="nav-link <?= ($activePage==='services') ? 'active' : '' ?>">
            <span class="nav-icon"><iconify-icon icon="mdi:cog-outline"></iconify-icon></span> Layanan & Harga
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $baseUrl ?>/app/views/auth/logout.php" class="nav-link text-danger d-flex align-items-center justify-content-center gap-2" style="color: #ef4444 !important; font-weight: 600;">
            <iconify-icon icon="mdi:logout" style="font-size: 20px;"></iconify-icon> Keluar
        </a>
    </div>
</div>
