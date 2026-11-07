@extends('layouts.shop')

@section('title', 'Адреса доставки')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}?v={{ filemtime(public_path('css/shop-account.css')) }}">
@endpush

@section('content')
    <div class="account-page shop-account">
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
    </div>
@endsection
