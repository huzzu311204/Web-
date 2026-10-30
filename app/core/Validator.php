<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

class Validator
{
    public static function isValidVietnamPhone(string $phone): bool
    {
        $cleaned = preg_replace('/\s+/', '', trim($phone));
        return (bool) preg_match('/^(0[35789][0-9]{8}|02[0-9]{9})$/', (string) $cleaned);
    }

    public static function isValidEmail(string $email): bool
    {
        if (trim($email) === '') {
            return true;
        }
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateDateRange(string $startDate, string $endDate, bool $allowPastStart = false): array
    {
        $start = DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
        $end   = DateTimeImmutable::createFromFormat('Y-m-d', $endDate);

        if (!$start || $start->format('Y-m-d') !== $startDate) {
            return ['ok' => false, 'message' => 'Ngày bắt đầu thuê không hợp lệ.', 'days' => 0];
        }
        if (!$end || $end->format('Y-m-d') !== $endDate) {
            return ['ok' => false, 'message' => 'Ngày kết thúc thuê không hợp lệ.', 'days' => 0];
        }

        $today = new DateTimeImmutable('today');
        if (!$allowPastStart && $start < $today) {
            return ['ok' => false, 'message' => 'Ngày bắt đầu thuê không được nằm trong quá khứ.', 'days' => 0];
        }

        if ($end < $start) {
            return ['ok' => false, 'message' => 'Ngày kết thúc không được trước ngày bắt đầu.', 'days' => 0];
        }

        $days = (int) $start->diff($end)->days + 1;
        if ($days < 1 || $days > 30) {
            return ['ok' => false, 'message' => 'Thời gian thuê của một đơn phải từ 1 đến tối đa 30 ngày.', 'days' => $days];
        }

        return ['ok' => true, 'message' => '', 'days' => $days];
    }

    public static function sanitizeSort(string $sort, array $allowed, string $default): string
    {
        return in_array($sort, $allowed, true) ? $sort : $default;
    }
}

