<?php

namespace App\Support;

class ProductDisplayFormatter
{
    /**
     * Code 128 patterns for values 0-106. Each digit is the width of the next bar/space.
     *
     * @var array<int, string>
     */
    private const CODE_128_PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    public static function formatVolume(mixed $value, ?string $unit): ?string
    {
        return self::formatValueUnit($value, $unit, [
            'l' => 'л',
            'ml' => 'мл',
            'g' => 'г',
            'kg' => 'кг',
        ]);
    }

    public static function formatWeight(mixed $value, ?string $unit): ?string
    {
        return self::formatValueUnit($value, $unit, [
            'g' => 'г',
            'kg' => 'кг',
        ]);
    }

    public static function formatShelfLife(mixed $value, ?string $unit): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (int) $value;
        $forms = match ($unit) {
            'year', 'years' => ['год', 'года', 'лет'],
            'month', 'months' => ['месяц', 'месяца', 'месяцев'],
            'day', 'days' => ['день', 'дня', 'дней'],
            default => null,
        };

        if ($forms === null) {
            return trim(self::formatNumber($number) . ' ' . (string) $unit);
        }

        return self::formatNumber($number) . ' ' . self::pluralizeRu($number, $forms);
    }

    public static function formatBarcodeSvg(?string $barcode): ?string
    {
        $barcode = trim((string) $barcode);

        if ($barcode === '') {
            return null;
        }

        $codes = [104];

        foreach (str_split($barcode) as $character) {
            $ascii = ord($character);

            if ($ascii < 32 || $ascii > 126) {
                return null;
            }

            $codes[] = $ascii - 32;
        }

        $checksum = 104;

        foreach (array_slice($codes, 1) as $index => $code) {
            $checksum += $code * ($index + 1);
        }

        $codes[] = $checksum % 103;
        $codes[] = 106;

        $module = 2;
        $height = 72;
        $quietZone = 10;
        $x = $quietZone;
        $rects = [];

        foreach ($codes as $code) {
            foreach (str_split(self::CODE_128_PATTERNS[$code]) as $index => $width) {
                $width = (int) $width * $module;

                if ($index % 2 === 0) {
                    $rects[] = sprintf('<rect x="%d" y="0" width="%d" height="%d" />', $x, $width, $height);
                }

                $x += $width;
            }
        }

        $width = $x + $quietZone;
        $label = htmlspecialchars($barcode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<svg class="barcode-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" role="img" aria-label="Штрихкод %s"><title>Штрихкод %s</title><rect width="100%%" height="100%%" fill="#ffffff" />%s</svg>',
            $width,
            $height,
            $label,
            $label,
            implode('', $rects),
        );
    }

    private static function formatValueUnit(mixed $value, ?string $unit, array $labels): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $label = $labels[$unit] ?? $unit;

        return trim(self::formatNumber((float) $value) . ' ' . (string) $label);
    }

    private static function formatNumber(float|int $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3, ',', ' '), '0'), ',');
    }

    /**
     * @param array{0: string, 1: string, 2: string} $forms
     */
    private static function pluralizeRu(int $number, array $forms): string
    {
        $absolute = abs($number);
        $lastTwo = $absolute % 100;
        $last = $absolute % 10;

        if ($lastTwo >= 11 && $lastTwo <= 14) {
            return $forms[2];
        }

        return match ($last) {
            1 => $forms[0],
            2, 3, 4 => $forms[1],
            default => $forms[2],
        };
    }
}
