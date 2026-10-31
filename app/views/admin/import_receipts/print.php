<?php
declare(strict_types=1);

use App\Core\Response;
use App\Core\UiHelper;

$rid = (int) $receipt['id'];
$code = UiHelper::formatReceiptCode($rid);
$details = $receipt['details'] ?? [];
?>
<div class="container py-3 no-print d-flex justify-content-between align-items-center" style="max-width: 210mm;">
    <a href="index.php?r=admin/import-receipts/detail&id=<?= $rid ?>" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Quay lại chi tiết phiếu
    </a>
    <button type="button" onclick="window.print()" class="btn btn-dark btn-sm">
        <i class="bi bi-printer" aria-hidden="true"></i> In phiếu (Ctrl + P)
    </button>
</div>

<div class="print-sheet">
    <!-- Đầu trang bản in -->
    <div class="d-flex justify-content-between align-items-start border-bottom border-dark pb-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">PROGEAR STUDIO</h1>
            <p class="small mb-0">184 Nam Kỳ Khởi Nghĩa, Phường Võ Thị Sáu, Quận 3, TP.HCM</p>
            <p class="small mb-0">Hotline: <span class="num">0901 234 567</span> &middot; Email: <span class="code">contact@progear.vn</span></p>
        </div>
        <div class="text-end">
            <h2 class="h4 fw-bold text-uppercase mb-1">PHIẾU NHẬP KHO</h2>
            <div class="code fw-bold">Mã phiếu: <?= Response::e($code) ?></div>
            <div class="small num">Ngày nhập: <?= Response::e(date('d/m/Y', strtotime((string) $receipt['import_date']))) ?></div>
        </div>
    </div>

    <!-- Thông tin Nhà cung cấp & Chứng từ -->
    <div class="row g-3 mb-4">
        <div class="col-7">
            <p class="mb-1"><strong>Đơn vị giao hàng (Nhà cung cấp):</strong> <?= Response::e($receipt['supplier_name'] ?? '—') ?></p>
            <p class="mb-1"><strong>Số điện thoại:</strong> <span class="num"><?= Response::e($receipt['supplier_phone'] ?? '—') ?></span></p>
            <p class="mb-0"><strong>Địa chỉ:</strong> <?= Response::e($receipt['supplier_address'] ?? '—') ?></p>
        </div>
        <div class="col-5">
            <p class="mb-1"><strong>Người lập phiếu:</strong> <?= Response::e($receipt['creator_name'] ?? '—') ?></p>
            <p class="mb-1"><strong>Thời điểm lập:</strong> <span class="num"><?= Response::e(date('d/m/Y H:i', strtotime((string) $receipt['created_at']))) ?></span></p>
            <p class="mb-0"><strong>Ghi chú:</strong> <?= Response::e($receipt['note'] ?: 'Không có') ?></p>
        </div>
    </div>

    <!-- Bảng chi tiết thiết bị nhập -->
    <table class="table table-bordered mb-4">
        <thead>
            <tr>
                <th style="width: 48px;" class="text-center">STT</th>
                <th style="width: 140px;">Mã SKU</th>
                <th>Tên thiết bị nhiếp ảnh</th>
                <th style="width: 90px;" class="text-end">Số lượng</th>
                <th style="width: 140px;" class="text-end">Đơn giá nhập</th>
                <th style="width: 150px;" class="text-end">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalQty = 0;
            foreach ($details as $idx => $d):
                $totalQty += (int) $d['quantity'];
            ?>
                <tr>
                    <td class="text-center num"><?= $idx + 1 ?></td>
                    <td class="code"><?= Response::e($d['product_code'] ?? '') ?></td>
                    <td><?= Response::e($d['product_name'] ?? '') ?></td>
                    <td class="text-end num"><?= (int) $d['quantity'] ?></td>
                    <td class="text-end num"><?= Response::money($d['import_price']) ?></td>
                    <td class="text-end price fw-semibold"><?= Response::money($d['subtotal']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">TỔNG CỘNG:</th>
                <th class="text-end num"><?= $totalQty ?></th>
                <th></th>
                <th class="text-end price fw-bold"><?= Response::money($receipt['total_amount']) ?></th>
            </tr>
        </tfoot>
    </table>

    <!-- Chữ ký xác nhận -->
    <div class="row text-center mt-5 pt-2">
        <div class="col-4">
            <strong>Người giao hàng</strong>
            <p class="small fst-italic">(Ký và ghi rõ họ tên)</p>
        </div>
        <div class="col-4">
            <strong>Thủ kho kiểm nhận</strong>
            <p class="small fst-italic">(Ký và ghi rõ họ tên)</p>
        </div>
        <div class="col-4">
            <strong>Người lập phiếu</strong>
            <p class="small fst-italic">(Ký và ghi rõ họ tên)</p>
            <div class="mt-5 fw-semibold"><?= Response::e($receipt['creator_name'] ?? '') ?></div>
        </div>
    </div>
</div>
