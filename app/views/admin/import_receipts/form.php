<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;
?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="index.php?r=admin/import-receipts" class="text-decoration-none">Phiếu nhập kho</a></li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Lập phiếu nhập mới</li>
            </ol>
        </nav>
        <h1 class="h2 mb-0">Lập Phiếu nhập kho mới</h1>
    </div>
    <div>
        <a href="index.php?r=admin/import-receipts" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>Quay lại danh sách</span>
        </a>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="card p-3 mb-4" style="border-left: 3px solid #f87171;" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-octagon" style="color: #f87171;" aria-hidden="true"></i>
            <span><?= Response::e($errors['general']) ?></span>
        </div>
    </div>
<?php endif; ?>

<form action="index.php?r=admin/import-receipts/create" method="post" id="import-receipt-form" novalidate>
    <?= Csrf::field() ?>

    <div class="row g-4">
        <!-- Thông tin chung của phiếu nhập -->
        <div class="col-lg-4">
            <div class="card p-4">
                <h2 class="h5 mb-3">Thông tin chứng từ</h2>

                <div class="mb-3">
                    <label for="supplier_id" class="form-label">Nhà cung cấp</label>
                    <select id="supplier_id" name="supplier_id" class="form-select" required>
                        <option value="">— Chọn nhà cung cấp —</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= (int) $sup['id'] ?>" <?= (int) ($old['supplier_id'] ?? 0) === (int) $sup['id'] ? 'selected' : '' ?>>
                                <?= Response::e($sup['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="import_date" class="form-label">Ngày nhập kho</label>
                    <input
                        type="date"
                        id="import_date"
                        name="import_date"
                        class="form-control num"
                        value="<?= Response::e($old['import_date'] ?? date('Y-m-d')) ?>"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label for="note" class="form-label">Ghi chú chứng từ / Số hóa đơn</label>
                    <textarea
                        id="note"
                        name="note"
                        rows="3"
                        maxlength="255"
                        class="form-control"
                        placeholder="Ví dụ: Nhập lô máy ảnh & ống kính Sony tháng 10..."
                    ><?= Response::e($old['note'] ?? '') ?></textarea>
                </div>

                <div class="p-3 rounded mb-4" style="background: var(--surface-input); border: 1px solid var(--bs-border-color);">
                    <div class="d-flex justify-content-between align-items-center mb-1 small text-muted">
                        <span>Tổng số lượng nhập:</span>
                        <strong id="receipt-preview-qty" class="num text-light">0</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary">
                        <span class="small text-muted">Tổng tiền tạm tính:</span>
                        <span id="receipt-preview-total" class="price fs-5">0 đ</span>
                    </div>
                    <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">
                        Lưu ý: Thành tiền và Tổng tiền chính thức sẽ được máy chủ tính lại và ghi trong 1 PDO Transaction.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    <span>Xác nhận nhập kho</span>
                </button>
            </div>
        </div>

        <!-- Danh sách các dòng sản phẩm nhập -->
        <div class="col-lg-8">
            <div class="card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h2 class="h5 mb-0">Danh sách thiết bị nhập kho</h2>
                        <small class="text-muted">Mỗi sản phẩm xuất hiện tối đa 1 dòng trong phiếu nhập.</small>
                    </div>
                    <button type="button" id="btn-add-receipt-line" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Thêm dòng sản phẩm</span>
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle" id="receipt-lines-table">
                        <thead>
                            <tr>
                                <th style="min-width: 260px;">Thiết bị (Mã SKU — Tên — Tồn hiện tại)</th>
                                <th style="width: 120px;" class="text-end">Số lượng</th>
                                <th style="width: 180px;" class="text-end">Đơn giá nhập (VNĐ)</th>
                                <th style="width: 150px;" class="text-end">Thành tiền</th>
                                <th style="width: 52px;"></th>
                            </tr>
                        </thead>
                        <tbody id="receipt-lines-tbody">
                            <?php foreach ($lines as $line): ?>
                                <tr class="receipt-line-row">
                                    <td>
                                        <select name="product_id[]" class="form-select receipt-product-select" required aria-label="Chọn thiết bị nhập kho">
                                            <option value="">— Chọn sản phẩm —</option>
                                            <?php foreach ($products as $p): ?>
                                                <option
                                                    value="<?= (int) $p['id'] ?>"
                                                    data-default-price="<?= (int) round(((int) $p['price']) * 0.85, -4) ?>"
                                                    <?= (int) ($line['product_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>
                                                >
                                                    [<?= Response::e($p['code']) ?>] <?= Response::e($p['name']) ?> (Tồn: <?= (int) $p['quantity'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            name="quantity[]"
                                            min="1"
                                            max="10000"
                                            class="form-control num text-end receipt-qty-input"
                                            value="<?= Response::e($line['quantity'] ?? '1') ?>"
                                            required
                                            aria-label="Số lượng nhập"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            name="import_price[]"
                                            min="0"
                                            step="10000"
                                            class="form-control num text-end receipt-price-input"
                                            value="<?= Response::e($line['import_price'] ?? '0') ?>"
                                            required
                                            aria-label="Đơn giá nhập"
                                        >
                                    </td>
                                    <td class="text-end num fw-semibold receipt-line-subtotal">0 đ</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-danger btn-icon btn-remove-receipt-line" aria-label="Xóa dòng sản phẩm">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Template ẩn dùng để nhân bản dòng sản phẩm bằng JS -->
<template id="receipt-line-template">
    <tr class="receipt-line-row">
        <td>
            <select name="product_id[]" class="form-select receipt-product-select" required aria-label="Chọn thiết bị nhập kho">
                <option value="">— Chọn sản phẩm —</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" data-default-price="<?= (int) round(((int) $p['price']) * 0.85, -4) ?>">
                        [<?= Response::e($p['code']) ?>] <?= Response::e($p['name']) ?> (Tồn: <?= (int) $p['quantity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="number" name="quantity[]" min="1" max="10000" class="form-control num text-end receipt-qty-input" value="1" required aria-label="Số lượng nhập">
        </td>
        <td>
            <input type="number" name="import_price[]" min="0" step="10000" class="form-control num text-end receipt-price-input" value="0" required aria-label="Đơn giá nhập">
        </td>
        <td class="text-end num fw-semibold receipt-line-subtotal">0 đ</td>
        <td class="text-end">
            <button type="button" class="btn btn-outline-danger btn-icon btn-remove-receipt-line" aria-label="Xóa dòng sản phẩm">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </td>
    </tr>
</template>
