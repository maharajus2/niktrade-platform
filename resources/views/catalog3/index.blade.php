@php
    use App\Models\CartItem;
    use App\Models\Product;
    use App\Models\SiteHomepageBanner;
    use App\Support\ProductDisplayFormatter;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Storage;

    $products ??= collect();
    $brands ??= collect();
    $categories ??= collect();
    $categoryProducts ??= collect();
    $brandProducts ??= collect();
    $cartProductItems ??= collect();

    $imageUrl = fn (?string $path): string => $path ? Storage::disk('public')->url($path) : asset('images/logo-icon.png');
    $productUrl = fn (Product $product): string => route('catalog.show', $product->slug ?: $product->id);
    $formatPrice = fn ($price): string => number_format((float) $price, 0, ',', ' ') . ' ₽';
    $productVolume = fn (Product $product): ?string => ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit)
        ?: ProductDisplayFormatter::formatWeight($product->weight_value, $product->weight_unit);

    $withImages = $products->filter(fn (Product $product): bool => $product->images->isNotEmpty())->values();
    $featured = $products->take(6)->values();
    $heroArt = collect([$withImages->get(0), $withImages->get(3) ?? $withImages->get(1), $withImages->get(2)])->filter()->values();

    $saleProducts = $products->filter(fn (Product $product): bool => $product->discounted_price !== null && (float) $product->discounted_price < (float) $product->price);
    $newProducts = $products->filter(fn (Product $product): bool => (bool) $product->is_new);

    $categoryImage = function ($category): ?string {
        $path = filled($category->image_path) && Storage::disk('public')->exists($category->image_path) ? $category->image_path : null;

        return $path ? Storage::disk('public')->url($path) : null;
    };

    $categoryTiles = $categories->take(7);

    $brandTiles = collect(['hiberg' => 'HIBERG', 'arvetera' => 'ARVETERA'])->map(function (string $label, string $key) use ($brands, $brandProducts, $imageUrl) {
        $brand = $brands->first(fn ($item) => str_contains(mb_strtolower($item->name), $key));
        $photo = $brandProducts->get($key)?->first()?->images?->first()?->file_path;

        return [
            'key' => $key,
            'label' => $brand?->name ?: $label,
            'tagline' => $key === 'hiberg' ? 'Качество на каждый день' : 'Доступно и эффективно',
            'url' => $brand ? route('catalog.index', ['brand' => $brand->id]) : route('catalog.index'),
            'image' => $photo ? $imageUrl($photo) : null,
        ];
    })->values();

    $banner = ($homepageBanners ?? collect())->first();
    $bannerImagePath = $banner ? ($banner->image_path ?: $banner->mobile_image_path) : null;
    $hero = [
        'title' => $banner?->title,
        'description' => $banner?->description ?: $banner?->subtitle,
        'button' => $banner?->button_label ?: 'В каталог',
        'url' => $banner ? $banner->resolveUrl() : route('catalog.index'),
        'image' => $bannerImagePath ? $imageUrl($bannerImagePath) : null,
    ];

    $icon = fn (string $name, string $class = ''): string => \App\Support\ShopIcons::svg($name, $class);
@endphp
@extends('layouts.shop')

@section('title', 'НИКТРЕЙД — бытовая и профессиональная химия')

@section('content')

        <section class="v3-hero" aria-label="Главный баннер">
            <div class="v3-banner {{ $hero['image'] ? 'has-image' : '' }}" @if ($hero['image']) style="background-image: url('{{ $hero['image'] }}')" @endif>
                <span class="v3-bubble is-1"></span><span class="v3-bubble is-2"></span><span class="v3-bubble is-3"></span>
                <div class="v3-banner-copy">
                    @if (filled($hero['title']))
                        <h1>{{ $hero['title'] }}</h1>
                    @else
                        <h1>Чистота<br>для <em>дома и бизнеса</em></h1>
                    @endif
                    <p>{{ $hero['description'] ?: 'Бытовая и профессиональная химия HIBERG и ARVETERA: собственное производство, доставка по всей России.' }}</p>
                    <a class="v3-btn" href="{{ $hero['url'] }}">{{ $hero['button'] }} {!! $icon('arrow') !!}</a>
                </div>
                @if (! $hero['image'] && $heroArt->isNotEmpty())
                    <div class="v3-banner-art" aria-hidden="true">
                        @foreach ($heroArt as $art)
                            <img src="{{ $imageUrl($art->images->first()->file_path) }}" alt="">
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="v3-tiles">
                <a class="v3-tile is-violet" href="#brands">
                    <span class="v3-tile-ico">{!! $icon('tag') !!}</span>
                    <span><strong>Наши бренды</strong><span class="sub">HIBERG и ARVETERA {!! $icon('arrow') !!}</span></span>
                </a>
                <a class="v3-tile is-lav" href="#footer">
                    <span class="v3-tile-ico">{!! $icon('box') !!}</span>
                    <span><strong>Доставка по России</strong><span class="sub">Условия и сроки {!! $icon('arrow') !!}</span></span>
                </a>
                <a class="v3-tile is-mint" href="{{ route('business.home') }}" data-biz-link>
                    <span class="v3-tile-ico">{!! $icon('building') !!}</span>
                    <span><strong>Для бизнеса (B2B)</strong><span class="sub">Оптовые поставки {!! $icon('arrow') !!}</span></span>
                </a>
            </div>
        </section>

        <section class="v3-cats" aria-label="Категории" id="categories">
            @foreach ($categoryTiles as $category)
                @php $categoryPhoto = $categoryImage($category); @endphp
                <a class="v3-cat{{ $categoryPhoto ? ' has-img' : '' }}" href="{{ route('catalog.index', ['category' => $category->id]) }}">
                    @if ($categoryPhoto)
                        <img src="{{ $categoryPhoto }}" alt="" loading="lazy">
                    @endif
                    <span>{{ $category->name }}</span>
                </a>
            @endforeach
            <a class="v3-cat is-all" href="{{ route('catalog.index') }}">{!! $icon('grid') !!}<span>Все категории</span></a>
        </section>

        <section class="v3-benefits" aria-label="Преимущества">
            <div class="v3-benefit">{!! $icon('factory') !!}<span>Собственное<br>производство</span></div>
            <div class="v3-benefit">{!! $icon('shield') !!}<span>Контроль<br>качества</span></div>
            <div class="v3-benefit">{!! $icon('percent') !!}<span>Выгодные<br>оптовые цены</span></div>
            <div class="v3-benefit">{!! $icon('truck') !!}<span>Доставка<br>по всей России</span></div>
            <div class="v3-benefit">{!! $icon('headset') !!}<span>Поддержка<br>клиентов</span></div>
        </section>

        <section class="v3-section" id="products">
            <div class="v3-head">
                <h2>Рекомендуем</h2>
                <a href="{{ route('catalog.index') }}">Смотреть весь каталог {!! $icon('arrow') !!}</a>
            </div>
            <div class="v3-products">
                @forelse ($featured as $product)
                    @include('shop.partials.product-card', ['product' => $product])
                @empty
                    <div class="v3-empty">Товары скоро появятся.</div>
                @endforelse
            </div>
        </section>

        <section class="v3-section" id="brands" aria-label="Бренды">
            <div class="v3-head"><h2>Наши бренды</h2></div>
            <div class="v3-brands">
                @foreach ($brandTiles as $brandTile)
                    <a class="v3-brand is-{{ $brandTile['key'] }}" href="{{ $brandTile['url'] }}">
                        <div>
                            <h3>{{ $brandTile['label'] }}</h3>
                            <p>{{ $brandTile['tagline'] }}</p>
                            <span class="more">Смотреть товары {!! $icon('arrow') !!}</span>
                        </div>
                        @if ($brandTile['image'])
                            <img src="{{ $brandTile['image'] }}" alt="" loading="lazy">
                        @endif
                    </a>
                @endforeach
            </div>
        </section>

        <section class="v3-section" id="business">
            <div class="v3-b2b">
                <div>
                    <span class="k">Для бизнеса</span>
                    <h2>Поставки для склада, клининга, HoReCa и автомойки</h2>
                    <p>Портал для компаний: заявки по общему прайсу, персональная скидка и счёт от менеджера.</p>
                </div>
                <a class="v3-btn" href="{{ route('business.home') }}" data-biz-link>Перейти в портал {!! $icon('arrow') !!}</a>
            </div>
        </section>
@endsection
