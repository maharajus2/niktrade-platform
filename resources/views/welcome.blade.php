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
            background: #0b1112;
            font-family: Inter, Arial, sans-serif;
            color: #0f172a;
            overflow-x: hidden;
        }

        .background {
            position: fixed;
            inset: 0;
            background-image: url('/images/homepage/niktrade-preview.png');
            background-size: cover;
            background-position: center;
            filter: blur(3px);
            transform: scale(1.05);
            opacity: 0.55;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.12), rgba(15, 23, 42, 0.55));
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
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(16, 185, 129, 0.15);
            box-shadow: 0 40px 80px rgba(15, 23, 42, 0.16);
            padding: 40px 48px;
            backdrop-filter: blur(14px);
        }

        .hero-top {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #166534;
            font-weight: 700;
            font-size: 0.95rem;
            border: 1px solid #bbf7d0;
        }

        .hero-title {
            margin-top: 24px;
            font-size: clamp(2.2rem, 4vw, 3.8rem);
            line-height: 1.02;
            letter-spacing: -0.03em;
            color: #09191f;
            max-width: 760px;
        }

        .hero-subtitle {
            margin-top: 20px;
            max-width: 680px;
            font-size: 1.05rem;
            line-height: 1.75;
            color: #334155;
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
            background: #16a34a;
            color: #ffffff;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            box-shadow: 0 18px 45px rgba(22, 163, 74, 0.22);
        }

        .hero-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 24px 54px rgba(22, 163, 74, 0.30);
        }

        .hero-note {
            margin-top: 22px;
            color: #475569;
            font-size: 0.98rem;
            line-height: 1.7;
            max-width: 680px;
        }

        @media (max-width: 820px) {
            .hero-card {
                padding: 28px 26px;
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
            <div class="hero-top">Сайт находится в разработке</div>

            <h1 class="hero-title">Пока мы готовим полноценный каталог, товары НИКТРЕЙД доступны на OZON.</h1>

            <p class="hero-subtitle">Ознакомьтесь с ассортиментом на OZON и оформите заказ уже сейчас. Мы работаем над удобным магазином НИКТРЕЙД прямо на сайте.</p>

            <div class="hero-actions">
                <a href="https://www.ozon.ru/seller/hermes-industry-zavod-proizvoditel/" target="_blank" rel="noopener noreferrer" class="hero-button">Перейти в каталог OZON</a>
            </div>

            <p class="hero-note">Это временная страница, пока мы готовим полный каталог и карточки товаров. Благодарим за терпение.</p>
        </div>
    </div>
</div>

</body>
</html>
