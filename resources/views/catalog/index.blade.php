<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог товаров</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f8fafc;
            color: #111827;
            font-family: Inter, Arial, sans-serif;
        }

        a {
            color: inherit;
        }

        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 48px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: end;
            margin-bottom: 24px;
        }

        .title {
            margin: 0;
            font-size: 2rem;
            line-height: 1.1;
        }

        .count {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
            gap: 12px;
            align-items: end;
            margin-bottom: 28px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field label {
            color: #4b5563;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .input,
        .select {
            width: 100%;
            min-height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font: inherit;
            padding: 8px 10px;
        }

        .button {
            min-height: 42px;
            border: 0;
            border-radius: 8px;
            background: #166534;
            color: #ffffff;
            font: inherit;
            font-weight: 700;
            padding: 8px 16px;
            cursor: pointer;
        }

        .reset {
            display: inline-flex;
            align-items: center;
            min-height: 42px;
            color: #4b5563;
            font-weight: 600;
            text-decoration: none;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .card {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
        }

        .image {
            display: grid;
            place-items: center;
            aspect-ratio: 640 / 980;
            background: #f3f4f6;
        }

        .image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .placeholder {
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .card-body {
            display: grid;
            gap: 8px;
            padding: 14px;
        }

        .name {
            margin: 0;
            font-size: 1rem;
            line-height: 1.3;
        }

        .meta {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .price {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: baseline;
            margin-top: 2px;
        }

        .current-price {
            color: #166534;
            font-size: 1.1rem;
            font-weight: 800;
        }

        .old-price {
            color: #9ca3af;
            text-decoration: line-through;
        }

        .empty {
            padding: 32px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            color: #6b7280;
            text-align: center;
        }

        .pagination {
            margin-top: 28px;
        }

        @media (max-width: 960px) {
            .filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .page {
                width: min(100% - 24px, 1180px);
                padding-top: 24px;
            }

            .header {
                display: grid;
            }

            .filters,
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="header">
            <div>
                <h1 class="title">Каталог товаров</h1>
                <div class="count">Найдено товаров: {{ $products->total() }}</div>
            </div>
        </header>

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

            @if ($filters['brand'] || $filters['category'] || $filters['direction'] || $filters['search'])
                <a class="reset" href="{{ route('catalog.index') }}">Сбросить</a>
            @endif
        </form>

        @if ($products->isNotEmpty())
            <section class="grid" aria-label="Список товаров">
                @foreach ($products as $product)
                    @php
                        $mainImage = $product->images->first();
                        $hasDiscount = $product->discounted_price !== null && $product->price !== null && $product->discounted_price < $product->price;
                    @endphp

                    <article class="card">
                        <div class="image">
                            @if ($mainImage)
                                <img
                                    src="{{ Storage::disk('public')->url($mainImage->file_path) }}"
                                    alt="{{ $mainImage->alt ?: $product->name }}"
                                    loading="lazy"
                                >
                            @else
                                <span class="placeholder">Нет изображения</span>
                            @endif
                        </div>

                        <div class="card-body">
                            <h2 class="name">{{ $product->name }}</h2>

                            @if ($product->article)
                                <div class="meta">Артикул: {{ $product->article }}</div>
                            @endif

                            @if ($product->volume_value || $product->volume_unit)
                                <div class="meta">
                                    Объем: {{ trim($product->volume_value . ' ' . $product->volume_unit) }}
                                </div>
                            @endif

                            <div class="price">
                                @if ($hasDiscount)
                                    <span class="current-price">{{ number_format((float) $product->discounted_price, 2, ',', ' ') }} ₽</span>
                                    <span class="old-price">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</span>
                                @elseif ($product->price !== null)
                                    <span class="current-price">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <div class="pagination">
                {{ $products->links() }}
            </div>
        @else
            <div class="empty">По выбранным условиям товары не найдены.</div>
        @endif
    </main>
</body>
</html>
