<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Validator;
use DomainException;
use PDO;
use Throwable;

class StockService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Xuất kho (EXPORT) hoặc Điều chỉnh kiểm kê (ADJUSTMENT) trong 1 Transaction có khóa dòng FOR UPDATE (Mục 6.2 & TC17, TC18, TC19)
     *
     * @param int    $productId ID sản phẩm
     * @param int    $delta     Số lượng thay đổi (âm khi giảm, dương khi tăng)
     * @param string $type      'EXPORT' hoặc 'ADJUSTMENT'
     * @param string $reason    Lý do bắt buộc (1–255 ký tự)
     * @param int    $userId    ID quản trị viên thao tác
     * @return array{product_id:int, code:string, name:string, quantity_before:int, quantity_after:int, change_qty:int, min_stock:int, status:string, low_or_out_count:int}
     */
    public function adjust(int $productId, int $delta, string $type, string $reason, int $userId): array
    {
        $type = strtoupper(trim($type));
        if (!in_array($type, ['EXPORT', 'ADJUSTMENT'], true)) {
            throw new DomainException('Loại giao dịch kho không hợp lệ.');
        }

        if ($delta === 0) {
            throw new DomainException('Số lượng thay đổi phải khác 0.');
        }

        if ($type === 'EXPORT' && $delta > 0) {
            $delta = -abs($delta);
        }

        $reason = trim($reason);
        if (!Validator::isValidLength($reason, 255, 1)) {
            throw new DomainException('Vui lòng nhập lý do xuất/điều chỉnh kho (từ 1 đến 255 ký tự).');
        }

        $this->db->beginTransaction();
        try {
            // Khóa dòng sản phẩm để chống race condition khi 2 tab cùng xuất kho (TC19)
            $lockStmt = $this->db->prepare(
                'SELECT id, code, name, quantity, min_stock, status
                 FROM products
                 WHERE id = :id
                 FOR UPDATE'
            );
            $lockStmt->execute(['id' => $productId]);
            $product = $lockStmt->fetch();

            if (!$product) {
                throw new DomainException('Không tìm thấy sản phẩm cần điều chỉnh tồn kho.');
            }

            $before = (int) $product['quantity'];
            $after = $before + $delta;

            if ($after < 0) {
                throw new DomainException('Tồn kho không đủ để thực hiện thao tác này (Hiện tồn: ' . $before . ').');
            }

            $updateStmt = $this->db->prepare('UPDATE products SET quantity = :qty WHERE id = :id');
            $updateStmt->execute([
                'qty' => $after,
                'id'  => $productId,
            ]);

            $logStmt = $this->db->prepare(
                'INSERT INTO stock_logs
                    (product_id, user_id, type, change_qty, quantity_before, quantity_after, reason, receipt_id)
                 VALUES
                    (:product_id, :user_id, :type, :change_qty, :quantity_before, :quantity_after, :reason, NULL)'
            );
            $logStmt->execute([
                'product_id'      => $productId,
                'user_id'         => $userId,
                'type'            => $type,
                'change_qty'      => $delta,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'reason'          => $reason,
            ]);

            $this->db->commit();

            return [
                'product_id'       => $productId,
                'code'             => (string) $product['code'],
                'name'             => (string) $product['name'],
                'quantity_before'  => $before,
                'quantity_after'   => $after,
                'change_qty'       => $delta,
                'min_stock'        => (int) $product['min_stock'],
                'status'           => (string) $product['status'],
                'low_or_out_count' => $this->countLowOrOutProducts(),
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lập phiếu nhập kho nhiều dòng trong 1 Transaction (Mục 6.2 & TC15, TC16)
     *
     * @param int         $supplierId ID nhà cung cấp
     * @param string      $importDate Ngày nhập (YYYY-MM-DD)
     * @param array       $lines      Mảng các dòng [['product_id' => ..., 'quantity' => ..., 'import_price' => ...], ...]
     * @param string|null $note       Ghi chú phiếu nhập
     * @param int         $userId     ID quản trị viên lập phiếu
     * @return int ID phiếu nhập vừa tạo
     */
    public function createReceipt(int $supplierId, string $importDate, array $lines, ?string $note, int $userId): int
    {
        if ($supplierId <= 0) {
            throw new DomainException('Vui lòng chọn nhà cung cấp hợp lệ.');
        }

        $supStmt = $this->db->prepare('SELECT id FROM suppliers WHERE id = :id LIMIT 1');
        $supStmt->execute(['id' => $supplierId]);
        if (!$supStmt->fetchColumn()) {
            throw new DomainException('Nhà cung cấp đã chọn không tồn tại trong hệ thống.');
        }

        $importDate = trim($importDate);
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $importDate);
        if (!$dt || $dt->format('Y-m-d') !== $importDate) {
            throw new DomainException('Ngày nhập kho không hợp lệ (định dạng chuẩn YYYY-MM-DD).');
        }

        $note = $note !== null ? trim($note) : null;
        if ($note !== null && $note !== '' && !Validator::isValidLength($note, 255)) {
            throw new DomainException('Ghi chú phiếu nhập tối đa 255 ký tự.');
        }

        if (empty($lines)) {
            throw new DomainException('Phiếu nhập phải có ít nhất 1 dòng sản phẩm.');
        }

        // Kiểm tra từng dòng, từ chối trùng sản phẩm và tự tính lại subtotal & total_amount ở server (TC15)
        $normalizedLines = [];
        $seenProducts = [];
        $totalAmount = 0;

        foreach ($lines as $idx => $line) {
            $rowNum = $idx + 1;
            $productId = (int) ($line['product_id'] ?? 0);
            $qtyRaw    = $line['quantity'] ?? '';
            $priceRaw  = $line['import_price'] ?? '';

            if ($productId <= 0) {
                throw new DomainException("Dòng #$rowNum: Vui lòng chọn sản phẩm hợp lệ.");
            }
            if (isset($seenProducts[$productId])) {
                throw new DomainException("Dòng #$rowNum: Sản phẩm bị trùng lặp trong cùng một phiếu nhập. Vui lòng gộp số lượng vào 1 dòng.");
            }
            $seenProducts[$productId] = true;

            if (!Validator::isPositiveInt($qtyRaw) || (int) $qtyRaw > 100000) {
                throw new DomainException("Dòng #$rowNum: Số lượng nhập phải là số nguyên dương (≥ 1).");
            }

            if (!Validator::isNonNegativeInt($priceRaw) || (int) $priceRaw > 999999999999) {
                throw new DomainException("Dòng #$rowNum: Đơn giá nhập phải là số nguyên không âm hợp lệ.");
            }

            $qty      = (int) $qtyRaw;
            $price    = (int) $priceRaw;
            $subtotal = $qty * $price;
            $totalAmount += $subtotal;

            $normalizedLines[] = [
                'product_id'   => $productId,
                'quantity'     => $qty,
                'import_price' => $price,
                'subtotal'     => $subtotal,
            ];
        }

        // Sắp xếp theo product_id tăng dần trước khi khóa dòng FOR UPDATE để tránh Deadlock
        usort($normalizedLines, static fn(array $a, array $b): int => $a['product_id'] <=> $b['product_id']);

        $this->db->beginTransaction();
        try {
            // 1. Ghi phiếu nhập master (import_receipts)
            $receiptStmt = $this->db->prepare(
                'INSERT INTO import_receipts (supplier_id, user_id, import_date, total_amount, note)
                 VALUES (:supplier_id, :user_id, :import_date, :total_amount, :note)'
            );
            $receiptStmt->execute([
                'supplier_id'  => $supplierId,
                'user_id'      => $userId,
                'import_date'  => $importDate,
                'total_amount' => $totalAmount,
                'note'         => ($note !== '') ? $note : null,
            ]);
            $receiptId = (int) $this->db->lastInsertId();

            $lockProdStmt = $this->db->prepare('SELECT id, quantity FROM products WHERE id = :id FOR UPDATE');
            $detailStmt   = $this->db->prepare(
                'INSERT INTO import_receipt_details (receipt_id, product_id, quantity, import_price, subtotal)
                 VALUES (:receipt_id, :product_id, :quantity, :import_price, :subtotal)'
            );
            $updProdStmt  = $this->db->prepare('UPDATE products SET quantity = :qty WHERE id = :id');
            $logStmt      = $this->db->prepare(
                "INSERT INTO stock_logs
                    (product_id, user_id, type, change_qty, quantity_before, quantity_after, reason, receipt_id)
                 VALUES
                    (:product_id, :user_id, 'IMPORT', :change_qty, :quantity_before, :quantity_after, :reason, :receipt_id)"
            );

            $formattedCode = sprintf('PN-%06d', $receiptId);

            // 2. Xử lý từng dòng chi tiết + cộng tồn + ghi stock_logs
            foreach ($normalizedLines as $item) {
                $pid = $item['product_id'];
                $lockProdStmt->execute(['id' => $pid]);
                $prodRow = $lockProdStmt->fetch();

                if (!$prodRow) {
                    throw new DomainException("Sản phẩm ID #$pid không tồn tại trong hệ thống.");
                }

                $before = (int) $prodRow['quantity'];
                $after  = $before + $item['quantity'];

                $detailStmt->execute([
                    'receipt_id'   => $receiptId,
                    'product_id'   => $pid,
                    'quantity'     => $item['quantity'],
                    'import_price' => $item['import_price'],
                    'subtotal'     => $item['subtotal'],
                ]);

                $updProdStmt->execute([
                    'qty' => $after,
                    'id'  => $pid,
                ]);

                $logStmt->execute([
                    'product_id'      => $pid,
                    'user_id'         => $userId,
                    'change_qty'      => $item['quantity'],
                    'quantity_before' => $before,
                    'quantity_after'  => $after,
                    'reason'          => 'Nhập kho theo phiếu ' . $formattedCode,
                    'receipt_id'      => $receiptId,
                ]);
            }

            $this->db->commit();
            return $receiptId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function countLowOrOutProducts(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM products
            WHERE status = 'ACTIVE'
              AND (quantity = 0 OR (min_stock > 0 AND quantity <= min_stock))
        ";
        return (int) $this->db->query($sql)->fetchColumn();
    }
}
