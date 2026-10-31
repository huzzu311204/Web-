<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\UiHelper;

$items = $result['items'] ?? [];
$total = (int) ($result['total'] ?? 0);
$page  = (int) ($result['page'] ?? 1);
$pages = (int) ($result['pages'] ?? 1);
$currentType = strtoupper(trim((string) ($filters['type'] ?? '')));
?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h2 mb-1">Lịch sử biến động Tồn kho</h1>
        <p class="text-muted small mb-0">
            Tổng cộng <strong class="num text-light"><?= $total ?></strong> dòng nhật ký. Mọi thay đổi số lượng tồn kho đều được ghi nhận tự động trong 1 PDO Transaction.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?r=admin/import-receipts/create" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
            <span>Lập phiếu nhập kho</span>
        </a>
        <a href="index.php?r=admin/products" class="btn btn-primary btn-sm">
            <i class="bi bi-sliders" aria-hidden="true"></i>
            <span>Xuất / Điều chỉnh tại bảng SP</span>
        </a>
    </div>
</div>

<!-- Bộ lọc lịch sử tồn kho -->
<div class="card p-3 mb-4">
    <form action="index.php" method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="admin/stock-logs">

        <div class="col-md-4">
            <label for="product_id" class="form-label">Sản phẩm</label>
            <select id="product_id" name="product_id" class="form-select">
                <option value="">Tất cả thiết bị</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (int) ($filters['product_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
                        [<?= Response::e($p['code']) ?>] <?= Response::e($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-sm-4 col-md-2">
            <label for="type" class="form-label">Loại giao dịch</label>
            <select id="type" name="type" class="form-select">
                <option value="">Tất cả loại</option>
                <option value="IMPORT" <?= $currentType === 'IMPORT' ? 'selected' : '' ?>>Nhập kho (IMPORT)</option>
                <option value="EXPORT" <?= $currentType === 'EXPORT' ? 'selected' : '' ?>>Xuất kho (EXPORT)</option>
                <option value="ADJUSTMENT" <?= $currentType === 'ADJUSTMENT' ? 'selected' : '' ?>>Điều chỉnh (ADJUSTMENT)</option>
            </select>
        </div>

        <div class="col-sm-4 col-md-2">
            <label for="date_from" class="form-label">Từ ngày</label>
            <input type="date" id="date_from" name="date_from" class="form-control num" value="<?= Response::e($filters['date_from'] ?? '') ?>">
        </div>

        <div class="col-sm-4 col-md-2">
            <label for="date_to" class="form-label">Đến ngày</label>
            <input type="date" id="date_to" name="date_to" class="form-control num" value="<?= Response::e($filters['date_to'] ?? '') ?>">
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-outline-secondary w-100">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <span>Lọc</span>
            </button>
            <a href="index.php?r=admin/stock-logs" class="btn btn-outline-secondary btn-icon" title="Đặt lại bộ lọc" aria-label="Đặt lại bộ lọc">
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
            </a>
        </div>
    </form>
</div>

<!-- Bảng lịch sử tồn kho -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Thời điểm</th>
                    <th>Thiết bị</th>
                    <th>Loại giao dịch</th>
                    <th class="text-end">Tồn trước</th>
                    <th class="text-end">Thay đổi</th>
                    <th class="text-end">Tồn sau</th>
                    <th>Lý do / Chứng từ</th>
                    <th>Người thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            Không có bản ghi biến động tồn kho nào khớp với bộ lọc.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $log): ?>
                        <?php
                        $change = (int) $log['change_qty'];
                        $changeColor = $change > 0 ? '#34d399' : ($change < 0 ? '#f87171' : '#9ca3af');
                        $changeSign  = $change > 0 ? '+' . $change : (string) $change;
                        $receiptId   = !empty($log['receipt_id']) ? (int) $log['receipt_id'] : null;
                        ?>
                        <tr>
                            <td class="num small text-muted">
                                <?= Response::e(date('d/m/Y H:i:s', strtotime((string) $log['created_at']))) ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= Response::e($log['product_name'] ?? '—') ?></div>
                                <div class="code small text-muted"><?= Response::e($log['product_code'] ?? '') ?></div>
                            </td>
                            <td>
                                <?= UiHelper::stockLogTypeBadge((string) $log['type']) ?>
                            </td>
                            <td class="text-end num text-muted"><?= (int) $log['quantity_before'] ?></td>
                            <td class="text-end num fw-bold" style="color: <?= $changeColor ?>;">
                                <?= Response::e($changeSign) ?>
                            </td>
                            <td class="text-end num fw-semibold text-light"><?= (int) $log['quantity_after'] ?></td>
                            <td>
                                <div><?= Response::e($log['reason'] ?? '—') ?></div>
                                <?php if ($receiptId !== null): ?>
                                    <a href="index.php?r=admin/import-receipts/detail&id=<?= $receiptId ?>" class="code small text-decoration-none" style="color: #f0a23b;">
                                        <i class="bi bi-link-45deg" aria-hidden="true"></i><?= Response::e(UiHelper::formatReceiptCode($receiptId)) ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <div><?= Response::e($log['user_full_name'] ?? '—') ?></div>
                                <?php if (!empty($log['username'])): ?>
                                    <span class="code text-muted">@<?= Response::e($log['username']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <div class="p-3 border-top border-secondary d-flex justify-content-end">
            <nav aria-label="Phân trang lịch sử tồn kho">
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
