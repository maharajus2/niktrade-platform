<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f8fafc;
            color: #111827;
            font-family: Inter, Arial, sans-serif;
        }

        a {
            color: inherit;
        }

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
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 24px;
            align-items: start;
        }

        .items,
        .totals,
        .empty {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
        }

        .item {
            display: grid;
            grid-template-columns: 96px minmax(0, 1fr) 160px 130px;
            gap: 16px;
            align-items: center;
            padding: 16px;
            border-top: 1px solid #e5e7eb;
        }

        .item:first-child {
            border-top: 0;
        }

        .image {
            display: grid;
            place-items: center;
            overflow: hidden;
            width: 96px;
            aspect-ratio: 1;
            border-radius: 8px;
            background: #f3f4f6;
            color: #9ca3af;
            font-size: 0.8rem;
        }

        .image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .name {
            margin: 0;
            font-size: 1rem;
            line-height: 1.35;
        }

        .meta {
            margin-top: 6px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .prices {
            display: grid;
            gap: 4px;
            color: #374151;
            font-size: 0.92rem;
        }

        .line-total {
            color: #166534;
            font-weight: 900;
        }

        .quantity-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .quantity {
            width: 74px;
            min-height: 40px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font: inherit;
            padding: 8px;
        }

        .button,
        .link-button,
        .danger-button,
        .checkout-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 40px;
            border: 0;
            border-radius: 8px;
            font: inherit;
            font-weight: 800;
            padding: 8px 14px;
            text-decoration: none;
        }

        .button {
            background: #166534;
            color: #ffffff;
            cursor: pointer;
        }

        .link-button {
            background: #ecfdf5;
            color: #166534;
        }

        .danger-button {
            margin-top: 8px;
            background: #fee2e2;
            color: #b91c1c;
            cursor: pointer;
        }

        .checkout-button {
            width: 100%;
            background: #166534;
            color: #ffffff;
        }

        .totals {
            display: grid;
            gap: 14px;
            padding: 18px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            color: #374151;
        }

        .total-row.final {
            border-top: 1px solid #e5e7eb;
            color: #111827;
            font-size: 1.25rem;
            font-weight: 900;
            padding-top: 14px;
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

            .item {
                grid-template-columns: 96px minmax(0, 1fr);
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
                grid-template-columns: 1fr;
            }

            .image {
                width: 100%;
            }
        }
    </style>
</head>
<body>
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

                            <div class="prices">
                                <div>Цена: {{ number_format((float) $item->price_snapshot, 2, ',', ' ') }} ₽</div>
                                <div>Скидка: {{ number_format((float) $item->discount_snapshot, 2, ',', ' ') }}%</div>
                                <div class="line-total">Итого: {{ number_format((float) $item->line_total, 2, ',', ' ') }} ₽</div>
                            </div>

                            <div>
                                <form class="quantity-form" method="POST" action="{{ route('cart.items.update', $item) }}">
                                    @csrf
                                    @method('PATCH')

                                    <input
                                        class="quantity"
                                        type="number"
                                        name="quantity"
                                        value="{{ $item->quantity }}"
                                        min="1"
                                    >

                                    <button class="button" type="submit">Обновить</button>
                                </form>

                                <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button class="danger-button" type="submit">Удалить</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </section>

                <aside class="totals" aria-label="Итоги корзины">
                    <div class="total-row">
                        <span>Сумма товаров</span>
                        <strong>{{ number_format((float) $cart->subtotal, 2, ',', ' ') }} ₽</strong>
                    </div>

                    <div class="total-row">
                        <span>Скидка</span>
                        <strong>{{ number_format((float) $cart->discount_total, 2, ',', ' ') }} ₽</strong>
                    </div>

                    <div class="total-row final">
                        <span>Итого</span>
                        <strong>{{ number_format((float) $cart->total, 2, ',', ' ') }} ₽</strong>
                    </div>

                    <a class="checkout-button" href="{{ route('checkout.index') }}">Оформить заказ</a>
                </aside>
            </div>
        @endif
    </main>
</body>
</html>
