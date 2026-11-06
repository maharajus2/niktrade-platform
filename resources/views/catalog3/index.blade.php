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

    $cartQuantity = 0;
    $cartTotal = 0.0;
    $dockItems = [];

    if (request()->hasSession()) {
        $cartScope = fn () => CartItem::query()->whereHas('cart', fn ($query) => $query
            ->where('session_id', request()->session()->getId())
            ->where('status', 'active'));
        $cartQuantity = (int) $cartScope()->sum('quantity');
        $cartTotal = (float) $cartScope()->sum('line_total');
        $dockItems = $cartScope()
            ->with(['product.images' => fn ($query) => $query->orderByDesc('is_main')->orderBy('sort_order')->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(fn (CartItem $item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => (string) $item->product?->name,
                'image' => ($path = $item->product?->images->first()?->file_path) ? Storage::disk('public')->url($path) : null,
                'quantity' => (int) $item->quantity,
            ])
            ->values()
            ->all();
    }

    $withImages = $products->filter(fn (Product $product): bool => $product->images->isNotEmpty())->values();
    $featured = $products->take(6)->values();
    $heroArt = collect([$withImages->get(0), $withImages->get(3) ?? $withImages->get(1), $withImages->get(2)])->filter()->values();

    $saleProducts = $products->filter(fn (Product $product): bool => $product->discounted_price !== null && (float) $product->discounted_price < (float) $product->price);
    $newProducts = $products->filter(fn (Product $product): bool => (bool) $product->is_new);

    $categoryImage = function ($category) use ($categoryProducts, $imageUrl): string {
        $path = filled($category->image_path) && Storage::disk('public')->exists($category->image_path) ? $category->image_path : null;

        return $imageUrl($path ?? $categoryProducts->get($category->id)?->images?->first()?->file_path);
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

    $sprite = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'pin' => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'user' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
        'cart' => '<path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>',
        'percent' => '<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2"/><circle cx="17" cy="17" r="2"/>',
        'flame' => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.4-.5-2-1-3-1.1-2.1-.2-4 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.2.4-2.3 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'briefcase' => '<path d="M3 8h18v12H3z"/><path d="M8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 13h18"/>',
        'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        'truck' => '<path d="M2 6h12v10H2z"/><path d="M14 10h4l4 3v3h-8z"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
        'shield' => '<path d="M12 3 4 6v6c0 5 3.4 8 8 9 4.6-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'factory' => '<path d="M3 21V10l6 3v-3l6 3V6h6v15z"/>',
        'headset' => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><path d="M4 14h3v5H4zM17 14h3v5h-3z"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12 4 7.5M12 12l8-4.5M12 12v9"/>',
        'building' => '<path d="M4 21V4h10v17M14 9h6v12M8 8h2M8 12h2M8 16h2M17 13h0M17 17h0"/>',
    ];
    $icon = fn (string $name, string $class = ''): string => '<svg class="i ' . $class . '" viewBox="0 0 24 24" aria-hidden="true">' . ($sprite[$name] ?? '') . '</svg>';

    $accountUrl = Auth::guard('customer')->check() ? route('customer.account') : route('customer.login');
    $accountLabel = Auth::guard('customer')->check() ? 'Кабинет' : 'Войти';
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Главная (вариант 3) - НИКТРЕЙД</title>
    <meta name="description" content="НИКТРЕЙД: бытовая и профессиональная химия HIBERG и ARVETERA для дома и бизнеса.">
    <link rel="stylesheet" href="{{ asset('css/home-v3.css') }}?v={{ filemtime(public_path('css/home-v3.css')) }}">
</head>
<body class="v3">

<header>
    <div class="v3-top">
        <div class="v3-container v3-top-row">
            <a class="v3-logo" href="{{ route('catalog.preview3') }}" aria-label="НИКТРЕЙД, главная">
                <img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД">
            </a>

            <form class="v3-search" role="search" method="GET" action="{{ route('catalog.index') }}">
                <input type="search" name="search" placeholder="Поиск по названию или артикулу" aria-label="Поиск по каталогу" autocomplete="off">
                <button type="submit" aria-label="Найти">{!! $icon('search') !!}</button>
            </form>

            <span class="v3-tool is-city">{!! $icon('pin') !!} Москва</span>

            <a class="v3-tool is-stack is-user" href="{{ $accountUrl }}">{!! $icon('user') !!}<span>{{ $accountLabel }}</span></a>

            <a class="v3-cart" href="{{ route('cart.index') }}" aria-label="Корзина">
                <span class="v3-cart-ico">
                    {!! $icon('cart') !!}
                    @if ($cartQuantity > 0)<span class="v3-cart-count">{{ $cartQuantity }}</span>@endif
                </span>
                <span class="total {{ $cartQuantity > 0 ? '' : 'is-empty' }}">{{ $cartQuantity > 0 ? $formatPrice($cartTotal) : 'Корзина' }}</span>
            </a>
        </div>
    </div>

    <div class="v3-navwrap">
        <div class="v3-container">
            <nav class="v3-nav" aria-label="Основная навигация">
                <button class="v3-catalog-btn" type="button" aria-expanded="false" aria-controls="v3Mega" data-v3-mega-toggle>
                    {!! $icon('menu') !!} Каталог товаров
                </button>
                @if ($saleProducts->isNotEmpty())
                    <a class="v3-nav-link is-sale" href="{{ route('catalog.index') }}">{!! $icon('percent') !!} Акции</a>
                @endif
                <a class="v3-nav-link is-hot" href="#products">{!! $icon('flame') !!} Хиты продаж</a>
                @if ($newProducts->isNotEmpty())
                    <a class="v3-nav-link" href="#products">{!! $icon('star') !!} Новинки</a>
                @endif
                <a class="v3-nav-link" href="#business">{!! $icon('briefcase') !!} Для бизнеса (B2B)</a>
                <a class="v3-nav-link" href="#brands">{!! $icon('tag') !!} Бренды</a>
                <a class="v3-nav-link" href="#footer">Доставка и оплата</a>
                <a class="v3-nav-link" href="#footer">Контакты</a>
            </nav>
        </div>

        <div class="v3-mega" id="v3Mega" hidden>
            <div class="v3-mega-col is-wide">
                <h3>Категории</h3>
                <div class="v3-mega-list">
                    @foreach ($categories as $category)
                        <a href="{{ route('catalog.index', ['category' => $category->id]) }}">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>
            <div class="v3-mega-col">
                <h3>Бренды</h3>
                @foreach ($brands as $brand)
                    <a href="{{ route('catalog.index', ['brand' => $brand->id]) }}">{{ $brand->name }}</a>
                @endforeach
            </div>
            <div class="v3-mega-col">
                <h3>Направления</h3>
                <a href="{{ route('catalog.index', ['direction' => 'home']) }}">Для дома</a>
                <a href="{{ route('catalog.index', ['direction' => 'professional']) }}">Для бизнеса</a>
                <a class="v3-mega-all" href="{{ route('catalog.index') }}">Весь каталог →</a>
            </div>
        </div>
    </div>
</header>

<main class="v3-main">
    <div class="v3-container">

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
                <a class="v3-tile is-mint" href="#business">
                    <span class="v3-tile-ico">{!! $icon('building') !!}</span>
                    <span><strong>Для бизнеса (B2B)</strong><span class="sub">Оптовые поставки {!! $icon('arrow') !!}</span></span>
                </a>
            </div>
        </section>

        <section class="v3-cats" aria-label="Категории" id="categories">
            @foreach ($categoryTiles as $category)
                <a class="v3-cat" href="{{ route('catalog.index', ['category' => $category->id]) }}">
                    <img src="{{ $categoryImage($category) }}" alt="" loading="lazy">
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
                    @php
                        $photo = $product->images->first()?->file_path;
                        $price = $product->discounted_price ?: $product->price;
                        $hasSale = $product->discounted_price !== null && (float) $product->discounted_price < (float) $product->price;
                        $outOfStock = $product->availability_status === 'out_of_stock';
                        $inCart = (int) ($cartProductItems->get($product->id)?->quantity ?? 0);
                    @endphp
                    <article class="v3-card" id="v3-product-{{ $product->id }}" data-v3-card data-product-id="{{ $product->id }}" data-item-id="{{ $cartProductItems->get($product->id)?->id }}" data-qty="{{ $inCart }}" data-add-url="{{ route('cart.add', $product->slug ?: $product->id) }}">
                        @if ($hasSale)
                            <span class="v3-badge is-sale">Акция</span>
                        @elseif ($product->is_new)
                            <span class="v3-badge is-new">Новинка</span>
                        @elseif ($product->is_best_seller || $product->is_featured)
                            <span class="v3-badge">Хит</span>
                        @endif
                        <a class="v3-card-img" href="{{ $productUrl($product) }}" tabindex="-1" aria-hidden="true">
                            <img src="{{ $imageUrl($photo) }}" alt="" loading="lazy">
                        </a>
                        <div class="v3-card-brand">{{ $product->brand?->name ?: 'НИКТРЕЙД' }}</div>
                        <a class="v3-card-name" href="{{ $productUrl($product) }}">{{ $product->name }}</a>
                        <div class="v3-card-meta">
                            @if ($productVolume($product)){{ $productVolume($product) }}@endif
                            @if ($product->article) · арт. {{ $product->article }}@endif
                        </div>
                        <div class="v3-stock {{ $outOfStock ? 'is-wait' : 'is-in' }}">{{ $outOfStock ? 'Скоро появится' : 'В наличии' }}</div>
                        <div class="v3-card-buy">
                            <div class="v3-price">
                                {{ $formatPrice($price) }}
                                @if ($hasSale)<del>{{ $formatPrice($product->price) }}</del>@endif
                            </div>
                            <div class="v3-buy-slot">
                                <div class="v3-qty" role="group" aria-label="Количество в корзине" @if ($inCart < 1 || $outOfStock) hidden @endif>
                                    <button class="v3-qty-btn" type="button" data-v3-dec aria-label="Уменьшить количество">−</button>
                                    <span class="v3-qty-num" aria-live="polite" data-v3-num>{{ $inCart }}</span>
                                    <button class="v3-qty-btn" type="button" data-v3-inc aria-label="Увеличить количество">+</button>
                                </div>
                                <form method="POST" action="{{ route('cart.add', $product->slug ?: $product->id) }}" data-v3-add-form @if ($inCart > 0 && ! $outOfStock) hidden @endif>
                                    @csrf
                                    <button class="v3-add" type="submit" aria-label="{{ $outOfStock ? 'Скоро появится' : 'В корзину' }}" @disabled($outOfStock)>
                                        {!! $icon('cart') !!}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
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
                    <p>Подберём линейку, объём и условия поставки под ваши задачи.</p>
                </div>
                <a class="v3-btn" href="{{ route('catalog.index', ['direction' => 'professional']) }}">Профессиональные товары {!! $icon('arrow') !!}</a>
            </div>
        </section>
    </div>
</main>

<footer class="v3-footer" id="footer">
    <div class="v3-container">
        <div class="v3-footer-grid">
            <div>
                <a class="v3-logo" href="{{ route('catalog.preview3') }}" aria-label="НИКТРЕЙД, главная"><img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД"></a>
                <span class="v3-phone">8 (800) 555-35-35</span>
                <span>Ежедневно с 9:00 до 18:00</span>
            </div>
            <div>
                <strong class="t">Каталог</strong>
                <a href="{{ route('catalog.index') }}">Все товары</a>
                <a href="#brands">Бренды</a>
                <a href="#categories">Категории</a>
            </div>
            <div>
                <strong class="t">Покупателям</strong>
                <a href="{{ route('cart.index') }}">Корзина</a>
                <a href="{{ $accountUrl }}">{{ Auth::guard('customer')->check() ? 'Личный кабинет' : 'Вход и регистрация' }}</a>
            </div>
            <div>
                <strong class="t">Бизнесу</strong>
                <a href="#business">Оптовые поставки</a>
                <a href="{{ route('catalog.index', ['direction' => 'professional']) }}">Профессиональная химия</a>
            </div>
        </div>
        <div class="v3-copy">© {{ date('Y') }} НИКТРЕЙД. Все права защищены.</div>
    </div>
</footer>

<aside class="v3-dock" id="v3Dock" aria-label="Мини-корзина" hidden>
    <div class="v3-dock-in">
        <div class="v3-dock-sum">
            <strong>Корзина</strong>
            <span data-v3-dock-count></span>
        </div>
        <div class="v3-dock-items" data-v3-dock-items></div>
        <div class="v3-dock-end">
            <button class="v3-dock-clear" type="button" data-v3-dock-clear>Очистить</button>
            <span class="v3-dock-total" data-v3-dock-total></span>
            <a class="v3-dock-go" href="{{ route('cart.index') }}">Оформить заказ</a>
            <button class="v3-dock-close" type="button" data-v3-dock-close aria-label="Скрыть мини-корзину">×</button>
        </div>
    </div>
</aside>

<script>
    window.V3_CART = {
        updateUrl: @json(route('cart.items.update', '__ID__')),
        destroyUrl: @json(route('cart.items.destroy', '__ID__')),
        initialCount: {{ $cartQuantity }},
        initialTotal: @json($formatPrice($cartTotal)),
        initialItems: @json($dockItems),
    };
</script>
<script src="{{ asset('js/home-v3.js') }}?v={{ filemtime(public_path('js/home-v3.js')) }}" defer></script>
</body>
</html>
