<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;

$items = $result['items'] ?? [];
$total = (int) ($result['total'] ?? 0);
$page  = (int) ($result['page'] ?? 1);
$pages = (int) ($result['pages'] ?? 1);
$currentStockFilter = strtolower(trim((string) ($filters['stock'] ?? '')));
?>
<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h2 mb-1">Sản phẩm & Kiểm soát Tồn kho</h1>
        <p class="text-muted small mb-0">
            Tổng cộng <strong id="admin-product-total" class="num text-light"><?= $total ?></strong> sản phẩm. Sửa nhanh Giá bán, Ngưỡng sắp hết, Trạng thái ngay trên dòng.
        </p>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <a
            href="index.php?r=admin/products&stock=alert"
            id="btn-quick-alert-filter"
            class="btn btn-outline-secondary btn-sm <?= $currentStockFilter === 'alert' ? 'border-warning text-warning' : '' ?>"
        >
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            <span>Lọc nhanh: Sắp hết / Hết hàng</span>
        </a>
        <a
            href="index.php?r=admin/products/export-csv"
            id="btn-export-csv"
            class="btn btn-outline-secondary btn-sm"
        >
            <i class="bi bi-filetype-csv" aria-hidden="true"></i>
            <span>Xuất CSV theo bộ lọc</span>
        </a>
        <a href="index.php?r=admin/products/create" class="btn btn-primary">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Thêm sản phẩm mới</span>
        </a>
    </div>
</div>

<!-- Bộ lọc & Tìm kiếm phía Admin (AJAX - Mục 5.2 - A5) -->
<div class="card p-3 mb-4">
    <form id="admin-product-filter-form" action="index.php" method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="admin/products">
        <input type="hidden" name="page" id="admin-filter-page" value="<?= $page ?>">

        <div class="col-md-3">
            <label for="filter-q" class="form-label">Tìm theo mã SKU hoặc tên</label>
            <input
                type="search"
                id="filter-q"
                name="q"
                class="form-control"
                placeholder="Ví dụ: CAM-SN-A7M3, Sony..."
                value="<?= Response::e($filters['q'] ?? '') ?>"
            >
        </div>

        <div class="col-sm-6 col-md-2">
            <label for="filter-category" class="form-label">Danh mục</label>
            <select id="filter-category" name="category_id" class="form-select">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
                        <?= Response::e($cat['display_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-sm-6 col-md-2">
            <label for="filter-brand" class="form-label">Thương hiệu</label>
            <select id="filter-brand" name="brand_id" class="form-select">
                <option value="">Tất cả hãng</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= (int) ($filters['brand_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>>
                        <?= Response::e($b['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-sm-4 col-md-2">
            <label for="filter-stock" class="form-label">Tình trạng tồn</label>
            <select id="filter-stock" name="stock" class="form-select">
                <option value="">Tất cả mức tồn</option>
                <option value="alert" <?= $currentStockFilter === 'alert' ? 'selected' : '' ?>>Sắp hết + Hết hàng</option>
                <option value="low_stock" <?= $currentStockFilter === 'low_stock' ? 'selected' : '' ?>>Chỉ Sắp hết</option>
                <option value="out_of_stock" <?= $currentStockFilter === 'out_of_stock' ? 'selected' : '' ?>>Chỉ Hết hàng</option>
                <option value="in_stock" <?= $currentStockFilter === 'in_stock' ? 'selected' : '' ?>>Đang Còn hàng</option>
            </select>
        </div>

        <div class="col-sm-4 col-md-1">
            <label for="filter-status" class="form-label">Trạng thái</label>
            <select id="filter-status" name="status" class="form-select">
                <option value="">Tất cả</option>
                <option value="ACTIVE" <?= ($filters['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Đang bán</option>
                <option value="INACTIVE" <?= ($filters['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Ngừng KD</option>
            </select>
        </div>

        <div class="col-sm-4 col-md-2">
            <label for="filter-sort" class="form-label">Sắp xếp</label>
            <select id="filter-sort" name="sort" class="form-select">
                <option value="newest" <?= ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                <option value="stock_asc" <?= ($filters['sort'] ?? '') === 'stock_asc' ? 'selected' : '' ?>>Tồn kho tăng dần</option>
                <option value="price_asc" <?= ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                <option value="price_desc" <?= ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                <option value="name_asc" <?= ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>Tên A – Z</option>
            </select>
        </div>
    </form>
</div>

<!-- Bảng danh sách sản phẩm -->
<div class="card ajax-loading-region" id="admin-products-region">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Mã SKU</th>
                    <th>Sản phẩm</th>
                    <th>Giá bán (Sửa nhanh)</th>
                    <th class="text-end">Tồn kho</th>
                    <th class="text-end">Ngưỡng báo</th>
                    <th>Nhãn tồn</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="admin-product-tbody">
                <?php require __DIR__ . '/_table_rows.php'; ?>
            </tbody>
        </table>
    </div>

    <div class="p-3 border-top border-secondary d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
        <div class="small text-muted">
            Mẹo: Sửa trực tiếp Giá bán / Ngưỡng báo / Trạng thái rồi bấm nút <i class="bi bi-check2 text-light" aria-hidden="true"></i> để lưu nhanh bằng AJAX. Tồn kho chỉ thay đổi qua Nhập kho, Xuất kho hoặc Điều chỉnh kiểm kê.
        </div>
        <div id="admin-product-pagination">
            <?php require __DIR__ . '/_pagination.php'; ?>
        </div>
    </div>
</div>

<!-- Modal Xác nhận Xóa Sản phẩm (Mục 5.2 - A2 & TC05, TC06) -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="h5 modal-title" id="deleteProductModalLabel">Xóa sản phẩm</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng hộp thoại"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                    Bạn có chắc chắn muốn xóa sản phẩm <strong id="del-prod-name" class="text-light"></strong> (<span id="del-prod-code" class="code"></span>)?
                </p>
                <p class="small text-muted mb-0">
                    Quy tắc bảo toàn dữ liệu: Nếu sản phẩm đã từng phát sinh phiếu nhập hoặc lịch sử tồn kho, hệ thống sẽ chuyển sang <strong>Ngừng kinh doanh (INACTIVE)</strong> thay vì xóa cứng.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy thao tác</button>
                <button type="button" id="btn-confirm-delete-product" class="btn btn-outline-danger" data-id="">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    <span>Xóa sản phẩm</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xuất kho / Điều chỉnh tồn kho (Mục 5.2 - A6 & 6.5) -->
<div class="modal fade" id="stockAdjustModal" tabindex="-1" aria-labelledby="stockAdjustModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="stock-adjust-form" action="index.php?r=admin/stock/adjust" method="post">
                <?= Csrf::field() ?>
                <input type="hidden" name="product_id" id="adj-product-id" value="">
                <div class="modal-header">
                    <h2 class="h5 modal-title" id="stockAdjustModalLabel">Xuất kho / Điều chỉnh kiểm kê</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng hộp thoại"></button>
                </div>
                <div class="modal-body">
                    <div class="p-3 rounded mb-3" style="background: var(--surface-input); border: 1px solid var(--bs-border-color);">
                        <div class="small text-muted">Thiết bị đang thao tác:</div>
                        <div class="fw-semibold" id="adj-product-name"></div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary small">
                            <span>Tồn kho hiện tại: <strong id="adj-current-qty" class="num text-light fs-6">0</strong></span>
                            <span>Tồn sau thao tác: <strong id="adj-after-qty" class="num fs-6" style="color: #f0a23b;">0</strong></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="adj-type" class="form-label">Loại nghiệp vụ kho</label>
                        <select id="adj-type" name="type" class="form-select" required>
                            <option value="EXPORT">Xuất kho (Giảm tồn kho)</option>
                            <option value="ADJUSTMENT_ADD">Điều chỉnh kiểm kê: Cộng thêm tồn (+)</option>
                            <option value="ADJUSTMENT_SUB">Điều chỉnh kiểm kê: Trừ bớt tồn (-)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="adj-qty" class="form-label">Số lượng thực hiện</label>
                        <input
                            type="number"
                            id="adj-qty"
                            name="quantity"
                            min="1"
                            max="10000"
                            value="1"
                            class="form-control num"
                            required
                        >
                        <div id="adj-qty-error" class="invalid-feedback">Tồn kho sau thao tác không được nhỏ hơn 0.</div>
                    </div>

                    <div class="mb-2">
                        <label for="adj-reason" class="form-label">Lý do xuất / điều chỉnh (Bắt buộc)</label>
                        <input
                            type="text"
                            id="adj-reason"
                            name="reason"
                            maxlength="255"
                            class="form-control"
                            placeholder="Ví dụ: Xuất bán theo hóa đơn #HD-1050, Kiểm kê định kỳ..."
                            required
                        >
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btn-submit-stock-adjust" class="btn btn-primary">
                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                        <span>Xác nhận cập nhật kho</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
