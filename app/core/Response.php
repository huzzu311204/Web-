<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function json(array $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    public static function getFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    public static function view(string $viewPath, array $data = [], ?string $layout = 'front'): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = dirname(__DIR__) . '/views/' . $viewPath . '.php';
        if (!is_file($viewFile)) {
            http_response_code(404);
            echo 'Không tìm thấy giao diện: ' . htmlspecialchars($viewPath, ENT_QUOTES, 'UTF-8');
            return;
        }

        if ($layout === null) {
            require $viewFile;
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = dirname(__DIR__) . '/views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }

        require $layoutFile;
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    public static function money(int|float|string $amount): string
    {
        return number_format((float) $amount, 0, ',', '.') . ' đ';
    }
}

