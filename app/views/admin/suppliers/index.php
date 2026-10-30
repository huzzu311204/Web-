<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;

$isEditing = !empty($old['id']);
?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h2 mb-1">Nhà cung cấp Thiết bị</h1>
        <p class="text-muted small mb-0">Quản lý đối tác phân phối chính hãng phục vụ tạo sản phẩm và lập phiếu nhập kho.</p>
    </div>
    <?php if ($isEditing): ?>
        <a href="index.php?r=admin/suppliers" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Chuyển sang thêm mới</span>
        </a>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Bảng danh sách nhà cung cấp -->
    <div class="col-lg-8">
        <div class="card">
            <div class="p-3 border-bottom border-secondary">
                <h2 class="h5 mb-0">Danh sách nhà cung cấp (<span class="num"><?= count($suppliers) ?></span>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nhà cung cấp</th>
                            <th>Liên hệ</th>
                            <th class="text-end">SP / Phiếu nhập</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($suppliers)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">Chưa có nhà cung cấp nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suppliers as $s): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= Response::e($s['name']) ?></div>
                                        <?php if (!empty($s['address'])): ?>
                                            <div class="small text-muted"><?= Response::e($s['address']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <div class="num"><?= Response::e($s['phone']) ?></div>
                                        <?php if (!empty($s['email'])): ?>
                                            <div class="text-muted"><?= Response::e($s['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end num">
                                        <?= (int) $s['product_count'] ?> SP &middot; <?= (int) $s['receipt_count'] ?> PN
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a href="index.php?r=admin/suppliers&edit=<?= (int) $s['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                <span>Sửa</span>
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteSupplierModal"
                                                data-id="<?= (int) $s['id'] ?>"
                                                data-name="<?= Response::e($s['name']) ?>"
                                                data-refs="<?= (int) $s['product_count'] + (int) $s['receipt_count'] ?>"
                                                aria-label="Xóa nhà cung cấp <?= Response::e($s['name']) ?>"
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
            <h2 class="h5 mb-3"><?= $isEditing ? 'Cập nhật nhà cung cấp' : 'Thêm nhà cung cấp' ?></h2>

            <form action="index.php?r=admin/suppliers/save" method="post" novalidate>
                <?= Csrf::field() ?>
                <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?= (int) $old['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Tên nhà cung cấp</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="150"
                        class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['name'] ?? '') ?>"
                        required
                        placeholder="Ví dụ: Công ty TNHH Sony Việt Nam"
                    >
                    <?php if (!empty($errors['name'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Số điện thoại liên hệ</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        maxlength="20"
                        class="form-control num <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['phone'] ?? '') ?>"
                        required
                        placeholder="02838222222 hoặc 0901234567"
                    >
                    <?php if (!empty($errors['phone'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="100"
                        class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['email'] ?? '') ?>"
                        placeholder="contact@supplier.vn"
                    >
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="address" class="form-label">Địa chỉ trụ sở / kho</label>
                    <textarea
                        id="address"
                        name="address"
                        rows="2"
                        maxlength="255"
                        class="form-control <?= !empty($errors['address']) ? 'is-invalid' : '' ?>"
                        placeholder="Số nhà, đường, quận/huyện, tỉnh/thành phố"
                    ><?= Response::e($old['address'] ?? '') ?></textarea>
                    <?php if (!empty($errors['address'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['address']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    <span><?= $isEditing ? 'Lưu thay đổi' : 'Thêm nhà cung cấp' ?></span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xác nhận Xóa Nhà cung cấp -->
<div class="modal fade" id="deleteSupplierModal" tabindex="-1" aria-labelledby="deleteSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="index.php?r=admin/suppliers/delete" method="post">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" id="del-sup-id" value="">
                <div class="modal-header">
                    <h2 class="h5 modal-title" id="deleteSupplierModalLabel">Xóa nhà cung cấp</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng hộp thoại"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Bạn có chắc chắn muốn xóa nhà cung cấp <strong id="del-sup-name" class="text-light"></strong>?</p>
                    <p id="del-sup-warning" class="small text-muted mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy thao tác</button>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                        <span>Xóa nhà cung cấp</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('deleteSupplierModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('del-sup-id').value = btn.getAttribute('data-id');
        document.getElementById('del-sup-name').textContent = btn.getAttribute('data-name');
        const refs = parseInt(btn.getAttribute('data-refs') || '0', 10);
        const warning = document.getElementById('del-sup-warning');

        if (refs > 0) {
            warning.textContent = 'Nhà cung cấp này đang có sản phẩm hoặc phiếu nhập tham chiếu. Hệ thống sẽ chặn xóa.';
            warning.style.color = '#facc15';
        } else {
            warning.textContent = 'Nhà cung cấp chưa có dữ liệu liên kết, có thể xóa an toàn.';
            warning.style.color = '#9ca3af';
        }
    });
});
</script>
