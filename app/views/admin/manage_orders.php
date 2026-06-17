<?php
// ============================================================
//  app/views/admin/manage_orders.php — Kelola & Update Status
// ============================================================
$pageTitle  = 'Kelola Pesanan';
$activePage = 'manage_orders';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/whatsapp.php';
require_once dirname(__DIR__) . '/layouts/layout.php';

$success = $error = '';
$statuses = ['Queued','Washing','Ironing','Ready','Completed'];

// ── Ambil daftar layanan untuk form edit ──────────────────────
$services_list = $conn->query("SELECT id, name, price_per_kg, duration_days FROM services WHERE is_active=1 ORDER BY name");
$services_arr = [];
while ($sv = $services_list->fetch_assoc()) $services_arr[] = $sv;

// ── Edit pesanan ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_order'])) {
    $order_id   = (int)$_POST['order_id'];
    $cust_name  = trim($_POST['cust_name']  ?? '');
    $cust_phone = trim($_POST['cust_phone'] ?? '');
    $cust_addr  = trim($_POST['cust_addr']  ?? '');
    $service_id = (int)($_POST['service_id'] ?? 0);
    $weight     = (float)($_POST['weight']  ?? 0);
    $notes      = trim($_POST['notes']      ?? '');

    if (!$cust_name || !$cust_phone || !$service_id || $weight <= 0) {
        $error = 'Harap isi semua kolom yang wajib diisi.';
    } else {
        $svc = $conn->prepare("SELECT * FROM services WHERE id = ?");
        $svc->bind_param('i', $service_id);
        $svc->execute();
        $svc_row = $svc->get_result()->fetch_assoc();

        if (!$svc_row) {
            $error = 'Layanan tidak ditemukan.';
        } else {
            $total_price    = $weight * $svc_row['price_per_kg'];
            $estimated_done = date('Y-m-d H:i:s', strtotime("+{$svc_row['duration_days']} days"));

            // Update/find customer by phone
            $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? LIMIT 1");
            $stmt->bind_param('s', $cust_phone);
            $stmt->execute();
            $cust_row = $stmt->get_result()->fetch_assoc();

            if ($cust_row) {
                $cust_id = $cust_row['id'];
                $upd = $conn->prepare("UPDATE customers SET name=?, address=? WHERE id=?");
                $upd->bind_param('ssi', $cust_name, $cust_addr, $cust_id);
                $upd->execute();
            } else {
                $ins = $conn->prepare("INSERT INTO customers (name, phone, address) VALUES (?,?,?)");
                $ins->bind_param('sss', $cust_name, $cust_phone, $cust_addr);
                $ins->execute();
                $cust_id = $conn->insert_id;
            }

            $upd = $conn->prepare("UPDATE orders SET customer_id=?, service_id=?, weight_kg=?, total_price=?, estimated_done=?, notes=? WHERE id=?");
            $upd->bind_param('iiiddsi', $cust_id, $service_id, $weight, $total_price, $estimated_done, $notes, $order_id);
            if ($upd->execute()) {
                $success = 'Pesanan berhasil diperbarui.';
            } else {
                $error = 'Gagal memperbarui pesanan.';
            }
        }
    }
}

// ── Update status ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];

    if (in_array($new_status, $statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $order_id);
        if ($stmt->execute()) {
            if ($new_status === 'Completed') {
                $arc = $conn->prepare("
                    SELECT o.invoice_number, c.name, c.phone, s.name AS svc, o.weight_kg, o.total_price, p.payment_method
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.id
                    JOIN services s  ON o.service_id  = s.id
                    LEFT JOIN payments p ON p.order_id = o.id
                    WHERE o.id = ?
                ");
                $arc->bind_param('i', $order_id);
                $arc->execute();
                $rd = $arc->get_result()->fetch_assoc();
                if ($rd) {
                    $ins = $conn->prepare("
                        INSERT IGNORE INTO transaction_history
                        (invoice_number, customer_name, customer_phone, service_name, weight_kg, total_price, payment_method)
                        VALUES (?,?,?,?,?,?,?)
                    ");
                    $ins->bind_param('ssssdds', $rd['invoice_number'], $rd['name'], $rd['phone'], $rd['svc'],
                                     $rd['weight_kg'], $rd['total_price'], $rd['payment_method']);
                    $ins->execute();
                }
            }
            $success = 'Status pesanan berhasil diperbarui.';
        } else {
            $error = 'Gagal memperbarui status.';
        }
    }
}

// ── Filter ────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? '';
$search        = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];
$types  = '';

if ($filter_status && in_array($filter_status, $statuses)) {
    $where[]  = 'o.status = ?';
    $params[] = $filter_status;
    $types   .= 's';
}
if ($search) {
    $where[] = '(o.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)';
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}

$whereSQL = implode(' AND ', $where);
$sql = "SELECT o.*, c.name AS cust_name, c.phone AS cust_phone,
               s.name AS svc_name, p.payment_status, p.payment_method
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        JOIN services s  ON o.service_id  = s.id
        LEFT JOIN payments p ON p.order_id = o.id
        WHERE {$whereSQL}
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!-- Filter bar -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <label class="form-label">Cari</label>
                <input type="text" name="q" class="form-control" placeholder="Invoice / nama / telepon..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-sm-4">
                <label class="form-label">Filter Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill d-flex align-items-center justify-content-center gap-1">
                    <iconify-icon icon="mdi:magnify" style="font-size: 18px;"></iconify-icon>Cari
                </button>
                <a href="manage_orders.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger mb-3"><i class="bi bi-x-circle me-2"></i><?= $error ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="d-flex align-items-center gap-2">
            <iconify-icon icon="mdi:format-list-checks" class="text-muted" style="font-size: 20px;"></iconify-icon>Daftar Pesanan
            <span class="badge bg-secondary ms-1"><?= $orders->num_rows ?></span>
        </span>
        <a href="orders.php" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
            <iconify-icon icon="mdi:plus-thick" style="font-size: 14px;"></iconify-icon>Pesanan Baru
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
                        <th>Status</th>
                        <th>Bayar</th>
                        <th style="min-width:170px;">Update Status</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($o = $orders->fetch_assoc()): ?>
                    <?php
                        $wa_data = [
                            'cust_name'      => $o['cust_name'],
                            'invoice_number' => $o['invoice_number'],
                            'svc_name'       => $o['svc_name'],
                            'weight_kg'      => $o['weight_kg'],
                            'total_price'    => $o['total_price'],
                            'created_at'     => $o['created_at'],
                            'estimated_done' => $o['estimated_done'],
                            'payment_status' => $o['payment_status'] ?? 'Unpaid',
                            'payment_method' => $o['payment_method'] ?? 'Cash',
                        ];
                        $wa_pesan = ($o['status'] === 'Ready')
                            ? pesanSiapDiambil($wa_data)
                            : pesanNotaBaru($wa_data);
                        $wa_link = generateWALink($o['cust_phone'], $wa_pesan);
                    ?>
                    <tr>
                        <td>
                            <code style="font-size:12px;color:var(--primary);"><?= $o['invoice_number'] ?></code>
                            <div style="font-size:11px;color:var(--muted);"><?= date('d/m/Y', strtotime($o['created_at'])) ?></div>
                        </td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($o['cust_name']) ?></div>
                            <div style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($o['cust_phone']) ?></div>
                            <!-- Tombol WA -->
                            <a href="<?= htmlspecialchars($wa_link) ?>" target="_blank"
                               class="btn btn-sm d-flex align-items-center gap-1 mt-1"
                               style="background:#25D366;color:#fff;border:none;font-size:11px;padding:3px 8px;border-radius:6px;width:fit-content;font-weight:600;">
                                <iconify-icon icon="mdi:whatsapp" style="font-size: 14px;"></iconify-icon> WA
                            </a>
                        </td>
                        <td style="font-size:13px;"><?= htmlspecialchars($o['svc_name']) ?></td>
                        <td><?= $o['weight_kg'] ?> kg</td>
                        <td style="font-weight:600;"><?= rupiah($o['total_price']) ?></td>
                        <td><?= statusBadge($o['status']) ?></td>
                        <td>
                            <?php
                                $isPaid = ($o['payment_status'] ?? 'Unpaid') === 'Paid';
                                $payInv = $o['invoice_number'];
                            ?>
                            <div class="d-flex flex-column gap-1" style="min-width:110px;">
                                <!-- Badge status bayar -->
                                <?php if ($isPaid): ?>
                                <span class="badge" style="background:#198754;font-size:11px;padding:4px 8px;border-radius:6px;width:fit-content;">
                                    <i class="bi bi-check-circle-fill me-1"></i>Lunas
                                </span>
                                <?php else: ?>
                                <a href="payment.php?inv=<?= $payInv ?>"
                                   class="badge text-decoration-none"
                                   style="background:#dc3545;font-size:11px;padding:4px 8px;border-radius:6px;width:fit-content;">
                                    <i class="bi bi-exclamation-circle me-1"></i>Belum Lunas
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($o['status'] !== 'Completed'): ?>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="new_status" class="form-select form-select-sm" style="min-width: 120px;">
                                    <?php foreach ($statuses as $s): ?>
                                    <option value="<?= $s ?>" <?= $s === $o['status'] ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-primary px-3 d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="mdi:check-bold" style="font-size: 14px;"></iconify-icon>
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="badge bg-dark d-inline-flex align-items-center gap-1">
                                <iconify-icon icon="mdi:check-decagram" style="font-size: 14px;"></iconify-icon> Selesai
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-warning d-flex align-items-center justify-content-center"
                                onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                    'id'         => $o['id'],
                                    'cust_name'  => $o['cust_name'],
                                    'cust_phone' => $o['cust_phone'],
                                    'cust_addr'  => $o['cust_addr'] ?? '',
                                    'service_id' => $o['service_id'],
                                    'weight_kg'  => $o['weight_kg'],
                                    'notes'      => $o['notes'] ?? '',
                                ]), ENT_QUOTES) ?>)">
                                <iconify-icon icon="mdi:pencil-outline" style="font-size: 16px;"></iconify-icon>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($orders->num_rows === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:10px;"></i>
                        Tidak ada pesanan ditemukan
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/layout_footer.php'; ?>

<!-- ── Modal Edit Pesanan ─────────────────────────────────── -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
      <div class="modal-header" style="background: var(--navy-deep); color: #fff;">
        <h5 class="modal-title d-flex align-items-center" id="editModalLabel">
            <iconify-icon icon="mdi:pencil-box-multiple" class="me-2" style="font-size: 22px;"></iconify-icon>Edit Pesanan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body p-4">
          <input type="hidden" name="edit_order" value="1">
          <input type="hidden" name="order_id" id="edit_order_id">
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label fw-semibold">Nama Pelanggan <span class="text-danger">*</span></label>
              <input type="text" name="cust_name" id="edit_cust_name" class="form-control" required
                     oninput="formatNamaEdit(this)" onkeydown="blockAngkaNamaEdit(event)">
              <div id="editNamaError" style="font-size:11px;color:#dc3545;margin-top:4px;display:none;">
                  <i class="bi bi-exclamation-circle me-1"></i>Nama tidak boleh mengandung angka
              </div>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold">No. Telepon <span class="text-danger">*</span></label>
              <input type="text" name="cust_phone" id="edit_cust_phone" class="form-control" required
                     oninput="filterAngkaSajaEdit(this)" onkeydown="blockHurufWAEdit(event)" inputmode="numeric">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Alamat</label>
              <input type="text" name="cust_addr" id="edit_cust_addr" class="form-control">
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold">Layanan <span class="text-danger">*</span></label>
              <select name="service_id" id="edit_service_id" class="form-select" required>
                <?php foreach ($services_arr as $sv): ?>
                <option value="<?= $sv['id'] ?>" data-price="<?= $sv['price_per_kg'] ?>"><?= htmlspecialchars($sv['name']) ?> — Rp <?= number_format($sv['price_per_kg'],0,',','.') ?>/kg</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-sm-3">
              <label class="form-label fw-semibold">Berat (kg) <span class="text-danger">*</span></label>
              <input type="number" name="weight" id="edit_weight" class="form-control" step="0.1" min="0.1" required>
            </div>
            <div class="col-sm-3">
              <label class="form-label fw-semibold">Estimasi Total</label>
              <div class="form-control bg-light fw-bold text-success" id="edit_total_preview">—</div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Catatan</label>
              <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer p-3 bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: var(--radius-sm);">Batal</button>
          <button type="submit" class="btn btn-accent text-white d-flex align-items-center gap-1" style="border-radius: var(--radius-sm);">
             <iconify-icon icon="mdi:content-save-outline" style="font-size: 18px;"></iconify-icon>Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?= $publicUrl ?>/js/manage_orders.js?v=<?= time() ?>"></script>
