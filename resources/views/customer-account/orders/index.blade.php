@extends('layouts.public')

@section('title', 'Мои заказы')

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

        .account-nav__link,
        .account-nav__button,
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            border: 0;
            border-radius: 8px;
            background: #ffffff;
            color: #166534;
            font: inherit;
            font-weight: 800;
            padding: 9px 12px;
            text-decoration: none;
            cursor: pointer;
        }

        .title {
            margin: 0 0 18px;
            font-size: 2rem;
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            border: 1px solid #d1d5db;
            border-radius: 999px;
            background: #ffffff;
            color: #374151;
            font-weight: 800;
            padding: 8px 13px;
            text-decoration: none;
        }

        .tab--active {
            border-color: #166534;
            background: #ecfdf5;
            color: #166534;
        }

        .orders {
            display: grid;
            gap: 12px;
        }

        .order-row {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr 1fr auto;
            gap: 12px;
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 14px;
        }

        .label {
            color: #6b7280;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .value {
            margin-top: 3px;
            font-weight: 900;
        }

        .archive-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            border-radius: 999px;
            background: #f3f4f6;
            color: #4b5563;
            font-size: 0.78rem;
            font-weight: 900;
            margin-top: 7px;
            padding: 5px 9px;
        }

        .empty {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            color: #6b7280;
            padding: 24px;
            text-align: center;
        }

        .pagination {
            margin-top: 18px;
        }

        @media (max-width: 760px) {
            .account-page {
                width: min(100% - 24px, 1080px);
            }

            .order-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <main class="account-page">
        @include('customer-account.partials.nav')

        <h1 class="title">Мои заказы</h1>

        <nav class="tabs" aria-label="Фильтр заказов">
            <a class="tab {{ $activeTab === 'active' ? 'tab--active' : '' }}" href="{{ route('customer.account.orders', ['tab' => 'active']) }}">
                Активные ({{ $counts['active'] }})
            </a>
            <a class="tab {{ $activeTab === 'archive' ? 'tab--active' : '' }}" href="{{ route('customer.account.orders', ['tab' => 'archive']) }}">
                Архив ({{ $counts['archive'] }})
            </a>
            <a class="tab {{ $activeTab === 'all' ? 'tab--active' : '' }}" href="{{ route('customer.account.orders', ['tab' => 'all']) }}">
                Все ({{ $counts['all'] }})
            </a>
        </nav>

        @if (session('success'))
            <div class="empty">{{ session('success') }}</div>
        @endif

        @if ($orders->isNotEmpty())
            <section class="orders" aria-label="Список заказов">
                @foreach ($orders as $order)
                    <article class="order-row">
                        <div>
                            <div class="label">Номер</div>
                            <div class="value">{{ $order->order_number }}</div>
                            @if ($order->isArchived())
                                <span class="archive-badge">Архив</span>
                            @endif
                        </div>

                        <div>
                            <div class="label">Дата</div>
                            <div class="value">{{ $order->created_at?->format('d.m.Y H:i') }}</div>
                        </div>

                        <div>
                            <div class="label">Статус</div>
                            <div class="value">{{ \App\Models\Order::statusLabel($order->status) }}</div>
                        </div>

                        <div>
                            <div class="label">Сумма</div>
                            <div class="value">{{ number_format((float) $order->total, 2, ',', ' ') }} ₽</div>
                        </div>

                        <a class="button" href="{{ route('customer.account.orders.show', $order) }}">Подробнее</a>
                    </article>
                @endforeach
            </section>

            <div class="pagination">
                {{ $orders->links() }}
            </div>
        @else
            <div class="empty">
                {{ $activeTab === 'archive' ? 'В архиве пока нет заказов.' : 'У вас пока нет заказов.' }}
            </div>
        @endif
    </main>
@endsection
