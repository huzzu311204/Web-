<?php
declare(strict_types=1);

if (($pages ?? 1) > 1): ?>
    <nav aria-label="Phân trang danh sách sản phẩm">
        <ul class="pagination mb-0 justify-content-end">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link admin-page-link" href="#" data-page="<?= max(1, $page - 1) ?>" aria-label="Trang trước">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link admin-page-link" href="#" data-page="<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                <a class="page-link admin-page-link" href="#" data-page="<?= min($pages, $page + 1) ?>" aria-label="Trang tiếp">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

