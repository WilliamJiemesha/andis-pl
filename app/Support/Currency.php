<?php

namespace App\Support;

class Currency
{
    public static function rupiah(float|int|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}
