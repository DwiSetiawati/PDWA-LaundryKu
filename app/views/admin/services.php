<?php
// ============================================================
//  admin/services.php — Kelola Layanan & Harga
// ============================================================
$pageTitle  = 'Layanan & Harga';
$activePage = 'services';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__) . '/layouts/layout.php';

$success = $error = '';

// ── Hapus layanan ─────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("UPDATE services SET is_active = 0 WHERE id = {$id}");
    $success = 'Layanan berhasil dihapus.';
}

// ── Tambah/Edit layanan ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $days     = (int)($_POST['days']  ?? 1);
    $desc     = trim($_POST['desc']   ?? '');

    if (!$name || $price <= 0 || $days < 1) {
        $error = 'Harap isi semua kolom dengan benar.';
    } elseif ($id > 0) {
        $stmt = $conn->prepare("UPDATE services SET name=?, price_per_kg=?, duration_days=?, description=? WHERE id=?");
        $stmt->bind_param('sdisi', $name, $price, $days, $desc, $id);
        $stmt->execute();
        $success = 'Layanan berhasil diperbarui.';
    } else {
        $stmt = $conn->prepare("INSERT INTO services (name, price_per_kg, duration_days, description) VALUES (?,?,?,?)");
        $stmt->bind_param('sdis', $name, $price, $days, $desc);
        $stmt->execute();
        $success = 'Layanan baru berhasil ditambahkan.';
    }
}

// Edit mode
$edit_svc = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM services WHERE id = {$id}");
    $edit_svc = $res->fetch_assoc();
}

$services = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name");
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <iconify-icon icon="mdi:<?= $edit_svc ? 'pencil-outline' : 'plus-circle-outline' ?>" class="text-muted me-2" style="font-size: 20px;"></iconify-icon>
                <span><?= $edit_svc ? 'Edit Layanan' : 'Tambah Layanan Baru' ?></span>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-danger mb-3"><i class="bi bi-x-circle me-2"></i><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="id" value="<?= $edit_svc['id'] ?? 0 ?>">
                    <div class="mb-3">
                        <label class="form-label">Nama Layanan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Reguler"
                               value="<?= htmlspecialchars($edit_svc['name'] ?? '') ?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Harga/kg (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" placeholder="5000"
                                   value="<?= $edit_svc['price_per_kg'] ?? '' ?>" step="500" min="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Estimasi (hari) <span class="text-danger">*</span></label>
                            <input type="number" name="days" class="form-control" placeholder="3"
                                   value="<?= $edit_svc['duration_days'] ?? '' ?>" min="1" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="desc" class="form-control" placeholder="Keterangan singkat..."
                               value="<?= htmlspecialchars($edit_svc['description'] ?? '') ?>">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill d-flex align-items-center justify-content-center gap-1">
                            <iconify-icon icon="mdi:<?= $edit_svc ? 'check-bold' : 'plus-thick' ?>" style="font-size: 16px;"></iconify-icon>
                            <span><?= $edit_svc ? 'Simpan Perubahan' : 'Tambah Layanan' ?></span>
                        </button>
                        <?php if ($edit_svc): ?>
                        <a href="services.php" class="btn btn-outline-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <iconify-icon icon="mdi:format-list-bulleted" class="text-muted me-2" style="font-size: 20px;"></iconify-icon>
                <span>Daftar Layanan</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Harga/kg</th>
                            <th>Estimasi</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($s = $services->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight:700; color: var(--navy-deep);"><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= rupiah($s['price_per_kg']) ?></td>
                            <td><?= $s['duration_days'] ?> hari</td>
                            <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($s['description'] ?? '') ?></td>
                            <td>
                                <a href="services.php?edit=<?= $s['id'] ?>"
                                   class="btn btn-sm btn-outline-primary py-1 px-2 me-1 d-inline-flex align-items-center justify-content-center">
                                    <iconify-icon icon="mdi:pencil-outline"></iconify-icon>
                                </a>
                                <a href="services.php?delete=<?= $s['id'] ?>"
                                   class="btn btn-sm btn-outline-danger py-1 px-2 d-inline-flex align-items-center justify-content-center"
                                   onclick="return confirm('Hapus layanan ini?')">
                                    <iconify-icon icon="mdi:trash-can-outline"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/layout_footer.php'; ?>
