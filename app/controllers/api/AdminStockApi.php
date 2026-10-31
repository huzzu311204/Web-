<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Core\UiHelper;
use App\Core\Validator;
use App\Services\StockService;
use DomainException;

class AdminStockApi
{
    /**
     * POST api/admin/stock-adjust — Xuất kho hoặc Điều chỉnh kiểm kê bằng AJAX (Mục 6.2 & TC17, TC18, TC19)
     */
    public function adjust(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $rawType   = strtoupper(trim((string) ($_POST['type'] ?? 'EXPORT')));
        $qtyRaw    = $_POST['quantity'] ?? '';
        $reason    = trim((string) ($_POST['reason'] ?? ''));

        if ($productId <= 0) {
            Response::json(['ok' => false, 'message' => 'Sản phẩm không hợp lệ.'], 422);
        }

        if (!Validator::isPositiveInt($qtyRaw) || (int) $qtyRaw > 100000) {
            Response::json(['ok' => false, 'message' => 'Số lượng thực hiện phải là số nguyên dương (≥ 1).'], 422);
        }

        $qty = (int) $qtyRaw;
        if ($rawType === 'EXPORT') {
            $type  = 'EXPORT';
            $delta = -$qty;
        } elseif ($rawType === 'ADJUSTMENT_ADD') {
            $type  = 'ADJUSTMENT';
            $delta = $qty;
        } elseif ($rawType === 'ADJUSTMENT_SUB') {
            $type  = 'ADJUSTMENT';
            $delta = -$qty;
        } elseif ($rawType === 'ADJUSTMENT') {
            $type  = 'ADJUSTMENT';
            $delta = (int) ($_POST['delta'] ?? $qty);
        } else {
            Response::json(['ok' => false, 'message' => 'Loại nghiệp vụ kho không hợp lệ.'], 422);
        }

        try {
            $stockService = new StockService();
            $result = $stockService->adjust(
                $productId,
                $delta,
                $type,
                $reason,
                (int) Auth::id()
            );

            $qtyAfter = (int) $result['quantity_after'];
            $minStock = (int) $result['min_stock'];
            $status   = (string) $result['status'];

            Response::json([
                'ok'               => true,
                'message'          => 'Đã cập nhật tồn kho "' . $result['name'] . '" (' . $result['code'] . '): ' . $result['quantity_before'] . ' → ' . $qtyAfter,
                'product_id'       => $productId,
                'quantity_before'  => $result['quantity_before'],
                'quantity_after'   => $qtyAfter,
                'change_qty'       => $result['change_qty'],
                'stock_badge_html' => UiHelper::stockBadge($qtyAfter, $minStock, $status),
                'row_class'        => UiHelper::productRowClass($qtyAfter, $minStock, $status),
                'low_or_out_count' => $result['low_or_out_count'],
            ]);
        } catch (DomainException $e) {
            Response::json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
