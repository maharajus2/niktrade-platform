<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class ProductionCalendar
{
    /**
     * @return array<string, string>
     */
    public static function holidays(int $year = 2026): array
    {
        return match ($year) {
            2026 => [
                '2026-01-01' => 'Новогодние каникулы',
                '2026-01-02' => 'Новогодние каникулы',
                '2026-01-03' => 'Новогодние каникулы',
                '2026-01-04' => 'Новогодние каникулы',
                '2026-01-05' => 'Новогодние каникулы',
                '2026-01-06' => 'Новогодние каникулы',
                '2026-01-07' => 'Рождество Христово',
                '2026-01-08' => 'Новогодние каникулы',
                '2026-01-09' => 'Перенос выходного с 3 января',
                '2026-02-23' => 'День защитника Отечества',
                '2026-03-08' => 'Международный женский день',
                '2026-03-09' => 'Перенос выходного дня',
                '2026-05-01' => 'Праздник Весны и Труда',
                '2026-05-09' => 'День Победы',
                '2026-05-11' => 'Перенос выходного дня',
                '2026-06-12' => 'День России',
                '2026-11-04' => 'День народного единства',
                '2026-12-31' => 'Перенос выходного с 4 января',
            ],
            default => [],
        };
    }

    /**
     * @return array{country: string, years: array<int>, holidays: array<string, string>}
     */
    public static function payload(int $year = 2026): array
    {
        return [
            'country' => 'RU',
            'years' => [$year],
            'holidays' => self::holidays($year),
        ];
    }

    public static function holidayLabel(Carbon|string $date): ?string
    {
        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        return self::holidays((int) $date->format('Y'))[$date->toDateString()] ?? null;
    }

    public static function isHoliday(Carbon|string $date): bool
    {
        return self::holidayLabel($date) !== null;
    }

    public static function isPreHoliday(Carbon|string $date): bool
    {
        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        return self::isHoliday($date->copy()->addDay());
    }
}
