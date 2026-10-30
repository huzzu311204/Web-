<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\UiHelper;

class CsvExporter
{
    /**
     * Chống chèn công thức (CSV Formula Injection - Mục 6.9 & TC24):
     * Nếu ô bắt đầu bằng =, +, -, @, tab hoặc xuống dòng thì thêm dấu ' ở đầu
     */
    public static function sanitizeCell(string $value): string
    {
        if ($value !== '' && strpbrk($value[0], "=+-@\t\r\n") !== false) {
            return "'" . $value;
        }
        return $value;
    }

    public static function exportProducts(array $products): never
    {
        $filename = 'danh-sach-san-pham-' . date('Ymd-His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'wb');
        // Ghi UTF-8 BOM để Excel trên Windows mở đúng dấu tiếng Việt (Mục 5.2 - A8 & TC23)
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'Mã SKU',
            'Tên sản phẩm',
            'Danh mục',
            'Thương hiệu',
            'Nhà cung cấp',
            'Giá bán (VNĐ)',
            'Tồn kho',
            'Ngưỡng sắp hết',
            'Nhãn tồn kho',
            'Trạng thái',
        ]);

        foreach ($products as $p) {
            $qty = (int) ($p['quantity'] ?? 0);
            $min = (int) ($p['min_stock'] ?? 0);
            $status = (string) ($p['status'] ?? 'ACTIVE');
            $stockState = UiHelper::stockState($qty, $min, $status);

            $stockLabel = match ($stockState) {
                'IN_STOCK'     => 'Còn hàng',
                'LOW_STOCK'    => 'Sắp hết',
                'OUT_OF_STOCK' => 'Hết hàng',
                default        => 'Ngừng kinh doanh',
            };

            fputcsv($out, [
                self::sanitizeCell((string) ($p['code'] ?? '')),
                self::sanitizeCell((string) ($p['name'] ?? '')),
                self::sanitizeCell((string) ($p['category_name'] ?? '')),
                self::sanitizeCell((string) ($p['brand_name'] ?? '—')),
                self::sanitizeCell((string) ($p['supplier_name'] ?? '')),
                (string) (int) ($p['price'] ?? 0),
                (string) $qty,
                (string) $min,
                $stockLabel,
                $status === 'ACTIVE' ? 'Đang bán' : 'Ngừng kinh doanh',
            ]);
        }

        fclose($out);
        exit;
    }
}

