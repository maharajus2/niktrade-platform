@extends('layouts.public')

@section('title', 'Заказ ' . $order->order_number)

@push('styles')
    <style>
        .account-page {
            width: min(1080px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 48px;
        }

        .account-nav,
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 22px;
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
            margin: 0 0 18px;
            font-size: 2rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 18px;
        }

        .card.full {
            grid-column: 1 / -1;
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
            gap: 10px;
        }

        .item {
            display: grid;
            grid-template-columns: 1.5fr repeat(4, minmax(0, 1fr));
            gap: 10px;
            border-top: 1px solid #f3f4f6;
            padding-top: 10px;
        }

        .item:first-child {
            border-top: 0;
            padding-top: 0;
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

            .grid,
            .item {
                grid-template-columns: 1fr;
            }

            .value {
                text-align: left;
            }
        }
    </style>
@endpush

@section('content')
    <main class="account-page">
        @include('customer-account.partials.nav')

        <h1 class="title">Заказ {{ $order->order_number }}</h1>

        @if (session('success'))
            <div class="message">{{ session('success') }}</div>
        @endif

        @error('order')
            <div class="message">{{ $message }}</div>
        @enderror

        <div class="actions">
            <a class="button" href="{{ route('customer.account.orders') }}">К списку заказов</a>

            @if ($order->canBeCancelledByCustomer())
                <form method="POST" action="{{ route('customer.orders.cancel', $order) }}" onsubmit="return confirm('Вы уверены, что хотите отменить заказ?')">
                    @csrf

                    <button class="danger-button" type="submit">Отменить заказ</button>
                </form>
            @endif
        </div>

        <div class="grid">
            <section class="card">
                <h2 class="card-title">Статусы</h2>
                <div class="row">
                    <span class="label">Заказ</span>
                    <span class="value">{{ \App\Models\Order::statusLabel($order->status) }}</span>
                </div>
                <div class="row">
                    <span class="label">Оплата</span>
                    <span class="value">{{ $order->payment_status }}</span>
                </div>
                <div class="row">
                    <span class="label">Доставка</span>
                    <span class="value">{{ $order->delivery_status }}</span>
                </div>
            </section>

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

            <section class="card full">
                <h2 class="card-title">Адрес доставки</h2>
                <div class="row">
                    <span class="label">Адрес</span>
                    <span class="value">
                        {{ collect([$order->postal_code, $order->region, $order->city, $order->street, $order->house, $order->building, $order->apartment])->filter()->implode(', ') ?: '—' }}
                    </span>
                </div>
                <div class="row">
                    <span class="label">Подъезд / этаж</span>
                    <span class="value">{{ collect([$order->entrance, $order->floor])->filter()->implode(', ') ?: '—' }}</span>
                </div>
                @if ($order->delivery_comment)
                    <div class="row">
                        <span class="label">Комментарий</span>
                        <span class="value">{{ $order->delivery_comment }}</span>
                    </div>
                @endif
            </section>

            <section class="card full">
                <h2 class="card-title">Состав заказа</h2>
                <div class="items">
                    @foreach ($order->items as $item)
                        <div class="item">
                            <div>
                                <div class="label">Товар</div>
                                <div class="value">{{ $item->product_name }}</div>
                            </div>
                            <div>
                                <div class="label">Артикул</div>
                                <div class="value">{{ $item->product_article ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="label">Кол-во</div>
                                <div class="value">{{ $item->quantity }}</div>
                            </div>
                            <div>
                                <div class="label">Цена</div>
                                <div class="value">{{ number_format((float) $item->discounted_unit_price, 2, ',', ' ') }} ₽</div>
                            </div>
                            <div>
                                <div class="label">Сумма</div>
                                <div class="value">{{ number_format((float) $item->line_total, 2, ',', ' ') }} ₽</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="card full">
                <h2 class="card-title">Итоги</h2>
                <div class="row">
                    <span class="label">Сумма товаров</span>
                    <span class="value">{{ number_format((float) $order->subtotal, 2, ',', ' ') }} ₽</span>
                </div>
                <div class="row">
                    <span class="label">Скидка</span>
                    <span class="value">{{ number_format((float) $order->discount_total, 2, ',', ' ') }} ₽</span>
                </div>
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
    </main>
@endsection
