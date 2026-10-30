<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;

$isEditing = !empty($old['id']);
?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h2 mb-1">Danh mục Sản phẩm</h1>
        <p class="text-muted small mb-0">Cấu trúc danh mục dạng cây phân cấp (tự tham chiếu qua <code>parent_id</code>).</p>
    </div>
    <?php if ($isEditing): ?>
        <a href="index.php?r=admin/categories" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Chuyển sang thêm mới</span>
        </a>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Bảng danh sách danh mục -->
    <div class="col-lg-8">
        <div class="card">
            <div class="p-3 border-bottom border-secondary">
                <h2 class="h5 mb-0">Cây danh mục (<span class="num"><?= count($categories) ?></span>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tên danh mục</th>
                            <th>Danh mục cha</th>
                            <th class="text-end">Số sản phẩm</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">Chưa có danh mục nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $c): ?>
                                <?php
                                $isChild = !empty($c['parent_id']);
                                $canDelete = ((int) $c['product_count'] === 0 && (int) $c['children_count'] === 0);
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold <?= $isChild ? 'ps-3' : '' ?>">
                                            <?php if ($isChild): ?>
                                                <i class="bi bi-arrow-return-right text-muted me-1" aria-hidden="true"></i>
                                            <?php endif; ?>
                                            <?= Response::e($c['name']) ?>
                                        </div>
                                        <?php if (!empty($c['description'])): ?>
                                            <div class="small text-muted <?= $isChild ? 'ps-4' : '' ?>"><?= Response::e($c['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= !empty($c['parent_name']) ? Response::e($c['parent_name']) : '<span class="badge-status stock-discontinued">Danh mục gốc</span>' ?>
                                    </td>
                                    <td class="text-end num"><?= (int) $c['product_count'] ?></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a href="index.php?r=admin/categories&edit=<?= (int) $c['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span>Sửa</span>
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCategoryModal"
                                                data-id="<?= (int) $c['id'] ?>"
                                                data-name="<?= Response::e($c['name']) ?>"
                                                data-can-delete="<?= $canDelete ? '1' : '0' ?>"
                                                data-product-count="<?= (int) $c['product_count'] ?>"
                                                data-children-count="<?= (int) $c['children_count'] ?>"
                                                aria-label="Xóa danh mục <?= Response::e($c['name']) ?>"
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
            <h2 class="h5 mb-3"><?= $isEditing ? 'Cập nhật danh mục' : 'Thêm danh mục mới' ?></h2>

            <form action="index.php?r=admin/categories/save" method="post" novalidate>
                <?= Csrf::field() ?>
                <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?= (int) $old['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Tên danh mục</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="120"
                        class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['name'] ?? '') ?>"
                        required
                        placeholder="Ví dụ: Máy ảnh Mirrorless"
                    >
                    <?php if (!empty($errors['name'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="parent_id" class="form-label">Danh mục cha</label>
                    <select
                        id="parent_id"
                        name="parent_id"
                        class="form-select <?= !empty($errors['parent_id']) ? 'is-invalid' : '' ?>"
                    >
                        <option value="">— Là danh mục gốc (Cấp 1) —</option>
                        <?php foreach ($parentOptions as $opt): ?>
                            <?php if ($opt['parent_id'] === null): ?>
                                <option value="<?= (int) $opt['id'] ?>" <?= (int) ($old['parent_id'] ?? 0) === (int) $opt['id'] ? 'selected' : '' ?>>
                                    <?= Response::e($opt['name']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['parent_id'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['parent_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Mô tả ngắn</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        maxlength="255"
                        class="form-control <?= !empty($errors['description']) ? 'is-invalid' : '' ?>"
                        placeholder="Mô tả nhóm thiết bị trong danh mục này"
                    ><?= Response::e($old['description'] ?? '') ?></textarea>
                    <?php if (!empty($errors['description'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['description']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    <span><?= $isEditing ? 'Lưu thay đổi' : 'Thêm danh mục' ?></span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xác nhận Xóa Danh mục (Mục 3.6) -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="index.php?r=admin/categories/delete" method="post">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" id="del-cat-id" value="">
                <div class="modal-header">
                    <h2 class="h5 modal-title" id="deleteCategoryModalLabel">Xóa danh mục sản phẩm</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng hộp thoại"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Bạn có chắc chắn muốn xóa danh mục <strong id="del-cat-name" class="text-light"></strong>?</p>
                    <p id="del-cat-warning" class="small text-muted mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy thao tác</button>
                    <button type="submit" id="del-cat-submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                        <span>Xóa danh mục</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('deleteCategoryModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('del-cat-id').value = btn.getAttribute('data-id');
        document.getElementById('del-cat-name').textContent = btn.getAttribute('data-name');
        const prodCount = parseInt(btn.getAttribute('data-product-count') || '0', 10);
        const childCount = parseInt(btn.getAttribute('data-children-count') || '0', 10);
        const warning = document.getElementById('del-cat-warning');

        if (prodCount > 0 || childCount > 0) {
            warning.textContent = `Lưu ý: Danh mục này đang có ${prodCount} sản phẩm và ${childCount} danh mục con. Hệ thống sẽ từ chối xóa để bảo vệ toàn vẹn khóa ngoại.`;
            warning.style.color = '#facc15';
        } else {
            warning.textContent = 'Danh mục này chưa có sản phẩm hay danh mục con nào, có thể xóa an toàn.';
            warning.style.color = '#9ca3af';
        }
    });
});
</script>

