<?php

namespace App\Support;

final class ClassLevel
{
    private const ROMAN = [
        'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6,
        'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12,
    ];

    public static function key(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/^KELAS\s+/u', '', $value);
        if (preg_match('/(?<!\d)(1[0-2]|[1-9])(?:\s*[A-Z])?(?!\d)/u', $value, $matches)) {
            return (string) ((int) $matches[1]);
        }

        if (preg_match('/\b(XII|XI|X|IX|VIII|VII|VI|V|IV|III|II|I)\b/u', $value, $matches)) {
            return (string) self::ROMAN[$matches[1]];
        }

        return preg_replace('/\s+/u', ' ', $value);
    }

    public static function label(?string $key): string
    {
        return $key !== null && ctype_digit($key) ? 'Kelas '.$key : (string) $key;
    }
}
