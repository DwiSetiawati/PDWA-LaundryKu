<?php
// ============================================================
//  app/models/User.php — Model Admin/User (tabel: admins)
// ============================================================

class User {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Cari admin berdasarkan username.
     * Mengembalikan array asosiatif (id, username, password, full_name) atau null.
     */
    public function findByUsername($username) {
        $stmt = $this->conn->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: null;
    }

    /**
     * Verifikasi password polos terhadap hash yang tersimpan.
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
