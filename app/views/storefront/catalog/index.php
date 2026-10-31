<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;
use App\Core\UiHelper;

$items = $result['items'] ?? [];
$total = (int) ($result['total'] ?? 0);
$page  = (int) ($result['page'] ?? 1);
$pages = (int) ($result['pages'] ?? 1);
$currentSort  = (string) ($result['sort'] ?? ($filters['sort'] ?? 'newest'));
$currentStock = strtolower(trim((string) ($filters['stock'] ?? '')));
$currentCatId = (int) ($filters['category_id'] ?? $filters['category'] ?? 0);
?>
<div class="container-studio py-4 py-lg-5">
    <!-- Tiêu đề trang & Thanh công cụ Sắp xếp -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-1">
                    <li class="breadcrumb-item"><a href="index.php?r=home" class="text-decoration-none">Trang chủ</a></li>
                    <li class="breadcrumb-item active text-muted" aria-current="page">Danh mục thiết bị</li>
                </ol>
            </nav>
            <h1 class="h2 mb-0">Danh mục Thiết bị Nhiếp ảnh & Quay phim</h1>
        </div>

        <div class="d-flex align-items-center justify-content-between justify-content-md-end gap-2">
            <!-- Nút mở Offcanvas bộ lọc trên di động (< lg) -->
            <button
                class="btn btn-outline-secondary d-lg-none"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#catalogFilterOffcanvas"
                aria-controls="catalogFilterOffcanvas"
            >
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <span>Bộ lọc thiết bị</span>
            </button>

            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted text-nowrap">
                    Hiển thị <strong id="catalog-total-count" class="num text-light"><?= $total ?></strong> thiết bị
                </span>
                <select id="catalog-sort-select" class="form-select form-select-sm" style="width: auto; min-width: 170px;" aria-label="Sắp xếp danh sách thiết bị">
                    <option value="newest" <?= $currentSort === 'newest' ? 'selected' : '' ?>>Mới cập nhật</option>
                    <option value="price_asc" <?= $currentSort === 'price_asc' ? 'selected' : '' ?>>Giá: Thấp → Cao</option>
                    <option value="price_desc" <?= $currentSort === 'price_desc' ? 'selected' : '' ?>>Giá: Cao → Thấp</option>
                    <option value="name_asc" <?= $currentSort === 'name_asc' ? 'selected' : '' ?>>Tên: A → Z</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Cột bộ lọc bên trái (Sticky trên >= lg, Offcanvas trên di động - MASTER.md Mục 3.1) -->
        <aside class="col-lg-3">
            <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="catalogFilterOffcanvas" aria-labelledby="catalogFilterOffcanvasLabel" style="background-color: var(--surface-elevated);">
                <div class="offcanvas-header border-bottom border-secondary d-lg-none">
                    <h2 class="h6 offcanvas-title fw-bold mb-0" id="catalogFilterOffcanvasLabel">
                        <i class="bi bi-funnel me-2" style="color: #f0a23b;" aria-hidden="true"></i>Bộ lọc thiết bị
                    </h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#catalogFilterOffcanvas" aria-label="Đóng bộ lọc"></button>
                </div>

                <div class="offcanvas-body p-3 p-lg-0">
                    <form id="catalog-filter-form" action="index.php" method="get" class="card p-3 w-100 sticky-lg-top" style="top: 84px; z-index: 10;">
                        <input type="hidden" name="r" value="catalog">
                        <input type="hidden" name="sort" id="filter-sort-hidden" value="<?= Response::e($currentSort) ?>">
                        <input type="hidden" name="page" id="filter-page-hidden" value="<?= $page ?>">

                        <!-- 1. Từ khóa tên / mã SKU -->
                        <div class="mb-3">
                            <label for="catalog-q" class="form-label">Tìm tên hoặc mã SKU</label>
                            <div class="input-group">
                                <input
                                    type="search"
                                    id="catalog-q"
                                    name="q"
                                    class="form-control"
                                    placeholder="VD: Sony A7, RF 50mm..."
                                    value="<?= Response::e($filters['q'] ?? '') ?>"
                                >
                            </div>
                        </div>

                        <!-- 2. Cây danh mục thiết bị -->
                        <div class="mb-3">
                            <label for="catalog-category" class="form-label">Danh mục thiết bị</label>
                            <select id="catalog-category" name="category_id" class="form-select">
                                <option value="">Tất cả danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>" <?= $currentCatId === (int) $cat['id'] ? 'selected' : '' ?>>
                                        <?= Response::e($cat['display_name']) ?> (<?= (int) $cat['product_count'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 3. Thương hiệu (Chọn nhiều checkbox - Mục 7 - GĐ3) -->
                        <div class="mb-3">
                            <label class="form-label d-block mb-2">Thương hiệu (Chọn nhiều)</label>
                            <div class="d-flex flex-column gap-1 pe-1" style="max-height: 210px; overflow-y: auto;">
                                <?php foreach ($brands as $b): ?>
                                    <?php
                                    $bid = (int) $b['id'];
                                    $checked = in_array($bid, $selectedBrands, true);
                                    ?>
                                    <div class="form-check">
                                        <input
                                            class="form-check-input catalog-brand-checkbox"
                                            type="checkbox"
                                            name="brands[]"
                                            value="<?= $bid ?>"
                                            id="brand-chk-<?= $bid ?>"
                                            <?= $checked ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label small d-flex justify-content-between align-items-center" for="brand-chk-<?= $bid ?>">
                                            <span><?= Response::e($b['name']) ?></span>
                                            <span class="num text-muted"><?= (int) $b['product_count'] ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 4. Khoảng giá bán (VNĐ) -->
                        <div class="mb-3">
                            <label class="form-label">Khoảng giá (VNĐ)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input
                                        type="number"
                                        id="catalog-min-price"
                                        name="min_price"
                                        min="0"
                                        step="500000"
                                        class="form-control form-control-sm num"
                                        placeholder="Từ (0)"
                                        value="<?= Response::e($filters['min_price'] ?? '') ?>"
                                        aria-label="Giá tối thiểu"
                                    >
                                </div>
                                <div class="col-6">
                                    <input
                                        type="number"
                                        id="catalog-max-price"
                                        name="max_price"
                                        min="0"
                                        step="500000"
                                        class="form-control form-control-sm num"
                                        placeholder="Đến (VNĐ)"
                                        value="<?= Response::e($filters['max_price'] ?? '') ?>"
                                        aria-label="Giá tối đa"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- 5. Tình trạng tồn kho tại Showroom -->
                        <div class="mb-3">
                            <label for="catalog-stock" class="form-label">Tình trạng tồn kho</label>
                            <select id="catalog-stock" name="stock" class="form-select">
                                <option value="" <?= $currentStock === '' ? 'selected' : '' ?>>Tất cả mức tồn kho</option>
                                <option value="in_stock" <?= $currentStock === 'in_stock' ? 'selected' : '' ?>>Đang còn hàng sẵn</option>
                                <option value="low_stock" <?= $currentStock === 'low_stock' ? 'selected' : '' ?>>Sắp hết hàng</option>
                                <option value="out_of_stock" <?= $currentStock === 'out_of_stock' ? 'selected' : '' ?>>Tạm hết hàng</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2 pt-2 border-top border-secondary">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-funnel" aria-hidden="true"></i>
                                <span>Áp dụng bộ lọc</span>
                            </button>
                            <button type="button" id="btn-sidebar-reset-filters" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                <span>Đặt lại mặc định</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Cột phải: Chip bộ lọc + Lưới sản phẩm + Phân trang -->
        <div class="col-lg-9">
            <div id="catalog-grid-region" class="ajax-loading-region">
                <?php require __DIR__ . '/_grid_wrapper.php'; ?>
            </div>
        </div>
    </div>

    <!-- Khối Đã xem gần đây ở cuối danh sách (Mục 7 - GĐ3 & TC13) -->
    <?php if (!empty($recentProducts)): ?>
        <section class="mt-5 pt-4 border-top border-secondary">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Thiết bị bạn đã xem gần đây</h2>
                <form action="index.php?r=recent/clear" method="post" class="m-0">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                        <span>Xóa lịch sử xem</span>
                    </button>
                </form>
            </div>
            <div class="row g-3">
                <?php foreach ($recentProducts as $rp): ?>
                    <div class="col-6 col-md-3">
                        <a href="index.php?r=product&id=<?= (int) $rp['id'] ?>" class="card p-2 text-decoration-none h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="product-card-img-wrap rounded mb-2">
                                    <img src="<?= Response::e(UiHelper::imageUrl($rp['image'] ?? null)) ?>" alt="<?= Response::e($rp['name']) ?>" loading="lazy">
                                </div>
                                <div class="small text-muted code"><?= Response::e($rp['code']) ?></div>
                                <div class="small fw-semibold text-light text-truncate mb-1"><?= Response::e($rp['name']) ?></div>
                            </div>
                            <div class="price small"><?= Response::money($rp['price']) ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
