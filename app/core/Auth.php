<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    private const SESSION_USER_KEY = 'admin_user';

    public static function check(): bool
    {
        return !empty($_SESSION[self::SESSION_USER_KEY])
            && ($_SESSION[self::SESSION_USER_KEY]['role'] ?? '') === 'ADMIN'
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
        $_SESSION[self::SESSION_USER_KEY] = [
            'id'        => (int) $user['id'],
            'username'  => (string) $user['username'],
            'full_name' => (string) $user['full_name'],
            'email'     => (string) ($user['email'] ?? ''),
            'role'      => (string) $user['role'],
            'status'    => (string) $user['status'],
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_USER_KEY]);
        if (session_status() === PHP_SESSION_ACTIVE) {
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
            Response::setFlash('warning', 'Vui lòng đăng nhập tài khoản quản trị để tiếp tục.');
            Response::redirect('index.php?r=admin/login');
        }
    }
}

