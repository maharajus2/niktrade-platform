@extends('layouts.public')

@section('title', 'Корзина')

@push('styles')
    <style>
        body {
            min-width: 320px;
            background:
                radial-gradient(circle at 8% 2%, rgba(10, 132, 255, .12), transparent 28rem),
                radial-gradient(circle at 92% 9%, rgba(33, 201, 139, .11), transparent 28rem),
                linear-gradient(135deg, #fbfdff 0%, #eef8ff 46%, #ffffff 100%);
            background-attachment: fixed;
            color: #10223f;
        }

        .nik-cart-page,
        .nik-cart-page * {
            box-sizing: border-box;
        }

        .nik-cart-page {
            --liquid-blue: #0a84ff;
            --liquid-blue-deep: #006eea;
            --liquid-green: #21c98b;
            --liquid-ink: #10223f;
            --liquid-muted: rgba(16, 34, 63, .62);
            --liquid-soft: rgba(255, 255, 255, .58);
            --liquid-border: rgba(255, 255, 255, .68);
            --liquid-line: rgba(16, 34, 63, .10);
            --liquid-shadow: 0 20px 54px rgba(20, 82, 148, .10), 0 1px 0 rgba(255, 255, 255, .72);
            position: relative;
            isolation: isolate;
            min-height: calc(100vh - 64px);
            overflow-x: clip;
            padding: 28px 0 72px;
        }

        .nik-cart-page::before {
            position: fixed;
            inset: 0;
            z-index: -1;
            background:
                radial-gradient(circle at 4% 18%, rgba(255, 255, 255, .56) 0 16px, transparent 17px),
                radial-gradient(circle at 96% 16%, rgba(255, 255, 255, .44) 0 22px, transparent 23px),
                linear-gradient(118deg, rgba(255, 255, 255, .24), transparent 30%, rgba(33, 201, 139, .05) 70%, transparent);
            opacity: .72;
            pointer-events: none;
            content: "";
        }

        .nik-cart-shell {
            width: min(1360px, calc(100% - 48px));
            margin: 0 auto;
        }

        .nik-cart-glass {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--liquid-border);
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .64), rgba(236, 249, 255, .34) 56%, rgba(255, 255, 255, .52));
            box-shadow: var(--liquid-shadow), inset 0 1px 0 rgba(255, 255, 255, .72);
            backdrop-filter: blur(20px) saturate(145%);
            -webkit-backdrop-filter: blur(20px) saturate(145%);
            isolation: isolate;
        }

        .nik-cart-glass::before {
            position: absolute;
            inset: 0;
            z-index: 0;
            border-radius: inherit;
            background:
                linear-gradient(128deg, rgba(255, 255, 255, .42), transparent 32%),
                linear-gradient(312deg, rgba(10, 132, 255, .055), transparent 48%);
            opacity: .68;
            pointer-events: none;
            content: "";
        }

        .nik-cart-glass > * {
            position: relative;
            z-index: 1;
        }

        .nik-cart-header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: end;
            margin-bottom: 24px;
        }

        .nik-cart-title {
            margin: 0;
            color: var(--liquid-ink);
            font-size: clamp(32px, 4vw, 48px);
            font-weight: 900;
            line-height: 1.04;
        }

        .nik-cart-subtitle {
            margin: 8px 0 0;
            color: var(--liquid-muted);
            font-size: 15px;
            font-weight: 700;
        }

        .nik-cart-link,
        .nik-cart-button,
        .nik-cart-checkout {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 44px;
            border: 0;
            border-radius: 12px;
            font: inherit;
            font-weight: 900;
            line-height: 1;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, color .18s ease;
        }

        .nik-cart-link {
            flex: 0 0 auto;
            padding: 0 16px;
            background: rgba(255, 255, 255, .62);
            box-shadow: inset 0 0 0 1px rgba(10, 132, 255, .18);
            color: var(--liquid-blue-deep);
        }

        .nik-cart-link:hover,
        .nik-cart-button:hover,
        .nik-cart-checkout:hover {
            transform: translateY(-1px);
        }

        .nik-cart-alert {
            width: fit-content;
            max-width: 100%;
            margin-bottom: 16px;
            border-radius: 14px;
            padding: 11px 14px;
            background: rgba(236, 253, 245, .78);
            box-shadow: inset 0 0 0 1px rgba(33, 201, 139, .20);
            color: #137a53;
            font-weight: 800;
        }

        .nik-cart-alert.is-error {
            background: rgba(254, 242, 242, .82);
            box-shadow: inset 0 0 0 1px rgba(220, 38, 38, .16);
            color: #b42318;
        }

        .nik-cart-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(330px, 380px);
            gap: 28px;
            align-items: start;
        }

        .nik-cart-list {
            border-radius: 26px;
            padding: 6px 22px;
        }

        .nik-cart-row {
            display: grid;
            grid-template-columns: 128px minmax(220px, 1fr) minmax(104px, .56fr) 146px minmax(118px, .58fr) 74px;
            gap: 18px;
            align-items: center;
            min-width: 0;
            padding: 20px 0;
        }

        .nik-cart-row + .nik-cart-row {
            border-top: 1px solid var(--liquid-line);
        }

        .nik-cart-image {
            display: grid;
            place-items: center;
            width: 128px;
            height: 128px;
            overflow: hidden;
            border-radius: 20px;
            background:
                radial-gradient(circle at 50% 18%, rgba(255, 255, 255, .78), transparent 42%),
                linear-gradient(135deg, rgba(255, 255, 255, .44), rgba(224, 246, 255, .28));
            box-shadow: inset 0 0 0 1px rgba(10, 132, 255, .10);
            color: rgba(16, 34, 63, .42);
            font-size: 13px;
            font-weight: 800;
            text-align: center;
            text-decoration: none;
        }

        .nik-cart-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            filter: drop-shadow(0 12px 18px rgba(26, 83, 140, .10));
        }

        .nik-cart-info {
            min-width: 0;
        }

        .nik-cart-name {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            line-height: 1.28;
        }

        .nik-cart-name a {
            color: var(--liquid-ink);
            text-decoration: none;
        }

        .nik-cart-name a:hover {
            color: var(--liquid-blue-deep);
        }

        .nik-cart-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
            color: var(--liquid-muted);
            font-size: 13px;
            font-weight: 750;
        }

        .nik-cart-chip {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            border-radius: 999px;
            padding: 4px 9px;
            background: rgba(255, 255, 255, .56);
            box-shadow: inset 0 0 0 1px rgba(16, 34, 63, .08);
            white-space: nowrap;
        }

        .nik-cart-cell {
            display: grid;
            gap: 5px;
            min-width: 0;
            color: var(--liquid-ink);
            font-weight: 900;
        }

        .nik-cart-label {
            color: rgba(16, 34, 63, .48);
            font-size: 12px;
            font-weight: 800;
        }

        .nik-cart-price {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: baseline;
        }

        .nik-cart-old-price {
            color: rgba(16, 34, 63, .38);
            font-size: 13px;
            font-weight: 800;
            text-decoration: line-through;
        }

        .nik-cart-line-total {
            color: #137a53;
            font-size: 18px;
        }

        .nik-cart-quantity {
            display: inline-grid;
            grid-template-columns: 42px minmax(44px, auto) 42px;
            width: fit-content;
            overflow: hidden;
            border-radius: 13px;
            background: rgba(255, 255, 255, .72);
            box-shadow: inset 0 0 0 1px rgba(10, 132, 255, .16);
        }

        .nik-cart-quantity-form {
            display: contents;
        }

        .nik-cart-quantity-button,
        .nik-cart-quantity-value {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 42px;
        }

        .nik-cart-quantity-button {
            border: 0;
            background: transparent;
            color: var(--liquid-blue-deep);
            cursor: pointer;
            font: inherit;
            font-size: 18px;
            font-weight: 900;
        }

        .nik-cart-quantity-button:hover {
            background: rgba(10, 132, 255, .08);
        }

        .nik-cart-quantity-value {
            min-width: 44px;
            border-inline: 1px solid rgba(16, 34, 63, .10);
            color: var(--liquid-ink);
            font-weight: 900;
            padding: 0 10px;
        }

        .nik-cart-actions {
            display: flex;
            justify-content: end;
        }

        .nik-cart-remove {
            border: 0;
            border-radius: 10px;
            background: rgba(255, 255, 255, .50);
            box-shadow: inset 0 0 0 1px rgba(16, 34, 63, .08);
            color: rgba(16, 34, 63, .56);
            cursor: pointer;
            font: inherit;
            font-size: 13px;
            font-weight: 900;
            min-height: 38px;
            padding: 0 12px;
            transition: background-color .18s ease, color .18s ease, box-shadow .18s ease;
        }

        .nik-cart-remove:hover {
            background: rgba(254, 242, 242, .82);
            box-shadow: inset 0 0 0 1px rgba(220, 38, 38, .16);
            color: #b42318;
        }

        .nik-cart-summary {
            position: sticky;
            top: 18px;
            display: grid;
            gap: 16px;
            border-radius: 24px;
            padding: 22px;
        }

        .nik-cart-summary-title {
            margin: 0;
            font-size: 22px;
            font-weight: 900;
            line-height: 1.15;
        }

        .nik-cart-summary-lines {
            display: grid;
            gap: 12px;
        }

        .nik-cart-total-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            color: var(--liquid-muted);
            font-size: 15px;
            font-weight: 750;
        }

        .nik-cart-total-row strong {
            color: var(--liquid-ink);
            font-weight: 900;
            text-align: right;
        }

        .nik-cart-total-row.is-discount strong {
            color: #137a53;
        }

        .nik-cart-total-row.is-final {
            align-items: baseline;
            border-top: 1px solid var(--liquid-line);
            margin-top: 2px;
            padding-top: 16px;
            color: var(--liquid-ink);
            font-size: 20px;
            font-weight: 900;
        }

        .nik-cart-total-row.is-final strong {
            color: #137a53;
            font-size: 24px;
        }

        .nik-cart-checkout,
        .nik-cart-button {
            background: linear-gradient(135deg, var(--liquid-blue), var(--liquid-green));
            box-shadow: 0 16px 30px rgba(10, 132, 255, .20);
            color: #ffffff;
            padding: 0 18px;
        }

        .nik-cart-checkout {
            width: 100%;
            min-height: 50px;
            font-size: 16px;
        }

        .nik-cart-delivery-note {
            margin: -4px 0 0;
            color: rgba(16, 34, 63, .50);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
        }

        .nik-cart-empty {
            display: grid;
            justify-items: center;
            gap: 18px;
            border-radius: 26px;
            padding: clamp(42px, 7vw, 76px) 24px;
            text-align: center;
        }

        .nik-cart-empty-mark {
            display: grid;
            place-items: center;
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .70);
            box-shadow: inset 0 0 0 1px rgba(10, 132, 255, .12);
            color: var(--liquid-blue-deep);
            font-size: 30px;
            font-weight: 900;
        }

        .nik-cart-empty-title {
            margin: 0;
            font-size: 26px;
            font-weight: 900;
            line-height: 1.16;
        }

        .nik-cart-empty-text {
            max-width: 440px;
            margin: -8px 0 0;
            color: var(--liquid-muted);
            font-weight: 700;
            line-height: 1.5;
        }

        .nik-cart-mobile-checkout {
            display: none;
        }

        @media (max-width: 1180px) {
            .nik-cart-layout {
                grid-template-columns: minmax(0, 1fr) minmax(308px, 340px);
                gap: 22px;
            }

            .nik-cart-row {
                grid-template-columns: 112px minmax(180px, 1fr) 108px 136px 120px 64px;
                gap: 14px;
            }

            .nik-cart-image {
                width: 112px;
                height: 112px;
            }
        }

        @media (max-width: 980px) {
            .nik-cart-layout {
                grid-template-columns: 1fr;
            }

            .nik-cart-summary {
                position: static;
            }
        }

        @media (max-width: 760px) {
            .public-header__inner {
                width: calc(100% - 24px);
                max-width: 1180px;
            }

            .public-header__brand,
            .public-header__nav {
                width: auto;
                min-width: 0;
            }

            .public-header__nav {
                flex-wrap: wrap;
                justify-content: flex-start;
            }

            .public-header__link,
            .public-header__logout,
            .public-header__cart {
                white-space: nowrap;
            }

            .nik-cart-page {
                padding: 22px 0 calc(112px + env(safe-area-inset-bottom));
            }

            .nik-cart-shell {
                width: min(100% - 24px, 1360px);
            }

            .nik-cart-header {
                display: grid;
                gap: 14px;
                align-items: start;
            }

            .nik-cart-link {
                width: fit-content;
            }

            .nik-cart-list {
                border-radius: 22px;
                padding: 4px 14px;
            }

            .nik-cart-row {
                grid-template-columns: 96px minmax(0, 1fr);
                gap: 12px;
                padding: 16px 0;
            }

            .nik-cart-image {
                align-self: start;
                width: 96px;
                height: 96px;
                border-radius: 16px;
            }

            .nik-cart-info,
            .nik-cart-cell,
            .nik-cart-actions {
                grid-column: 2;
            }

            .nik-cart-info {
                align-self: start;
            }

            .nik-cart-name {
                font-size: 16px;
            }

            .nik-cart-meta {
                gap: 6px;
                margin-top: 8px;
            }

            .nik-cart-chip {
                min-height: 24px;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .nik-cart-cell {
                display: flex;
                justify-content: space-between;
                gap: 10px;
                align-items: center;
                width: 100%;
            }

            .nik-cart-cell.is-quantity {
                display: grid;
                justify-items: start;
            }

            .nik-cart-line-total {
                font-size: 17px;
            }

            .nik-cart-actions {
                justify-content: start;
            }

            .nik-cart-remove {
                min-height: 34px;
                padding-inline: 10px;
            }

            .nik-cart-summary {
                border-radius: 22px;
                padding: 18px;
            }

            .nik-cart-summary .nik-cart-checkout {
                display: none;
            }

            .nik-cart-mobile-checkout {
                position: fixed;
                right: 0;
                bottom: 0;
                left: 0;
                z-index: 50;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 14px;
                align-items: center;
                padding: 12px 14px calc(12px + env(safe-area-inset-bottom));
                border-top: 1px solid rgba(255, 255, 255, .68);
                background: rgba(255, 255, 255, .88);
                box-shadow: 0 -14px 34px rgba(20, 82, 148, .12);
                backdrop-filter: blur(18px) saturate(150%);
                -webkit-backdrop-filter: blur(18px) saturate(150%);
            }

            .nik-cart-mobile-total {
                display: grid;
                gap: 3px;
                min-width: 0;
                color: rgba(16, 34, 63, .55);
                font-size: 12px;
                font-weight: 800;
            }

            .nik-cart-mobile-total strong {
                overflow: hidden;
                color: #137a53;
                font-size: 18px;
                font-weight: 900;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .nik-cart-mobile-checkout .nik-cart-checkout {
                width: auto;
                min-height: 46px;
                padding-inline: 16px;
                white-space: nowrap;
            }
        }

        @media (max-width: 430px) {
            .nik-cart-row {
                grid-template-columns: 84px minmax(0, 1fr);
            }

            .nik-cart-image {
                width: 84px;
                height: 84px;
            }

            .nik-cart-quantity {
                grid-template-columns: 38px minmax(40px, auto) 38px;
            }

            .nik-cart-quantity-button,
            .nik-cart-quantity-value {
                min-height: 38px;
            }

            .nik-cart-mobile-checkout {
                grid-template-columns: 1fr;
            }

            .nik-cart-mobile-checkout .nik-cart-checkout {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <main class="nik-cart-page">
        <div class="nik-cart-shell">
            <header class="nik-cart-header">
                <div>
                    <h1 class="nik-cart-title">Корзина</h1>
                    @if ($items->isNotEmpty())
                        @php
                            $absoluteItemsQuantity = abs((int) $itemsQuantity);
                            $lastTwoItemsQuantity = $absoluteItemsQuantity % 100;
                            $lastItemsQuantity = $absoluteItemsQuantity % 10;
                            $itemsQuantityLabel = $lastTwoItemsQuantity >= 11 && $lastTwoItemsQuantity <= 14
                                ? 'товаров'
                                : match ($lastItemsQuantity) {
                                    1 => 'товар',
                                    2, 3, 4 => 'товара',
                                    default => 'товаров',
                                };
                        @endphp
                        <p class="nik-cart-subtitle">
                            {{ $itemsQuantity }} {{ $itemsQuantityLabel }} в заказе
                        </p>
                    @endif
                </div>

                <a class="nik-cart-link" href="{{ route('catalog.index') }}">Продолжить покупки</a>
            </header>

            @if (session('success'))
                <div class="nik-cart-alert">{{ session('success') }}</div>
            @endif

            @if ($errors->has('cart'))
                <div class="nik-cart-alert is-error">{{ $errors->first('cart') }}</div>
            @endif

            @if ($items->isEmpty())
                <div class="nik-cart-empty nik-cart-glass">
                    <div class="nik-cart-empty-mark" aria-hidden="true">0</div>
                    <h2 class="nik-cart-empty-title">Корзина пока пуста</h2>
                    <p class="nik-cart-empty-text">Добавьте товары из каталога, чтобы перейти к оформлению заказа.</p>
                    <a class="nik-cart-button" href="{{ route('catalog.index') }}">Перейти в каталог</a>
                </div>
            @else
                <div class="nik-cart-layout">
                    <section class="nik-cart-list nik-cart-glass" aria-label="Товары в корзине">
                        @foreach ($items as $item)
                            @php
                                $product = $item->product;
                                $image = $product?->images->first();
                                $quantity = max(1, (int) $item->quantity);
                                $unitPrice = (float) $item->line_total / $quantity;
                                $oldUnitPrice = (float) $item->price_snapshot;
                                $hasItemDiscount = $oldUnitPrice > $unitPrice;
                                $productUrl = $product ? route('catalog.show', $product->slug ?: $product->id) : null;
                                $volume = $product
                                    ? \App\Support\ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit)
                                    : null;
                            @endphp

                            <article class="nik-cart-row" id="cart-item-{{ $item->id }}">
                                @if ($productUrl)
                                    <a class="nik-cart-image" href="{{ $productUrl }}" aria-label="{{ $product->name }}">
                                        @if ($image)
                                            <img
                                                src="{{ Storage::disk('public')->url($image->file_path) }}"
                                                alt="{{ $image->alt ?: $product->name }}"
                                                loading="lazy"
                                            >
                                        @else
                                            Нет фото
                                        @endif
                                    </a>
                                @else
                                    <div class="nik-cart-image">
                                        @if ($image)
                                            <img
                                                src="{{ Storage::disk('public')->url($image->file_path) }}"
                                                alt="{{ $image->alt ?: 'Товар' }}"
                                                loading="lazy"
                                            >
                                        @else
                                            Нет фото
                                        @endif
                                    </div>
                                @endif

                                <div class="nik-cart-info">
                                    <h2 class="nik-cart-name">
                                        @if ($productUrl)
                                            <a href="{{ $productUrl }}">{{ $product->name }}</a>
                                        @else
                                            Товар удален
                                        @endif
                                    </h2>

                                    @if ($volume || $product?->article)
                                        <div class="nik-cart-meta">
                                            @if ($volume)
                                                <span class="nik-cart-chip">{{ $volume }}</span>
                                            @endif

                                            @if ($product?->article)
                                                <span class="nik-cart-chip">Арт. {{ $product->article }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="nik-cart-cell">
                                    <span class="nik-cart-label">Цена</span>
                                    <span class="nik-cart-price">
                                        <span>{{ number_format($unitPrice, 2, ',', ' ') }} ₽</span>
                                        @if ($hasItemDiscount)
                                            <span class="nik-cart-old-price">{{ number_format($oldUnitPrice, 2, ',', ' ') }} ₽</span>
                                        @endif
                                    </span>
                                </div>

                                <div class="nik-cart-cell is-quantity">
                                    <span class="nik-cart-label">Количество</span>
                                    <div class="nik-cart-quantity" aria-label="Количество товара">
                                        @if ($item->quantity > 1)
                                            <form class="nik-cart-quantity-form" method="POST" action="{{ route('cart.items.update', $item) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $item->quantity - 1 }}">
                                                <input type="hidden" name="redirect_anchor" value="#cart-item-{{ $item->id }}">
                                                <button class="nik-cart-quantity-button" type="submit" aria-label="Уменьшить количество">-</button>
                                            </form>
                                        @else
                                            <form class="nik-cart-quantity-form" method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="nik-cart-quantity-button" type="submit" aria-label="Убрать товар из корзины">-</button>
                                            </form>
                                        @endif

                                        <span class="nik-cart-quantity-value">{{ $item->quantity }}</span>

                                        <form class="nik-cart-quantity-form" method="POST" action="{{ route('cart.items.update', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                            <input type="hidden" name="redirect_anchor" value="#cart-item-{{ $item->id }}">
                                            <button class="nik-cart-quantity-button" type="submit" aria-label="Увеличить количество">+</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="nik-cart-cell">
                                    <span class="nik-cart-label">Сумма</span>
                                    <strong class="nik-cart-line-total">{{ number_format((float) $item->line_total, 2, ',', ' ') }} ₽</strong>
                                </div>

                                <div class="nik-cart-actions">
                                    <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                        @csrf
                                        @method('DELETE')

                                        <button class="nik-cart-remove" type="submit">Удалить</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </section>

                    <aside class="nik-cart-summary nik-cart-glass" aria-label="Итоги корзины">
                        <h2 class="nik-cart-summary-title">Ваш заказ</h2>

                        <div class="nik-cart-summary-lines">
                            <div class="nik-cart-total-row">
                                <span>Товары ({{ $itemsQuantity }})</span>
                                <strong>{{ number_format((float) $cart->subtotal, 2, ',', ' ') }} ₽</strong>
                            </div>

                            @if ((float) $cart->discount_total > 0)
                                <div class="nik-cart-total-row is-discount">
                                    <span>Скидка</span>
                                    <strong>-{{ number_format((float) $cart->discount_total, 2, ',', ' ') }} ₽</strong>
                                </div>
                            @endif

                            <div class="nik-cart-total-row">
                                <span>Общий вес</span>
                                <strong>{{ $totalWeight }}</strong>
                            </div>

                            <div class="nik-cart-total-row">
                                <span>Доставка</span>
                                <strong>При оформлении</strong>
                            </div>
                            <p class="nik-cart-delivery-note">Стоимость доставки рассчитывается на следующем шаге.</p>

                            <div class="nik-cart-total-row is-final">
                                <span>Итого</span>
                                <strong>{{ number_format((float) $cart->total, 2, ',', ' ') }} ₽</strong>
                            </div>
                        </div>

                        <a class="nik-cart-checkout" href="{{ route('checkout.index') }}">Оформить заказ</a>
                    </aside>
                </div>

                <div class="nik-cart-mobile-checkout" aria-label="Оформление заказа">
                    <div class="nik-cart-mobile-total">
                        <span>Итого</span>
                        <strong>{{ number_format((float) $cart->total, 2, ',', ' ') }} ₽</strong>
                    </div>
                    <a class="nik-cart-checkout" href="{{ route('checkout.index') }}">Оформить заказ</a>
                </div>
            @endif
        </div>
    </main>
@endsection
