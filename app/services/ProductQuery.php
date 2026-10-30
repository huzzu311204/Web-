<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Validator;
use App\Models\Category;
use App\Models\ProductSpec;
use PDO;

class ProductQuery
{
    private PDO $db;

    private const SORTS = [
        'newest'     => 'p.created_at DESC, p.id DESC',
        'price_asc'  => 'p.price ASC, p.id DESC',
        'price_desc' => 'p.price DESC, p.id DESC',
        'name_asc'   => 'p.name ASC, p.id DESC',
        'stock_asc'  => 'p.quantity ASC, p.id DESC',
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Truy vấn danh sách sản phẩm có tìm kiếm, lọc nhiều tiêu chí, sắp xếp và phân trang (Mục 5.3 & 6.8)
     */
    public function paginate(array $filters, bool $isStorefront = true, int $defaultPerPage = 9): array
    {
        [$whereSql, $params] = $this->buildWhere($filters, $isStorefront);

        $sortKey = Validator::sanitizeSort(
            (string) ($filters['sort'] ?? 'newest'),
            array_keys(self::SORTS),
            'newest'
        );
        $orderBy = self::SORTS[$sortKey];

        $page    = Validator::clampInt($filters['page'] ?? 1, 1, 1000, 1);
        $perPage = Validator::clampInt($filters['limit'] ?? $defaultPerPage, 1, 100, $defaultPerPage);

        // Đếm tổng số dòng
        $countSql = "SELECT COUNT(*) FROM products p $whereSql";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT p.*,
                   c.name AS category_name,
                   b.name AS brand_name,
                   s.name AS supplier_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            $whereSql
            ORDER BY $orderBy
            LIMIT $perPage OFFSET $offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        // Gắn thông số kỹ thuật cho từng sản phẩm để hiển thị spec chip trên thẻ
        if (!empty($items)) {
            $ids = array_map(static fn(array $r): int => (int) $r['id'], $items);
            $specModel = new ProductSpec();
            $specsMap = $specModel->getByProductIds($ids);
            foreach ($items as &$item) {
                $item['specs'] = $specsMap[(int) $item['id']] ?? [];
            }
            unset($item);
        }

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'pages'   => $totalPages,
            'perPage' => $perPage,
            'sort'    => $sortKey,
        ];
    }

    /**
     * Lấy toàn bộ dòng theo đúng bộ lọc & sắp xếp hiện tại để xuất CSV (Mục 5.2 - A8)
     */
    public function fetchAllFiltered(array $filters, bool $isStorefront = false): array
    {
        [$whereSql, $params] = $this->buildWhere($filters, $isStorefront);

        $sortKey = Validator::sanitizeSort(
            (string) ($filters['sort'] ?? 'newest'),
            array_keys(self::SORTS),
            'newest'
        );
        $orderBy = self::SORTS[$sortKey];

        $sql = "
            SELECT p.*,
                   c.name AS category_name,
                   b.name AS brand_name,
                   s.name AS supplier_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            $whereSql
            ORDER BY $orderBy
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function buildWhere(array $filters, bool $isStorefront): array
    {
        $conditions = [];
        $params = [];

        // Khu vực khách chỉ xem sản phẩm ACTIVE (Mục 4.4)
        if ($isStorefront) {
            $conditions[] = "p.status = 'ACTIVE'";
        } else {
            $status = strtoupper(trim((string) ($filters['status'] ?? '')));
            if (in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
                $conditions[] = 'p.status = ?';
                $params[] = $status;
            }
        }

        // Tìm kiếm theo tên hoặc mã SKU
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $conditions[] = '(p.name LIKE ? OR p.code LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        // Lọc theo danh mục (hỗ trợ cả danh mục cha lấy luôn danh mục con)
        $categoryId = (int) ($filters['category_id'] ?? $filters['category'] ?? 0);
        if ($categoryId > 0) {
            $categoryModel = new Category();
            $catIds = $categoryModel->getCategoryAndChildrenIds($categoryId);
            if (!empty($catIds)) {
                $ph = implode(',', array_fill(0, count($catIds), '?'));
                $conditions[] = "p.category_id IN ($ph)";
                $params = array_merge($params, $catIds);
            }
        }

        // Lọc thương hiệu (chọn 1 hoặc chọn nhiều bằng IN (?, ?) - Mục 6.8)
        $brandIds = [];
        if (!empty($filters['brands'])) {
            $rawBrands = is_array($filters['brands'])
                ? $filters['brands']
                : explode(',', (string) $filters['brands']);
            foreach ($rawBrands as $bId) {
                $id = (int) $bId;
                if ($id > 0) {
                    $brandIds[] = $id;
                }
            }
            $brandIds = array_values(array_unique($brandIds));
        } elseif (!empty($filters['brand_id'])) {
            $id = (int) $filters['brand_id'];
            if ($id > 0) {
                $brandIds[] = $id;
            }
        }

        if (!empty($brandIds)) {
            $ph = implode(',', array_fill(0, count($brandIds), '?'));
            $conditions[] = "p.brand_id IN ($ph)";
            $params = array_merge($params, $brandIds);
        }

        // Lọc khoảng giá (Mục 5.1 - K1)
        if (isset($filters['min_price']) && $filters['min_price'] !== '' && is_numeric($filters['min_price'])) {
            $minPrice = max(0, (int) $filters['min_price']);
            $conditions[] = 'p.price >= ?';
            $params[] = $minPrice;
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '' && is_numeric($filters['max_price'])) {
            $maxPrice = max(0, (int) $filters['max_price']);
            $conditions[] = 'p.price <= ?';
            $params[] = $maxPrice;
        }

        // Lọc tình trạng tồn kho (Mục 4.4 & 5.2 - A5)
        $stock = strtolower(trim((string) ($filters['stock'] ?? '')));
        if ($stock === 'out_of_stock') {
            $conditions[] = 'p.quantity = 0';
        } elseif ($stock === 'low_stock') {
            $conditions[] = '(p.quantity > 0 AND p.min_stock > 0 AND p.quantity <= p.min_stock)';
        } elseif ($stock === 'in_stock') {
            $conditions[] = '(p.quantity > 0 AND (p.min_stock = 0 OR p.quantity > p.min_stock))';
        } elseif ($stock === 'alert') {
            // Nút lọc nhanh "Sắp hết / Hết hàng" phía Admin (Mục 5.2 - A5)
            $conditions[] = '(p.quantity = 0 OR (p.min_stock > 0 AND p.quantity <= p.min_stock))';
        }

        $whereSql = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
        return [$whereSql, $params];
    }
}

