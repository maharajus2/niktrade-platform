@extends('layouts.shop')

@section('title', 'Регистрация')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-auth.css') }}?v={{ filemtime(public_path('css/shop-auth.css')) }}">
@endpush

@section('content')
    <div class="nik-auth-page">
        <div class="nik-auth-layout">
            <aside class="nik-auth-aside">
                <h2>Создайте аккаунт НИКТРЕЙД</h2>
                <p>Регистрация занимает минуту и делает покупки удобнее.</p>
                <ul class="nik-auth-benefits">
                    <li>История и статусы заказов</li>
                    <li>Сохранённые адреса доставки</li>
                    <li>Быстрое оформление заказа</li>
                </ul>
            </aside>

            <form class="nik-auth-card" method="POST" action="{{ route('customer.register.store') }}">
                @csrf

                <h1 class="nik-auth-title">Регистрация</h1>
                <p class="nik-auth-subtitle">Заполните данные, чтобы создать аккаунт</p>

                <div class="nik-auth-row">
                    <label class="nik-auth-field">
                        <span class="nik-auth-label">Имя</span>
                        <input class="nik-auth-input" type="text" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required>
                        @error('first_name')
                            <span class="nik-auth-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="nik-auth-field">
                        <span class="nik-auth-label">Фамилия</span>
                        <input class="nik-auth-input" type="text" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name">
                        @error('last_name')
                            <span class="nik-auth-error">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <label class="nik-auth-field">
                    <span class="nik-auth-label">Email</span>
                    <input class="nik-auth-input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                    @error('email')
                        <span class="nik-auth-error">{{ $message }}</span>
                    @enderror
                </label>

                <label class="nik-auth-field">
                    <span class="nik-auth-label">Телефон</span>
                    <input class="nik-auth-input" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" required>
                    @error('phone')
                        <span class="nik-auth-error">{{ $message }}</span>
                    @enderror
                </label>

                <div class="nik-auth-row">
                    <label class="nik-auth-field">
                        <span class="nik-auth-label">Пароль</span>
                        <input class="nik-auth-input" type="password" name="password" autocomplete="new-password" required>
                        @error('password')
                            <span class="nik-auth-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="nik-auth-field">
                        <span class="nik-auth-label">Повторите пароль</span>
                        <input class="nik-auth-input" type="password" name="password_confirmation" autocomplete="new-password" required>
                    </label>
                </div>

                <button class="nik-auth-button" type="submit">Зарегистрироваться</button>

                <div class="nik-auth-switch">
                    Уже есть аккаунт?
                    <a href="{{ route('customer.login') }}">Войти</a>
                </div>
            </form>
        </div>
    </div>
@endsection
