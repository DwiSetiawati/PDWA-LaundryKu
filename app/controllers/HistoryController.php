<?php
// ============================================================
//  app/controllers/HistoryController.php — Riwayat Transaksi
// ============================================================

require_once dirname(__DIR__) . '/helpers/helpers.php';

class HistoryController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Ambil riwayat transaksi dengan filter opsional.
     * Mengembalikan array ['rows' => array, 'total_rev' => float]
     */
    public function getHistory(string $search = '', string $from = '', string $to = ''): array {
        $where  = ['1=1'];
        $params = [];
        $types  = '';

        if ($search) {
            $where[] = '(invoice_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like]);
            $types .= 'sss';
        }
        if ($from) {
            $where[]  = 'DATE(completed_at) >= ?';
            $params[] = $from;
            $types   .= 's';
        }
        if ($to) {
            $where[]  = 'DATE(completed_at) <= ?';
            $params[] = $to;
            $types   .= 's';
        }

        $whereSQL = implode(' AND ', $where);
        $sql = "SELECT * FROM transaction_history WHERE {$whereSQL} ORDER BY completed_at DESC";

        $stmt = $this->conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows      = [];
        $total_rev = 0;
        while ($r = $result->fetch_assoc()) {
            $rows[]     = $r;
            $total_rev += $r['total_price'];
        }

        return compact('rows', 'total_rev');
    }

    /**
     * Hitung rata-rata per transaksi.
     */
    public function getAverage(array $rows, float $total_rev): float {
        return count($rows) > 0 ? $total_rev / count($rows) : 0.0;
    }

    /**
     * Ikon metode pembayaran.
     */
    public function getPaymentIcon(string $method): string {
        return match($method) {
            'Transfer' => '🏦',
            'QRIS'     => '📱',
            default    => '💵',
        };
    }
}
