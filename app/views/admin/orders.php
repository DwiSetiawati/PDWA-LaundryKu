<?php
// ============================================================
//  app/views/admin/orders.php — Input Pesanan Baru
// ============================================================
$pageTitle  = 'Input Pesanan';
$activePage = 'orders';
require_once dirname(__DIR__) . '/layouts/layout.php';
require_once dirname(__DIR__, 2) . '/config/whatsapp.php';

$success = $error = '';
$wa_link_created = '';

$services_result = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            $invoice = generateInvoice($conn);
            $ord = $conn->prepare("INSERT INTO orders (invoice_number, customer_id, service_id, weight_kg, total_price, estimated_done, notes) VALUES (?,?,?,?,?,?,?)");
            $ord->bind_param('siiddss', $invoice, $cust_id, $service_id, $weight, $total_price, $estimated_done, $notes);
            $ord->execute();
            $order_id_created = $conn->insert_id;

            $pay = $conn->prepare("INSERT INTO payments (order_id, payment_status, payment_method) VALUES (?, 'Unpaid', 'Cash')");
            $pay->bind_param('i', $order_id_created);
            $pay->execute();

            $wa_data = [
                'cust_name'      => $cust_name,
                'invoice_number' => $invoice,
                'svc_name'       => $svc_row['name'],
                'weight_kg'      => $weight,
                'total_price'    => $total_price,
                'created_at'     => date('Y-m-d H:i:s'),
                'estimated_done' => $estimated_done,
            ];
            $wa_link_created = generateWALink($cust_phone, pesanNotaBaru($wa_data));
            $success = "Pesanan berhasil dibuat! Invoice: <strong>{$invoice}</strong> — Total: <strong>" . rupiah($total_price) . "</strong>";
        }
    }
}

$services_result = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name");
?>

<?php if ($success): ?>
<div class="alert alert-success mb-4" style="border-radius:12px;">
    <div class="d-flex align-items-start gap-3">
        <i class="bi bi-check-circle-fill text-success" style="font-size:24px;margin-top:2px;"></i>
        <div class="flex-fill">
            <div style="font-weight:700;font-size:15px;margin-bottom:4px;">Pesanan berhasil dibuat!</div>
            <div style="font-size:13px;margin-bottom:12px;"><?= $success ?></div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= htmlspecialchars($wa_link_created) ?>" target="_blank" class="btn btn-success btn-sm">
                    <i class="bi bi-whatsapp me-1"></i>Kirim Nota via WhatsApp
                </a>
                <a href="manage_orders.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-list-check me-1"></i>Lihat Semua Pesanan
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <iconify-icon icon="mdi:plus-circle-outline" class="text-muted me-2" style="font-size: 20px;"></iconify-icon>
                <span>Form Pesanan Baru</span>
            </div>
            <div class="card-body">
                <form method="POST" id="orderForm" autocomplete="off">
                    <h6 class="section-label mb-3">Data Pelanggan</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="cust_name" id="custName" class="form-control"
                                   placeholder="Budi Santoso"
                                   value="<?= htmlspecialchars($_POST['cust_name'] ?? '') ?>" required
                                   oninput="formatNama(this)" onkeydown="blockAngkaNama(event)">
                            <div id="namaError" style="font-size:11px;color:#dc3545;margin-top:4px;display:none;">
                                <i class="bi bi-exclamation-circle me-1"></i>Nama tidak boleh mengandung angka
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><iconify-icon icon="mdi:whatsapp" style="color: #25D366; font-size: 18px;"></iconify-icon></span>
                                <input type="text" name="cust_phone" id="custPhone" class="form-control"
                                       placeholder="08xxxxxxxxxx"
                                       value="<?= htmlspecialchars($_POST['cust_phone'] ?? '') ?>" required
                                       oninput="filterAngkaSaja(this)" onkeydown="blockHurufWA(event)" inputmode="numeric">
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Alamat <span class="text-muted">(opsional)</span></label>
                        <input type="text" name="cust_addr" class="form-control" placeholder="Jl. ..."
                               value="<?= htmlspecialchars($_POST['cust_addr'] ?? '') ?>">
                    </div>
                    <hr style="border-color:var(--border);margin:20px 0;">
                    <h6 class="section-label mb-3">Detail Pesanan</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-7">
                            <label class="form-label">Jenis Layanan <span class="text-danger">*</span></label>
                            <select name="service_id" class="form-select" id="serviceSelect" required>
                                <option value="">— Pilih Layanan —</option>
                                <?php while ($svc = $services_result->fetch_assoc()): ?>
                                <option value="<?= $svc['id'] ?>"
                                        data-price="<?= $svc['price_per_kg'] ?>"
                                        data-days="<?= $svc['duration_days'] ?>"
                                        <?= (($_POST['service_id'] ?? '') == $svc['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($svc['name']) ?> — <?= rupiah($svc['price_per_kg']) ?>/kg
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label">Berat (kg) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="weight" class="form-control" id="weightInput"
                                       placeholder="0.0" step="0.1" min="0.1"
                                       value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>" required>
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Pakaian sensitif, warna khusus, dll..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                    <div id="pricePreview" class="mb-4 p-3" style="background:var(--accent-lt);border-radius:10px;display:none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;">Estimasi Total</div>
                                <div id="previewTotal" style="font-size:24px;font-weight:800;color:var(--primary);"></div>
                            </div>
                            <div class="text-end">
                                <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;">Estimasi Selesai</div>
                                <div id="previewDate" style="font-size:14px;font-weight:700;color:var(--accent);"></div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3" style="font-size:15px; font-weight: 600;">
                        <iconify-icon icon="mdi:receipt-text-plus-outline" class="me-2" style="font-size: 18px; vertical-align: middle;"></iconify-icon>Buat Pesanan & Kirim Nota WA
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center">
                <iconify-icon icon="mdi:washing-machine" class="text-muted me-2" style="font-size: 20px;"></iconify-icon>
                <span>Daftar Layanan</span>
            </div>
            <div class="card-body p-0">
                <?php
                $svc_list = $conn->query("SELECT * FROM services WHERE is_active = 1");
                while ($s = $svc_list->fetch_assoc()):
                ?>
                <div class="d-flex justify-content-between align-items-center px-4 py-3"
                     style="border-bottom:1px solid var(--border);">
                    <div>
                        <div style="font-weight:700;font-size:14px; color: var(--navy-deep);"><?= htmlspecialchars($s['name']) ?></div>
                        <div style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($s['description']) ?></div>
                    </div>
                    <div class="text-end">
                        <div style="font-weight:700;color:var(--primary);"><?= rupiah($s['price_per_kg']) ?>/kg</div>
                        <div style="font-size:12px;color:var(--muted);"><?= $s['duration_days'] ?> hari</div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<style>
.section-label { font-size:11px; text-transform:uppercase; letter-spacing:1px; color:var(--muted); font-weight:700; }
</style>
<script src="<?= $publicUrl ?>/js/orders.js?v=<?= time() ?>"></script>
<?php require_once dirname(__DIR__) . '/layouts/layout_footer.php'; ?>
