<?php
// ============================================================
//  app/views/auth/logout.php — Keluar dari Admin Panel
// ============================================================

require_once dirname(__DIR__, 2) . '/controllers/LogoutController.php';

$logout = new LogoutController();
$logout->logout();

header('Location: login.php');
exit;
