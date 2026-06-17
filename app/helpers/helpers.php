<?php
// ============================================================
//  app/config/helpers.php — Fungsi Pembantu Global
// ============================================================

function generateInvoice($conn) {
    $prefix = 'LND';
    $date   = date('Ymd');
    $result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
    $row    = $result->fetch_assoc();
    $seq    = str_pad($row['total'] + 1, 4, '0', STR_PAD_LEFT);
    return $prefix . '-' . $date . '-' . $seq;
}

function rupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function statusBadge($status) {
    $map = [
        'Queued'    => ['secondary', 'Antrian'],
        'Washing'   => ['primary',   'Sedang Dicuci'],
        'Ironing'   => ['warning',   'Setrika'],
        'Ready'     => ['success',   'Siap Diambil'],
        'Completed' => ['dark',      'Selesai'],
    ];
    $d = $map[$status] ?? ['light', $status];
    return "<span class=\"badge bg-{$d[0]}\">{$d[1]}</span>";
}

function paymentBadge($status) {
    if ($status === 'Paid') return '<span class="badge bg-success">Lunas</span>';
    return '<span class="badge bg-danger">Belum Lunas</span>';
}
