@extends('layouts.business')

@section('title', $product->seo_title ?: $product->name)

@section('meta_description', $product->seo_description ?: $product->short_description ?: '')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-catalog.css') }}?v={{ filemtime(public_path('css/shop-catalog.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/shop-product.css') }}?v={{ filemtime(public_path('css/shop-product.css')) }}">
@endpush

@section('content')
    <div class="nik-product-page">
        @php
            $hasDiscount = $product->discounted_price !== null
                && $product->price !== null
                && $product->discounted_price < $product->price;

            $hasDiscountPercent = (float) ($product->discount_percent ?? 0) > 0;
            $directionLabel = \App\Support\ProductName::directionLabel($product->direction) ?? ($directions[$product->direction] ?? $product->direction);
            $displayName = \App\Support\ProductName::withoutBrand($product->name, $product->brand?->name, $product->direction);
            $money = fn ($value): string => number_format((float) $value, fmod((float) $value, 1) == 0.0 ? 0 : 2, ',', ' ') . ' ₽';
            $icon = fn (string $name, string $class = ''): string => \App\Support\ShopIcons::svg($name, $class);
            $availabilityLabels = [
                'in_stock' => 'В наличии',
                'out_of_stock' => 'Временно отсутствует',
                'discontinued' => 'Снят с производства',
            ];
            $availabilityStatus = $product->availability_status ?? 'in_stock';
            $availabilityLabel = $availabilityLabels[$availabilityStatus] ?? $availabilityLabels['in_stock'];
            $availabilityClass = match ($availabilityStatus) {
                'out_of_stock' => 'is-out',
                'discontinued' => 'is-discontinued',
                default => '',
            };
            $canAddToCart = $availabilityStatus === 'in_stock';
            $productVolume = \App\Support\ProductDisplayFormatter::formatVolume($product->volume_value, $product->volume_unit);
            $productWeight = \App\Support\ProductDisplayFormatter::formatWeight($product->weight_value, $product->weight_unit);
            $productShelfLife = \App\Support\ProductDisplayFormatter::formatShelfLife($product->shelf_life_value, $product->shelf_life_unit);
            $bizQty = $contractor->cartItems()->pluck('quantity', 'product_id');
            $bizProductQty = (int) ($bizQty->get($product->id) ?? 0);
            $canOrder = $canAddToCart && $product->business_pricing['price'] !== null;
            $hasDocuments = $product->certificates->isNotEmpty() || filled($product->instruction_file_path);
        @endphp

        <div class="nik-product-shell">
            <nav class="nik-product-breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('business.account') }}">Кабинет</a>
                <span>/</span>
                <a href="{{ route('business.catalog') }}">Каталог</a>

                @if ($product->category)
                    <span>/</span>
                    <a href="{{ route('business.catalog', ['category' => $product->category->id]) }}">{{ $product->category->name }}</a>
                @endif

                @if ($product->brand)
                    <span>/</span>
                    <a href="{{ route('business.catalog', ['brand' => $product->brand->id]) }}">{{ $product->brand->name }}</a>
                @endif

                <span>/</span>
                <span aria-current="page">{{ $displayName }}</span>
            </nav>

            <section class="nik-product-layout">
                <div class="nik-product-gallery shop-panel" aria-label="Галерея товара">
                    @if ($mainImage)
                        <a
                            class="nik-product-main-image"
                            href="{{ Storage::disk('public')->url($mainImage->file_path) }}"
                            target="_blank"
                            rel="noopener"
                            aria-label="Открыть изображение товара"
                            data-product-main-link
                        >
                            <img
                                src="{{ Storage::disk('public')->url($mainImage->file_path) }}"
                                alt="{{ $mainImage->alt ?: $product->name }}"
                                data-product-main-image
                            >
                            <span class="nik-product-zoom-lens" data-product-zoom-lens aria-hidden="true"></span>
                        </a>
                    @else
                        <div class="nik-product-placeholder">Нет изображения</div>
                    @endif

                    @if ($galleryImages->isNotEmpty())
                        <div class="nik-product-mobile-gallery" aria-label="Фотографии товара">
                            @foreach ($galleryImages as $image)
                                <a
                                    class="nik-product-mobile-slide"
                                    href="{{ Storage::disk('public')->url($image->file_path) }}"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="Открыть фотографию товара"
                                >
                                    <img
                                        src="{{ Storage::disk('public')->url($image->file_path) }}"
                                        alt="{{ $image->alt ?: $product->name }}"
                                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                    >
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($galleryImages->count() > 1)
                        <div class="nik-product-thumbs" aria-label="Миниатюры товара">
                            @foreach ($galleryImages as $image)
                                <a
                                    class="nik-product-thumb @if ($loop->first) is-active @endif"
                                    href="{{ Storage::disk('public')->url($image->file_path) }}"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="Открыть фотографию товара"
                                    data-product-thumb
                                    data-image-src="{{ Storage::disk('public')->url($image->file_path) }}"
                                    data-image-alt="{{ $image->alt ?: $product->name }}"
                                    @if ($loop->first) aria-current="true" @endif
                                >
                                    <img
                                        src="{{ Storage::disk('public')->url($image->file_path) }}"
                                        alt="{{ $image->alt ?: $product->name }}"
                                        loading="lazy"
                                    >
                                </a>
                            @endforeach
                        </div>

                        <div class="nik-product-image-prompt" data-product-image-prompt hidden>
                            <span>Открыть выбранное изображение на отдельной странице?</span>
                            <a href="{{ Storage::disk('public')->url($mainImage?->file_path ?? $galleryImages->first()?->file_path) }}" target="_blank" rel="noopener" data-product-image-open>
                                Открыть
                            </a>
                            <button type="button" data-product-image-cancel>Закрыть</button>
                        </div>
                    @endif
                </div>

                <section class="nik-product-info shop-panel" aria-label="Информация о товаре">
                    @if ($product->brand)
                        <p class="nik-product-brand">{{ $product->brand->name }}</p>
                    @endif

                    <h1 class="nik-product-title" title="{{ $product->name }}">{{ $displayName }}</h1>

                    @if ($product->short_description)
                        <p class="nik-product-intro">{{ $product->short_description }}</p>
                    @endif

                    <div class="nik-product-meta">
                        @if ($product->article)
                            <span class="nik-product-chip">Артикул {{ $product->article }}</span>
                        @endif

                        @if ($product->barcode)
                            <span class="nik-product-chip">Штрихкод {{ $product->barcode }}</span>
                        @endif

                        @if ($directionLabel)
                            <span class="nik-product-chip">{{ $directionLabel }}</span>
                        @endif

                        <span class="nik-product-status {{ $availabilityClass }}">{{ $availabilityLabel }}</span>
                    </div>

                    <div class="biz-product-price">
                        @if ($product->business_pricing['price'] !== null)
                            <div class="nik-product-price">
                                <span class="nik-product-current-price">{{ $money($product->business_pricing['price']) }}</span>
                                @if ($product->business_pricing['has_discount'])
                                    <span class="nik-product-old-price">{{ $money($product->business_pricing['list']) }}</span>
                                    <span class="nik-product-discount">Ваша скидка {{ rtrim(rtrim(number_format($product->business_pricing['discount_percent'], 2, ',', ''), '0'), ',') }}%</span>
                                @endif
                            </div>
                            <div class="biz-price-rows">
                                <div><span>Цена для бизнеса</span><strong>{{ $money($product->business_pricing['list']) }}</strong></div>
                                @if ($product->business_pricing['has_discount'])
                                    <div><span>Ваша цена со скидкой</span><strong>{{ $money($product->business_pricing['price']) }}</strong></div>
                                @endif
                            </div>
                        @else
                            <div class="biz-price-label">Цена по запросу</div>
                        @endif
                    </div>

                    @if (session('success'))
                        <div class="nik-product-alert">{{ session('success') }}</div>
                    @endif

                    <div class="nik-product-actions biz-actions" data-biz-item data-product-id="{{ $product->id }}" data-qty="{{ $bizProductQty }}" data-add-url="{{ route('business.cart.add') }}" data-update-url="{{ route('business.cart.update', $product->id) }}">
                        @if ($canOrder)
                            <div class="v3-qty is-large" role="group" aria-label="Количество в заявке" data-biz-qty @if ($bizProductQty < 1) hidden @endif>
                                <button class="v3-qty-btn" type="button" data-biz-dec aria-label="Уменьшить количество">−</button>
                                <span class="v3-qty-num" aria-live="polite" data-biz-num>{{ $bizProductQty }}</span>
                                <button class="v3-qty-btn" type="button" data-biz-inc aria-label="Увеличить количество">+</button>
                            </div>

                            <form method="POST" action="{{ route('business.cart.add') }}" data-biz-add-form data-biz-add-wrap @if ($bizProductQty > 0) hidden @endif>
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <button class="shop-btn is-large" type="submit" data-biz-add>{!! $icon('cart') !!} Добавить в заявку</button>
                            </form>
                        @else
                            <button class="shop-btn is-large" type="button" disabled>
                                {{ $availabilityStatus === 'discontinued' ? 'Снят с производства' : ($availabilityStatus === 'out_of_stock' ? 'Временно отсутствует' : 'Цена по запросу') }}
                            </button>
                        @endif

                        <a class="nik-product-cart-link" href="{{ route('business.cart') }}" data-biz-cart-link @if ($bizProductQty < 1) hidden @endif>Перейти к заявке</a>
                    </div>
                    <p class="biz-min-hint">Минимальная сумма заявки — от 5&nbsp;000&nbsp;₽. Счёт выставит ваш менеджер.</p>

                    <div class="nik-product-specs" aria-label="Ключевые характеристики">
                        @if ($productVolume)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Объем</div>
                                <div class="nik-product-spec-value">{{ $productVolume }}</div>
                            </div>
                        @endif

                        @if ($product->category)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Категория</div>
                                <div class="nik-product-spec-value">{{ $product->category->name }}</div>
                            </div>
                        @endif

                        @if ($product->productLine)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Линейка</div>
                                <div class="nik-product-spec-value">{{ $product->productLine->name }}</div>
                            </div>
                        @endif

                        @if ($productShelfLife)
                            <div class="nik-product-spec">
                                <div class="nik-product-spec-label">Срок годности</div>
                                <div class="nik-product-spec-value">{{ $productShelfLife }}</div>
                            </div>
                        @endif
                    </div>
                </section>
            </section>

            <div class="nik-product-tabs" role="tablist" aria-label="Информация о товаре">
                <button
                    id="product-tab-main"
                    class="nik-product-tab is-active"
                    type="button"
                    role="tab"
                    aria-selected="true"
                    aria-controls="product-panel-main"
                    data-product-tab="main"
                >
                    Основная информация
                </button>
                <button
                    id="product-tab-details"
                    class="nik-product-tab"
                    type="button"
                    role="tab"
                    aria-selected="false"
                    aria-controls="product-panel-details"
                    data-product-tab="details"
                >
                    Подробная информация
                </button>
                @if ($hasDocuments)
                <button
                    id="product-tab-documents"
                    class="nik-product-tab"
                    type="button"
                    role="tab"
                    aria-selected="false"
                    aria-controls="product-panel-documents"
                    data-product-tab="documents"
                >
                    Документация
                </button>
                @endif
            </div>

            <div class="nik-product-tab-panels">
                <div
                    id="product-panel-main"
                    class="nik-product-tab-panel"
                    role="tabpanel"
                    aria-labelledby="product-tab-main"
                    data-product-tab-panel="main"
                >
                    @if (filled($product->description))
                    <section class="nik-product-section shop-panel">
                        <h2>Описание</h2>
                        <p>{{ $product->description }}</p>
                    </section>
                    @endif

                    @if (filled($product->composition))
                        <section class="nik-product-section shop-panel">
                            <h2>Состав</h2>
                            <p>{{ $product->composition }}</p>
                        </section>
                    @endif
                </div>

                <div
                    id="product-panel-details"
                    class="nik-product-tab-panel"
                    role="tabpanel"
                    aria-labelledby="product-tab-details"
                    data-product-tab-panel="details"
                    hidden
                >
                    <section class="nik-product-section shop-panel">
                        <h2>Характеристики</h2>

                        <div class="nik-product-details-grid">
                            @if ($product->article)
                                <div class="nik-product-detail">
                                    <div class="nik-product-detail-label">Артикул</div>
                                    <div class="nik-product-detail-value">{{ $product->article }}</div>
                                </div>
                            @endif

                            @if ($product->barcode)
                                <div class="nik-product-detail">
                                    <div class="nik-product-detail-label">Штрихкод</div>
                                    <div class="nik-product-detail-value">{{ $product->barcode }}</div>
                                </div>
                            @endif

                            @if ($product->brand)
                                <div class="nik-product-detail">
                                    <div class="nik-product-detail-label">Бренд</div>
                                    <div class="nik-product-detail-value">{{ $product->brand->name }}</div>
                                </div>
                            @endif

                            @if ($product->category)
                                <div class="nik-product-detail">
                                    <div class="nik-product-detail-label">Категория</div>
                                    <div class="nik-product-detail-value">{{ $product->category->name }}</div>
                                </div>
                            @endif

                            @if ($directionLabel)
                                <div class="nik-product-detail">
                                    <div class="nik-product-detail-label">Направление</div>
                                    <div class="nik-product-detail-value">{{ $directionLabel }}</div>
                                </div>
                            @endif

                            @if ($product->productType)
                                <div class="nik-product-detail">
                                    <div class="nik-product-detail-label">Тип товара</div>
                                    <div class="nik-product-detail-value">{{ $product->productType->name }}</div>
                                </div>
                            @endif

                            @if ($product->productLine)
                                <div class="nik-product-detail">
                                    <div class="nik-product-detail-label">Линейка</div>
                                    <div class="nik-product-detail-value">{{ $product->productLine->name }}</div>
                                </div>
                            @endif

                            @if ($productVolume)
                                <div class="nik-product-detail">
                                    <div class="nik-product-detail-label">Объем</div>
                                    <div class="nik-product-detail-value">{{ $productVolume }}</div>
                                </div>
                            @endif

                            @if ($productWeight)
                                <div class="nik-product-detail">
                                    <div class="nik-product-detail-label">Вес</div>
                                    <div class="nik-product-detail-value">{{ $productWeight }}</div>
                                </div>
                            @endif

                            @if ($productShelfLife)
                                <div class="nik-product-detail">
                                    <div class="nik-product-detail-label">Срок годности</div>
                                    <div class="nik-product-detail-value">{{ $productShelfLife }}</div>
                                </div>
                            @endif
                        </div>
                    </section>

                    @if ($product->usage_method)
                        <section class="nik-product-section shop-panel">
                            <h2>Способ применения</h2>
                            <p>{{ $product->usage_method }}</p>
                        </section>
                    @endif

                    @if ($product->precautions || $product->storage_conditions || $product->disposal_method)
                        <section class="nik-product-section shop-panel">
                            <h2>Безопасность и хранение</h2>

                            @if ($product->precautions)
                                <h3>Меры предосторожности</h3>
                                <p>{{ $product->precautions }}</p>
                            @endif

                            @if ($product->storage_conditions)
                                <h3>Условия хранения</h3>
                                <p>{{ $product->storage_conditions }}</p>
                            @endif

                            @if ($product->disposal_method)
                                <h3>Утилизация</h3>
                                <p>{{ $product->disposal_method }}</p>
                            @endif
                        </section>
                    @endif
                </div>

                @if ($hasDocuments)
                <div
                    id="product-panel-documents"
                    class="nik-product-tab-panel"
                    role="tabpanel"
                    aria-labelledby="product-tab-documents"
                    data-product-tab-panel="documents"
                    hidden
                >
                    <section class="nik-product-section shop-panel">
                        <h2>Документация</h2>

                            <div class="nik-product-docs">
                                @foreach ($product->certificates as $certificate)
                                    @php
                                        $expiresAt = $certificate->expires_at;
                                        $isExpired = $expiresAt && $expiresAt->isPast();
                                        $isSoon = $expiresAt && ! $isExpired && $expiresAt->lte(now()->addDays(30));
                                        $status = $isExpired ? 'просрочен' : ($isSoon ? 'скоро истекает' : 'действует');
                                        $statusClass = $isExpired ? 'nik-product-status-expired' : ($isSoon ? 'nik-product-status-soon' : 'nik-product-status-active');
                                    @endphp

                                    <article class="nik-product-doc">
                                        <div class="nik-product-doc-title">{{ $certificate->name }}</div>
                                        <div class="nik-product-doc-meta">
                                            @if ($certificate->certificate_type)
                                                {{ $certificate->certificate_type }}
                                            @endif

                                            @if ($certificate->number)
                                                № {{ $certificate->number }}
                                            @endif
                                        </div>
                                        <div class="nik-product-doc-meta">
                                            Статус: <span class="{{ $statusClass }}">{{ $status }}</span>

                                            @if ($certificate->is_permanent)
                                                · бессрочно
                                            @elseif ($expiresAt)
                                                · до {{ $expiresAt->format('d.m.Y') }}
                                            @endif
                                        </div>

                                        @if ($certificate->file_path)
                                            <a class="nik-product-doc-link" href="{{ Storage::disk('public')->url($certificate->file_path) }}" target="_blank" rel="noopener">
                                                Открыть PDF
                                            </a>
                                        @endif
                                    </article>
                                @endforeach

                                @if ($product->instruction_file_path)
                                    <article class="nik-product-doc">
                                        <div class="nik-product-doc-title">Инструкция по применению</div>
                                        <a class="nik-product-doc-link" href="{{ Storage::disk('public')->url($product->instruction_file_path) }}" target="_blank" rel="noopener">
                                            Открыть PDF
                                        </a>
                                    </article>
                                @endif
                            </div>
                    </section>
                </div>
                @endif
            </div>

            @if ($relatedProducts->isNotEmpty())
                <section class="nik-product-related" aria-labelledby="related-products-title">
                    <h2 id="related-products-title">Похожие товары</h2>
                    <div class="v3-products is-related">
                        @foreach ($relatedProducts as $relatedProduct)
                            @include('business.partials.product-card', ['product' => $relatedProduct, 'bizQty' => $bizQty])
                        @endforeach
                    </div>
                </section>

            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/product-gallery.js')
@endpush
