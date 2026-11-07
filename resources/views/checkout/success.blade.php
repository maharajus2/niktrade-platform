@extends('layouts.shop')

@section('title', 'Заказ оформлен — НИКТРЕЙД')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-catalog.css') }}?v={{ filemtime(public_path('css/shop-catalog.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/shop-checkout.css') }}?v={{ filemtime(public_path('css/shop-checkout.css')) }}">
@endpush

@section('content')
    <div class="shop-success">
        <section class="shop-success-card">
            <h1>Спасибо за заказ!</h1>
            <p>Мы приняли ваш заказ и скоро свяжемся с вами для уточнения деталей.</p>
            <div class="shop-success-number">Заказ № {{ $order->order_number }}</div>
            <a class="shop-btn is-large" href="{{ route('catalog.index') }}">Вернуться в каталог</a>
        </section>
    </div>
@endsection
