<?php
declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Database;
use App\Core\Response;
use App\Models\Category;
use App\Models\ProductSpec;
use PDO;

class HomeController
{
    public function index(): void
    {
        $db = Database::getConnection();
        $categoryModel = new Category();

        // Sản phẩm nổi bật cho Hero bất đối xứng (Sony FX3 hoặc sản phẩm ACTIVE đầu tiên)
        $heroStmt = $db->query("
            SELECT p.*, b.name AS brand_name, c.name AS category_name
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.status = 'ACTIVE' AND p.quantity > 0
            ORDER BY p.price DESC
            LIMIT 1
        ");
        $heroProduct = $heroStmt->fetch() ?: null;

        // Danh sách 6 sản phẩm mới nhất đang ACTIVE
        $newStmt = $db->query("
            SELECT p.*, b.name AS brand_name, c.name AS category_name
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.status = 'ACTIVE'
            ORDER BY p.created_at DESC, p.id DESC
            LIMIT 6
        ");
        $newestProducts = $newStmt->fetchAll();

        if (!empty($newestProducts)) {
            $ids = array_map(static fn(array $r): int => (int) $r['id'], $newestProducts);
            $specModel = new ProductSpec();
            $specsMap = $specModel->getByProductIds($ids);
            foreach ($newestProducts as &$item) {
                $item['specs'] = $specsMap[(int) $item['id']] ?? [];
            }
            unset($item);
        }

        // Sản phẩm đã xem gần đây từ session (tối đa 8 sản phẩm - Mục 7 GĐ3)
        $recentIds = $_SESSION['recent_viewed'] ?? [];
        $recentProducts = [];
        if (!empty($recentIds) && is_array($recentIds)) {
            $ph = implode(',', array_fill(0, count($recentIds), '?'));
            $recStmt = $db->prepare("
                SELECT p.*, b.name AS brand_name, c.name AS category_name
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.id IN ($ph) AND p.status = 'ACTIVE'
            ");
            $recStmt->execute(array_values($recentIds));
            $rowsById = [];
            foreach ($recStmt->fetchAll() as $r) {
                $rowsById[(int) $r['id']] = $r;
            }
            foreach ($recentIds as $rid) {
                if (isset($rowsById[(int) $rid])) {
                    $recentProducts[] = $rowsById[(int) $rid];
                }
            }
        }

        Response::view('storefront/home/index', [
            'pageTitle'      => 'ProGear Studio — Quản lý & Phân phối Thiết bị Nhiếp ảnh',
            'heroProduct'    => $heroProduct,
            'categories'     => $categoryModel->getAllWithCounts(),
            'newestProducts' => $newestProducts,
            'recentProducts' => $recentProducts,
        ], 'front');
    }
}

