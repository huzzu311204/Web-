<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;

$isEditing = !empty($old['id']);
?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h2 mb-1">Thương hiệu Thiết bị</h1>
        <p class="text-muted small mb-0">Quản lý các hãng sản xuất máy ảnh, ống kính, đèn và phụ kiện studio.</p>
    </div>
    <?php if ($isEditing): ?>
        <a href="index.php?r=admin/brands" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Chuyển sang thêm mới</span>
        </a>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Bảng danh sách thương hiệu -->
    <div class="col-lg-8">
        <div class="card">
            <div class="p-3 border-bottom border-secondary">
                <h2 class="h5 mb-0">Danh sách thương hiệu (<span class="num"><?= count($brands) ?></span>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tên thương hiệu</th>
                            <th>Tệp Logo</th>
                            <th class="text-end">Số sản phẩm</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($brands)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">Chưa có thương hiệu nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($brands as $b): ?>
                                <tr>
                                    <td class="fw-semibold"><?= Response::e($b['name']) ?></td>
                                    <td class="code small text-muted"><?= !empty($b['logo']) ? Response::e($b['logo']) : '—' ?></td>
                                    <td class="text-end num"><?= (int) $b['product_count'] ?></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a href="index.php?r=admin/brands&edit=<?= (int) $b['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span>Sửa</span>
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteBrandModal"
                                                data-id="<?= (int) $b['id'] ?>"
                                                data-name="<?= Response::e($b['name']) ?>"
                                                data-product-count="<?= (int) $b['product_count'] ?>"
                                                aria-label="Xóa thương hiệu <?= Response::e($b['name']) ?>"
                                            >
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                <span>Xóa</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Form Thêm / Sửa dùng chung -->
    <div class="col-lg-4">
        <div class="card p-4">
            <h2 class="h5 mb-3"><?= $isEditing ? 'Cập nhật thương hiệu' : 'Thêm thương hiệu mới' ?></h2>

            <form action="index.php?r=admin/brands/save" method="post" novalidate>
                <?= Csrf::field() ?>
                <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?= (int) $old['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Tên thương hiệu</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="100"
                        class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['name'] ?? '') ?>"
                        required
                        placeholder="Ví dụ: Sony, Canon, Sigma..."
                    >
                    <?php if (!empty($errors['name'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="logo" class="form-label">Tên file logo (tùy chọn)</label>
                    <input
                        type="text"
                        id="logo"
                        name="logo"
                        maxlength="255"
                        class="form-control code <?= !empty($errors['logo']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['logo'] ?? '') ?>"
                        placeholder="sony.png"
                    >
                    <?php if (!empty($errors['logo'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['logo']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    <span><?= $isEditing ? 'Lưu thay đổi' : 'Thêm thương hiệu' ?></span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xác nhận Xóa Thương hiệu -->
<div class="modal fade" id="deleteBrandModal" tabindex="-1" aria-labelledby="deleteBrandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="index.php?r=admin/brands/delete" method="post">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" id="del-brand-id" value="">
                <div class="modal-header">
                    <h2 class="h5 modal-title" id="deleteBrandModalLabel">Xóa thương hiệu</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng hộp thoại"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Bạn có chắc chắn muốn xóa thương hiệu <strong id="del-brand-name" class="text-light"></strong>?</p>
                    <p id="del-brand-warning" class="small text-muted mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy thao tác</button>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                        <span>Xóa thương hiệu</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('deleteBrandModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('del-brand-id').value = btn.getAttribute('data-id');
        document.getElementById('del-brand-name').textContent = btn.getAttribute('data-name');
        const prodCount = parseInt(btn.getAttribute('data-product-count') || '0', 10);
        const warning = document.getElementById('del-brand-warning');

        if (prodCount > 0) {
            warning.textContent = `Thương hiệu này đang gắn với ${prodCount} sản phẩm. Hệ thống sẽ chặn xóa để đảm bảo toàn vẹn dữ liệu.`;
            warning.style.color = '#facc15';
        } else {
            warning.textContent = 'Thương hiệu chưa có sản phẩm tham chiếu, có thể xóa hoàn toàn.';
            warning.style.color = '#9ca3af';
        }
    });
});
</script>
