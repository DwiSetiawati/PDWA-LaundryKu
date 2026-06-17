<?php
// ============================================================
//  admin/report.php — Laporan Pendapatan Bulanan
// ============================================================
$pageTitle  = 'Laporan Bulanan';
$activePage = 'report';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__) . '/layouts/layout.php';

$month = (int)($_GET['month'] ?? date('n'));
$year  = (int)($_GET['year']  ?? date('Y'));
$month = max(1, min(12, $month));
$year  = max(2020, min((int)date('Y') + 1, $year));

$months_id = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];

// ── Data laporan ──────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT th.*, DAYOFMONTH(th.completed_at) AS day_num
    FROM transaction_history th
    WHERE MONTH(th.completed_at) = ? AND YEAR(th.completed_at) = ?
    ORDER BY th.completed_at ASC
");
$stmt->bind_param('ii', $month, $year);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$total = 0;
$by_day = [];
$by_service = [];

while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
    $total += $r['total_price'];
    $d = $r['day_num'];
    $by_day[$d] = ($by_day[$d] ?? 0) + $r['total_price'];
    $by_service[$r['service_name']] = ($by_service[$r['service_name']] ?? 0) + $r['total_price'];
}

// Chart data for JS
$chart_labels = json_encode(array_keys($by_day));
$chart_data   = json_encode(array_values($by_day));
$svc_labels   = json_encode(array_keys($by_service));
$svc_data     = json_encode(array_values($by_service));
?>

<!-- Filter bulan/tahun -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="d-flex gap-3 align-items-end flex-wrap">
            <div>
                <label class="form-label">Bulan</label>
                <select name="month" class="form-select" style="min-width:140px;">
                    <?php for ($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m===$month?'selected':'' ?>><?= $months_id[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Tahun</label>
                <select name="year" class="form-select" style="min-width:100px;">
                    <?php for ($y=date('Y'); $y>=2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-filter me-1"></i>Tampilkan
            </button>
        </form>
    </div>
</div>

<!-- Summary header -->
<div class="p-4 mb-4" style="background:linear-gradient(135deg,var(--primary),#2563a8);border-radius:14px;color:#fff;">
    <div class="row align-items-center">
        <div class="col">
            <div style="font-size:13px;opacity:0.7;">Laporan Pendapatan</div>
            <h3 class="mb-0" style="font-weight: 800; color: #fff;"><?= $months_id[$month] ?> <?= $year ?></h3>
        </div>
        <div class="col text-end">
            <div style="font-size:13px;opacity:0.7;">Total Pendapatan</div>
            <h3 class="mb-0"><?= rupiah($total) ?></h3>
            <div style="font-size:12px;opacity:0.7;"><?= count($rows) ?> transaksi</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Daily revenue chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">Pendapatan Per Hari</div>
            <div class="card-body">
                <canvas id="dailyChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <!-- By service -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Per Layanan</div>
            <div class="card-body">
                <canvas id="serviceChart"></canvas>
                <div class="mt-3">
                    <?php foreach ($by_service as $svc => $amt): ?>
                    <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                        <span><?= htmlspecialchars($svc) ?></span>
                        <strong><?= rupiah($amt) ?></strong>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($by_service)): ?>
                    <p class="text-muted text-center" style="font-size:13px;">Belum ada data</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detail table -->
<div class="card">
    <div class="card-header"><i class="bi bi-table me-2 text-muted"></i>Detail Transaksi — <?= $months_id[$month] ?> <?= $year ?></div>
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
                        <th>Metode</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><code style="font-size:12px;color:var(--primary);"><?= $r['invoice_number'] ?></code></td>
                        <td><?= htmlspecialchars($r['customer_name']) ?></td>
                        <td><?= htmlspecialchars($r['service_name']) ?></td>
                        <td><?= $r['weight_kg'] ?> kg</td>
                        <td style="font-weight:700;"><?= rupiah($r['total_price']) ?></td>
                        <td><?= $r['payment_method'] ?? '-' ?></td>
                        <td style="font-size:12px;"><?= date('d M Y', strtotime($r['completed_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:10px;"></i>
                        Tidak ada transaksi di bulan ini
                    </td></tr>
                    <?php endif; ?>
                    <?php if (!empty($rows)): ?>
                    <tr style="background:var(--accent-lt);">
                        <td colspan="4" style="font-weight:700;">TOTAL</td>
                        <td style="font-weight:800;color:var(--primary);"><?= rupiah($total) ?></td>
                        <td colspan="2"></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="chartData" style="display:none"
     data-labels="<?= htmlspecialchars($chart_labels, ENT_QUOTES) ?>"
     data-values="<?= htmlspecialchars($chart_data,   ENT_QUOTES) ?>"
     data-svclabels="<?= htmlspecialchars($svc_labels, ENT_QUOTES) ?>"
     data-svcvalues="<?= htmlspecialchars($svc_data,   ENT_QUOTES) ?>"></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= $publicUrl ?>/js/report.js"></script>


<?php require_once dirname(__DIR__) . '/layouts/layout_footer.php'; ?>
