<?php

namespace App\Services\Search;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * Поиск по товарам с допуском опечаток и неверной раскладки.
 * Каталог небольшой, поэтому индекс берём в память и ранжируем в PHP (не зависит от СУБД).
 */
class ProductSearchService
{
    private const LAYOUT = [
        'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н', 'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з',
        '[' => 'х', ']' => 'ъ', 'a' => 'ф', 's' => 'ы', 'd' => 'в', 'f' => 'а', 'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л',
        'l' => 'д', ';' => 'ж', "'" => 'э', 'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь',
        ',' => 'б', '.' => 'ю',
    ];

    private const TRANSLIT = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y',
        'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e',
        'ю' => 'yu', 'я' => 'ya',
    ];

    /**
     * @return array{items: list<array<string, mixed>>, total: int, fuzzy: bool}
     */
    public function search(string $query, int $limit = 6): array
    {
        $ranked = $this->rank($query);
        $fuzzy = $ranked !== [] && ! $ranked[0]['exact'];

        return [
            'items' => array_slice(array_map(fn (array $row): array => $row['product'], $ranked), 0, $limit),
            'total' => count($ranked),
            'fuzzy' => $fuzzy,
            'ids' => array_map(fn (array $row): int => $row['product']['id'], $ranked),
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(string $query): array
    {
        return $this->search($query, 0)['ids'];
    }

    public function hasExactMatch(string $query): bool
    {
        $ranked = $this->rank($query);

        return $ranked !== [] && $ranked[0]['exact'];
    }

    /**
     * @return list<array{score: float, exact: bool, product: array<string, mixed>}>
     */
    private function rank(string $query): array
    {
        $variants = $this->variants($query);

        if ($variants === []) {
            return [];
        }

        $rows = [];

        foreach ($this->index() as $entry) {
            $best = null;

            foreach ($variants as $variant) {
                $result = $this->score($variant, $entry);

                if ($result !== null && ($best === null || $result['score'] > $best['score'])) {
                    $best = $result;
                }
            }

            if ($best !== null) {
                $rows[] = ['score' => $best['score'], 'exact' => $best['exact'], 'product' => $entry['product']];
            }
        }

        usort($rows, fn (array $a, array $b): int => $b['score'] <=> $a['score'] ?: strcmp($a['product']['name'], $b['product']['name']));

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function variants(string $query): array
    {
        $normalized = $this->normalize($query);

        if (mb_strlen($normalized) < 2) {
            return [];
        }

        $variants = [$normalized];

        if (preg_match('/^[a-z\[\];\',.\s]+$/iu', $query)) {
            $switched = '';

            foreach (mb_str_split(mb_strtolower($query)) as $char) {
                $switched .= self::LAYOUT[$char] ?? $char;
            }

            $switched = $this->normalize($switched);

            if ($switched !== '' && $switched !== $normalized) {
                $variants[] = $switched;
            }
        }

        // Обратный случай: латинское слово набрано на русской раскладке («руиукп» -> «hiberg»).
        if (preg_match('/^[а-яё\s]+$/iu', $query)) {
            $reverse = array_flip(array_filter(self::LAYOUT, fn (string $ru): bool => true));
            $latin = '';

            foreach (mb_str_split(mb_strtolower(str_replace('ё', 'е', $query))) as $char) {
                $mapped = $reverse[$char] ?? $char;
                $latin .= preg_match('/^[a-z]$/', $mapped) || $mapped === ' ' ? $mapped : '';
            }

            $latin = $this->normalize($latin);

            if ($latin !== '' && ! in_array($latin, $variants, true)) {
                $variants[] = $latin;
            }

            // Русская запись латинского слова по звучанию («хиберг» -> «hiberg»).
            $phonetic = '';

            foreach (mb_str_split(mb_strtolower(str_replace('ё', 'е', $query))) as $char) {
                $phonetic .= $char === ' ' ? ' ' : (self::TRANSLIT[$char] ?? '');
            }

            $phonetic = $this->normalize($phonetic);

            if ($phonetic !== '' && ! in_array($phonetic, $variants, true)) {
                $variants[] = $phonetic;
            }
        }

        return $variants;
    }

    /**
     * @param array{name: string, article: string, extra: string, tokens: list<string>, product: array<string, mixed>} $entry
     * @return array{score: float, exact: bool}|null
     */
    private function score(string $query, array $entry): ?array
    {
        // 1) прямое вхождение всей строки
        if (str_contains($entry['name'], $query)) {
            return ['score' => 100 + (str_starts_with($entry['name'], $query) ? 10 : 0), 'exact' => true];
        }

        if ($entry['article'] !== '' && str_contains($entry['article'], $query)) {
            return ['score' => 95, 'exact' => true];
        }

        $queryTokens = array_values(array_filter(explode(' ', $query), fn (string $t): bool => mb_strlen($t) >= 2));

        if ($queryTokens === []) {
            return null;
        }

        $total = 0.0;
        $exact = true;

        // 2) каждое слово запроса должно найтись в названии/бренде/категории (с допуском опечаток)
        foreach ($queryTokens as $token) {
            $tokenScore = 0.0;

            foreach ($entry['tokens'] as $word) {
                if (str_starts_with($word, $token)) {
                    $tokenScore = max($tokenScore, 30);

                    continue;
                }

                if (str_contains($word, $token)) {
                    $tokenScore = max($tokenScore, 20);

                    continue;
                }

                $allowed = match (true) {
                    mb_strlen($token) <= 3 => 0,
                    mb_strlen($token) <= 6 => 1,
                    default => 2,
                };

                if ($allowed === 0) {
                    continue;
                }

                $distance = min(
                    $this->levenshtein($token, $word),
                    $this->levenshtein($token, mb_substr($word, 0, mb_strlen($token))),
                );

                if ($distance <= $allowed) {
                    $tokenScore = max($tokenScore, 18 - 6 * $distance);
                    $exact = false;
                }
            }

            if ($tokenScore <= 0) {
                return null;
            }

            $total += $tokenScore;
        }

        return ['score' => $total / count($queryTokens), 'exact' => $exact];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = str_replace('ё', 'е', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function levenshtein(string $a, string $b): int
    {
        $a = mb_str_split($a);
        $b = mb_str_split($b);
        $la = count($a);
        $lb = count($b);

        if ($la === 0) {
            return $lb;
        }

        if ($lb === 0) {
            return $la;
        }

        $prev = range(0, $lb);

        for ($i = 1; $i <= $la; $i++) {
            $cur = [$i];

            for ($j = 1; $j <= $lb; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $cur[$j] = min($prev[$j] + 1, $cur[$j - 1] + 1, $prev[$j - 1] + $cost);

                // перестановка соседних букв считается одной ошибкой
                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $cur[$j] = min($cur[$j], ($prev2[$j - 2] ?? PHP_INT_MAX) + 1);
                }
            }

            $prev2 = $prev;
            $prev = $cur;
        }

        return $prev[$lb];
    }

    /**
     * @return list<array{name: string, article: string, tokens: list<string>, product: array<string, mixed>}>
     */
    private function index(): array
    {
        return Cache::remember('shop.search.index.v1', 120, function (): array {
            return Product::query()
                ->where('is_active', true)
                ->with([
                    'brand:id,name',
                    'category:id,name',
                    'images' => fn ($query) => $query->orderByDesc('is_main')->orderBy('sort_order')->orderBy('id'),
                ])
                ->get()
                ->map(function (Product $product): array {
                    $name = $this->normalize((string) $product->name);
                    $extra = $this->normalize(trim(($product->brand?->name ?? '') . ' ' . ($product->category?->name ?? '')));
                    $price = $product->discounted_price ?: $product->price;

                    return [
                        'name' => $name,
                        'article' => $this->normalize((string) $product->article),
                        'tokens' => array_values(array_unique(array_filter(explode(' ', $name . ' ' . $extra)))),
                        'product' => [
                            'id' => (int) $product->id,
                            'name' => (string) $product->name,
                            'brand' => (string) ($product->brand?->name ?? ''),
                            'direction' => (string) ($product->direction ?? ''),
                            'article' => (string) $product->article,
                            'slug' => $product->slug ?: (string) $product->id,
                            'price' => (float) $price,
                            'image' => $product->images->first()?->file_path,
                            'in_stock' => ($product->availability_status ?? 'in_stock') === 'in_stock',
                        ],
                    ];
                })
                ->values()
                ->all();
        });
    }
}
