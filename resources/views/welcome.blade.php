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
            font-family: Arial, sans-serif;
            background: #111;
        }

        .background {
            position: fixed;
            inset: 0;

            background:
                linear-gradient(
                    rgba(0,0,0,.55),
                    rgba(0,0,0,.55)
                ),
                url('/images/preprod-splash.png');

            background-size: cover;
            background-position: center;
            filter: blur(8px);
            transform: scale(1.1);
        }

        .content {
            position: relative;
            z-index: 10;

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 20px;
        }

        .card {
            max-width: 900px;
            width: 100%;

            background: rgba(20,20,20,.75);
            backdrop-filter: blur(12px);

            border-radius: 24px;

            padding: 40px;

            text-align: center;

            box-shadow: 0 0 50px rgba(0,0,0,.4);
        }

        .card img {
            max-width: 100%;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        h1 {
            color: white;
            font-size: 42px;
            margin-bottom: 15px;
        }

        p {
            color: #d5d5d5;
            font-size: 20px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;

            background: #f59e0b;
            color: #111;

            padding: 16px 32px;

            border-radius: 12px;

            text-decoration: none;
            font-weight: bold;
            font-size: 18px;

            transition: .2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            background: #ffb11b;
        }

        .footer {
            margin-top: 25px;
            color: #999;
            font-size: 14px;
        }

        @media (max-width: 768px) {

            .card {
                padding: 20px;
            }

            h1 {
                font-size: 28px;
            }

            p {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<div class="background"></div>

<div class="content">

    <div class="card">

        <img src="/images/preprod-splash.png" alt="Никтрейд">

        <h1>Сайт в разработке</h1>

        <p>
            Вы кто такие?<br>
            Я вас не звал.<br>
            Идите лесом.
        </p>

        <a href="/admin" class="btn">
            Пустите! Я Айтишник!
        </a>

        <div class="footer">
            Предрелизная версия сайта Никтрейд
        </div>

    </div>

</div>

</body>
</html>