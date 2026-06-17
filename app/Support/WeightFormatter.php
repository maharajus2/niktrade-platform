<?php

namespace App\Support;

class WeightFormatter
{
    public static function formatGrams(?int $grams): string
    {
        if ($grams === null || $grams <= 0) {
            return '—';
        }

        if ($grams < 1000) {
            return self::formatNumber($grams) . ' г';
        }

        return self::formatNumber($grams / 1000) . ' кг';
    }

    public static function formatValueUnit(mixed $value, ?string $unit): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::formatNumber((float) $value) . ' ' . match ($unit) {
            'kg' => 'кг',
            default => 'г',
        };
    }

    private static function formatNumber(float|int $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3, ',', ' '), '0'), ',');
    }
}
