@php
    $mainImage = $product->images->first();
    $hasDiscount = $product->discounted_price !== null
        && $product->price !== null
        && $product->discounted_price < $product->price;

    $availabilityLabels = [
        'in_stock' => 'В наличии',
        'out_of_stock' => 'Временно отсутствует',
        'discontinued' => 'Снят с производства',
    ];
    $availabilityStatus = $product->availability_status ?? 'in_stock';
    $availabilityLabel = $availabilityLabels[$availabilityStatus] ?? $availabilityLabels['in_stock'];
    $productVolume = \App\Support\ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit);
    $productWeight = \App\Support\ProductDisplayFormatter::formatWeight($product->weight_value, $product->weight_unit);
    $cartProductItem = $cartProductItems->get($product->id);
    $cartProductQuantity = (int) ($cartProductItem?->quantity ?? 0);
    $catalogAnchor = 'product-' . $product->id;
@endphp

<article class="card nik-catalog2-glass" id="{{ $catalogAnchor }}">
    <a class="image" href="{{ route('catalog.show', $product->slug ?: $product->id) }}">
        @if ($mainImage)
            <img
                src="{{ Storage::disk('public')->url($mainImage->file_path) }}"
                alt="{{ $mainImage->alt ?: $product->name }}"
                loading="lazy"
            >
        @else
            <span class="placeholder">Нет изображения</span>
        @endif
    </a>

    <div class="card-body">
        <span class="brand">{{ $product->brand?->name }}</span>
        <h2 class="name">
            <a href="{{ route('catalog.show', $product->slug ?: $product->id) }}">{{ $product->name }}</a>
        </h2>

        @if ($product->article)
            <div class="meta">Артикул: {{ $product->article }}</div>
        @endif

        @if ($productVolume)
            <div class="meta">
                Объем: {{ $productVolume }}
            </div>
        @endif

        @if ($productWeight)
            <div class="meta">Вес: {{ $productWeight }}</div>
        @endif

        <div class="availability-badge is-{{ $availabilityStatus }}">{{ $availabilityLabel }}</div>

        <div class="price">
            @if ($hasDiscount)
                <span class="current-price">{{ number_format((float) $product->discounted_price, 2, ',', ' ') }} ₽</span>
                <span class="old-price">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</span>
            @elseif ($product->price !== null)
                <span class="current-price">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</span>
            @endif
        </div>

        <div class="card-actions">
            @if ($availabilityStatus === 'in_stock')
                @if ($cartProductItem)
                    <div class="action-row">
                        <div class="quantity-control" aria-label="Количество товара в корзине">
                            @if ($cartProductQuantity > 1)
                                <form class="quantity-form" method="POST" action="{{ route('cart.items.update', $cartProductItem) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ $cartProductQuantity - 1 }}">
                                    <input type="hidden" name="redirect_anchor" value="{{ $catalogAnchor }}">
                                    <button class="quantity-button" type="submit" aria-label="Уменьшить количество">-</button>
                                </form>
                            @else
                                <form class="quantity-form" method="POST" action="{{ route('cart.items.destroy', $cartProductItem) }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="redirect_anchor" value="{{ $catalogAnchor }}">
                                    <button class="quantity-button" type="submit" aria-label="Убрать товар из корзины">-</button>
                                </form>
                            @endif

                            <span class="quantity-value">{{ $cartProductQuantity }}</span>

                            <form class="quantity-form" method="POST" action="{{ route('cart.items.update', $cartProductItem) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="quantity" value="{{ $cartProductQuantity + 1 }}">
                                <input type="hidden" name="redirect_anchor" value="{{ $catalogAnchor }}">
                                <button class="quantity-button" type="submit" aria-label="Увеличить количество">+</button>
                            </form>
                        </div>

                        <a class="cart-link" href="{{ route('cart.index') }}">В корзину</a>
                    </div>
                @else
                    <form method="POST" action="{{ route('cart.add', $product->slug ?: $product->id) }}">
                        @csrf
                        <input type="hidden" name="redirect_anchor" value="{{ $catalogAnchor }}">

                        <button class="cart-button" type="submit">Добавить в корзину</button>
                    </form>
                @endif
            @else
                <button class="cart-button" type="button" disabled>
                    {{ $availabilityStatus === 'out_of_stock' ? 'Временно отсутствует' : 'Снят с производства' }}
                </button>
            @endif
        </div>
    </div>
</article>
