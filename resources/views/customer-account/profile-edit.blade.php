@extends('layouts.shop')

@section('title', 'Редактировать профиль')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}?v={{ filemtime(public_path('css/shop-account.css')) }}">
@endpush

@section('content')
    <div class="account-page shop-account">
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
    </div>
@endsection
