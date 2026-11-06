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
                    @include('catalog.partials.product-card')
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
