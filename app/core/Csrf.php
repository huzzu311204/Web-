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
        if ($sessionToken === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function requireValid(bool $isAjax = false, bool $isAdminRoute = false): void
    {
        if (!self::verify()) {
            if ($isAjax) {
                Response::json([
                    'ok'      => false,
                    'message' => 'Phiên làm việc hoặc mã bảo mật (CSRF) không hợp lệ. Vui lòng tải lại trang.',
                ], 403);
            }
            Response::setFlash('error', 'Phiên bảo mật (CSRF) không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
            $fallback = $isAdminRoute ? 'index.php?r=admin/products' : 'index.php?r=home';
            Response::redirect(self::safeRedirectTarget($fallback));
        }
    }

    /**
     * Ưu tiên chuyển về route cố định an toàn; chỉ dùng Referer nếu khớp tuyệt đối cùng host và route hợp lệ (Mục 11 & TC28)
     */
    public static function safeRedirectTarget(string $fallback = 'index.php?r=home'): string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer === '') {
            return $fallback;
        }

        $parts = parse_url($referer);
        if ($parts === false || empty($parts['host'])) {
            return $fallback;
        }

        $currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $refererHost = strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
        if ($currentHost === '' || ($refererHost !== $currentHost && strtolower($parts['host']) !== $currentHost)) {
            return $fallback;
        }

        $path = $parts['path'] ?? '';
        $base = basename($path);
        if ($base !== 'index.php' && $base !== '') {
            return $fallback;
        }

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $qParams);
            $r = (string) ($qParams['r'] ?? '');
            if ($r !== '' && preg_match('/^[a-zA-Z0-9_\/-]+$/', $r)) {
                return 'index.php?r=' . rawurlencode($r);
            }
        }

        return $fallback;
    }
}
