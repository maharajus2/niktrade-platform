@extends('layouts.business')

@section('title', 'Мои заявки — НИКТРЕЙД')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-catalog.css') }}?v={{ filemtime(public_path('css/shop-catalog.css')) }}">
@endpush

@section('content')
    @php $money = fn ($value): string => number_format((float) $value, fmod((float) $value, 1) == 0.0 ? 0 : 2, ',', ' ') . ' ₽'; @endphp

    <section class="biz-page">
        <div class="biz-page-head">
            <div>
                <span class="biz-eyebrow">Кабинет компании</span>
                <h1>Мои заявки</h1>
            </div>
            <a class="biz-btn is-ghost" href="{{ route('business.catalog') }}">В каталог</a>
        </div>

        @if ($requests->isEmpty())
            <div class="biz-empty">
                <h2>Заявок пока нет</h2>
                <p>Соберите заявку в каталоге и отправьте её менеджеру.</p>
                <a class="biz-btn" href="{{ route('business.catalog') }}">Перейти в каталог</a>
            </div>
        @else
            <div class="biz-req-list">
                @foreach ($requests as $item)
                    <a class="biz-req" href="{{ route('business.requests.show', $item->number) }}">
                        <span class="biz-req-num">{{ $item->number }}</span>
                        <span class="biz-req-date">{{ $item->created_at->format('d.m.Y H:i') }}</span>
                        <span class="biz-status is-{{ $item->status->value }}">{{ $item->status->getLabel() }}</span>
                        <span class="biz-req-count">{{ $item->items_count }} шт.</span>
                        <strong class="biz-req-total">{{ $money($item->total) }}</strong>
                    </a>
                @endforeach
            </div>
            <div class="shop-pagination">{{ $requests->links('pagination.catalog', ['noun' => 'заявок']) }}</div>
        @endif
    </section>
@endsection
