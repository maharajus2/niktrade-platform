@extends('layouts.business')

@section('title', 'Каталог для бизнеса — НИКТРЕЙД')
@section('meta_description', 'Каталог НИКТРЕЙД для корпоративных клиентов: цены для бизнеса и персональная скидка.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-catalog.css') }}?v={{ filemtime(public_path('css/shop-catalog.css')) }}">
@endpush

@section('content')
    @php
        $bizQty = $contractor->cartItems()->pluck('quantity', 'product_id');
        $hasActiveFilters = $filters['brand'] || $filters['category'] || $filters['direction'] || $filters['search'];
    @endphp

    <nav class="shop-crumbs" aria-label="Хлебные крошки">
        <a href="{{ route('business.account') }}">Кабинет</a>
        <span>/</span>
        <span aria-current="page">Каталог</span>
    </nav>

    <header class="shop-head">
        <div>
            <h1>Каталог для бизнеса</h1>
            <p>@if ($contractor->discount_percent > 0)Цены с вашей персональной скидкой {{ rtrim(rtrim(number_format((float) $contractor->discount_percent, 2, ',', ''), '0'), ',') }}%@else Цены для корпоративных клиентов @endif · заявка от 5 000 ₽</p>
        </div>
        <div class="shop-count">Найдено товаров: <strong>{{ $products->total() }}</strong></div>
    </header>

    <details class="shop-filters" id="catalog-filters" @if ($hasActiveFilters) open @endif>
        <summary>
            <span>{{ $hasActiveFilters ? 'Фильтры применены' : 'Показать фильтры' }}</span>

            @if ($hasActiveFilters)
                <a class="shop-filters-reset" href="{{ route('business.catalog') }}">Сбросить фильтры</a>
            @else
                <span class="shop-filters-state">поиск, бренд, категория, направление</span>
            @endif
        </summary>

        <form class="shop-filters-body" method="GET" action="{{ route('business.catalog') }}">
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
                    <a class="shop-reset" href="{{ route('business.catalog') }}">Сбросить</a>
                @endif
            </div>
        </form>
    </details>

    @if ($products->isNotEmpty())
        <section class="v3-products is-catalog" aria-label="Список товаров">
            @foreach ($products as $product)
                @include('business.partials.product-card', ['product' => $product, 'bizQty' => $bizQty])
            @endforeach
        </section>

        <div class="shop-pagination">
            {{ $products->links('pagination.catalog') }}
        </div>
    @else
        <div class="shop-empty">
            <h2>По выбранным условиям товары не найдены</h2>
            <p>Попробуйте изменить запрос или сбросить фильтры.</p>
            <a class="shop-btn" href="{{ route('business.catalog') }}">Все товары</a>
        </div>
    @endif
@endsection
