@extends('layouts.public')

@section('title', 'Заказ оформлен')

@push('styles')
    <style>
        .page {
            width: min(720px, calc(100% - 32px));
            margin: 0 auto;
            display: grid;
            place-items: center;
            padding: 48px 0;
        }

        .card {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            padding: 32px;
            text-align: center;
        }

        .title {
            margin: 0 0 12px;
            font-size: 2rem;
        }

        .text {
            margin: 0 0 20px;
            color: #4b5563;
            line-height: 1.6;
        }

        .number {
            display: inline-flex;
            border-radius: 999px;
            background: #ecfdf5;
            color: #166534;
            font-weight: 900;
            padding: 10px 16px;
        }

        .button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 42px;
            margin-top: 24px;
            border-radius: 8px;
            background: #166534;
            color: #ffffff;
            font-weight: 800;
            padding: 10px 16px;
            text-decoration: none;
        }
    </style>
@endpush

@section('content')
    <main class="page">
        <section class="card">
            <h1 class="title">Спасибо за заказ!</h1>
            <p class="text">Мы приняли ваш заказ и скоро свяжемся с вами для уточнения деталей.</p>
            <div class="number">Заказ № {{ $order->order_number }}</div>
            <br>
            <a class="button" href="{{ route('catalog.index') }}">Вернуться в каталог</a>
        </section>
    </main>
@endsection
