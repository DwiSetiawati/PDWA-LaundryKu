<?php
// ============================================================
//  app/controllers/ManageOrdersController.php — Kelola & Update Status Pesanan
// ============================================================

require_once dirname(__DIR__) . '/helpers/helpers.php';
require_once dirname(__DIR__) . '/config/whatsapp.php';

class ManageOrdersController {
    private $conn;
    private $statuses = ['Queued', 'Washing', 'Ironing', 'Ready', 'Completed'];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Ambil daftar layanan aktif (untuk form edit modal).
     */
    public function getServicesList(): array {
        $result = $this->conn->query("SELECT id, name, price_per_kg, duration_days FROM services WHERE is_active=1 ORDER BY name");
        $arr = [];
        while ($row = $result->fetch_assoc()) $arr[] = $row;
        return $arr;
    }

    /**
     * Daftar status pesanan yang valid.
     */
    public function getStatuses(): array {
        return $this->statuses;
    }

    /**
     * Ambil daftar pesanan dengan filter opsional.
     * Mengembalikan mysqli_result.
     */
    public function getOrders(string $filterStatus = '', string $search = '') {
        $where  = ['1=1'];
        $params = [];
        $types  = '';

        if ($filterStatus && in_array($filterStatus, $this->statuses)) {
            $where[]  = 'o.status = ?';
            $params[] = $filterStatus;
            $types   .= 's';
        }
        if ($search) {
            $where[] = '(o.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like]);
            $types .= 'sss';
        }

        $whereSQL = implode(' AND ', $where);
        $sql = "SELECT o.*, c.name AS cust_name, c.phone AS cust_phone, c.address AS cust_addr,
                       s.name AS svc_name, p.payment_status, p.payment_method
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                JOIN services s  ON o.service_id  = s.id
                LEFT JOIN payments p ON p.order_id = o.id
                WHERE {$whereSQL}
                ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Edit data pesanan (customer + detail pesanan).
     * Mengembalikan array ['success' => string, 'error' => string]
     */
    public function editOrder(array $post): array {
        $order_id   = (int)$post['order_id'];
        $cust_name  = trim($post['cust_name']  ?? '');
        $cust_phone = trim($post['cust_phone'] ?? '');
        $cust_addr  = trim($post['cust_addr']  ?? '');
        $service_id = (int)($post['service_id'] ?? 0);
        $weight     = (float)($post['weight']  ?? 0);
        $notes      = trim($post['notes']      ?? '');

        if (!$cust_name || !$cust_phone || !$service_id || $weight <= 0) {
            return ['success' => '', 'error' => 'Harap isi semua kolom yang wajib diisi.'];
        }

        $svc = $this->conn->prepare("SELECT * FROM services WHERE id = ?");
        $svc->bind_param('i', $service_id);
        $svc->execute();
        $svc_row = $svc->get_result()->fetch_assoc();

        if (!$svc_row) {
            return ['success' => '', 'error' => 'Layanan tidak ditemukan.'];
        }

        $total_price    = $weight * $svc_row['price_per_kg'];
        $estimated_done = date('Y-m-d H:i:s', strtotime("+{$svc_row['duration_days']} days"));

        // Update atau buat customer
        $stmt = $this->conn->prepare("SELECT id FROM customers WHERE phone = ? LIMIT 1");
        $stmt->bind_param('s', $cust_phone);
        $stmt->execute();
        $cust_row = $stmt->get_result()->fetch_assoc();

        if ($cust_row) {
            $cust_id = $cust_row['id'];
            $upd = $this->conn->prepare("UPDATE customers SET name=?, address=? WHERE id=?");
            $upd->bind_param('ssi', $cust_name, $cust_addr, $cust_id);
            $upd->execute();
        } else {
            $ins = $this->conn->prepare("INSERT INTO customers (name, phone, address) VALUES (?,?,?)");
            $ins->bind_param('sss', $cust_name, $cust_phone, $cust_addr);
            $ins->execute();
            $cust_id = $this->conn->insert_id;
        }

        $upd = $this->conn->prepare(
            "UPDATE orders SET customer_id=?, service_id=?, weight_kg=?, total_price=?, estimated_done=?, notes=? WHERE id=?"
        );
        $upd->bind_param('iiiddsi', $cust_id, $service_id, $weight, $total_price, $estimated_done, $notes, $order_id);

        if ($upd->execute()) {
            return ['success' => 'Pesanan berhasil diperbarui.', 'error' => ''];
        }
        return ['success' => '', 'error' => 'Gagal memperbarui pesanan.'];
    }

    /**
     * Update status pesanan. Jika Completed, arsipkan ke transaction_history.
     * Mengembalikan array ['success' => string, 'error' => string]
     */
    public function updateStatus(int $order_id, string $new_status): array {
        if (!in_array($new_status, $this->statuses)) {
            return ['success' => '', 'error' => 'Status tidak valid.'];
        }

        $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $order_id);

        if (!$stmt->execute()) {
            return ['success' => '', 'error' => 'Gagal memperbarui status.'];
        }

        // Arsipkan ke transaction_history jika selesai
        if ($new_status === 'Completed') {
            $arc = $this->conn->prepare("
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
                $ins = $this->conn->prepare("
                    INSERT IGNORE INTO transaction_history
                    (invoice_number, customer_name, customer_phone, service_name, weight_kg, total_price, payment_method)
                    VALUES (?,?,?,?,?,?,?)
                ");
                $ins->bind_param('ssssdds',
                    $rd['invoice_number'], $rd['name'], $rd['phone'], $rd['svc'],
                    $rd['weight_kg'], $rd['total_price'], $rd['payment_method']
                );
                $ins->execute();
            }
        }

        return ['success' => 'Status pesanan berhasil diperbarui.', 'error' => ''];
    }
}
