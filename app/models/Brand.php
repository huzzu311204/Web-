<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Brand
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllWithCounts(): array
    {
        $sql = '
            SELECT b.*,
                   (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) AS product_count
            FROM brands b
            ORDER BY b.name ASC
        ';
        return $this->db->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM brands WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM brands WHERE name = :name AND id != :id');
            $stmt->execute(['name' => trim($name), 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM brands WHERE name = :name');
            $stmt->execute(['name' => trim($name)]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function hasProducts(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE brand_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO brands (name, logo) VALUES (:name, :logo)');
        $stmt->execute([
            'name' => trim($data['name']),
            'logo' => trim($data['logo'] ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE brands SET name = :name, logo = :logo WHERE id = :id');
        return $stmt->execute([
            'name' => trim($data['name']),
            'logo' => trim($data['logo'] ?? '') ?: null,
            'id'   => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM brands WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

