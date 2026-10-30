<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\UiHelper;
use App\Core\Csrf;
?>
<div class="container-studio py-4 py-lg-5">
    <!-- Hero bất đối xứng (Mục 5.1 & MASTER.md 3.1: nửa trái tiêu đề + nút, nửa phải ảnh thiết bị nổi bật) -->
    <section class="card p-4 p-lg-5 mb-5">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <span class="badge-status stock-in mb-3">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    <span>Hệ thống tra cứu & kiểm soát tồn kho thời gian thực</span>
                </span>
                <h1 class="display-6 fw-bold mb-3">Thiết bị Nhiếp ảnh & Quay phim Chuyên nghiệp</h1>
                <p class="text-muted mb-4">
                    Tra cứu thông số kỹ thuật chi tiết theo ngàm ống kính, lọc đa tiêu chí, so sánh trực diện tối đa 3 thiết bị và kiểm tra tình trạng tồn kho chính xác tại showroom.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="index.php?r=catalog" class="btn btn-primary">
                        <i class="bi bi-camera" aria-hidden="true"></i>
                        <span>Xem toàn bộ sản phẩm</span>
                    </a>
                </div>
            </div>

            <div class="col-lg-6">
                <?php if ($heroProduct): ?>
                    <div class="card p-3" style="background: #15171a;">
                        <div class="row g-3 align-items-center">
                            <div class="col-sm-6">
                                <div class="product-card-img-wrap rounded border border-secondary">
                                    <img src="<?= Response::e(UiHelper::imageUrl($heroProduct['image'] ?? null)) ?>" alt="<?= Response::e($heroProduct['name']) ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted mb-1">
                                    <?= Response::e($heroProduct['brand_name'] ?? '') ?> &middot; <span class="code"><?= Response::e($heroProduct['code']) ?></span>
                                </div>
                                <h2 class="h5 mb-2"><?= Response::e($heroProduct['name']) ?></h2>
                                <div class="mb-2">
                                    <?= UiHelper::stockBadge((int) $heroProduct['quantity'], (int) $heroProduct['min_stock'], (string) $heroProduct['status']) ?>
                                </div>
                                <div class="price fs-4 mb-3"><?= Response::money($heroProduct['price']) ?></div>
                                <a href="index.php?r=catalog&q=<?= urlencode((string) $heroProduct['code']) ?>" class="btn btn-outline-secondary btn-sm w-100">
                                    <span>Xem thông số</span>
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Lưới danh mục thiết bị -->
    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h3 mb-0">Danh mục thiết bị</h2>
            <a href="index.php?r=catalog" class="small text-decoration-none">Xem tất cả <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <?php if ($cat['parent_id'] !== null || (int) $cat['id'] > 1): ?>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="index.php?r=catalog&category_id=<?= (int) $cat['id'] ?>" class="card p-3 text-decoration-none h-100 d-flex flex-column justify-content-between">
                            <div class="fw-semibold text-light mb-2"><?= Response::e($cat['name']) ?></div>
                            <div class="small text-muted d-flex align-items-center justify-content-between">
                                <span><span class="num text-light"><?= (int) $cat['product_count'] ?></span> SP</span>
                                <i class="bi bi-chevron-right" style="color: #f0a23b;" aria-hidden="true"></i>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Danh sách thiết bị nổi bật / mới nhất -->
    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="h3 mb-0">Thiết bị mới cập nhật</h2>
                <small class="text-muted">Hiển thị nhãn tồn kho và thông số kỹ thuật chuẩn.</small>
            </div>
            <a href="index.php?r=catalog" class="btn btn-outline-secondary btn-sm">Lọc & Tìm kiếm nâng cao</a>
        </div>

        <div class="row g-4">
            <?php foreach ($newestProducts as $p): ?>
                <?php
                $isOut = (int) $p['quantity'] <= 0;
                $specs = array_slice($p['specs'] ?? [], 0, 3);
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card product-card <?= $isOut ? 'is-out-of-stock' : '' ?>">
                        <a href="index.php?r=product&id=<?= (int) $p['id'] ?>" class="text-decoration-none text-light">
                            <div class="product-card-img-wrap">
                                <img src="<?= Response::e(UiHelper::imageUrl($p['image'] ?? null)) ?>" alt="<?= Response::e($p['name']) ?>" loading="lazy">
                            </div>
                        </a>
                        <div class="p-3 d-flex flex-column flex-grow-1 justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <span class="small text-muted"><?= Response::e($p['brand_name'] ?? $p['category_name']) ?> &middot; <span class="code"><?= Response::e($p['code']) ?></span></span>
                                    <?= UiHelper::stockBadge((int) $p['quantity'], (int) $p['min_stock'], (string) $p['status']) ?>
                                </div>
                                <h3 class="h5 mb-2">
                                    <a href="index.php?r=product&id=<?= (int) $p['id'] ?>" class="text-decoration-none text-light">
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

                            <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary">
                                <div class="price fs-5"><?= Response::money($p['price']) ?></div>
                                <a href="index.php?r=product&id=<?= (int) $p['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <span>Chi tiết</span>
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Đã xem gần đây (Mục 5.1 - K4) -->
    <?php if (!empty($recentProducts)): ?>
        <section>
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Sản phẩm đã xem gần đây</h2>
                <form action="index.php?r=recent/clear" method="post" class="m-0">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Xóa lịch sử xem</button>
                </form>
            </div>
            <div class="row g-3">
                <?php foreach ($recentProducts as $rp): ?>
                    <div class="col-6 col-md-3">
                        <a href="index.php?r=product&id=<?= (int) $rp['id'] ?>" class="card p-2 text-decoration-none h-100">
                            <div class="product-card-img-wrap rounded mb-2">
                                <img src="<?= Response::e(UiHelper::imageUrl($rp['image'] ?? null)) ?>" alt="<?= Response::e($rp['name']) ?>" loading="lazy">
                            </div>
                            <div class="small fw-semibold text-light text-truncate"><?= Response::e($rp['name']) ?></div>
                            <div class="price small"><?= Response::money($rp['price']) ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

