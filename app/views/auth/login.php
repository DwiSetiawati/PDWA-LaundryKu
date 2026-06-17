<?php
// ============================================================
//  app/views/auth/login.php
// ============================================================
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';
require_once dirname(__DIR__, 2) . '/controllers/AuthController.php';

$auth  = new AuthController($conn);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($username && $password) {
        if ($auth->login($username, $password)) {
            header('Location: ../admin/dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi username dan password.';
    }
}

$docRoot   = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$appRoot   = str_replace('\\', '/', dirname(__DIR__, 3));
$baseUrl   = '/' . trim(str_replace($docRoot, '', $appRoot), '/');
$baseUrl   = rtrim($baseUrl, '/');
$publicUrl = $baseUrl . '/public';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — LaundryKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $publicUrl ?>/css/style.css" rel="stylesheet">
    <!-- Iconify -->
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <style>
        body {
            background: linear-gradient(180deg, #f0f9ff 0%, #ffffff 100%) !important;
        }
        .login-card-box {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
        }
        .login-input {
            background: #fff !important;
            border: 1px solid var(--border-hover) !important;
            border-left: none !important;
            color: var(--text) !important;
            transition: all 0.3s ease;
            box-shadow: none !important;
        }
        .login-input::placeholder {
            color: var(--muted) !important;
            font-weight: 400;
        }
        .login-input:focus {
            background: #fff !important;
            box-shadow: none !important;
        }
        .login-input-group-text {
            background: #fff !important;
            border: 1px solid var(--border-hover) !important;
            border-right: none !important;
            color: var(--primary) !important;
            transition: all 0.3s ease;
        }
        .input-group:focus-within .login-input,
        .input-group:focus-within .login-input-group-text {
            border-color: var(--primary) !important;
        }
        .input-group:focus-within {
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.15) !important;
            border-radius: var(--radius-sm);
        }
        .btn-primary-modern {
            background: var(--primary) !important;
            border: none !important;
            box-shadow: 0 8px 24px rgba(2, 132, 199, 0.2) !important;
            color: #fff !important;
            transition: all 0.3s ease !important;
        }
        .btn-primary-modern:hover {
            transform: translateY(-2px);
            background: #0270a8 !important;
            box-shadow: 0 12px 32px rgba(2, 132, 199, 0.3) !important;
        }
        .login-label {
            color: var(--navy-deep) !important;
            font-weight: 700 !important;
            font-size: 13px;
            margin-bottom: 8px;
        }
        /* Background Shapes matching Tracking Index */
        .bg-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .shape {
            position: absolute;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            border-radius: 50%;
            filter: blur(80px);
        }
        .shape-1 { width: 400px; height: 400px; top: -100px; right: -100px; }
        .shape-2 { width: 300px; height: 300px; bottom: -50px; left: -100px; }
    </style>
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;">
<div class="bg-shapes">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
</div>
<div style="width:100%;max-width:440px;padding:16px;z-index:1;">
    <div class="card login-card-box">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <div style="width:70px; height:70px; background:var(--accent-lt); color:var(--primary); border-radius:20px; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; box-shadow:var(--shadow-sm);">
                    <iconify-icon icon="mdi:washing-machine" style="font-size: 38px;"></iconify-icon>
                </div>
                <h4 class="brand-font" style="font-weight:800; font-size: 26px; margin:0; letter-spacing: -0.02em;">Laundry<span style="color: var(--mint);">Ku</span></h4>
                <p style="color:var(--muted); font-size: 14px;">Masuk ke Portal Admin</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger mb-4" style="font-size:13px; background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; border-radius: 12px; font-weight: 500;">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label login-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text login-input-group-text"><iconify-icon icon="mdi:account-outline" style="font-size: 22px;"></iconify-icon></span>
                        <input type="text" name="username" class="form-control login-input py-2" placeholder="Masukkan username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                </div>
                <div class="mb-5">
                    <label class="form-label login-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text login-input-group-text"><iconify-icon icon="mdi:lock-outline" style="font-size: 22px;"></iconify-icon></span>
                        <input type="password" name="password" class="form-control login-input py-2" placeholder="Masukkan password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-modern w-100 py-3 mb-4" style="font-size: 16px; font-weight: 700; border-radius: 14px;">
                    <iconify-icon icon="mdi:login" class="me-2" style="font-size: 20px; vertical-align: middle;"></iconify-icon>Masuk Portal
                </button>
                <div class="text-center">
                    <a href="<?= $baseUrl ?>/app/views/tracking/index.php" style="color: var(--muted); font-size: 13.5px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--muted)'">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Tracking
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
