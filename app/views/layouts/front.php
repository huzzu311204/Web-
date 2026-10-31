<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;

$currentRoute = (string) ($_GET['r'] ?? 'home');
$compareIds = $_SESSION['compare_ids'] ?? [];
$compareCount = is_array($compareIds) ? count($compareIds) : 0;
$flashes = Response::getFlashes();
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Response::e(Csrf::token()) ?>">
    <title><?= Response::e($pageTitle ?? 'ProGear Studio — Thiết bị Nhiếp ảnh & Quay phim Chuyên nghiệp') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar Khách hàng (Mục 9.4) -->
    <header class="navbar navbar-expand-lg navbar-studio sticky-top">
        <div class="container-studio w-100 d-flex align-items-center justify-content-between">
            <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none fw-bold fs-5 text-light" href="index.php?r=home">
                <i class="bi bi-aperture" style="color: #f0a23b;" aria-hidden="true"></i>
                <span>PROGEAR <span class="fw-normal text-muted fs-6">STUDIO</span></span>
            </a>

            <button class="navbar-toggler border-secondary btn-icon" type="button" data-bs-toggle="collapse" data-bs-target="#storefrontNavbar" aria-controls="storefrontNavbar" aria-expanded="false" aria-label="Mở menu điều hướng">
                <i class="bi bi-list fs-4" aria-hidden="true"></i>
            </button>

            <div class="collapse navbar-collapse" id="storefrontNavbar">
                <!-- Ô tìm kiếm nhanh trên Navbar -->
                <form class="d-flex my-3 my-lg-0 mx-lg-4 flex-grow-1" style="max-width: 400px;" action="index.php" method="get" role="search">
                    <input type="hidden" name="r" value="catalog">
                    <div class="input-group">
                        <input type="search" name="q" class="form-control" placeholder="Tìm máy ảnh, ống kính, mã SKU..." value="<?= Response::e($_GET['q'] ?? '') ?>" aria-label="Tìm kiếm sản phẩm">
                        <button class="btn btn-outline-secondary btn-icon" type="submit" aria-label="Tìm kiếm">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>

                <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentRoute === 'home' ? 'active' : '' ?>" href="index.php?r=home">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= in_array($currentRoute, ['catalog', 'product'], true) ? 'active' : '' ?>" href="index.php?r=catalog">Danh mục thiết bị</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-secondary position-relative" href="index.php?r=compare" aria-label="So sánh sản phẩm">
                            <i class="bi bi-columns-gap" aria-hidden="true"></i>
                            <span>So sánh</span>
                            <span id="navbar-compare-count" class="counter-badge ms-1" aria-live="polite"><?= (int) $compareCount ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($flashes)): ?>
        <div class="container-studio w-100 mt-3">
            <?php foreach ($flashes as $type => $msg): ?>
                <?php
                $isError = in_array($type, ['error', 'danger'], true);
                $borderColor = $isError ? '#f87171' : ($type === 'warning' ? '#facc15' : '#34d399');
                $iconClass = $isError ? 'bi-exclamation-octagon' : ($type === 'warning' ? 'bi-exclamation-triangle' : 'bi-check-circle');
                ?>
                <div class="card p-3 mb-2 d-flex flex-row align-items-center justify-content-between" style="border-left: 3px solid <?= $borderColor ?>;" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= $iconClass ?>" style="color: <?= $borderColor ?>;" aria-hidden="true"></i>
                        <span><?= Response::e($msg) ?></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.remove()" aria-label="Đóng thông báo"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Nội dung chính -->
    <main class="flex-grow-1">
        <?= $content ?>
    </main>

    <!-- Thanh so sánh cố định cuối màn hình (Mục 7 - GĐ4, tối đa 3 sản phẩm) -->
    <?php if ($currentRoute !== 'compare'): ?>
        <div id="compare-sticky-bar" class="compare-bar-fixed d-none" role="region" aria-label="Thanh so sánh sản phẩm">
            <div class="container-studio d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2" id="compare-bar-items"></div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="btn-compare-bar-clear" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                        <span>Xóa hết</span>
                    </button>
                    <a href="index.php?r=compare" class="btn btn-primary btn-sm">
                        <i class="bi bi-columns-gap" aria-hidden="true"></i>
                        <span>So sánh ngay (<span id="compare-bar-count" class="num">0</span>/3)</span>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Toast Container cho AJAX -->
    <div id="studio-toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;" aria-live="polite" aria-atomic="true"></div>

    <!-- Footer -->
    <footer class="footer-studio">
        <div class="container-studio">
            <div class="row g-4">
                <div class="col-md-5">
                    <div class="d-flex align-items-center gap-2 fw-bold fs-5 mb-2">
                        <i class="bi bi-aperture" style="color: #f0a23b;" aria-hidden="true"></i>
                        <span>PROGEAR STUDIO</span>
                    </div>
                    <p class="text-muted small mb-2">
                        Hệ thống phân phối & quản lý thiết bị nhiếp ảnh, ống kính, đèn studio và phụ kiện quay phim chính hãng. Tra cứu thông số kỹ thuật chuẩn xác, so sánh trực quan và cập nhật tồn kho thời gian thực.
                    </p>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-clock me-1" aria-hidden="true"></i>Giờ mở cửa showroom: <span class="num text-light">08:00 – 20:30</span> (Tất cả các ngày trong tuần)
                    </p>
                </div>
                <div class="col-md-4">
                    <h3 class="h6 fw-semibold mb-3">Địa chỉ Showroom & Kho</h3>
                    <ul class="list-unstyled text-muted small mb-0 d-flex flex-column gap-2">
                        <li>
                            <i class="bi bi-geo-alt me-2" style="color: #f0a23b;" aria-hidden="true"></i>184 Nam Kỳ Khởi Nghĩa, Phường Võ Thị Sáu, Quận 3, TP.HCM
                        </li>
                        <li>
                            <i class="bi bi-telephone me-2" style="color: #f0a23b;" aria-hidden="true"></i>Hotline tư vấn kỹ thuật: <span class="num text-light">0901 234 567</span>
                        </li>
                        <li>
                            <i class="bi bi-envelope me-2" style="color: #f0a23b;" aria-hidden="true"></i>Email: <span class="code text-light">contact@progear.vn</span>
                        </li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h3 class="h6 fw-semibold mb-3">Liên kết nhanh</h3>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                        <li><a href="index.php?r=catalog" class="text-muted text-decoration-none">Tất cả thiết bị</a></li>
                        <li><a href="index.php?r=catalog&stock=in_stock" class="text-muted text-decoration-none">Sản phẩm đang còn hàng</a></li>
                        <li><a href="index.php?r=compare" class="text-muted text-decoration-none">Bảng so sánh thông số</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center text-muted small">
                <span>&copy; <?= date('Y') ?> ProGear Studio. Bài thực hành Lập trình Web (Bài TH3).</span>
                <span>Quản lý sản phẩm & tồn kho chính xác với PDO Transaction.</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
    <?php if (!empty($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?= Response::e($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
