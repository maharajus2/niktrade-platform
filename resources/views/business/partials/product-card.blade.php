@php
    use App\Models\Product;
    use App\Support\ProductDisplayFormatter;
    use Illuminate\Support\Facades\Storage;

    $imageUrl = fn (?string $path): string => $path ? Storage::disk('public')->url($path) : asset('images/logo-icon.png');
    $productUrl = route('business.product', $product->slug ?: $product->id);
    $formatPrice = fn ($price): string => number_format((float) $price, fmod((float) $price, 1) == 0.0 ? 0 : 2, ',', ' ') . ' ₽';
    $volume = ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit)
        ?: ProductDisplayFormatter::formatWeight($product->weight_value, $product->weight_unit);
    $pricing = $product->business_pricing;
    $photo = $product->images->first()?->file_path;
    $outOfStock = $product->availability_status === 'out_of_stock';
@endphp
<article class="v3-card biz-pcard" id="biz-product-{{ $product->id }}">
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
</article>
