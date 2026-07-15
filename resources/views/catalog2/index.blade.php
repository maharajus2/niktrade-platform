@php
    use App\Models\Product;
    use App\Models\SiteHomepageBanner;
    use App\Support\ProductDisplayFormatter;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Storage;

    $imageUrl = function (?string $path): string {
        return $path ? Storage::disk('public')->url($path) : asset('images/logo-icon.png');
    };

    $normalizeBannerColor = function (?string $color): ?string {
        $color = is_string($color) ? trim($color) : '';

        return preg_match('/^#(?:[A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) ? $color : null;
    };

    $bannerFontStacks = SiteHomepageBanner::fontFamilyStacks();

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

    $categoryImagePath = function ($category): ?string {
        $path = $category?->image_path;

        return filled($path) && Storage::disk('public')->exists($path) ? $path : null;
    };

    $heroImage = $productImagePaths->skip(1)->first() ?: $productImagePaths->first();
    $fallbackBannerImage = asset('images/logo-icon.png');
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

    $categoryCards = $categories->take(6)->map(function ($category, int $index) use ($categoryProducts, $categoryImagePath, $imageUrl, $visualImage) {
        $product = $categoryProducts->get($category->id);

        return [
            'name' => $category->name,
            'url' => route('catalog.index', ['category' => $category->id]),
            'image' => $imageUrl($categoryImagePath($category) ?? $product?->images?->first()?->file_path ?? $visualImage($index)),
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

    $brandCardData = collect($heroBrandNames)->map(function (string $label, string $key) use ($brands, $fallbackBannerImage) {
        $brand = $brands->first(fn ($brand) => str_contains(mb_strtolower($brand->name), $key));
        return [
            'key' => $key,
            'label' => $brand?->name ?: $label,
            'tagline' => $key === 'hiberg' ? 'Качество на каждый день' : 'Доступно и эффективно',
            'url' => $brand ? route('catalog.index', ['brand' => $brand->id]) : route('catalog.preview', ['brand' => $key]),
            'image' => $fallbackBannerImage,
        ];
    })->values();

    $promoImages = $products->filter(fn (Product $product): bool => $product->images->isNotEmpty())->values();

    $serviceLinks = [
        ['label' => 'Новости', 'url' => '#news'],
        ['label' => 'Как заказать', 'url' => '#footer'],
        ['label' => 'Купить оптом', 'url' => '#business'],
        ['label' => 'Дилерам', 'url' => '#business'],
        ['label' => 'Где купить', 'url' => '#footer'],
    ];

    $directionLabels = [
        ['label' => 'Для дома', 'caption' => 'стирка, уборка, кухня', 'needle' => ['дом', 'стир', 'уборк', 'посуд']],
        ['label' => 'Для авто', 'caption' => 'автохимия и уход', 'needle' => ['авто', 'машин', 'стекл']],
        ['label' => 'Для бизнеса', 'caption' => 'HoReCa и опт', 'needle' => ['horeca', 'бизнес', 'проф']],
        ['label' => 'Дезинфекция', 'caption' => 'санитарные решения', 'needle' => ['дез', 'санит']],
        ['label' => 'Промо', 'caption' => 'акции и новинки', 'needle' => ['акц', 'нов']],
        ['label' => 'Все товары', 'caption' => 'полный каталог', 'needle' => []],
    ];

    $catalogDirections = collect($directionLabels)->map(function (array $direction, int $index) use ($categories, $products, $categoryImagePath, $imageUrl, $visualImage) {
        $category = $categories->first(function ($category) use ($direction): bool {
            $name = mb_strtolower((string) $category->name);

            return collect($direction['needle'])->contains(fn (string $needle): bool => str_contains($name, $needle));
        });

        $product = $products->first(function (Product $product) use ($direction): bool {
            $haystack = mb_strtolower((string) $product->name . ' ' . (string) $product->category?->name);

            return $direction['needle'] !== []
                && collect($direction['needle'])->contains(fn (string $needle): bool => str_contains($haystack, $needle));
        }) ?? $products->get($index);

        return [
            'label' => $direction['label'],
            'caption' => $direction['caption'],
            'url' => $category ? route('catalog.index', ['category' => $category->id]) : route('catalog.index'),
            'image' => $imageUrl($categoryImagePath($category) ?? $product?->images?->first()?->file_path ?? $visualImage($index)),
        ];
    });

    $saleProducts = $products
        ->filter(fn (Product $product): bool => $product->discounted_price !== null && (float) $product->discounted_price < (float) $product->price)
        ->values();
    $newProducts = $products->filter(fn (Product $product): bool => $product->is_new)->values();
    $businessProducts = $products
        ->filter(fn (Product $product): bool => $product->direction === 'professional' || str_contains(mb_strtolower((string) $product->category?->name), 'horeca'))
        ->values();
    $productTabs = [
        ['label' => 'Рекомендуем', 'count' => $featured->count(), 'url' => '#products', 'active' => true],
        ['label' => 'Мега выгода', 'count' => $saleProducts->count(), 'url' => '#products', 'active' => false],
        ['label' => 'Новинки', 'count' => $newProducts->count(), 'url' => '#products', 'active' => false],
        ['label' => 'Для бизнеса', 'count' => $businessProducts->count(), 'url' => '#business', 'active' => false],
    ];

    $bannerSlides = ($homepageBanners ?? collect())
        ->map(function (SiteHomepageBanner $banner, int $index) use ($imageUrl, $fallbackBannerImage, $normalizeBannerColor, $bannerFontStacks) {
            $hasBannerImage = filled($banner->image_path) || filled($banner->mobile_image_path);
            $imagePath = $banner->image_path
                ?: $banner->mobile_image_path
                ?: $banner->product?->images?->first()?->file_path;
            $fontFamily = $banner->font_family ?: SiteHomepageBanner::FONT_DEFAULT;

            return [
                'eyebrow' => $banner->eyebrow,
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'description' => $banner->description,
                'badge' => $banner->badge_text,
                'button_label' => $banner->button_label ?: 'Перейти',
                'url' => $banner->resolveUrl(),
                'image' => $imagePath ? $imageUrl($imagePath) : $fallbackBannerImage,
                'mobile_image' => $banner->mobile_image_path ? $imageUrl($banner->mobile_image_path) : null,
                'is_background' => $hasBannerImage,
                'theme' => $banner->theme ?: 'blue',
                'text_color' => $normalizeBannerColor($banner->text_color),
                'font_stack' => $bannerFontStacks[$fontFamily] ?? null,
                'target_blank' => $banner->opens_in_new_tab,
            ];
        })
        ->values();

    if ($bannerSlides->isEmpty()) {
        $bannerSlides = collect([
            [
                'eyebrow' => 'Производитель бытовой химии',
                'title' => 'Профессиональная бытовая химия',
                'subtitle' => 'для дома, бизнеса и автомобиля',
                'description' => 'Собственное производство, понятный каталог и продукты для ежедневной чистоты.',
                'badge' => 'НИКТРЕЙД',
                'button_label' => 'Перейти в каталог',
                'url' => '#products',
                'image' => $fallbackBannerImage,
                'mobile_image' => null,
                'is_background' => false,
                'theme' => 'blue',
                'text_color' => null,
                'font_stack' => null,
                'target_blank' => false,
            ],
            [
                'eyebrow' => 'Акции и спецпредложения',
                'title' => 'Выгодные закупки',
                'subtitle' => 'товары со скидками и быстрым заказом',
                'description' => 'Соберите корзину из популярных позиций и оформите заказ без лишних шагов.',
                'badge' => 'Акция',
                'button_label' => 'Смотреть акции',
                'url' => '#promos',
                'image' => $fallbackBannerImage,
                'mobile_image' => null,
                'is_background' => false,
                'theme' => 'green',
                'text_color' => null,
                'font_stack' => null,
                'target_blank' => false,
            ],
            [
                'eyebrow' => 'Для бизнеса и HoReCa',
                'title' => 'Химия для бизнеса',
                'subtitle' => 'склады, сервисы, клининг и рестораны',
                'description' => 'Подберите средства под ваши задачи: уборка, дезинфекция, кухня, автохимия.',
                'badge' => 'B2B',
                'button_label' => 'Перейти в раздел',
                'url' => '#business',
                'image' => $fallbackBannerImage,
                'mobile_image' => null,
                'is_background' => false,
                'theme' => 'cyan',
                'text_color' => null,
                'font_stack' => null,
                'target_blank' => false,
            ],
        ]);
    }
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
            --liquid-blue: #0a84ff;
            --liquid-blue-deep: #006eea;
            --liquid-green: #21c98b;
            --liquid-ink: #10223f;
            --liquid-border: rgba(255, 255, 255, .78);
            --liquid-shadow: 0 36px 96px rgba(20, 82, 148, .20), 0 16px 42px rgba(10, 132, 255, .13), 0 1px 0 rgba(255, 255, 255, .70);
            min-height: 100vh;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 11% 7%, rgba(10, 132, 255, .30), transparent 25rem),
                radial-gradient(circle at 86% 12%, rgba(33, 201, 139, .24), transparent 23rem),
                radial-gradient(circle at 68% 88%, rgba(125, 211, 252, .25), transparent 28rem),
                linear-gradient(112deg, transparent 0 18%, rgba(255,255,255,.46) 30%, transparent 43% 100%),
                linear-gradient(135deg, #f7fcff 0%, #e6f5ff 42%, #f7fcff 100%);
            background-attachment: fixed;
            color: #10223f;
            font-family: Inter, Arial, sans-serif;
            letter-spacing: 0;
            position: relative;
            isolation: isolate;
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
                radial-gradient(circle at 14% 70%, rgba(255,255,255,.52) 0 36px, transparent 37px),
                radial-gradient(circle at 46% 7%, rgba(255,255,255,.64) 0 16px, transparent 17px),
                radial-gradient(circle at 62% 36%, rgba(255,255,255,.34) 0 54px, transparent 56px),
                radial-gradient(circle at 96% 18%, rgba(255,255,255,.62) 0 22px, transparent 23px),
                radial-gradient(circle at 34% 38%, rgba(255,255,255,.42) 0 13px, transparent 14px),
                linear-gradient(118deg, rgba(255,255,255,.28), transparent 22%, rgba(33,201,139,.10) 64%, transparent),
                repeating-linear-gradient(105deg, rgba(255,255,255,.12) 0 1px, transparent 1px 42px);
            opacity: .95;
        }

        .nik-catalog2::after {
            z-index: 0;
            background-image:
                radial-gradient(ellipse at 24% 18%, rgba(255,255,255,.76), transparent 23rem),
                radial-gradient(ellipse at 78% 27%, rgba(10,132,255,.18), transparent 26rem),
                radial-gradient(ellipse at 58% 64%, rgba(33,201,139,.14), transparent 30rem),
                linear-gradient(120deg, rgba(255,255,255,.36), transparent 18%);
            filter: blur(18px);
            opacity: .92;
        }

        .nik-catalog2-shell {
            position: relative;
            z-index: 1;
            width: min(1440px, calc(100% - 48px));
            margin: 0 auto;
            padding: 18px 0 28px;
        }

        .nik-catalog2-glass {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--liquid-border);
            background:
                radial-gradient(circle at 18% 0%, rgba(255,255,255,.52), transparent 35%),
                radial-gradient(circle at 88% 100%, rgba(10,132,255,.20), transparent 42%),
                radial-gradient(circle at 0% 100%, rgba(33,201,139,.12), transparent 34%),
                linear-gradient(135deg, rgba(255, 255, 255, .34), rgba(226, 246, 255, .16) 48%, rgba(255, 255, 255, .25));
            background-attachment: fixed;
            background-clip: padding-box;
            box-shadow:
                var(--liquid-shadow),
                0 0 0 1px rgba(255,255,255,.24),
                inset 0 1px 0 rgba(255,255,255,.98),
                inset 1px 0 0 rgba(255,255,255,.52),
                inset 0 -22px 42px rgba(10,132,255,.10),
                inset 0 0 38px rgba(255,255,255,.20);
            backdrop-filter: blur(36px) saturate(205%) contrast(1.07);
            -webkit-backdrop-filter: blur(36px) saturate(205%) contrast(1.07);
            isolation: isolate;
            transform: translateZ(0);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .nik-catalog2-glass::before,
        .nik-catalog2-glass::after {
            position: absolute;
            inset: 0;
            z-index: 0;
            border-radius: inherit;
            pointer-events: none;
            content: "";
        }

        .nik-catalog2-glass::before {
            background:
                radial-gradient(circle at 18% 0%, rgba(255,255,255,.92), transparent 19%),
                linear-gradient(126deg, rgba(255,255,255,.74) 0 7%, rgba(255,255,255,.30) 8% 18%, rgba(255,255,255,0) 42%),
                linear-gradient(112deg, transparent 0 45%, rgba(255,255,255,.30) 48%, transparent 56%),
                linear-gradient(315deg, rgba(10,132,255,.13), transparent 44%);
            mix-blend-mode: screen;
            opacity: .92;
        }

        .nik-catalog2-glass::after {
            inset: 1px;
            background:
                radial-gradient(ellipse at 50% 106%, rgba(10,132,255,.19), transparent 51%),
                linear-gradient(to top, rgba(255,255,255,.34), rgba(255,255,255,0) 40%),
                linear-gradient(90deg, rgba(255,255,255,.24), transparent 18%, transparent 82%, rgba(78, 169, 255, .18));
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,.22),
                inset 0 0 32px rgba(255,255,255,.24),
                inset 0 -28px 52px rgba(10,132,255,.12);
            opacity: .86;
        }

        .nik-catalog2-glass:hover {
            border-color: rgba(255,255,255,.92);
            box-shadow:
                0 42px 112px rgba(20,82,148,.22),
                0 18px 46px rgba(10,132,255,.15),
                0 1px 0 rgba(255,255,255,.76),
                inset 0 1px 0 rgba(255,255,255,1),
                inset 0 -24px 44px rgba(10,132,255,.12),
                inset 0 0 42px rgba(255,255,255,.24);
        }

        .nik-catalog2-glass > * {
            position: relative;
            z-index: 1;
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
            overflow-x: auto;
            scrollbar-width: none;
        }

        .nik-catalog2-nav::-webkit-scrollbar {
            display: none;
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
            background:
                radial-gradient(circle at 22% 0%, rgba(255,255,255,.88), transparent 48%),
                linear-gradient(135deg, rgba(255,255,255,.62), rgba(232,247,255,.38));
            color: #10223f;
            font-size: 14px;
            font-weight: 800;
            box-shadow:
                0 12px 30px rgba(39, 99, 159, .12),
                inset 0 1px 0 rgba(255,255,255,.88),
                inset 0 -1px 0 rgba(80, 160, 232, .13);
            backdrop-filter: blur(20px) saturate(165%);
            -webkit-backdrop-filter: blur(20px) saturate(165%);
        }

        .nik-catalog2-catalog-btn {
            min-width: 136px;
            min-height: 48px;
            gap: 12px;
            border-color: rgba(255, 255, 255, .45);
            background:
                radial-gradient(circle at 24% 0%, rgba(255,255,255,.50), transparent 34%),
                linear-gradient(180deg, rgba(42, 157, 255, .98), rgba(0, 110, 234, .94));
            color: #fff;
            box-shadow:
                0 18px 38px rgba(0, 112, 235, .30),
                inset 0 1px 0 rgba(255,255,255,.52),
                inset 0 -1px 0 rgba(0, 61, 170, .20);
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
            background:
                radial-gradient(circle at 30% 18%, rgba(255,255,255,.48), transparent 34%),
                linear-gradient(180deg, #2a9dff, #006eea);
            color: #fff;
            font-size: 10px;
            font-weight: 900;
            box-shadow: 0 8px 18px rgba(0, 112, 235, .30);
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
            z-index: 0;
            background:
                linear-gradient(135deg, rgba(255,255,255,.72) 0%, rgba(255,255,255,.18) 36%, transparent 58%),
                radial-gradient(circle at 72% 42%, rgba(0, 132, 255, .23), transparent 19rem),
                radial-gradient(circle at 18% 12%, rgba(255,255,255,.70), transparent 18rem),
                radial-gradient(circle at 58% 108%, rgba(33,201,139,.14), transparent 16rem);
            content: "";
            opacity: .96;
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

        .nik-catalog2-advantage i,
        .nik-catalog2-why i {
            display: inline-flex;
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            border: 1px solid rgba(255,255,255,.62);
            background:
                radial-gradient(circle at 28% 10%, rgba(255,255,255,.85), transparent 40%),
                linear-gradient(135deg, rgba(185,225,255,.66), rgba(255,255,255,.28));
            color: #0b86ff;
            box-shadow:
                0 10px 22px rgba(10,132,255,.13),
                inset 0 1px 0 rgba(255,255,255,.88);
            backdrop-filter: blur(14px) saturate(160%);
            -webkit-backdrop-filter: blur(14px) saturate(160%);
        }

        .nik-catalog2-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: clamp(34px, 4.6vw, 64px);
        }

        .nik-catalog2-action {
            min-height: 46px;
            padding: 0 26px;
        }

        .nik-catalog2-action.is-primary {
            border-color: rgba(255, 255, 255, .42);
            background:
                radial-gradient(circle at 24% 0%, rgba(255,255,255,.50), transparent 34%),
                linear-gradient(180deg, rgba(42,157,255,.98), rgba(0,110,234,.94));
            color: #fff;
            box-shadow:
                0 18px 38px rgba(0, 112, 235, .30),
                inset 0 1px 0 rgba(255,255,255,.50),
                inset 0 -1px 0 rgba(0,61,170,.20);
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
            background:
                radial-gradient(circle at 86% 50%, rgba(10,132,255,.28), transparent 42%),
                radial-gradient(circle at 18% 0%, rgba(255,255,255,.46), transparent 36%),
                linear-gradient(135deg, rgba(235,248,255,.30), rgba(255,255,255,.16));
            background-attachment: fixed;
            padding: 30px;
        }

        .nik-catalog2-brand-card.is-arvetera {
            background:
                radial-gradient(circle at 86% 52%, rgba(33,201,139,.30), transparent 42%),
                radial-gradient(circle at 18% 0%, rgba(255,255,255,.44), transparent 36%),
                linear-gradient(135deg, rgba(225,255,241,.28), rgba(255,255,255,.16));
            background-attachment: fixed;
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
            background:
                radial-gradient(circle at 24% 0%, rgba(255,255,255,.92), transparent 42%),
                linear-gradient(135deg, rgba(255,255,255,.62), rgba(235,248,255,.34));
            padding: 0 22px;
            color: #10223f;
            font-size: 13px;
            font-weight: 900;
            box-shadow:
                0 10px 24px rgba(39,99,159,.10),
                inset 0 1px 0 rgba(255,255,255,.88);
            backdrop-filter: blur(16px) saturate(160%);
            -webkit-backdrop-filter: blur(16px) saturate(160%);
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
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .nik-catalog2-category-card:hover,
        .nik-catalog2-product-card:hover,
        .nik-catalog2-promo:hover {
            transform: translateY(-2px);
            border-color: rgba(255,255,255,.86);
            box-shadow:
                0 30px 82px rgba(20, 82, 148, .18),
                0 12px 30px rgba(10,132,255,.13),
                inset 0 1px 0 rgba(255,255,255,.96);
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
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .nik-catalog2-product-image {
            position: relative;
            display: flex;
            height: 145px;
            align-items: center;
            justify-content: center;
            margin: -2px -2px 8px;
            border: 1px solid rgba(255,255,255,.42);
            border-radius: 18px;
            background:
                radial-gradient(circle at 50% 20%, rgba(255,255,255,.58), transparent 45%),
                linear-gradient(180deg, rgba(255,255,255,.25), rgba(168,219,255,.12));
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.68),
                inset 0 -1px 0 rgba(10,132,255,.09);
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
            border: 1px solid rgba(255,255,255,.62);
            border-radius: 999px;
            background:
                radial-gradient(circle at 24% 0%, rgba(255,255,255,.88), transparent 42%),
                linear-gradient(135deg, rgba(255,255,255,.58), rgba(229,246,255,.34));
            color: #477096;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(39,99,159,.12), inset 0 1px 0 rgba(255,255,255,.82);
            backdrop-filter: blur(14px) saturate(160%);
            -webkit-backdrop-filter: blur(14px) saturate(160%);
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
            border: 1px solid rgba(255,255,255,.42);
            border-radius: 13px;
            background:
                radial-gradient(circle at 30% 10%, rgba(255,255,255,.52), transparent 36%),
                linear-gradient(180deg, rgba(42,157,255,.98), rgba(0,110,234,.94));
            color: #fff;
            cursor: pointer;
            box-shadow:
                0 14px 28px rgba(0, 112, 235, .30),
                inset 0 1px 0 rgba(255,255,255,.48),
                inset 0 -1px 0 rgba(0,61,170,.20);
        }

        .nik-catalog2-advantages {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
            border-radius: 20px;
            padding: 14px;
            background:
                radial-gradient(circle at 9% 0%, rgba(255,255,255,.50), transparent 28%),
                radial-gradient(circle at 96% 100%, rgba(10,132,255,.14), transparent 34%),
                linear-gradient(135deg, rgba(255,255,255,.28), rgba(230,247,255,.16));
            background-attachment: fixed;
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
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .nik-catalog2-promo.is-sale {
            background:
                radial-gradient(circle at 76% 50%, rgba(255, 187, 64, .26), transparent 46%),
                radial-gradient(circle at 18% 0%, rgba(255,255,255,.42), transparent 36%),
                linear-gradient(135deg, rgba(255, 242, 221, .28), rgba(255,255,255,.15));
            background-attachment: fixed;
        }

        .nik-catalog2-promo.is-new {
            background:
                radial-gradient(circle at 78% 52%, rgba(33, 201, 139, .25), transparent 46%),
                radial-gradient(circle at 18% 0%, rgba(255,255,255,.42), transparent 36%),
                linear-gradient(135deg, rgba(219, 255, 249, .27), rgba(255,255,255,.15));
            background-attachment: fixed;
        }

        .nik-catalog2-promo.is-b2b {
            background:
                radial-gradient(circle at 78% 52%, rgba(10,132,255,.27), transparent 46%),
                radial-gradient(circle at 18% 0%, rgba(255,255,255,.42), transparent 36%),
                linear-gradient(135deg, rgba(224, 241, 255, .28), rgba(255,255,255,.15));
            background-attachment: fixed;
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
            background:
                radial-gradient(circle at 24% 0%, rgba(255,255,255,.88), transparent 42%),
                linear-gradient(135deg, rgba(255,255,255,.58), rgba(235,248,255,.28));
            padding: 0 16px;
            font-size: 12px;
            font-weight: 900;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.82);
            backdrop-filter: blur(14px) saturate(160%);
            -webkit-backdrop-filter: blur(14px) saturate(160%);
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
            border: 1px solid rgba(255,255,255,.54);
            background:
                radial-gradient(circle at 88% 52%, rgba(10,132,255,.16), transparent 42%),
                linear-gradient(135deg, rgba(255,255,255,.46), rgba(232,247,255,.24));
            box-shadow:
                0 14px 32px rgba(39,99,159,.10),
                inset 0 1px 0 rgba(255,255,255,.76);
            backdrop-filter: blur(18px) saturate(160%);
            -webkit-backdrop-filter: blur(18px) saturate(160%);
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
            border: 1px solid rgba(255,255,255,.56);
            border-radius: 18px;
            background:
                radial-gradient(circle at 78% 20%, rgba(10,132,255,.18), transparent 42%),
                linear-gradient(135deg, rgba(221,244,255,.46), rgba(255,255,255,.26));
            padding: 12px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.76);
            backdrop-filter: blur(16px) saturate(155%);
            -webkit-backdrop-filter: blur(16px) saturate(155%);
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
            border: 1px solid rgba(255,255,255,.52);
            background:
                radial-gradient(circle at 18% 0%, rgba(255,255,255,.68), transparent 42%),
                linear-gradient(135deg, rgba(255,255,255,.42), rgba(232,247,255,.24));
            padding: 12px;
            box-shadow:
                0 14px 32px rgba(39,99,159,.10),
                inset 0 1px 0 rgba(255,255,255,.78);
            backdrop-filter: blur(18px) saturate(160%);
            -webkit-backdrop-filter: blur(18px) saturate(160%);
        }

        .nik-catalog2-news-card img {
            width: 100%;
            height: 86px;
            object-fit: contain;
            border-radius: 13px;
            background:
                radial-gradient(circle at 50% 20%, rgba(255,255,255,.56), transparent 45%),
                linear-gradient(180deg, rgba(255,255,255,.26), rgba(168,219,255,.12));
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
            background:
                radial-gradient(circle at 20% 0%, rgba(255,255,255,.86), transparent 42%),
                linear-gradient(135deg, rgba(255,255,255,.58), rgba(232,247,255,.32));
            padding: 0 14px;
            color: #10223f;
            outline: 0;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.82),
                inset 0 -1px 0 rgba(10,132,255,.08);
            backdrop-filter: blur(16px) saturate(160%);
            -webkit-backdrop-filter: blur(16px) saturate(160%);
        }

        .nik-catalog2-subscribe button {
            min-height: 44px;
            border: 1px solid rgba(255,255,255,.42);
            border-radius: 14px;
            background:
                radial-gradient(circle at 28% 0%, rgba(255,255,255,.50), transparent 36%),
                linear-gradient(180deg, rgba(42,157,255,.98), rgba(0,110,234,.94));
            padding: 0 20px;
            color: #fff;
            font-weight: 900;
            box-shadow:
                0 14px 30px rgba(0,112,235,.28),
                inset 0 1px 0 rgba(255,255,255,.46);
        }

        @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
            .nik-catalog2-glass,
            .nik-catalog2-catalog-btn,
            .nik-catalog2-nav a,
            .nik-catalog2-icon-link,
            .nik-catalog2-action,
            .nik-catalog2-brand-card a,
            .nik-catalog2-promo a,
            .nik-catalog2-brand-mini,
            .nik-catalog2-about-visual,
            .nik-catalog2-news-card,
            .nik-catalog2-subscribe input {
                background: rgba(255,255,255,.86);
            }
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

        @media (max-width: 1480px) and (min-width: 1181px) {
            .nik-catalog2-header {
                grid-template-columns: 220px minmax(0, 1fr) auto;
                gap: 12px;
            }

            .nik-catalog2-nav {
                gap: 8px;
            }

            .nik-catalog2-nav a {
                min-height: 42px;
                padding: 0 12px;
                font-size: 13px;
            }

            .nik-catalog2-catalog-btn {
                min-width: 126px;
            }

            .nik-catalog2-phone {
                font-size: 14px;
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

        /* Final Niktrade soft glass direction based on the new homepage reference. */
        .nik-catalog2 {
            --liquid-blue: #287df2;
            --liquid-blue-deep: #0b70f0;
            --liquid-green: #2f9f6d;
            --liquid-ink: #0f1b33;
            --liquid-border: rgba(211, 226, 246, .88);
            --liquid-shadow: 0 24px 66px rgba(39, 88, 145, .12), 0 2px 10px rgba(39, 88, 145, .06);
            background:
                linear-gradient(118deg, rgba(255,255,255,.92) 0%, rgba(255,255,255,.20) 34%, rgba(236,246,255,.46) 62%, rgba(255,255,255,.80) 100%),
                linear-gradient(180deg, #fbfdff 0%, #eef6ff 46%, #f8fbff 100%);
            background-attachment: fixed;
        }

        .nik-catalog2::before {
            background:
                linear-gradient(90deg, rgba(255,255,255,.62), rgba(255,255,255,0) 32%, rgba(108,173,255,.08) 72%, rgba(255,255,255,.46)),
                linear-gradient(180deg, rgba(255,255,255,.64), rgba(255,255,255,0) 34%);
            opacity: .76;
        }

        .nik-catalog2::after {
            background:
                linear-gradient(145deg, rgba(255,255,255,.34), rgba(255,255,255,0) 46%, rgba(194,224,255,.16));
            filter: none;
            opacity: .70;
        }

        .nik-catalog2-glass {
            border-color: var(--liquid-border);
            background:
                linear-gradient(145deg, rgba(255,255,255,.90), rgba(246,251,255,.76) 56%, rgba(239,247,255,.68));
            background-attachment: fixed;
            box-shadow:
                var(--liquid-shadow),
                inset 0 1px 0 rgba(255,255,255,.96),
                inset 0 -1px 0 rgba(114,176,245,.14);
            backdrop-filter: blur(18px) saturate(135%);
            -webkit-backdrop-filter: blur(18px) saturate(135%);
        }

        .nik-catalog2-glass::before {
            background: linear-gradient(138deg, rgba(255,255,255,.72), rgba(255,255,255,.18) 36%, rgba(255,255,255,0) 58%);
            mix-blend-mode: normal;
            opacity: .72;
        }

        .nik-catalog2-glass::after {
            inset: 0;
            background: transparent;
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,.72),
                inset 0 -18px 36px rgba(40,125,242,.06);
            opacity: .95;
        }

        .nik-catalog2-glass:hover {
            border-color: rgba(221,233,248,.96);
            box-shadow:
                0 30px 76px rgba(39,88,145,.15),
                0 8px 22px rgba(40,125,242,.08),
                inset 0 1px 0 rgba(255,255,255,.96),
                inset 0 -1px 0 rgba(114,176,245,.16);
        }

        .nik-catalog2-header {
            top: 12px;
        }

        .nik-catalog2-catalog-btn,
        .nik-catalog2-nav a,
        .nik-catalog2-icon-link,
        .nik-catalog2-action,
        .nik-catalog2-brand-card a,
        .nik-catalog2-promo a,
        .nik-catalog2-brand-mini,
        .nik-catalog2-about-visual,
        .nik-catalog2-news-card,
        .nik-catalog2-subscribe input,
        .nik-catalog2-favorite,
        .nik-catalog2-product-image,
        .nik-catalog2-advantage i,
        .nik-catalog2-why i {
            border-color: rgba(218,231,247,.92);
            background:
                linear-gradient(145deg, rgba(255,255,255,.88), rgba(246,251,255,.72));
            box-shadow:
                0 10px 26px rgba(39,88,145,.09),
                inset 0 1px 0 rgba(255,255,255,.94),
                inset 0 -1px 0 rgba(40,125,242,.08);
            backdrop-filter: blur(12px) saturate(125%);
            -webkit-backdrop-filter: blur(12px) saturate(125%);
        }

        .nik-catalog2-catalog-btn,
        .nik-catalog2-action.is-primary,
        .nik-catalog2-cart-btn,
        .nik-catalog2-subscribe button {
            border-color: rgba(255,255,255,.58);
            background: linear-gradient(180deg, #5aa4ff 0%, #287df2 56%, #0b70f0 100%);
            color: #fff;
            box-shadow:
                0 16px 34px rgba(40,125,242,.28),
                inset 0 1px 0 rgba(255,255,255,.46),
                inset 0 -1px 0 rgba(4,76,179,.24);
        }

        .nik-catalog2-hero {
            min-height: 398px;
            border-radius: 32px;
        }

        .nik-catalog2-hero::before {
            background:
                linear-gradient(102deg, rgba(255,255,255,.86) 0%, rgba(255,255,255,.62) 36%, rgba(235,246,255,.34) 68%, rgba(255,255,255,.12) 100%);
            opacity: .96;
        }

        .nik-catalog2-hero h1 {
            color: #0f1b33;
            letter-spacing: 0;
        }

        .nik-catalog2-hero h2,
        .nik-catalog2-section-head a,
        .nik-catalog2-news-card time,
        .nik-catalog2-news-card a {
            color: #287df2;
        }

        .nik-catalog2-hero-product {
            opacity: .52;
            filter: drop-shadow(0 28px 42px rgba(39,88,145,.14));
        }

        .nik-catalog2-bubble {
            border-color: rgba(218,231,247,.76);
            background: radial-gradient(circle at 34% 24%, rgba(255,255,255,.86), rgba(232,244,255,.34) 58%, rgba(255,255,255,.18));
            box-shadow: inset 0 1px 8px rgba(255,255,255,.74), 0 12px 24px rgba(39,88,145,.08);
        }

        .nik-catalog2-brand-card,
        .nik-catalog2-brand-card.is-arvetera {
            background:
                linear-gradient(106deg, rgba(255,255,255,.88) 0%, rgba(255,255,255,.58) 54%, rgba(231,245,255,.36) 100%);
            background-attachment: fixed;
        }

        .nik-catalog2-brand-card.is-arvetera {
            background:
                linear-gradient(106deg, rgba(255,255,255,.88) 0%, rgba(255,255,255,.58) 54%, rgba(231,250,242,.42) 100%);
            background-attachment: fixed;
        }

        .nik-catalog2-brand-card h3 {
            color: #12213d;
            text-transform: none;
        }

        .nik-catalog2-brand-card.is-arvetera h3 {
            color: #2f7d48;
        }

        .nik-catalog2-brand-card img {
            opacity: .88;
            filter: drop-shadow(0 18px 26px rgba(39,88,145,.12));
        }

        .nik-catalog2-category-card,
        .nik-catalog2-product-card {
            border-radius: 22px;
        }

        .nik-catalog2-product-card {
            min-height: 318px;
        }

        .nik-catalog2-category-card:hover,
        .nik-catalog2-product-card:hover,
        .nik-catalog2-promo:hover {
            transform: translateY(-2px);
            border-color: rgba(221,233,248,.96);
            box-shadow:
                0 30px 76px rgba(39,88,145,.15),
                0 8px 22px rgba(40,125,242,.08),
                inset 0 1px 0 rgba(255,255,255,.96);
        }

        .nik-catalog2-badge {
            border: 1px solid rgba(255,255,255,.70);
            background: linear-gradient(145deg, #e8f3ff, #ffffff);
            color: #287df2;
            box-shadow: 0 8px 18px rgba(39,88,145,.08), inset 0 1px 0 rgba(255,255,255,.92);
        }

        .nik-catalog2-badge.is-sale {
            background: linear-gradient(145deg, #fff3dd, #ffffff);
            color: #f08a00;
        }

        .nik-catalog2-badge.is-new {
            background: linear-gradient(145deg, #e8f3ff, #ffffff);
            color: #287df2;
        }

        .nik-catalog2-price {
            color: #0f1b33;
        }

        .nik-catalog2-promo.is-sale,
        .nik-catalog2-promo.is-new,
        .nik-catalog2-promo.is-b2b {
            background:
                linear-gradient(110deg, rgba(255,255,255,.90), rgba(255,255,255,.66) 48%, rgba(239,247,255,.46));
            background-attachment: fixed;
        }

        .nik-catalog2-servicebar {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-height: 34px;
            padding: 0 4px;
            color: rgba(15,27,51,.62);
            font-size: 12px;
            font-weight: 800;
        }

        .nik-catalog2-servicebar > div,
        .nik-catalog2-servicebar nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
        }

        .nik-catalog2-servicebar a {
            color: rgba(15,27,51,.66);
            transition: color .18s ease;
        }

        .nik-catalog2-servicebar a:hover {
            color: #287df2;
        }

        .nik-catalog2-mega-strip {
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr);
            gap: 16px;
            align-items: stretch;
            margin-bottom: 18px;
            border-radius: 26px;
            padding: 14px;
        }

        .nik-catalog2-mega-copy {
            display: flex;
            min-height: 94px;
            flex-direction: column;
            justify-content: center;
            border-radius: 20px;
            padding: 18px;
            background: linear-gradient(145deg, rgba(232,243,255,.96), rgba(255,255,255,.72));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.94), inset 0 -1px 0 rgba(40,125,242,.08);
        }

        .nik-catalog2-mega-copy span {
            color: #287df2;
            font-size: 12px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .nik-catalog2-mega-copy strong {
            margin-top: 6px;
            color: #0f1b33;
            font-size: 21px;
            font-weight: 950;
            line-height: 1.1;
        }

        .nik-catalog2-mega-links {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
        }

        .nik-catalog2-mega-links a {
            display: grid;
            min-width: 0;
            min-height: 94px;
            grid-template-columns: 50px minmax(0, 1fr);
            grid-template-rows: 1fr auto;
            gap: 4px 10px;
            align-items: center;
            border: 1px solid rgba(218,231,247,.92);
            border-radius: 20px;
            background: linear-gradient(145deg, rgba(255,255,255,.88), rgba(246,251,255,.72));
            padding: 12px;
            box-shadow: 0 10px 26px rgba(39,88,145,.08), inset 0 1px 0 rgba(255,255,255,.94);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .nik-catalog2-mega-links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(39,88,145,.12), inset 0 1px 0 rgba(255,255,255,.96);
        }

        .nik-catalog2-mega-links img {
            grid-row: 1 / 3;
            width: 50px;
            height: 50px;
            object-fit: contain;
            filter: drop-shadow(0 10px 14px rgba(39,88,145,.12));
        }

        .nik-catalog2-mega-links span {
            overflow: hidden;
            color: #0f1b33;
            font-size: 13px;
            font-weight: 950;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nik-catalog2-mega-links small {
            overflow: hidden;
            color: rgba(15,27,51,.58);
            font-size: 11px;
            font-weight: 760;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nik-catalog2-product-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0 0 14px;
        }

        .nik-catalog2-product-tabs a {
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            gap: 9px;
            border: 1px solid rgba(218,231,247,.92);
            border-radius: 999px;
            background: linear-gradient(145deg, rgba(255,255,255,.88), rgba(246,251,255,.72));
            padding: 0 14px;
            color: rgba(15,27,51,.72);
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 8px 20px rgba(39,88,145,.08), inset 0 1px 0 rgba(255,255,255,.92);
        }

        .nik-catalog2-product-tabs a.is-active {
            background: linear-gradient(145deg, rgba(232,243,255,.96), rgba(255,255,255,.74));
            color: #287df2;
        }

        .nik-catalog2-product-tabs span {
            display: inline-flex;
            min-width: 24px;
            justify-content: center;
            border-radius: 999px;
            background: rgba(40,125,242,.10);
            padding: 3px 7px;
            font-size: 11px;
        }

        .nik-catalog2-product-facts {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 2px;
        }

        .nik-catalog2-product-facts span {
            display: inline-flex;
            min-height: 23px;
            align-items: center;
            border-radius: 999px;
            background: rgba(235,243,252,.78);
            padding: 0 8px;
            color: rgba(15,27,51,.58);
            font-size: 11px;
            font-weight: 850;
        }

        .nik-catalog2-product-facts .is-stock {
            background: rgba(34,197,94,.12);
            color: #16834f;
        }

        .nik-catalog2-product-facts .is-wait {
            background: rgba(245,158,11,.13);
            color: #b66b00;
        }

        .nik-catalog2-cart-btn {
            position: relative;
        }

        .nik-catalog2-cart-btn > span {
            position: absolute;
            top: -6px;
            right: -5px;
            display: inline-flex;
            min-width: 18px;
            height: 18px;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255,255,255,.92);
            border-radius: 999px;
            background: #0f1b33;
            color: #fff;
            font-size: 10px;
            font-weight: 950;
        }

        .nik-catalog2-commerce-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .nik-catalog2-commerce-card {
            min-height: 188px;
            border-radius: 24px;
            padding: 24px;
        }

        .nik-catalog2-commerce-card span {
            color: #287df2;
            font-size: 12px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .nik-catalog2-commerce-card h3 {
            margin: 10px 0 8px;
            color: #0f1b33;
            font-size: 24px;
            font-weight: 950;
            line-height: 1.08;
        }

        .nik-catalog2-commerce-card p {
            margin: 0;
            color: rgba(15,27,51,.66);
            font-size: 14px;
            font-weight: 760;
            line-height: 1.45;
        }

        .nik-catalog2-commerce-card a {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            margin-top: 18px;
            border: 1px solid rgba(218,231,247,.92);
            border-radius: 999px;
            background: linear-gradient(145deg, rgba(255,255,255,.88), rgba(246,251,255,.72));
            padding: 0 15px;
            color: #287df2;
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 8px 20px rgba(39,88,145,.08), inset 0 1px 0 rgba(255,255,255,.92);
        }

        @media (max-width: 760px) {
            .nik-catalog2 {
                background:
                    linear-gradient(125deg, rgba(255,255,255,.92) 0%, rgba(255,255,255,.22) 42%, rgba(234,245,255,.52) 100%),
                    linear-gradient(180deg, #fbfdff 0%, #eef7ff 50%, #f9fcff 100%);
                background-attachment: fixed;
            }

            .nik-catalog2-hero {
                min-height: auto;
                padding-bottom: 210px;
            }

            .nik-catalog2-hero-product {
                opacity: .62;
            }

            .nik-catalog2-servicebar {
                display: grid;
                gap: 8px;
            }

            .nik-catalog2-servicebar > div,
            .nik-catalog2-servicebar nav {
                gap: 10px;
                overflow-x: auto;
                flex-wrap: nowrap;
                scrollbar-width: none;
            }

            .nik-catalog2-servicebar > div::-webkit-scrollbar,
            .nik-catalog2-servicebar nav::-webkit-scrollbar {
                display: none;
            }

            .nik-catalog2-mega-strip {
                grid-template-columns: 1fr;
                padding: 12px;
            }

            .nik-catalog2-mega-copy {
                min-height: auto;
            }

            .nik-catalog2-mega-links {
                display: flex;
                overflow-x: auto;
                margin-right: -12px;
                padding-right: 12px;
                scroll-snap-type: x mandatory;
                scrollbar-width: none;
            }

            .nik-catalog2-mega-links::-webkit-scrollbar {
                display: none;
            }

            .nik-catalog2-mega-links a {
                min-width: 190px;
                scroll-snap-align: start;
            }

            .nik-catalog2-product-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                margin-right: -12px;
                padding-right: 12px;
                scrollbar-width: none;
            }

            .nik-catalog2-product-tabs::-webkit-scrollbar {
                display: none;
            }

            .nik-catalog2-product-tabs a {
                flex: 0 0 auto;
            }

            .nik-catalog2-product-card {
                min-height: 296px;
            }

            .nik-catalog2-commerce-grid {
                grid-template-columns: 1fr;
            }
        }

        .nik-catalog2-header {
            grid-template-columns: 200px minmax(0, 1fr) auto;
            gap: 10px 12px;
        }

        .nik-catalog2-logo img {
            width: 190px;
        }

        .nik-catalog2-nav {
            gap: 7px;
            overflow: hidden;
        }

        .nik-catalog2-nav a {
            flex: 0 0 auto;
            min-height: 42px;
            padding: 0 10px;
            font-size: 13px;
        }

        .nik-catalog2-catalog-btn {
            min-width: 112px;
            min-height: 42px;
            padding: 0 12px;
            gap: 10px;
        }

        .nik-catalog2-head-tools {
            gap: 8px;
        }

        .nik-catalog2-phone {
            font-size: 14px;
        }

        .nik-catalog2-icon-link {
            width: 42px;
            height: 42px;
        }

        .nik-catalog2-menu-toggle,
        .nik-catalog2-menu-backdrop,
        .nik-catalog2-menu-panel {
            display: none;
        }

        .nik-catalog2-menu-toggle {
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 44px;
            padding: 0 16px;
            border: 1px solid rgba(218,231,247,.92);
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(255,255,255,.90), rgba(246,251,255,.76));
            color: #10223f;
            font: inherit;
            font-size: 14px;
            font-weight: 900;
            box-shadow:
                0 10px 26px rgba(39,88,145,.10),
                inset 0 1px 0 rgba(255,255,255,.96),
                inset 0 -1px 0 rgba(40,125,242,.10);
            backdrop-filter: blur(12px) saturate(125%);
            -webkit-backdrop-filter: blur(12px) saturate(125%);
        }

        .nik-catalog2-menu-toggle span:last-child,
        .nik-catalog2-menu-close span {
            display: grid;
            gap: 4px;
        }

        .nik-catalog2-menu-toggle i,
        .nik-catalog2-menu-close i {
            display: block;
            width: 18px;
            height: 2px;
            border-radius: 99px;
            background: currentColor;
        }

        .nik-catalog2-menu-close i:first-child {
            transform: translateY(3px) rotate(45deg);
        }

        .nik-catalog2-menu-close i:last-child {
            transform: translateY(-3px) rotate(-45deg);
        }

        .nik-catalog2-menu-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border: 1px solid rgba(218,231,247,.92);
            border-radius: 16px;
            background: linear-gradient(145deg, rgba(255,255,255,.90), rgba(246,251,255,.74));
            color: #10223f;
            box-shadow:
                0 10px 24px rgba(39,88,145,.10),
                inset 0 1px 0 rgba(255,255,255,.96);
        }

        .nik-catalog2-menu-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .nik-catalog2-menu-panel-head img {
            width: 148px;
            height: auto;
        }

        .nik-catalog2-menu-service,
        .nik-catalog2-menu-links,
        .nik-catalog2-menu-tools {
            display: grid;
            gap: 10px;
        }

        .nik-catalog2-menu-service {
            color: rgba(15,27,51,.64);
            font-size: 13px;
            font-weight: 800;
        }

        .nik-catalog2-menu-service nav {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .nik-catalog2-menu-service a,
        .nik-catalog2-menu-links a,
        .nik-catalog2-menu-phone,
        .nik-catalog2-menu-icon {
            border: 1px solid rgba(218,231,247,.92);
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(255,255,255,.90), rgba(246,251,255,.72));
            color: #10223f;
            box-shadow:
                0 10px 26px rgba(39,88,145,.09),
                inset 0 1px 0 rgba(255,255,255,.94),
                inset 0 -1px 0 rgba(40,125,242,.08);
        }

        .nik-catalog2-menu-service a {
            min-height: 38px;
            padding: 10px 12px;
            font-size: 12px;
            font-weight: 850;
        }

        .nik-catalog2-menu-links a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 48px;
            padding: 0 16px;
            font-size: 15px;
            font-weight: 900;
        }

        .nik-catalog2-menu-links a::after {
            content: "›";
            color: #287df2;
            font-size: 20px;
            line-height: 1;
        }

        .nik-catalog2-menu-tools {
            grid-template-columns: repeat(3, 52px) minmax(0, 1fr);
            align-items: center;
        }

        .nik-catalog2-menu-phone {
            grid-column: 1 / -1;
            display: grid;
            gap: 2px;
            padding: 13px 16px;
            font-size: 17px;
            font-weight: 950;
            white-space: nowrap;
        }

        .nik-catalog2-menu-phone small {
            color: rgba(15,27,51,.56);
            font-size: 12px;
            font-weight: 750;
        }

        .nik-catalog2-menu-icon {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
        }

        .nik-catalog2-menu-icon svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .nik-catalog2-menu-icon .nik-catalog2-cart-count {
            top: 4px;
            right: 4px;
        }

        body.nik-catalog2-menu-lock {
            overflow: hidden;
        }

        @media (max-width: 1280px) and (min-width: 761px) {
            .nik-catalog2-header {
                grid-template-columns: 190px minmax(0, 1fr) auto;
            }

            .nik-catalog2-logo img {
                width: 178px;
            }

            .nik-catalog2-nav {
                gap: 6px;
            }

            .nik-catalog2-nav a {
                padding: 0 8px;
                font-size: 12px;
            }

            .nik-catalog2-catalog-btn {
                min-width: 104px;
            }
        }

        @media (max-width: 760px) {
            .nik-catalog2-header {
                position: sticky;
                top: 10px;
                z-index: 80;
                display: grid;
                grid-template-columns: 1fr auto;
                gap: 12px;
                align-items: center;
                margin-bottom: 14px;
            }

            .nik-catalog2-servicebar,
            .nik-catalog2-nav,
            .nik-catalog2-head-tools {
                display: none;
            }

            .nik-catalog2-logo img {
                width: 158px;
            }

            .nik-catalog2-menu-toggle {
                display: inline-flex;
            }

            .nik-catalog2-menu-backdrop {
                position: fixed;
                inset: 0;
                z-index: 110;
                display: block;
                padding: 0;
                border: 0;
                background: rgba(10, 24, 45, .22);
                opacity: 0;
                pointer-events: none;
                transition: opacity .22s ease;
                backdrop-filter: blur(6px);
                -webkit-backdrop-filter: blur(6px);
            }

            .nik-catalog2-menu-panel {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                z-index: 120;
                display: flex;
                width: min(360px, calc(100vw - 28px));
                flex-direction: column;
                gap: 18px;
                padding: 18px;
                overflow-y: auto;
                border-left: 1px solid rgba(218,231,247,.92);
                background:
                    linear-gradient(145deg, rgba(255,255,255,.94), rgba(239,247,255,.82)),
                    radial-gradient(circle at 18% 8%, rgba(40,125,242,.14), transparent 18rem);
                box-shadow:
                    -26px 0 70px rgba(39,88,145,.20),
                    inset 1px 0 0 rgba(255,255,255,.92);
                transform: translateX(calc(100% + 32px));
                transition: transform .26s cubic-bezier(.22, .84, .28, 1);
                backdrop-filter: blur(22px) saturate(145%);
                -webkit-backdrop-filter: blur(22px) saturate(145%);
            }

            .nik-catalog2.is-menu-open .nik-catalog2-menu-backdrop {
                opacity: 1;
                pointer-events: auto;
            }

            .nik-catalog2.is-menu-open .nik-catalog2-menu-panel {
                transform: translateX(0);
            }
        }

        .nik-catalog2-hero-carousel {
            min-height: clamp(430px, 32vw, 520px);
            padding: 0;
        }

        .nik-catalog2-hero-grid {
            grid-template-columns: 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .nik-catalog2-brand-stack {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .nik-catalog2-brand-card {
            min-height: 220px;
            padding: 34px 38px;
        }

        .nik-catalog2-brand-card img {
            right: 28px;
            bottom: 14px;
            width: min(36%, 260px);
            max-height: 190px;
        }

        .nik-catalog2-hero-carousel::before {
            z-index: 1;
            pointer-events: none;
        }

        .nik-catalog2-hero-track {
            position: absolute;
            inset: 0;
            z-index: 2;
        }

        .nik-catalog2-hero-slide {
            position: absolute;
            inset: 0;
            display: grid;
            grid-template-columns: minmax(0, .96fr) minmax(300px, .58fr);
            align-items: center;
            gap: clamp(30px, 5vw, 72px);
            padding: clamp(44px, 4.6vw, 70px) clamp(44px, 5vw, 76px);
            opacity: 0;
            pointer-events: none;
            font-family: var(--banner-font-family, inherit);
            transform: translateX(30px) scale(.985);
            transition:
                opacity .46s ease,
                transform .58s cubic-bezier(.22, .84, .28, 1);
        }

        .nik-catalog2-hero-slide.is-active {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(0) scale(1);
        }

        .nik-catalog2-hero-slide.is-leaving {
            transform: translateX(-22px) scale(.99);
        }

        .nik-catalog2-hero-slide.is-green {
            background:
                radial-gradient(circle at 82% 42%, rgba(47,159,109,.18), transparent 18rem),
                radial-gradient(circle at 18% 24%, rgba(255,255,255,.58), transparent 20rem);
        }

        .nik-catalog2-hero-slide.is-cyan {
            background:
                radial-gradient(circle at 78% 34%, rgba(18,184,205,.16), transparent 18rem),
                radial-gradient(circle at 20% 12%, rgba(255,255,255,.58), transparent 20rem);
        }

        .nik-catalog2-hero-slide.is-dark {
            background:
                linear-gradient(115deg, rgba(15,27,51,.08), rgba(40,125,242,.08)),
                radial-gradient(circle at 82% 44%, rgba(15,27,51,.16), transparent 18rem);
        }

        .nik-catalog2-hero-slide.has-banner-image {
            grid-template-columns: minmax(0, 1fr);
            overflow: hidden;
        }

        .nik-catalog2-hero-slide.has-banner-image::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(255,255,255,.96) 0%, rgba(255,255,255,.82) 33%, rgba(246,251,255,.42) 61%, rgba(246,251,255,.14) 100%),
                radial-gradient(circle at 24% 48%, rgba(255,255,255,.78), transparent 34rem);
        }

        .nik-catalog2-hero-slide.has-banner-image .nik-catalog2-hero-copy {
            position: relative;
            z-index: 2;
        }

        .nik-catalog2-hero-carousel .nik-catalog2-hero-copy {
            width: min(760px, 100%);
        }

        .nik-catalog2-hero-badge {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            min-height: 30px;
            margin-top: 18px;
            padding: 0 12px;
            border: 1px solid rgba(218,231,247,.90);
            border-radius: 999px;
            background: linear-gradient(145deg, rgba(255,255,255,.86), rgba(246,251,255,.64));
            color: #287df2;
            font-size: 12px;
            font-weight: 900;
            box-shadow:
                0 10px 22px rgba(39,88,145,.08),
                inset 0 1px 0 rgba(255,255,255,.92);
        }

        .nik-catalog2-hero-description {
            max-width: 540px;
            margin: 18px 0 0;
            color: rgba(15,27,51,.64);
            font-size: 15px;
            font-weight: 760;
            line-height: 1.55;
        }

        .nik-catalog2-hero-slide.has-custom-text-color .nik-catalog2-eyebrow,
        .nik-catalog2-hero-slide.has-custom-text-color .nik-catalog2-hero-badge,
        .nik-catalog2-hero-slide.has-custom-text-color h1,
        .nik-catalog2-hero-slide.has-custom-text-color h2,
        .nik-catalog2-hero-slide.has-custom-text-color .nik-catalog2-hero-description {
            color: var(--banner-text-color);
        }

        .nik-catalog2-hero-media {
            display: flex;
            justify-content: flex-end;
        }

        .nik-catalog2-hero-media.is-background {
            position: absolute;
            inset: 0;
            z-index: 0;
            display: block;
        }

        .nik-catalog2-hero-slide-image {
            width: min(100%, 430px);
            max-height: 380px;
            object-fit: contain;
            opacity: .82;
            filter: drop-shadow(0 28px 42px rgba(39,88,145,.16));
        }

        .nik-catalog2-hero-media.is-background .nik-catalog2-hero-slide-image {
            width: 100%;
            height: 100%;
            max-height: none;
            object-fit: cover;
            opacity: 1;
            filter: none;
        }

        .nik-catalog2-hero-arrow {
            position: absolute;
            top: 50%;
            z-index: 5;
            display: inline-flex;
            width: 48px;
            height: 48px;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(218,231,247,.92);
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(255,255,255,.86), rgba(246,251,255,.68));
            color: #10223f;
            font: inherit;
            font-size: 30px;
            font-weight: 700;
            line-height: 1;
            box-shadow:
                0 14px 30px rgba(39,88,145,.12),
                inset 0 1px 0 rgba(255,255,255,.96);
            transform: translateY(-50%);
            transition: transform .18s ease, box-shadow .18s ease;
            backdrop-filter: blur(14px) saturate(135%);
            -webkit-backdrop-filter: blur(14px) saturate(135%);
        }

        .nik-catalog2-hero-arrow:hover {
            transform: translateY(-50%) scale(1.04);
            box-shadow:
                0 18px 36px rgba(39,88,145,.16),
                inset 0 1px 0 rgba(255,255,255,.96);
        }

        .nik-catalog2-hero-arrow.is-prev {
            left: 16px;
        }

        .nik-catalog2-hero-arrow.is-next {
            right: 16px;
        }

        .nik-catalog2-hero-dots {
            position: absolute;
            left: 50%;
            bottom: 20px;
            z-index: 5;
            display: flex;
            gap: 10px;
            transform: translateX(-50%);
        }

        .nik-catalog2-hero-dot {
            width: 42px;
            height: 4px;
            padding: 0;
            border: 0;
            border-radius: 999px;
            background: rgba(15,27,51,.18);
            transition: width .2s ease, background .2s ease;
        }

        .nik-catalog2-hero-dot.is-active {
            width: 62px;
            background: #287df2;
        }

        @media (max-width: 760px) {
            .nik-catalog2-hero-grid {
                gap: 14px;
                margin-bottom: 14px;
            }

            .nik-catalog2-hero-carousel {
                min-height: 560px;
                padding: 0;
            }

            .nik-catalog2-hero-slide {
                display: block;
                padding: 28px 22px 210px;
            }

            .nik-catalog2-hero-carousel .nik-catalog2-hero-copy {
                width: 100%;
            }

            .nik-catalog2-hero-description {
                font-size: 14px;
            }

            .nik-catalog2-hero-carousel .nik-catalog2-actions {
                margin-top: 34px;
            }

            .nik-catalog2-hero-carousel .nik-catalog2-action {
                min-height: 46px;
                padding: 0 24px;
            }

            .nik-catalog2-hero-slide.has-banner-image::before {
                background:
                    linear-gradient(180deg, rgba(255,255,255,.95) 0%, rgba(255,255,255,.86) 42%, rgba(247,251,255,.54) 72%, rgba(247,251,255,.20) 100%),
                    radial-gradient(circle at 50% 22%, rgba(255,255,255,.76), transparent 20rem);
            }

            .nik-catalog2-hero-slide-image {
                position: absolute;
                right: -8px;
                bottom: 82px;
                width: min(54%, 220px);
                max-height: 150px;
                opacity: .34;
            }

            .nik-catalog2-hero-media.is-background .nik-catalog2-hero-slide-image {
                inset: auto;
                position: static;
                width: 100%;
                height: 100%;
                max-height: none;
                opacity: 1;
                object-fit: cover;
            }

            .nik-catalog2-brand-stack {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .nik-catalog2-brand-card {
                min-height: 176px;
                padding: 20px 15px;
            }

            .nik-catalog2-brand-card h3 {
                font-size: clamp(22px, 6vw, 27px);
                line-height: 1.02;
            }

            .nik-catalog2-brand-card p {
                max-width: 9.8em;
                margin: 8px 0 12px;
                font-size: 12px;
                line-height: 1.25;
            }

            .nik-catalog2-brand-card a {
                min-height: 34px;
                padding: 0 10px;
                border-radius: 12px;
                font-size: 11px;
            }

            .nik-catalog2-brand-card img {
                right: 6px;
                bottom: 8px;
                width: 54%;
                max-height: 82px;
                opacity: .42;
            }

            .nik-catalog2-hero-arrow {
                top: auto;
                bottom: 18px;
                width: 44px;
                height: 44px;
            }

            .nik-catalog2-hero-arrow.is-prev {
                left: 22px;
            }

            .nik-catalog2-hero-arrow.is-next {
                right: 22px;
            }

            .nik-catalog2-hero-dots {
                bottom: 38px;
            }

            .nik-catalog2-hero-dot {
                width: 24px;
            }

            .nik-catalog2-hero-dot.is-active {
                width: 38px;
            }
        }
    </style>
</head>
<body>
<div class="nik-catalog2">
    <div class="nik-catalog2-shell">
        <header class="nik-catalog2-header" aria-label="Навигация">
            <div class="nik-catalog2-servicebar">
                <div>
                    <span>Ваш город: Москва</span>
                    <span>Доставка по России</span>
                </div>
                <nav aria-label="Сервисная навигация">
                    @foreach ($serviceLinks as $link)
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </nav>
            </div>

            <a class="nik-catalog2-logo" href="{{ route('catalog.preview') }}" aria-label="НИКТРЕЙД">
                <img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД">
            </a>

            <button
                class="nik-catalog2-menu-toggle"
                type="button"
                aria-controls="catalog2MobileMenu"
                aria-expanded="false"
                data-catalog2-menu-toggle
            >
                <span>Меню</span>
                <span aria-hidden="true"><i></i><i></i><i></i></span>
            </button>

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

            <button class="nik-catalog2-menu-backdrop" type="button" aria-label="Закрыть меню" data-catalog2-menu-backdrop></button>

            <aside class="nik-catalog2-menu-panel" id="catalog2MobileMenu" aria-hidden="true" aria-label="Мобильное меню">
                <div class="nik-catalog2-menu-panel-head">
                    <img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД">
                    <button class="nik-catalog2-menu-close" type="button" aria-label="Закрыть меню" data-catalog2-menu-close>
                        <span aria-hidden="true"><i></i><i></i></span>
                    </button>
                </div>

                <div class="nik-catalog2-menu-service">
                    <span>Ваш город: Москва</span>
                    <span>Доставка по России</span>
                    <nav aria-label="Сервисная навигация">
                        @foreach ($serviceLinks as $link)
                            <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                        @endforeach
                    </nav>
                </div>

                <nav class="nik-catalog2-menu-links" aria-label="Основная навигация">
                    <a href="#products">Каталог</a>
                    <a href="#brands">Бренды</a>
                    <a href="#categories">Категории</a>
                    <a href="#promos">Акции</a>
                    <a href="#products">Новинки</a>
                    <a href="#about">О компании</a>
                    <a href="#footer">Доставка и оплата</a>
                </nav>

                <div class="nik-catalog2-menu-tools">
                    <div class="nik-catalog2-menu-phone">
                        8 (800) 555-35-35
                        <small>Ежедневно с 9:00 до 18:00</small>
                    </div>
                    <a class="nik-catalog2-menu-icon" href="#" aria-label="Избранное">
                        <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                    </a>
                    <a class="nik-catalog2-menu-icon" href="{{ Auth::guard('customer')->check() ? route('customer.account') : route('customer.login') }}" aria-label="Аккаунт">
                        <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                    </a>
                    <a class="nik-catalog2-menu-icon" href="{{ route('cart.index') }}" aria-label="Корзина">
                        <svg viewBox="0 0 24 24"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                        <span class="nik-catalog2-cart-count">{{ $cartQuantity }}</span>
                    </a>
                </div>
            </aside>
        </header>

        <section class="nik-catalog2-hero-grid">
            <div class="nik-catalog2-hero nik-catalog2-glass nik-catalog2-hero-carousel" data-catalog2-hero-carousel>
                <span class="nik-catalog2-bubble is-1"></span>
                <span class="nik-catalog2-bubble is-2"></span>
                <span class="nik-catalog2-bubble is-3"></span>

                <div class="nik-catalog2-hero-track">
                    @foreach ($bannerSlides as $slide)
                        @php
                            $slideStyle = array_filter([
                                filled($slide['text_color']) ? '--banner-text-color: ' . $slide['text_color'] : null,
                                filled($slide['font_stack']) ? '--banner-font-family: ' . $slide['font_stack'] : null,
                            ]);
                        @endphp
                        <article
                            class="nik-catalog2-hero-slide is-{{ $slide['theme'] }} {{ $slide['is_background'] ? 'has-banner-image' : '' }} {{ filled($slide['text_color']) ? 'has-custom-text-color' : '' }} {{ $loop->first ? 'is-active' : '' }}"
                            data-catalog2-hero-slide
                            aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                            @if ($slideStyle) style="{{ implode('; ', $slideStyle) }}" @endif
                        >
                            <div class="nik-catalog2-hero-copy">
                                @if (filled($slide['eyebrow']))
                                    <div class="nik-catalog2-eyebrow">{{ $slide['eyebrow'] }}</div>
                                @endif

                                @if (filled($slide['badge']))
                                    <div class="nik-catalog2-hero-badge">{{ $slide['badge'] }}</div>
                                @endif

                                <h1>{{ $slide['title'] }}</h1>

                                @if (filled($slide['subtitle']))
                                    <h2>{{ $slide['subtitle'] }}</h2>
                                @endif

                                @if (filled($slide['description']))
                                    <p class="nik-catalog2-hero-description">{{ $slide['description'] }}</p>
                                @endif

                                <div class="nik-catalog2-actions">
                                    <a
                                        class="nik-catalog2-action is-primary"
                                        href="{{ $slide['url'] }}"
                                        @if ($slide['target_blank']) target="_blank" rel="noopener noreferrer" @endif
                                    >
                                        {{ $slide['button_label'] }}
                                    </a>
                                </div>
                            </div>

                            <picture class="nik-catalog2-hero-media {{ $slide['is_background'] ? 'is-background' : 'is-artwork' }}">
                                @if (filled($slide['mobile_image']))
                                    <source media="(max-width: 760px)" srcset="{{ $slide['mobile_image'] }}">
                                @endif
                                <img class="nik-catalog2-hero-slide-image" src="{{ $slide['image'] }}" alt="{{ $slide['title'] }}">
                            </picture>
                        </article>
                    @endforeach
                </div>

                @if ($bannerSlides->count() > 1)
                    <button class="nik-catalog2-hero-arrow is-prev" type="button" aria-label="Предыдущий баннер" data-catalog2-hero-prev>‹</button>
                    <button class="nik-catalog2-hero-arrow is-next" type="button" aria-label="Следующий баннер" data-catalog2-hero-next>›</button>
                    <div class="nik-catalog2-hero-dots" aria-label="Слайды баннера">
                        @foreach ($bannerSlides as $slide)
                            <button
                                class="nik-catalog2-hero-dot {{ $loop->first ? 'is-active' : '' }}"
                                type="button"
                                aria-label="Показать баннер {{ $loop->iteration }}"
                                data-catalog2-hero-dot="{{ $loop->index }}"
                            ></button>
                        @endforeach
                    </div>
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

        <section class="nik-catalog2-mega-strip nik-catalog2-glass" aria-label="Направления каталога">
            <div class="nik-catalog2-mega-copy">
                <span>Каталог товаров</span>
                <strong>Быстрый выбор по задачам</strong>
            </div>
            <div class="nik-catalog2-mega-links">
                @foreach ($catalogDirections as $direction)
                    <a href="{{ $direction['url'] }}">
                        <img src="{{ $direction['image'] }}" alt="{{ $direction['label'] }}">
                        <span>{{ $direction['label'] }}</span>
                        <small>{{ $direction['caption'] }}</small>
                    </a>
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
            <div class="nik-catalog2-product-tabs" aria-label="Подборки товаров">
                @foreach ($productTabs as $tab)
                    <a class="{{ $tab['active'] ? 'is-active' : '' }}" href="{{ $tab['url'] }}">
                        {{ $tab['label'] }}
                        <span>{{ $tab['count'] }}</span>
                    </a>
                @endforeach
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
                            <span class="nik-catalog2-product-facts">
                                @if ($product->article)
                                    <span>арт. {{ $product->article }}</span>
                                @endif
                                <span class="{{ $product->availability_status === 'out_of_stock' ? 'is-wait' : 'is-stock' }}">
                                    {{ $product->availability_status === 'out_of_stock' ? 'скоро появится' : 'в наличии' }}
                                </span>
                            </span>
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
                                @php($cartQuantityForProduct = (int) ($cartProductItems->get($product->id)?->quantity ?? 0))
                                <button class="nik-catalog2-cart-btn {{ $cartQuantityForProduct > 0 ? 'is-in-cart' : '' }}" type="submit" aria-label="Добавить в корзину">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                                    @if ($cartQuantityForProduct > 0)
                                        <span>{{ $cartQuantityForProduct }}</span>
                                    @endif
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

        <section id="business" class="nik-catalog2-commerce-grid">
            <article class="nik-catalog2-commerce-card nik-catalog2-glass">
                <span>Оптовым клиентам</span>
                <h3>Поставки для бизнеса</h3>
                <p>Подберём линейку, объём и условия поставки под склад, клининг, HoReCa или автомойку.</p>
                <a href="#products">Смотреть профессиональные товары</a>
            </article>
            <article class="nik-catalog2-commerce-card nik-catalog2-glass">
                <span>Дилерам</span>
                <h3>Бренды HIBERG и ARVETERA</h3>
                <p>Реальные товарные фото, актуальные категории и быстрый переход к брендовым подборкам.</p>
                <a href="#brands">Перейти к брендам</a>
            </article>
            <article class="nik-catalog2-commerce-card nik-catalog2-glass">
                <span>Доставка</span>
                <h3>По России</h3>
                <p>Каталог собран вокруг задач покупателя: дом, автомобиль, бизнес и санитарные решения.</p>
                <a href="#categories">Выбрать направление</a>
            </article>
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
        const carousel = document.querySelector('[data-catalog2-hero-carousel]');

        if (!carousel) {
            return;
        }

        const slides = [...carousel.querySelectorAll('[data-catalog2-hero-slide]')];

        if (slides.length < 2) {
            return;
        }

        const dots = [...carousel.querySelectorAll('[data-catalog2-hero-dot]')];
        const prev = carousel.querySelector('[data-catalog2-hero-prev]');
        const next = carousel.querySelector('[data-catalog2-hero-next]');
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let activeIndex = 0;
        let timer = null;

        const setSlide = (nextIndex) => {
            const normalizedIndex = (nextIndex + slides.length) % slides.length;

            if (normalizedIndex === activeIndex) {
                return;
            }

            slides[activeIndex]?.classList.add('is-leaving');
            slides[activeIndex]?.classList.remove('is-active');
            slides[activeIndex]?.setAttribute('aria-hidden', 'true');

            dots[activeIndex]?.classList.remove('is-active');

            activeIndex = normalizedIndex;

            slides[activeIndex]?.classList.remove('is-leaving');
            slides[activeIndex]?.classList.add('is-active');
            slides[activeIndex]?.setAttribute('aria-hidden', 'false');

            dots[activeIndex]?.classList.add('is-active');
        };

        const stop = () => {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        };

        const start = () => {
            if (prefersReducedMotion || timer !== null) {
                return;
            }

            timer = window.setInterval(() => setSlide(activeIndex + 1), 6500);
        };

        prev?.addEventListener('click', () => {
            stop();
            setSlide(activeIndex - 1);
            start();
        });

        next?.addEventListener('click', () => {
            stop();
            setSlide(activeIndex + 1);
            start();
        });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                stop();
                setSlide(index);
                start();
            });
        });

        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', start);

        start();
    })();

    (() => {
        const root = document.querySelector('.nik-catalog2');
        const toggle = document.querySelector('[data-catalog2-menu-toggle]');
        const close = document.querySelector('[data-catalog2-menu-close]');
        const backdrop = document.querySelector('[data-catalog2-menu-backdrop]');
        const panel = document.getElementById('catalog2MobileMenu');

        if (!root || !toggle || !close || !backdrop || !panel) {
            return;
        }

        const setOpen = (isOpen) => {
            root.classList.toggle('is-menu-open', isOpen);
            document.body.classList.toggle('nik-catalog2-menu-lock', isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
            panel.setAttribute('aria-hidden', String(!isOpen));
        };

        toggle.addEventListener('click', () => setOpen(!root.classList.contains('is-menu-open')));
        close.addEventListener('click', () => setOpen(false));
        backdrop.addEventListener('click', () => setOpen(false));

        panel.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setOpen(false));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        });

        window.matchMedia('(min-width: 761px)').addEventListener('change', (event) => {
            if (event.matches) {
                setOpen(false);
            }
        });
    })();

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
