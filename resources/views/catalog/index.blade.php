@extends('layouts.shop')

@section('title', 'Каталог товаров — НИКТРЕЙД')
@section('meta_description', 'Каталог бытовой и профессиональной химии НИКТРЕЙД. Подбор товаров по бренду, категории и назначению.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-catalog.css') }}?v={{ filemtime(public_path('css/shop-catalog.css')) }}">
@endpush

@section('content')
    @php
        $hasActiveFilters = $filters['brand'] || $filters['category'] || $filters['direction'] || $filters['search'];
    @endphp

    <nav class="shop-crumbs" aria-label="Хлебные крошки">
        <a href="{{ route('catalog.preview3') }}">Главная</a>
        <span>/</span>
        <span aria-current="page">Каталог</span>
    </nav>

    <header class="shop-head">
        <div>
            <h1>Каталог товаров</h1>
            <p>Для дома, бизнеса и ежедневной чистоты</p>
        </div>
        <div class="shop-count">Найдено товаров: <strong>{{ $products->total() }}</strong></div>
    </header>

    <details class="shop-filters" id="catalog-filters" @if ($hasActiveFilters) open @endif>
        <summary>
            <span>{{ $hasActiveFilters ? 'Фильтры применены' : 'Показать фильтры' }}</span>

            @if ($hasActiveFilters)
                <a class="shop-filters-reset" href="{{ route('catalog.index') }}">Сбросить фильтры</a>
            @else
                <span class="shop-filters-state">поиск, бренд, категория, направление</span>
            @endif
        </summary>

        <form class="shop-filters-body" method="GET" action="{{ route('catalog.index') }}">
            <div class="shop-field">
                <label for="search">Поиск</label>
                <input id="search" class="shop-input" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Название или артикул">
            </div>

            <div class="shop-field">
                <label for="brand">Бренд</label>
                <select id="brand" class="shop-select" name="brand">
                    <option value="">Все бренды</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected($filters['brand'] === $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="shop-field">
                <label for="category">Категория</label>
                <select id="category" class="shop-select" name="category">
                    <option value="">Все категории</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($filters['category'] === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="shop-field">
                <label for="direction">Направление</label>
                <select id="direction" class="shop-select" name="direction">
                    <option value="">Все направления</option>
                    @foreach ($directions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['direction'] === $value)>{{ \App\Support\ProductName::directionLabel($value) ?? $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="shop-filters-actions">
                <button class="shop-btn" type="submit">Показать</button>
                @if ($hasActiveFilters)
                    <a class="shop-reset" href="{{ route('catalog.index') }}">Сбросить</a>
                @endif
            </div>
        </form>
    </details>

    @if ($products->isNotEmpty())
        <section class="v3-products is-catalog" aria-label="Список товаров">
            @foreach ($products as $product)
                @include('shop.partials.product-card', ['product' => $product])
            @endforeach
        </section>

        <div class="shop-pagination">
            {{ $products->links('pagination.catalog') }}
        </div>
    @else
        <div class="shop-empty">
            <h2>По выбранным условиям товары не найдены</h2>
            <p>Попробуйте изменить запрос или сбросить фильтры.</p>
            <a class="shop-btn" href="{{ route('catalog.index') }}">Все товары</a>
        </div>
    @endif
@endsection
