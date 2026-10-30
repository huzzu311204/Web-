<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\UiHelper;

if (empty($items)): ?>
    <tr>
        <td colspan="9" class="text-center py-5">
            <p class="text-muted mb-2">Không tìm thấy sản phẩm nào khớp với bộ lọc hiện tại.</p>
            <a href="index.php?r=admin/products" class="btn btn-outline-secondary btn-sm">Xóa bộ lọc</a>
        </td>
    </tr>
<?php else: ?>
    <?php foreach ($items as $p): ?>
        <?php
        $pid    = (int) $p['id'];
        $qty    = (int) $p['quantity'];
        $min    = (int) $p['min_stock'];
        $status = (string) $p['status'];
        $rowCls = UiHelper::productRowClass($qty, $min, $status);
        $imgUrl = UiHelper::imageUrl($p['image'] ?? null);
        ?>
        <tr id="product-row-<?= $pid ?>" class="<?= $rowCls ?>" data-product-id="<?= $pid ?>">
            <td style="width: 72px;">
                <div class="rounded overflow-hidden border border-secondary" style="width: 56px; height: 42px; background: #0f1012;">
                    <img src="<?= Response::e($imgUrl) ?>" alt="<?= Response::e($p['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </td>
            <td class="code small"><?= Response::e($p['code']) ?></td>
            <td>
                <div class="fw-semibold"><?= Response::e($p['name']) ?></div>
                <div class="small text-muted">
                    <?= Response::e($p['category_name'] ?? '—') ?>
                    <?php if (!empty($p['brand_name'])): ?>
                        &middot; <?= Response::e($p['brand_name']) ?>
                    <?php endif; ?>
                </div>
            </td>
            <td style="width: 165px;">
                <div class="input-group input-group-sm">
                    <input
                        type="number"
                        min="0"
                        step="10000"
                        class="form-control form-control-sm num inline-price-input"
                        value="<?= (int) $p['price'] ?>"
                        data-original="<?= (int) $p['price'] ?>"
                        aria-label="Giá bán của <?= Response::e($p['name']) ?>"
                    >
                    <span class="input-group-text bg-dark border-secondary text-muted">đ</span>
                </div>
            </td>
            <td class="text-end num fs-6" id="product-qty-<?= $pid ?>">
                <?= $qty ?>
            </td>
            <td style="width: 110px;">
                <input
                    type="number"
                    min="0"
                    max="10000"
                    class="form-control form-control-sm num text-end inline-min-stock-input"
                    value="<?= $min ?>"
                    data-original="<?= $min ?>"
                    aria-label="Ngưỡng sắp hết của <?= Response::e($p['name']) ?>"
                >
            </td>
            <td id="product-stock-badge-<?= $pid ?>">
                <?= UiHelper::stockBadge($qty, $min, $status) ?>
            </td>
            <td style="width: 150px;">
                <select
                    class="form-select form-select-sm inline-status-select"
                    data-original="<?= Response::e($status) ?>"
                    aria-label="Trạng thái kinh doanh của <?= Response::e($p['name']) ?>"
                >
                    <option value="ACTIVE" <?= $status === 'ACTIVE' ? 'selected' : '' ?>>Đang bán</option>
                    <option value="INACTIVE" <?= $status === 'INACTIVE' ? 'selected' : '' ?>>Ngừng KD</option>
                </select>
            </td>
            <td class="text-end">
                <div class="d-inline-flex align-items-center gap-1">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm btn-save-inline"
                        data-id="<?= $pid ?>"
                        title="Lưu nhanh giá, ngưỡng & trạng thái"
                        aria-label="Lưu nhanh dòng <?= Response::e($p['code']) ?>"
                    >
                        <i class="bi bi-check2" aria-hidden="true"></i>
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm btn-open-stock-modal"
                        data-bs-toggle="modal"
                        data-bs-target="#stockAdjustModal"
                        data-id="<?= $pid ?>"
                        data-code="<?= Response::e($p['code']) ?>"
                        data-name="<?= Response::e($p['name']) ?>"
                        data-qty="<?= $qty ?>"
                        title="Xuất kho hoặc Điều chỉnh kiểm kê"
                        aria-label="Điều chỉnh tồn kho <?= Response::e($p['name']) ?>"
                    >
                        <i class="bi bi-sliders" aria-hidden="true"></i>
                    </button>
                    <a
                        href="index.php?r=admin/products/edit&id=<?= $pid ?>"
                        class="btn btn-outline-secondary btn-sm"
                        title="Chỉnh sửa chi tiết & thông số"
                        aria-label="Chỉnh sửa sản phẩm <?= Response::e($p['name']) ?>"
                    >
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm btn-open-delete-product"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteProductModal"
                        data-id="<?= $pid ?>"
                        data-code="<?= Response::e($p['code']) ?>"
                        data-name="<?= Response::e($p['name']) ?>"
                        title="Xóa hoặc ngừng kinh doanh"
                        aria-label="Xóa sản phẩm <?= Response::e($p['name']) ?>"
                    >
                        <i class="bi bi-trash" aria-hidden="true"></i>
                    </button>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
