<?php
// ============================================================
//  app/controllers/DashboardController.php
// ============================================================

class DashboardController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Ambil ringkasan statistik untuk kartu-kartu di dashboard.
     * Mengembalikan array dengan key: today, active, income, ready
     */
    public function getDashboardStats() {
        $stats = [
            'today'  => 0,
            'active' => 0,
            'income' => 0,
            'ready'  => 0,
        ];

        // Pesanan masuk hari ini
        $res = $this->conn->query("SELECT COUNT(*) AS total FROM orders WHERE DATE(created_at) = CURDATE()");
        if ($res) $stats['today'] = (int) $res->fetch_assoc()['total'];

        // Pesanan aktif (belum selesai)
        $res = $this->conn->query("SELECT COUNT(*) AS total FROM orders WHERE status != 'Completed'");
        if ($res) $stats['active'] = (int) $res->fetch_assoc()['total'];

        // Pendapatan bulan ini (dari riwayat transaksi yang sudah selesai)
        $res = $this->conn->query("
            SELECT COALESCE(SUM(total_price), 0) AS total
            FROM transaction_history
            WHERE MONTH(completed_at) = MONTH(CURDATE()) AND YEAR(completed_at) = YEAR(CURDATE())
        ");
        if ($res) $stats['income'] = (float) $res->fetch_assoc()['total'];

        // Pesanan siap diambil
        $res = $this->conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'Ready'");
        if ($res) $stats['ready'] = (int) $res->fetch_assoc()['total'];

        return $stats;
    }

    /**
     * Ambil daftar pesanan yang sedang dikerjakan (belum selesai),
     * untuk ditampilkan di tabel "Pesanan Sedang Dikerjakan".
     * Mengembalikan mysqli_result.
     */
    public function getActiveOrders($limit = 8) {
        $stmt = $this->conn->prepare("
            SELECT o.invoice_number, c.name AS cust_name, s.name AS svc_name,
                   o.total_price, o.status, p.payment_status
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN services s  ON o.service_id  = s.id
            LEFT JOIN payments p ON p.order_id = o.id
            WHERE o.status != 'Completed'
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
}
