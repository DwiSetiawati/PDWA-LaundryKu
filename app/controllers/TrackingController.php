<?php
// ============================================================
//  app/controllers/TrackingController.php
// ============================================================

class TrackingController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Cari pesanan berdasarkan nomor invoice.
     * Mengembalikan array ['order' => array|null, 'error' => string]
     */
    public function tracking($invoice) {
        $order = null;
        $error = '';

        if ($invoice === '') {
            return ['order' => null, 'error' => ''];
        }

        $stmt = $this->conn->prepare("
            SELECT o.*, c.name AS cust_name, c.phone AS cust_phone,
                   s.name AS svc_name, p.payment_status
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN services s  ON o.service_id  = s.id
            LEFT JOIN payments p ON p.order_id = o.id
            WHERE o.invoice_number = ?
        ");
        $stmt->bind_param('s', $invoice);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            $error = "Invoice \"{$invoice}\" tidak ditemukan. Periksa kembali nomor invoice Anda.";
            $order = null;
        }

        return ['order' => $order, 'error' => $error];
    }

    /**
     * Urutan status pesanan beserta icon & label untuk timeline tracking.
     */
    public function getStatusFlow() {
        return [
            'Queued'    => ['icon' => '📝', 'label' => 'Antrian'],
            'Washing'   => ['icon' => '🌀', 'label' => 'Dicuci'],
            'Ironing'   => ['icon' => '👔', 'label' => 'Disetrika'],
            'Ready'     => ['icon' => '📦', 'label' => 'Siap Diambil'],
            'Completed' => ['icon' => '✅', 'label' => 'Selesai'],
        ];
    }

    /**
     * Index status saat ini berdasarkan urutan getStatusFlow().
     */
    public function getStatusIndex($status) {
        $keys = array_keys($this->getStatusFlow());
        $idx  = array_search($status, $keys);
        return $idx === false ? -1 : $idx;
    }
}
