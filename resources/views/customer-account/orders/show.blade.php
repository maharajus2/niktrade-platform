@extends('layouts.shop')

@section('title', 'Заказ ' . $order->order_number)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}?v={{ filemtime(public_path('css/shop-account.css')) }}">
@endpush

@section('content')
    <div class="account-page shop-account">
        @include('customer-account.partials.nav')

        <div class="page-header">
            <div class="title-row">
                <h1 class="title">Заказ {{ $order->order_number }}</h1>
                <span class="status-badge {{ $order->status === \App\Models\Order::STATUS_CANCELLED ? 'status-badge--danger' : '' }}">
                    {{ \App\Models\Order::statusLabel($order->status) }}
                </span>
                @if ($order->isArchived())
                    <span class="archive-badge">Архивный заказ</span>
                @endif
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

        @if ($order->isArchived())
            <div class="archive-note">Этот заказ завершён и находится в архиве.</div>
        @endif

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
                    <span class="label">{{ ($order->fulfillment_method ?? \App\Models\Order::FULFILLMENT_DELIVERY) === \App\Models\Order::FULFILLMENT_PICKUP ? 'Самовывоз' : 'Получение' }}</span>
                    <span class="value">{{ \App\Models\Order::fulfillmentStatusLabel($order->fulfillment_status, $order->fulfillment_method) }}</span>
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
    </div>
@endsection
