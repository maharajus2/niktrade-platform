@extends('catalog2.index')

@section('title', $product->seo_title ?: $product->name)

@if ($product->seo_description ?: $product->short_description)
    @section('meta_description', $product->seo_description ?: $product->short_description)
@endif

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/catalog-list.css') }}?v={{ filemtime(public_path('css/catalog-list.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/product-page.css') }}?v={{ filemtime(public_path('css/product-page.css')) }}">
@endpush

@section('catalog_results')
    <main class="nik-product-page">
        @php
            $hasDiscount = $product->discounted_price !== null
                && $product->price !== null
                && $product->discounted_price < $product->price;

            $hasDiscountPercent = (float) ($product->discount_percent ?? 0) > 0;
            $directionLabel = $directions[$product->direction] ?? $product->direction;
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
            $hasDocuments = $product->certificates->isNotEmpty() || filled($product->instruction_file_path);
        @endphp

        <div class="nik-product-shell">
            <nav class="nik-product-breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ route('catalog.preview') }}">Главная</a>
                <span>/</span>
                <a href="{{ route('catalog.index') }}">Каталог</a>

                @if ($product->category)
                    <span>/</span>
                    <a href="{{ route('catalog.index', ['category' => $product->category->id]) }}">{{ $product->category->name }}</a>
                @endif

                @if ($product->brand)
                    <span>/</span>
                    <a href="{{ route('catalog.index', ['brand' => $product->brand->id]) }}">{{ $product->brand->name }}</a>
                @endif

                <span>/</span>
                <span aria-current="page">{{ $product->name }}</span>
            </nav>

            <section class="nik-product-layout">
                <div class="nik-product-gallery nik-catalog2-glass" aria-label="Галерея товара">
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

                <section class="nik-product-info nik-catalog2-glass" aria-label="Информация о товаре">
                    @if ($product->brand)
                        <p class="nik-product-brand">{{ $product->brand->name }}</p>
                    @endif

                    <h1 class="nik-product-title">{{ $product->name }}</h1>

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

                        <span class="nik-product-status {{ $availabilityClass }}">{{ $availabilityLabel }}</span>
                    </div>

                    <div>
                        <div class="nik-product-price">
                            @if ($hasDiscount)
                                <span class="nik-product-current-price">{{ number_format((float) $product->discounted_price, 2, ',', ' ') }} ₽</span>
                                <span class="nik-product-old-price">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</span>
                            @elseif ($product->price !== null)
                                <span class="nik-product-current-price">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</span>
                            @endif

                            @if ($hasDiscountPercent)
                                <span class="nik-product-discount">Скидка {{ number_format((float) $product->discount_percent, 2, ',', ' ') }}%</span>
                            @endif
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="nik-product-alert">{{ session('success') }}</div>
                    @endif

                    <div class="nik-product-actions">
                        @if ($canAddToCart)
                            @if ($cartProductItem)
                                <div class="nik-product-quantity" aria-label="Количество товара в корзине">
                                    @if ($cartProductQuantity > 1)
                                        <form class="nik-product-quantity-form" method="POST" action="{{ route('cart.items.update', $cartProductItem) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ $cartProductQuantity - 1 }}">
                                            <button class="nik-product-quantity-button" type="submit" aria-label="Уменьшить количество">-</button>
                                        </form>
                                    @else
                                        <form class="nik-product-quantity-form" method="POST" action="{{ route('cart.items.destroy', $cartProductItem) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="nik-product-quantity-button" type="submit" aria-label="Убрать товар из корзины">-</button>
                                        </form>
                                    @endif

                                    <span class="nik-product-quantity-value">{{ $cartProductQuantity }}</span>

                                    <form class="nik-product-quantity-form" method="POST" action="{{ route('cart.items.update', $cartProductItem) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $cartProductQuantity + 1 }}">
                                        <button class="nik-product-quantity-button" type="submit" aria-label="Увеличить количество">+</button>
                                    </form>
                                </div>
                            @else
                                <form method="POST" action="{{ route('cart.add', $product->slug ?: $product->id) }}">
                                    @csrf

                                    <button class="nik-product-button" type="submit">Добавить в корзину</button>
                                </form>
                            @endif
                        @else
                            <button class="nik-product-button" type="button" disabled>
                                {{ $availabilityStatus === 'out_of_stock' ? 'Временно отсутствует' : 'Снят с производства' }}
                            </button>
                        @endif

                        @if ($cartProductQuantity > 0)
                            <a class="nik-product-cart-link" href="{{ route('cart.index') }}">Перейти в корзину</a>
                        @endif
                    </div>

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
                    <section class="nik-product-section nik-catalog2-glass">
                        <h2>Описание</h2>
                        <p>{{ $product->description }}</p>
                    </section>
                    @endif

                    @if (filled($product->composition))
                        <section class="nik-product-section nik-catalog2-glass">
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
                    <section class="nik-product-section nik-catalog2-glass">
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
                        <section class="nik-product-section nik-catalog2-glass">
                            <h2>Способ применения</h2>
                            <p>{{ $product->usage_method }}</p>
                        </section>
                    @endif

                    @if ($product->precautions || $product->storage_conditions || $product->disposal_method)
                        <section class="nik-product-section nik-catalog2-glass">
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
                    <section class="nik-product-section nik-catalog2-glass">
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
                <section class="nik-product-related nik-catalog-list" aria-labelledby="related-products-title">
                    <h2 id="related-products-title">Похожие товары</h2>
                    <div class="grid">
                        @foreach ($relatedProducts as $relatedProduct)
                            @include('catalog.partials.product-card', ['product' => $relatedProduct])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/product-gallery.js')
@endpush
