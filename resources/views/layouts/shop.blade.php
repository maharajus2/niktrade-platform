@php
    use Illuminate\Support\Facades\Auth;

    $icon = fn (string $name, string $class = ''): string => \App\Support\ShopIcons::svg($name, $class);
    $formatPrice = fn ($price): string => number_format((float) $price, 0, ',', ' ') . ' ₽';
    $shopHomeUrl = route('catalog.preview3');
    $accountUrl = Auth::guard('customer')->check() ? route('customer.account') : route('customer.login');
    $accountLabel = Auth::guard('customer')->check() ? 'Кабинет' : 'Войти';
    $defaultMetaDescription = 'НИКТРЕЙД: бытовая и профессиональная химия HIBERG и ARVETERA для дома и бизнеса.';
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'НИКТРЕЙД')</title>
    @if (trim($__env->yieldContent('meta_description', $defaultMetaDescription)) !== '')
        <meta name="description" content="@yield('meta_description', $defaultMetaDescription)">
    @endif
    <link rel="stylesheet" href="{{ asset('css/home-v3.css') }}?v={{ filemtime(public_path('css/home-v3.css')) }}">
    @stack('styles')
</head>
<body class="v3 @yield('body_class')">

<header>
    <div class="v3-top">
        <div class="v3-container v3-top-row">
            <a class="v3-logo" href="{{ $shopHomeUrl }}" aria-label="НИКТРЕЙД, главная">
                <img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД">
            </a>

            <form class="v3-search" role="search" method="GET" action="{{ route('catalog.index') }}" data-v3-search data-suggest-url="{{ route('search.suggest') }}">
                <input type="search" name="search" placeholder="Поиск по названию или артикулу" aria-label="Поиск по каталогу" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="v3Suggest" aria-autocomplete="list">
                <button type="submit" aria-label="Найти">{!! $icon('search') !!}</button>
                <div class="v3-suggest" id="v3Suggest" role="listbox" hidden></div>
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
                @if ($shopHasSale)
                    <a class="v3-nav-link is-sale" href="{{ route('catalog.index') }}">{!! $icon('percent') !!} Акции</a>
                @endif
                <a class="v3-nav-link is-hot" href="{{ $shopHomeUrl }}#products">{!! $icon('flame') !!} Хиты продаж</a>
                @if ($shopHasNew)
                    <a class="v3-nav-link" href="{{ $shopHomeUrl }}#products">{!! $icon('star') !!} Новинки</a>
                @endif
                <a class="v3-nav-link" href="{{ $shopHomeUrl }}#business">{!! $icon('briefcase') !!} Для бизнеса (B2B)</a>
                <a class="v3-nav-link" href="{{ $shopHomeUrl }}#brands">{!! $icon('tag') !!} Бренды</a>
                <a class="v3-nav-link" href="#footer">Доставка и оплата</a>
                <a class="v3-nav-link" href="#footer">Контакты</a>
            </nav>
        </div>

        <div class="v3-mega" id="v3Mega" hidden>
            <div class="v3-mega-col is-wide">
                <h3>Категории</h3>
                <div class="v3-mega-list">
                    @foreach ($shopCategories as $category)
                        <a href="{{ route('catalog.index', ['category' => $category->id]) }}">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>
            <div class="v3-mega-col">
                <h3>Бренды</h3>
                @foreach ($shopBrands as $brand)
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
        @yield('content')
    </div>
</main>

<footer class="v3-footer" id="footer">
    <div class="v3-container">
        <div class="v3-footer-grid">
            <div>
                <a class="v3-logo" href="{{ $shopHomeUrl }}" aria-label="НИКТРЕЙД, главная"><img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД"></a>
                <span class="v3-phone">8 (800) 555-35-35</span>
                <span>Ежедневно с 9:00 до 18:00</span>
            </div>
            <div>
                <strong class="t">Каталог</strong>
                <a href="{{ route('catalog.index') }}">Все товары</a>
                <a href="{{ $shopHomeUrl }}#brands">Бренды</a>
                <a href="{{ $shopHomeUrl }}#categories">Категории</a>
            </div>
            <div>
                <strong class="t">Покупателям</strong>
                <a href="{{ route('cart.index') }}">Корзина</a>
                <a href="{{ $accountUrl }}">{{ Auth::guard('customer')->check() ? 'Личный кабинет' : 'Вход и регистрация' }}</a>
            </div>
            <div>
                <strong class="t">Бизнесу</strong>
                <a href="{{ $shopHomeUrl }}#business">Оптовые поставки</a>
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
@stack('scripts')
</body>
</html>
