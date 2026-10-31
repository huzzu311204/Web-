<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Response;
use App\Core\UiHelper;
use App\Models\ImportReceipt;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\StockService;
use DomainException;

class ImportReceiptController
{
    public function index(): void
    {
        $receiptModel = new ImportReceipt();
        $supplierModel = new Supplier();

        $result = $receiptModel->paginate($_GET, 10);

        Response::view('admin/import_receipts/index', [
            'pageTitle' => 'Quản lý Phiếu nhập kho — ProGear Studio',
            'result'    => $result,
            'suppliers' => $supplierModel->getAllWithCounts(),
            'filters'   => $_GET,
        ], 'admin');
    }

    public function create(): void
    {
        $supplierModel = new Supplier();
        $productModel  = new Product();

        $errors = [];
        $old = [
            'supplier_id' => '',
            'import_date' => date('Y-m-d'),
            'note'        => '',
        ];
        $lines = [
            ['product_id' => '', 'quantity' => '1', 'import_price' => '0'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $supplierId = (int) ($_POST['supplier_id'] ?? 0);
            $importDate = trim((string) ($_POST['import_date'] ?? date('Y-m-d')));
            $note       = trim((string) ($_POST['note'] ?? ''));

            $old['supplier_id'] = $supplierId ?: '';
            $old['import_date'] = $importDate;
            $old['note']        = $note;

            $prodIds   = $_POST['product_id'] ?? [];
            $qtys      = $_POST['quantity'] ?? [];
            $prices    = $_POST['import_price'] ?? [];

            $lines = [];
            if (is_array($prodIds)) {
                foreach ($prodIds as $i => $pidRaw) {
                    $pid = trim((string) $pidRaw);
                    $qty = trim((string) ($qtys[$i] ?? '1'));
                    $prc = trim((string) ($prices[$i] ?? '0'));
                    if ($pid !== '' || $qty !== '' || $prc !== '') {
                        $lines[] = [
                            'product_id'   => $pid,
                            'quantity'     => $qty,
                            'import_price' => $prc,
                        ];
                    }
                }
            }

            if (empty($lines)) {
                $lines = [['product_id' => '', 'quantity' => '1', 'import_price' => '0']];
            }

            try {
                $stockService = new StockService();
                $receiptId = $stockService->createReceipt(
                    $supplierId,
                    $importDate,
                    $lines,
                    $note,
                    (int) Auth::id()
                );

                Response::setFlash('success', 'Đã lập phiếu nhập kho ' . UiHelper::formatReceiptCode($receiptId) . ' và cập nhật tồn kho thành công.');
                Response::redirect('index.php?r=admin/import-receipts/detail&id=' . $receiptId);
            } catch (DomainException $e) {
                $errors['general'] = $e->getMessage();
            }
        }

        Response::view('admin/import_receipts/form', [
            'pageTitle'   => 'Lập Phiếu nhập kho mới — ProGear Studio',
            'suppliers'   => $supplierModel->getAllWithCounts(),
            'products'    => $productModel->getAllSimple(),
            'old'         => $old,
            'lines'       => $lines,
            'errors'      => $errors,
            'pageScripts' => ['js/admin-stock.js'],
        ], 'admin');
    }

    public function detail(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $receiptModel = new ImportReceipt();
        $receipt = $receiptModel->findWithDetails($id);

        if (!$receipt) {
            Response::setFlash('error', 'Không tìm thấy phiếu nhập kho.');
            Response::redirect('index.php?r=admin/import-receipts');
        }

        Response::view('admin/import_receipts/detail', [
            'pageTitle' => 'Chi tiết Phiếu nhập ' . UiHelper::formatReceiptCode($id) . ' — ProGear Studio',
            'receipt'   => $receipt,
        ], 'admin');
    }

    public function printReceipt(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $receiptModel = new ImportReceipt();
        $receipt = $receiptModel->findWithDetails($id);

        if (!$receipt) {
            Response::setFlash('error', 'Không tìm thấy phiếu nhập kho cần in.');
            Response::redirect('index.php?r=admin/import-receipts');
        }

        Response::view('admin/import_receipts/print', [
            'pageTitle' => 'In Phiếu nhập ' . UiHelper::formatReceiptCode($id) . ' — ProGear Studio',
            'receipt'   => $receipt,
        ], 'print');
    }
}
