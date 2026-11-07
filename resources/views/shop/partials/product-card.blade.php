@php
    use App\Models\Product;
    use App\Support\ProductDisplayFormatter;
    use Illuminate\Support\Facades\Storage;

    $cartProductItems ??= collect();
    $icon ??= fn (string $name, string $class = ''): string => \App\Support\ShopIcons::svg($name, $class);
    $imageUrl = fn (?string $path): string => $path ? Storage::disk('public')->url($path) : asset('images/logo-icon.png');
    $productUrl = fn (Product $product): string => route('catalog.show', $product->slug ?: $product->id);
    $formatPrice = fn ($price): string => number_format((float) $price, 0, ',', ' ') . ' ₽';
    $productVolume = fn (Product $product): ?string => ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit)
        ?: ProductDisplayFormatter::formatWeight($product->weight_value, $product->weight_unit);
@endphp
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
    @if ($directionLabel = \App\Support\ProductName::directionLabel($product->direction))
        <span class="v3-dir is-{{ $product->direction }}">{{ $directionLabel }}</span>
    @endif
    <a class="v3-card-img" href="{{ $productUrl($product) }}" tabindex="-1" aria-hidden="true">
        <img src="{{ $imageUrl($photo) }}" alt="" loading="lazy">
    </a>
    <div class="v3-card-brand">{{ $product->brand?->name ?: 'НИКТРЕЙД' }}</div>
    <a class="v3-card-name" href="{{ $productUrl($product) }}" title="{{ $product->name }}">{{ \App\Support\ProductName::withoutBrand($product->name, $product->brand?->name, $product->direction) }}</a>
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
