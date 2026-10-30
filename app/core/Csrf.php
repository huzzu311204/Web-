<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    public static function verify(?string $token = null): bool
    {
        if ($token === null) {
            $token = $_POST['_csrf_token']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? '';
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';
        if ($sessionToken === '' || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, (string) $token);
    }

    public static function requireValid(bool $isAjax = false): void
    {
        if (!self::verify()) {
            if ($isAjax) {
                Response::json([
                    'ok'      => false,
                    'message' => 'Phiên làm việc hoặc mã bảo mật (CSRF) không hợp lệ. Vui lòng tải lại trang.',
                ], 403);
            }
            Response::setFlash('error', 'Phiên bảo mật (CSRF) không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
            $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
            Response::redirect($referer);
        }
    }
}

