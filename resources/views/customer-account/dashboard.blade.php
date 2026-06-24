@extends('layouts.public')

@section('title', 'Личный кабинет')

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
        .action-link {
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

        .dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
            gap: 16px;
            align-items: start;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(17, 24, 39, 0.06);
        }

        .profile-card {
            display: grid;
            grid-template-columns: 72px minmax(0, 1fr);
            gap: 16px;
            align-items: start;
        }

        .avatar {
            display: grid;
            place-items: center;
            width: 72px;
            height: 72px;
            border-radius: 999px;
            background: #166534;
            color: #ffffff;
            font-size: 2rem;
            font-weight: 900;
        }

        .card-title {
            margin: 0 0 14px;
            font-size: 1.15rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .value {
            margin-top: 4px;
            font-weight: 900;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .stat-value {
            margin-top: 6px;
            color: #166534;
            font-size: 1.6rem;
            font-weight: 900;
        }

        .actions-card {
            margin-top: 16px;
        }

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .action-link.primary {
            background: #166534;
            color: #ffffff;
        }

        @media (max-width: 820px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .account-page {
                width: min(100% - 24px, 1080px);
            }

            .profile-card,
            .profile-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <main class="account-page">
        @include('customer-account.partials.nav')

        @php
            $fullName = trim($customer->first_name . ' ' . $customer->last_name) ?: 'Покупатель';
            $avatarLetter = mb_strtoupper(mb_substr($fullName, 0, 1));
        @endphp

        <h1 class="title">Личный кабинет</h1>

        <div class="dashboard-grid">
            <section class="card profile-card">
                <div class="avatar">{{ $avatarLetter }}</div>

                <div>
                    <h2 class="card-title">{{ $fullName }}</h2>

                    <div class="profile-grid">
                        <div>
                            <div class="label">Email</div>
                            <div class="value">{{ $customer->email ?: '—' }}</div>
                        </div>

                        <div>
                            <div class="label">Телефон</div>
                            <div class="value">{{ $customer->phone ?: '—' }}</div>
                        </div>

                        <div>
                            <div class="label">Регистрация</div>
                            <div class="value">{{ $customer->created_at?->format('d.m.Y') ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="stats-grid" aria-label="Статистика аккаунта">
                <div class="card">
                    <div class="label">Мои заказы</div>
                    <div class="stat-value">{{ $ordersCount }}</div>
                </div>

                <div class="card">
                    <div class="label">Адреса доставки</div>
                    <div class="stat-value">{{ $addressesCount }}</div>
                </div>

                <div class="card">
                    <div class="label">Потрачено</div>
                    <div class="stat-value">{{ number_format((float) $totalSpent, 2, ',', ' ') }} ₽</div>
                </div>

                <div class="card">
                    <div class="label">Общий вес заказов</div>
                    <div class="stat-value">{{ \App\Support\WeightFormatter::formatGrams((int) $totalOrderedWeightGrams) }}</div>
                </div>
            </section>
        </div>

        <section class="card actions-card">
            <h2 class="card-title">Быстрые действия</h2>

            <div class="quick-actions">
                <a class="action-link primary" href="{{ route('customer.account.orders') }}">Мои заказы</a>
                <a class="action-link" href="{{ route('customer.account.addresses') }}">Адреса доставки</a>
                <a class="action-link" href="{{ route('catalog.index') }}">Перейти в каталог</a>
            </div>
        </section>
    </main>
@endsection
