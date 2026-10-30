<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    /**
     * Đăng ký route kèm cấu hình kiểm soát tập trung (Mục 8.1):
     * - method: 'GET' | 'POST' | ['GET', 'POST']
     * - auth:   'admin' | null
     * - ajax:   bool
     */
    public function add(string $route, callable|array $handler, array $options = []): void
    {
        $route = trim($route, '/');
        $this->routes[$route] = [
            'handler' => $handler,
            'options' => array_merge([
                'method' => ['GET', 'POST'],
                'auth'   => null,
                'ajax'   => str_starts_with($route, 'api/'),
            ], $options),
        ];
    }

    public function dispatch(string $route): void
    {
        $route = trim($route, '/');
        if ($route === '') {
            $route = 'home';
        }

        $isApiPrefix = str_starts_with($route, 'api/');
        $isAdminPrefix = str_starts_with($route, 'admin') || str_starts_with($route, 'api/admin/');

        if ($isAdminPrefix) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        if (!isset($this->routes[$route])) {
            if ($isApiPrefix) {
                Response::json([
                    'ok'      => false,
                    'message' => 'Endpoint yêu cầu không tồn tại.',
                ], 404);
            }
            http_response_code(404);
            Response::view('storefront/404', ['pageTitle' => 'Không tìm thấy trang — ProGear Studio'], 'front');
            return;
        }

        $entry   = $this->routes[$route];
        $options = $entry['options'];
        $isAjax  = (bool) $options['ajax'];
        $reqMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        // 1. Kiểm tra phương thức HTTP (TC29, TC34)
        $allowedMethods = (array) $options['method'];
        if (!in_array($reqMethod, $allowedMethods, true)) {
            header('Allow: ' . implode(', ', $allowedMethods));
            if ($isAjax) {
                Response::json([
                    'ok'      => false,
                    'message' => 'Phương thức HTTP không được phép cho thao tác này.',
                ], 405);
            }
            http_response_code(405);
            Response::view('storefront/404', [
                'pageTitle'    => 'Phương thức không hợp lệ — ProGear Studio',
                'errorHeading' => 'Phương thức truy cập không hợp lệ (405)',
                'errorMessage' => 'Thao tác này yêu cầu phương thức gửi dữ liệu khác. Vui lòng thực hiện từ giao diện hệ thống.',
            ], 'front');
            return;
        }

        // 2. Kiểm tra phân quyền tập trung (TC26, TC27, TC30)
        if ($options['auth'] === 'admin') {
            Auth::requireAdmin($isAjax);
        }

        // 3. Kiểm tra CSRF token tập trung cho mọi request POST (TC28)
        if ($reqMethod === 'POST') {
            Csrf::requireValid($isAjax, $options['auth'] === 'admin');
        }

        // 4. Thực thi Controller
        $handler = $entry['handler'];
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->$method();
            return;
        }

        $handler();
    }
}
