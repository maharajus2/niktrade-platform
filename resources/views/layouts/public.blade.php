@php
    $cartQuantity = 0;

    if (request()->hasSession()) {
        $cartQuantity = (int) \App\Models\CartItem::query()
            ->whereHas('cart', fn ($query) => $query
                ->where('session_id', request()->session()->getId())
                ->where('status', 'active'))
            ->sum('quantity');
    }
@endphp

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Niktrade')</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f8fafc;
            color: #111827;
            font-family: Inter, Arial, sans-serif;
        }

        a {
            color: inherit;
        }

        .public-header {
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .public-header__inner {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: center;
            min-height: 64px;
        }

        .public-header__brand,
        .public-header__cart {
            font-weight: 900;
            text-decoration: none;
        }

        .public-header__brand {
            color: #111827;
        }

        .public-header__nav {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .public-header__link,
        .public-header__cart,
        .public-header__logout {
            border-radius: 8px;
            padding: 9px 12px;
            text-decoration: none;
        }

        .public-header__link {
            color: #374151;
            font-weight: 700;
        }

        .public-header__logout {
            border: 0;
            background: transparent;
            color: #374151;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
        }

        .public-header__cart {
            background: #ecfdf5;
            color: #166534;
        }

        @media (max-width: 640px) {
            .public-header__inner {
                width: min(100% - 24px, 1180px);
                display: grid;
                padding: 12px 0;
            }

            .public-header__nav {
                justify-content: space-between;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <header class="public-header">
        <div class="public-header__inner">
            <a class="public-header__brand" href="{{ route('catalog.index') }}">Niktrade</a>

            <nav class="public-header__nav" aria-label="Основная навигация">
                <a class="public-header__link" href="{{ route('catalog.index') }}">Каталог</a>

                @if (\Illuminate\Support\Facades\Auth::guard('customer')->check())
                    <form method="POST" action="{{ route('customer.logout') }}">
                        @csrf

                        <button class="public-header__logout" type="submit">Выйти</button>
                    </form>
                @else
                    <a class="public-header__link" href="{{ route('customer.login') }}">Войти</a>
                    <a class="public-header__link" href="{{ route('customer.register') }}">Регистрация</a>
                @endif

                <a class="public-header__cart" href="{{ route('cart.index') }}">🛒 Корзина ({{ $cartQuantity }})</a>
            </nav>
        </div>
    </header>

    @yield('content')
</body>
</html>
