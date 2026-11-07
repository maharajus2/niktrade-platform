@extends('layouts.shop')

@section('title', 'Подтверждение телефона')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}?v={{ filemtime(public_path('css/shop-account.css')) }}">
@endpush

@section('content')
    <div class="account-page shop-account">
        @include('customer-account.partials.nav')

        <h1 class="title">Подтверждение телефона</h1>

        <section class="card">
            <div class="info-grid">
                <div>
                    <div class="label">Телефон</div>
                    <div class="value">{{ $customer->phone ?: '—' }}</div>
                </div>

                <div>
                    <div class="label">Статус</div>
                    @if ($customer->hasVerifiedPhone())
                        <div class="status ok">Подтверждён</div>
                    @else
                        <div class="status pending">Не подтверждён</div>
                    @endif
                </div>
            </div>

            <div class="note">
                Откройте бота и нажмите Start. Затем вернитесь на сайт и запросите код.
            </div>

            <div class="form-row">
                @if ($telegramBotUsername)
                    <a class="neutral-button" href="https://t.me/{{ $telegramBotUsername }}" target="_blank" rel="noopener">
                        Открыть Telegram-бота
                    </a>
                @endif

                <form method="POST" action="{{ route('customer.account.phone-verification.request-code') }}">
                    @csrf
                    <button class="button" type="submit">Запросить код</button>
                </form>
            </div>

            @error('phone_verification')
                <div class="error">{{ $message }}</div>
            @enderror

            <form class="form-row" method="POST" action="{{ route('customer.account.phone-verification.confirm') }}">
                @csrf

                <div class="field">
                    <label class="label" for="code">Код из Telegram</label>
                    <input class="input" id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required>
                    @error('code')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <button class="button" type="submit">Подтвердить</button>
            </form>

            <div>
                <a class="neutral-button" href="{{ route('customer.account') }}">Отмена</a>
            </div>
        </section>
    </div>
@endsection
