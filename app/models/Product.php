<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class Product
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $sql = '
            SELECT p.*,
                   c.name AS category_name,
                   b.name AS brand_name,
                   s.name AS supplier_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            WHERE p.id = :id
            LIMIT 1
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAllSimple(): array
    {
        $stmt = $this->db->query('SELECT id, code, name, price, quantity, status FROM products ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $code = strtoupper(trim($code));
        if ($excludeId !== null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE code = :code AND id != :id');
            $stmt->execute(['code' => $code, 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE code = :code');
            $stmt->execute(['code' => $code]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function hasInventoryHistory(int $id): bool
    {
        $stmt1 = $this->db->prepare('SELECT COUNT(*) FROM import_receipt_details WHERE product_id = :id');
        $stmt1->execute(['id' => $id]);
        if ((int) $stmt1->fetchColumn() > 0) {
            return true;
        }

        $stmt2 = $this->db->prepare('SELECT COUNT(*) FROM stock_logs WHERE product_id = :id');
        $stmt2->execute(['id' => $id]);
        return (int) $stmt2->fetchColumn() > 0;
    }

    /**
     * Tạo sản phẩm mới kèm thông số kỹ thuật động và ghi stock_logs "Tồn đầu kỳ" nếu quantity > 0 (Mục 4.3 & 6.2)
     */
    public function createWithSpecs(array $data, array $specs, int $userId): int
    {
        $this->db->beginTransaction();
        try {
            $initialQty = max(0, (int) ($data['quantity'] ?? 0));

            $stmt = $this->db->prepare(
                'INSERT INTO products
                    (category_id, supplier_id, brand_id, code, name, price, quantity, min_stock, description, image, status)
                 VALUES
                    (:category_id, :supplier_id, :brand_id, :code, :name, :price, :quantity, :min_stock, :description, :image, :status)'
            );
            $stmt->execute([
                'category_id' => (int) $data['category_id'],
                'supplier_id' => (int) $data['supplier_id'],
                'brand_id'    => !empty($data['brand_id']) ? (int) $data['brand_id'] : null,
                'code'        => strtoupper(trim((string) $data['code'])),
                'name'        => trim((string) $data['name']),
                'price'       => (int) $data['price'],
                'quantity'    => $initialQty,
                'min_stock'   => (int) ($data['min_stock'] ?? 0),
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'image'       => $data['image'] ?? null,
                'status'      => ($data['status'] ?? 'ACTIVE') === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE',
            ]);

            $productId = (int) $this->db->lastInsertId();

            $specModel = new ProductSpec();
            $specModel->replaceForProduct($productId, $specs);

            // Nếu có tồn đầu kỳ > 0 thì ghi một dòng stock_logs loại ADJUSTMENT với lý do "Tồn đầu kỳ" (Mục 4.3 & 6.2)
            if ($initialQty > 0) {
                $logStmt = $this->db->prepare(
                    "INSERT INTO stock_logs
                        (product_id, user_id, type, change_qty, quantity_before, quantity_after, reason, receipt_id)
                     VALUES
                        (:product_id, :user_id, 'ADJUSTMENT', :change_qty, 0, :quantity_after, 'Tồn đầu kỳ', NULL)"
                );
                $logStmt->execute([
                    'product_id'     => $productId,
                    'user_id'        => $userId,
                    'change_qty'     => $initialQty,
                    'quantity_after' => $initialQty,
                ]);
            }

            $this->db->commit();
            return $productId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật sản phẩm và thông số kỹ thuật động (Không sửa products.quantity - Mục 4.3)
     */
    public function updateWithSpecs(int $id, array $data, array $specs): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE products
                 SET category_id = :category_id,
                     supplier_id = :supplier_id,
                     brand_id    = :brand_id,
                     code        = :code,
                     name        = :name,
                     price       = :price,
                     min_stock   = :min_stock,
                     description = :description,
                     image       = :image,
                     status      = :status
                 WHERE id = :id'
            );
            $stmt->execute([
                'category_id' => (int) $data['category_id'],
                'supplier_id' => (int) $data['supplier_id'],
                'brand_id'    => !empty($data['brand_id']) ? (int) $data['brand_id'] : null,
                'code'        => strtoupper(trim((string) $data['code'])),
                'name'        => trim((string) $data['name']),
                'price'       => (int) $data['price'],
                'min_stock'   => (int) ($data['min_stock'] ?? 0),
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'image'       => $data['image'] ?? null,
                'status'      => ($data['status'] ?? 'ACTIVE') === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE',
                'id'          => $id,
            ]);

            $specModel = new ProductSpec();
            $specModel->replaceForProduct($id, $specs);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Sửa nhanh tại bảng (Chỉ cho phép sửa price, min_stock, status - Tuyệt đối không sửa quantity - TC21, TC22)
     */
    public function updateInline(int $id, int $price, int $minStock, string $status): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE products
             SET price = :price, min_stock = :min_stock, status = :status
             WHERE id = :id'
        );
        return $stmt->execute([
            'price'     => $price,
            'min_stock' => $minStock,
            'status'    => $status === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE',
            'id'        => $id,
        ]);
    }

    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE products SET status = :status WHERE id = :id');
        return $stmt->execute([
            'status' => $status === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE',
            'id'     => $id,
        ]);
    }

    public function hardDelete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Lấy sản phẩm liên quan: với máy ảnh ưu tiên ống kính cùng Ngàm, dự phòng cùng danh mục & thương hiệu (Mục 6.8)
     */
    public function getRelatedProducts(array $product, int $limit = 4): array
    {
        $productId = (int) $product['id'];
        $categoryId = (int) $product['category_id'];
        $brandId = (int) ($product['brand_id'] ?? 0);

        // Nếu là thân máy ảnh (category_id IN (1, 2, 3)), ưu tiên gợi ý ống kính (category_id = 4) cùng Ngàm
        if (in_array($categoryId, [1, 2, 3], true)) {
            $sqlMount = "
                SELECT p.*, b.name AS brand_name, c.name AS category_name
                FROM products p
                JOIN product_specs s_lens ON s_lens.product_id = p.id AND s_lens.spec_name = 'Ngàm'
                JOIN product_specs s_body ON s_body.product_id = :product_id AND s_body.spec_name = 'Ngàm'
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE s_lens.spec_value = s_body.spec_value
                  AND p.category_id = 4
                  AND p.status = 'ACTIVE'
                  AND p.id != :exclude_id
                ORDER BY p.quantity > 0 DESC, p.id ASC
                LIMIT $limit
            ";
            $stmt = $this->db->prepare($sqlMount);
            $stmt->execute(['product_id' => $productId, 'exclude_id' => $productId]);
            $lensMatches = $stmt->fetchAll();
            if (!empty($lensMatches)) {
                return $lensMatches;
            }
        }

        // Dự phòng: sản phẩm ACTIVE cùng danh mục, ưu tiên cùng thương hiệu
        $sqlFallback = "
            SELECT p.*, b.name AS brand_name, c.name AS category_name
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.category_id = :category_id
              AND p.status = 'ACTIVE'
              AND p.id != :exclude_id
            ORDER BY (p.brand_id = :brand_id) DESC, p.quantity > 0 DESC, p.id DESC
            LIMIT $limit
        ";
        $stmt = $this->db->prepare($sqlFallback);
        $stmt->execute([
            'category_id' => $categoryId,
            'exclude_id'  => $productId,
            'brand_id'    => $brandId,
        ]);
        return $stmt->fetchAll();
    }
}
