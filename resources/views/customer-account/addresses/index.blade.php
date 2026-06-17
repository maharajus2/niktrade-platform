@extends('layouts.public')

@section('title', 'Адреса доставки')

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
        .account-nav__button {
            background: #ffffff;
            color: #166534;
        }

        .button {
            background: #166534;
            color: #ffffff;
        }

        .danger-button {
            background: #fee2e2;
            color: #991b1b;
        }

        .title {
            margin: 0 0 18px;
            font-size: 2rem;
        }

        .layout {
            display: grid;
            grid-template-columns: 380px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .card,
        .address {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 18px;
        }

        .card-title {
            margin: 0 0 14px;
            font-size: 1.15rem;
        }

        .form {
            display: grid;
            gap: 12px;
        }

        .fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .field {
            display: grid;
            gap: 5px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .label {
            color: #6b7280;
            font-size: 0.86rem;
            font-weight: 700;
        }

        .input,
        .textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font: inherit;
            padding: 9px 10px;
        }

        .textarea {
            min-height: 80px;
            resize: vertical;
        }

        .checkbox {
            display: flex;
            gap: 8px;
            align-items: center;
            color: #374151;
            font-weight: 700;
        }

        .addresses {
            display: grid;
            gap: 14px;
        }

        .address-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .address-title {
            font-weight: 900;
        }

        .badge {
            border-radius: 999px;
            background: #ecfdf5;
            color: #166534;
            font-size: 0.85rem;
            font-weight: 800;
            padding: 5px 9px;
        }

        .message {
            border-radius: 8px;
            background: #ecfdf5;
            color: #166534;
            font-weight: 800;
            margin-bottom: 16px;
            padding: 12px;
        }

        .error {
            color: #b91c1c;
            font-size: 0.85rem;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .account-page {
                width: min(100% - 24px, 1080px);
            }

            .fields {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <main class="account-page">
        @include('customer-account.partials.nav')

        <h1 class="title">Адреса доставки</h1>

        @if (session('success'))
            <div class="message">{{ session('success') }}</div>
        @endif

        <div class="layout">
            <section class="card">
                <h2 class="card-title">Новый адрес</h2>

                <form class="form" method="POST" action="{{ route('customer.account.addresses.store') }}">
                    @csrf

                    @include('customer-account.addresses.partials.form-fields', [
                        'address' => null,
                    ])

                    <button class="button" type="submit">Сохранить адрес</button>
                </form>
            </section>

            <section class="addresses" aria-label="Список адресов доставки">
                @forelse ($addresses as $address)
                    <article class="address">
                        <div class="address-header">
                            <div class="address-title">{{ $address->title ?: 'Адрес доставки' }}</div>

                            @if ($address->is_default)
                                <span class="badge">По умолчанию</span>
                            @endif
                        </div>

                        <form class="form" method="POST" action="{{ route('customer.account.addresses.update', $address) }}">
                            @csrf
                            @method('PATCH')

                            @include('customer-account.addresses.partials.form-fields', [
                                'address' => $address,
                            ])

                            <div class="actions">
                                <button class="button" type="submit">Обновить</button>
                            </div>
                        </form>

                        <form class="actions" method="POST" action="{{ route('customer.account.addresses.destroy', $address) }}" onsubmit="return confirm('Удалить этот адрес?')">
                            @csrf
                            @method('DELETE')

                            <button class="danger-button" type="submit">Удалить</button>
                        </form>
                    </article>
                @empty
                    <div class="card">У вас пока нет сохранённых адресов.</div>
                @endforelse
            </section>
        </div>
    </main>
@endsection
