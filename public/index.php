<?php
declare(strict_types=1);

// Tắt hiển thị lỗi trực tiếp ra trình duyệt khi chạy thực tế để đảm bảo bảo mật (Mục 11)
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
error_reporting(E_ALL);

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Header bảo mật nền (Mục 11)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

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
$router->add('home',            [\App\Controllers\Storefront\HomeController::class, 'index'],     ['method' => 'GET']);
$router->add('catalog',         [\App\Controllers\Storefront\CatalogController::class, 'index'],  ['method' => 'GET']);
$router->add('product',         [\App\Controllers\Storefront\CatalogController::class, 'detail'], ['method' => 'GET']);
$router->add('compare',         [\App\Controllers\Storefront\CompareController::class, 'index'],  ['method' => 'GET']);
$router->add('recent/clear',    [\App\Controllers\Storefront\CatalogController::class, 'clearRecent'], ['method' => 'POST']);

// --- Admin Auth Routes ---
$router->add('admin/login',     [\App\Controllers\Admin\AuthController::class, 'login'],  ['method' => ['GET', 'POST']]);
$router->add('admin/logout',    [\App\Controllers\Admin\AuthController::class, 'logout'], ['auth' => 'admin', 'method' => 'POST']);

// --- Admin Routes (Kiểm tra quyền, phương thức & CSRF tập trung trong Router - Mục 8.1) ---
$router->add('admin',                [\App\Controllers\Admin\ProductController::class, 'index'],  ['auth' => 'admin', 'method' => 'GET']);
$router->add('admin/products',       [\App\Controllers\Admin\ProductController::class, 'index'],  ['auth' => 'admin', 'method' => 'GET']);
$router->add('admin/products/create',[\App\Controllers\Admin\ProductController::class, 'create'], ['auth' => 'admin', 'method' => ['GET', 'POST']]);
$router->add('admin/products/edit',  [\App\Controllers\Admin\ProductController::class, 'edit'],   ['auth' => 'admin', 'method' => ['GET', 'POST']]);
$router->add('admin/products/export-csv', [\App\Controllers\Admin\ProductController::class, 'exportCsv'], ['auth' => 'admin', 'method' => 'GET']);

$router->add('admin/categories',        [\App\Controllers\Admin\CategoryController::class, 'index'],  ['auth' => 'admin', 'method' => 'GET']);
$router->add('admin/categories/save',   [\App\Controllers\Admin\CategoryController::class, 'save'],   ['auth' => 'admin', 'method' => 'POST']);
$router->add('admin/categories/delete', [\App\Controllers\Admin\CategoryController::class, 'delete'], ['auth' => 'admin', 'method' => 'POST']);

$router->add('admin/brands',            [\App\Controllers\Admin\BrandController::class, 'index'],     ['auth' => 'admin', 'method' => 'GET']);
$router->add('admin/brands/save',       [\App\Controllers\Admin\BrandController::class, 'save'],      ['auth' => 'admin', 'method' => 'POST']);
$router->add('admin/brands/delete',     [\App\Controllers\Admin\BrandController::class, 'delete'],    ['auth' => 'admin', 'method' => 'POST']);

$router->add('admin/suppliers',         [\App\Controllers\Admin\SupplierController::class, 'index'],  ['auth' => 'admin', 'method' => 'GET']);
$router->add('admin/suppliers/save',    [\App\Controllers\Admin\SupplierController::class, 'save'],   ['auth' => 'admin', 'method' => 'POST']);
$router->add('admin/suppliers/delete',  [\App\Controllers\Admin\SupplierController::class, 'delete'], ['auth' => 'admin', 'method' => 'POST']);

$router->add('admin/import-receipts',        [\App\Controllers\Admin\ImportReceiptController::class, 'index'],  ['auth' => 'admin', 'method' => 'GET']);
$router->add('admin/import-receipts/create', [\App\Controllers\Admin\ImportReceiptController::class, 'create'], ['auth' => 'admin', 'method' => ['GET', 'POST']]);
$router->add('admin/import-receipts/detail', [\App\Controllers\Admin\ImportReceiptController::class, 'detail'], ['auth' => 'admin', 'method' => 'GET']);
$router->add('admin/import-receipts/print',  [\App\Controllers\Admin\ImportReceiptController::class, 'printReceipt'], ['auth' => 'admin', 'method' => 'GET']);

$router->add('admin/stock-logs',        [\App\Controllers\Admin\StockController::class, 'index'],  ['auth' => 'admin', 'method' => 'GET']);
$router->add('admin/stock/adjust',      [\App\Controllers\Admin\StockController::class, 'adjust'], ['auth' => 'admin', 'method' => 'POST']);

$router->add('admin/users',             [\App\Controllers\Admin\UserController::class, 'index'],        ['auth' => 'admin', 'method' => 'GET']);
$router->add('admin/users/save',        [\App\Controllers\Admin\UserController::class, 'save'],         ['auth' => 'admin', 'method' => 'POST']);
$router->add('admin/users/toggle',      [\App\Controllers\Admin\UserController::class, 'toggleStatus'], ['auth' => 'admin', 'method' => 'POST']);

// --- AJAX API Endpoints (Mục 7.1) ---
$router->add('api/products',             [\App\Controllers\Api\ProductApi::class, 'index'],          ['method' => 'GET',  'ajax' => true]);
$router->add('api/compare',              [\App\Controllers\Api\CompareApi::class, 'handle'],         ['method' => 'POST', 'ajax' => true]);
$router->add('api/admin/products',       [\App\Controllers\Api\AdminProductApi::class, 'index'],     ['auth' => 'admin', 'method' => 'GET',  'ajax' => true]);
$router->add('api/admin/sku-check',      [\App\Controllers\Api\AdminProductApi::class, 'skuCheck'],  ['auth' => 'admin', 'method' => 'GET',  'ajax' => true]);
$router->add('api/admin/product-inline', [\App\Controllers\Api\AdminProductApi::class, 'inline'],    ['auth' => 'admin', 'method' => 'POST', 'ajax' => true]);
$router->add('api/admin/product-action', [\App\Controllers\Api\AdminProductApi::class, 'action'],    ['auth' => 'admin', 'method' => 'POST', 'ajax' => true]);
$router->add('api/admin/stock-adjust',   [\App\Controllers\Api\AdminStockApi::class, 'adjust'],      ['auth' => 'admin', 'method' => 'POST', 'ajax' => true]);

try {
    $route = (string) ($_GET['r'] ?? 'home');
    $router->dispatch($route);
} catch (\Throwable $e) {
    // Ghi log nội bộ và hiển thị câu thông báo chung cho người dùng (Mục 11 & TC33)
    error_log('Unhandled system error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    $route = (string) ($_GET['r'] ?? '');
    if (str_starts_with($route, 'api/')) {
        Response::json([
            'ok'      => false,
            'message' => 'Đã xảy ra lỗi xử lý trên hệ thống. Vui lòng thử lại sau.',
        ], 500);
    }
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="vi" data-bs-theme="dark"><head><meta charset="utf-8"><title>Thông báo hệ thống — ProGear Studio</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>';
    echo '<body class="bg-dark text-light d-flex align-items-center justify-content-center min-vh-100 p-4">';
    echo '<div class="card border-secondary p-4" style="max-width:520px;background:#1b1d21;">';
    echo '<h4 class="text-warning mb-3">Hệ thống tạm thời gián đoạn</h4>';
    echo '<p class="mb-2">Không thể hoàn tất yêu cầu vào lúc này. Vui lòng kiểm tra dịch vụ cơ sở dữ liệu hoặc thử lại sau.</p>';
    echo '<div class="mt-3"><a href="index.php?r=home" class="btn btn-outline-light btn-sm">Quay lại trang chủ</a></div>';
    echo '</div></body></html>';
}
