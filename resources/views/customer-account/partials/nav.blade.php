<nav class="account-nav" aria-label="Навигация личного кабинета">
    <a class="account-nav__link" href="{{ route('customer.account') }}">Профиль</a>
    <a class="account-nav__link" href="{{ route('customer.account.orders') }}">Заказы</a>
    <a class="account-nav__link" href="{{ route('customer.account.addresses') }}">Адреса</a>

    <form method="POST" action="{{ route('customer.logout') }}">
        @csrf

        <button class="account-nav__button" type="submit">Выйти</button>
    </form>
</nav>
