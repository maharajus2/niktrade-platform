@extends('layouts.shop')

@section('title', 'Доступ к заказу запрещён')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-account.css') }}?v={{ filemtime(public_path('css/shop-account.css')) }}">
@endpush

@section('content')
    <div class="forbidden-page shop-account">
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
    </div>
@endsection
