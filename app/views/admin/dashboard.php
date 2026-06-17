<?php
// ============================================================
//  app/views/admin/dashboard.php
// ============================================================
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once dirname(__DIR__) . '/layouts/layout.php';
require_once dirname(__DIR__, 2) . '/controllers/DashboardController.php';

$admin  = new DashboardController($conn);
$stats  = $admin->getDashboardStats();
$recent = $admin->getActiveOrders();
?>

<!-- Stats cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon" style="background:var(--accent-lt);color:var(--primary);"><iconify-icon icon="mdi:clock-fast" style="font-size: 26px;"></iconify-icon></div>
            <div>
                <div class="stat-value"><?= $stats['today'] ?></div>
                <div class="stat-label">Pesanan Hari Ini</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon" style="background:#e6fcf5;color:#10b981;"><iconify-icon icon="mdi:washing-machine" style="font-size: 26px;"></iconify-icon></div>
            <div>
                <div class="stat-value"><?= $stats['active'] ?></div>
                <div class="stat-label">Pesanan Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><iconify-icon icon="mdi:cash" style="font-size: 26px;"></iconify-icon></div>
            <div>
                <div class="stat-value" style="font-size:18px;"><?= rupiah($stats['income']) ?></div>
                <div class="stat-label">Pendapatan Bulan Ini</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon" style="background:#f3e8ff;color:#a855f7;"><iconify-icon icon="mdi:truck-fast" style="font-size: 26px;"></iconify-icon></div>
            <div>
                <div class="stat-value"><?= $stats['ready'] ?></div>
                <div class="stat-label">Siap Diambil</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= $baseUrl ?>/app/views/admin/orders.php" class="btn btn-primary d-flex align-items-center gap-2">
                        <iconify-icon icon="mdi:plus-circle-outline" style="font-size: 18px;"></iconify-icon>Input Pesanan Baru
                    </a>
                    <a href="<?= $baseUrl ?>/app/views/admin/manage_orders.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <iconify-icon icon="mdi:format-list-checks" style="font-size: 18px;"></iconify-icon>Kelola Pesanan
                    </a>
                    <a href="<?= $baseUrl ?>/app/views/admin/payment.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <iconify-icon icon="mdi:credit-card-outline" style="font-size: 18px;"></iconify-icon>Catat Pembayaran
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent orders -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="d-flex align-items-center gap-2">
            <iconify-icon icon="" class="text-muted" style="font-size: 20px;"></iconify-icon>Pesanan Sedang Dikerjakan
        </span>
        <a href="<?= $baseUrl ?>/app/views/admin/manage_orders.php" style="font-size:13px;color:var(--primary); font-weight: 600; text-decoration: none;">Lihat semua →</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Invoice</th><th>Pelanggan</th><th>Layanan</th>
                        <th>Total</th><th>Status</th><th>Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $recent->fetch_assoc()): ?>
                    <tr>
                        <td><code style="font-size:12px;color:var(--primary);"><?= $r['invoice_number'] ?></code></td>
                        <td><?= htmlspecialchars($r['cust_name']) ?></td>
                        <td style="font-size:13px;"><?= htmlspecialchars($r['svc_name']) ?></td>
                        <td style="font-weight:600;"><?= rupiah($r['total_price']) ?></td>
                        <td><?= statusBadge($r['status']) ?></td>
                        <td><?= paymentBadge($r['payment_status'] ?? 'Unpaid') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/layout_footer.php'; ?>
