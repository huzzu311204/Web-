<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Validator;
use PDO;

class StockLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Lọc và phân trang lịch sử biến động tồn kho (Mục 6.3)
     */
    public function paginate(array $filters, int $defaultPerPage = 15): array
    {
        $conditions = [];
        $params = [];

        $productId = (int) ($filters['product_id'] ?? 0);
        if ($productId > 0) {
            $conditions[] = 'l.product_id = ?';
            $params[] = $productId;
        }

        $type = strtoupper(trim((string) ($filters['type'] ?? '')));
        if (in_array($type, ['IMPORT', 'EXPORT', 'ADJUSTMENT'], true)) {
            $conditions[] = 'l.type = ?';
            $params[] = $type;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $conditions[] = 'l.created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $conditions[] = 'l.created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        $whereSql = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $page    = Validator::clampInt($filters['page'] ?? 1, 1, 1000, 1);
        $perPage = Validator::clampInt($filters['limit'] ?? $defaultPerPage, 1, 100, $defaultPerPage);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM stock_logs l $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT l.*,
                   p.code AS product_code,
                   p.name AS product_name,
                   u.full_name AS user_full_name,
                   u.username AS username
            FROM stock_logs l
            LEFT JOIN products p ON p.id = l.product_id
            LEFT JOIN users u ON u.id = l.user_id
            $whereSql
            ORDER BY l.created_at DESC, l.id DESC
            LIMIT $perPage OFFSET $offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'items'   => $stmt->fetchAll(),
            'total'   => $total,
            'page'    => $page,
            'pages'   => $totalPages,
            'perPage' => $perPage,
        ];
    }
}
