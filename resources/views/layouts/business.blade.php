@php
    use Illuminate\Support\Facades\Auth;

    $icon = fn (string $name, string $class = ''): string => \App\Support\ShopIcons::svg($name, $class);
    $contractor = Auth::guard('business')->user();
    $shopHomeUrl = route('catalog.preview3');
    $defaultMetaDescription = 'Портал корпоративных клиентов НИКТРЕЙД: поставки профессиональной химии HIBERG и ARVETERA для бизнеса.';
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Портал для бизнеса — НИКТРЕЙД')</title>
    <meta name="description" content="@yield('meta_description', $defaultMetaDescription)">
    <link rel="stylesheet" href="{{ asset('css/home-v3.css') }}?v={{ filemtime(public_path('css/home-v3.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/business.css') }}?v={{ filemtime(public_path('css/business.css')) }}">
    @stack('styles')
</head>
<body class="v3 biz @yield('body_class')">

<header>
    <div class="v3-top">
        <div class="v3-container biz-top-row">
            <a class="v3-logo" href="{{ route('business.home') }}" aria-label="НИКТРЕЙД, портал для бизнеса">
                <img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД">
            </a>

            <span class="biz-badge">Портал корпоративных клиентов</span>

            <a class="v3-tool biz-shop-link" href="{{ $shopHomeUrl }}">{!! $icon('arrow', 'is-back') !!}<span>В магазин для дома</span></a>

            @if ($contractor)
                <a class="v3-tool is-stack is-user" href="{{ route('business.account') }}">{!! $icon('user') !!}<span>{{ \Illuminate\Support\Str::limit($contractor->company_name, 22) }}</span></a>
                <a class="v3-tool is-stack biz-cart-link" href="{{ route('business.cart') }}" aria-label="Заявка">{!! $icon('cart') !!}<span>Заявка</span><b class="biz-cart-badge" data-biz-cart-count @if (($bizCartCount ?? 0) < 1) hidden @endif>{{ $bizCartCount ?? 0 }}</b></a>
            @else
                <a class="v3-tool is-stack is-user" href="{{ route('business.home') }}#auth">{!! $icon('user') !!}<span>Войти</span></a>
            @endif
        </div>
    </div>

    <div class="v3-navwrap">
        <div class="v3-container">
            <nav class="v3-nav" aria-label="Навигация портала">
                <a class="v3-nav-link" href="{{ route('business.home') }}#how">{!! $icon('flame') !!} Как это работает</a>
                <a class="v3-nav-link" href="{{ route('business.home') }}#advantages">{!! $icon('star') !!} Преимущества</a>
                <a class="v3-nav-link" href="{{ route('business.home') }}#terms">{!! $icon('percent') !!} Условия</a>
                <a class="v3-nav-link" href="#footer">Контакты</a>
                @if ($contractor)
                    <a class="v3-nav-link" href="{{ route('business.catalog') }}">{!! $icon('grid') !!} Каталог</a>
                    <a class="v3-nav-link" href="{{ route('business.requests.index') }}">{!! $icon('box') !!} Мои заявки</a>
                    <a class="v3-nav-link is-account" href="{{ route('business.account') }}">{!! $icon('briefcase') !!} Кабинет компании</a>
                @endif
            </nav>
        </div>
    </div>
</header>

<main class="v3-main">
    <div class="v3-container">
        @if (session('success'))
            <div class="biz-flash" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->has('product_id') || $errors->has('quantity'))
            <div class="biz-flash is-error" role="alert">{{ $errors->first('product_id') ?: $errors->first('quantity') }}</div>
        @endif
        @yield('content')
    </div>
</main>

<footer class="v3-footer" id="footer">
    <div class="v3-container">
        <div class="v3-footer-grid">
            <div>
                <a class="v3-logo" href="{{ route('business.home') }}" aria-label="НИКТРЕЙД, портал для бизнеса"><img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД"></a>
                <span class="v3-phone">8 (800) 555-35-35</span>
                <span>Ежедневно с 9:00 до 18:00</span>
            </div>
            <div>
                <strong class="t">Портал</strong>
                <a href="{{ route('business.home') }}#how">Как это работает</a>
                <a href="{{ route('business.home') }}#terms">Условия сотрудничества</a>
                @if ($contractor)
                    <a href="{{ route('business.catalog') }}">Каталог</a>
                    <a href="{{ route('business.account') }}">Кабинет компании</a>
                @else
                    <a href="{{ route('business.home') }}#auth">Вход и регистрация</a>
                @endif
            </div>
            <div>
                <strong class="t">Розничный магазин</strong>
                <a href="{{ $shopHomeUrl }}">Главная магазина</a>
                <a href="{{ route('catalog.index') }}">Каталог для дома</a>
            </div>
            <div></div>
        </div>
        <div class="v3-copy">© {{ date('Y') }} НИКТРЕЙД. Все права защищены.</div>
    </div>
</footer>

@if ($contractor)
    @php $bizMissing = max(0, (float) config('business.min_request_amount', 5000) - (float) ($bizCartTotal ?? 0)); @endphp
    @unless (request()->routeIs('business.cart'))
        <div class="biz-bar" data-biz-bar @if (($bizCartCount ?? 0) < 1) hidden @endif>
            <div class="biz-bar-inner">
                <div class="biz-bar-sum"><span>Ваша заявка</span><strong data-biz-cart-total>{{ number_format((float) ($bizCartTotal ?? 0), fmod((float) ($bizCartTotal ?? 0), 1) == 0.0 ? 0 : 2, ',', ' ') }}&nbsp;₽</strong></div>
                <span class="biz-bar-note @if ($bizMissing <= 0) is-ok @endif" data-biz-min-note>{{ $bizMissing > 0 ? 'До минимума ещё '.number_format($bizMissing, fmod($bizMissing, 1) == 0.0 ? 0 : 2, ',', ' ').' ₽' : 'Можно отправлять заявку' }}</span>
                <a class="biz-btn" href="{{ route('business.cart') }}">Перейти к заявке</a>
            </div>
        </div>
    @endunless
    <script src="{{ asset('js/biz-cart.js') }}?v={{ filemtime(public_path('js/biz-cart.js')) }}" defer></script>
@endif
@stack('scripts')
</body>
</html>
