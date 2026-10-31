<?php
declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Services\ProductQuery;

class CatalogController
{
    private const MAX_RECENT_VIEWED = 8;

    /**
     * GET index.php?r=catalog — Trang danh mục thiết bị, lọc đa tiêu chí, sắp xếp & phân trang (Mục 7 - GĐ3 & TC07, TC08, TC09)
     */
    public function index(): void
    {
        $queryService  = new ProductQuery();
        $categoryModel = new Category();
        $brandModel    = new Brand();

        $result     = $queryService->paginate($_GET, true, 9);
        $categories = $categoryModel->getTreeOptions();
        $brands     = $brandModel->getAllWithCounts();

        $selectedBrands = $this->parseSelectedBrands($_GET);
        $activeChips    = $this->buildActiveFilterChips($_GET, $categories, $brands, $selectedBrands);
        $recentProducts = $this->getRecentViewedProducts();

        Response::view('storefront/catalog/index', [
            'pageTitle'      => 'Danh mục Thiết bị Nhiếp ảnh & Quay phim — ProGear Studio',
            'result'         => $result,
            'categories'     => $categories,
            'brands'         => $brands,
            'filters'        => $_GET,
            'selectedBrands' => $selectedBrands,
            'activeChips'    => $activeChips,
            'recentProducts' => $recentProducts,
            'pageScripts'    => ['js/catalog.js'],
        ], 'front');
    }

    /**
     * GET index.php?r=product&id=... — Chi tiết thiết bị, bảng thông số, gợi ý cùng Ngàm & lưu Đã xem gần đây (Mục 7 - GĐ3 & TC13)
     */
    public function detail(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $productModel = new Product();
        $product = $productModel->findById($id);

        // Khách chỉ xem sản phẩm ACTIVE (Mục 7 - GĐ3)
        if (!$product || ($product['status'] ?? '') !== 'ACTIVE') {
            http_response_code(404);
            Response::view('storefront/404', [
                'pageTitle' => 'Không tìm thấy thiết bị — ProGear Studio',
            ], 'front');
            return;
        }

        // Cập nhật danh sách "Đã xem gần đây" trong $_SESSION['recent_viewed'] (tối đa 8, mới nhất trước, bỏ trùng - TC13)
        self::pushRecentViewed($id);

        $specModel = new ProductSpec();
        $specs = $specModel->getByProductId($id);

        $relatedProducts = $productModel->getRelatedProducts($product, 4);
        if (!empty($relatedProducts)) {
            $relIds = array_map(static fn(array $r): int => (int) $r['id'], $relatedProducts);
            $relSpecsMap = $specModel->getByProductIds($relIds);
            foreach ($relatedProducts as &$rp) {
                $rp['specs'] = $relSpecsMap[(int) $rp['id']] ?? [];
            }
            unset($rp);
        }

        $recentProducts = $this->getRecentViewedProducts($id);

        Response::view('storefront/catalog/detail', [
            'pageTitle'       => $product['name'] . ' (' . $product['code'] . ') — ProGear Studio',
            'product'         => $product,
            'specs'           => $specs,
            'relatedProducts' => $relatedProducts,
            'recentProducts'  => $recentProducts,
            'pageScripts'     => ['js/catalog.js'],
        ], 'front');
    }

    /**
     * POST index.php?r=recent/clear — Xóa lịch sử sản phẩm đã xem gần đây (Mục 7 - GĐ3)
     */
    public function clearRecent(): void
    {
        unset($_SESSION['recent_viewed']);
        Response::setFlash('success', 'Đã xóa lịch sử sản phẩm đã xem gần đây.');
        Response::redirect(Csrf::safeRedirectTarget('index.php?r=catalog'));
    }

    /**
     * Lưu ID sản phẩm vừa xem vào đầu mảng $_SESSION['recent_viewed'], bỏ trùng và giới hạn đúng 8 phần tử (TC13)
     */
    public static function pushRecentViewed(int $productId): array
    {
        if ($productId <= 0) {
            return $_SESSION['recent_viewed'] ?? [];
        }

        $recent = $_SESSION['recent_viewed'] ?? [];
        if (!is_array($recent)) {
            $recent = [];
        }

        // Bỏ trùng nếu sản phẩm đã có trong lịch sử rồi đưa lên đầu danh sách (mới nhất trước)
        $recent = array_values(array_filter(
            array_map('intval', $recent),
            static fn(int $existingId): bool => $existingId > 0 && $existingId !== $productId
        ));
        array_unshift($recent, $productId);

        if (count($recent) > self::MAX_RECENT_VIEWED) {
            $recent = array_slice($recent, 0, self::MAX_RECENT_VIEWED);
        }

        $_SESSION['recent_viewed'] = $recent;
        return $recent;
    }

    public function parseSelectedBrands(array $filters): array
    {
        $brandIds = [];
        if (!empty($filters['brands'])) {
            $raw = is_array($filters['brands'])
                ? $filters['brands']
                : explode(',', (string) $filters['brands']);
            foreach ($raw as $b) {
                $id = (int) $b;
                if ($id > 0) {
                    $brandIds[] = $id;
                }
            }
        } elseif (!empty($filters['brand_id'])) {
            $id = (int) $filters['brand_id'];
            if ($id > 0) {
                $brandIds[] = $id;
            }
        }
        return array_values(array_unique($brandIds));
    }

    /**
     * Dựng danh sách chip bộ lọc đang áp dụng để hiển thị phía trên lưới sản phẩm (TC07)
     */
    public function buildActiveFilterChips(array $filters, array $categories, array $brands, array $selectedBrands): array
    {
        $chips = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $chips[] = [
                'type'  => 'q',
                'value' => '',
                'label' => 'Từ khóa: "' . $q . '"',
            ];
        }

        $categoryId = (int) ($filters['category_id'] ?? $filters['category'] ?? 0);
        if ($categoryId > 0) {
            foreach ($categories as $cat) {
                if ((int) $cat['id'] === $categoryId) {
                    $chips[] = [
                        'type'  => 'category_id',
                        'value' => (string) $categoryId,
                        'label' => 'Danh mục: ' . $cat['name'],
                    ];
                    break;
                }
            }
        }

        if (!empty($selectedBrands)) {
            $brandMap = [];
            foreach ($brands as $b) {
                $brandMap[(int) $b['id']] = (string) $b['name'];
            }
            foreach ($selectedBrands as $bid) {
                if (isset($brandMap[$bid])) {
                    $chips[] = [
                        'type'  => 'brand',
                        'value' => (string) $bid,
                        'label' => 'Hãng: ' . $brandMap[$bid],
                    ];
                }
            }
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '' && is_numeric($filters['min_price'])) {
            $minP = max(0, (int) $filters['min_price']);
            $chips[] = [
                'type'  => 'min_price',
                'value' => '',
                'label' => 'Giá từ: ' . Response::money($minP),
            ];
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '' && is_numeric($filters['max_price'])) {
            $maxP = max(0, (int) $filters['max_price']);
            $chips[] = [
                'type'  => 'max_price',
                'value' => '',
                'label' => 'Giá đến: ' . Response::money($maxP),
            ];
        }

        $stock = strtolower(trim((string) ($filters['stock'] ?? '')));
        $stockLabels = [
            'in_stock'     => 'Tồn kho: Đang còn hàng',
            'low_stock'    => 'Tồn kho: Sắp hết hàng',
            'out_of_stock' => 'Tồn kho: Hết hàng',
        ];
        if (isset($stockLabels[$stock])) {
            $chips[] = [
                'type'  => 'stock',
                'value' => '',
                'label' => $stockLabels[$stock],
            ];
        }

        return $chips;
    }

    private function getRecentViewedProducts(?int $excludeId = null): array
    {
        $recentIds = $_SESSION['recent_viewed'] ?? [];
        if (empty($recentIds) || !is_array($recentIds)) {
            return [];
        }

        $filteredIds = [];
        foreach ($recentIds as $rid) {
            $id = (int) $rid;
            if ($id > 0 && ($excludeId === null || $id !== $excludeId)) {
                $filteredIds[] = $id;
            }
        }

        if (empty($filteredIds)) {
            return [];
        }

        $db = Database::getConnection();
        $ph = implode(',', array_fill(0, count($filteredIds), '?'));
        $stmt = $db->prepare("
            SELECT p.*, b.name AS brand_name, c.name AS category_name
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.id IN ($ph) AND p.status = 'ACTIVE'
        ");
        $stmt->execute($filteredIds);

        $rowsById = [];
        foreach ($stmt->fetchAll() as $row) {
            $rowsById[(int) $row['id']] = $row;
        }

        $ordered = [];
        foreach ($filteredIds as $id) {
            if (isset($rowsById[$id])) {
                $ordered[] = $rowsById[$id];
            }
        }

        return $ordered;
    }
}
