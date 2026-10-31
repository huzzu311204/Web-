<?php
declare(strict_types=1);

use App\Core\Response;

$items = $items ?? ($result['items'] ?? []);
$total = (int) ($total ?? ($result['total'] ?? 0));
$page  = (int) ($page ?? ($result['page'] ?? 1));
$pages = (int) ($pages ?? ($result['pages'] ?? 1));
$activeChips = $activeChips ?? [];
?>
<!-- Thanh hiển thị Chip bộ lọc đang áp dụng & nút Xóa tất cả (Mục 7 - GĐ3 & TC07) -->
<?php if (!empty($activeChips)): ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="small text-muted">Đang lọc theo:</span>
        <?php foreach ($activeChips as $chip): ?>
            <span class="filter-chip">
                <span><?= Response::e($chip['label']) ?></span>
                <button
                    type="button"
                    class="btn-remove-filter-chip"
                    data-filter-type="<?= Response::e($chip['type']) ?>"
                    data-filter-value="<?= Response::e($chip['value']) ?>"
                    aria-label="Gỡ bộ lọc <?= Response::e($chip['label']) ?>"
                >
                    <i class="bi bi-x" aria-hidden="true"></i>
                </button>
            </span>
        <?php endforeach; ?>
        <button type="button" id="btn-clear-all-filters" class="btn btn-outline-secondary btn-sm py-0 px-2" style="min-height: 28px; font-size: 0.78rem;">
            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
            <span>Xóa tất cả</span>
        </button>
    </div>
<?php endif; ?>

<?php if (empty($items)): ?>
    <!-- Trạng thái rỗng có hướng dẫn rõ (MASTER.md Mục 3.1) -->
    <div class="card p-5 text-center">
        <div class="mb-2">
            <i class="bi bi-search fs-2 text-muted" aria-hidden="true"></i>
        </div>
        <h2 class="h5 mb-2">Không có sản phẩm nào khớp với bộ lọc hiện tại</h2>
        <p class="text-muted small mb-3">
            Bạn hãy thử mở rộng khoảng giá, bỏ bớt điều kiện thương hiệu hoặc xóa bộ lọc để xem toàn bộ thiết bị trong showroom.
        </p>
        <div>
            <a href="index.php?r=catalog" id="btn-empty-reset-filters" class="btn btn-primary btn-sm">
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                <span>Xóa toàn bộ bộ lọc</span>
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Lưới thẻ sản phẩm (3 cột trên Desktop >= xl, 2 cột trên Tablet md) -->
    <div class="row g-4">
        <?php foreach ($items as $p): ?>
            <div class="col-md-6 col-xl-4">
                <?php require __DIR__ . '/_card.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Thanh phân trang phía Server + hỗ trợ AJAX -->
    <?php if ($pages > 1): ?>
        <?php
        $queryParams = $filters ?? $_GET;
        unset($queryParams['page']);
        $queryParams['r'] = 'catalog';
        ?>
        <nav aria-label="Phân trang danh mục thiết bị" class="mt-4 d-flex justify-content-center">
            <ul class="pagination mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <?php $prevQs = http_build_query(array_merge($queryParams, ['page' => max(1, $page - 1)])); ?>
                    <a class="page-link catalog-page-link" href="index.php?<?= Response::e($prevQs) ?>" data-page="<?= max(1, $page - 1) ?>" aria-label="Trang trước">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </a>
                </li>

                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <?php $pageQs = http_build_query(array_merge($queryParams, ['page' => $i])); ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link num catalog-page-link" href="index.php?<?= Response::e($pageQs) ?>" data-page="<?= $i ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                    <?php $nextQs = http_build_query(array_merge($queryParams, ['page' => min($pages, $page + 1)])); ?>
                    <a class="page-link catalog-page-link" href="index.php?<?= Response::e($nextQs) ?>" data-page="<?= min($pages, $page + 1) ?>" aria-label="Trang tiếp">
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
