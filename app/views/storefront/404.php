<?php
declare(strict_types=1);

use App\Core\Response;
?>
<div class="container-studio py-5">
    <div class="card p-4 p-md-5 container-narrow text-center">
        <div class="mb-3">
            <i class="bi bi-compass fs-1" style="color: #f0a23b;" aria-hidden="true"></i>
        </div>
        <h1 class="h2 mb-2"><?= Response::e($errorHeading ?? 'Không tìm thấy trang yêu cầu') ?></h1>
        <p class="text-muted mb-4"><?= Response::e($errorMessage ?? 'Đường dẫn bạn truy cập không tồn tại hoặc sản phẩm đã được ngừng kinh doanh.') ?></p>
        <div class="d-flex justify-content-center gap-2">
            <a href="index.php?r=home" class="btn btn-primary">
                <i class="bi bi-house-door" aria-hidden="true"></i>
                <span>Về trang chủ</span>
            </a>
            <a href="index.php?r=catalog" class="btn btn-outline-secondary">
                <i class="bi bi-camera" aria-hidden="true"></i>
                <span>Xem danh mục sản phẩm</span>
            </a>
        </div>
    </div>
</div>
