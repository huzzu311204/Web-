<?php
declare(strict_types=1);

namespace App\Core;

class UiHelper
{
    /**
     * Trả về trạng thái tồn kho theo quy tắc Mục 4.4 & 9.3:
     * - status = INACTIVE -> DISCONTINUED (Ngừng kinh doanh)
     * - quantity = 0 -> OUT_OF_STOCK (Hết hàng)
     * - min_stock > 0 && quantity <= min_stock -> LOW_STOCK (Sắp hết)
     * - còn lại -> IN_STOCK (Còn hàng)
     */
    public static function stockState(int $quantity, int $minStock, string $status = 'ACTIVE'): string
    {
        if ($status === 'INACTIVE') {
            return 'DISCONTINUED';
        }
        if ($quantity <= 0) {
            return 'OUT_OF_STOCK';
        }
        if ($minStock > 0 && $quantity <= $minStock) {
            return 'LOW_STOCK';
        }
        return 'IN_STOCK';
    }

    public static function stockBadge(int $quantity, int $minStock, string $status = 'ACTIVE'): string
    {
        $state = self::stockState($quantity, $minStock, $status);

        $map = [
            'IN_STOCK' => [
                'label' => 'Còn hàng',
                'class' => 'stock-in',
                'icon'  => 'bi-check-circle',
            ],
            'LOW_STOCK' => [
                'label' => 'Sắp hết',
                'class' => 'stock-low',
                'icon'  => 'bi-exclamation-triangle',
            ],
            'OUT_OF_STOCK' => [
                'label' => 'Hết hàng',
                'class' => 'stock-out',
                'icon'  => 'bi-x-circle',
            ],
            'DISCONTINUED' => [
                'label' => 'Ngừng kinh doanh',
                'class' => 'stock-discontinued',
                'icon'  => 'bi-slash-circle',
            ],
        ];

        $info = $map[$state];

        return sprintf(
            '<span class="badge-status %s"><i class="bi %s" aria-hidden="true"></i><span>%s</span></span>',
            Response::e($info['class']),
            Response::e($info['icon']),
            Response::e($info['label'])
        );
    }

    public static function productStatusBadge(string $status): string
    {
        if ($status === 'ACTIVE') {
            return '<span class="badge-status stock-in"><i class="bi bi-check-circle" aria-hidden="true"></i><span>Đang bán</span></span>';
        }
        return '<span class="badge-status stock-discontinued"><i class="bi bi-slash-circle" aria-hidden="true"></i><span>Ngừng kinh doanh</span></span>';
    }

    public static function stockLogTypeBadge(string $type): string
    {
        return match ($type) {
            'IMPORT'     => '<span class="badge-status stock-in"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i><span>Nhập kho</span></span>',
            'EXPORT'     => '<span class="badge-status stock-out"><i class="bi bi-box-arrow-up" aria-hidden="true"></i><span>Xuất kho</span></span>',
            'ADJUSTMENT' => '<span class="badge-status stock-low"><i class="bi bi-sliders" aria-hidden="true"></i><span>Điều chỉnh</span></span>',
            default      => '<span class="badge-status stock-discontinued">' . Response::e($type) . '</span>',
        };
    }

    public static function productRowClass(int $quantity, int $minStock, string $status = 'ACTIVE'): string
    {
        if ($status === 'INACTIVE') {
            return '';
        }
        if ($quantity <= 0) {
            return 'row-out-of-stock';
        }
        if ($minStock > 0 && $quantity <= $minStock) {
            return 'row-low-stock';
        }
        return '';
    }

    public static function formatReceiptCode(int $id): string
    {
        return 'PN-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    public static function imageUrl(?string $image): string
    {
        if (!$image) {
            return 'images/products/default-gear.svg';
        }
        if (str_starts_with($image, 'uploads/')) {
            return ltrim($image, '/');
        }
        return 'images/products/' . ltrim($image, '/');
    }
}
