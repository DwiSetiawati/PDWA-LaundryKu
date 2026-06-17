<?php
// ============================================================
//  app/controllers/ServiceController.php — Kelola Layanan & Harga
// ============================================================

class ServiceController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Ambil semua layanan aktif.
     */
    public function getActiveServices() {
        return $this->conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name");
    }

    /**
     * Ambil satu layanan berdasarkan ID (untuk mode edit).
     */
    public function findById(int $id): ?array {
        $res = $this->conn->query("SELECT * FROM services WHERE id = {$id}");
        return $res ? $res->fetch_assoc() : null;
    }

    /**
     * Tambah layanan baru.
     * Mengembalikan array ['success' => string, 'error' => string]
     */
    public function addService(array $post): array {
        $name  = trim($post['name']  ?? '');
        $price = (float)($post['price'] ?? 0);
        $days  = (int)($post['days']   ?? 1);
        $desc  = trim($post['desc']    ?? '');

        if (!$name || $price <= 0 || $days < 1) {
            return ['success' => '', 'error' => 'Harap isi semua kolom dengan benar.'];
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO services (name, price_per_kg, duration_days, description) VALUES (?,?,?,?)"
        );
        $stmt->bind_param('sdis', $name, $price, $days, $desc);
        $stmt->execute();

        return ['success' => 'Layanan baru berhasil ditambahkan.', 'error' => ''];
    }

    /**
     * Edit layanan yang sudah ada.
     * Mengembalikan array ['success' => string, 'error' => string]
     */
    public function updateService(int $id, array $post): array {
        $name  = trim($post['name']  ?? '');
        $price = (float)($post['price'] ?? 0);
        $days  = (int)($post['days']   ?? 1);
        $desc  = trim($post['desc']    ?? '');

        if (!$name || $price <= 0 || $days < 1) {
            return ['success' => '', 'error' => 'Harap isi semua kolom dengan benar.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE services SET name=?, price_per_kg=?, duration_days=?, description=? WHERE id=?"
        );
        $stmt->bind_param('sdisi', $name, $price, $days, $desc, $id);
        $stmt->execute();

        return ['success' => 'Layanan berhasil diperbarui.', 'error' => ''];
    }

    /**
     * Soft-delete layanan (set is_active = 0).
     */
    public function deleteService(int $id): void {
        $this->conn->query("UPDATE services SET is_active = 0 WHERE id = {$id}");
    }
}
