@extends('catalog2.index')

@section('title', 'Вход')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth-page.css') }}?v={{ filemtime(public_path('css/auth-page.css')) }}">
@endpush

@section('catalog_results')
    <main class="nik-auth-page">
        <div class="nik-auth-layout">
            <aside class="nik-auth-aside nik-catalog2-glass">
                <h2>Личный кабинет НИКТРЕЙД</h2>
                <p>Следите за заказами, сохраняйте адреса доставки и оформляйте покупки быстрее.</p>
                <ul class="nik-auth-benefits">
                    <li>История и статусы заказов</li>
                    <li>Сохранённые адреса доставки</li>
                    <li>Быстрое оформление заказа</li>
                </ul>
            </aside>

            <form class="nik-auth-card nik-catalog2-glass" method="POST" action="{{ route('customer.login.store') }}">
                @csrf

                <h1 class="nik-auth-title">Вход</h1>
                <p class="nik-auth-subtitle">Войдите, чтобы продолжить покупки</p>

                <label class="nik-auth-field">
                    <span class="nik-auth-label">Email</span>
                    <input class="nik-auth-input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                    @error('email')
                        <span class="nik-auth-error">{{ $message }}</span>
                    @enderror
                </label>

                <label class="nik-auth-field">
                    <span class="nik-auth-label">Пароль</span>
                    <input class="nik-auth-input" type="password" name="password" autocomplete="current-password" required>
                    @error('password')
                        <span class="nik-auth-error">{{ $message }}</span>
                    @enderror
                </label>

                <label class="nik-auth-checkbox">
                    <input type="checkbox" name="remember" value="1">
                    <span>Запомнить меня</span>
                </label>

                <button class="nik-auth-button" type="submit">Войти</button>

                <div class="nik-auth-switch">
                    Нет аккаунта?
                    <a href="{{ route('customer.register') }}">Зарегистрироваться</a>
                </div>
            </form>
        </div>
    </main>
@endsection
