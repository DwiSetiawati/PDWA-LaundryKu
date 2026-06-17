<?php
// ============================================================
//  fix_password.php — Perbaiki password admin di database
//  HAPUS FILE INI setelah berhasil login!
// ============================================================

require_once __DIR__ . '/app/config/database.php';

$hash = '$2y$10$kDMH0BFbeB.MlFmLJ2DSqOQjRLBYAYieIZnVewftS2tb0tuA02mTq';
// Hash di atas = password_hash('admin123', PASSWORD_DEFAULT)

$stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = 'admin'");
$stmt->bind_param('s', $hash);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "<h2 style='color:green'>✅ Password berhasil diperbaiki!</h2>";
    echo "<p>Sekarang kamu bisa login dengan:</p>";
    echo "<ul><li><strong>Username:</strong> admin</li><li><strong>Password:</strong> admin123</li></ul>";
    echo "<p style='color:red'><strong>⚠️ PENTING: Hapus file fix_password.php ini sekarang!</strong></p>";
    echo "<p><a href='app/views/auth/login.php'>→ Ke halaman login</a></p>";
} else {
    echo "<h2 style='color:orange'>⚠️ Tidak ada baris yang diupdate.</h2>";
    echo "<p>Kemungkinan username 'admin' belum ada. Coba jalankan query berikut di phpMyAdmin:</p>";
    echo "<pre style='background:#f5f5f5;padding:12px'>";
    echo "INSERT INTO admins (username, password, full_name)\nVALUES ('admin', '$hash', 'Administrator');";
    echo "</pre>";
}
