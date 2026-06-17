<?php
// ============================================================
//  app/controllers/AuthController.php — Autentikasi Admin
// ============================================================

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/helpers.php';
require_once dirname(__DIR__) . '/models/User.php';

class AuthController {
    private $conn;
    private $userModel;

    public function __construct($conn) {
        $this->conn      = $conn;
        $this->userModel = new User($conn);
    }

    public function login($username, $password) {
        $admin = $this->userModel->findByUsername($username);
        if ($admin && $this->userModel->verifyPassword($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            return true;
        }
        return false;
    }

    public function logout() {
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
}
