@extends('layouts.public')

@section('title', 'Личный кабинет')

@push('styles')
    <style>
        .account-page {
            width: min(960px, calc(100% - 32px));
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
        .card-link {
            display: inline-flex;
            align-items: center;
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

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 20px;
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

        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        @media (max-width: 640px) {
            .account-page {
                width: min(100% - 24px, 960px);
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <main class="account-page">
        @include('customer-account.partials.nav')

        <h1 class="title">Личный кабинет</h1>

        <section class="card">
            <div class="profile-grid">
                <div>
                    <div class="label">Покупатель</div>
                    <div class="value">{{ trim($customer->first_name . ' ' . $customer->last_name) }}</div>
                </div>

                <div>
                    <div class="label">Email</div>
                    <div class="value">{{ $customer->email ?: '—' }}</div>
                </div>

                <div>
                    <div class="label">Телефон</div>
                    <div class="value">{{ $customer->phone ?: '—' }}</div>
                </div>

                @if ($customer->is_quick_registered)
                    <div>
                        <div class="label">Регистрация</div>
                        <div class="value">Быстрая регистрация при оформлении заказа</div>
                    </div>
                @endif
            </div>

            <div class="links">
                <a class="card-link" href="{{ route('customer.account.orders') }}">Мои заказы ({{ $ordersCount }})</a>
                <a class="card-link" href="{{ route('customer.account.addresses') }}">Адреса доставки ({{ $addressesCount }})</a>
            </div>
        </section>
    </main>
@endsection
