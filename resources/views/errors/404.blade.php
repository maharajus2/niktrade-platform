@extends('layouts.shop')

@section('title', 'Страница не найдена — НИКТРЕЙД')

@push('styles')
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="{{ asset('css/shop-catalog.css') }}?v={{ filemtime(public_path('css/shop-catalog.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/shop-404.css') }}?v={{ filemtime(public_path('css/shop-404.css')) }}">
@endpush

@section('content')
    <section class="shop-404">
        <div class="shop-404-copy">
            <div class="shop-404-code">404</div>
            <h1>Уборщица всё выбросила</h1>
            <blockquote>«Мы просили убрать мусор. Кто же знал, что она и страницу выбросит?»</blockquote>
            <p>Страница, которую вы ищете, не найдена. Возможно, её уже вынесли вместе с мусором, а может быть, в адресе опечатка.</p>
            <div class="shop-404-actions">
                <a class="shop-btn" href="{{ route('catalog.index') }}">Перейти в каталог</a>
                <a class="shop-404-link" href="{{ route('catalog.preview3') }}">На главную</a>
            </div>
        </div>

        <div class="shop-404-art" aria-hidden="true">
            <svg viewBox="0 0 560 340" role="img" focusable="false">
                <ellipse cx="280" cy="316" rx="256" ry="14" fill="#e4e1fb"/>

                <!-- урна с табличкой -->
                <g class="shop-404-sign">
                    <rect x="446" y="52" width="8" height="104" rx="3" fill="#b0834f"/>
                    <g transform="rotate(7 450 52)">
                        <rect x="392" y="8" width="116" height="66" rx="9" fill="#fff" stroke="#10172f" stroke-width="4"/>
                        <text x="450" y="54" text-anchor="middle" font-family="Manrope, Arial, sans-serif" font-size="40" font-weight="800" fill="#e5484d">404</text>
                    </g>
                </g>
                <path d="M410 152h80l-8 150h-64z" fill="#cfd0ee" stroke="#5348d8" stroke-width="4" stroke-linejoin="round"/>
                <path d="M436 164l-3 128M450 164v128M464 164l3 128" stroke="#5348d8" stroke-width="3" stroke-linecap="round" opacity=".55"/>
                <rect x="400" y="138" width="100" height="16" rx="8" fill="#8a7bf5" stroke="#5348d8" stroke-width="3" transform="rotate(-6 450 146)"/>
                <circle cx="424" cy="146" r="10" fill="#fff" stroke="#10172f" stroke-width="3"/>
                <circle cx="478" cy="142" r="8" fill="#fde6ee" stroke="#10172f" stroke-width="3"/>

                <!-- тележка уборщицы -->
                <line x1="238" y1="214" x2="208" y2="168" stroke="#10172f" stroke-width="6" stroke-linecap="round"/>
                <line x1="300" y1="226" x2="316" y2="112" stroke="#b0834f" stroke-width="7" stroke-linecap="round"/>
                <rect x="294" y="98" width="30" height="18" rx="6" fill="#8a7bf5" stroke="#10172f" stroke-width="3" transform="rotate(8 309 107)"/>
                <path d="M254 222h94l-10 70h-74z" fill="#3dc9a8" stroke="#10172f" stroke-width="4" stroke-linejoin="round"/>
                <rect x="242" y="206" width="118" height="16" rx="8" fill="#10172f"/>
                <rect x="328" y="170" width="18" height="38" rx="6" fill="#fdeede" stroke="#10172f" stroke-width="3"/>
                <rect x="332" y="160" width="10" height="12" rx="3" fill="#5348d8"/>
                <circle cx="272" cy="302" r="11" fill="#10172f"/>
                <circle cx="272" cy="302" r="4" fill="#cfd0ee"/>
                <circle cx="330" cy="302" r="11" fill="#10172f"/>
                <circle cx="330" cy="302" r="4" fill="#cfd0ee"/>

                <!-- уборщица -->
                <rect x="156" y="246" width="17" height="54" rx="6" fill="#10172f"/>
                <rect x="182" y="246" width="17" height="54" rx="6" fill="#10172f"/>
                <ellipse cx="163" cy="303" rx="14" ry="7" fill="#5348d8"/>
                <ellipse cx="193" cy="303" rx="14" ry="7" fill="#5348d8"/>
                <rect x="146" y="152" width="62" height="104" rx="24" fill="#5348d8"/>
                <rect x="158" y="190" width="38" height="46" rx="10" fill="#dcd8ff"/>
                <line x1="152" y1="172" x2="128" y2="146" stroke="#f6c9a6" stroke-width="13" stroke-linecap="round"/>
                <line x1="202" y1="172" x2="212" y2="170" stroke="#f6c9a6" stroke-width="13" stroke-linecap="round"/>
                <circle cx="177" cy="122" r="27" fill="#f6c9a6"/>
                <circle cx="152" cy="132" r="9" fill="#6b3f2a"/>
                <path d="M150 118a27 27 0 0 1 54 0z" fill="#8a7bf5"/>
                <rect x="170" y="112" width="40" height="8" rx="4" fill="#5348d8"/>
                <circle cx="168" cy="128" r="3" fill="#10172f"/>
                <circle cx="187" cy="128" r="3" fill="#10172f"/>
                <ellipse cx="177" cy="140" rx="5" ry="6" fill="#10172f"/>
            </svg>
        </div>
    </section>
@endsection
