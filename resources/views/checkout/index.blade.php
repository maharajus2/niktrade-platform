<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа</title>

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
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 24px;
            align-items: start;
        }

        .form,
        .summary {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 18px;
        }

        .form {
            display: grid;
            gap: 22px;
        }

        .section {
            display: grid;
            gap: 14px;
        }

        .section-title {
            margin: 0;
            font-size: 1.2rem;
        }

        .fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .label {
            color: #4b5563;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .input,
        .textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font: inherit;
            padding: 10px 12px;
        }

        .textarea {
            min-height: 110px;
            resize: vertical;
        }

        .error {
            color: #b91c1c;
            font-size: 0.85rem;
        }

        .button,
        .link-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 42px;
            border: 0;
            border-radius: 8px;
            font: inherit;
            font-weight: 800;
            padding: 10px 16px;
            text-decoration: none;
        }

        .button {
            width: fit-content;
            background: #166534;
            color: #ffffff;
            cursor: pointer;
        }

        .link-button {
            background: #ecfdf5;
            color: #166534;
        }

        .summary {
            display: grid;
            gap: 14px;
        }

        .summary-title {
            margin: 0;
            font-size: 1.2rem;
        }

        .item {
            display: grid;
            gap: 4px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
        }

        .item:first-of-type {
            border-top: 0;
            padding-top: 0;
        }

        .item-name {
            font-weight: 800;
        }

        .item-meta {
            color: #6b7280;
            font-size: 0.9rem;
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

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
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

            .fields {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="header">
            <h1 class="title">Оформление заказа</h1>
            <a class="link-button" href="{{ route('cart.index') }}">Вернуться в корзину</a>
        </header>

        <div class="layout">
            <form class="form" method="POST" action="{{ route('checkout.store') }}">
                @csrf

                <section class="section">
                    <h2 class="section-title">Покупатель</h2>

                    <div class="fields">
                        <label class="field">
                            <span class="label">Имя</span>
                            <input class="input" type="text" name="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Фамилия</span>
                            <input class="input" type="text" name="last_name" value="{{ old('last_name') }}">
                            @error('last_name')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Email</span>
                            <input class="input" type="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Телефон</span>
                            <input class="input" type="tel" name="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </section>

                <section class="section">
                    <h2 class="section-title">Доставка</h2>

                    <div class="fields">
                        <label class="field">
                            <span class="label">Город</span>
                            <input class="input" type="text" name="city" value="{{ old('city') }}" required>
                            @error('city')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Улица</span>
                            <input class="input" type="text" name="street" value="{{ old('street') }}" required>
                            @error('street')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Дом</span>
                            <input class="input" type="text" name="house" value="{{ old('house') }}" required>
                            @error('house')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Квартира</span>
                            <input class="input" type="text" name="apartment" value="{{ old('apartment') }}">
                            @error('apartment')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Подъезд</span>
                            <input class="input" type="text" name="entrance" value="{{ old('entrance') }}">
                            @error('entrance')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="field">
                            <span class="label">Этаж</span>
                            <input class="input" type="text" name="floor" value="{{ old('floor') }}">
                            @error('floor')
                                <span class="error">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </section>

                <section class="section">
                    <h2 class="section-title">Комментарий</h2>

                    <label class="field full">
                        <span class="label">Комментарий к заказу</span>
                        <textarea class="textarea" name="comment">{{ old('comment') }}</textarea>
                        @error('comment')
                            <span class="error">{{ $message }}</span>
                        @enderror
                    </label>
                </section>

                <button class="button" type="submit">Оформить заказ</button>
            </form>

            <aside class="summary" aria-label="Состав заказа">
                <h2 class="summary-title">Ваш заказ</h2>

                @foreach ($items as $item)
                    <div class="item">
                        <div class="item-name">{{ $item->product?->name ?? 'Товар удален' }}</div>
                        <div class="item-meta">
                            {{ $item->quantity }} × {{ number_format((float) $item->price_snapshot, 2, ',', ' ') }} ₽
                        </div>
                        <div class="item-meta">
                            Итого: {{ number_format((float) $item->line_total, 2, ',', ' ') }} ₽
                        </div>
                    </div>
                @endforeach

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
            </aside>
        </div>
    </main>
</body>
</html>
