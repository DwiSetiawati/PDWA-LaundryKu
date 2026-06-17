<?php
// ============================================================
//  app/views/admin/payment.php — Kelola Pembayaran (Simple)
// ============================================================
$pageTitle  = 'Pembayaran';
$activePage = 'payment';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/whatsapp.php';
require_once dirname(__DIR__) . '/layouts/layout.php';

$success = $error = '';
$wa_lunas_link = '';

// ── Proses konfirmasi pembayaran ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $order_id    = (int)$_POST['order_id'];
    $pay_method  = $_POST['pay_method'] ?? 'Cash';
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $valid       = ['Cash','Transfer','QRIS'];

    if (!in_array($pay_method, $valid)) {
        $error = 'Metode pembayaran tidak valid.';
    } elseif ($amount_paid <= 0) {
        $error = 'Jumlah bayar harus lebih dari 0.';
    } else {
        $stmt = $conn->prepare("UPDATE payments
                                SET payment_status='Paid', payment_method=?, amount_paid=?, paid_at=NOW()
                                WHERE order_id=?");
        $stmt->bind_param('sdi', $pay_method, $amount_paid, $order_id);
        if ($stmt->execute()) {
            // Generate WA link konfirmasi lunas
            $o2 = $conn->prepare("
                SELECT o.*, c.name AS cust_name, c.phone AS cust_phone, s.name AS svc_name
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                JOIN services s  ON o.service_id = s.id
                WHERE o.id = ?
            ");
            $o2->bind_param('i', $order_id);
            $o2->execute();
            $o2row = $o2->get_result()->fetch_assoc();
            if ($o2row) {
                $wa_data_lunas = [
                    'cust_name'      => $o2row['cust_name'],
                    'invoice_number' => $o2row['invoice_number'],
                    'total_price'    => $o2row['total_price'],
                    'payment_method' => $pay_method,
                ];
                $wa_lunas_link = generateWALink($o2row['cust_phone'], pesanPembayaranLunas($wa_data_lunas));
            }
            $success = 'Pembayaran berhasil dicatat!';
        } else {
            $error = 'Gagal menyimpan pembayaran.';
        }
    }
}

// ── Cari pesanan by invoice (GET atau POST) ───────────────────
$search_inv = trim($_GET['inv'] ?? $_POST['search_inv'] ?? '');
$order = null;

if ($search_inv) {
    $stmt = $conn->prepare("
        SELECT o.*, c.name AS cust_name, c.phone AS cust_phone, c.address,
               s.name AS svc_name, s.price_per_kg,
               p.payment_status, p.payment_method, p.amount_paid, p.paid_at
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        JOIN services s  ON o.service_id  = s.id
        LEFT JOIN payments p ON p.order_id = o.id
        WHERE o.invoice_number = ?
    ");
    $stmt->bind_param('s', $search_inv);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) $error = "Invoice <strong>{$search_inv}</strong> tidak ditemukan.";
}

// ── Daftar belum lunas ────────────────────────────────────────
$unpaid = $conn->query("
    SELECT o.id, o.invoice_number, c.name AS cust_name, c.phone,
           o.total_price, o.status, o.created_at
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE p.payment_status = 'Unpaid' OR p.payment_status IS NULL
    ORDER BY o.created_at DESC
    LIMIT 15
");
$unpaid_count = $unpaid->num_rows;
?>

<?php if ($success): ?>
<div class="alert alert-success mb-4">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-check-circle-fill text-success" style="font-size:22px;"></i>
        <div class="flex-fill">
            <div style="font-weight:700;">Pembayaran berhasil dicatat!</div>
            <?php if ($wa_lunas_link): ?>
            <div class="mt-2">
                <a href="<?= htmlspecialchars($wa_lunas_link) ?>" target="_blank" class="btn btn-success btn-sm">
                    <i class="bi bi-whatsapp me-1"></i>Kirim Konfirmasi Lunas via WA
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger mb-4"><i class="bi bi-x-circle me-2"></i><?= $error ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- ── Kiri: Cari + Form Bayar ───────────────────────────── -->
    <div class="col-lg-7">

        <!-- Cari invoice -->
        <div class="card mb-4">
            <div class="card-body py-3">
                <form method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text"><iconify-icon icon="mdi:magnify" style="font-size: 20px;"></iconify-icon></span>
                        <input type="text" name="inv" class="form-control form-control-lg"
                               placeholder="Nomor invoice... (cth: LND-20260617-0001)"
                               value="<?= htmlspecialchars($search_inv) ?>" autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary px-4 d-flex align-items-center justify-content-center">Cari</button>
                </form>
            </div>
        </div>

        <?php if ($order): ?>
        <!-- Detail pesanan & form bayar -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center"><iconify-icon icon="mdi:receipt-text-outline" class="text-muted me-2" style="font-size: 20px;"></iconify-icon>Detail Pesanan</span>
                <?= statusBadge($order['status']) ?>
            </div>
            <div class="card-body">
                <!-- Info ringkas pelanggan & pesanan -->
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="info-block">
                            <div class="info-label">Invoice</div>
                            <div class="info-val" style="color:var(--primary);font-size:15px;"><?= $order['invoice_number'] ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-block">
                            <div class="info-label">Pelanggan</div>
                            <div class="info-val"><?= htmlspecialchars($order['cust_name']) ?></div>
                            <div style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($order['cust_phone']) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-block">
                            <div class="info-label">Layanan</div>
                            <div class="info-val"><?= htmlspecialchars($order['svc_name']) ?></div>
                            <div style="font-size:12px;color:var(--muted);">
                                <?= $order['weight_kg'] ?> kg × <?= rupiah($order['price_per_kg']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-block">
                            <div class="info-label">Total Tagihan</div>
                            <div class="info-val" style="font-size:22px;font-weight:800;color:var(--primary);">
                                <?= rupiah($order['total_price']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($order['payment_status'] === 'Paid'): ?>
                <!-- Sudah lunas -->
                <div class="p-4 text-center" style="background:#e6fcf5;border-radius:12px;">
                    <iconify-icon icon="mdi:check-decagram" style="font-size: 40px; color: var(--mint); display: block; margin: 0 auto 10px;"></iconify-icon>
                    <div style="font-weight:700;font-size:16px;color:#0d9668;">Sudah Lunas</div>
                    <div style="font-size:13px;color:var(--muted);margin-top:4px;">
                        <?= htmlspecialchars($order['payment_method']) ?> •
                        <?= rupiah($order['amount_paid']) ?> •
                        <?= date('d M Y H:i', strtotime($order['paid_at'])) ?>
                    </div>
                </div>
                <?php else: ?>
                <!-- Form pembayaran — simple & langsung -->
                <div style="background:var(--bg);border-radius:12px;padding:20px;">
                    <div class="mb-3 d-flex align-items-center gap-2" style="font-weight:700;color:var(--text);">
                        <iconify-icon icon="mdi:cash-multiple" class="text-success" style="font-size: 22px;"></iconify-icon>Konfirmasi Pembayaran
                    </div>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="search_inv" value="<?= $search_inv ?>">

                        <!-- Metode -->
                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran</label>
                            <div class="d-flex gap-2">
                                <input type="radio" name="pay_method" id="mCash" value="Cash" class="btn-check" checked>
                                <label class="btn btn-outline-secondary flex-fill" for="mCash">Cash</label>

                                <input type="radio" name="pay_method" id="mTransfer" value="Transfer" class="btn-check">
                                <label class="btn btn-outline-secondary flex-fill" for="mTransfer">Transfer</label>

                                <input type="radio" name="pay_method" id="mQRIS" value="QRIS" class="btn-check">
                                <label class="btn btn-outline-secondary flex-fill" for="mQRIS">QRIS</label>
                            </div>
                        </div>

                        <!-- Jumlah bayar -->
                        <div class="mb-3">
                            <label class="form-label">Jumlah Dibayar</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="amount_paid" id="amountPaid" class="form-control"
                                       value="<?= $order['total_price'] ?>" step="1000" min="0">
                            </div>
                        </div>

                        <!-- Kembalian (hanya muncul untuk Cash) -->
                        <div id="kembalianBox" class="mb-3 p-3" style="background:#fff;border-radius:8px;border:1px solid var(--border);display:none;">
                            <div class="d-flex justify-content-between">
                                <span style="font-size:13px;">Tagihan</span>
                                <span style="font-weight:600;"><?= rupiah($order['total_price']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span style="font-size:13px;">Kembalian</span>
                                <span id="kembalianAmt" style="font-weight:700;color:#059669;font-size:16px;">Rp 0</span>
                            </div>
                        </div>

                        <button type="submit" name="process_payment" class="btn btn-accent w-100 py-3" style="font-size:15px; font-weight: 600;">
                            <iconify-icon icon="mdi:check-circle-outline" class="me-2" style="font-size: 18px; vertical-align: middle;"></iconify-icon>Tandai Lunas
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif (!$search_inv): ?>
        <!-- Placeholder -->
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <iconify-icon icon="mdi:magnify" style="font-size: 40px; display: block; margin: 0 auto 12px; color: var(--muted);"></iconify-icon>
                <div style="font-size:14px;">Masukkan nomor invoice di atas untuk mencari pesanan,<br>atau klik invoice dari daftar di sebelah kanan.</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Kanan: Daftar belum lunas ─────────────────────────── -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <iconify-icon icon="mdi:alert-circle-outline" class="text-warning me-2" style="font-size: 20px;"></iconify-icon>
                <span>Belum Lunas</span>
                <span class="badge bg-warning text-dark ms-2"><?= $unpaid_count ?></span>
            </div>
            <div class="card-body p-0" style="max-height:520px;overflow-y:auto;">
                <?php
                // Re-query because pointer may be exhausted
                $unpaid2 = $conn->query("
                    SELECT o.id, o.invoice_number, c.name AS cust_name, c.phone,
                           o.total_price, o.status, o.created_at
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN payments p ON p.order_id = o.id
                    WHERE p.payment_status = 'Unpaid' OR p.payment_status IS NULL
                    ORDER BY o.created_at DESC LIMIT 15
                ");
                while ($u = $unpaid2->fetch_assoc()):
                ?>
                <a href="payment.php?inv=<?= $u['invoice_number'] ?>"
                   class="d-flex align-items-center justify-content-between px-4 py-3 text-decoration-none"
                   style="border-bottom:1px solid var(--border);color:inherit;
                          <?= ($search_inv === $u['invoice_number']) ? 'background:#fffbeb;' : '' ?>
                          transition:background .15s;"
                   onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='<?= ($search_inv === $u['invoice_number']) ? '#fffbeb' : 'transparent' ?>'">
                    <div>
                        <div style="font-weight:700;font-size:13px;color:var(--primary);">
                            <?= $u['invoice_number'] ?>
                        </div>
                        <div style="font-size:12px;color:var(--muted);">
                            <?= htmlspecialchars($u['cust_name']) ?> •
                            <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-weight:700;font-size:13px;"><?= rupiah($u['total_price']) ?></div>
                        <?= statusBadge($u['status']) ?>
                    </div>
                </a>
                <?php endwhile; ?>
                <?php if ($unpaid_count === 0): ?>
                <div class="text-center text-muted py-5" style="font-size:13px;">
                    <iconify-icon icon="mdi:check-decagram-outline" style="color:var(--mint); font-size: 36px; display: block; margin: 0 auto 8px;"></iconify-icon>
                    Semua pesanan sudah lunas!
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.info-block { background:var(--bg); border-radius:10px; padding:12px 16px; }
.info-label { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
.info-val   { font-weight:700; font-size:15px; }
</style>

<div id="totalPriceData" style="display:none"
     data-total="<?= $order ? $order['total_price'] : 0 ?>"></div>
<script src="<?= $publicUrl ?>/js/payment.js"></script>

<?php require_once dirname(__DIR__) . '/layouts/layout_footer.php'; ?>
