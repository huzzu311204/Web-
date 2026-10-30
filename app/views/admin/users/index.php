<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;
use App\Core\Auth;

$isEditing = !empty($old['id']);
?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h2 mb-1">Tài khoản Quản trị viên</h1>
        <p class="text-muted small mb-0">Quản lý nhân sự điều phối kho & duyệt đơn (Mật khẩu lưu bằng bcrypt hash).</p>
    </div>
    <?php if ($isEditing): ?>
        <a href="index.php?r=admin/users" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Chuyển sang thêm mới</span>
        </a>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Bảng danh sách tài khoản -->
    <div class="col-lg-8">
        <div class="card">
            <div class="p-3 border-bottom border-secondary">
                <h2 class="h5 mb-0">Danh sách tài khoản (<span class="num"><?= count($users) ?></span>)</h2>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Họ và tên</th>
                            <th>Liên hệ</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="code fw-semibold"><?= Response::e($u['username']) ?></td>
                                <td>
                                    <div class="fw-medium"><?= Response::e($u['full_name']) ?></div>
                                    <small class="text-muted">Tạo ngày: <span class="num"><?= Response::e(substr((string) $u['created_at'], 0, 10)) ?></span></small>
                                </td>
                                <td class="small">
                                    <?php if (!empty($u['phone'])): ?>
                                        <div class="num"><?= Response::e($u['phone']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($u['email'])): ?>
                                        <div class="text-muted"><?= Response::e($u['email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'ACTIVE'): ?>
                                        <span class="badge-status status-returned">
                                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                            <span>Đang hoạt động</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-status status-rejected">
                                            <i class="bi bi-lock" aria-hidden="true"></i>
                                            <span>Đã khóa</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="index.php?r=admin/users&edit=<?= (int) $u['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                            <span>Sửa</span>
                                        </a>
                                        <?php if ((int) $u['id'] !== Auth::id()): ?>
                                            <form action="index.php?r=admin/users/toggle" method="post" class="m-0">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                <button type="submit" class="btn <?= $u['status'] === 'ACTIVE' ? 'btn-outline-danger' : 'btn-outline-secondary' ?> btn-sm">
                                                    <i class="bi <?= $u['status'] === 'ACTIVE' ? 'bi-lock' : 'bi-unlock' ?>" aria-hidden="true"></i>
                                                    <span><?= $u['status'] === 'ACTIVE' ? 'Khóa' : 'Mở' ?></span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Form Thêm / Sửa dùng chung (Mục 4.2) -->
    <div class="col-lg-4">
        <div class="card p-4">
            <h2 class="h5 mb-3"><?= $isEditing ? 'Cập nhật tài khoản' : 'Thêm tài khoản Admin' ?></h2>

            <form action="index.php?r=admin/users/save" method="post" novalidate>
                <?= Csrf::field() ?>
                <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?= (int) $old['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập (Username)</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control code <?= !empty($errors['username']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['username'] ?? '') ?>"
                        <?= $isEditing ? 'readonly' : 'required' ?>
                        placeholder="Ví dụ: kho_progear"
                    >
                    <?php if (!empty($errors['username'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['username']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Họ và tên</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="form-control <?= !empty($errors['full_name']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['full_name'] ?? '') ?>"
                        required
                        placeholder="Nhập họ và tên nhân viên"
                    >
                    <?php if (!empty($errors['full_name'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['full_name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Số điện thoại</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="form-control num <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['phone'] ?? '') ?>"
                        placeholder="0901234567"
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
                        class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['email'] ?? '') ?>"
                        placeholder="nhanvien@progear.vn"
                    >
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">
                        <?= $isEditing ? 'Mật khẩu mới (để trống nếu giữ nguyên)' : 'Mật khẩu khởi tạo' ?>
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                        placeholder="Tối thiểu 6 ký tự"
                        <?= $isEditing ? '' : 'required' ?>
                    >
                    <?php if (!empty($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <span><?= $isEditing ? 'Lưu thay đổi' : 'Tạo tài khoản' ?></span>
                </button>
            </form>
        </div>
    </div>
</div>

