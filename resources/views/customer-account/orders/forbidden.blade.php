@extends('layouts.public')

@section('title', 'Доступ к заказу запрещён')

@push('styles')
    <style>
        .forbidden-page {
            width: min(680px, calc(100% - 32px));
            margin: 0 auto;
            display: grid;
            place-items: center;
            min-height: calc(100vh - 64px);
            padding: 40px 0;
        }

        .forbidden-card {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 32px;
            text-align: center;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        .forbidden-icon {
            display: inline-grid;
            place-items: center;
            width: 56px;
            height: 56px;
            margin-bottom: 18px;
            border-radius: 50%;
            background: #ecfdf5;
            color: #166534;
            font-size: 1.7rem;
        }

        .forbidden-title {
            margin: 0 0 16px;
            font-size: 2rem;
            line-height: 1.15;
        }

        .forbidden-text {
            display: grid;
            gap: 10px;
            margin: 0 auto 24px;
            color: #4b5563;
            line-height: 1.6;
        }

        .forbidden-text p {
            margin: 0;
        }

        .forbidden-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }

        .primary-button,
        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            border-radius: 8px;
            padding: 10px 16px;
            font: inherit;
            font-weight: 800;
            text-decoration: none;
        }

        .primary-button {
            border: 0;
            background: #166534;
            color: #ffffff;
            cursor: pointer;
        }

        .secondary-button {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
        }

        @media (max-width: 520px) {
            .forbidden-card {
                padding: 24px;
            }

            .forbidden-title {
                font-size: 1.65rem;
            }

            .forbidden-actions,
            .primary-button,
            .secondary-button {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <main class="forbidden-page">
        <section class="forbidden-card" aria-labelledby="forbidden-title">
            <div class="forbidden-icon" aria-hidden="true">🔒</div>

            <h1 class="forbidden-title" id="forbidden-title">Доступ к заказу запрещён</h1>

            <div class="forbidden-text">
                <p>Похоже, вы открыли ссылку из Telegram.</p>
                <p>Этот заказ принадлежит другому аккаунту.</p>
                <p>Возможно, вы вошли не под той учётной записью, с которой оформляли заказ.</p>
            </div>

            <div class="forbidden-actions">
                <form method="POST" action="{{ route('customer.account.orders.switch-account', $order) }}">
                    @csrf

                    <button class="primary-button" type="submit">Войти в другую учётную запись</button>
                </form>

                <a class="secondary-button" href="{{ route('catalog.index') }}">Перейти в каталог</a>
            </div>
        </section>
    </main>
@endsection
