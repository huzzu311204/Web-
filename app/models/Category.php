<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllWithCounts(): array
    {
        $sql = '
            SELECT c.*,
                   p_cat.name AS parent_name,
                   (SELECT COUNT(*) FROM products pr WHERE pr.category_id = c.id) AS product_count,
                   (SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id) AS children_count
            FROM categories c
            LEFT JOIN categories p_cat ON p_cat.id = c.parent_id
            ORDER BY COALESCE(c.parent_id, c.id) ASC, c.parent_id IS NOT NULL ASC, c.name ASC
        ';
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Trả về danh sách danh mục đã sắp xếp dạng cây (cha trước, con thụt lề ngay sau)
     */
    public function getTreeOptions(?int $excludeId = null): array
    {
        $all = $this->getAllWithCounts();
        $parents = [];
        $childrenByParent = [];

        foreach ($all as $row) {
            if ($excludeId !== null && (int) $row['id'] === $excludeId) {
                continue;
            }
            if ($row['parent_id'] === null) {
                $parents[] = $row;
            } else {
                $childrenByParent[(int) $row['parent_id']][] = $row;
            }
        }

        $result = [];
        foreach ($parents as $parent) {
            $parent['depth'] = 0;
            $parent['display_name'] = $parent['name'];
            $result[] = $parent;

            $pid = (int) $parent['id'];
            if (!empty($childrenByParent[$pid])) {
                foreach ($childrenByParent[$pid] as $child) {
                    $child['depth'] = 1;
                    $child['display_name'] = '— ' . $child['name'];
                    $result[] = $child;
                }
            }
        }

        return $result;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM categories WHERE name = :name AND id != :id');
            $stmt->execute(['name' => trim($name), 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM categories WHERE name = :name');
            $stmt->execute(['name' => trim($name)]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function hasProducts(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function hasChildren(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM categories WHERE parent_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (parent_id, name, description)
             VALUES (:parent_id, :name, :description)'
        );
        $stmt->execute([
            'parent_id'   => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'name'        => trim($data['name']),
            'description' => trim($data['description'] ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE categories
             SET parent_id = :parent_id, name = :name, description = :description
             WHERE id = :id'
        );
        return $stmt->execute([
            'parent_id'   => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'name'        => trim($data['name']),
            'description' => trim($data['description'] ?? '') ?: null,
            'id'          => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Kiểm tra xem $candidateParentId có phải là chính $categoryId hoặc là hậu duệ của $categoryId hay không (chống vòng lặp cha-con)
     */
    public function isDescendantOrSelf(int $categoryId, int $candidateParentId): bool
    {
        if ($categoryId === $candidateParentId) {
            return true;
        }

        $visited = [$categoryId => true];
        $currentId = $candidateParentId;
        $depth = 0;
        $maxDepth = 20;

        while ($currentId > 0 && $depth < $maxDepth) {
            if (isset($visited[$currentId])) {
                return true;
            }
            $visited[$currentId] = true;

            $stmt = $this->db->prepare('SELECT parent_id FROM categories WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $currentId]);
            $parentId = $stmt->fetchColumn();

            if ($parentId === false || $parentId === null) {
                break;
            }
            $currentId = (int) $parentId;
            $depth++;
        }

        return false;
    }

    /**
     * Lấy mảng ID bao gồm cả danh mục hiện tại và mọi danh mục con/cháu (dùng tập visited chống vòng lặp vô hạn)
     */
    public function getCategoryAndChildrenIds(int $categoryId): array
    {
        $visited = [];
        $queue = [$categoryId];
        $depth = 0;
        $maxDepth = 10;

        while (!empty($queue) && $depth < $maxDepth) {
            $currentBatch = [];
            foreach ($queue as $id) {
                if (!isset($visited[$id])) {
                    $visited[$id] = true;
                    $currentBatch[] = $id;
                }
            }

            if (empty($currentBatch)) {
                break;
            }

            $placeholders = implode(',', array_fill(0, count($currentBatch), '?'));
            $stmt = $this->db->prepare("SELECT id FROM categories WHERE parent_id IN ($placeholders)");
            $stmt->execute($currentBatch);
            $children = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            $queue = [];
            foreach ($children as $childId) {
                if (!isset($visited[$childId])) {
                    $queue[] = $childId;
                }
            }
            $depth++;
        }

        return array_keys($visited);
    }
}

