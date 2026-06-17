<?php
// ============================================================
//  app/controllers/OrderController.php — Input Pesanan Baru
// ============================================================

require_once dirname(__DIR__) . '/helpers/helpers.php';
require_once dirname(__DIR__) . '/config/whatsapp.php';

class OrderController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Ambil semua layanan aktif untuk ditampilkan di form pesanan.
     */
    public function getActiveServices() {
        return $this->conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name");
    }

    /**
     * Buat pesanan baru dari data POST.
     * Mengembalikan array ['success' => string, 'error' => string, 'wa_link' => string]
     */
    public function createOrder(array $post): array {
        $cust_name  = trim($post['cust_name']  ?? '');
        $cust_phone = trim($post['cust_phone'] ?? '');
        $cust_addr  = trim($post['cust_addr']  ?? '');
        $service_id = (int)($post['service_id'] ?? 0);
        $weight     = (float)($post['weight']  ?? 0);
        $notes      = trim($post['notes']      ?? '');

        if (!$cust_name || !$cust_phone || !$service_id || $weight <= 0) {
            return ['success' => '', 'error' => 'Harap isi semua kolom yang wajib diisi.', 'wa_link' => ''];
        }

        $svc = $this->conn->prepare("SELECT * FROM services WHERE id = ?");
        $svc->bind_param('i', $service_id);
        $svc->execute();
        $svc_row = $svc->get_result()->fetch_assoc();

        if (!$svc_row) {
            return ['success' => '', 'error' => 'Layanan tidak ditemukan.', 'wa_link' => ''];
        }

        $total_price    = $weight * $svc_row['price_per_kg'];
        $estimated_done = date('Y-m-d H:i:s', strtotime("+{$svc_row['duration_days']} days"));

        // Cek / buat customer
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

        // Buat pesanan
        $invoice = generateInvoice($this->conn);
        $ord = $this->conn->prepare(
            "INSERT INTO orders (invoice_number, customer_id, service_id, weight_kg, total_price, estimated_done, notes)
             VALUES (?,?,?,?,?,?,?)"
        );
        $ord->bind_param('siiddss', $invoice, $cust_id, $service_id, $weight, $total_price, $estimated_done, $notes);
        $ord->execute();
        $order_id = $this->conn->insert_id;

        // Buat record pembayaran awal
        $pay = $this->conn->prepare("INSERT INTO payments (order_id, payment_status, payment_method) VALUES (?, 'Unpaid', 'Cash')");
        $pay->bind_param('i', $order_id);
        $pay->execute();

        // Generate link WhatsApp
        $wa_data = [
            'cust_name'      => $cust_name,
            'invoice_number' => $invoice,
            'svc_name'       => $svc_row['name'],
            'weight_kg'      => $weight,
            'total_price'    => $total_price,
            'created_at'     => date('Y-m-d H:i:s'),
            'estimated_done' => $estimated_done,
        ];
        $wa_link = generateWALink($cust_phone, pesanNotaBaru($wa_data));

        return [
            'success'  => "Pesanan berhasil dibuat! Invoice: <strong>{$invoice}</strong> — Total: <strong>" . rupiah($total_price) . "</strong>",
            'error'    => '',
            'wa_link'  => $wa_link,
            'invoice'  => $invoice,
        ];
    }
}
