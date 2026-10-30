<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $route, callable|array $handler): void
    {
        $this->routes[$route] = $handler;
    }

    public function dispatch(string $route): void
    {
        $route = trim($route, '/');
        if ($route === '') {
            $route = 'home';
        }

        if (!isset($this->routes[$route])) {
            if (str_starts_with($route, 'api/')) {
                Response::json([
                    'ok'      => false,
                    'message' => 'Endpoint API không tồn tại: ' . $route,
                ], 404);
            }
            http_response_code(404);
            Response::view('storefront/404', ['pageTitle' => 'Không tìm thấy trang'], 'front');
            return;
        }

        $handler = $this->routes[$route];
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->$method();
            return;
        }

        $handler();
    }
}

