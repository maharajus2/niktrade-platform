<?php

namespace App\Support;

class ProductName
{
    private const DIRECTION_LABELS = [
        'home' => 'Для дома',
        'professional' => 'Для бизнеса',
    ];

    private const DIRECTION_WORDS = [
        'home' => ['home', 'хоум', 'для дома'],
        'professional' => ['professional', 'profession', 'pro', 'проф', 'для бизнеса'],
    ];

    public static function directionLabel(?string $direction): ?string
    {
        return self::DIRECTION_LABELS[(string) $direction] ?? null;
    }

    /**
     * Название для показа в карточках: без бренда в начале (он показан отдельно)
     * и без слова-направления («HOME:»), если направление показано шильдиком.
     * «Hermes Industry HOME: Антижир» (бренд Hermes Industry, направление home) -> «Антижир».
     */
    public static function withoutBrand(?string $name, ?string $brand, ?string $direction = null): string
    {
        $original = trim((string) $name);
        $name = $original;
        $brand = trim((string) $brand);

        if ($name === '') {
            return '';
        }

        if ($brand !== '') {
            $name = self::stripPrefix($name, $brand);
        }

        foreach (self::DIRECTION_WORDS[(string) $direction] ?? [] as $word) {
            $stripped = self::stripPrefix($name, $word, true);

            if ($stripped !== $name) {
                $name = $stripped;
                break;
            }
        }

        return $name === '' ? $original : $name;
    }

    private static function stripPrefix(string $name, string $prefix, bool $wholeWord = false): string
    {
        $length = mb_strlen($prefix);

        if (mb_strtolower(mb_substr($name, 0, $length)) !== mb_strtolower($prefix)) {
            return $name;
        }

        $tail = mb_substr($name, $length);

        // слово должно заканчиваться: после него пробел или знак, а не продолжение слова
        if ($wholeWord && $tail !== '' && preg_match('/^[\p{L}\p{N}]/u', $tail)) {
            return $name;
        }

        $rest = ltrim($tail, " \t:;,.-–—");

        if (mb_strlen($rest) < 3) {
            return $name;
        }

        return mb_strtoupper(mb_substr($rest, 0, 1)) . mb_substr($rest, 1);
    }
}
