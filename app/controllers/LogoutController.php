<?php
// ============================================================
//  app/controllers/LogoutController.php
// ============================================================

class LogoutController {

    /**
     * Hapus session admin yang sedang login.
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }
}
