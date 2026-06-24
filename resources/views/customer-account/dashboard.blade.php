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
            grid-template-columns: 132px minmax(0, 1fr);
            gap: 20px;
            align-items: start;
        }

        .avatar-block {
            display: grid;
            gap: 10px;
            justify-items: center;
        }

        .avatar-upload {
            position: relative;
            display: block;
            width: 120px;
            height: 120px;
            cursor: pointer;
        }

        .avatar {
            overflow: hidden;
            display: grid;
            place-items: center;
            width: 120px;
            height: 120px;
            border-radius: 999px;
            background: #166534;
            color: #ffffff;
            font-size: 2.8rem;
            font-weight: 900;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-edit {
            position: absolute;
            right: 4px;
            bottom: 4px;
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border: 2px solid #ffffff;
            border-radius: 999px;
            background: #166534;
            color: #ffffff;
            font-size: 1rem;
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

        .avatar-input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .avatar-delete {
            border: 0;
            background: transparent;
            color: #6b7280;
            font: inherit;
            font-size: 0.9rem;
            font-weight: 800;
            padding: 0;
            cursor: pointer;
        }

        .error {
            color: #b91c1c;
            font-size: 0.85rem;
            font-weight: 700;
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

            .avatar-block {
                justify-items: start;
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
                <div class="avatar-block">
                    <form method="POST" action="{{ route('customer.account.avatar.update') }}" enctype="multipart/form-data">
                        @csrf

                        <label class="avatar-upload" aria-label="Загрузить аватар">
                            <span class="avatar">
                                @if ($customer->avatar_path)
                                    <img src="{{ Storage::disk('public')->url($customer->avatar_path) }}" alt="{{ $fullName }}">
                                @else
                                    {{ $avatarLetter }}
                                @endif
                            </span>
                            <span class="avatar-edit" aria-hidden="true">📷</span>
                            <input
                                class="avatar-input"
                                type="file"
                                name="avatar"
                                accept="image/jpeg,image/png,image/webp"
                                onchange="this.form.submit()"
                            >
                        </label>

                        @error('avatar')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </form>

                    @if ($customer->avatar_path)
                        <form method="POST" action="{{ route('customer.account.avatar.destroy') }}">
                            @csrf
                            @method('DELETE')

                            <button class="avatar-delete" type="submit">Удалить фото</button>
                        </form>
                    @endif
                </div>

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
