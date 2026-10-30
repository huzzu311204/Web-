<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\Csrf;

$flashes = Response::getFlashes();
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Response::e($pageTitle ?? 'Đăng nhập Quản trị — ProGear Studio') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">

    <div style="width: 100%; max-width: 420px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="index.php?r=home" class="text-decoration-none d-flex align-items-center gap-2 text-light fw-bold fs-5">
                <i class="bi bi-aperture" style="color: #f0a23b;" aria-hidden="true"></i>
                <span>PROGEAR <span class="fw-normal text-muted fs-6">ADMIN</span></span>
            </a>
            <a href="index.php?r=home" class="text-muted small text-decoration-none">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Trang khách
            </a>
        </div>

        <?php foreach ($flashes as $type => $msg): ?>
            <?php $borderColor = in_array($type, ['error', 'danger'], true) ? '#f87171' : '#34d399'; ?>
            <div class="card p-3 mb-3 small" style="border-left: 3px solid <?= $borderColor ?>;" role="alert">
                <?= Response::e($msg) ?>
            </div>
        <?php endforeach; ?>

        <div class="card p-4">
            <h1 class="h3 mb-1">Đăng nhập quản trị</h1>
            <p class="text-muted small mb-4">Dành cho nhân viên điều phối thiết bị & duyệt đơn thuê.</p>

            <?php if (!empty($errors['general'])): ?>
                <div class="p-3 mb-3 rounded small d-flex align-items-center gap-2" style="background: rgba(248, 113, 113, 0.12); border: 1px solid #f87171; color: #f87171;" role="alert">
                    <i class="bi bi-exclamation-octagon" aria-hidden="true"></i>
                    <span><?= Response::e($errors['general']) ?></span>
                </div>
            <?php endif; ?>

            <form action="index.php?r=admin/login" method="post" novalidate>
                <?= Csrf::field() ?>

                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>"
                        value="<?= Response::e($old['username'] ?? '') ?>"
                        placeholder="Nhập username (ví dụ: admin)"
                        autocomplete="username"
                        autofocus
                        required
                    >
                    <?php if (!empty($errors['username'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['username']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                        placeholder="Nhập mật khẩu"
                        autocomplete="current-password"
                        required
                    >
                    <?php if (!empty($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= Response::e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                    <span>Đăng nhập hệ thống</span>
                </button>
            </form>

            <hr class="border-secondary my-4">
            <div class="small text-muted">
                <div>Tài khoản mẫu chạy thử:</div>
                <div class="mt-1">Username: <span class="code text-light">admin</span> &middot; Mật khẩu: <span class="code text-light">Admin@123</span></div>
            </div>
        </div>
    </div>

</body>
</html>

