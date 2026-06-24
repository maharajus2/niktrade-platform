@extends('layouts.public')

@section('title', 'Корзина')

@push('styles')
    <style>
        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 48px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: center;
            margin-bottom: 24px;
        }

        .title {
            margin: 0;
            font-size: 2rem;
            line-height: 1.1;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 24px;
            align-items: start;
        }

        .items {
            display: grid;
            gap: 14px;
        }

        .item,
        .totals,
        .empty {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
        }

        .item {
            display: grid;
            grid-template-columns: 96px minmax(0, 1fr);
            gap: 16px;
            padding: 16px;
        }

        .image {
            display: grid;
            place-items: center;
            overflow: hidden;
            width: 96px;
            height: 96px;
            border-radius: 10px;
            background: #f3f4f6;
            color: #9ca3af;
            font-size: 0.8rem;
            text-align: center;
        }

        .image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-main {
            display: grid;
            gap: 10px;
            min-width: 0;
        }

        .name {
            margin: 0;
            font-size: 1.05rem;
            line-height: 1.35;
        }

        .name a {
            color: #111827;
            text-decoration: none;
        }

        .meta {
            margin-top: 5px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .item-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 12px;
            align-items: end;
        }

        .item-prices {
            display: grid;
            gap: 4px;
            color: #374151;
            text-align: right;
        }

        .line-total {
            color: #166534;
            font-size: 1.1rem;
            font-weight: 900;
        }

        .quantity-control {
            display: inline-grid;
            grid-template-columns: 38px minmax(42px, auto) 38px;
            overflow: hidden;
            width: fit-content;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
        }

        .quantity-form {
            display: contents;
        }

        .quantity-button,
        .quantity-value {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 38px;
        }

        .quantity-button {
            border: 0;
            background: #f9fafb;
            color: #166534;
            font: inherit;
            font-weight: 900;
            cursor: pointer;
        }

        .quantity-value {
            border-inline: 1px solid #d1d5db;
            color: #111827;
            font-weight: 800;
            padding: 0 10px;
        }

        .button,
        .link-button,
        .checkout-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 42px;
            border: 0;
            border-radius: 8px;
            font: inherit;
            font-weight: 800;
            padding: 9px 14px;
            text-decoration: none;
        }

        .button,
        .checkout-button {
            background: #166534;
            color: #ffffff;
        }

        .link-button {
            background: #ecfdf5;
            color: #166534;
        }

        .remove-button {
            border: 0;
            background: transparent;
            color: #b91c1c;
            font: inherit;
            font-size: 0.9rem;
            font-weight: 800;
            padding: 0;
            cursor: pointer;
        }

        .totals {
            position: sticky;
            top: 18px;
            display: grid;
            gap: 14px;
            padding: 18px;
        }

        .totals-title {
            margin: 0;
            font-size: 1.15rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            color: #374151;
        }

        .total-row strong {
            text-align: right;
        }

        .total-row.final {
            border-top: 1px solid #e5e7eb;
            color: #111827;
            font-size: 1.25rem;
            font-weight: 900;
            padding-top: 14px;
        }

        .checkout-button {
            width: 100%;
            font-size: 1rem;
        }

        .alert {
            margin-bottom: 16px;
            width: fit-content;
            border-radius: 8px;
            background: #ecfdf5;
            color: #166534;
            font-weight: 700;
            padding: 10px 14px;
        }

        .empty {
            display: grid;
            gap: 16px;
            justify-items: center;
            padding: 40px 20px;
            color: #6b7280;
            text-align: center;
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .totals {
                position: static;
            }
        }

        @media (max-width: 640px) {
            .page {
                width: min(100% - 24px, 1180px);
                padding-top: 24px;
            }

            .header {
                display: grid;
            }

            .item {
                grid-template-columns: 80px minmax(0, 1fr);
                gap: 12px;
                padding: 12px;
            }

            .image {
                width: 80px;
                height: 80px;
            }

            .item-footer {
                display: grid;
            }

            .item-prices {
                text-align: left;
            }
        }

        @media (max-width: 420px) {
            .item {
                grid-template-columns: 1fr;
            }

            .image {
                width: 96px;
                height: 96px;
            }
        }
    </style>
@endpush

@section('content')
    <main class="page">
        <header class="header">
            <h1 class="title">Корзина</h1>
            <a class="link-button" href="{{ route('catalog.index') }}">Продолжить покупки</a>
        </header>

        @if (session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif

        @if ($items->isEmpty())
            <div class="empty">
                <div>Корзина пока пуста.</div>
                <a class="button" href="{{ route('catalog.index') }}">Перейти в каталог</a>
            </div>
        @else
            <div class="layout">
                <section class="items" aria-label="Товары в корзине">
                    @foreach ($items as $item)
                        @php
                            $product = $item->product;
                            $image = $product?->images->first();
                            $unitPrice = (float) $item->line_total / max(1, (int) $item->quantity);
                        @endphp

                        <article class="item">
                            <div class="image">
                                @if ($image)
                                    <img
                                        src="{{ Storage::disk('public')->url($image->file_path) }}"
                                        alt="{{ $image->alt ?: $product->name }}"
                                        loading="lazy"
                                    >
                                @else
                                    Нет фото
                                @endif
                            </div>

                            <div class="item-main">
                                <div>
                                    <h2 class="name">
                                        @if ($product)
                                            <a href="{{ route('catalog.show', $product->slug ?: $product->id) }}">{{ $product->name }}</a>
                                        @else
                                            Товар удален
                                        @endif
                                    </h2>

                                    @if ($product?->article)
                                        <div class="meta">Артикул: {{ $product->article }}</div>
                                    @endif
                                </div>

                                <div class="item-footer">
                                    <div>
                                        <div class="quantity-control" aria-label="Количество товара">
                                            @if ($item->quantity > 1)
                                                <form class="quantity-form" method="POST" action="{{ route('cart.items.update', $item) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="quantity" value="{{ $item->quantity - 1 }}">
                                                    <button class="quantity-button" type="submit" aria-label="Уменьшить количество">-</button>
                                                </form>
                                            @else
                                                <form class="quantity-form" method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="quantity-button" type="submit" aria-label="Убрать товар из корзины">-</button>
                                                </form>
                                            @endif

                                            <span class="quantity-value">{{ $item->quantity }}</span>

                                            <form class="quantity-form" method="POST" action="{{ route('cart.items.update', $item) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                                <button class="quantity-button" type="submit" aria-label="Увеличить количество">+</button>
                                            </form>
                                        </div>

                                        <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button class="remove-button" type="submit">Удалить</button>
                                        </form>
                                    </div>

                                    <div class="item-prices">
                                        <div>{{ number_format($unitPrice, 2, ',', ' ') }} ₽ × {{ $item->quantity }}</div>
                                        <div class="line-total">{{ number_format((float) $item->line_total, 2, ',', ' ') }} ₽</div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>

                <aside class="totals" aria-label="Итоги корзины">
                    <h2 class="totals-title">Итого</h2>

                    <div class="total-row">
                        <span>Количество товаров</span>
                        <strong>{{ $itemsQuantity }}</strong>
                    </div>

                    <div class="total-row">
                        <span>Общий вес</span>
                        <strong>{{ $totalWeight }}</strong>
                    </div>

                    <div class="total-row">
                        <span>Сумма товаров</span>
                        <strong>{{ number_format((float) $cart->subtotal, 2, ',', ' ') }} ₽</strong>
                    </div>

                    @if ((float) $cart->discount_total > 0)
                        <div class="total-row">
                            <span>Скидка</span>
                            <strong>{{ number_format((float) $cart->discount_total, 2, ',', ' ') }} ₽</strong>
                        </div>
                    @endif

                    <div class="total-row final">
                        <span>Итого</span>
                        <strong>{{ number_format((float) $cart->total, 2, ',', ' ') }} ₽</strong>
                    </div>

                    <a class="checkout-button" href="{{ route('checkout.index') }}">Оформить заказ</a>
                </aside>
            </div>
        @endif
    </main>
@endsection
