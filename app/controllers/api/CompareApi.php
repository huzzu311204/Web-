<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Response;
use App\Core\UiHelper;

class CompareApi
{
    private const MAX_COMPARE = 3;

    /**
     * POST index.php?r=api/compare — Thêm, xóa hoặc xóa tất cả sản phẩm trong danh sách so sánh (Mục 7 - GĐ4 & TC11)
     */
    public function handle(): void
    {
        $action = strtolower(trim((string) ($_POST['action'] ?? 'toggle')));
        $productId = (int) ($_POST['product_id'] ?? $_POST['id'] ?? 0);

        $compareIds = $this->getValidCompareIds();

        if ($action === 'clear') {
            $_SESSION['compare_ids'] = [];
            Response::json([
                'ok'      => true,
                'action'  => 'cleared',
                'count'   => 0,
                'items'   => [],
                'message' => 'Đã xóa toàn bộ danh sách so sánh.',
            ]);
        }

        if ($action === 'list') {
            $items = $this->fetchCompareProducts($compareIds);
            Response::json([
                'ok'    => true,
                'count' => count($items),
                'items' => $items,
            ]);
        }

        if ($productId <= 0) {
            Response::json(['ok' => false, 'message' => 'Sản phẩm không hợp lệ.'], 422);
        }

        // Gỡ sản phẩm luôn được phép, kể cả khi sản phẩm đã ngừng kinh doanh hoặc bị xóa
        // (getValidCompareIds() đã loại các ID không còn ACTIVE; thao tác này chỉ cần idempotent).
        if ($action === 'remove') {
            $idx = array_search($productId, $compareIds, true);
            if ($idx !== false) {
                unset($compareIds[$idx]);
                $compareIds = array_values($compareIds);
            }
            $_SESSION['compare_ids'] = $compareIds;
            $items = $this->fetchCompareProducts($compareIds);

            Response::json([
                'ok'         => true,
                'action'     => 'removed',
                'in_compare' => false,
                'count'      => count($items),
                'items'      => $items,
                'message'    => 'Đã gỡ thiết bị khỏi danh sách so sánh.',
            ]);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, code, name, price, image, status FROM products WHERE id = :id AND status = 'ACTIVE' LIMIT 1");
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            Response::json(['ok' => false, 'message' => 'Thiết bị không tồn tại hoặc đã ngừng kinh doanh.'], 404);
        }

        $existsIdx = array_search($productId, $compareIds, true);

        if ($action === 'remove' || ($action === 'toggle' && $existsIdx !== false)) {
            if ($existsIdx !== false) {
                unset($compareIds[$existsIdx]);
                $compareIds = array_values($compareIds);
            }
            $_SESSION['compare_ids'] = $compareIds;
            $items = $this->fetchCompareProducts($compareIds);

            Response::json([
                'ok'         => true,
                'action'     => 'removed',
                'in_compare' => false,
                'count'      => count($items),
                'items'      => $items,
                'message'    => 'Đã gỡ "' . $product['name'] . '" khỏi danh sách so sánh.',
            ]);
        }

        // Thêm vào danh sách so sánh (action = add hoặc toggle khi chưa có)
        if ($existsIdx === false) {
            if (count($compareIds) >= self::MAX_COMPARE) {
                $items = $this->fetchCompareProducts($compareIds);
                Response::json([
                    'ok'         => false,
                    'in_compare' => false,
                    'count'      => count($items),
                    'items'      => $items,
                    'message'    => 'Chỉ so sánh tối đa 3 sản phẩm cùng lúc. Vui lòng gỡ bớt 1 thiết bị trước khi thêm mới.',
                ], 422);
            }
            $compareIds[] = $productId;
        }

        $_SESSION['compare_ids'] = $compareIds;
        $items = $this->fetchCompareProducts($compareIds);

        Response::json([
            'ok'         => true,
            'action'     => 'added',
            'in_compare' => true,
            'count'      => count($items),
            'items'      => $items,
            'message'    => 'Đã thêm "' . $product['name'] . '" vào bảng so sánh (' . count($items) . '/3).',
        ]);
    }

    /**
     * Lọc danh sách ID trong session đảm bảo chỉ giữ sản phẩm ACTIVE và tối đa 3 phần tử
     */
    private function getValidCompareIds(): array
    {
        $raw = $_SESSION['compare_ids'] ?? [];
        if (!is_array($raw) || empty($raw)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $raw), static fn(int $id): bool => $id > 0)));
        if (empty($ids)) {
            return [];
        }

        $ids = array_slice($ids, 0, self::MAX_COMPARE);
        $db = Database::getConnection();
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT id FROM products WHERE id IN ($ph) AND status = 'ACTIVE'");
        $stmt->execute($ids);
        $activeIds = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));

        $validOrdered = [];
        foreach ($ids as $id) {
            if (in_array($id, $activeIds, true)) {
                $validOrdered[] = $id;
            }
        }

        $_SESSION['compare_ids'] = $validOrdered;
        return $validOrdered;
    }

    private function fetchCompareProducts(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $db = Database::getConnection();
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("
            SELECT p.id, p.code, p.name, p.price, p.image
            FROM products p
            WHERE p.id IN ($ph) AND p.status = 'ACTIVE'
        ");
        $stmt->execute($ids);

        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[(int) $r['id']] = [
                'id'              => (int) $r['id'],
                'code'            => (string) $r['code'],
                'name'            => (string) $r['name'],
                'price'           => (int) $r['price'],
                'price_formatted' => Response::money($r['price']),
                'image_url'       => UiHelper::imageUrl($r['image'] ?? null),
            ];
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($map[$id])) {
                $ordered[] = $map[$id];
            }
        }

        return $ordered;
    }
}
