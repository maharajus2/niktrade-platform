@extends('layouts.public')

@section('title', 'Заказ ' . $order->order_number)

@push('styles')
    <style>
        .account-page {
            width: min(1080px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 48px;
        }

        .account-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 22px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .title-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .account-nav__link,
        .account-nav__button,
        .button,
        .danger-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            border: 0;
            border-radius: 8px;
            font: inherit;
            font-weight: 800;
            padding: 9px 12px;
            text-decoration: none;
            cursor: pointer;
        }

        .account-nav__link,
        .account-nav__button,
        .button {
            background: #ffffff;
            color: #166534;
        }

        .danger-button {
            background: #fee2e2;
            color: #991b1b;
        }

        .title {
            margin: 0;
            font-size: 2rem;
        }

        .status-badge {
            border-radius: 999px;
            background: #ecfdf5;
            color: #166534;
            font-weight: 900;
            padding: 7px 11px;
        }

        .status-badge--danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 18px;
        }

        .card-title {
            margin: 0 0 12px;
            font-size: 1.15rem;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border-top: 1px solid #f3f4f6;
            padding: 9px 0;
        }

        .row:first-of-type {
            border-top: 0;
        }

        .label {
            color: #6b7280;
            font-weight: 700;
        }

        .value {
            text-align: right;
            font-weight: 900;
        }

        .items {
            display: grid;
            gap: 12px;
        }

        .item {
            display: grid;
            grid-template-columns: 72px minmax(0, 1fr);
            gap: 14px;
            border: 1px solid #f3f4f6;
            border-radius: 10px;
            padding: 12px;
        }

        .product-thumb {
            display: grid;
            place-items: center;
            overflow: hidden;
            width: 72px;
            height: 72px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            color: #9ca3af;
            font-size: 0.72rem;
            text-align: center;
        }

        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-body {
            display: grid;
            gap: 7px;
            min-width: 0;
        }

        .item-name {
            color: #111827;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .product-link {
            color: #166534;
            text-decoration: none;
        }

        .item-meta {
            color: #6b7280;
            font-size: 0.92rem;
        }

        .item-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 16px;
            align-items: baseline;
        }

        .line-total {
            font-weight: 900;
        }

        .message {
            border-radius: 8px;
            background: #ecfdf5;
            color: #166534;
            font-weight: 800;
            margin-bottom: 16px;
            padding: 12px;
        }

        @media (max-width: 820px) {
            .account-page {
                width: min(100% - 24px, 1080px);
            }

            .page-header,
            .grid {
                grid-template-columns: 1fr;
                display: grid;
            }

            .actions {
                justify-content: flex-start;
            }

            .value {
                text-align: left;
            }
        }

        @media (max-width: 420px) {
            .item {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <main class="account-page">
        @include('customer-account.partials.nav')

        <div class="page-header">
            <div class="title-row">
                <h1 class="title">Заказ {{ $order->order_number }}</h1>
                <span class="status-badge {{ $order->status === \App\Models\Order::STATUS_CANCELLED ? 'status-badge--danger' : '' }}">
                    {{ \App\Models\Order::statusLabel($order->status) }}
                </span>
            </div>

            <div class="actions">
                <a class="button" href="{{ route('customer.account.orders') }}">К списку заказов</a>

                @if ($order->canBeCancelledByCustomer())
                    <form method="POST" action="{{ route('customer.orders.cancel', $order) }}" onsubmit="return confirm('Вы уверены, что хотите отменить заказ?')">
                        @csrf

                        <button class="danger-button" type="submit">Отменить заказ</button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="message">{{ session('success') }}</div>
        @endif

        @error('order')
            <div class="message">{{ $message }}</div>
        @enderror

        <div class="grid">
            <section class="card">
                <h2 class="card-title">Покупатель</h2>
                <div class="row">
                    <span class="label">Имя</span>
                    <span class="value">{{ trim($order->customer_first_name . ' ' . $order->customer_last_name) ?: '—' }}</span>
                </div>
                <div class="row">
                    <span class="label">Email</span>
                    <span class="value">{{ $order->email }}</span>
                </div>
                <div class="row">
                    <span class="label">Телефон</span>
                    <span class="value">{{ $order->phone }}</span>
                </div>
            </section>

            @if (($order->fulfillment_method ?? \App\Models\Order::FULFILLMENT_DELIVERY) === \App\Models\Order::FULFILLMENT_PICKUP)
                <section class="card">
                    <h2 class="card-title">Пункт самовывоза</h2>
                    <div class="row">
                        <span class="label">Пункт</span>
                        <span class="value">{{ $order->warehouse_name_snapshot ?: '—' }}</span>
                    </div>
                    <div class="row">
                        <span class="label">Адрес</span>
                        <span class="value">{{ $order->warehouse_address_snapshot ?: '—' }}</span>
                    </div>
                    @if ($order->warehouse_phone_snapshot)
                        <div class="row">
                            <span class="label">Телефон</span>
                            <span class="value">{{ $order->warehouse_phone_snapshot }}</span>
                        </div>
                    @endif
                    @if ($order->warehouse_working_hours_snapshot)
                        <div class="row">
                            <span class="label">Часы работы</span>
                            <span class="value">{{ $order->warehouse_working_hours_snapshot }}</span>
                        </div>
                    @endif
                </section>
            @else
                <section class="card">
                    <h2 class="card-title">Адрес доставки</h2>
                    <div class="row">
                        <span class="label">Адрес</span>
                        <span class="value">
                            {{ collect([$order->postal_code, $order->region, $order->city, $order->street, $order->house, $order->building, $order->apartment])->filter()->implode(', ') ?: '—' }}
                        </span>
                    </div>
                    <div class="row">
                        <span class="label">Подъезд / этаж</span>
                        <span class="value">{{ collect([$order->entrance, $order->floor])->filter()->implode(' / ') ?: '—' }}</span>
                    </div>
                    @if ($order->delivery_comment)
                        <div class="row">
                            <span class="label">Комментарий</span>
                            <span class="value">{{ $order->delivery_comment }}</span>
                        </div>
                    @endif
                </section>
            @endif

            <section class="card">
                <h2 class="card-title">Статусы</h2>
                <div class="row">
                    <span class="label">Заказ</span>
                    <span class="value">{{ \App\Models\Order::statusLabel($order->status) }}</span>
                </div>
                <div class="row">
                    <span class="label">Оплата</span>
                    <span class="value">{{ \App\Models\Order::paymentStatusLabel($order->payment_status) }}</span>
                </div>
                <div class="row">
                    <span class="label">Доставка</span>
                    <span class="value">{{ \App\Models\Order::deliveryStatusLabel($order->delivery_status) }}</span>
                </div>
            </section>

            <section class="card">
                <h2 class="card-title">Итоги</h2>
                <div class="row">
                    <span class="label">Сумма товаров</span>
                    <span class="value">{{ number_format((float) $order->subtotal, 2, ',', ' ') }} ₽</span>
                </div>
                @if ((float) $order->discount_total > 0)
                    <div class="row">
                        <span class="label">Скидка</span>
                        <span class="value">{{ number_format((float) $order->discount_total, 2, ',', ' ') }} ₽</span>
                    </div>
                @endif
                <div class="row">
                    <span class="label">Доставка</span>
                    <span class="value">{{ number_format((float) $order->delivery_total, 2, ',', ' ') }} ₽</span>
                </div>
                <div class="row">
                    <span class="label">Итого</span>
                    <span class="value">{{ number_format((float) $order->total, 2, ',', ' ') }} ₽</span>
                </div>
                @if ($order->total_weight_grams)
                    <div class="row">
                        <span class="label">Общий вес</span>
                        <span class="value">{{ $totalWeight }}</span>
                    </div>
                @endif
            </section>
        </div>

        <section class="card">
            <h2 class="card-title">Состав заказа</h2>
            <div class="items">
                @foreach ($order->items as $item)
                    @php
                        $product = $item->product;
                        $productImagePath = $item->product_image_path
                            ?: $product?->images?->first()?->file_path;
                        $productUrl = $product?->is_active
                            ? route('catalog.show', $product->slug ?: $product->id)
                            : null;
                    @endphp

                    <article class="item">
                        <div class="product-thumb">
                            @if ($productImagePath)
                                <img
                                    src="{{ Storage::disk('public')->url($productImagePath) }}"
                                    alt="{{ $item->product_name }}"
                                    loading="lazy"
                                >
                            @else
                                Нет фото
                            @endif
                        </div>

                        <div class="item-body">
                            <div>
                                @if ($productUrl)
                                    <a class="product-link item-name" href="{{ $productUrl }}">{{ $item->product_name }}</a>
                                @else
                                    <div class="item-name">{{ $item->product_name }}</div>
                                @endif
                                <div class="item-meta">Артикул: {{ $item->product_article ?: '—' }}</div>
                            </div>

                            <div class="item-summary">
                                <span>{{ $item->quantity }} × {{ number_format((float) $item->discounted_unit_price, 2, ',', ' ') }} ₽</span>
                                <span class="line-total">Сумма: {{ number_format((float) $item->line_total, 2, ',', ' ') }} ₽</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </main>
@endsection
