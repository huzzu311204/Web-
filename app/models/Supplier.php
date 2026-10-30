<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Supplier
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllWithCounts(): array
    {
        $sql = '
            SELECT s.*,
                   (SELECT COUNT(*) FROM products p WHERE p.supplier_id = s.id) AS product_count,
                   (SELECT COUNT(*) FROM import_receipts ir WHERE ir.supplier_id = s.id) AS receipt_count
            FROM suppliers s
            ORDER BY s.name ASC
        ';
        return $this->db->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM suppliers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM suppliers WHERE name = :name AND id != :id');
            $stmt->execute(['name' => trim($name), 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM suppliers WHERE name = :name');
            $stmt->execute(['name' => trim($name)]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function hasReferences(int $id): bool
    {
        $stmt1 = $this->db->prepare('SELECT COUNT(*) FROM products WHERE supplier_id = :id');
        $stmt1->execute(['id' => $id]);
        if ((int) $stmt1->fetchColumn() > 0) {
            return true;
        }

        $stmt2 = $this->db->prepare('SELECT COUNT(*) FROM import_receipts WHERE supplier_id = :id');
        $stmt2->execute(['id' => $id]);
        return (int) $stmt2->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO suppliers (name, phone, email, address)
             VALUES (:name, :phone, :email, :address)'
        );
        $stmt->execute([
            'name'    => trim($data['name']),
            'phone'   => trim($data['phone']),
            'email'   => trim($data['email'] ?? '') ?: null,
            'address' => trim($data['address'] ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE suppliers
             SET name = :name, phone = :phone, email = :email, address = :address
             WHERE id = :id'
        );
        return $stmt->execute([
            'name'    => trim($data['name']),
            'phone'   => trim($data['phone']),
            'email'   => trim($data['email'] ?? '') ?: null,
            'address' => trim($data['address'] ?? '') ?: null,
            'id'      => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM suppliers WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

