@php
    use App\Models\Product;
    use App\Support\ProductDisplayFormatter;
    use Illuminate\Support\Facades\Storage;

    $icon ??= fn (string $name, string $class = ''): string => \App\Support\ShopIcons::svg($name, $class);
    $imageUrl = fn (?string $path): string => $path ? Storage::disk('public')->url($path) : asset('images/logo-icon.png');
    $productUrl = route('business.product', $product->slug ?: $product->id);
    $formatPrice = fn ($price): string => number_format((float) $price, fmod((float) $price, 1) == 0.0 ? 0 : 2, ',', ' ') . ' ₽';
    $volume = ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit)
        ?: ProductDisplayFormatter::formatWeight($product->weight_value, $product->weight_unit);
    $pricing = $product->business_pricing;
    $photo = $product->images->first()?->file_path;
    $outOfStock = $product->availability_status === 'out_of_stock';
    $canOrder = $product->availability_status === 'in_stock' && $pricing['price'] !== null;
    $inRequest = (int) (($bizQty ?? collect())->get($product->id) ?? 0);
@endphp
<article class="v3-card biz-pcard" id="biz-product-{{ $product->id }}" data-biz-item data-product-id="{{ $product->id }}" data-qty="{{ $inRequest }}" data-add-url="{{ route('business.cart.add') }}" data-update-url="{{ route('business.cart.update', $product->id) }}">
    @if ($directionLabel = \App\Support\ProductName::directionLabel($product->direction))
        <span class="v3-dir is-{{ $product->direction }}">{{ $directionLabel }}</span>
    @endif
    <a class="v3-card-img" href="{{ $productUrl }}" tabindex="-1" aria-hidden="true">
        <img src="{{ $imageUrl($photo) }}" alt="" loading="lazy">
    </a>
    <div class="v3-card-brand">{{ $product->brand?->name ?: 'НИКТРЕЙД' }}</div>
    <a class="v3-card-name" href="{{ $productUrl }}" title="{{ $product->name }}">{{ \App\Support\ProductName::withoutBrand($product->name, $product->brand?->name, $product->direction) }}</a>
    <div class="v3-card-meta">
        @if ($volume){{ $volume }}@endif
        @if ($product->article) · арт. {{ $product->article }}@endif
    </div>
    <div class="v3-stock {{ $outOfStock ? 'is-wait' : 'is-in' }}">{{ $outOfStock ? 'Скоро появится' : 'В наличии' }}</div>
    <div class="v3-card-buy biz-card-buy">
        <div class="biz-card-pricebox">
            @if ($pricing['price'] !== null)
                <div class="v3-price biz-price">
                    {{ $formatPrice($pricing['price']) }}
                    @if ($pricing['has_discount'])<del>{{ $formatPrice($pricing['list']) }}</del>@endif
                </div>
                <div class="biz-price-label">{{ $pricing['has_discount'] ? 'Ваша цена, скидка '.rtrim(rtrim(number_format($pricing['discount_percent'], 2, ',', ''), '0'), ',').'%' : 'Цена для бизнеса' }}</div>
            @else
                <div class="biz-price-label">Цена по запросу</div>
            @endif
        </div>
        <div class="v3-buy-slot biz-buy-slot">
            @if ($canOrder)
                <div class="v3-qty" role="group" aria-label="Количество в заявке" data-biz-qty @if ($inRequest < 1) hidden @endif>
                    <button class="v3-qty-btn" type="button" data-biz-dec aria-label="Уменьшить количество">−</button>
                    <input class="biz-qty-input" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="off" aria-label="Количество" data-biz-num value="{{ $inRequest }}">
                    <button class="v3-qty-btn" type="button" data-biz-inc aria-label="Увеличить количество">+</button>
                </div>
                <form method="POST" action="{{ route('business.cart.add') }}" data-biz-add-form data-biz-add-wrap @if ($inRequest > 0) hidden @endif>
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button class="v3-add" type="submit" data-biz-add aria-label="Добавить в заявку">{!! $icon('cart') !!}<span>В заявку</span></button>
                </form>
            @endif
        </div>
    </div>
</article>
