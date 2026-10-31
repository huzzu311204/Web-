<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\UiHelper;

$rid = (int) $receipt['id'];
$code = UiHelper::formatReceiptCode($rid);
$details = $receipt['details'] ?? [];
?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="index.php?r=admin/import-receipts" class="text-decoration-none">Phiếu nhập kho</a></li>
                <li class="breadcrumb-item active text-muted code" aria-current="page"><?= Response::e($code) ?></li>
            </ol>
        </nav>
        <h1 class="h2 mb-0">Chi tiết Phiếu nhập <span class="code" style="color: #f0a23b;"><?= Response::e($code) ?></span></h1>
    </div>

    <div class="d-flex gap-2">
        <a href="index.php?r=admin/import-receipts" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>Danh sách phiếu nhập</span>
        </a>
        <a href="index.php?r=admin/import-receipts/print&id=<?= $rid ?>" target="_blank" class="btn btn-primary btn-sm">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>In phiếu nhập (A4)</span>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card p-4 h-100">
            <h2 class="h6 text-muted text-uppercase mb-3">Thông tin Nhà cung cấp</h2>
            <div class="fw-bold fs-5 mb-2"><?= Response::e($receipt['supplier_name'] ?? '—') ?></div>
            <div class="small text-muted d-flex flex-column gap-1">
                <div><i class="bi bi-telephone me-2" aria-hidden="true"></i>SĐT: <span class="num text-light"><?= Response::e($receipt['supplier_phone'] ?? '—') ?></span></div>
                <div><i class="bi bi-envelope me-2" aria-hidden="true"></i>Email: <span class="code text-light"><?= Response::e($receipt['supplier_email'] ?? '—') ?></span></div>
                <div><i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Địa chỉ: <span class="text-light"><?= Response::e($receipt['supplier_address'] ?? '—') ?></span></div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-4 h-100">
            <h2 class="h6 text-muted text-uppercase mb-3">Thông tin Chứng từ Nhập kho</h2>
            <div class="row g-2 small">
                <div class="col-5 text-muted">Mã phiếu nhập:</div>
                <div class="col-7 code fw-bold text-light"><?= Response::e($code) ?></div>

                <div class="col-5 text-muted">Ngày nhập kho:</div>
                <div class="col-7 num text-light"><?= Response::e(date('d/m/Y', strtotime((string) $receipt['import_date']))) ?></div>

                <div class="col-5 text-muted">Thời điểm ghi nhận:</div>
                <div class="col-7 num text-light"><?= Response::e(date('d/m/Y H:i:s', strtotime((string) $receipt['created_at']))) ?></div>

                <div class="col-5 text-muted">Người lập phiếu:</div>
                <div class="col-7 text-light">
                    <?= Response::e($receipt['creator_name'] ?? '—') ?>
                    <?php if (!empty($receipt['creator_username'])): ?>
                        (<span class="code">@<?= Response::e($receipt['creator_username']) ?></span>)
                    <?php endif; ?>
                </div>

                <div class="col-5 text-muted">Ghi chú:</div>
                <div class="col-7 text-light"><?= Response::e($receipt['note'] ?: '—') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="p-3 border-bottom border-secondary d-flex align-items-center justify-content-between">
        <h2 class="h5 mb-0">Danh sách dòng hàng đã nhập</h2>
        <a href="index.php?r=admin/stock-logs" class="small text-decoration-none">
            Xem lịch sử biến động tồn kho <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Mã SKU</th>
                    <th>Tên thiết bị</th>
                    <th>Danh mục / Hãng</th>
                    <th class="text-end">Số lượng nhập</th>
                    <th class="text-end">Đơn giá nhập</th>
                    <th class="text-end">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sumQty = 0;
                foreach ($details as $idx => $d):
                    $sumQty += (int) $d['quantity'];
                ?>
                    <tr>
                        <td class="num text-muted"><?= $idx + 1 ?></td>
                        <td class="code"><?= Response::e($d['product_code'] ?? '') ?></td>
                        <td class="fw-semibold">
                            <a href="index.php?r=admin/products/edit&id=<?= (int) $d['product_id'] ?>" class="text-decoration-none text-light">
                                <?= Response::e($d['product_name'] ?? '') ?>
                            </a>
                        </td>
                        <td class="small text-muted">
                            <?= Response::e($d['category_name'] ?? '—') ?>
                            <?php if (!empty($d['brand_name'])): ?>
                                &middot; <?= Response::e($d['brand_name']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-end num fw-semibold" style="color: #34d399;">+<?= (int) $d['quantity'] ?></td>
                        <td class="text-end num"><?= Response::money($d['import_price']) ?></td>
                        <td class="text-end price"><?= Response::money($d['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Tổng cộng phiếu nhập:</th>
                    <th class="text-end num"><?= $sumQty ?> thiết bị</th>
                    <th></th>
                    <th class="text-end price fs-5"><?= Response::money($receipt['total_amount']) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
