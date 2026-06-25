@extends('layouts.public')

@section('title', 'Редактировать профиль')

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
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(17, 24, 39, 0.06);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field.full,
        .checkbox-field,
        .form-actions {
            grid-column: 1 / -1;
        }

        .label {
            display: block;
            color: #374151;
            font-size: 0.9rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .input {
            width: 100%;
            min-height: 42px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 9px 11px;
            font: inherit;
        }

        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #374151;
            font-weight: 800;
        }

        .error {
            margin-top: 5px;
            color: #b91c1c;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 4px;
        }

        .button {
            background: #166534;
            color: #ffffff;
        }

        .neutral-button {
            background: #f3f4f6;
            color: #374151;
        }

        @media (max-width: 640px) {
            .account-page {
                width: min(100% - 24px, 860px);
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                justify-content: stretch;
                flex-direction: column-reverse;
            }
        }
    </style>
@endpush

@section('content')
    <main class="account-page">
        @include('customer-account.partials.nav')

        <h1 class="title">Редактировать профиль</h1>

        <form class="card form-grid" method="POST" action="{{ route('customer.account.profile.update') }}">
            @csrf
            @method('PATCH')

            <div class="field">
                <label class="label" for="first_name">Имя</label>
                <input class="input" id="first_name" type="text" name="first_name" value="{{ old('first_name', $customer->first_name) }}" required>
                @error('first_name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="last_name">Фамилия</label>
                <input class="input" id="last_name" type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}">
                @error('last_name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email', $customer->email) }}" required>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="phone">Телефон</label>
                <input class="input" id="phone" type="text" name="phone" value="{{ old('phone', $customer->phone) }}" required>
                @error('phone')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field full">
                <label class="label" for="birthday">Дата рождения</label>
                <input class="input" id="birthday" type="date" name="birthday" value="{{ old('birthday', $customer->birthday?->format('Y-m-d')) }}">
                @error('birthday')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <label class="checkbox-field">
                <input type="checkbox" name="accepts_marketing" value="1" @checked(old('accepts_marketing', $customer->accepts_marketing))>
                Получать маркетинговые рассылки
            </label>

            <div class="form-actions">
                <a class="neutral-button" href="{{ route('customer.account') }}">Отмена</a>
                <button class="button" type="submit">Сохранить</button>
            </div>
        </form>
    </main>
@endsection
