<?php
// ============================================================
//  app/controllers/ReportController.php — Laporan Pendapatan Bulanan
// ============================================================

class ReportController {
    private $conn;
    private $months_id = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Validasi dan normalisasi bulan & tahun dari input GET.
     */
    public function parseMonthYear(array $get): array {
        $month = (int)($get['month'] ?? date('n'));
        $year  = (int)($get['year']  ?? date('Y'));
        $month = max(1, min(12, $month));
        $year  = max(2020, min((int)date('Y') + 1, $year));
        return ['month' => $month, 'year' => $year];
    }

    /**
     * Ambil nama bulan dalam Bahasa Indonesia.
     */
    public function getMonthName(int $month): string {
        return $this->months_id[$month] ?? '';
    }

    /**
     * Ambil seluruh daftar nama bulan (untuk dropdown filter).
     */
    public function getAllMonths(): array {
        return $this->months_id;
    }

    /**
     * Ambil data transaksi untuk bulan & tahun tertentu.
     * Mengembalikan array:
     *   rows       => array semua transaksi
     *   total      => float total pendapatan
     *   by_day     => array [hari => total]
     *   by_service => array [nama_layanan => total]
     */
    public function getReportData(int $month, int $year): array {
        $stmt = $this->conn->prepare("
            SELECT th.*, DAYOFMONTH(th.completed_at) AS day_num
            FROM transaction_history th
            WHERE MONTH(th.completed_at) = ? AND YEAR(th.completed_at) = ?
            ORDER BY th.completed_at ASC
        ");
        $stmt->bind_param('ii', $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows       = [];
        $total      = 0;
        $by_day     = [];
        $by_service = [];

        while ($r = $result->fetch_assoc()) {
            $rows[]  = $r;
            $total  += $r['total_price'];
            $d = $r['day_num'];
            $by_day[$d]                = ($by_day[$d] ?? 0) + $r['total_price'];
            $by_service[$r['service_name']] = ($by_service[$r['service_name']] ?? 0) + $r['total_price'];
        }

        return compact('rows', 'total', 'by_day', 'by_service');
    }

    /**
     * Encode data chart ke JSON untuk dikirim ke JavaScript.
     */
    public function getChartJson(array $by_day, array $by_service): array {
        return [
            'chart_labels' => json_encode(array_keys($by_day)),
            'chart_data'   => json_encode(array_values($by_day)),
            'svc_labels'   => json_encode(array_keys($by_service)),
            'svc_data'     => json_encode(array_values($by_service)),
        ];
    }
}
