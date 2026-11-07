@extends('layouts.shop')

@section('title', 'Мои заказы')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-catalog.css') }}?v={{ filemtime(public_path('css/shop-catalog.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}?v={{ filemtime(public_path('css/shop-account.css')) }}">
@endpush

@section('content')
    <div class="account-page shop-account">
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
                {{ $orders->links('pagination.catalog') }}
            </div>
        @else
            <div class="empty">
                {{ $activeTab === 'archive' ? 'В архиве пока нет заказов.' : 'У вас пока нет заказов.' }}
            </div>
        @endif
    </div>
@endsection
