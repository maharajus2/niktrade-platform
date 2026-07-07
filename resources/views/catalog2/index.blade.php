@php
    use App\Models\Product;
    use App\Support\ProductDisplayFormatter;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Storage;

    $imageUrl = function (?string $path): string {
        return $path ? Storage::disk('public')->url($path) : asset('images/logo-icon.png');
    };

    $productUrl = fn (Product $product): string => route('catalog.show', $product->slug ?: $product->id);
    $formatPrice = fn ($price): string => number_format((float) $price, 0, ',', ' ') . ' ₽';

    $productVolume = function (Product $product): ?string {
        return ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit)
            ?: ProductDisplayFormatter::formatWeight($product->weight_value, $product->weight_unit);
    };

    $cartQuantity = 0;

    if (request()->hasSession()) {
        $cartQuantity = (int) \App\Models\CartItem::query()
            ->whereHas('cart', fn ($query) => $query
                ->where('session_id', request()->session()->getId())
                ->where('status', 'active'))
            ->sum('quantity');
    }

    $productImagePaths = $products
        ->flatMap(fn (Product $product) => $product->images->pluck('file_path'))
        ->filter()
        ->values();

    $visualImage = function (int $index = 0) use ($productImagePaths): ?string {
        if ($productImagePaths->isEmpty()) {
            return null;
        }

        return $productImagePaths->get($index % $productImagePaths->count());
    };

    $heroImage = $productImagePaths->skip(1)->first() ?: $productImagePaths->first();
    $featured = $products->take(10)->values();
    $heroBrandNames = ['hiberg' => 'HIBERG', 'arvetera' => 'ARVETERA'];

    $categoryFallbacks = [
        'Для стирки',
        'Для уборки дома',
        'Для посуды',
        'Автохимия',
        'HoReCa',
        'Дезинфекция',
    ];

    $categoryCards = $categories->take(6)->map(function ($category, int $index) use ($categoryProducts, $imageUrl, $visualImage) {
        $product = $categoryProducts->get($category->id);

        return [
            'name' => $category->name,
            'url' => route('catalog.index', ['category' => $category->id]),
            'image' => $imageUrl($product?->images?->first()?->file_path ?? $visualImage($index)),
        ];
    })->values();

    foreach ($categoryFallbacks as $fallback) {
        if ($categoryCards->count() >= 6) {
            break;
        }

        $product = $products->get($categoryCards->count());
        $categoryCards->push([
            'name' => $fallback,
            'url' => '#products',
            'image' => $imageUrl($product?->images?->first()?->file_path ?? $visualImage($categoryCards->count())),
        ]);
    }

    $brandCardData = collect($heroBrandNames)->map(function (string $label, string $key) use ($brandProducts, $brands, $imageUrl, $visualImage) {
        $brand = $brands->first(fn ($brand) => str_contains(mb_strtolower($brand->name), $key));
        $items = $brandProducts->first(fn ($items, $name) => str_contains($name, $key)) ?? collect();
        return [
            'key' => $key,
            'label' => $brand?->name ?: $label,
            'tagline' => $key === 'hiberg' ? 'Качество на каждый день' : 'Доступно и эффективно',
            'url' => $brand ? route('catalog.index', ['brand' => $brand->id]) : route('catalog.preview', ['brand' => $key]),
            'image' => $imageUrl($items->first()?->images?->last()?->file_path ?? $visualImage($key === 'hiberg' ? 1 : 2)),
        ];
    })->values();

    $promoImages = $products->filter(fn (Product $product): bool => $product->images->isNotEmpty())->values();
@endphp

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог 2 - НИКТРЕЙД</title>
    <meta name="description" content="Экспериментальный каталог бытовой химии НИКТРЕЙД в стиле Liquid Glass.">
    <style>
        .nik-catalog2,
        .nik-catalog2 * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
        }

        .nik-catalog2 {
            min-height: 100vh;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 10% 6%, rgba(0, 142, 255, .20), transparent 24rem),
                radial-gradient(circle at 88% 7%, rgba(11, 188, 199, .17), transparent 22rem),
                linear-gradient(180deg, #eaf7ff 0%, #f5fbff 36%, #e8f6ff 100%);
            color: #10223f;
            font-family: Inter, Arial, sans-serif;
            letter-spacing: 0;
        }

        .nik-catalog2 a {
            color: inherit;
            text-decoration: none;
        }

        .nik-catalog2 button,
        .nik-catalog2 input {
            font: inherit;
        }

        .nik-catalog2 img {
            max-width: 100%;
        }

        .nik-catalog2::before,
        .nik-catalog2::after {
            position: fixed;
            inset: 0;
            pointer-events: none;
            content: "";
        }

        .nik-catalog2::before {
            z-index: 0;
            background:
                radial-gradient(circle at 2% 12%, rgba(255,255,255,.86) 0 18px, transparent 19px),
                radial-gradient(circle at 46% 7%, rgba(255,255,255,.64) 0 16px, transparent 17px),
                radial-gradient(circle at 96% 18%, rgba(255,255,255,.62) 0 22px, transparent 23px),
                radial-gradient(circle at 34% 38%, rgba(255,255,255,.42) 0 13px, transparent 14px);
            opacity: .82;
        }

        .nik-catalog2::after {
            z-index: 0;
            background-image:
                linear-gradient(120deg, rgba(255,255,255,.28), transparent 18%),
                radial-gradient(circle at 50% 0%, rgba(255,255,255,.68), transparent 28rem);
        }

        .nik-catalog2-shell {
            position: relative;
            z-index: 1;
            width: min(1440px, calc(100% - 48px));
            margin: 0 auto;
            padding: 18px 0 28px;
        }

        .nik-catalog2-glass {
            border: 1px solid rgba(255, 255, 255, .72);
            background: rgba(255, 255, 255, .48);
            box-shadow: 0 24px 70px rgba(33, 105, 168, .15), inset 0 1px 0 rgba(255,255,255,.78);
            backdrop-filter: blur(24px) saturate(1.18);
        }

        .nik-catalog2-header {
            position: sticky;
            top: 10px;
            z-index: 20;
            display: grid;
            grid-template-columns: 235px minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
            margin-bottom: 22px;
        }

        .nik-catalog2-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nik-catalog2-logo img {
            width: 204px;
            height: auto;
            filter: drop-shadow(0 8px 16px rgba(30, 107, 178, .12));
        }

        .nik-catalog2-nav {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .nik-catalog2-catalog-btn,
        .nik-catalog2-nav a,
        .nik-catalog2-icon-link,
        .nik-catalog2-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,.68);
            border-radius: 18px;
            background: rgba(255,255,255,.52);
            color: #10223f;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 12px 30px rgba(39, 99, 159, .10), inset 0 1px 0 rgba(255,255,255,.78);
            backdrop-filter: blur(18px);
        }

        .nik-catalog2-catalog-btn {
            min-width: 136px;
            min-height: 48px;
            gap: 12px;
            border-color: rgba(0, 127, 255, .28);
            background: linear-gradient(135deg, #0b86ff, #006eea);
            color: #fff;
            box-shadow: 0 16px 36px rgba(0, 112, 235, .26);
        }

        .nik-catalog2-catalog-btn span {
            display: grid;
            gap: 4px;
        }

        .nik-catalog2-catalog-btn i {
            display: block;
            width: 18px;
            height: 2px;
            border-radius: 99px;
            background: currentColor;
        }

        .nik-catalog2-nav a {
            min-height: 44px;
            padding: 0 14px;
            white-space: nowrap;
        }

        .nik-catalog2-head-tools {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nik-catalog2-phone {
            display: grid;
            gap: 2px;
            color: #10223f;
            font-size: 16px;
            font-weight: 900;
            white-space: nowrap;
        }

        .nik-catalog2-phone small {
            color: rgba(16, 34, 63, .58);
            font-size: 11px;
            font-weight: 700;
        }

        .nik-catalog2-icon-link {
            position: relative;
            width: 44px;
            height: 44px;
            border-radius: 999px;
        }

        .nik-catalog2-icon-link svg {
            width: 21px;
            height: 21px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
        }

        .nik-catalog2-cart-count {
            position: absolute;
            top: -6px;
            right: -4px;
            display: inline-flex;
            min-width: 18px;
            height: 18px;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255,255,255,.9);
            border-radius: 999px;
            background: #0b86ff;
            color: #fff;
            font-size: 10px;
            font-weight: 900;
        }

        .nik-catalog2-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.38fr) minmax(360px, .95fr);
            gap: 18px;
            margin-bottom: 18px;
        }

        .nik-catalog2-hero {
            position: relative;
            min-height: 354px;
            overflow: hidden;
            border-radius: 32px;
            padding: 46px 48px;
        }

        .nik-catalog2-hero::before {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 72% 42%, rgba(0, 132, 255, .18), transparent 19rem),
                radial-gradient(circle at 18% 12%, rgba(255,255,255,.88), transparent 18rem);
            content: "";
        }

        .nik-catalog2-hero-copy {
            position: relative;
            z-index: 2;
            width: min(560px, 64%);
        }

        .nik-catalog2-eyebrow {
            color: rgba(16, 34, 63, .68);
            font-size: 13px;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .nik-catalog2-hero h1 {
            margin: 14px 0 8px;
            color: #10223f;
            font-size: clamp(42px, 4.4vw, 68px);
            font-weight: 950;
            line-height: .96;
        }

        .nik-catalog2-hero h2 {
            margin: 0;
            color: #0b86ff;
            font-size: clamp(24px, 2.1vw, 34px);
            font-weight: 900;
            line-height: 1.12;
        }

        .nik-catalog2-features {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin: 34px 0 26px;
        }

        .nik-catalog2-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #10223f;
            font-size: 12px;
            font-weight: 850;
            line-height: 1.25;
        }

        .nik-catalog2-feature i,
        .nik-catalog2-advantage i,
        .nik-catalog2-why i {
            display: inline-flex;
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: rgba(0, 132, 255, .10);
            color: #0b86ff;
        }

        .nik-catalog2-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .nik-catalog2-action {
            min-height: 46px;
            padding: 0 26px;
        }

        .nik-catalog2-action.is-primary {
            border-color: rgba(0, 127, 255, .24);
            background: linear-gradient(135deg, #0b86ff, #006eea);
            color: #fff;
            box-shadow: 0 18px 38px rgba(0, 112, 235, .26);
        }

        .nik-catalog2-hero-product {
            position: absolute;
            right: 6%;
            bottom: 2%;
            z-index: 1;
            width: min(34%, 310px);
            max-height: 88%;
            object-fit: contain;
            filter: drop-shadow(0 30px 42px rgba(0, 81, 149, .24));
        }

        .nik-catalog2-bubble {
            position: absolute;
            border: 1px solid rgba(255,255,255,.78);
            border-radius: 999px;
            background: radial-gradient(circle at 35% 28%, rgba(255,255,255,.98), rgba(255,255,255,.14) 58%, rgba(0, 132, 255, .08));
            box-shadow: inset 0 2px 10px rgba(255,255,255,.78), 0 10px 28px rgba(0, 112, 235, .10);
        }

        .nik-catalog2-bubble.is-1 { top: 44px; right: 188px; width: 46px; height: 46px; }
        .nik-catalog2-bubble.is-2 { bottom: 88px; left: 38px; width: 28px; height: 28px; }
        .nik-catalog2-bubble.is-3 { bottom: 42px; right: 310px; width: 68px; height: 68px; opacity: .75; }

        .nik-catalog2-brand-stack {
            display: grid;
            gap: 18px;
        }

        .nik-catalog2-brand-card {
            position: relative;
            min-height: 168px;
            overflow: hidden;
            border-radius: 28px;
            padding: 30px;
        }

        .nik-catalog2-brand-card.is-arvetera {
            background: linear-gradient(135deg, rgba(228, 255, 241, .74), rgba(255,255,255,.48));
        }

        .nik-catalog2-brand-card h3 {
            position: relative;
            z-index: 2;
            margin: 0;
            font-size: 38px;
            font-weight: 950;
            line-height: 1;
        }

        .nik-catalog2-brand-card.is-arvetera h3 {
            color: #128357;
        }

        .nik-catalog2-brand-card p {
            position: relative;
            z-index: 2;
            margin: 10px 0 18px;
            font-size: 15px;
            font-weight: 850;
        }

        .nik-catalog2-brand-card a {
            position: relative;
            z-index: 2;
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            border: 1px solid rgba(255,255,255,.76);
            border-radius: 14px;
            background: rgba(255,255,255,.66);
            padding: 0 22px;
            color: #10223f;
            font-size: 13px;
            font-weight: 900;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.78);
        }

        .nik-catalog2-brand-card img {
            position: absolute;
            right: 12px;
            bottom: 8px;
            width: 42%;
            max-height: 145px;
            object-fit: contain;
            filter: drop-shadow(0 18px 24px rgba(34, 84, 138, .16));
        }

        .nik-catalog2-categories,
        .nik-catalog2-products,
        .nik-catalog2-advantages,
        .nik-catalog2-promos,
        .nik-catalog2-info-grid,
        .nik-catalog2-news,
        .nik-catalog2-footer {
            margin-top: 18px;
        }

        .nik-catalog2-categories {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 12px;
        }

        .nik-catalog2-category-card {
            display: grid;
            min-height: 146px;
            place-items: center;
            gap: 8px;
            border-radius: 22px;
            padding: 16px;
            text-align: center;
        }

        .nik-catalog2-category-card img {
            width: 86px;
            height: 76px;
            object-fit: contain;
            filter: drop-shadow(0 12px 15px rgba(33, 91, 145, .15));
        }

        .nik-catalog2-category-card strong {
            font-size: 14px;
            font-weight: 900;
        }

        .nik-catalog2-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 8px 0 12px;
        }

        .nik-catalog2-section-head h2 {
            margin: 0;
            color: #10223f;
            font-size: 28px;
            font-weight: 950;
        }

        .nik-catalog2-section-head a {
            color: #0b86ff;
            font-size: 13px;
            font-weight: 900;
        }

        .nik-catalog2-product-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 14px;
        }

        .nik-catalog2-product-card {
            position: relative;
            display: flex;
            min-height: 282px;
            flex-direction: column;
            overflow: hidden;
            border-radius: 22px;
            padding: 14px;
        }

        .nik-catalog2-product-image {
            position: relative;
            display: flex;
            height: 145px;
            align-items: center;
            justify-content: center;
        }

        .nik-catalog2-product-image img {
            width: 100%;
            height: 132px;
            object-fit: contain;
            transition: opacity .16s ease, transform .16s ease;
            filter: drop-shadow(0 16px 18px rgba(33, 91, 145, .14));
        }

        .nik-catalog2-product-card:hover .nik-catalog2-product-image img {
            transform: translateY(-2px) scale(1.03);
        }

        .nik-catalog2-badge,
        .nik-catalog2-favorite {
            position: absolute;
            z-index: 2;
            top: 13px;
        }

        .nik-catalog2-badge {
            left: 13px;
            border-radius: 8px;
            background: linear-gradient(135deg, #7c3aed, #2563eb);
            padding: 7px 9px;
            color: #fff;
            font-size: 11px;
            font-weight: 950;
        }

        .nik-catalog2-badge.is-sale {
            background: linear-gradient(135deg, #ff4d6d, #f97316);
        }

        .nik-catalog2-badge.is-new {
            background: linear-gradient(135deg, #0ea5e9, #0b86ff);
        }

        .nik-catalog2-favorite {
            right: 13px;
            width: 32px;
            height: 32px;
            border: 0;
            border-radius: 999px;
            background: rgba(255,255,255,.58);
            color: #477096;
            cursor: pointer;
        }

        .nik-catalog2-dots {
            display: flex;
            justify-content: center;
            gap: 5px;
            min-height: 8px;
        }

        .nik-catalog2-dots span {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: rgba(0, 126, 235, .20);
        }

        .nik-catalog2-dots span.is-active {
            background: #0b86ff;
        }

        .nik-catalog2-product-meta {
            display: grid;
            gap: 5px;
            margin-top: auto;
        }

        .nik-catalog2-product-meta small {
            color: #12906d;
            font-size: 11px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .nik-catalog2-product-meta strong {
            min-height: 36px;
            color: #10223f;
            font-size: 14px;
            font-weight: 900;
            line-height: 1.2;
        }

        .nik-catalog2-product-meta em {
            color: rgba(16,34,63,.58);
            font-size: 12px;
            font-style: normal;
            font-weight: 700;
        }

        .nik-catalog2-price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 8px;
        }

        .nik-catalog2-price {
            display: flex;
            align-items: baseline;
            gap: 8px;
            color: #10223f;
            font-size: 22px;
            font-weight: 950;
        }

        .nik-catalog2-price del {
            color: rgba(16,34,63,.45);
            font-size: 13px;
            font-weight: 800;
        }

        .nik-catalog2-cart-btn {
            display: inline-flex;
            width: 42px;
            height: 42px;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 13px;
            background: linear-gradient(135deg, #0b86ff, #006eea);
            color: #fff;
            cursor: pointer;
            box-shadow: 0 14px 24px rgba(0, 112, 235, .28);
        }

        .nik-catalog2-advantages {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
            border-radius: 20px;
            padding: 14px;
        }

        .nik-catalog2-advantage {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 850;
        }

        .nik-catalog2-promos {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .nik-catalog2-promo {
            position: relative;
            min-height: 138px;
            overflow: hidden;
            border-radius: 22px;
            padding: 24px;
        }

        .nik-catalog2-promo.is-sale {
            background: linear-gradient(135deg, rgba(255, 242, 221, .72), rgba(255,255,255,.54));
        }

        .nik-catalog2-promo.is-new {
            background: linear-gradient(135deg, rgba(219, 255, 249, .72), rgba(255,255,255,.54));
        }

        .nik-catalog2-promo.is-b2b {
            background: linear-gradient(135deg, rgba(224, 241, 255, .74), rgba(255,255,255,.54));
        }

        .nik-catalog2-promo span {
            color: #0b86ff;
            font-size: 15px;
            font-weight: 950;
        }

        .nik-catalog2-promo h3 {
            position: relative;
            z-index: 2;
            margin: 8px 0 18px;
            font-size: 24px;
            font-weight: 950;
        }

        .nik-catalog2-promo a {
            position: relative;
            z-index: 2;
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            border: 1px solid rgba(255,255,255,.74);
            border-radius: 12px;
            background: rgba(255,255,255,.58);
            padding: 0 16px;
            font-size: 12px;
            font-weight: 900;
        }

        .nik-catalog2-promo img {
            position: absolute;
            right: 14px;
            bottom: 8px;
            width: 34%;
            max-height: 128px;
            object-fit: contain;
            filter: drop-shadow(0 14px 16px rgba(33,91,145,.14));
        }

        .nik-catalog2-info-grid {
            display: grid;
            grid-template-columns: .86fr 1.1fr .86fr;
            gap: 14px;
        }

        .nik-catalog2-panel {
            overflow: hidden;
            border-radius: 24px;
            padding: 24px;
        }

        .nik-catalog2-brand-list {
            display: grid;
            gap: 12px;
        }

        .nik-catalog2-brand-mini {
            position: relative;
            min-height: 112px;
            overflow: hidden;
            border-radius: 18px;
            padding: 20px;
            background: rgba(255,255,255,.42);
        }

        .nik-catalog2-brand-mini strong {
            display: block;
            font-size: 24px;
            font-weight: 950;
        }

        .nik-catalog2-brand-mini small {
            color: rgba(16,34,63,.68);
            font-weight: 800;
        }

        .nik-catalog2-brand-mini img {
            position: absolute;
            right: 12px;
            bottom: 8px;
            width: 42%;
            max-height: 92px;
            object-fit: contain;
        }

        .nik-catalog2-panel h2,
        .nik-catalog2-panel h3 {
            margin: 0 0 14px;
            font-size: 24px;
            font-weight: 950;
        }

        .nik-catalog2-about p,
        .nik-catalog2-news p {
            color: rgba(16,34,63,.70);
            font-size: 14px;
            font-weight: 700;
            line-height: 1.6;
        }

        .nik-catalog2-about-visual {
            display: flex;
            min-height: 136px;
            align-items: flex-end;
            justify-content: flex-end;
            margin-top: 10px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(221, 244, 255, .72), rgba(255,255,255,.52));
            padding: 12px;
        }

        .nik-catalog2-about-visual img {
            width: min(360px, 75%);
            max-height: 130px;
            object-fit: contain;
        }

        .nik-catalog2-why-list {
            display: grid;
            gap: 14px;
        }

        .nik-catalog2-why {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            font-size: 14px;
            font-weight: 850;
            line-height: 1.35;
        }

        .nik-catalog2-news {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            border-radius: 24px;
            padding: 18px;
        }

        .nik-catalog2-news h2 {
            grid-column: 1 / -1;
            margin: 0;
            font-size: 24px;
            font-weight: 950;
        }

        .nik-catalog2-news-card {
            display: grid;
            gap: 8px;
            border-radius: 18px;
            background: rgba(255,255,255,.42);
            padding: 12px;
        }

        .nik-catalog2-news-card img {
            width: 100%;
            height: 86px;
            object-fit: contain;
            border-radius: 13px;
            background: rgba(255,255,255,.45);
        }

        .nik-catalog2-news-card time,
        .nik-catalog2-news-card a {
            color: #0b86ff;
            font-size: 12px;
            font-weight: 900;
        }

        .nik-catalog2-news-card strong {
            font-size: 14px;
            font-weight: 950;
            line-height: 1.28;
        }

        .nik-catalog2-footer {
            display: grid;
            grid-template-columns: 1.2fr 1.4fr 1fr;
            gap: 20px;
            border-radius: 24px;
            padding: 24px;
        }

        .nik-catalog2-subscribe {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nik-catalog2-subscribe input {
            width: 100%;
            min-height: 44px;
            border: 1px solid rgba(255,255,255,.72);
            border-radius: 14px;
            background: rgba(255,255,255,.62);
            padding: 0 14px;
            color: #10223f;
            outline: 0;
        }

        .nik-catalog2-subscribe button {
            min-height: 44px;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(135deg, #0b86ff, #006eea);
            padding: 0 20px;
            color: #fff;
            font-weight: 900;
        }

        .nik-catalog2-footer-links {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .nik-catalog2-footer-links strong,
        .nik-catalog2-contact strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 950;
        }

        .nik-catalog2-footer-links a,
        .nik-catalog2-contact span {
            display: block;
            margin-top: 6px;
            color: rgba(16,34,63,.68);
            font-size: 13px;
            font-weight: 750;
        }

        @media (max-width: 1180px) {
            .nik-catalog2-header {
                grid-template-columns: 190px 1fr;
            }

            .nik-catalog2-head-tools {
                grid-column: 1 / -1;
                justify-content: flex-end;
            }

            .nik-catalog2-nav {
                overflow-x: auto;
                padding-bottom: 4px;
                scrollbar-width: none;
            }

            .nik-catalog2-nav::-webkit-scrollbar {
                display: none;
            }

            .nik-catalog2-hero-grid,
            .nik-catalog2-info-grid {
                grid-template-columns: 1fr;
            }

            .nik-catalog2-categories {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .nik-catalog2-product-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .nik-catalog2-advantages {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .nik-catalog2-shell {
                width: min(100% - 24px, 1440px);
                padding-top: 12px;
            }

            .nik-catalog2-header {
                position: static;
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .nik-catalog2-logo img {
                width: 160px;
            }

            .nik-catalog2-nav {
                margin: 0 -12px;
                padding: 0 12px 6px;
            }

            .nik-catalog2-head-tools {
                justify-content: space-between;
            }

            .nik-catalog2-phone {
                font-size: 13px;
            }

            .nik-catalog2-hero {
                min-height: auto;
                border-radius: 26px;
                padding: 26px 22px 220px;
            }

            .nik-catalog2-hero-copy {
                width: 100%;
            }

            .nik-catalog2-hero h1 {
                font-size: clamp(29px, 7.4vw, 32px);
                line-height: 1.04;
            }

            .nik-catalog2-hero h2 {
                font-size: 23px;
            }

            .nik-catalog2-features {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .nik-catalog2-hero-product {
                right: 18%;
                bottom: 14px;
                width: 52%;
                max-height: 210px;
            }

            .nik-catalog2-brand-card,
            .nik-catalog2-panel,
            .nik-catalog2-news,
            .nik-catalog2-footer {
                border-radius: 22px;
            }

            .nik-catalog2-categories {
                display: flex;
                overflow-x: auto;
                margin-right: -12px;
                padding-right: 12px;
                scroll-snap-type: x mandatory;
                scrollbar-width: none;
            }

            .nik-catalog2-categories::-webkit-scrollbar {
                display: none;
            }

            .nik-catalog2-category-card {
                min-width: 146px;
                scroll-snap-align: start;
            }

            .nik-catalog2-product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .nik-catalog2-product-card {
                min-height: 264px;
                padding: 11px;
            }

            .nik-catalog2-product-image {
                height: 124px;
            }

            .nik-catalog2-product-image img {
                height: 112px;
            }

            .nik-catalog2-product-meta strong {
                font-size: 13px;
            }

            .nik-catalog2-price {
                font-size: 18px;
            }

            .nik-catalog2-advantages,
            .nik-catalog2-promos,
            .nik-catalog2-news,
            .nik-catalog2-footer,
            .nik-catalog2-footer-links {
                grid-template-columns: 1fr;
            }

            .nik-catalog2-advantage {
                min-height: 44px;
            }

            .nik-catalog2-promo img {
                width: 36%;
            }
        }
    </style>
</head>
<body>
<div class="nik-catalog2">
    <div class="nik-catalog2-shell">
        <header class="nik-catalog2-header" aria-label="Навигация">
            <a class="nik-catalog2-logo" href="{{ route('catalog.preview') }}" aria-label="НИКТРЕЙД">
                <img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД">
            </a>

            <nav class="nik-catalog2-nav">
                <a class="nik-catalog2-catalog-btn" href="#products">
                    Каталог
                    <span aria-hidden="true"><i></i><i></i><i></i></span>
                </a>
                <a href="#brands">Бренды</a>
                <a href="#categories">Категории</a>
                <a href="#promos">Акции</a>
                <a href="#products">Новинки</a>
                <a href="#about">О компании</a>
                <a href="#footer">Доставка и оплата</a>
            </nav>

            <div class="nik-catalog2-head-tools">
                <div class="nik-catalog2-phone">
                    8 (800) 555-35-35
                    <small>Ежедневно с 9:00 до 18:00</small>
                </div>
                <a class="nik-catalog2-icon-link" href="#" aria-label="Избранное">
                    <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                </a>
                <a class="nik-catalog2-icon-link" href="{{ Auth::guard('customer')->check() ? route('customer.account') : route('customer.login') }}" aria-label="Аккаунт">
                    <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                </a>
                <a class="nik-catalog2-icon-link" href="{{ route('cart.index') }}" aria-label="Корзина">
                    <svg viewBox="0 0 24 24"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                    <span class="nik-catalog2-cart-count">{{ $cartQuantity }}</span>
                </a>
            </div>
        </header>

        <section class="nik-catalog2-hero-grid">
            <div class="nik-catalog2-hero nik-catalog2-glass">
                <span class="nik-catalog2-bubble is-1"></span>
                <span class="nik-catalog2-bubble is-2"></span>
                <span class="nik-catalog2-bubble is-3"></span>
                <div class="nik-catalog2-hero-copy">
                    <div class="nik-catalog2-eyebrow">Производитель бытовой химии</div>
                    <h1>Профессиональная бытовая химия</h1>
                    <h2>для дома, бизнеса и автомобиля</h2>
                    <div class="nik-catalog2-features">
                        @foreach (['Собственное производство', 'Высокое качество', 'Выгодные цены', 'Быстрая доставка по России'] as $feature)
                            <div class="nik-catalog2-feature"><i>✦</i><span>{{ $feature }}</span></div>
                        @endforeach
                    </div>
                    <div class="nik-catalog2-actions">
                        <a class="nik-catalog2-action is-primary" href="#products">Перейти в каталог</a>
                        <a class="nik-catalog2-action" href="#promos">Смотреть акции</a>
                    </div>
                </div>
                @if ($heroImage)
                    <img class="nik-catalog2-hero-product" src="{{ $imageUrl($heroImage) }}" alt="{{ $heroProduct?->name }}">
                @endif
            </div>

            <div class="nik-catalog2-brand-stack">
                @foreach ($brandCardData as $brandCard)
                    <article class="nik-catalog2-brand-card nik-catalog2-glass is-{{ $brandCard['key'] }}">
                        <h3>{{ $brandCard['label'] }}</h3>
                        <p>{{ $brandCard['tagline'] }}</p>
                        <a href="{{ $brandCard['url'] }}">Смотреть товары</a>
                        <img src="{{ $brandCard['image'] }}" alt="{{ $brandCard['label'] }}">
                    </article>
                @endforeach
            </div>
        </section>

        <section id="categories" class="nik-catalog2-categories">
            @foreach ($categoryCards as $category)
                <a class="nik-catalog2-category-card nik-catalog2-glass" href="{{ $category['url'] }}">
                    <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}">
                    <strong>{{ $category['name'] }}</strong>
                </a>
            @endforeach
            <a class="nik-catalog2-category-card nik-catalog2-glass" href="{{ route('catalog.index') }}">
                <img src="{{ $imageUrl($heroImage) }}" alt="Все категории">
                <strong>Все категории</strong>
            </a>
        </section>

        <section id="products" class="nik-catalog2-products">
            <div class="nik-catalog2-section-head">
                <h2>Популярные товары</h2>
                <a href="{{ route('catalog.index') }}">Смотреть все →</a>
            </div>
            <div class="nik-catalog2-product-grid">
                @forelse ($featured->take(12) as $product)
                    @php
                        $images = $product->images->pluck('file_path')->filter()->map($imageUrl)->values();
                        if ($images->isEmpty()) {
                            $images = collect([asset('images/logo-icon.png')]);
                        }
                        $price = $product->discounted_price ?: $product->price;
                        $hasSale = $product->discounted_price !== null && (float) $product->discounted_price < (float) $product->price;
                    @endphp
                    <article class="nik-catalog2-product-card nik-catalog2-glass" id="catalog2-product-{{ $product->id }}" data-catalog2-product-card data-images='@json($images)'>
                        @if ($hasSale)
                            <span class="nik-catalog2-badge is-sale">Акция</span>
                        @elseif ($product->is_new)
                            <span class="nik-catalog2-badge is-new">Новинка</span>
                        @elseif ($product->is_best_seller || $product->is_featured)
                            <span class="nik-catalog2-badge">Хит</span>
                        @endif
                        <button class="nik-catalog2-favorite" type="button" aria-label="В избранное">♡</button>
                        <a class="nik-catalog2-product-image" href="{{ $productUrl($product) }}" data-catalog2-image-zone>
                            <img src="{{ $images->first() }}" alt="{{ $product->name }}" data-catalog2-image>
                        </a>
                        <div class="nik-catalog2-dots" data-catalog2-dots>
                            @foreach ($images as $index => $image)
                                <span class="{{ $index === 0 ? 'is-active' : '' }}"></span>
                            @endforeach
                        </div>
                        <a class="nik-catalog2-product-meta" href="{{ $productUrl($product) }}">
                            <small>{{ $product->brand?->name ?: 'НИКТРЕЙД' }}</small>
                            <strong>{{ $product->name }}</strong>
                            @if ($productVolume($product))
                                <em>{{ $productVolume($product) }}</em>
                            @endif
                        </a>
                        <div class="nik-catalog2-price-row">
                            <div class="nik-catalog2-price">
                                {{ $formatPrice($price) }}
                                @if ($hasSale)
                                    <del>{{ $formatPrice($product->price) }}</del>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('cart.add', $product->slug ?: $product->id) }}">
                                @csrf
                                <input type="hidden" name="redirect_anchor" value="catalog2-product-{{ $product->id }}">
                                <button class="nik-catalog2-cart-btn" type="submit" aria-label="Добавить в корзину">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="nik-catalog2-panel nik-catalog2-glass">Активные товары с фотографиями пока не найдены.</div>
                @endforelse
            </div>
        </section>

        <section class="nik-catalog2-advantages nik-catalog2-glass">
            @foreach (['Собственное производство', 'Контроль качества', 'Сертифицированная продукция', 'Выгодные оптовые цены', 'Доставка по всей России', 'Поддержка клиентов'] as $advantage)
                <div class="nik-catalog2-advantage"><i>✧</i><span>{{ $advantage }}</span></div>
            @endforeach
        </section>

        <section id="promos" class="nik-catalog2-promos">
            @foreach ([
                ['tone' => 'sale', 'label' => 'Акции и спецпредложения', 'title' => 'Скидки до -30%', 'button' => 'Смотреть все акции', 'image' => $promoImages->get(1)?->images?->first()?->file_path],
                ['tone' => 'new', 'label' => 'Новинки', 'title' => 'Попробуйте первыми!', 'button' => 'Смотреть новинки', 'image' => $promoImages->get(2)?->images?->first()?->file_path],
                ['tone' => 'b2b', 'label' => 'Для бизнеса и HoReCa', 'title' => 'Оптом выгоднее!', 'button' => 'Перейти в раздел', 'image' => $promoImages->get(3)?->images?->first()?->file_path],
            ] as $promo)
                <article class="nik-catalog2-promo nik-catalog2-glass is-{{ $promo['tone'] }}">
                    <span>{{ $promo['label'] }}</span>
                    <h3>{{ $promo['title'] }}</h3>
                    <a href="#products">{{ $promo['button'] }}</a>
                    <img src="{{ $imageUrl($promo['image'] ?? $heroImage) }}" alt="{{ $promo['label'] }}">
                </article>
            @endforeach
        </section>

        <section id="brands" class="nik-catalog2-info-grid">
            <article class="nik-catalog2-panel nik-catalog2-glass">
                <div class="nik-catalog2-section-head">
                    <h2>Наши бренды</h2>
                    <a href="{{ route('catalog.index') }}">Смотреть все →</a>
                </div>
                <div class="nik-catalog2-brand-list">
                    @foreach ($brandCardData as $brandCard)
                        <a class="nik-catalog2-brand-mini" href="{{ $brandCard['url'] }}">
                            <strong>{{ $brandCard['label'] }}</strong>
                            <small>{{ $brandCard['tagline'] }}</small>
                            <span>В каталог →</span>
                            <img src="{{ $brandCard['image'] }}" alt="{{ $brandCard['label'] }}">
                        </a>
                    @endforeach
                </div>
            </article>

            <article id="about" class="nik-catalog2-panel nik-catalog2-glass nik-catalog2-about">
                <h2>О компании НИКТРЕЙД</h2>
                <p>Мы производим и поставляем качественную бытовую и профессиональную химию под собственными брендами HIBERG и ARVETERA. Сотрудничаем с партнёрами по всей России и странам СНГ.</p>
                <a class="nik-catalog2-action" href="#">Подробнее о компании</a>
                <div class="nik-catalog2-about-visual">
                    <img src="{{ $imageUrl($promoImages->get(4)?->images?->first()?->file_path ?? $heroImage) }}" alt="Продукция НИКТРЕЙД">
                </div>
            </article>

            <article class="nik-catalog2-panel nik-catalog2-glass">
                <h3>Почему выбирают нас</h3>
                <div class="nik-catalog2-why-list">
                    @foreach ([
                        'Современное оборудование и собственные лаборатории',
                        'Строгий контроль качества на всех этапах',
                        'Разработка рецептур и инновационные решения',
                        'Индивидуальный подход к каждому клиенту',
                    ] as $item)
                        <div class="nik-catalog2-why"><i>✦</i><span>{{ $item }}</span></div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="nik-catalog2-news nik-catalog2-glass">
            <h2>Новости компании</h2>
            @foreach ([
                ['date' => '15 мая 2024', 'title' => 'Запуск новой производственной линии'],
                ['date' => '5 мая 2024', 'title' => 'Участие в выставке CleanExpo 2024'],
                ['date' => '28 апреля 2024', 'title' => 'Новый сертификат качества ISO 9001'],
            ] as $index => $news)
                <article class="nik-catalog2-news-card">
                    <img src="{{ $imageUrl($promoImages->get($index + 5)?->images?->first()?->file_path ?? $heroImage) }}" alt="{{ $news['title'] }}">
                    <time>{{ $news['date'] }}</time>
                    <strong>{{ $news['title'] }}</strong>
                    <a href="#">Читать далее →</a>
                </article>
            @endforeach
        </section>

        <footer id="footer" class="nik-catalog2-footer nik-catalog2-glass">
            <div class="nik-catalog2-contact">
                <strong>8 (800) 555-35-35</strong>
                <span>Ежедневно с 9:00 до 18:00</span>
                <img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД" style="width: 160px; margin-top: 18px;">
                <span>© {{ date('Y') }} НИКТРЕЙД. Все права защищены.</span>
            </div>

            <div>
                <strong>Будьте в курсе новинок и акций</strong>
                <p style="margin: 8px 0 12px; color: rgba(16,34,63,.68); font-size: 13px; font-weight: 750;">Подпишитесь на рассылку и получайте лучшие предложения первыми.</p>
                <form class="nik-catalog2-subscribe" action="#" onsubmit="return false;">
                    <input type="email" placeholder="Ваш e-mail" aria-label="Ваш e-mail">
                    <button type="submit">Подписаться</button>
                </form>
            </div>

            <div class="nik-catalog2-footer-links">
                <div>
                    <strong>Каталог</strong>
                    <a href="{{ route('catalog.index') }}">Все товары</a>
                    <a href="#brands">Бренды</a>
                    <a href="#categories">Категории</a>
                </div>
                <div>
                    <strong>Покупателям</strong>
                    <a href="#footer">Доставка и оплата</a>
                    <a href="#footer">Возврат и обмен</a>
                    <a href="#footer">Частые вопросы</a>
                </div>
                <div>
                    <strong>Компания</strong>
                    <a href="#about">О компании</a>
                    <a href="#about">Производство</a>
                    <a href="#footer">Контакты</a>
                </div>
            </div>
        </footer>
    </div>
</div>

<script>
    (() => {
        const isCoarse = window.matchMedia('(pointer: coarse)').matches;

        if (isCoarse) {
            return;
        }

        document.querySelectorAll('[data-catalog2-product-card]').forEach((card) => {
            const images = JSON.parse(card.dataset.images || '[]');
            const image = card.querySelector('[data-catalog2-image]');
            const zone = card.querySelector('[data-catalog2-image-zone]');
            const dots = [...card.querySelectorAll('[data-catalog2-dots] span')];

            if (!image || !zone || images.length < 2) {
                return;
            }

            const setIndex = (index) => {
                image.src = images[index];
                dots.forEach((dot, dotIndex) => dot.classList.toggle('is-active', dotIndex === index));
            };

            zone.addEventListener('mousemove', (event) => {
                const rect = zone.getBoundingClientRect();
                const ratio = Math.min(0.999, Math.max(0, (event.clientX - rect.left) / rect.width));
                setIndex(Math.floor(ratio * images.length));
            });

            zone.addEventListener('mouseleave', () => setIndex(0));
        });
    })();
</script>
</body>
</html>
