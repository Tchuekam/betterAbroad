<?php
declare(strict_types=1);

final class Validation
{
    public static function required(array $payload, array $fields): array
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($payload[$field]) || $payload[$field] === '') {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    public static function sanitizeString(?string $value, int $maxLength = 255): string
    {
        $clean = trim((string) $value);
        $clean = mb_substr($clean, 0, $maxLength);
        return filter_var($clean, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }
}
