<?php
declare(strict_types=1);

// Tắt hiển thị lỗi trực tiếp ra trình duyệt khi chạy thực tế để đảm bảo bảo mật (mục 11)
ini_set('display_errors', '0');
error_reporting(E_ALL);

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Thiết lập cookie session an toàn (HttpOnly & SameSite)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Autoloader chuẩn PSR-4 cho namespace App\*
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);
    $className = array_pop($parts);
    $subDir = strtolower(implode('/', $parts));

    $file = dirname(__DIR__) . '/app/' . ($subDir ? $subDir . '/' : '') . $className . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use App\Core\Router;
use App\Core\Response;

$router = new Router();

// --- Storefront Routes (Khách hàng - Không cần đăng nhập) ---
$router->add('home', [\App\Controllers\Storefront\HomeController::class, 'index']);
$router->add('catalog', [\App\Controllers\Storefront\CatalogController::class, 'index']);
$router->add('product', [\App\Controllers\Storefront\CatalogController::class, 'detail']);
$router->add('cart', [\App\Controllers\Storefront\CartController::class, 'index']);
$router->add('request', [\App\Controllers\Storefront\RentalRequestController::class, 'submit']);
$router->add('request/success', [\App\Controllers\Storefront\RentalRequestController::class, 'success']);
$router->add('lookup', [\App\Controllers\Storefront\LookupController::class, 'index']);
$router->add('lookup/cancel', [\App\Controllers\Storefront\LookupController::class, 'cancel']);
$router->add('lookup/extend', [\App\Controllers\Storefront\LookupController::class, 'extend']);
$router->add('lookup/cancel-extension', [\App\Controllers\Storefront\LookupController::class, 'cancelExtension']);
$router->add('policy', [\App\Controllers\Storefront\PolicyController::class, 'index']);

// --- Admin Routes (Quản trị viên - Bảo vệ bởi Auth::requireAdmin ở Backend) ---
$router->add('admin/login', [\App\Controllers\Admin\AuthController::class, 'login']);
$router->add('admin/logout', [\App\Controllers\Admin\AuthController::class, 'logout']);
$router->add('admin', [\App\Controllers\Admin\DashboardController::class, 'index']);
$router->add('admin/dashboard', [\App\Controllers\Admin\DashboardController::class, 'index']);

$router->add('admin/products', [\App\Controllers\Admin\ProductController::class, 'index']);
$router->add('admin/products/create', [\App\Controllers\Admin\ProductController::class, 'create']);
$router->add('admin/products/edit', [\App\Controllers\Admin\ProductController::class, 'edit']);

$router->add('admin/categories', [\App\Controllers\Admin\CategoryController::class, 'index']);
$router->add('admin/categories/save', [\App\Controllers\Admin\CategoryController::class, 'save']);
$router->add('admin/categories/delete', [\App\Controllers\Admin\CategoryController::class, 'delete']);

$router->add('admin/brands', [\App\Controllers\Admin\BrandController::class, 'index']);
$router->add('admin/brands/save', [\App\Controllers\Admin\BrandController::class, 'save']);
$router->add('admin/brands/delete', [\App\Controllers\Admin\BrandController::class, 'delete']);

$router->add('admin/suppliers', [\App\Controllers\Admin\SupplierController::class, 'index']);
$router->add('admin/suppliers/save', [\App\Controllers\Admin\SupplierController::class, 'save']);
$router->add('admin/suppliers/delete', [\App\Controllers\Admin\SupplierController::class, 'delete']);

$router->add('admin/import-receipts', [\App\Controllers\Admin\ImportReceiptController::class, 'index']);
$router->add('admin/import-receipts/create', [\App\Controllers\Admin\ImportReceiptController::class, 'create']);
$router->add('admin/import-receipts/detail', [\App\Controllers\Admin\ImportReceiptController::class, 'detail']);

$router->add('admin/rentals', [\App\Controllers\Admin\RentalController::class, 'index']);
$router->add('admin/rentals/detail', [\App\Controllers\Admin\RentalController::class, 'detail']);
$router->add('admin/rentals/print', [\App\Controllers\Admin\RentalController::class, 'printSlip']);

$router->add('admin/users', [\App\Controllers\Admin\UserController::class, 'index']);
$router->add('admin/users/save', [\App\Controllers\Admin\UserController::class, 'save']);
$router->add('admin/users/toggle', [\App\Controllers\Admin\UserController::class, 'toggleStatus']);

// --- AJAX API Endpoints (Mục 7.1) ---
$router->add('api/products', [\App\Controllers\Api\ProductApi::class, 'index']);
$router->add('api/availability', [\App\Controllers\Api\AvailabilityApi::class, 'check']);
$router->add('api/cart', [\App\Controllers\Api\CartApi::class, 'handle']);
$router->add('api/recommend', [\App\Controllers\Api\RecommendApi::class, 'index']);
$router->add('api/extension-quote', [\App\Controllers\Api\ExtensionQuoteApi::class, 'quote']);
$router->add('api/admin/rental-action', [\App\Controllers\Api\AdminRentalApi::class, 'handle']);
$router->add('api/admin/extension-action', [\App\Controllers\Api\AdminExtensionApi::class, 'handle']);
$router->add('api/admin/product-action', [\App\Controllers\Api\AdminProductApi::class, 'handle']);

try {
    $route = (string) ($_GET['r'] ?? 'home');
    $router->dispatch($route);
} catch (Throwable $e) {
    error_log('Unhandled exception: ' . $e->getMessage());
    $route = (string) ($_GET['r'] ?? '');
    if (str_starts_with($route, 'api/')) {
        Response::json([
            'ok'      => false,
            'message' => 'Đã xảy ra lỗi hệ thống. Vui lòng kiểm tra kết nối cơ sở dữ liệu.',
        ], 500);
    }
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="vi" data-bs-theme="dark"><head><meta charset="utf-8"><title>Lỗi hệ thống</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>';
    echo '<body class="bg-dark text-light d-flex align-items-center justify-content-center min-vh-100 p-4">';
    echo '<div class="card border-secondary p-4" style="max-width:560px;background:#1b1d21;">';
    echo '<h4 class="text-warning mb-3">Thông báo hệ thống</h4>';
    echo '<p class="mb-2">' . Response::e($e->getMessage()) . '</p>';
    echo '<p class="text-secondary small mb-0">Hướng dẫn: Khởi động dịch vụ <strong>MySQL</strong> trong XAMPP Control Panel và chạy file <code>database/schema.sql</code> trong DBeaver.</p>';
    echo '</div></body></html>';
}

