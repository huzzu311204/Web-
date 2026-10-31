<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Product;
use App\Models\StockLog;
use App\Services\StockService;
use DomainException;

class StockController
{
    /**
     * GET admin/stock-logs — Lịch sử biến động tồn kho (Mục 6.3)
     */
    public function index(): void
    {
        $logModel     = new StockLog();
        $productModel = new Product();

        $result = $logModel->paginate($_GET, 15);

        Response::view('admin/stock/index', [
            'pageTitle' => 'Lịch sử biến động Tồn kho — ProGear Studio',
            'result'    => $result,
            'products'  => $productModel->getAllSimple(),
            'filters'   => $_GET,
        ], 'admin');
    }

    /**
     * POST admin/stock/adjust — Form POST dự phòng khi không chạy qua AJAX (Mục 6.1)
     */
    public function adjust(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $rawType   = strtoupper(trim((string) ($_POST['type'] ?? 'EXPORT')));
        $qtyRaw    = $_POST['quantity'] ?? '';
        $reason    = trim((string) ($_POST['reason'] ?? ''));

        if (!Validator::isPositiveInt($qtyRaw) || (int) $qtyRaw > 100000) {
            Response::setFlash('error', 'Số lượng thực hiện phải là số nguyên dương (≥ 1).');
            Response::redirect('index.php?r=admin/products');
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
        } else {
            Response::setFlash('error', 'Loại nghiệp vụ kho không hợp lệ.');
            Response::redirect('index.php?r=admin/products');
        }

        try {
            $stockService = new StockService();
            $res = $stockService->adjust($productId, $delta, $type, $reason, (int) Auth::id());
            Response::setFlash(
                'success',
                'Đã cập nhật tồn kho cho "' . $res['name'] . '" (' . $res['code'] . '): ' . $res['quantity_before'] . ' → ' . $res['quantity_after']
            );
        } catch (DomainException $e) {
            Response::setFlash('error', $e->getMessage());
        }

        Response::redirect('index.php?r=admin/products');
    }
}
