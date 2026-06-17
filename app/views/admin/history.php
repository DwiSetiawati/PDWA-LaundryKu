<?php
// ============================================================
//  admin/history.php — Riwayat Transaksi (Completed)
// ============================================================
$pageTitle  = 'Riwayat Transaksi';
$activePage = 'history';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__) . '/layouts/layout.php';

$search = trim($_GET['q'] ?? '');
$from   = $_GET['from'] ?? '';
$to     = $_GET['to']   ?? '';

$where  = ['1=1'];
$params = [];
$types  = '';

if ($search) {
    $where[] = '(invoice_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)';
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}
if ($from) {
    $where[] = 'DATE(completed_at) >= ?';
    $params[] = $from; $types .= 's';
}
if ($to) {
    $where[] = 'DATE(completed_at) <= ?';
    $params[] = $to; $types .= 's';
}

$whereSQL = implode(' AND ', $where);
$sql = "SELECT * FROM transaction_history WHERE {$whereSQL} ORDER BY completed_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$history = $stmt->get_result();

// Total dari hasil query
$total_rev = 0;
$rows = [];
while ($r = $history->fetch_assoc()) { $rows[] = $r; $total_rev += $r['total_price']; }
?>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label">Cari</label>
                <input type="text" name="q" class="form-control" placeholder="Invoice / nama / telepon..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Sampai</label>
                <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-sm-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                <a href="history.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="bi bi-receipt" style="color:var(--accent);"></i></div>
            <div>
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value"><?= count($rows) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-cash-coin" style="color:#10b981;"></i></div>
            <div>
                <div class="stat-label">Total Pendapatan</div>
                <div class="stat-value" style="font-size:18px;"><?= rupiah($total_rev) ?></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-graph-up" style="color:#3b82f6;"></i></div>
            <div>
                <div class="stat-label">Rata-rata per Transaksi</div>
                <div class="stat-value" style="font-size:18px;">
                    <?= count($rows) > 0 ? rupiah($total_rev / count($rows)) : rupiah(0) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-muted"></i>Riwayat Transaksi</span>
        <a href="report.php" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-bar-chart me-1"></i>Laporan Bulanan
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Pelanggan</th>
                        <th>Layanan</th>
                        <th>Berat</th>
                        <th>Total</th>
                        <th>Metode Bayar</th>
                        <th>Selesai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><code style="font-size:12px;color:var(--primary);"><?= $r['invoice_number'] ?></code></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($r['customer_name']) ?></div>
                            <div style="font-size:12px;color:var(--muted);"><?= $r['customer_phone'] ?></div>
                        </td>
                        <td><?= htmlspecialchars($r['service_name']) ?></td>
                        <td><?= $r['weight_kg'] ?> kg</td>
                        <td style="font-weight:700;"><?= rupiah($r['total_price']) ?></td>
                        <td>
                            <?php
                            $icon = match($r['payment_method']) {
                                'Transfer' => '',
                                'QRIS'     => '',
                                default    => '',
                            };
                            echo $icon . ' ' . $r['payment_method'];
                            ?>
                        </td>
                        <td style="font-size:12px;"><?= date('d M Y H:i', strtotime($r['completed_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:10px;"></i>
                        Belum ada riwayat transaksi
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/layout_footer.php'; ?>
