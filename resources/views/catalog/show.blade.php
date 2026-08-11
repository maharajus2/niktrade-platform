@extends('layouts.public')

@section('title', $product->seo_title ?: $product->name)

@if ($product->seo_description ?: $product->short_description)
    @section('meta_description', $product->seo_description ?: $product->short_description)
@endif

@push('styles')
    <style>
        body {
            min-width: 320px;
            background:
                radial-gradient(circle at 8% 2%, rgba(10, 132, 255, .12), transparent 28rem),
                radial-gradient(circle at 92% 9%, rgba(33, 201, 139, .11), transparent 28rem),
                linear-gradient(135deg, #fbfdff 0%, #eef8ff 46%, #ffffff 100%);
            background-attachment: fixed;
            color: #10223f;
        }

        .nik-product-page,
        .nik-product-page * {
            box-sizing: border-box;
        }

        .nik-product-page {
            --liquid-blue: #0a84ff;
            --liquid-blue-deep: #006eea;
            --liquid-green: #21c98b;
            --liquid-ink: #10223f;
            --liquid-muted: rgba(16, 34, 63, .62);
            --liquid-border: rgba(255, 255, 255, .68);
            --liquid-shadow: 0 20px 54px rgba(20, 82, 148, .10), 0 1px 0 rgba(255, 255, 255, .72);
            position: relative;
            isolation: isolate;
            overflow: hidden;
            min-height: calc(100vh - 64px);
            padding: 24px 0 72px;
        }

        .nik-product-page::before {
            position: fixed;
            inset: 0;
            z-index: -1;
            background:
                radial-gradient(circle at 4% 18%, rgba(255, 255, 255, .56) 0 16px, transparent 17px),
                radial-gradient(circle at 96% 16%, rgba(255, 255, 255, .44) 0 22px, transparent 23px),
                linear-gradient(118deg, rgba(255, 255, 255, .24), transparent 30%, rgba(33, 201, 139, .05) 70%, transparent);
            opacity: .72;
            pointer-events: none;
            content: "";
        }

        .nik-product-shell {
            width: min(1360px, calc(100% - 48px));
            margin: 0 auto;
        }

        .nik-product-glass {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--liquid-border);
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .62), rgba(236, 249, 255, .34) 56%, rgba(255, 255, 255, .50));
            box-shadow: var(--liquid-shadow), inset 0 1px 0 rgba(255, 255, 255, .72);
            backdrop-filter: blur(20px) saturate(145%);
            -webkit-backdrop-filter: blur(20px) saturate(145%);
            isolation: isolate;
        }

        .nik-product-glass::before {
            position: absolute;
            inset: 0;
            z-index: 0;
            border-radius: inherit;
            background:
                linear-gradient(128deg, rgba(255, 255, 255, .42), transparent 32%),
                linear-gradient(312deg, rgba(10, 132, 255, .055), transparent 48%);
            opacity: .68;
            pointer-events: none;
            content: "";
        }

        .nik-product-glass > * {
            position: relative;
            z-index: 1;
        }

        .nik-product-breadcrumbs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 20px;
            color: rgba(16, 34, 63, .48);
            font-size: 13px;
            font-weight: 600;
        }

        .nik-product-breadcrumbs a {
            text-decoration: none;
        }

        .nik-product-breadcrumbs a:hover {
            color: var(--liquid-blue-deep);
        }

        .nik-product-breadcrumbs span {
            max-width: min(420px, 100%);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .nik-product-layout {
            display: grid;
            grid-template-columns: minmax(0, 54fr) minmax(420px, 46fr);
            gap: 28px;
            align-items: start;
        }

        .nik-product-gallery {
            border-radius: 26px;
            padding: 20px;
        }

        .nik-product-main-image,
        .nik-product-placeholder {
            position: relative;
            display: grid;
            place-items: center;
            height: clamp(560px, 58vh, 650px);
            overflow: hidden;
            border-radius: 22px;
            background:
                radial-gradient(circle at 50% 18%, rgba(255, 255, 255, .76), transparent 38%),
                linear-gradient(135deg, rgba(255, 255, 255, .30), rgba(224, 246, 255, .16));
        }

        .nik-product-main-image img {
            position: absolute;
            inset: 28px;
            width: calc(100% - 56px);
            height: calc(100% - 56px);
            object-fit: contain;
            filter: drop-shadow(0 18px 26px rgba(26, 83, 140, .10));
        }

        .nik-product-placeholder {
            color: rgba(16, 34, 63, .48);
            font-weight: 800;
        }

        .nik-product-mobile-gallery {
            display: none;
        }

        .nik-product-thumbs {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .nik-product-thumb {
            display: grid;
            place-items: center;
            overflow: hidden;
            width: 74px;
            height: 74px;
            border: 1px solid rgba(255, 255, 255, .70);
            border-radius: 14px;
            background: rgba(255, 255, 255, .46);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .72);
            text-decoration: none;
            transition: transform .18s ease, border-color .18s ease;
        }

        .nik-product-thumb:hover,
        .nik-product-thumb:focus-visible {
            border-color: rgba(10, 132, 255, .42);
            outline: 0;
            transform: translateY(-1px);
        }

        .nik-product-thumb:first-child {
            border-color: rgba(10, 132, 255, .38);
        }

        .nik-product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .nik-product-info {
            display: grid;
            gap: 22px;
            border-radius: 26px;
            padding: clamp(28px, 3vw, 42px);
        }

        .nik-product-brand {
            margin: 0 0 -8px;
            color: var(--liquid-blue-deep);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .10em;
            text-transform: uppercase;
        }

        .nik-product-title {
            margin: 0;
            color: var(--liquid-ink);
            font-size: clamp(2.45rem, 3.55vw, 3.25rem);
            font-weight: 700;
            line-height: 1.06;
            letter-spacing: 0;
        }

        .nik-product-intro {
            max-width: 56ch;
            margin: -6px 0 0;
            color: rgba(16, 34, 63, .70);
            font-size: 1rem;
            line-height: 1.62;
            white-space: pre-line;
        }

        .nik-product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 9px 14px;
            align-items: center;
            margin-top: -2px;
        }

        .nik-product-chip {
            color: rgba(16, 34, 63, .58);
            font-size: 13px;
            font-weight: 700;
        }

        .nik-product-status {
            display: inline-flex;
            gap: 7px;
            align-items: center;
            color: #087443;
            font-size: 13px;
            font-weight: 900;
        }

        .nik-product-status::before {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
            content: "";
        }

        .nik-product-status.is-out {
            color: #a16207;
        }

        .nik-product-status.is-discontinued {
            color: #b91c1c;
        }

        .nik-product-price {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .nik-product-current-price {
            color: var(--liquid-blue-deep);
            font-size: clamp(2rem, 3vw, 2.45rem);
            font-weight: 750;
            line-height: 1;
        }

        .nik-product-old-price {
            color: rgba(16, 34, 63, .42);
            font-size: 1.08rem;
            font-weight: 800;
            text-decoration: line-through;
        }

        .nik-product-discount {
            display: inline-flex;
            min-height: 30px;
            align-items: center;
            border-radius: 999px;
            background: linear-gradient(180deg, #2a9dff, #006eea);
            color: #fff;
            font-size: .85rem;
            font-weight: 800;
            padding: 5px 10px;
            box-shadow: 0 8px 20px rgba(0, 112, 235, .16), inset 0 1px 0 rgba(255, 255, 255, .32);
        }

        .nik-product-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .nik-product-button,
        .nik-product-cart-link,
        .nik-product-quantity-button,
        .nik-product-doc-link {
            border: 1px solid rgba(255, 255, 255, .58);
            font: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .nik-product-button,
        .nik-product-cart-link {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-weight: 800;
            padding: 12px 18px;
        }

        .nik-product-button {
            min-width: 188px;
            background: linear-gradient(180deg, #188fff, #006eea);
            color: #fff;
            box-shadow: 0 12px 24px rgba(0, 112, 235, .22), inset 0 1px 0 rgba(255, 255, 255, .42);
        }

        .nik-product-button:disabled {
            background:
                radial-gradient(circle at 22% 0%, rgba(255, 255, 255, .40), transparent 40%),
                linear-gradient(180deg, rgba(127, 143, 164, .72), rgba(92, 108, 132, .70));
            color: rgba(255, 255, 255, .90);
            cursor: not-allowed;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .32);
        }

        .nik-product-cart-link,
        .nik-product-doc-link {
            background: rgba(255, 255, 255, .48);
            color: var(--liquid-blue-deep);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .78);
        }

        .nik-product-button:not(:disabled):hover,
        .nik-product-cart-link:hover,
        .nik-product-doc-link:hover,
        .nik-product-quantity-button:hover {
            border-color: rgba(10, 132, 255, .36);
            box-shadow: 0 12px 26px rgba(0, 112, 235, .14), inset 0 1px 0 rgba(255, 255, 255, .88);
            transform: translateY(-1px);
        }

        .nik-product-quantity {
            display: inline-grid;
            grid-template-columns: 46px minmax(52px, auto) 46px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .62);
            border-radius: 16px;
            background: rgba(255, 255, 255, .46);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .76);
        }

        .nik-product-quantity-form {
            display: contents;
        }

        .nik-product-quantity-button,
        .nik-product-quantity-value {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            justify-content: center;
        }

        .nik-product-quantity-button {
            border: 0;
            background: rgba(255, 255, 255, .22);
            color: var(--liquid-blue-deep);
            font-size: 1.25rem;
            font-weight: 900;
        }

        .nik-product-quantity-value {
            border-inline: 1px solid rgba(255, 255, 255, .62);
            color: var(--liquid-ink);
            font-weight: 900;
            padding: 0 14px;
        }

        .nik-product-alert {
            width: fit-content;
            border: 1px solid rgba(255, 255, 255, .62);
            border-radius: 16px;
            background: rgba(234, 255, 247, .58);
            color: #087443;
            font-weight: 800;
            padding: 11px 14px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .86);
        }

        .nik-product-specs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 22px;
            margin-top: 2px;
        }

        .nik-product-spec {
            min-width: 0;
            padding-top: 14px;
            border-top: 1px solid rgba(16, 34, 63, .09);
        }

        .nik-product-spec-label {
            color: rgba(16, 34, 63, .54);
            font-size: .82rem;
            font-weight: 700;
        }

        .nik-product-spec-value {
            margin-top: 5px;
            color: var(--liquid-ink);
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .nik-product-content-nav {
            display: flex;
            gap: 8px;
            margin: 44px 0 28px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: none;
        }

        .nik-product-content-nav::-webkit-scrollbar {
            display: none;
        }

        .nik-product-content-nav a {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            min-height: 40px;
            border: 1px solid rgba(16, 34, 63, .08);
            border-radius: 999px;
            background: rgba(255, 255, 255, .52);
            color: rgba(16, 34, 63, .68);
            font-size: 14px;
            font-weight: 800;
            padding: 9px 14px;
            text-decoration: none;
        }

        .nik-product-sections {
            display: grid;
            gap: 48px;
            max-width: 980px;
            margin: 0 auto;
        }

        .nik-product-section {
            scroll-margin-top: 28px;
        }

        .nik-product-section h2 {
            margin: 0 0 14px;
            color: var(--liquid-ink);
            font-size: clamp(1.45rem, 2.4vw, 2rem);
            font-weight: 760;
            line-height: 1.2;
        }

        .nik-product-section h3 {
            margin: 28px 0 8px;
            color: var(--liquid-ink);
            font-size: 1.05rem;
            line-height: 1.25;
        }

        .nik-product-section p {
            margin: 0;
            color: rgba(16, 34, 63, .70);
            font-size: 16px;
            line-height: 1.78;
            white-space: pre-line;
        }

        .nik-product-details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 34px;
            margin-top: 18px;
        }

        .nik-product-detail {
            min-width: 0;
            padding-top: 14px;
            border-top: 1px solid rgba(16, 34, 63, .10);
        }

        .nik-product-detail-label {
            color: rgba(16, 34, 63, .52);
            font-size: 13px;
            font-weight: 700;
        }

        .nik-product-detail-value {
            margin-top: 5px;
            color: var(--liquid-ink);
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .nik-product-docs {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        .nik-product-doc {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px 18px;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, .66);
            border-radius: 18px;
            background: rgba(255, 255, 255, .48);
            box-shadow: 0 14px 34px rgba(20, 82, 148, .08), inset 0 1px 0 rgba(255, 255, 255, .78);
            padding: 16px;
        }

        .nik-product-doc-title {
            color: var(--liquid-ink);
            font-weight: 900;
        }

        .nik-product-doc-meta {
            color: rgba(16, 34, 63, .58);
            font-size: .92rem;
            line-height: 1.45;
        }

        .nik-product-doc-link {
            display: inline-flex;
            width: fit-content;
            min-height: 38px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-weight: 800;
            padding: 9px 13px;
        }

        .nik-product-status-active {
            color: #087443;
            font-weight: 900;
        }

        .nik-product-status-soon {
            color: #a16207;
            font-weight: 900;
        }

        .nik-product-status-expired {
            color: #b91c1c;
            font-weight: 900;
        }

        @media (max-width: 1024px) {
            .nik-product-layout {
                grid-template-columns: 1fr;
            }

            .nik-product-info {
                gap: 18px;
            }

            .nik-product-main-image,
            .nik-product-placeholder {
                height: 440px;
            }
        }

        @media (max-width: 640px) {
            .public-header__inner {
                width: calc(100% - 24px);
                max-width: 1180px;
            }

            .public-header__brand,
            .public-header__nav {
                width: auto;
                min-width: 0;
            }

            .public-header__nav {
                flex-wrap: wrap;
                justify-content: flex-start;
            }

            .public-header__link,
            .public-header__logout,
            .public-header__cart {
                white-space: nowrap;
            }

            .nik-product-page {
                padding: 16px 0 44px;
            }

            .nik-product-shell {
                width: min(100% - 24px, 1360px);
            }

            .nik-product-breadcrumbs {
                gap: 6px;
                margin-bottom: 12px;
                font-size: 12px;
            }

            .nik-product-breadcrumbs span {
                max-width: 210px;
            }

            .nik-product-gallery,
            .nik-product-info {
                border-radius: 20px;
            }

            .nik-product-gallery {
                padding: 14px;
            }

            .nik-product-main-image,
            .nik-product-placeholder {
                height: min(60vh, 420px);
            }

            .nik-product-mobile-gallery {
                display: none;
            }

            .nik-product-mobile-gallery::-webkit-scrollbar {
                display: none;
            }

            .nik-product-mobile-slide {
                display: grid;
                place-items: center;
                min-height: min(60vh, 420px);
                overflow: hidden;
                border-radius: 18px;
                background:
                    radial-gradient(circle at 45% 12%, rgba(255, 255, 255, .68), transparent 34%),
                    linear-gradient(135deg, rgba(255, 255, 255, .34), rgba(218, 244, 255, .20));
                scroll-snap-align: center;
            }

            .nik-product-mobile-slide img {
                position: absolute;
                inset: 14px;
                width: calc(100% - 28px);
                height: calc(100% - 28px);
                object-fit: contain;
                filter: drop-shadow(0 18px 28px rgba(26, 83, 140, .12));
            }

            .nik-product-placeholder {
                min-height: 320px;
            }

            .nik-product-thumbs {
                display: flex;
                gap: 8px;
                overflow-x: auto;
                padding-bottom: 2px;
                scrollbar-width: none;
            }

            .nik-product-thumbs::-webkit-scrollbar {
                display: none;
            }

            .nik-product-thumb {
                flex: 0 0 58px;
                width: 58px;
                height: 58px;
                border-radius: 14px;
            }

            .nik-product-info {
                padding: 22px;
            }

            .nik-product-title {
                font-size: clamp(2rem, 9.8vw, 2.75rem);
                line-height: 1.08;
            }

            .nik-product-intro {
                font-size: .98rem;
            }

            .nik-product-actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .nik-product-button,
            .nik-product-cart-link {
                width: 100%;
            }

            .nik-product-quantity {
                width: 100%;
                grid-template-columns: 48px minmax(0, 1fr) 48px;
            }

            .nik-product-specs {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .nik-product-section {
                scroll-margin-top: 18px;
            }

            .nik-product-content-nav {
                flex-wrap: wrap;
                margin: 30px 0 28px;
                overflow: visible;
            }

            .nik-product-sections {
                gap: 38px;
            }

            .nik-product-section p {
                font-size: 15px;
            }

            .nik-product-details-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .nik-product-doc {
                grid-template-columns: 1fr;
            }

            .nik-product-doc-link {
                width: 100%;
            }
        }

        @media (max-width: 360px) {
            .nik-product-shell {
                width: min(100% - 16px, 1360px);
            }

            .nik-product-info {
                padding: 18px;
            }

            .nik-product-current-price {
                font-size: 1.8rem;
            }
        }
    </style>
@endpush

@section('content')
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
        @endphp

        <div class="nik-product-shell">
            <nav class="nik-product-breadcrumbs" aria-label="Хлебные крошки">
                <a href="{{ url('/') }}">Главная</a>
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
                <div class="nik-product-gallery nik-product-glass" aria-label="Галерея товара">
                    @if ($mainImage)
                        <a
                            class="nik-product-main-image"
                            href="{{ Storage::disk('public')->url($mainImage->file_path) }}"
                            target="_blank"
                            rel="noopener"
                            aria-label="Открыть изображение товара"
                        >
                            <img
                                src="{{ Storage::disk('public')->url($mainImage->file_path) }}"
                                alt="{{ $mainImage->alt ?: $product->name }}"
                            >
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
                                    class="nik-product-thumb"
                                    href="{{ Storage::disk('public')->url($image->file_path) }}"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="Открыть фотографию товара"
                                >
                                    <img
                                        src="{{ Storage::disk('public')->url($image->file_path) }}"
                                        alt="{{ $image->alt ?: $product->name }}"
                                        loading="lazy"
                                    >
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <section class="nik-product-info nik-product-glass" aria-label="Информация о товаре">
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

            <nav class="nik-product-content-nav" aria-label="Разделы товара">
                @if ($product->description || $product->composition)
                    <a href="#description">Описание</a>
                @endif

                <a href="#characteristics">Характеристики</a>

                @if ($product->usage_method)
                    <a href="#usage">Применение</a>
                @endif

                @if ($product->precautions || $product->storage_conditions || $product->disposal_method)
                    <a href="#safety">Безопасность</a>
                @endif

                @if ($product->certificates->isNotEmpty() || $product->instruction_file_path)
                    <a href="#documents">Документы</a>
                @endif
            </nav>

            <div class="nik-product-sections">
                @if ($product->description || $product->composition)
                    <section id="description" class="nik-product-section">
                        <h2>Описание</h2>

                        @if ($product->description)
                            <p>{{ $product->description }}</p>
                        @endif

                        @if ($product->composition)
                            <h3>Состав</h3>
                            <p>{{ $product->composition }}</p>
                        @endif
                    </section>
                @endif

                <section id="characteristics" class="nik-product-section">
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
                    <section id="usage" class="nik-product-section">
                        <h2>Способ применения</h2>
                        <p>{{ $product->usage_method }}</p>
                    </section>
                @endif

                @if ($product->precautions || $product->storage_conditions || $product->disposal_method)
                    <section id="safety" class="nik-product-section">
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

                @if ($product->certificates->isNotEmpty() || $product->instruction_file_path)
                    <section id="documents" class="nik-product-section">
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
                @endif
            </div>
        </div>
    </main>
@endsection
