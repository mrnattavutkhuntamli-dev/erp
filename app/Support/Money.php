<?php

namespace App\Support;

class Money
{
    public static function toCents($amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', ',');
    }
}
