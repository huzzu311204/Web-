<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\UiHelper;

$pid = (int) $p['id'];
$qty = (int) $p['quantity'];
$min = (int) $p['min_stock'];
$status = (string) ($p['status'] ?? 'ACTIVE');
$isOut = $qty <= 0;
$specs = array_slice($p['specs'] ?? [], 0, 3);
$compareIds = $_SESSION['compare_ids'] ?? [];
$inCompare = is_array($compareIds) && in_array($pid, array_map('intval', $compareIds), true);
?>
<div class="card product-card <?= $isOut ? 'is-out-of-stock' : '' ?> h-100">
    <a href="index.php?r=product&id=<?= $pid ?>" class="text-decoration-none text-light">
        <div class="product-card-img-wrap">
            <img src="<?= Response::e(UiHelper::imageUrl($p['image'] ?? null)) ?>" alt="<?= Response::e($p['name']) ?>" loading="lazy">
        </div>
    </a>
    <div class="p-3 d-flex flex-column flex-grow-1 justify-content-between">
        <div>
            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                <span class="small text-muted text-truncate">
                    <?= Response::e($p['brand_name'] ?? $p['category_name'] ?? 'Thiết bị') ?>
                    &middot; <span class="code"><?= Response::e($p['code']) ?></span>
                </span>
                <?= UiHelper::stockBadge($qty, $min, $status) ?>
            </div>

            <h3 class="h5 mb-2">
                <a href="index.php?r=product&id=<?= $pid ?>" class="text-decoration-none text-light">
                    <?= Response::e($p['name']) ?>
                </a>
            </h3>

            <?php if (!empty($specs)): ?>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <?php foreach ($specs as $sp): ?>
                        <span class="spec-chip"><?= Response::e($sp['spec_name']) ?>: <?= Response::e($sp['spec_value']) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary gap-2">
            <div class="price fs-5"><?= Response::money($p['price']) ?></div>

            <div class="d-flex align-items-center gap-1">
                <button
                    type="button"
                    class="btn <?= $inCompare ? 'btn-primary' : 'btn-outline-secondary' ?> btn-sm btn-toggle-compare"
                    data-id="<?= $pid ?>"
                    data-name="<?= Response::e($p['name']) ?>"
                    title="Thêm vào bảng so sánh thông số"
                    aria-label="So sánh <?= Response::e($p['name']) ?>"
                >
                    <i class="bi bi-columns-gap" aria-hidden="true"></i>
                    <span><?= $inCompare ? 'Đã chọn' : 'So sánh' ?></span>
                </button>
                <a href="index.php?r=product&id=<?= $pid ?>" class="btn btn-outline-secondary btn-sm btn-icon" title="Xem chi tiết thiết bị" aria-label="Chi tiết <?= Response::e($p['name']) ?>">
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</div>
