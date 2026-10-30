<?php
declare(strict_types=1);

namespace App\Core;

class Validator
{
    public static function isValidVietnamPhone(string $phone): bool
    {
        $cleaned = preg_replace('/\s+/', '', trim($phone));
        return (bool) preg_match('/^(0[35789][0-9]{8}|02[0-9]{9})$/', (string) $cleaned);
    }

    public static function isValidEmail(string $email): bool
    {
        $trimmed = trim($email);
        if ($trimmed === '') {
            return true;
        }
        return mb_strlen($trimmed, 'UTF-8') <= 100
            && filter_var($trimmed, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isValidLength(string $value, int $maxLength, int $minLength = 0): bool
    {
        $len = mb_strlen(trim($value), 'UTF-8');
        return $len >= $minLength && $len <= $maxLength;
    }

    public static function isNonNegativeInt(mixed $value): bool
    {
        if (is_int($value)) {
            return $value >= 0;
        }
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }
        $str = trim((string) $value);
        return (bool) preg_match('/^\d+$/', $str);
    }

    public static function isPositiveInt(mixed $value): bool
    {
        return self::isNonNegativeInt($value) && (int) $value > 0;
    }

    public static function sanitizeSort(string $sort, array $allowed, string $default): string
    {
        return in_array($sort, $allowed, true) ? $sort : $default;
    }

    public static function clampInt(mixed $value, int $min, int $max, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }
        $intVal = (int) $value;
        return max($min, min($max, $intVal));
    }
}
