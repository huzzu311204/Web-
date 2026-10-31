<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;
use App\Core\UiHelper;
use App\Core\Validator;
use App\Models\Product;
use App\Services\ImageUploader;
use App\Services\ProductQuery;
use PDOException;

class AdminProductApi
{
    /**
     * GET api/admin/products — Lọc, tìm kiếm, sắp xếp, phân trang danh sách sản phẩm cho Admin bằng AJAX (Mục 7.1)
     */
    public function index(): void
    {
        $queryService = new ProductQuery();
        $result = $queryService->paginate($_GET, false, 10);

        ob_start();
        $items = $result['items'];
        require dirname(__DIR__, 2) . '/views/admin/products/_table_rows.php';
        $rowsHtml = ob_get_clean();

        ob_start();
        $page = $result['page'];
        $pages = $result['pages'];
        require dirname(__DIR__, 2) . '/views/admin/products/_pagination.php';
        $paginationHtml = ob_get_clean();

        Response::json([
            'ok'             => true,
            'rows_html'      => $rowsHtml,
            'pagination_html'=> $paginationHtml,
            'total'          => $result['total'],
            'page'           => $result['page'],
            'pages'          => $result['pages'],
        ]);
    }

    /**
     * GET api/admin/sku-check — Kiểm tra mã SKU đã tồn tại chưa ngay khi Admin gõ (Mục 5.2 - A2 & TC02)
     */
    public function skuCheck(): void
    {
        $code = strtoupper(trim((string) ($_GET['code'] ?? '')));
        $excludeId = isset($_GET['exclude_id']) && $_GET['exclude_id'] !== '' ? (int) $_GET['exclude_id'] : null;

        if ($code === '') {
            Response::json(['ok' => true, 'exists' => false, 'valid' => false, 'message' => '']);
        }

        if (!Validator::isValidLength($code, 50, 2) || !preg_match('/^[A-Z0-9_-]+$/', $code)) {
            Response::json([
                'ok'      => true,
                'exists'  => false,
                'valid'   => false,
                'message' => 'Mã SKU từ 2–50 ký tự (chữ in hoa, số, gạch ngang/dưới).',
            ]);
        }

        $productModel = new Product();
        $exists = $productModel->codeExists($code, $excludeId);

        Response::json([
            'ok'      => true,
            'exists'  => $exists,
            'valid'   => !$exists,
            'message' => $exists ? 'Mã SKU "' . $code . '" đã tồn tại trong hệ thống.' : 'Mã SKU hợp lệ, có thể sử dụng.',
        ]);
    }

    /**
     * POST api/admin/product-inline — Sửa nhanh giá bán, ngưỡng sắp hết, trạng thái ngay trên bảng (Mục 5.2 - A7 & TC21, TC22)
     */
    public function inline(): void
    {
        // Chặn tuyệt đối nếu trình duyệt hoặc attacker cố gửi trường quantity lên endpoint sửa nhanh (TC22)
        if (array_key_exists('quantity', $_POST)) {
            Response::json([
                'ok'      => false,
                'message' => 'Không được phép sửa trực tiếp số lượng tồn kho tại bảng. Vui lòng dùng chức năng Nhập/Xuất/Điều chỉnh kho.',
            ], 422);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $productModel = new Product();
        $product = $productModel->findById($id);

        if (!$product) {
            Response::json(['ok' => false, 'message' => 'Không tìm thấy sản phẩm cần cập nhật.'], 404);
        }

        $priceRaw    = $_POST['price'] ?? $product['price'];
        $minStockRaw = $_POST['min_stock'] ?? $product['min_stock'];
        $status      = strtoupper(trim((string) ($_POST['status'] ?? $product['status'])));

        if (!Validator::isNonNegativeInt($priceRaw) || (int) $priceRaw > 999999999999) {
            Response::json([
                'ok'      => false,
                'message' => 'Giá bán phải là số nguyên không âm hợp lệ.',
                'current' => [
                    'price'     => (int) $product['price'],
                    'min_stock' => (int) $product['min_stock'],
                    'status'    => (string) $product['status'],
                ],
            ], 422);
        }

        if (!Validator::isNonNegativeInt($minStockRaw) || (int) $minStockRaw > 100000) {
            Response::json([
                'ok'      => false,
                'message' => 'Ngưỡng sắp hết phải là số nguyên không âm.',
                'current' => [
                    'price'     => (int) $product['price'],
                    'min_stock' => (int) $product['min_stock'],
                    'status'    => (string) $product['status'],
                ],
            ], 422);
        }

        if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            Response::json(['ok' => false, 'message' => 'Trạng thái sản phẩm không hợp lệ.'], 422);
        }

        $price    = (int) $priceRaw;
        $minStock = (int) $minStockRaw;

        $productModel->updateInline($id, $price, $minStock, $status);

        $qty = (int) $product['quantity'];
        $stockBadgeHtml = UiHelper::stockBadge($qty, $minStock, $status);
        $rowClass = UiHelper::productRowClass($qty, $minStock, $status);

        Response::json([
            'ok'               => true,
            'message'          => 'Đã lưu thay đổi nhanh cho "' . $product['code'] . '".',
            'price'            => $price,
            'price_formatted'  => Response::money($price),
            'min_stock'        => $minStock,
            'status'           => $status,
            'stock_badge_html' => $stockBadgeHtml,
            'row_class'        => $rowClass,
        ]);
    }

    /**
     * POST api/admin/product-action — Xóa sản phẩm (xóa cứng hoặc chuyển INACTIVE) hoặc đổi trạng thái ẩn/hiện (Mục 4.3, TC05, TC06)
     */
    public function action(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $action = trim((string) ($_POST['action'] ?? ''));

        $productModel = new Product();
        $product = $productModel->findById($id);

        if (!$product) {
            Response::json(['ok' => false, 'message' => 'Không tìm thấy sản phẩm.'], 404);
        }

        if ($action === 'toggle_status') {
            $newStatus = $product['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
            $productModel->setStatus($id, $newStatus);
            $qty = (int) $product['quantity'];
            $min = (int) $product['min_stock'];

            Response::json([
                'ok'               => true,
                'action_done'      => 'status_changed',
                'status'           => $newStatus,
                'stock_badge_html' => UiHelper::stockBadge($qty, $min, $newStatus),
                'row_class'        => UiHelper::productRowClass($qty, $min, $newStatus),
                'message'          => $newStatus === 'ACTIVE'
                    ? 'Đã kích hoạt bán lại sản phẩm "' . $product['name'] . '".'
                    : 'Đã chuyển sản phẩm "' . $product['name'] . '" sang Ngừng kinh doanh.',
            ]);
        }

        if ($action === 'delete') {
            // Nếu đã có trong import_receipt_details hoặc stock_logs -> Không xóa cứng, chuyển INACTIVE (TC05)
            if ($productModel->hasInventoryHistory($id)) {
                $productModel->setStatus($id, 'INACTIVE');
                $qty = (int) $product['quantity'];
                $min = (int) $product['min_stock'];

                Response::json([
                    'ok'               => true,
                    'action_done'      => 'soft_deleted',
                    'status'           => 'INACTIVE',
                    'stock_badge_html' => UiHelper::stockBadge($qty, $min, 'INACTIVE'),
                    'row_class'        => '',
                    'message'          => 'Sản phẩm "' . $product['name'] . '" đã có lịch sử kho nên được chuyển sang trạng thái Ngừng kinh doanh (INACTIVE) để bảo toàn dữ liệu.',
                ]);
            }

            // Chưa phát sinh dữ liệu -> Xóa hẳn và dọn ảnh upload (TC06); chỉ bắt mã lỗi khóa ngoại 23000
            try {
                $productModel->hardDelete($id);
                ImageUploader::deleteOldImage($product['image'] ?? null);

                Response::json([
                    'ok'          => true,
                    'action_done' => 'hard_deleted',
                    'message'     => 'Đã xóa vĩnh viễn sản phẩm "' . $product['name'] . '".',
                ]);
            } catch (PDOException $e) {
                if ((string) $e->getCode() !== '23000') {
                    throw $e;
                }
                $productModel->setStatus($id, 'INACTIVE');
                $qty = (int) $product['quantity'];
                $min = (int) $product['min_stock'];

                Response::json([
                    'ok'               => true,
                    'action_done'      => 'soft_deleted',
                    'status'           => 'INACTIVE',
                    'stock_badge_html' => UiHelper::stockBadge($qty, $min, 'INACTIVE'),
                    'row_class'        => '',
                    'message'          => 'Sản phẩm "' . $product['name'] . '" đã phát sinh dữ liệu liên quan nên được chuyển sang Ngừng kinh doanh (INACTIVE).',
                ]);
            }
        }

        Response::json(['ok' => false, 'message' => 'Hành động không hợp lệ.'], 400);
    }
}

