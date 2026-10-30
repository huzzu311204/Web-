<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\UiHelper;
?>
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h2 mb-1">Tổng quan vận hành</h1>
        <p class="text-muted small mb-0">Số liệu thời gian thực từ cơ sở dữ liệu & danh sách đơn cần xử lý ngay.</p>
    </div>
    <div>
        <a href="index.php?r=admin/rentals" class="btn btn-primary">
            <i class="bi bi-calendar2-check" aria-hidden="true"></i>
            <span>Quản lý tất cả đơn thuê</span>
        </a>
    </div>
</div>

<!-- 5 Thẻ số liệu (Mục 5.2 & 4.2 - Không dùng biểu đồ) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <a href="index.php?r=admin/rentals&filter=PENDING" class="card p-3 text-decoration-none h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between text-muted small mb-2">
                <span>Đơn chờ duyệt</span>
                <i class="bi bi-hourglass-split fs-5" style="color: #aab2bd;" aria-hidden="true"></i>
            </div>
            <div class="num fs-2 fw-bold text-light"><?= (int) ($stats['pending_rentals'] ?? 0) ?></div>
            <span class="small text-muted mt-1">Cần kiểm tra lịch & duyệt</span>
        </a>
    </div>

    <div class="col-6 col-xl">
        <a href="index.php?r=admin/rentals&filter=EXT_PENDING" class="card p-3 text-decoration-none h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between text-muted small mb-2">
                <span>Xin gia hạn chờ duyệt</span>
                <i class="bi bi-calendar-plus fs-5" style="color: #5eead4;" aria-hidden="true"></i>
            </div>
            <div class="num fs-2 fw-bold" style="color: #5eead4;"><?= (int) ($stats['pending_extensions'] ?? 0) ?></div>
            <span class="small text-muted mt-1">Yêu cầu thêm ngày thuê</span>
        </a>
    </div>

    <div class="col-6 col-xl">
        <a href="index.php?r=admin/rentals&filter=RENTING" class="card p-3 text-decoration-none h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between text-muted small mb-2">
                <span>Đơn đang thuê</span>
                <i class="bi bi-camera fs-5" style="color: #7cc4e8;" aria-hidden="true"></i>
            </div>
            <div class="num fs-2 fw-bold" style="color: #7cc4e8;"><?= (int) ($stats['renting_count'] ?? 0) ?></div>
            <span class="small text-muted mt-1">Thiết bị đang ở chỗ khách</span>
        </a>
    </div>

    <div class="col-6 col-xl">
        <a href="index.php?r=admin/rentals&filter=DUE_SOON" class="card p-3 text-decoration-none h-100 d-flex flex-column justify-content-between" style="border-left: 3px solid #f0a23b;">
            <div class="d-flex align-items-center justify-content-between text-muted small mb-2">
                <span>Sắp đến hạn (≤ 2 ngày)</span>
                <i class="bi bi-clock fs-5" style="color: #f0a23b;" aria-hidden="true"></i>
            </div>
            <div class="num fs-2 fw-bold" style="color: #f0a23b;"><?= (int) ($stats['due_soon_count'] ?? 0) ?></div>
            <span class="small text-muted mt-1">Chuẩn bị nhận trả thiết bị</span>
        </a>
    </div>

    <div class="col-12 col-xl">
        <a href="index.php?r=admin/rentals&filter=OVERDUE" class="card p-3 text-decoration-none h-100 d-flex flex-column justify-content-between" style="border-left: 3px solid #f87171;">
            <div class="d-flex align-items-center justify-content-between text-muted small mb-2">
                <span>Đơn quá hạn trả</span>
                <i class="bi bi-exclamation-triangle fs-5" style="color: #f87171;" aria-hidden="true"></i>
            </div>
            <div class="num fs-2 fw-bold" style="color: #f87171;"><?= (int) ($stats['overdue_count'] ?? 0) ?></div>
            <span class="small text-muted mt-1">Đang giữ thiết bị & tính phí trễ</span>
        </a>
    </div>
</div>

<!-- Bảng Đơn cần chú ý (Mục 5.2 & 6.6) -->
<div class="card">
    <div class="p-3 border-bottom border-secondary d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
        <div>
            <h2 class="h5 mb-0">Đơn thuê cần chú ý</h2>
            <small class="text-muted">Bao gồm đơn quá hạn (viền đỏ), sắp đến hạn trả trong 2 ngày tới (viền hổ phách) và đơn mới chờ duyệt.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?r=admin/rentals&filter=OVERDUE" class="btn btn-outline-secondary btn-sm">Lọc quá hạn</a>
            <a href="index.php?r=admin/rentals&filter=DUE_SOON" class="btn btn-outline-secondary btn-sm">Lọc sắp đến hạn</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Số điện thoại</th>
                    <th>Ngày thuê</th>
                    <th>Hạn trả</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Tổng thuê</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attentionRentals)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <p class="text-muted mb-2">Hiện không có đơn quá hạn, sắp đến hạn hoặc chờ duyệt nào.</p>
                            <a href="index.php?r=admin/rentals" class="btn btn-outline-secondary btn-sm">Xem toàn bộ danh sách đơn</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attentionRentals as $r): ?>
                        <?php $rowClass = UiHelper::rentalRowClass((string) $r['status'], (string) $r['end_date']); ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <a href="index.php?r=admin/rentals/detail&id=<?= (int) $r['id'] ?>" class="code fw-semibold text-decoration-none">
                                    <?= Response::e($r['code']) ?>
                                </a>
                            </td>
                            <td class="fw-medium"><?= Response::e($r['customer_name']) ?></td>
                            <td class="num"><?= Response::e($r['phone']) ?></td>
                            <td class="num"><?= Response::e($r['start_date']) ?></td>
                            <td class="num"><?= Response::e($r['end_date']) ?></td>
                            <td>
                                <?= UiHelper::statusBadge((string) $r['status']) ?>
                                <?= UiHelper::dueWarningBadge((string) $r['status'], (string) $r['end_date']) ?>
                                <?php if ((int) ($r['has_pending_ext'] ?? 0) > 0): ?>
                                    <span class="badge-status status-approved ms-1" title="Có yêu cầu gia hạn chờ duyệt">
                                        <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                                        <span>Xin gia hạn</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end price"><?= Response::money($r['rental_total']) ?></td>
                            <td class="text-end">
                                <a href="index.php?r=admin/rentals/detail&id=<?= (int) $r['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <span>Chi tiết</span>
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

