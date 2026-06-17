@extends('layouts.public')

@section('title', $product->seo_title ?: $product->name)

@if ($product->seo_description ?: $product->short_description)
    @section('meta_description', $product->seo_description ?: $product->short_description)
@endif

@push('styles')
    <style>
        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 28px 0 48px;
        }

        .back {
            display: inline-flex;
            margin-bottom: 20px;
            color: #166534;
            font-weight: 700;
            text-decoration: none;
        }

        .product {
            display: grid;
            grid-template-columns: minmax(0, 520px) minmax(0, 1fr);
            gap: 32px;
            align-items: start;
        }

        .main-image,
        .placeholder {
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            aspect-ratio: 640 / 980;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .placeholder {
            color: #9ca3af;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .thumb {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            aspect-ratio: 1;
        }

        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .summary {
            display: grid;
            gap: 18px;
        }

        .title {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.2rem);
            line-height: 1.05;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            color: #4b5563;
            font-size: 0.95rem;
        }

        .badge {
            border-radius: 999px;
            background: #ecfdf5;
            color: #166534;
            padding: 6px 10px;
            font-weight: 700;
        }

        .availability-badge {
            width: fit-content;
            border-radius: 999px;
            background: #f9fafb;
            color: #111827;
            padding: 6px 10px;
            font-weight: 800;
        }

        .price {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: baseline;
        }

        .current-price {
            color: #166534;
            font-size: 1.8rem;
            font-weight: 900;
        }

        .old-price {
            color: #9ca3af;
            font-size: 1.1rem;
            text-decoration: line-through;
        }

        .discount {
            color: #b91c1c;
            font-weight: 800;
        }

        .cart-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .button,
        .cart-link {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: fit-content;
            border: 0;
            border-radius: 8px;
            font: inherit;
            font-weight: 800;
            padding: 12px 18px;
            text-decoration: none;
        }

        .button {
            background: #166534;
            color: #ffffff;
            cursor: pointer;
        }

        .button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .cart-link {
            background: #ecfdf5;
            color: #166534;
        }

        .cart-state {
            color: #166534;
            font-weight: 800;
        }

        .alert {
            width: fit-content;
            border-radius: 8px;
            background: #ecfdf5;
            color: #166534;
            font-weight: 700;
            padding: 10px 14px;
        }

        .specs,
        .section {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 18px;
        }

        .specs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .spec-label {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .spec-value {
            margin-top: 3px;
            font-weight: 700;
        }

        .sections {
            display: grid;
            gap: 18px;
            margin-top: 28px;
        }

        .section h2 {
            margin: 0 0 10px;
            font-size: 1.25rem;
        }

        .section p {
            margin: 0;
            color: #374151;
            line-height: 1.65;
            white-space: pre-line;
        }

        .docs {
            display: grid;
            gap: 12px;
        }

        .doc {
            display: grid;
            gap: 6px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
        }

        .doc:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .doc-title {
            font-weight: 800;
        }

        .doc-meta {
            color: #6b7280;
            font-size: 0.92rem;
        }

        .doc-link {
            color: #166534;
            font-weight: 700;
        }

        .status-active {
            color: #166534;
            font-weight: 800;
        }

        .status-soon {
            color: #b45309;
            font-weight: 800;
        }

        .status-expired {
            color: #b91c1c;
            font-weight: 800;
        }

        @media (max-width: 900px) {
            .product {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .page {
                width: min(100% - 24px, 1180px);
            }

            .specs {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <main class="page">
        <a class="back" href="{{ route('catalog.index') }}">← Вернуться в каталог</a>

        @php
            $hasDiscount = $product->discounted_price !== null
                && $product->price !== null
                && $product->discounted_price < $product->price;

            $hasDiscountPercent = (float) ($product->discount_percent ?? 0) > 0;
            $directionLabel = $directions[$product->direction] ?? $product->direction;
            $availabilityLabels = [
                'in_stock' => '🟢 В наличии',
                'out_of_stock' => '🟡 Временно отсутствует',
                'discontinued' => '🔴 Снят с производства',
            ];
            $availabilityStatus = $product->availability_status ?? 'in_stock';
            $availabilityLabel = $availabilityLabels[$availabilityStatus] ?? $availabilityLabels['in_stock'];
            $canAddToCart = $availabilityStatus === 'in_stock';
            $productWeight = $product->weight_value !== null
                ? \App\Support\WeightFormatter::formatValueUnit($product->weight_value, $product->weight_unit)
                : null;
        @endphp

        <section class="product">
            <div>
                @if ($mainImage)
                    <div class="main-image">
                        <img
                            src="{{ Storage::disk('public')->url($mainImage->file_path) }}"
                            alt="{{ $mainImage->alt ?: $product->name }}"
                        >
                    </div>
                @else
                    <div class="placeholder">Нет изображения</div>
                @endif

                @if ($galleryImages->count() > 1)
                    <div class="gallery" aria-label="Галерея товара">
                        @foreach ($galleryImages as $image)
                            <div class="thumb">
                                <img
                                    src="{{ Storage::disk('public')->url($image->file_path) }}"
                                    alt="{{ $image->alt ?: $product->name }}"
                                    loading="lazy"
                                >
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="summary">
                <div>
                    <h1 class="title">{{ $product->name }}</h1>

                    <div class="meta">
                        @if ($product->article)
                            <span>Артикул: {{ $product->article }}</span>
                        @endif

                        @if ($product->barcode)
                            <span>Штрихкод: {{ $product->barcode }}</span>
                        @endif
                    </div>
                </div>

                @if ($directionLabel)
                    <div><span class="badge">{{ $directionLabel }}</span></div>
                @endif

                <div><span class="availability-badge">{{ $availabilityLabel }}</span></div>

                <div class="price">
                    @if ($hasDiscount)
                        <span class="current-price">{{ number_format((float) $product->discounted_price, 2, ',', ' ') }} ₽</span>
                        <span class="old-price">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</span>
                    @elseif ($product->price !== null)
                        <span class="current-price">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</span>
                    @endif

                    @if ($hasDiscountPercent)
                        <span class="discount">Скидка {{ number_format((float) $product->discount_percent, 2, ',', ' ') }}%</span>
                    @endif
                </div>

                @if (session('success'))
                    <div class="alert">{{ session('success') }}</div>
                @endif

                @if ($cartProductQuantity > 0)
                    <div class="cart-state">В корзине: {{ $cartProductQuantity }} шт.</div>
                @endif

                <div class="cart-actions">
                    @if ($canAddToCart)
                        <form method="POST" action="{{ route('cart.add', $product->slug ?: $product->id) }}">
                            @csrf

                            <button class="button" type="submit">
                                {{ $cartProductQuantity > 0 ? 'Товар в корзине' : 'Добавить в корзину' }}
                            </button>
                        </form>
                    @else
                        <button class="button" type="button" disabled>
                            {{ $availabilityStatus === 'out_of_stock' ? 'Временно отсутствует' : 'Снят с производства' }}
                        </button>
                    @endif

                    @if ($cartProductQuantity > 0)
                        <a class="cart-link" href="{{ route('cart.index') }}">Перейти в корзину</a>
                    @endif
                </div>

                <div class="specs">
                    <div>
                        <div class="spec-label">Статус наличия</div>
                        <div class="spec-value">{{ $availabilityLabel }}</div>
                    </div>

                    @if ($product->brand)
                        <div>
                            <div class="spec-label">Бренд</div>
                            <div class="spec-value">{{ $product->brand->name }}</div>
                        </div>
                    @endif

                    @if ($product->category)
                        <div>
                            <div class="spec-label">Категория</div>
                            <div class="spec-value">{{ $product->category->name }}</div>
                        </div>
                    @endif

                    @if ($product->productType)
                        <div>
                            <div class="spec-label">Тип товара</div>
                            <div class="spec-value">{{ $product->productType->name }}</div>
                        </div>
                    @endif

                    @if ($product->productLine)
                        <div>
                            <div class="spec-label">Линейка</div>
                            <div class="spec-value">{{ $product->productLine->name }}</div>
                        </div>
                    @endif

                    @if ($product->volume_value || $product->volume_unit)
                        <div>
                            <div class="spec-label">Объем</div>
                            <div class="spec-value">{{ trim($product->volume_value . ' ' . $product->volume_unit) }}</div>
                        </div>
                    @endif

                    @if ($productWeight)
                        <div>
                            <div class="spec-label">Вес</div>
                            <div class="spec-value">{{ $productWeight }}</div>
                        </div>
                    @endif

                    @if ($product->shelf_life_value || $product->shelf_life_unit)
                        <div>
                            <div class="spec-label">Срок годности</div>
                            <div class="spec-value">{{ trim($product->shelf_life_value . ' ' . $product->shelf_life_unit) }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <div class="sections">
            @if ($product->short_description)
                <section class="section">
                    <h2>Краткое описание</h2>
                    <p>{{ $product->short_description }}</p>
                </section>
            @endif

            @if ($product->description)
                <section class="section">
                    <h2>Описание</h2>
                    <p>{{ $product->description }}</p>
                </section>
            @endif

            @if ($product->composition)
                <section class="section">
                    <h2>Состав</h2>
                    <p>{{ $product->composition }}</p>
                </section>
            @endif

            @if ($product->usage_method)
                <section class="section">
                    <h2>Способ применения</h2>
                    <p>{{ $product->usage_method }}</p>
                </section>
            @endif

            @if ($product->storage_conditions)
                <section class="section">
                    <h2>Условия хранения</h2>
                    <p>{{ $product->storage_conditions }}</p>
                </section>
            @endif

            @if ($product->precautions)
                <section class="section">
                    <h2>Меры предосторожности</h2>
                    <p>{{ $product->precautions }}</p>
                </section>
            @endif

            @if ($product->disposal_method)
                <section class="section">
                    <h2>Утилизация</h2>
                    <p>{{ $product->disposal_method }}</p>
                </section>
            @endif

            @if ($product->certificates->isNotEmpty() || $product->instruction_file_path)
                <section class="section">
                    <h2>Документация</h2>

                    <div class="docs">
                        @foreach ($product->certificates as $certificate)
                            @php
                                $expiresAt = $certificate->expires_at;
                                $isExpired = $expiresAt && $expiresAt->isPast();
                                $isSoon = $expiresAt && ! $isExpired && $expiresAt->lte(now()->addDays(30));
                                $status = $isExpired ? 'просрочен' : ($isSoon ? 'скоро истекает' : 'действует');
                                $statusClass = $isExpired ? 'status-expired' : ($isSoon ? 'status-soon' : 'status-active');
                            @endphp

                            <article class="doc">
                                <div class="doc-title">{{ $certificate->name }}</div>
                                <div class="doc-meta">
                                    @if ($certificate->certificate_type)
                                        {{ $certificate->certificate_type }}
                                    @endif

                                    @if ($certificate->number)
                                        № {{ $certificate->number }}
                                    @endif
                                </div>
                                <div class="doc-meta">
                                    Статус: <span class="{{ $statusClass }}">{{ $status }}</span>

                                    @if ($certificate->is_permanent)
                                        · бессрочно
                                    @elseif ($expiresAt)
                                        · до {{ $expiresAt->format('d.m.Y') }}
                                    @endif
                                </div>

                                @if ($certificate->file_path)
                                    <a class="doc-link" href="{{ Storage::disk('public')->url($certificate->file_path) }}" target="_blank" rel="noopener">
                                        Открыть PDF
                                    </a>
                                @endif
                            </article>
                        @endforeach

                        @if ($product->instruction_file_path)
                            <article class="doc">
                                <div class="doc-title">Инструкция по применению</div>
                                <a class="doc-link" href="{{ Storage::disk('public')->url($product->instruction_file_path) }}" target="_blank" rel="noopener">
                                    Открыть PDF
                                </a>
                            </article>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection
