<?php
// ============================================================
//  app/views/components/topbar.php — Komponen Topbar Admin
// ============================================================
?>
<div class="topbar">
    <button class="btn btn-sm d-lg-none me-2 d-flex align-items-center justify-content-center" id="sidebarToggle" style="background:none;border:none;padding: 4px; border-radius: 8px;">
        <iconify-icon icon="mdi:menu" style="font-size: 24px; color: var(--navy-deep);"></iconify-icon>
    </button>
    <h6 class="page-title m-0"><?= htmlspecialchars($pageTitle ?? '') ?></h6>
    <div class="admin-info">
        <span class="text-muted d-none d-sm-inline"><?= date('l, d M Y') ?></span>
        <div class="admin-avatar">
            <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?>
        </div>
        <span style="font-weight: 600; color: var(--navy-deep);"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
    </div>
</div>
