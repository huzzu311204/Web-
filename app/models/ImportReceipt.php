<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Validator;
use PDO;

class ImportReceipt
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Phân trang danh sách phiếu nhập kho (Mục 6.3)
     */
    public function paginate(array $filters, int $defaultPerPage = 10): array
    {
        $conditions = [];
        $params = [];

        $supplierId = (int) ($filters['supplier_id'] ?? 0);
        if ($supplierId > 0) {
            $conditions[] = 'r.supplier_id = ?';
            $params[] = $supplierId;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $conditions[] = 'r.import_date >= ?';
            $params[] = $dateFrom;
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $conditions[] = 'r.import_date <= ?';
            $params[] = $dateTo;
        }

        $whereSql = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $page    = Validator::clampInt($filters['page'] ?? 1, 1, 1000, 1);
        $perPage = Validator::clampInt($filters['limit'] ?? $defaultPerPage, 1, 100, $defaultPerPage);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM import_receipts r $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT r.*,
                   s.name AS supplier_name,
                   s.phone AS supplier_phone,
                   u.full_name AS creator_name,
                   u.username AS creator_username,
                   (SELECT COUNT(*) FROM import_receipt_details d WHERE d.receipt_id = r.id) AS line_count,
                   (SELECT COALESCE(SUM(d.quantity), 0) FROM import_receipt_details d WHERE d.receipt_id = r.id) AS total_qty
            FROM import_receipts r
            LEFT JOIN suppliers s ON s.id = r.supplier_id
            LEFT JOIN users u ON u.id = r.user_id
            $whereSql
            ORDER BY r.import_date DESC, r.id DESC
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

    /**
     * Lấy chi tiết phiếu nhập (Header + Danh sách dòng sản phẩm)
     */
    public function findWithDetails(int $id): ?array
    {
        $sql = '
            SELECT r.*,
                   s.name AS supplier_name,
                   s.phone AS supplier_phone,
                   s.email AS supplier_email,
                   s.address AS supplier_address,
                   u.full_name AS creator_name,
                   u.username AS creator_username
            FROM import_receipts r
            LEFT JOIN suppliers s ON s.id = r.supplier_id
            LEFT JOIN users u ON u.id = r.user_id
            WHERE r.id = :id
            LIMIT 1
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $receipt = $stmt->fetch();

        if (!$receipt) {
            return null;
        }

        $detailSql = '
            SELECT d.*,
                   p.code AS product_code,
                   p.name AS product_name,
                   p.image AS product_image,
                   b.name AS brand_name,
                   c.name AS category_name
            FROM import_receipt_details d
            LEFT JOIN products p ON p.id = d.product_id
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE d.receipt_id = :receipt_id
            ORDER BY d.id ASC
        ';
        $dStmt = $this->db->prepare($detailSql);
        $dStmt->execute(['receipt_id' => $id]);
        $receipt['details'] = $dStmt->fetchAll();

        return $receipt;
    }
}
