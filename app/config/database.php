<?php
// ============================================================
//  app/config/database.php — Koneksi Database (baca dari .env)
// ============================================================

if (!defined('DB_HOST')) {
    $envFile = dirname(__DIR__, 2) . '/.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'laundry_db');
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:20px;color:red;">
        <h3>❌ Koneksi Database Gagal</h3>
        <p>' . $conn->connect_error . '</p>
        <p>Pastikan XAMPP MySQL sudah berjalan dan database <strong>laundry_db</strong> sudah dibuat.</p>
    </div>');
}

$conn->set_charset('utf8mb4');
