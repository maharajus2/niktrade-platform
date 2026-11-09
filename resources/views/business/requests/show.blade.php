@extends('layouts.business')

@section('title', 'Заявка '.$businessRequest->number.' — НИКТРЕЙД')

@section('content')
    @php
        $money = fn ($value): string => number_format((float) $value, fmod((float) $value, 1) == 0.0 ? 0 : 2, ',', ' ') . ' ₽';
        $r = $businessRequest;
        $r->loadMissing('items.product');
        $hasInvoice = filled($r->invoice_path);
        $pct = rtrim(rtrim(number_format((float) $r->discount_percent, 2, ',', ''), '0'), ',');
    @endphp

    <section class="biz-page">
        <div class="biz-page-head">
            <div>
                <span class="biz-eyebrow"><a href="{{ route('business.requests.index') }}">← Мои заявки</a></span>
                <h1>Заявка {{ $r->number }}</h1>
            </div>
            <span class="biz-status is-large is-{{ $r->status->value }}">{{ $r->status->getLabel() }}</span>
        </div>

        <div class="biz-cart-layout">
            <div>
                <div class="biz-table-wrap">
                    <table class="biz-table">
                        <thead><tr><th>Товар</th><th class="num">Цена</th><th class="num">Кол-во</th><th class="num">Сумма</th></tr></thead>
                        <tbody>
                            @foreach ($r->items as $line)
                                <tr>
                                    <td>
                                        @if ($line->product && $line->product->is_active)
                                            <a href="{{ route('business.product', $line->product->slug ?: $line->product->id) }}">{{ $line->name }}</a>
                                        @else
                                            {{ $line->name }}
                                        @endif
                                        @if ($line->article)<div class="biz-row-meta">арт. {{ $line->article }}</div>@endif
                                    </td>
                                    <td class="num">{{ $money($line->unit_price) }}</td>
                                    <td class="num">{{ $line->quantity }}</td>
                                    <td class="num">{{ $money($line->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (filled($r->comment))
                    <div class="biz-card biz-block"><h2>Ваш комментарий</h2><p>{{ $r->comment }}</p></div>
                @endif
            </div>

            <aside class="biz-summary">
                <h2>Итого</h2>
                <dl>
                    <div><dt>Цены для бизнеса</dt><dd>{{ $money($r->subtotal) }}</dd></div>
                    @if ((float) $r->discount_percent > 0)
                        <div><dt>Скидка {{ $pct }}%</dt><dd>−{{ $money($r->subtotal - $r->total) }}</dd></div>
                    @endif
                    <div class="is-total"><dt>К оплате</dt><dd>{{ $money($r->total) }}</dd></div>
                </dl>
                <dl class="biz-meta">
                    <div><dt>Создана</dt><dd>{{ $r->created_at->format('d.m.Y H:i') }}</dd></div>
                    <div><dt>Менеджер</dt><dd>{{ $r->manager?->name ?? 'будет назначен' }}</dd></div>
                </dl>

                <h3>Счёт</h3>
                @if ($hasInvoice)
                    <p class="biz-note is-left">Счёт № {{ $r->invoice_number }}@if ($r->invoice_issued_at) от {{ $r->invoice_issued_at->format('d.m.Y') }}@endif</p>
                    <a class="biz-btn biz-submit" href="{{ route('business.requests.invoice', $r->number) }}">Скачать счёт (PDF)</a>
                @else
                    <p class="biz-note is-left">Счёт появится здесь, когда менеджер его выставит.</p>
                @endif
            </aside>
        </div>

        <div class="biz-card biz-block">
            <h2>История статусов</h2>
            <ol class="biz-timeline">
                @foreach ($r->statusLogs as $log)
                    <li>
                        <span class="biz-status is-{{ $log->to_status->value }}">{{ $log->to_status->getLabel() }}</span>
                        <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d.m.Y H:i') }}</time>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
@endsection
