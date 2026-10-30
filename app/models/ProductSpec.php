<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ProductSpec
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getByProductId(int $productId): array
    {
        $stmt = $this->db->prepare('SELECT id, spec_name, spec_value FROM product_specs WHERE product_id = :pid ORDER BY id ASC');
        $stmt->execute(['pid' => $productId]);
        return $stmt->fetchAll();
    }

    public function getByProductIds(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->db->prepare("SELECT product_id, spec_name, spec_value FROM product_specs WHERE product_id IN ($placeholders) ORDER BY id ASC");
        $stmt->execute(array_values($productIds));

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['product_id']][] = $row;
        }
        return $grouped;
    }

    public function replaceForProduct(int $productId, array $specs): void
    {
        $del = $this->db->prepare('DELETE FROM product_specs WHERE product_id = :pid');
        $del->execute(['pid' => $productId]);

        $ins = $this->db->prepare(
            'INSERT INTO product_specs (product_id, spec_name, spec_value)
             VALUES (:pid, :name, :val)'
        );

        foreach ($specs as $spec) {
            $name = trim((string) ($spec['name'] ?? ''));
            $val  = trim((string) ($spec['value'] ?? ''));
            if ($name === '' || $val === '') {
                continue;
            }
            $ins->execute([
                'pid'  => $productId,
                'name' => mb_substr($name, 0, 100, 'UTF-8'),
                'val'  => mb_substr($val, 0, 255, 'UTF-8'),
            ]);
        }
    }
}
