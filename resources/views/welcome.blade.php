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
            overflow: hidden;
            background: #111;
            font-family: Arial, sans-serif;
        }

        .background {
            position: fixed;
            inset: 0;

            background-image: url('/images/preprod-splash-v2.png');
            background-size: cover;
            background-position: center;

            filter: blur(8px);
            transform: scale(1.1);

            opacity: 0.6;
        }

        .content {
            position: relative;
            z-index: 10;

            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 20px;
        }

        .card {
            position: relative;

            max-width: 820px;
            width: 100%;
        }

        .card img {
            width: 100%;
            display: block;
            border-radius: 20px;

            box-shadow: 0 0 60px rgba(0,0,0,.5);
        }

        /*
         * Кликабельная область поверх кнопки
         * "Пустите! Я Айтишник!"
         */

        .admin-link {
            position: absolute;

            left: 34%;
            bottom: 20%;

            width: 32%;
            height: 7%;

            display: block;

            border-radius: 12px;
        }

        @media (max-width: 768px) {
            .admin-link {
                left: 29%;
                width: 42%;
                bottom: 10%;
                height: 8%;
            }
        }
    </style>
</head>
<body>

<div class="background"></div>

<div class="content">

    <div class="card">

        <img src="/images/preprod-splash-v2.png" alt="Никтрейд">

        <a href="/admin" class="admin-link" title="Вход в админку"></a>

    </div>

</div>

</body>
</html>