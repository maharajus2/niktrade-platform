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

        .page-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            margin-bottom: 18px;
        }

        .account-nav__link,
        .account-nav__button,
        .button,
        .neutral-button,
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
        .account-nav__button,
        .neutral-button {
            background: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
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
            margin: 0;
            font-size: 2rem;
        }

        .card,
        .address {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 18px;
        }

        .address.is-default {
            border-color: #86efac;
            background: #f0fdf4;
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
            color: #111827;
            font-weight: 900;
        }

        .badge {
            width: fit-content;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 0.85rem;
            font-weight: 800;
            padding: 5px 9px;
            white-space: nowrap;
        }

        .address-lines {
            display: grid;
            gap: 5px;
            color: #374151;
            margin: 12px 0;
        }

        .muted {
            color: #6b7280;
        }

        .empty-state {
            display: grid;
            gap: 14px;
            justify-items: start;
            color: #4b5563;
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

        .create-card {
            margin-top: 16px;
        }

        @media (max-width: 640px) {
            .account-page {
                width: min(100% - 24px, 1080px);
            }

            .page-header {
                display: grid;
                gap: 12px;
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

        @php
            $isCreating = request()->boolean('create');
            $editingAddress = $addresses->firstWhere('id', (int) request('edit'));
        @endphp

        <div class="page-header">
            <h1 class="title">Адреса доставки</h1>

            @if (! $isCreating)
                <a class="button" href="{{ route('customer.account.addresses', ['create' => 1]) }}">
                    {{ $addresses->isEmpty() ? 'Добавить адрес' : 'Добавить новый адрес' }}
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="message">{{ session('success') }}</div>
        @endif

        <section class="addresses" aria-label="Список адресов доставки">
            @forelse ($addresses as $address)
                <article class="address {{ $address->is_default ? 'is-default' : '' }}">
                    <div class="address-header">
                        <div class="address-title">{{ $address->title ?: 'Адрес доставки' }}</div>

                        @if ($address->is_default)
                            <span class="badge">По умолчанию</span>
                        @endif
                    </div>

                    @if ($editingAddress?->id === $address->id)
                        <form class="form" method="POST" action="{{ route('customer.account.addresses.update', $address) }}">
                            @csrf
                            @method('PATCH')

                            @include('customer-account.addresses.partials.form-fields', [
                                'address' => $address,
                            ])

                            <div class="actions">
                                <button class="button" type="submit">Сохранить</button>
                                <a class="neutral-button" href="{{ route('customer.account.addresses') }}">Отмена</a>
                            </div>
                        </form>
                    @else
                        <div class="address-lines">
                            <div>
                                {{ collect([$address->postal_code, $address->region, $address->city])->filter()->implode(', ') ?: '—' }}
                            </div>
                            <div>
                                {{ collect([
                                    $address->street,
                                    $address->house,
                                    $address->building ? 'корп. ' . $address->building : null,
                                    $address->apartment ? 'кв. ' . $address->apartment : null,
                                ])->filter()->implode(', ') }}
                            </div>

                            @if ($address->entrance || $address->floor)
                                <div>
                                    {{ collect([
                                        $address->entrance ? 'Подъезд ' . $address->entrance : null,
                                        $address->floor ? 'этаж ' . $address->floor : null,
                                    ])->filter()->implode(' / ') }}
                                </div>
                            @endif

                            @if ($address->comment)
                                <div class="muted">{{ $address->comment }}</div>
                            @endif
                        </div>

                        <div class="actions">
                            <a class="neutral-button" href="{{ route('customer.account.addresses', ['edit' => $address->id]) }}">Изменить</a>

                            @if (! $address->is_default)
                                <form method="POST" action="{{ route('customer.account.addresses.update', $address) }}">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden" name="title" value="{{ $address->title }}">
                                    <input type="hidden" name="postal_code" value="{{ $address->postal_code }}">
                                    <input type="hidden" name="region" value="{{ $address->region }}">
                                    <input type="hidden" name="city" value="{{ $address->city }}">
                                    <input type="hidden" name="street" value="{{ $address->street }}">
                                    <input type="hidden" name="house" value="{{ $address->house }}">
                                    <input type="hidden" name="building" value="{{ $address->building }}">
                                    <input type="hidden" name="apartment" value="{{ $address->apartment }}">
                                    <input type="hidden" name="entrance" value="{{ $address->entrance }}">
                                    <input type="hidden" name="floor" value="{{ $address->floor }}">
                                    <input type="hidden" name="comment" value="{{ $address->comment }}">
                                    <input type="hidden" name="is_default" value="1">

                                    <button class="button" type="submit">Сделать основным</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('customer.account.addresses.destroy', $address) }}" onsubmit="return confirm('Удалить этот адрес?')">
                                @csrf
                                @method('DELETE')

                                <button class="danger-button" type="submit">Удалить</button>
                            </form>
                        </div>
                    @endif
                </article>
            @empty
                <div class="card empty-state">
                    <div>У вас пока нет адресов доставки.</div>

                    @if (! $isCreating)
                        <a class="button" href="{{ route('customer.account.addresses', ['create' => 1]) }}">Добавить адрес</a>
                    @endif
                </div>
            @endforelse
        </section>

        @if ($isCreating)
            <section class="card create-card">
                <h2 class="card-title">Новый адрес</h2>

                <form class="form" method="POST" action="{{ route('customer.account.addresses.store') }}">
                    @csrf

                    @include('customer-account.addresses.partials.form-fields', [
                        'address' => null,
                    ])

                    <div class="actions">
                        <button class="button" type="submit">Сохранить адрес</button>
                        <a class="neutral-button" href="{{ route('customer.account.addresses') }}">Отмена</a>
                    </div>
                </form>
            </section>
        @endif
    </main>
@endsection
