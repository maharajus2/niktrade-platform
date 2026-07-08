<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Никтрейд — сайт в разработке</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: #eef6ff;
            font-family: Inter, Arial, sans-serif;
            color: #0f1b33;
            overflow-x: hidden;
        }

        .background {
            position: fixed;
            inset: 0;
            background-image: url('/images/homepage/niktrade-preview.png');
            background-size: cover;
            background-position: top center;
            filter: blur(16px) saturate(1.08);
            transform: scale(1.08);
            opacity: 0.72;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background:
                linear-gradient(118deg, rgba(255, 255, 255, .92) 0%, rgba(255, 255, 255, .56) 42%, rgba(235, 246, 255, .80) 100%),
                radial-gradient(circle at 18% 12%, rgba(40, 125, 242, .18), transparent 28rem),
                radial-gradient(circle at 86% 78%, rgba(47, 159, 109, .14), transparent 26rem);
        }

        .overlay::after {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, .36);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .content {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 32px;
        }

        .hero {
            width: 100%;
            max-width: 1040px;
        }

        .hero-card {
            border-radius: 30px;
            position: relative;
            isolation: isolate;
            overflow: hidden;
            border: 1px solid rgba(211, 226, 246, .92);
            background:
                linear-gradient(145deg, rgba(255, 255, 255, .92), rgba(246, 251, 255, .80) 58%, rgba(239, 247, 255, .72));
            box-shadow:
                0 28px 76px rgba(39, 88, 145, .16),
                0 6px 18px rgba(39, 88, 145, .08),
                inset 0 1px 0 rgba(255, 255, 255, .96),
                inset 0 -1px 0 rgba(40, 125, 242, .10);
            padding: 42px 48px;
            backdrop-filter: blur(20px) saturate(1.28);
            -webkit-backdrop-filter: blur(20px) saturate(1.28);
        }

        .hero-card::before,
        .hero-card::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 0;
            border-radius: inherit;
            pointer-events: none;
        }

        .hero-card::before {
            background: linear-gradient(138deg, rgba(255, 255, 255, .78), rgba(255, 255, 255, .20) 38%, rgba(255, 255, 255, 0) 62%);
            opacity: .82;
        }

        .hero-card::after {
            box-shadow:
                inset 0 0 0 1px rgba(255, 255, 255, .72),
                inset 0 -22px 42px rgba(40, 125, 242, .06);
        }

        .hero-card > * {
            position: relative;
            z-index: 1;
        }

        .hero-brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .hero-brand img {
            width: min(210px, 56vw);
            height: auto;
            filter: drop-shadow(0 10px 18px rgba(39, 88, 145, .12));
        }

        .hero-top {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 11px 16px;
            border-radius: 999px;
            background: linear-gradient(145deg, rgba(232, 243, 255, .96), rgba(255, 255, 255, .76));
            color: #287df2;
            font-weight: 850;
            font-size: 0.95rem;
            border: 1px solid rgba(218, 231, 247, .94);
            box-shadow: 0 10px 24px rgba(40, 125, 242, .10), inset 0 1px 0 rgba(255, 255, 255, .92);
        }

        .hero-title {
            margin-top: 28px;
            font-size: clamp(2.15rem, 4vw, 3.7rem);
            line-height: 1.02;
            letter-spacing: 0;
            color: #0f1b33;
            max-width: 760px;
        }

        .hero-subtitle {
            margin-top: 20px;
            max-width: 680px;
            font-size: 1.05rem;
            line-height: 1.75;
            color: rgba(15, 27, 51, .68);
        }

        .hero-actions {
            margin-top: 32px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .hero-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 16px 30px;
            border-radius: 999px;
            background: linear-gradient(180deg, #5aa4ff 0%, #287df2 56%, #0b70f0 100%);
            color: #ffffff;
            font-weight: 850;
            font-size: 1rem;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            box-shadow:
                0 18px 38px rgba(40, 125, 242, .28),
                inset 0 1px 0 rgba(255, 255, 255, .46),
                inset 0 -1px 0 rgba(4, 76, 179, .24);
        }

        .hero-button:hover {
            transform: translateY(-1px);
            box-shadow:
                0 24px 52px rgba(40, 125, 242, .34),
                inset 0 1px 0 rgba(255, 255, 255, .48),
                inset 0 -1px 0 rgba(4, 76, 179, .24);
        }

        .hero-note {
            margin-top: 22px;
            color: rgba(15, 27, 51, .58);
            font-size: 0.98rem;
            line-height: 1.7;
            max-width: 680px;
        }

        .hero-preview-note {
            margin-top: 28px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(218, 231, 247, .92);
            border-radius: 999px;
            background: linear-gradient(145deg, rgba(255, 255, 255, .86), rgba(246, 251, 255, .72));
            padding: 10px 14px;
            color: rgba(15, 27, 51, .64);
            font-size: .9rem;
            font-weight: 760;
            box-shadow: 0 10px 24px rgba(39, 88, 145, .08), inset 0 1px 0 rgba(255, 255, 255, .92);
        }

        @media (max-width: 820px) {
            .hero-card {
                padding: 28px 26px;
            }

            .hero-brand {
                align-items: flex-start;
                flex-direction: column;
            }

            .hero-title {
                font-size: clamp(1.9rem, 7vw, 3rem);
            }

            .hero-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-button {
                width: 100%;
            }
        }

        @media (max-width: 560px) {
            .content {
                padding: 20px;
            }

            .hero-card {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>

<div class="background"></div>
<div class="overlay"></div>

<div class="content">
    <div class="hero">
        <div class="hero-card">
            <div class="hero-brand">
                <img src="{{ asset('images/logont.png') }}" alt="НИКТРЕЙД">
                <div class="hero-top">Сайт находится в разработке</div>
            </div>

            <h1 class="hero-title">Пока мы готовим полноценный каталог, товары НИКТРЕЙД доступны на OZON.</h1>

            <p class="hero-subtitle">Ознакомьтесь с ассортиментом на OZON и оформите заказ уже сейчас. Мы работаем над удобным магазином НИКТРЕЙД прямо на сайте.</p>

            <div class="hero-actions">
                <a href="https://www.ozon.ru/seller/hermes-industry-zavod-proizvoditel/" target="_blank" rel="noopener noreferrer" class="hero-button">Перейти в каталог OZON</a>
            </div>

            <p class="hero-note">Это временная страница, пока мы готовим полный каталог и карточки товаров. Благодарим за терпение.</p>
            <div class="hero-preview-note">На фоне — текущий дизайн будущего каталога НИКТРЕЙД</div>
        </div>
    </div>
</div>

</body>
</html>
