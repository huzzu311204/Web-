<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;
use App\Core\UiHelper;

$actionUrl = $isEdit
    ? 'index.php?r=admin/products/edit&id=' . (int) $old['id']
    : 'index.php?r=admin/products/create';
?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="index.php?r=admin/products" class="text-decoration-none">Sản phẩm</a></li>
                <li class="breadcrumb-item active text-muted" aria-current="page"><?= $isEdit ? 'Chỉnh sửa' : 'Thêm mới' ?></li>
            </ol>
        </nav>
        <h1 class="h2 mb-0"><?= $isEdit ? 'Chỉnh sửa sản phẩm: ' . Response::e($old['name']) : 'Thêm sản phẩm mới' ?></h1>
    </div>
    <div>
        <a href="index.php?r=admin/products" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>Quay lại danh sách</span>
        </a>
    </div>
</div>

<form action="<?= Response::e($actionUrl) ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= Csrf::field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" id="product-id-field" value="<?= (int) $old['id'] ?>">
    <?php endif; ?>

    <div class="row g-4">
        <!-- Cột trái: Thông tin chính & Thông số kỹ thuật động -->
        <div class="col-lg-8">
            <div class="card p-4 mb-4">
                <h2 class="h5 mb-3">Thông tin cơ bản</h2>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="code" class="form-label">Mã SKU (Duy nhất)</label>
                        <input
                            type="text"
                            id="code"
                            name="code"
                            maxlength="50"
                            class="form-control code text-uppercase <?= !empty($errors['code']) ? 'is-invalid' : '' ?>"
                            value="<?= Response::e($old['code'] ?? '') ?>"
                            data-exclude-id="<?= $isEdit ? (int) $old['id'] : '' ?>"
                            placeholder="CAM-SN-A7M3"
                            required
                        >
                        <div id="sku-check-feedback" class="small mt-1" aria-live="polite">
                            <?php if (!empty($errors['code'])): ?>
                                <span style="color: #f87171;"><?= Response::e($errors['code']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label for="name" class="form-label">Tên sản phẩm</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="200"
                            class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                            value="<?= Response::e($old['name'] ?? '') ?>"
                            placeholder="Ví dụ: Sony Alpha A7 Mark III (Body)"
                            required
                        >
                        <?php if (!empty($errors['name'])): ?>
                            <div class="invalid-feedback d-block"><?= Response::e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <label for="category_id" class="form-label">Danh mục</label>
                        <select id="category_id" name="category_id" class="form-select <?= !empty($errors['category_id']) ? 'is-invalid' : '' ?>" required>
                            <option value="">— Chọn danh mục —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>" <?= (int) ($old['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
                                    <?= Response::e($cat['display_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['category_id'])): ?>
                            <div class="invalid-feedback d-block"><?= Response::e($errors['category_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <label for="brand_id" class="form-label">Thương hiệu</label>
                        <select id="brand_id" name="brand_id" class="form-select <?= !empty($errors['brand_id']) ? 'is-invalid' : '' ?>">
                            <option value="">— Không chọn / Khác —</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= (int) $b['id'] ?>" <?= (int) ($old['brand_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>>
                                    <?= Response::e($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['brand_id'])): ?>
                            <div class="invalid-feedback d-block"><?= Response::e($errors['brand_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <label for="supplier_id" class="form-label">Nhà cung cấp mặc định</label>
                        <select id="supplier_id" name="supplier_id" class="form-select <?= !empty($errors['supplier_id']) ? 'is-invalid' : '' ?>" required>
                            <option value="">— Chọn nhà cung cấp —</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= (int) $sup['id'] ?>" <?= (int) ($old['supplier_id'] ?? 0) === (int) $sup['id'] ? 'selected' : '' ?>>
                                    <?= Response::e($sup['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['supplier_id'])): ?>
                            <div class="invalid-feedback d-block"><?= Response::e($errors['supplier_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Mô tả chi tiết thiết bị</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            maxlength="5000"
                            class="form-control <?= !empty($errors['description']) ? 'is-invalid' : '' ?>"
                            placeholder="Mô tả đặc điểm kỹ thuật, phụ kiện đi kèm hộp..."
                        ><?= Response::e($old['description'] ?? '') ?></textarea>
                        <?php if (!empty($errors['description'])): ?>
                            <div class="invalid-feedback d-block"><?= Response::e($errors['description']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Khối Thông số kỹ thuật động (Mục 5.2 - A2) -->
            <div class="card p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <h2 class="h5 mb-0">Thông số kỹ thuật động</h2>
                        <small class="text-muted">Mẹo: Dùng tên thông số <code>Ngàm</code> (ví dụ: <code>Sony E</code>, <code>Canon RF</code>, <code>Fujifilm X</code>) để hệ thống tự động gợi ý ống kính tương thích.</small>
                    </div>
                    <button type="button" id="btn-add-spec-row" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Thêm dòng thông số</span>
                    </button>
                </div>

                <?php if (!empty($errors['specs'])): ?>
                    <div class="invalid-feedback d-block mb-2"><?= Response::e($errors['specs']) ?></div>
                <?php endif; ?>

                <div id="spec-rows-container" class="d-flex flex-column gap-2 mt-2">
                    <?php foreach ($specs as $idx => $s): ?>
                        <div class="row g-2 align-items-center spec-row-item">
                            <div class="col-5">
                                <input
                                    type="text"
                                    name="spec_name[]"
                                    maxlength="100"
                                    class="form-control"
                                    placeholder="Tên thông số (VD: Ngàm, Độ phân giải)"
                                    value="<?= Response::e($s['name'] ?? '') ?>"
                                    aria-label="Tên thông số kỹ thuật"
                                >
                            </div>
                            <div class="col-6">
                                <input
                                    type="text"
                                    name="spec_value[]"
                                    maxlength="255"
                                    class="form-control"
                                    placeholder="Giá trị (VD: Sony E, 24.2 MP)"
                                    value="<?= Response::e($s['value'] ?? '') ?>"
                                    aria-label="Giá trị thông số kỹ thuật"
                                >
                            </div>
                            <div class="col-1 text-end">
                                <button type="button" class="btn btn-outline-danger btn-icon btn-remove-spec-row" aria-label="Xóa dòng thông số">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Cột phải: Giá bán, Tồn kho, Trạng thái & Ảnh đại diện -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h2 class="h5 mb-3">Giá bán & Kiểm soát tồn kho</h2>

                <div class="mb-3">
                    <label for="price" class="form-label">Giá bán niêm yết (VNĐ)</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="10000"
                        class="form-control num <?= !empty($errors['price']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['price'] ?? '0') ?>"
                        required
                    >
                    <?php if (!empty($errors['price'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['price']) ?></div>
                    <?php endif; ?>
                </div>

                <?php if (!$isEdit): ?>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Số lượng tồn đầu kỳ</label>
                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            min="0"
                            max="100000"
                            class="form-control num <?= !empty($errors['quantity']) ? 'is-invalid' : '' ?>"
                            value="<?= Response::e($old['quantity'] ?? '0') ?>"
                            required
                        >
                        <small class="text-muted d-block mt-1">
                            Nếu lớn hơn 0, hệ thống tự động tạo bản ghi <code>stock_logs</code> với lý do "Tồn đầu kỳ".
                        </small>
                        <?php if (!empty($errors['quantity'])): ?>
                            <div class="invalid-feedback d-block"><?= Response::e($errors['quantity']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Tồn kho hiện tại (Chỉ đọc)</label>
                        <div class="p-2 rounded d-flex align-items-center justify-content-between" style="background: var(--surface-input); border: 1px solid var(--bs-border-color);">
                            <span class="num fs-5 text-light ps-2"><?= (int) ($old['quantity'] ?? 0) ?></span>
                            <?= UiHelper::stockBadge((int) ($old['quantity'] ?? 0), (int) ($old['min_stock'] ?? 0), (string) ($old['status'] ?? 'ACTIVE')) ?>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Tồn kho không sửa trực tiếp tại đây để đảm bảo mọi biến động đều có bản ghi trong <code>stock_logs</code>.
                        </small>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="min_stock" class="form-label">Ngưỡng cảnh báo sắp hết (<code>min_stock</code>)</label>
                    <input
                        type="number"
                        id="min_stock"
                        name="min_stock"
                        min="0"
                        max="100000"
                        class="form-control num <?= !empty($errors['min_stock']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['min_stock'] ?? '2') ?>"
                        required
                    >
                    <?php if (!empty($errors['min_stock'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['min_stock']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-0">
                    <label for="status" class="form-label">Trạng thái kinh doanh</label>
                    <select id="status" name="status" class="form-select">
                        <option value="ACTIVE" <?= ($old['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>Đang kinh doanh (ACTIVE)</option>
                        <option value="INACTIVE" <?= ($old['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Ngừng kinh doanh (INACTIVE)</option>
                    </select>
                </div>
            </div>

            <!-- Upload ảnh an toàn (Mục 6.7) -->
            <div class="card p-4 mb-4">
                <h2 class="h5 mb-3">Ảnh sản phẩm (Tỷ lệ 4:3)</h2>

                <?php if (!empty($old['image'])): ?>
                    <div class="product-card-img-wrap rounded border border-secondary mb-3">
                        <img src="<?= Response::e(UiHelper::imageUrl((string) $old['image'])) ?>" alt="Ảnh hiện tại">
                    </div>
                <?php endif; ?>

                <div>
                    <label for="image" class="form-label"><?= !empty($old['image']) ? 'Thay đổi ảnh mới (tùy chọn)' : 'Chọn tệp ảnh tải lên' ?></label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/jpeg,image/png,image/webp"
                        class="form-control <?= !empty($errors['image']) ? 'is-invalid' : '' ?>"
                    >
                    <small class="text-muted d-block mt-1">
                        Chỉ nhận định dạng <strong>JPG, PNG, WebP</strong> (tối đa 2 MB). Không chấp nhận tệp SVG.
                    </small>
                    <?php if (!empty($errors['image'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['image']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span><?= $isEdit ? 'Lưu cập nhật sản phẩm' : 'Tạo sản phẩm mới' ?></span>
            </button>
        </div>
    </div>
</form>

