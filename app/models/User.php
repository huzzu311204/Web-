<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => trim($username)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT id, username, full_name, email, phone, role, status, created_at FROM users ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE username = :username AND id != :id');
            $stmt->execute(['username' => trim($username), 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
            $stmt->execute(['username' => trim($username)]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, password, full_name, email, phone, role, status)
             VALUES (:username, :password, :full_name, :email, :phone, :role, :status)'
        );
        $stmt->execute([
            'username'  => trim($data['username']),
            'password'  => password_hash($data['password'], PASSWORD_DEFAULT),
            'full_name' => trim($data['full_name']),
            'email'     => trim($data['email'] ?? '') ?: null,
            'phone'     => trim($data['phone'] ?? '') ?: null,
            'role'      => 'ADMIN',
            'status'    => ($data['status'] ?? 'ACTIVE') === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!empty($data['password'])) {
            $stmt = $this->db->prepare(
                'UPDATE users
                 SET full_name = :full_name, email = :email, phone = :phone, password = :password
                 WHERE id = :id'
            );
            return $stmt->execute([
                'full_name' => trim($data['full_name']),
                'email'     => trim($data['email'] ?? '') ?: null,
                'phone'     => trim($data['phone'] ?? '') ?: null,
                'password'  => password_hash($data['password'], PASSWORD_DEFAULT),
                'id'        => $id,
            ]);
        }

        $stmt = $this->db->prepare(
            'UPDATE users
             SET full_name = :full_name, email = :email, phone = :phone
             WHERE id = :id'
        );
        return $stmt->execute([
            'full_name' => trim($data['full_name']),
            'email'     => trim($data['email'] ?? '') ?: null,
            'phone'     => trim($data['phone'] ?? '') ?: null,
            'id'        => $id,
        ]);
    }

    public function toggleStatus(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }
        $newStatus = $user['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $stmt = $this->db->prepare('UPDATE users SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $newStatus, 'id' => $id]);
    }
}

