@extends('layouts.shop')

@section('title', 'Корзина — НИКТРЕЙД')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-catalog.css') }}?v={{ filemtime(public_path('css/shop-catalog.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/shop-cart.css') }}?v={{ filemtime(public_path('css/shop-cart.css')) }}">
@endpush

@section('content')
    @php
        use App\Support\ProductDisplayFormatter;
        use App\Support\ProductName;

        $absoluteItemsQuantity = abs((int) $itemsQuantity);
        $lastTwo = $absoluteItemsQuantity % 100;
        $lastOne = $absoluteItemsQuantity % 10;
        $itemsQuantityLabel = $lastTwo >= 11 && $lastTwo <= 14
            ? 'товаров'
            : match ($lastOne) {
                1 => 'товар',
                2, 3, 4 => 'товара',
                default => 'товаров',
            };
        $money = fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' ₽';
    @endphp

    <div class="shop-cart">
        <header class="shop-head">
            <div>
                <h1>Корзина</h1>
                @if ($items->isNotEmpty())
                    <p>{{ $itemsQuantity }} {{ $itemsQuantityLabel }} в заказе</p>
                @endif
            </div>
            <a class="shop-btn is-ghost" href="{{ route('catalog.index') }}">Продолжить покупки</a>
        </header>

        @if (session('success'))
            <div class="shop-alert">{{ session('success') }}</div>
        @endif

        @if ($errors->has('cart'))
            <div class="shop-alert is-error">{{ $errors->first('cart') }}</div>
        @endif

        @if ($items->isEmpty())
            <div class="shop-cart-empty">
                <div class="shop-cart-empty-mark" aria-hidden="true">0</div>
                <h2>Корзина пока пуста</h2>
                <p>Добавьте товары из каталога, чтобы перейти к оформлению заказа.</p>
                <a class="shop-btn" href="{{ route('catalog.index') }}">Перейти в каталог</a>
            </div>
        @else
            <div class="shop-cart-layout">
                <section class="shop-cart-list" aria-label="Товары в корзине">
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
                                ? ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit)
                                : null;
                            $displayName = $product
                                ? ProductName::withoutBrand((string) $product->name, (string) ($product->brand?->name ?? ''), $product->direction)
                                : 'Товар удалён';
                        @endphp

                        <article class="shop-cart-row" id="cart-item-{{ $item->id }}">
                            <a class="shop-cart-image" @if ($productUrl) href="{{ $productUrl }}" @endif aria-label="{{ $displayName }}">
                                @if ($image)
                                    <img src="{{ Storage::disk('public')->url($image->file_path) }}" alt="{{ $image->alt ?: $displayName }}" loading="lazy">
                                @else
                                    <span>Нет фото</span>
                                @endif
                            </a>

                            <div class="shop-cart-info">
                                @if ($product?->brand?->name)
                                    <div class="shop-cart-brand">{{ $product->brand->name }}</div>
                                @endif
                                <h2 class="shop-cart-name" title="{{ $product?->name }}">
                                    @if ($productUrl)
                                        <a href="{{ $productUrl }}">{{ $displayName }}</a>
                                    @else
                                        {{ $displayName }}
                                    @endif
                                </h2>

                                @if ($volume || $product?->article)
                                    <div class="shop-cart-meta">
                                        @if ($volume)<span>{{ $volume }}</span>@endif
                                        @if ($product?->article)<span>Арт. {{ $product->article }}</span>@endif
                                    </div>
                                @endif
                            </div>

                            <div class="shop-cart-cell">
                                <span class="shop-cart-label">Цена</span>
                                <span class="shop-cart-price">
                                    <span>{{ $money($unitPrice) }}</span>
                                    @if ($hasItemDiscount)
                                        <del>{{ $money($oldUnitPrice) }}</del>
                                    @endif
                                </span>
                            </div>

                            <div class="shop-cart-cell is-quantity">
                                <span class="shop-cart-label">Количество</span>
                                <div class="shop-cart-qty" aria-label="Количество товара">
                                    @if ($item->quantity > 1)
                                        <form method="POST" action="{{ route('cart.items.update', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ $item->quantity - 1 }}">
                                            <input type="hidden" name="redirect_anchor" value="#cart-item-{{ $item->id }}">
                                            <button type="submit" aria-label="Уменьшить количество">−</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" aria-label="Убрать товар из корзины">−</button>
                                        </form>
                                    @endif

                                    <span class="shop-cart-qty-value">{{ $item->quantity }}</span>

                                    <form method="POST" action="{{ route('cart.items.update', $item) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                                        <input type="hidden" name="redirect_anchor" value="#cart-item-{{ $item->id }}">
                                        <button type="submit" aria-label="Увеличить количество">+</button>
                                    </form>
                                </div>
                            </div>

                            <div class="shop-cart-cell">
                                <span class="shop-cart-label">Сумма</span>
                                <strong class="shop-cart-sum">{{ $money($item->line_total) }}</strong>
                            </div>

                            <div class="shop-cart-actions">
                                <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="shop-cart-remove" type="submit">Удалить</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </section>

                <aside class="shop-cart-summary" aria-label="Итоги корзины">
                    <h2>Ваш заказ</h2>

                    <div class="shop-cart-lines">
                        <div class="shop-cart-line">
                            <span>Товары ({{ $itemsQuantity }})</span>
                            <strong>{{ $money($cart->subtotal) }}</strong>
                        </div>

                        @if ((float) $cart->discount_total > 0)
                            <div class="shop-cart-line is-discount">
                                <span>Скидка</span>
                                <strong>−{{ $money($cart->discount_total) }}</strong>
                            </div>
                        @endif

                        <div class="shop-cart-line">
                            <span>Общий вес</span>
                            <strong>{{ $totalWeight }}</strong>
                        </div>

                        <div class="shop-cart-line">
                            <span>Доставка</span>
                            <strong>При оформлении</strong>
                        </div>
                        <p class="shop-cart-note">Стоимость доставки рассчитывается на следующем шаге.</p>

                        <div class="shop-cart-line is-final">
                            <span>Итого</span>
                            <strong>{{ $money($cart->total) }}</strong>
                        </div>
                    </div>

                    <a class="shop-btn is-large shop-cart-go" href="{{ route('checkout.index') }}">Оформить заказ</a>
                </aside>
            </div>

            <div class="shop-cart-bar" aria-label="Оформление заказа">
                <div class="shop-cart-bar-total">
                    <span>Итого</span>
                    <strong>{{ $money($cart->total) }}</strong>
                </div>
                <a class="shop-btn" href="{{ route('checkout.index') }}">Оформить заказ</a>
            </div>
        @endif
    </div>
@endsection
