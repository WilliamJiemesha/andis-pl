<?php

namespace App\Support;

class PriceCode
{
    private const DIGITS = [
        '1' => 'T',
        '2' => 'E',
        '3' => 'K',
        '4' => 'I',
        '5' => 'R',
        '6' => 'O',
        '7' => 'S',
        '8' => 'A',
        '9' => 'N',
        '0' => 'P',
    ];

    private const LETTERS = [
        'T' => '1',
        'E' => '2',
        'K' => '3',
        'I' => '4',
        'R' => '5',
        'O' => '6',
        'S' => '7',
        'A' => '8',
        'N' => '9',
        'P' => '0',
    ];

    public static function encode(float|int|string|null $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) round((float) $amount));

        if (! $digits) {
            return null;
        }

        $zeroCount = strlen($digits) - strlen(rtrim($digits, '0'));

        if ($zeroCount > 1) {
            $digits = rtrim($digits, '0').'0'.(string) $zeroCount;
        }

        return strtr($digits, self::DIGITS);
    }

    public static function decode(?string $code): ?int
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        $digits = strtr($code, self::LETTERS);

        if (! ctype_digit($digits)) {
            return null;
        }

        if (strlen($digits) >= 2 && $digits[-2] === '0') {
            $digits = substr($digits, 0, -2).str_repeat('0', (int) $digits[-1]);
        }

        return (int) $digits;
    }
}
