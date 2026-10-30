<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    private const SESSION_USER_KEY = 'admin_user';
    private static bool $verifiedThisRequest = false;

    public static function check(): bool
    {
        if (empty($_SESSION[self::SESSION_USER_KEY]['id'])) {
            return false;
        }

        if (!self::$verifiedThisRequest) {
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare('SELECT id, username, full_name, email, role, status FROM users WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => (int) $_SESSION[self::SESSION_USER_KEY]['id']]);
                $freshUser = $stmt->fetch();

                if (!$freshUser || $freshUser['role'] !== 'ADMIN' || $freshUser['status'] !== 'ACTIVE') {
                    self::logout();
                    return false;
                }

                $_SESSION[self::SESSION_USER_KEY] = [
                    'id'        => (int) $freshUser['id'],
                    'username'  => (string) $freshUser['username'],
                    'full_name' => (string) $freshUser['full_name'],
                    'email'     => (string) ($freshUser['email'] ?? ''),
                    'role'      => (string) $freshUser['role'],
                    'status'    => (string) $freshUser['status'],
                ];
                self::$verifiedThisRequest = true;
            } catch (\Throwable $e) {
                error_log('Auth verification error: ' . $e->getMessage());
                return false;
            }
        }

        return ($_SESSION[self::SESSION_USER_KEY]['role'] ?? '') === 'ADMIN'
            && ($_SESSION[self::SESSION_USER_KEY]['status'] ?? '') === 'ACTIVE';
    }

    public static function user(): ?array
    {
        return self::check() ? $_SESSION[self::SESSION_USER_KEY] : null;
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION[self::SESSION_USER_KEY]['id'] : null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        unset($_SESSION['_login_attempts']);
        $_SESSION[self::SESSION_USER_KEY] = [
            'id'        => (int) $user['id'],
            'username'  => (string) $user['username'],
            'full_name' => (string) $user['full_name'],
            'email'     => (string) ($user['email'] ?? ''),
            'role'      => (string) $user['role'],
            'status'    => (string) $user['status'],
        ];
        self::$verifiedThisRequest = true;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_USER_KEY]);
        self::$verifiedThisRequest = false;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    (bool) $params['secure'],
                    (bool) $params['httponly']
                );
            }
            session_destroy();
            session_start();
            session_regenerate_id(true);
        }
    }

    public static function requireAdmin(bool $isAjax = false): void
    {
        if (!self::check()) {
            if ($isAjax) {
                Response::json([
                    'ok'      => false,
                    'message' => 'Truy cập bị từ chối. Vui lòng đăng nhập quyền Quản trị viên.',
                ], 401);
            }
            Response::setFlash('warning', 'Vui lòng đăng nhập tài khoản quản trị hợp lệ để tiếp tục.');
            Response::redirect('index.php?r=admin/login');
        }
    }
}
