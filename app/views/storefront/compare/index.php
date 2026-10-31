<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\UiHelper;

$count = count($products);
?>
<div class="container-studio py-4 py-lg-5">
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-1">
                    <li class="breadcrumb-item"><a href="index.php?r=home" class="text-decoration-none">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="index.php?r=catalog" class="text-decoration-none">Danh mục thiết bị</a></li>
                    <li class="breadcrumb-item active text-muted" aria-current="page">So sánh thiết bị</li>
                </ol>
            </nav>
            <h1 class="h2 mb-0">So sánh trực diện Thông số Kỹ thuật (<span class="num"><?= (int) $count ?></span>/3)</h1>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <?php if ($count > 0): ?>
                <button type="button" id="btn-compare-page-clear" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    <span>Xóa tất cả danh sách</span>
                </button>
            <?php endif; ?>
            <a href="index.php?r=catalog" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Chọn thêm thiết bị</span>
            </a>
        </div>
    </div>

    <?php if ($count === 0): ?>
        <!-- Trạng thái rỗng khi chưa chọn thiết bị nào -->
        <div class="card p-5 text-center">
            <div class="mb-2">
                <i class="bi bi-columns-gap fs-2 text-muted" aria-hidden="true"></i>
            </div>
            <h2 class="h5 mb-2">Chưa có thiết bị nào trong Bảng so sánh</h2>
            <p class="text-muted small mb-3">
                Bạn hãy chọn từ 2 đến 3 thiết bị nhiếp ảnh trong danh mục để đối chiếu thông số kỹ thuật, giá bán và tình trạng tồn kho trực diện.
            </p>
            <div>
                <a href="index.php?r=catalog" class="btn btn-primary btn-sm">
                    <i class="bi bi-camera" aria-hidden="true"></i>
                    <span>Mở Danh mục để chọn thiết bị</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php if ($count === 1): ?>
            <div class="card p-3 mb-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2" style="border-left: 3px solid #facc15;">
                <div class="d-flex align-items-center gap-2 small">
                    <i class="bi bi-exclamation-triangle" style="color: #facc15;" aria-hidden="true"></i>
                    <span>Bạn mới chọn <strong>1 thiết bị</strong>. Hãy chọn thêm ít nhất 1 thiết bị nữa (tối đa 3) để bảng đối chiếu thông số phát huy hiệu quả tốt nhất.</span>
                </div>
                <a href="index.php?r=catalog" class="btn btn-primary btn-sm text-nowrap">Chọn thêm thiết bị</a>
            </div>
        <?php endif; ?>

        <!-- Bảng so sánh cột = sản phẩm, hàng = thông tin cơ bản + hợp thông số kỹ thuật (Mục 7 - GĐ4 & TC12) -->
        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" style="border-color: var(--bs-border-color);">
                    <thead>
                        <tr>
                            <th style="width: 220px; min-width: 180px;">Tiêu chí đối chiếu</th>
                            <?php foreach ($products as $p): ?>
                                <?php $pid = (int) $p['id']; ?>
                                <th style="min-width: 260px;" class="align-top">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <span class="small text-muted code"><?= Response::e($p['code']) ?></span>
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger btn-sm py-0 px-2 btn-compare-remove-item"
                                            data-id="<?= $pid ?>"
                                            style="min-height: 26px; font-size: 0.75rem;"
                                            aria-label="Xóa <?= Response::e($p['name']) ?> khỏi bảng so sánh"
                                        >
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                            <span>Bỏ chọn</span>
                                        </button>
                                    </div>
                                    <a href="index.php?r=product&id=<?= $pid ?>" class="text-decoration-none text-light d-block mb-2">
                                        <div class="product-card-img-wrap rounded border border-secondary mb-2" style="max-height: 180px;">
                                            <img src="<?= Response::e(UiHelper::imageUrl($p['image'] ?? null)) ?>" alt="<?= Response::e($p['name']) ?>">
                                        </div>
                                        <div class="h6 fw-bold mb-0"><?= Response::e($p['name']) ?></div>
                                    </a>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th class="text-muted fw-medium">Thương hiệu</th>
                            <?php foreach ($products as $p): ?>
                                <td class="fw-semibold"><?= Response::e($p['brand_name'] ?? '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium">Danh mục</th>
                            <?php foreach ($products as $p): ?>
                                <td><?= Response::e($p['category_name'] ?? '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium">Giá bán niêm yết</th>
                            <?php foreach ($products as $p): ?>
                                <td class="price fs-5"><?= Response::money($p['price']) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium">Tình trạng tồn kho</th>
                            <?php foreach ($products as $p): ?>
                                <td>
                                    <?= UiHelper::stockBadge((int) $p['quantity'], (int) $p['min_stock'], (string) $p['status']) ?>
                                    <span class="small text-muted ms-1 num">(Tồn: <?= (int) $p['quantity'] ?>)</span>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Hợp (Union) toàn bộ các dòng thông số kỹ thuật từ product_specs (TC12) -->
                        <?php if (!empty($specUnionNames)): ?>
                            <tr>
                                <th colspan="<?= $count + 1 ?>" class="text-uppercase small" style="background: #15171a; color: #f0a23b;">
                                    Thông số kỹ thuật chi tiết
                                </th>
                            </tr>
                            <?php foreach ($specUnionNames as $specName): ?>
                                <tr>
                                    <th class="text-muted fw-medium"><?= Response::e($specName) ?></th>
                                    <?php foreach ($products as $p): ?>
                                        <?php
                                        $pid = (int) $p['id'];
                                        $val = $specMatrix[$specName][$pid] ?? '—';
                                        ?>
                                        <td class="num <?= $val === '—' ? 'text-muted' : 'fw-semibold text-light' ?>">
                                            <?= Response::e($val) ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <tr>
                            <th class="text-muted fw-medium">Xem chi tiết</th>
                            <?php foreach ($products as $p): ?>
                                <?php $pid = (int) $p['id']; ?>
                                <td>
                                    <a href="index.php?r=product&id=<?= $pid ?>" class="btn btn-outline-secondary btn-sm w-100">
                                        <span>Xem trang thiết bị</span>
                                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
