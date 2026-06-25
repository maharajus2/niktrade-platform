@extends('layouts.public')

@section('title', 'Подтверждение телефона')

@push('styles')
    <style>
        .account-page {
            width: min(860px, calc(100% - 32px));
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
        .button,
        .neutral-button {
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

        .card {
            display: grid;
            gap: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(17, 24, 39, 0.06);
        }

        .info-grid {
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

        .status {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            min-height: 32px;
            border-radius: 999px;
            font-size: 0.9rem;
            font-weight: 900;
            padding: 6px 10px;
        }

        .status.ok {
            background: #dcfce7;
            color: #166534;
        }

        .status.pending {
            background: #fee2e2;
            color: #991b1b;
        }

        .note {
            border-radius: 10px;
            background: #f9fafb;
            color: #374151;
            font-weight: 700;
            padding: 14px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: end;
        }

        .field {
            display: grid;
            gap: 6px;
            min-width: min(260px, 100%);
        }

        .input {
            width: 100%;
            min-height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 9px 11px;
            font: inherit;
        }

        .button {
            background: #166534;
            color: #ffffff;
        }

        .neutral-button {
            background: #f3f4f6;
            color: #374151;
        }

        .error {
            color: #b91c1c;
            font-size: 0.85rem;
            font-weight: 700;
        }

        @media (max-width: 640px) {
            .account-page {
                width: min(100% - 24px, 860px);
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .form-row,
            .button,
            .neutral-button {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <main class="account-page">
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
    </main>
@endsection
