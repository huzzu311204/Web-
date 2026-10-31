<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\UiHelper;

$items = $result['items'] ?? [];
$total = (int) ($result['total'] ?? 0);
$page  = (int) ($result['page'] ?? 1);
$pages = (int) ($result['pages'] ?? 1);
?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h2 mb-1">Phiếu nhập kho</h1>
        <p class="text-muted small mb-0">
            Tổng cộng <strong class="num text-light"><?= $total ?></strong> phiếu nhập. Phiếu nhập sau khi lưu được khóa chỉnh sửa/xóa để bảo toàn lịch sử tồn kho.
        </p>
    </div>
    <div>
        <a href="index.php?r=admin/import-receipts/create" class="btn btn-primary">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Lập phiếu nhập mới</span>
        </a>
    </div>
</div>

<!-- Bộ lọc phiếu nhập -->
<div class="card p-3 mb-4">
    <form action="index.php" method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="admin/import-receipts">

        <div class="col-md-4">
            <label for="supplier_id" class="form-label">Nhà cung cấp</label>
            <select id="supplier_id" name="supplier_id" class="form-select">
                <option value="">Tất cả nhà cung cấp</option>
                <?php foreach ($suppliers as $sup): ?>
                    <option value="<?= (int) $sup['id'] ?>" <?= (int) ($filters['supplier_id'] ?? 0) === (int) $sup['id'] ? 'selected' : '' ?>>
                        <?= Response::e($sup['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-sm-6 col-md-3">
            <label for="date_from" class="form-label">Từ ngày</label>
            <input type="date" id="date_from" name="date_from" class="form-control num" value="<?= Response::e($filters['date_from'] ?? '') ?>">
        </div>

        <div class="col-sm-6 col-md-3">
            <label for="date_to" class="form-label">Đến ngày</label>
            <input type="date" id="date_to" name="date_to" class="form-control num" value="<?= Response::e($filters['date_to'] ?? '') ?>">
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-outline-secondary w-100">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <span>Lọc</span>
            </button>
            <a href="index.php?r=admin/import-receipts" class="btn btn-outline-secondary btn-icon" title="Đặt lại bộ lọc" aria-label="Đặt lại bộ lọc">
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
            </a>
        </div>
    </form>
</div>

<!-- Bảng danh sách phiếu nhập -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Ngày nhập</th>
                    <th>Nhà cung cấp</th>
                    <th>Người lập phiếu</th>
                    <th class="text-end">Số dòng / Tổng SL</th>
                    <th class="text-end">Tổng tiền nhập</th>
                    <th>Ghi chú</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <p class="text-muted mb-2">Chưa có phiếu nhập kho nào khớp với bộ lọc.</p>
                            <a href="index.php?r=admin/import-receipts/create" class="btn btn-primary btn-sm">Lập phiếu nhập đầu tiên</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $r): ?>
                        <?php $rid = (int) $r['id']; ?>
                        <tr>
                            <td class="code fw-semibold">
                                <a href="index.php?r=admin/import-receipts/detail&id=<?= $rid ?>" class="text-decoration-none" style="color: #f0a23b;">
                                    <?= Response::e(UiHelper::formatReceiptCode($rid)) ?>
                                </a>
                            </td>
                            <td class="num"><?= Response::e(date('d/m/Y', strtotime((string) $r['import_date']))) ?></td>
                            <td>
                                <div class="fw-semibold"><?= Response::e($r['supplier_name'] ?? '—') ?></div>
                                <?php if (!empty($r['supplier_phone'])): ?>
                                    <div class="small text-muted num"><?= Response::e($r['supplier_phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= Response::e($r['creator_name'] ?? '—') ?></div>
                                <?php if (!empty($r['creator_username'])): ?>
                                    <small class="code text-muted">@<?= Response::e($r['creator_username']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end num">
                                <?= (int) $r['line_count'] ?> mặt hàng (<strong class="text-light">+<?= (int) $r['total_qty'] ?></strong>)
                            </td>
                            <td class="text-end price"><?= Response::money($r['total_amount']) ?></td>
                            <td class="small text-muted" style="max-width: 240px;">
                                <?= Response::e($r['note'] ?: '—') ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="index.php?r=admin/import-receipts/detail&id=<?= $rid ?>" class="btn btn-outline-secondary btn-sm" title="Xem chi tiết phiếu nhập">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                        <span>Chi tiết</span>
                                    </a>
                                    <a href="index.php?r=admin/import-receipts/print&id=<?= $rid ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="In phiếu nhập A4" aria-label="In phiếu nhập <?= Response::e(UiHelper::formatReceiptCode($rid)) ?>">
                                        <i class="bi bi-printer" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <div class="p-3 border-top border-secondary d-flex justify-content-end">
            <nav aria-label="Phân trang phiếu nhập">
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $baseParams = $_GET;
                    unset($baseParams['page']);
                    ?>
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <?php $qs = http_build_query(array_merge($baseParams, ['page' => $i])); ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link num" href="index.php?<?= Response::e($qs) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
