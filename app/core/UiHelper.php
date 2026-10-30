<?php
declare(strict_types=1);

namespace App\Core;

class UiHelper
{
    private const STATUS_MAP = [
        'PENDING'   => [
            'label' => 'Chờ duyệt',
            'class' => 'status-pending',
            'icon'  => 'bi-hourglass-split',
        ],
        'APPROVED'  => [
            'label' => 'Đã duyệt',
            'class' => 'status-approved',
            'icon'  => 'bi-check2-circle',
        ],
        'RENTING'   => [
            'label' => 'Đang thuê',
            'class' => 'status-renting',
            'icon'  => 'bi-camera',
        ],
        'RETURNED'  => [
            'label' => 'Đã trả',
            'class' => 'status-returned',
            'icon'  => 'bi-box-arrow-in-down',
        ],
        'REJECTED'  => [
            'label' => 'Từ chối',
            'class' => 'status-rejected',
            'icon'  => 'bi-x-circle',
        ],
        'CANCELLED' => [
            'label' => 'Đã hủy',
            'class' => 'status-cancelled',
            'icon'  => 'bi-slash-circle',
        ],
    ];

    public static function statusBadge(string $status): string
    {
        $info = self::STATUS_MAP[$status] ?? [
            'label' => $status,
            'class' => 'status-cancelled',
            'icon'  => 'bi-info-circle',
        ];

        return sprintf(
            '<span class="badge-status %s"><i class="bi %s" aria-hidden="true"></i><span>%s</span></span>',
            Response::e($info['class']),
            Response::e($info['icon']),
            Response::e($info['label'])
        );
    }

    public static function dueWarningBadge(string $status, string $endDate): string
    {
        if ($status !== 'RENTING') {
            return '';
        }

        $today = date('Y-m-d');
        $twoDaysLater = date('Y-m-d', strtotime('+2 days'));

        if ($endDate < $today) {
            return '<span class="badge-status status-overdue ms-1"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span>Quá hạn</span></span>';
        }

        if ($endDate >= $today && $endDate <= $twoDaysLater) {
            return '<span class="badge-status status-due-soon ms-1"><i class="bi bi-clock" aria-hidden="true"></i><span>Sắp đến hạn</span></span>';
        }

        return '';
    }

    public static function rentalRowClass(string $status, string $endDate): string
    {
        if ($status !== 'RENTING') {
            return '';
        }

        $today = date('Y-m-d');
        $twoDaysLater = date('Y-m-d', strtotime('+2 days'));

        if ($endDate < $today) {
            return 'row-overdue';
        }

        if ($endDate >= $today && $endDate <= $twoDaysLater) {
            return 'row-due-soon';
        }

        return '';
    }

    public static function imageUrl(?string $image): string
    {
        if (!$image) {
            return 'images/products/default-gear.svg';
        }
        if (str_starts_with($image, 'uploads/')) {
            return '../' . ltrim($image, '/');
        }
        return 'images/products/' . ltrim($image, '/');
    }
}
