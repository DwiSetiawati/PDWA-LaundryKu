<?php
// ============================================================
//  app/config/whatsapp.php — Konfigurasi & Helper WhatsApp
// ============================================================

if (!defined('TOKO_NAMA')) {
    $envFile = dirname(__DIR__, 2) . '/.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
    define('TOKO_NAMA',    $_ENV['TOKO_NAMA']    ?? 'LaundryKu');
    define('TOKO_ALAMAT',  $_ENV['TOKO_ALAMAT']  ?? 'Jl. Papua . Wosi Dalam');
    define('TOKO_TELP',    $_ENV['TOKO_TELP']    ?? '082197719828');
    define('TRACKING_URL', $_ENV['TRACKING_URL'] ?? 'http://localhost/laundry_fix/app/views/tracking/index.php');
}

function generateWALink($phone, $message) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') $phone = '62' . substr($phone, 1);
    return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
}

function pesanNotaBaru($data) {
    $tgl_masuk = date('d/m/Y H:i', strtotime($data['created_at']));
    $est       = $data['estimated_done'] ? date('d/m/Y', strtotime($data['estimated_done'])) : '-';
    $total     = 'Rp ' . number_format($data['total_price'], 0, ',', '.');
    $tracking  = TRACKING_URL . '?inv=' . urlencode($data['invoice_number']);
    return " *" . TOKO_NAMA . "* — Nota Pesanan Baru\n\nHalo, *{$data['cust_name']}*! \nPesanan laundry Anda telah kami terima.\n\n━━━━━━━━━━━━━━━━━━━\n *DETAIL PESANAN*\n━━━━━━━━━━━━━━━━━━━\nInvoice      : *{$data['invoice_number']}*\nLayanan      : {$data['svc_name']}\nBerat        : {$data['weight_kg']} kg\nTotal        : *{$total}*\nMasuk        : {$tgl_masuk}\nEst. Selesai : *{$est}*\n━━━━━━━━━━━━━━━━━━━\n\n *Lacak status cucian Anda:*\n{$tracking}\n\nSimpan nomor invoice untuk tracking.\nTerima kasih telah mempercayai kami! \n\n_" . TOKO_NAMA . " • " . TOKO_TELP . "_";
}

function pesanSiapDiambil($data) {
    $total        = 'Rp ' . number_format($data['total_price'], 0, ',', '.');
    $status_bayar = ($data['payment_status'] === 'Paid') ? ' Sudah Lunas' : ' Belum Lunas — ' . $total;
    return " *" . TOKO_NAMA . "* — Cucian Siap Diambil!\n\nHalo, *{$data['cust_name']}*! \nCucian Anda sudah selesai dan siap diambil!\n\n━━━━━━━━━━━━━━━━━━━\n Invoice     : *{$data['invoice_number']}*\n Layanan     : {$data['svc_name']}\n Pembayaran  : {$status_bayar}\n━━━━━━━━━━━━━━━━━━━\n\n *Alamat Toko:*\n" . TOKO_ALAMAT . "\n " . TOKO_TELP . "\n\nHarap diambil segera ya! Terima kasih \n\n_" . TOKO_NAMA . "_";
}

function pesanPembayaranLunas($data) {
    $total  = 'Rp ' . number_format($data['total_price'], 0, ',', '.');
    $tgl    = date('d/m/Y H:i');
    $metode = $data['payment_method'] ?? 'Cash';
    return " *" . TOKO_NAMA . "* — Pembayaran Diterima\n\nHalo, *{$data['cust_name']}*!\nPembayaran Anda telah kami terima. Terima kasih!\n\n━━━━━━━━━━━━━━━━━━━\n Invoice   : *{$data['invoice_number']}*\n Total     : *{$total}*\n Metode    : {$metode}\n Tgl Bayar : {$tgl}\n━━━━━━━━━━━━━━━━━━━\n\nSampai jumpa di kunjungan berikutnya! \n\n_" . TOKO_NAMA . " • " . TOKO_TELP . "_";
}
