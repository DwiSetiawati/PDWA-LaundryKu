<?php
// ============================================================
//  app/controllers/PaymentController.php — Kelola Pembayaran
// ============================================================

require_once dirname(__DIR__) . '/helpers/helpers.php';
require_once dirname(__DIR__) . '/config/whatsapp.php';

class PaymentController {
    private $conn;
    private $validMethods = ['Cash', 'Transfer', 'QRIS'];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Cari pesanan berdasarkan nomor invoice.
     * Mengembalikan array data pesanan atau null.
     */
    public function findByInvoice(string $invoice): ?array {
        if ($invoice === '') return null;

        $stmt = $this->conn->prepare("
            SELECT o.*, c.name AS cust_name, c.phone AS cust_phone, c.address,
                   s.name AS svc_name, s.price_per_kg,
                   p.payment_status, p.payment_method, p.amount_paid, p.paid_at
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN services s  ON o.service_id  = s.id
            LEFT JOIN payments p ON p.order_id = o.id
            WHERE o.invoice_number = ?
        ");
        $stmt->bind_param('s', $invoice);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Ambil daftar pesanan belum lunas (maks $limit baris).
     */
    public function getUnpaidOrders(int $limit = 15) {
        return $this->conn->query("
            SELECT o.id, o.invoice_number, c.name AS cust_name, c.phone,
                   o.total_price, o.status, o.created_at
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            LEFT JOIN payments p ON p.order_id = o.id
            WHERE p.payment_status = 'Unpaid' OR p.payment_status IS NULL
            ORDER BY o.created_at DESC
            LIMIT {$limit}
        ");
    }

    /**
     * Proses konfirmasi pembayaran.
     * Mengembalikan array ['success' => string, 'error' => string, 'wa_link' => string]
     */
    public function processPayment(array $post): array {
        $order_id    = (int)$post['order_id'];
        $pay_method  = $post['pay_method']  ?? 'Cash';
        $amount_paid = (float)($post['amount_paid'] ?? 0);

        if (!in_array($pay_method, $this->validMethods)) {
            return ['success' => '', 'error' => 'Metode pembayaran tidak valid.', 'wa_link' => ''];
        }
        if ($amount_paid <= 0) {
            return ['success' => '', 'error' => 'Jumlah bayar harus lebih dari 0.', 'wa_link' => ''];
        }

        $stmt = $this->conn->prepare(
            "UPDATE payments SET payment_status='Paid', payment_method=?, amount_paid=?, paid_at=NOW() WHERE order_id=?"
        );
        $stmt->bind_param('sdi', $pay_method, $amount_paid, $order_id);

        if (!$stmt->execute()) {
            return ['success' => '', 'error' => 'Gagal menyimpan pembayaran.', 'wa_link' => ''];
        }

        // Generate WA link konfirmasi lunas
        $wa_link = '';
        $o2 = $this->conn->prepare("
            SELECT o.*, c.name AS cust_name, c.phone AS cust_phone, s.name AS svc_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN services s  ON o.service_id  = s.id
            WHERE o.id = ?
        ");
        $o2->bind_param('i', $order_id);
        $o2->execute();
        $o2row = $o2->get_result()->fetch_assoc();

        if ($o2row) {
            $wa_data = [
                'cust_name'      => $o2row['cust_name'],
                'invoice_number' => $o2row['invoice_number'],
                'total_price'    => $o2row['total_price'],
                'payment_method' => $pay_method,
            ];
            $wa_link = generateWALink($o2row['cust_phone'], pesanPembayaranLunas($wa_data));
        }

        return ['success' => 'Pembayaran berhasil dicatat!', 'error' => '', 'wa_link' => $wa_link];
    }

    /**
     * Metode pembayaran yang valid.
     */
    public function getValidMethods(): array {
        return $this->validMethods;
    }
}
