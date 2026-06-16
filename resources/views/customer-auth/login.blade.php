@extends('layouts.public')

@section('title', 'Вход')

@push('styles')
    <style>
        .auth-page {
            width: min(520px, calc(100% - 32px));
            margin: 0 auto;
            padding: 40px 0 56px;
        }

        .auth-card {
            display: grid;
            gap: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 22px;
        }

        .auth-title {
            margin: 0;
            font-size: 1.8rem;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .label {
            color: #4b5563;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font: inherit;
            padding: 10px 12px;
        }

        .checkbox {
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }

        .error {
            color: #b91c1c;
            font-size: 0.85rem;
        }

        .button {
            min-height: 42px;
            border: 0;
            border-radius: 8px;
            background: #166534;
            color: #ffffff;
            font: inherit;
            font-weight: 800;
            padding: 10px 16px;
            cursor: pointer;
        }

        .link {
            color: #166534;
            font-weight: 700;
        }
    </style>
@endpush

@section('content')
    <main class="auth-page">
        <form class="auth-card" method="POST" action="{{ route('customer.login.store') }}">
            @csrf

            <h1 class="auth-title">Вход</h1>

            <label class="field">
                <span class="label">Email</span>
                <input class="input" type="email" name="email" value="{{ old('email') }}" required>
                @error('email')
                    <span class="error">{{ $message }}</span>
                @enderror
            </label>

            <label class="field">
                <span class="label">Пароль</span>
                <input class="input" type="password" name="password" required>
                @error('password')
                    <span class="error">{{ $message }}</span>
                @enderror
            </label>

            <label class="checkbox">
                <input type="checkbox" name="remember" value="1">
                <span>Запомнить меня</span>
            </label>

            <button class="button" type="submit">Войти</button>

            <div>
                Нет аккаунта?
                <a class="link" href="{{ route('customer.register') }}">Зарегистрироваться</a>
            </div>
        </form>
    </main>
@endsection
