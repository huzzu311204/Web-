<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Database;

$currentRoute = (string) ($_GET['r'] ?? 'admin/products');
$adminUser = Auth::user();
$flashes = Response::getFlashes();

// Đếm số sản phẩm sắp hết hoặc hết hàng hiển thị ở Sidebar cạnh mục Sản phẩm (Mục 5.2 - A5 & 9.5)
$lowOrOutCount = 0;
try {
    $db = Database::getConnection();
    $lowOrOutCount = (int) $db->query("
        SELECT COUNT(*)
        FROM products
        WHERE status = 'ACTIVE'
          AND (quantity = 0 OR (min_stock > 0 AND quantity <= min_stock))
    ")->fetchColumn();
} catch (\Throwable $e) {
    $lowOrOutCount = 0;
}

$navItems = [
    ['route' => 'admin/products',        'match' => ['admin', 'admin/products', 'admin/products/create', 'admin/products/edit'], 'icon' => 'bi-camera', 'label' => 'Sản phẩm', 'badge' => $lowOrOutCount],
    ['route' => 'admin/categories',      'match' => ['admin/categories'], 'icon' => 'bi-diagram-3', 'label' => 'Danh mục'],
    ['route' => 'admin/brands',          'match' => ['admin/brands'], 'icon' => 'bi-award', 'label' => 'Thương hiệu'],
    ['route' => 'admin/suppliers',       'match' => ['admin/suppliers'], 'icon' => 'bi-building', 'label' => 'Nhà cung cấp'],
    ['route' => 'admin/import-receipts', 'match' => ['admin/import-receipts', 'admin/import-receipts/create', 'admin/import-receipts/detail'], 'icon' => 'bi-file-earmark-plus', 'label' => 'Phiếu nhập kho'],
    ['route' => 'admin/stock-logs',      'match' => ['admin/stock-logs'], 'icon' => 'bi-clock-history', 'label' => 'Lịch sử tồn kho'],
    ['route' => 'admin/users',           'match' => ['admin/users'], 'icon' => 'bi-people', 'label' => 'Tài khoản Admin'],
];
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Response::e(Csrf::token()) ?>">
    <title><?= Response::e($pageTitle ?? 'Quản trị Sản phẩm & Kho — ProGear Studio') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar Desktop (rộng 240px - Mục 9.5) -->
        <aside class="admin-sidebar d-none d-lg-flex">
            <div class="p-3 border-bottom border-secondary d-flex align-items-center gap-2">
                <i class="bi bi-aperture fs-5" style="color: #f0a23b;" aria-hidden="true"></i>
                <div>
                    <div class="fw-bold text-light lh-1">PROGEAR</div>
                    <small class="text-muted" style="font-size: 0.75rem;">Quản lý Sản phẩm & Kho</small>
                </div>
            </div>

            <nav class="py-3 flex-grow-1" aria-label="Menu quản trị">
                <?php foreach ($navItems as $item): ?>
                    <?php $isActive = in_array($currentRoute, $item['match'], true); ?>
                    <a href="index.php?r=<?= Response::e($item['route']) ?>" class="admin-nav-link <?= $isActive ? 'active' : '' ?>">
                        <i class="bi <?= Response::e($item['icon']) ?>" aria-hidden="true"></i>
                        <span><?= Response::e($item['label']) ?></span>
                        <?php if (!empty($item['badge']) && (int) $item['badge'] > 0): ?>
                            <span id="sidebar-low-stock-badge" class="sidebar-alert-badge" title="Số sản phẩm sắp hết hoặc hết hàng"><?= (int) $item['badge'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="p-3 border-top border-secondary">
                <a href="index.php?r=home" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    <span>Xem trang khách</span>
                </a>
            </div>
        </aside>

        <!-- Sidebar Offcanvas cho màn hình nhỏ -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="adminSidebarOffcanvas" aria-labelledby="adminSidebarLabel" style="width: 240px; background-color: var(--surface-elevated);">
            <div class="offcanvas-header border-bottom border-secondary">
                <h2 class="h6 offcanvas-title fw-bold mb-0" id="adminSidebarLabel">
                    <i class="bi bi-aperture me-2" style="color: #f0a23b;" aria-hidden="true"></i>PROGEAR ADMIN
                </h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Đóng menu"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column justify-content-between">
                <nav class="py-2" aria-label="Menu quản trị di động">
                    <?php foreach ($navItems as $item): ?>
                        <?php $isActive = in_array($currentRoute, $item['match'], true); ?>
                        <a href="index.php?r=<?= Response::e($item['route']) ?>" class="admin-nav-link <?= $isActive ? 'active' : '' ?>">
                            <i class="bi <?= Response::e($item['icon']) ?>" aria-hidden="true"></i>
                            <span><?= Response::e($item['label']) ?></span>
                            <?php if (!empty($item['badge']) && (int) $item['badge'] > 0): ?>
                                <span class="sidebar-alert-badge"><?= (int) $item['badge'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <div class="p-3 border-top border-secondary">
                    <a href="index.php?r=home" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        <span>Xem trang khách</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Khu vực nội dung chính -->
        <div class="admin-main">
            <!-- Topbar mỏng -->
            <header class="admin-topbar">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebarOffcanvas" aria-label="Mở menu quản trị">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </button>
                    <span class="text-muted small d-none d-sm-inline">Hệ thống quản lý sản phẩm & kiểm soát tồn kho</span>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <?php if ($adminUser): ?>
                        <div class="text-end lh-sm">
                            <div class="fw-semibold small"><?= Response::e($adminUser['full_name']) ?></div>
                            <span class="code text-muted" style="font-size: 0.75rem;">@<?= Response::e($adminUser['username']) ?></span>
                        </div>
                        <form action="index.php?r=admin/logout" method="post" class="m-0">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                <span>Đăng xuất</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Nội dung trang Admin -->
            <main class="p-3 p-md-4 flex-grow-1">
                <?php if (!empty($flashes)): ?>
                    <?php foreach ($flashes as $type => $msg): ?>
                        <?php
                        $isError = in_array($type, ['error', 'danger'], true);
                        $borderColor = $isError ? '#f87171' : ($type === 'warning' ? '#facc15' : '#34d399');
                        $iconClass = $isError ? 'bi-exclamation-octagon' : ($type === 'warning' ? 'bi-exclamation-triangle' : 'bi-check-circle');
                        ?>
                        <div class="card p-3 mb-3 d-flex flex-row align-items-center justify-content-between" style="border-left: 3px solid <?= $borderColor ?>;" role="alert">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi <?= $iconClass ?>" style="color: <?= $borderColor ?>;" aria-hidden="true"></i>
                                <span><?= Response::e($msg) ?></span>
                            </div>
                            <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.remove()" aria-label="Đóng thông báo"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="studio-toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;" aria-live="polite" aria-atomic="true"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
    <?php if (!empty($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?= Response::e($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
