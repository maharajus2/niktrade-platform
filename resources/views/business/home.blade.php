@extends('layouts.business')

@section('title', 'Портал для бизнеса — НИКТРЕЙД')

@php
    $registerErrors = $errors->getBag('register');
    $loginErrors = $errors->getBag('login');
    $openRegister = $registerErrors->any();
@endphp

@section('content')
    <section class="biz-hero">
        <div class="biz-hero-copy">
            <span class="biz-eyebrow">Портал корпоративных клиентов</span>
            <h1>Профессиональная химия <em>для вашего бизнеса</em></h1>
            <p>Заявки по общему прайсу, персональные условия для вашей компании и счёт от менеджера. Для клининга, HoReCa, автомоек, складов и производств.</p>
            <ul class="biz-points">
                <li>Общий прайс сразу после регистрации</li>
                <li>Персональная скидка для вашей компании</li>
                <li>Заявка вместо корзины, счёт выставляет менеджер</li>
                <li>Минимальная заявка от 5&nbsp;000&nbsp;₽</li>
            </ul>
        </div>

        <div class="biz-auth" id="auth" data-biz-auth data-open="{{ $openRegister ? 'register' : 'login' }}">
            <div class="biz-tabs" role="tablist">
                <button type="button" role="tab" id="bizTabLogin" aria-controls="bizPanelLogin" data-biz-tab="login">Вход</button>
                <button type="button" role="tab" id="bizTabRegister" aria-controls="bizPanelRegister" data-biz-tab="register">Регистрация компании</button>
            </div>

            <form class="biz-form" id="bizPanelLogin" role="tabpanel" aria-labelledby="bizTabLogin" method="POST" action="{{ route('business.login') }}" data-biz-panel="login">
                @csrf
                <label class="biz-field">
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                </label>
                @if ($loginErrors->has('email'))<p class="biz-error">{{ $loginErrors->first('email') }}</p>@endif
                <label class="biz-field">
                    <span>Пароль</span>
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>
                @if ($loginErrors->has('password'))<p class="biz-error">{{ $loginErrors->first('password') }}</p>@endif
                <label class="biz-check"><input type="checkbox" name="remember" value="1"> Запомнить меня</label>
                <button class="biz-btn" type="submit">Войти в портал</button>
                <p class="biz-note">Нет аккаунта? <button class="biz-link" type="button" data-biz-tab="register">Зарегистрировать компанию</button></p>
            </form>

            <form class="biz-form" id="bizPanelRegister" role="tabpanel" aria-labelledby="bizTabRegister" method="POST" action="{{ route('business.register') }}" data-biz-panel="register" hidden>
                @csrf
                <label class="biz-field">
                    <span>Название компании</span>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" maxlength="255" autocomplete="organization" placeholder="ООО «Ромашка» или ИП Иванов И. И." required>
                </label>
                @if ($registerErrors->has('company_name'))<p class="biz-error">{{ $registerErrors->first('company_name') }}</p>@endif

                <div class="biz-row">
                    <label class="biz-field">
                        <span>ИНН</span>
                        <input type="text" name="inn" value="{{ old('inn') }}" inputmode="numeric" maxlength="12" autocomplete="off" placeholder="10 или 12 цифр" data-biz-inn required>
                    </label>
                    <label class="biz-field" data-biz-kpp-field>
                        <span>КПП</span>
                        <input type="text" name="kpp" value="{{ old('kpp') }}" inputmode="text" maxlength="9" autocomplete="off" placeholder="9 знаков" data-biz-kpp>
                    </label>
                </div>
                @if ($registerErrors->has('inn'))<p class="biz-error">{{ $registerErrors->first('inn') }}</p>@endif
                @if ($registerErrors->has('kpp'))<p class="biz-error">{{ $registerErrors->first('kpp') }}</p>@endif
                <p class="biz-hint" data-biz-kpp-hint>У индивидуальных предпринимателей КПП нет: для ИНН из 12 цифр поле не нужно.</p>

                <label class="biz-field">
                    <span>Контактное лицо</span>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" maxlength="255" autocomplete="name" required>
                </label>
                @if ($registerErrors->has('contact_name'))<p class="biz-error">{{ $registerErrors->first('contact_name') }}</p>@endif

                <div class="biz-row">
                    <label class="biz-field">
                        <span>Телефон</span>
                        <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="50" autocomplete="tel" required>
                    </label>
                    <label class="biz-field">
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                    </label>
                </div>
                @if ($registerErrors->has('phone'))<p class="biz-error">{{ $registerErrors->first('phone') }}</p>@endif
                @if ($registerErrors->has('email'))<p class="biz-error">{{ $registerErrors->first('email') }}</p>@endif

                <div class="biz-row">
                    <label class="biz-field">
                        <span>Пароль</span>
                        <input type="password" name="password" minlength="8" autocomplete="new-password" required>
                    </label>
                    <label class="biz-field">
                        <span>Повторите пароль</span>
                        <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                    </label>
                </div>
                @if ($registerErrors->has('password'))<p class="biz-error">{{ $registerErrors->first('password') }}</p>@endif

                <button class="biz-btn" type="submit">Зарегистрировать компанию</button>
                <p class="biz-note">Уже есть аккаунт? <button class="biz-link" type="button" data-biz-tab="login">Войти</button></p>
            </form>
        </div>
    </section>

    <section class="biz-section" id="how">
        <h2>Как это работает</h2>
        <ol class="biz-steps">
            <li><span class="n">1</span><strong>Регистрация компании</strong><p>Укажите ИНН и КПП, контакты и создайте пароль.</p></li>
            <li><span class="n">2</span><strong>Заявка из каталога</strong><p>Соберите нужные позиции по общему прайсу, с вашей скидкой, если она есть.</p></li>
            <li><span class="n">3</span><strong>Счёт от менеджера</strong><p>Менеджер проверит заявку и выставит счёт на оплату.</p></li>
            <li><span class="n">4</span><strong>Оплата и отгрузка</strong><p>После оплаты мы отгружаем заказ. Статус видно в кабинете.</p></li>
        </ol>
    </section>

    <section class="biz-section" id="advantages">
        <h2>Преимущества</h2>
        <div class="biz-cards">
            <article><h3>Общий прайс сразу</h3><p>Доступ к ценам открывается после регистрации, без ожидания подтверждения.</p></article>
            <article><h3>Персональная скидка</h3><p>Менеджер может задать вашей компании скидку на весь ассортимент.</p></article>
            <article><h3>Заявка и счёт</h3><p>Никакой онлайн-оплаты: оформляете заявку, менеджер выставляет счёт.</p></article>
            <article><h3>Профессиональные линейки</h3><p>Средства HIBERG и ARVETERA для ежедневной работы бизнеса.</p></article>
        </div>
    </section>

    <section class="biz-section" id="terms">
        <h2>Условия</h2>
        <dl class="biz-terms">
            <div><dt>Минимальная заявка</dt><dd>от 5&nbsp;000&nbsp;₽</dd></div>
            <div><dt>Оплата</dt><dd>по счёту, который выставляет менеджер</dd></div>
            <div><dt>Цены</dt><dd>общий прайс, персональная скидка по договорённости</dd></div>
            <div><dt>Доступ</dt><dd>только для зарегистрированных компаний</dd></div>
        </dl>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    const box = document.querySelector('[data-biz-auth]');
    if (!box) return;
    const tabs = box.querySelectorAll('[role="tab"]');
    const panels = box.querySelectorAll('[data-biz-panel]');
    const show = (name, focus) => {
        tabs.forEach((t) => { const on = t.dataset.bizTab === name; t.classList.toggle('is-active', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
        panels.forEach((p) => { p.hidden = p.dataset.bizPanel !== name; });
        if (focus) box.querySelector('[data-biz-panel="' + name + '"] input')?.focus();
    };
    box.addEventListener('click', (e) => {
        const t = e.target.closest('[data-biz-tab]');
        if (t) show(t.dataset.bizTab, true);
    });
    // Переключение вкладок стрелками, как принято для tablist.
    const tabList = Array.from(tabs);
    tabList.forEach((t) => {
        t.addEventListener('keydown', (e) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
            e.preventDefault();
            const i = tabList.indexOf(t);
            const next = e.key === 'Home' ? 0 : e.key === 'End' ? tabList.length - 1 : (i + (e.key === 'ArrowRight' ? 1 : -1) + tabList.length) % tabList.length;
            show(tabList[next].dataset.bizTab, false);
            tabList[next].focus();
        });
    });
    show(box.dataset.open || 'login', false);
    if (box.dataset.open === 'register' || location.hash === '#auth') box.scrollIntoView({ block: 'center' });

    // КПП нужен только юрлицам (ИНН из 10 цифр).
    const inn = box.querySelector('[data-biz-inn]');
    const kppField = box.querySelector('[data-biz-kpp-field]');
    const kpp = box.querySelector('[data-biz-kpp]');
    const hint = box.querySelector('[data-biz-kpp-hint]');
    const syncKpp = () => {
        const sole = (inn?.value || '').replace(/\D/g, '').length === 12;
        if (kppField) kppField.hidden = sole;
        if (kpp) { kpp.disabled = sole; kpp.required = !sole; }
        if (hint) hint.hidden = !sole;
    };
    inn?.addEventListener('input', () => { inn.value = inn.value.replace(/\D/g, '').slice(0, 12); syncKpp(); });
    kpp?.addEventListener('input', () => { kpp.value = kpp.value.toUpperCase().replace(/[^0-9A-Z]/g, '').slice(0, 9); });
    syncKpp();
})();
</script>
@endpush
