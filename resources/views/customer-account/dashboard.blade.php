@extends('layouts.shop')

@section('title', 'Личный кабинет')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}?v={{ filemtime(public_path('css/shop-account.css')) }}">
@endpush

@section('content')
    <div class="account-page shop-account">
        @include('customer-account.partials.nav')

        @php
            $fullName = trim($customer->first_name . ' ' . $customer->last_name) ?: 'Покупатель';
            $avatarLetter = mb_strtoupper(mb_substr($fullName, 0, 1));
            $telegramBotUsername = ltrim((string) config('services.telegram.bot_username'), '@');
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
                            <div class="label">Подтверждение телефона</div>
                            @if ($customer->hasVerifiedPhone())
                                <div class="telegram-status">Подтверждён</div>
                            @else
                                <div class="telegram-status pending">Не подтверждён</div>
                            @endif
                        </div>

                        <div>
                            <div class="label">Регистрация</div>
                            <div class="value">{{ $customer->created_at?->format('d.m.Y') ?: '—' }}</div>
                        </div>
                    </div>

                    <div class="telegram-box">
                        <div class="label">Telegram</div>

                        @if ($customer->hasVerifiedTelegram())
                            <div class="telegram-status">Telegram подключён</div>

                            @if ($customer->telegram_display_name)
                                <div class="telegram-muted">{{ $customer->telegram_display_name }}</div>
                            @endif

                            @if ($customer->telegram_verified_at)
                                <div class="telegram-muted">Подключён: {{ $customer->telegram_verified_at->format('d.m.Y H:i') }}</div>
                            @endif

                            @if ($telegramBotUsername)
                                <form method="POST" action="{{ route('customer.telegram.link') }}">
                                    @csrf

                                    <button class="telegram-delete" type="submit">Переподключить Telegram</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('customer.account.telegram.destroy') }}">
                                @csrf
                                @method('DELETE')

                                <button class="telegram-delete" type="submit">Отвязать Telegram</button>
                            </form>
                        @elseif ($telegramBotUsername)
                            <div class="telegram-muted">Откройте бот, чтобы подключить Telegram к аккаунту.</div>

                            <form method="POST" action="{{ route('customer.telegram.link') }}">
                                @csrf

                                <button class="action-link primary" type="submit">Подключить Telegram</button>
                            </form>
                        @else
                            <div class="telegram-muted">Telegram-подтверждение пока не настроено.</div>
                        @endif

                        @error('telegram')
                            <div class="error">{{ $message }}</div>
                        @enderror
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
                <a class="action-link primary" href="{{ route('customer.account.profile.edit') }}">Редактировать профиль</a>
                <a class="action-link" href="{{ route('customer.account.phone-verification') }}">Подтвердить телефон через Telegram</a>
                <a class="action-link primary" href="{{ route('customer.account.orders') }}">Мои заказы</a>
                <a class="action-link" href="{{ route('customer.account.addresses') }}">Адреса доставки</a>
                <a class="action-link" href="{{ route('catalog.index') }}">Перейти в каталог</a>
            </div>
        </section>
    </div>
@endsection
