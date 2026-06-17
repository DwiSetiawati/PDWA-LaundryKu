<?php
// ============================================================
//  app/views/layouts/layout.php — Layout Admin (Header/Sidebar)
// ============================================================

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Base URL untuk asset — deteksi otomatis subfolder instalasi
$docRoot   = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$appRoot   = str_replace('\\', '/', dirname(__DIR__, 3)); // 3 level up dari app/views/layouts/
$baseUrl   = '/' . trim(str_replace($docRoot, '', $appRoot), '/');
$publicUrl = $baseUrl . '/public';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . $baseUrl . '/app/views/auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — LaundryKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $publicUrl ?>/css/style.css" rel="stylesheet">
    <!-- Iconify -->
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
</head>
<body>
<div class="admin-sidebar-overlay" id="sidebarOverlay"></div>

<?php require_once dirname(__DIR__) . '/components/sidebar.php'; ?>

<!-- ── Main Content ──────────────────────────────────────────── -->
<div class="main-content">
    <!-- Bubble Background Elements -->
    <div class="bubbles-bg">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <?php require_once dirname(__DIR__) . '/components/topbar.php'; ?>
    <div class="content-area" style="position: relative; z-index: 1;">
