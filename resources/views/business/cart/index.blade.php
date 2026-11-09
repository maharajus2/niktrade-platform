@extends('layouts.business')

@section('title', 'Заявка — НИКТРЕЙД')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/shop-catalog.css') }}?v={{ filemtime(public_path('css/shop-catalog.css')) }}">
@endpush

@section('content')
    @php
        $money = fn ($value): string => number_format((float) $value, fmod((float) $value, 1) == 0.0 ? 0 : 2, ',', ' ') . ' ₽';
        $icon = fn (string $name, string $class = ''): string => \App\Support\ShopIcons::svg($name, $class);
        $hasItems = $summary['items']->isNotEmpty();
        (new \Illuminate\Database\Eloquent\Collection($summary['items']->pluck('product')->all()))->loadMissing('images');
        $discount = (float) auth('business')->user()->discount_percent;
        $otherErrors = collect($errors->getBag('default')->toArray())->except(['product_id', 'quantity'])->flatten();
    @endphp

    <section class="biz-page">
        <div class="biz-page-head">
            <div>
                <span class="biz-eyebrow">Текущая заявка</span>
                <h1>Заявка на поставку</h1>
            </div>
            <a class="biz-btn is-ghost" href="{{ route('business.catalog') }}">Продолжить подбор</a>
        </div>

        @if ($otherErrors->isNotEmpty())
            <div class="biz-flash is-error" role="alert">
                @foreach ($otherErrors as $message)<div>{{ $message }}</div>@endforeach
            </div>
        @endif

        @if (! $hasItems)
            <div class="biz-empty">
                <h2>В заявке пока ничего нет</h2>
                <p>Добавьте товары из каталога. Минимальная сумма заявки — {{ $money($summary['min_amount']) }}.</p>
                <a class="biz-btn" href="{{ route('business.catalog') }}">Перейти в каталог</a>
            </div>
        @else
            <div class="biz-cart-layout">
                <div class="biz-cart-list" aria-label="Товары в заявке">
                    @foreach ($summary['items'] as $row)
                        @php $product = $row['product']; $photo = $product->images->first()?->file_path ?? null; @endphp
                        <article class="biz-row-item @if (! $row['available']) is-unavailable @endif" data-biz-item data-biz-row data-product-id="{{ $product->id }}" data-qty="{{ $row['quantity'] }}" data-add-url="{{ route('business.cart.add') }}" data-update-url="{{ route('business.cart.update', $product->id) }}">
                            <a class="biz-row-img" href="{{ route('business.product', $product->slug ?: $product->id) }}" tabindex="-1" aria-hidden="true">
                                <img src="{{ $photo ? \Illuminate\Support\Facades\Storage::disk('public')->url($photo) : asset('images/logo-icon.png') }}" alt="" loading="lazy">
                            </a>
                            <div class="biz-row-main">
                                <a class="biz-row-name" href="{{ route('business.product', $product->slug ?: $product->id) }}">{{ $product->name }}</a>
                                <div class="biz-row-meta">
                                    @if ($product->article)арт. {{ $product->article }} · @endif
                                    <span data-biz-unit-price>@if ($row['unit_price'] !== null){{ $money($row['unit_price']) }} за шт.@else цена по запросу @endif</span>
                                </div>
                                @unless ($row['available'])
                                    <div class="biz-row-warn">Товар временно недоступен для заказа. Удалите его, чтобы отправить заявку.</div>
                                @endunless
                            </div>
                            <div class="v3-qty" role="group" aria-label="Количество" data-biz-qty>
                                <button class="v3-qty-btn" type="button" data-biz-dec aria-label="Уменьшить количество">−</button>
                                <span class="v3-qty-num" aria-live="polite" data-biz-num>{{ $row['quantity'] }}</span>
                                <button class="v3-qty-btn" type="button" data-biz-inc aria-label="Увеличить количество">+</button>
                            </div>
                            <div class="biz-row-sum" data-biz-line>{{ $row['line_total'] !== null ? $money($row['line_total']) : '—' }}</div>
                            <button class="biz-row-remove" type="button" data-biz-remove aria-label="Удалить из заявки">×</button>
                        </article>
                    @endforeach
                </div>

                <aside class="biz-summary" aria-label="Итого">
                    <h2>Итого</h2>
                    <dl>
                        <div><dt>Цены для бизнеса</dt><dd data-biz-subtotal>{{ $money($summary['subtotal']) }}</dd></div>
                        @if ($discount > 0)
                            <div><dt>Ваша скидка {{ rtrim(rtrim(number_format($discount, 2, ',', ''), '0'), ',') }}%</dt><dd data-biz-discount>−{{ $money($summary['subtotal'] - $summary['total']) }}</dd></div>
                        @endif
                        <div class="is-total"><dt>К оплате по счёту</dt><dd data-biz-cart-total>{{ $money($summary['total']) }}</dd></div>
                    </dl>
                    <p class="biz-min-note @if ($summary['can_submit']) is-ok @endif" data-biz-min-note>
                        {{ $summary['can_submit'] ? 'Можно отправлять заявку' : ($summary['missing'] > 0 ? 'До минимума ещё '.$money($summary['missing']) : 'Проверьте товары в заявке') }}
                    </p>
                    <form method="POST" action="{{ route('business.requests.store') }}">
                        @csrf
                        <label class="biz-label" for="biz-comment">Комментарий к заявке</label>
                        <textarea id="biz-comment" class="shop-input biz-textarea" name="comment" rows="3" maxlength="1000" placeholder="Адрес доставки, пожелания по срокам">{{ old('comment') }}</textarea>
                        <button class="biz-btn biz-submit" type="submit" data-biz-submit @disabled(! $summary['can_submit'])>Отправить заявку</button>
                    </form>
                    <p class="biz-note">Заявка не является заказом с оплатой. Менеджер свяжется с вами и выставит счёт.</p>
                </aside>
            </div>
        @endif
    </section>
@endsection
