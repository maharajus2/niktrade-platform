@extends('layouts.business')

@section('title', 'Кабинет компании — НИКТРЕЙД')

@section('content')
    <section class="biz-account">
        <div class="biz-account-head">
            <div>
                <span class="biz-eyebrow">Кабинет компании</span>
                <h1>{{ $contractor->company_name }}</h1>
            </div>
            <form method="POST" action="{{ route('business.logout') }}">
                @csrf
                <button class="biz-btn is-ghost" type="submit">Выйти</button>
            </form>
        </div>

        <div class="biz-account-grid">
            <article class="biz-card">
                <h2>Реквизиты</h2>
                <dl>
                    <div><dt>ИНН</dt><dd>{{ $contractor->inn }}</dd></div>
                    @if ($contractor->kpp)<div><dt>КПП</dt><dd>{{ $contractor->kpp }}</dd></div>@endif
                    @if ($contractor->ogrn)<div><dt>ОГРН</dt><dd>{{ $contractor->ogrn }}</dd></div>@endif
                    @if ($contractor->legal_address)<div><dt>Юридический адрес</dt><dd>{{ $contractor->legal_address }}</dd></div>@endif
                </dl>
            </article>

            <article class="biz-card">
                <h2>Контакты</h2>
                <dl>
                    <div><dt>Контактное лицо</dt><dd>{{ $contractor->contact_name }}</dd></div>
                    <div><dt>Телефон</dt><dd>{{ $contractor->phone }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $contractor->email }}</dd></div>
                </dl>
            </article>

            <article class="biz-card">
                <h2>Ваши условия</h2>
                <dl>
                    <div><dt>Скидка</dt><dd>{{ rtrim(rtrim(number_format((float) $contractor->discount_percent, 2, ',', ''), '0'), ',') }}%</dd></div>
                    <div><dt>Менеджер</dt><dd>{{ $contractor->manager?->name ?? 'будет назначен' }}</dd></div>
                    <div><dt>Минимальная заявка</dt><dd>от 5&nbsp;000&nbsp;₽</dd></div>
                </dl>
            </article>
        </div>

        <p class="biz-soon">Оформление заявок появится на следующем этапе.</p>
        <p><a class="biz-btn" href="{{ route('business.catalog') }}">Открыть каталог</a></p>
    </section>
@endsection
