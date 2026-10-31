<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;
use App\Core\UiHelper;

$pid    = (int) $product['id'];
$qty    = (int) $product['quantity'];
$min    = (int) $product['min_stock'];
$status = (string) $product['status'];
$compareIds = $_SESSION['compare_ids'] ?? [];
$inCompare  = is_array($compareIds) && in_array($pid, array_map('intval', $compareIds), true);

$mountValue = null;
foreach ($specs as $sp) {
    if (mb_strtolower((string) $sp['spec_name'], 'UTF-8') === 'ngàm') {
        $mountValue = (string) $sp['spec_value'];
        break;
    }
}
?>
<div class="container-studio py-4 py-lg-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="index.php?r=home" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="index.php?r=catalog" class="text-decoration-none">Danh mục thiết bị</a></li>
            <?php if (!empty($product['category_name'])): ?>
                <li class="breadcrumb-item">
                    <a href="index.php?r=catalog&category_id=<?= (int) $product['category_id'] ?>" class="text-decoration-none">
                        <?= Response::e($product['category_name']) ?>
                    </a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active text-muted text-truncate" aria-current="page" style="max-width: 280px;">
                <?= Response::e($product['name']) ?>
            </li>
        </ol>
    </nav>

    <!-- Khối thông tin chính của thiết bị -->
    <div class="card p-4 p-lg-5 mb-5">
        <div class="row g-4 g-lg-5 align-items-start">
            <!-- Cột ảnh đại diện tỷ lệ 4:3 -->
            <div class="col-lg-6">
                <div class="product-card-img-wrap rounded border border-secondary">
                    <img
                        src="<?= Response::e(UiHelper::imageUrl($product['image'] ?? null)) ?>"
                        alt="<?= Response::e($product['name']) ?>"
                    >
                </div>
            </div>

            <!-- Cột thông tin giá, tồn kho, mô tả & thao tác so sánh -->
            <div class="col-lg-6">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div class="small text-muted">
                        <?php if (!empty($product['brand_name'])): ?>
                            <a href="index.php?r=catalog&brands[]=<?= (int) $product['brand_id'] ?>" class="text-decoration-none fw-semibold text-light">
                                <?= Response::e($product['brand_name']) ?>
                            </a>
                            &middot;
                        <?php endif; ?>
                        <span>Mã SKU: <strong class="code text-light"><?= Response::e($product['code']) ?></strong></span>
                    </div>
                    <?= UiHelper::stockBadge($qty, $min, $status) ?>
                </div>

                <h1 class="h2 fw-bold mb-3"><?= Response::e($product['name']) ?></h1>

                <div class="p-3 rounded mb-4" style="background: var(--surface-input); border: 1px solid var(--bs-border-color);">
                    <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2">
                        <div>
                            <div class="small text-muted mb-1">Giá niêm yết tại Showroom:</div>
                            <div class="price display-6 fw-bold"><?= Response::money($product['price']) ?></div>
                        </div>
                        <div class="text-sm-end">
                            <div class="small text-muted mb-1">Số lượng sẵn có tại kho:</div>
                            <div class="num fs-5 fw-semibold text-light">
                                <?= $qty > 0 ? $qty . ' thiết bị' : 'Tạm hết hàng (0)' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($product['description'])): ?>
                    <div class="mb-4">
                        <h2 class="h6 text-uppercase text-muted mb-2">Mô tả & Đặc điểm nổi bật</h2>
                        <p class="mb-0 text-light" style="line-height: 1.65;">
                            <?= nl2br(Response::e($product['description'])) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Tóm tắt nhanh các chip thông số chính -->
                <?php if (!empty($specs)): ?>
                    <div class="mb-4">
                        <div class="small text-muted mb-2">Thông số kỹ thuật tiêu biểu:</div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach (array_slice($specs, 0, 5) as $sp): ?>
                                <span class="spec-chip">
                                    <strong><?= Response::e($sp['spec_name']) ?>:</strong> <?= Response::e($sp['spec_value']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Một nút hành động chính trên màn hình chi tiết -->
                <div class="d-flex flex-wrap gap-2 pt-3 border-top border-secondary">
                    <button
                        type="button"
                        class="btn <?= $inCompare ? 'btn-primary' : 'btn-outline-secondary' ?> btn-toggle-compare"
                        data-id="<?= $pid ?>"
                        data-name="<?= Response::e($product['name']) ?>"
                    >
                        <i class="bi bi-columns-gap" aria-hidden="true"></i>
                        <span><?= $inCompare ? 'Đã có trong Bảng so sánh' : 'Thêm vào Bảng so sánh (Tối đa 3)' ?></span>
                    </button>

                    <a href="index.php?r=compare" class="btn btn-outline-secondary">
                        <span>Mở Bảng so sánh</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng Thông số kỹ thuật chi tiết (product_specs) -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card p-4 h-100">
                <h2 class="h4 mb-3">Bảng thông số kỹ thuật chi tiết</h2>
                <?php if (empty($specs)): ?>
                    <p class="text-muted mb-0">Thông số kỹ thuật của thiết bị này đang được cập nhật.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <tbody>
                                <?php foreach ($specs as $sp): ?>
                                    <tr>
                                        <th style="width: 38%;" class="text-muted fw-medium"><?= Response::e($sp['spec_name']) ?></th>
                                        <td class="fw-semibold text-light num"><?= Response::e($sp['spec_value']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <h2 class="h5 mb-3">Thông tin nguồn gốc & Bảo hành</h2>
                <ul class="list-unstyled small d-flex flex-column gap-3 mb-0">
                    <li class="d-flex justify-content-between border-bottom border-secondary pb-2">
                        <span class="text-muted">Danh mục:</span>
                        <span class="fw-semibold text-light"><?= Response::e($product['category_name'] ?? '—') ?></span>
                    </li>
                    <li class="d-flex justify-content-between border-bottom border-secondary pb-2">
                        <span class="text-muted">Thương hiệu:</span>
                        <span class="fw-semibold text-light"><?= Response::e($product['brand_name'] ?? '—') ?></span>
                    </li>
                    <li class="d-flex justify-content-between border-bottom border-secondary pb-2">
                        <span class="text-muted">Nhà phân phối:</span>
                        <span class="fw-semibold text-light text-end"><?= Response::e($product['supplier_name'] ?? '—') ?></span>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span class="text-muted">Tình trạng tại kho:</span>
                        <span><?= UiHelper::stockBadge($qty, $min, $status) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Sản phẩm liên quan / Ống kính tương thích cùng Ngàm (Mục 7 - GĐ3) -->
    <?php if (!empty($relatedProducts)): ?>
        <section class="mb-5">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <?php if (in_array((int) $product['category_id'], [1, 2, 3], true) && $mountValue !== null): ?>
                        <h2 class="h3 mb-0">Ống kính tương thích cùng Ngàm <span class="code" style="color: #f0a23b;"><?= Response::e($mountValue) ?></span></h2>
                        <small class="text-muted">Tự động đối chiếu thông số <code>Ngàm = <?= Response::e($mountValue) ?></code> trong bảng <code>product_specs</code>.</small>
                    <?php else: ?>
                        <h2 class="h3 mb-0">Thiết bị liên quan cùng phân khúc</h2>
                        <small class="text-muted">Gợi ý các thiết bị cùng danh mục và thương hiệu đang kinh doanh.</small>
                    <?php endif; ?>
                </div>
                <a href="index.php?r=catalog" class="small text-decoration-none">Xem toàn bộ danh mục <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>

            <div class="row g-4">
                <?php foreach ($relatedProducts as $p): ?>
                    <div class="col-md-6 col-xl-3">
                        <?php require __DIR__ . '/_card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Sản phẩm đã xem gần đây (Mục 7 - GĐ3 & TC13) -->
    <?php if (!empty($recentProducts)): ?>
        <section class="pt-4 border-top border-secondary">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Thiết bị đã xem gần đây</h2>
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
