@extends('catalog2.index')

@section('title', 'Каталог товаров')
@section('meta_description', 'Каталог бытовой и профессиональной химии НИКТРЕЙД. Подбор товаров по бренду, категории и назначению.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/catalog-list.css') }}?v={{ filemtime(public_path('css/catalog-list.css')) }}">
@endpush

@section('catalog_results')
    <main class="nik-catalog-list" id="products">
        <header class="header nik-catalog2-glass">
            <div>
                <a class="breadcrumb" href="{{ route('catalog.preview') }}">Главная /</a>
                <h1 class="title">Каталог товаров</h1>
                <p class="subtitle">Для дома, бизнеса и ежедневной чистоты</p>
            </div>
            <div class="count">Найдено товаров: <strong>{{ $products->total() }}</strong></div>
        </header>

        @php
            $hasActiveFilters = $filters['brand'] || $filters['category'] || $filters['direction'] || $filters['search'];
        @endphp

        <details class="filters-panel nik-catalog2-glass" id="catalog-filters" @if ($hasActiveFilters) open @endif>
            <summary class="filters-toggle">
                <span>{{ $hasActiveFilters ? 'Фильтры применены' : 'Показать фильтры' }}</span>

                @if ($hasActiveFilters)
                    <a class="filters-reset" href="{{ route('catalog.index') }}">Сбросить фильтры</a>
                @else
                    <span class="filters-state">поиск, бренд, категория, направление</span>
                @endif
            </summary>

            <form class="filters" method="GET" action="{{ route('catalog.index') }}">
            <div class="field">
                <label for="search">Поиск</label>
                <input
                    id="search"
                    class="input"
                    type="search"
                    name="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Название или артикул"
                >
            </div>

            <div class="field">
                <label for="brand">Бренд</label>
                <select id="brand" class="select" name="brand">
                    <option value="">Все бренды</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected($filters['brand'] === $brand->id)>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="category">Категория</label>
                <select id="category" class="select" name="category">
                    <option value="">Все категории</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($filters['category'] === $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="direction">Направление</label>
                <select id="direction" class="select" name="direction">
                    <option value="">Все направления</option>
                    @foreach ($directions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['direction'] === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button class="button" type="submit">Показать</button>

            @if ($hasActiveFilters)
                <a class="reset" href="{{ route('catalog.index') }}">Сбросить</a>
            @endif
            </form>
        </details>

        @if ($products->isNotEmpty())
            <section class="grid" aria-label="Список товаров">
                @foreach ($products as $product)
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
                @endforeach
            </section>

            <div class="pagination">
                {{ $products->links('pagination.catalog') }}
            </div>
        @else
            <div class="empty nik-catalog2-glass">
                <h2>По выбранным условиям товары не найдены</h2>
                <p>Попробуйте изменить запрос или сбросить фильтры.</p>
                <a class="button" href="{{ route('catalog.index') }}">Все товары</a>
            </div>
        @endif
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/catalog-list.js') }}?v={{ filemtime(public_path('js/catalog-list.js')) }}" defer></script>
@endpush
