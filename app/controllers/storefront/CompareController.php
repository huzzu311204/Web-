<?php
declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Database;
use App\Core\Response;
use App\Models\ProductSpec;

class CompareController
{
    private const MAX_COMPARE = 3;

    /**
     * GET index.php?r=compare — Bảng so sánh trực diện tối đa 3 sản phẩm + hợp toàn bộ thông số kỹ thuật (Mục 7 - GĐ4 & TC11, TC12)
     */
    public function index(): void
    {
        $rawIds = $_SESSION['compare_ids'] ?? [];
        $ids = [];
        if (is_array($rawIds)) {
            foreach ($rawIds as $rid) {
                $id = (int) $rid;
                if ($id > 0 && !in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
        }
        $ids = array_slice($ids, 0, self::MAX_COMPARE);

        $products = [];
        $specUnionNames = [];
        $specMatrix = [];

        if (!empty($ids)) {
            $db = Database::getConnection();
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("
                SELECT p.*,
                       b.name AS brand_name,
                       c.name AS category_name,
                       s.name AS supplier_name
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN suppliers s ON s.id = p.supplier_id
                WHERE p.id IN ($ph) AND p.status = 'ACTIVE'
            ");
            $stmt->execute($ids);

            $rowsById = [];
            foreach ($stmt->fetchAll() as $row) {
                $rowsById[(int) $row['id']] = $row;
            }

            $validIds = [];
            foreach ($ids as $id) {
                if (isset($rowsById[$id])) {
                    $products[] = $rowsById[$id];
                    $validIds[] = $id;
                }
            }
            $_SESSION['compare_ids'] = $validIds;

            if (!empty($validIds)) {
                $specModel = new ProductSpec();
                $specsByProduct = $specModel->getByProductIds($validIds);

                // Hợp (union) toàn bộ tên thông số kỹ thuật theo đúng thứ tự xuất hiện (TC12)
                foreach ($validIds as $pid) {
                    $pSpecs = $specsByProduct[$pid] ?? [];
                    foreach ($pSpecs as $sp) {
                        $sName = trim((string) $sp['spec_name']);
                        $sVal  = trim((string) $sp['spec_value']);
                        if ($sName === '') {
                            continue;
                        }
                        if (!in_array($sName, $specUnionNames, true)) {
                            $specUnionNames[] = $sName;
                        }
                        $specMatrix[$sName][$pid] = $sVal;
                    }
                }
            }
        }

        Response::view('storefront/compare/index', [
            'pageTitle'      => 'So sánh Thông số Kỹ thuật Thiết bị — ProGear Studio',
            'products'       => $products,
            'specUnionNames' => $specUnionNames,
            'specMatrix'     => $specMatrix,
        ], 'front');
    }
}
