# Корпоративная почта Niktrade: baseline проверок

Дата: 2026-07-13.

## Команды

```powershell
php -v
php artisan about --only=environment,cache,drivers
composer test
php artisan test # с временным process-only APP_KEY
vendor\bin\pint --test
npm run build
```

## Результаты

- PHP: 8.4.23 в текущей локальной среде.
- Laravel: 13.13.0.
- Локальная среда: `local`, `debug=true`, database `sqlite`, session `database`, queue `database`, mail `log`.
- `composer test` без `APP_KEY`: не проходит, потому что отсутствует application encryption key.
- `php artisan test` с временным process-only `APP_KEY`: проходит, 2 теста / 2 assertions.
- `vendor\bin\pint --test`: не проходит на существующем форматировании проекта. Это baseline до почтового модуля.
- `npm run build`: проходит. Есть предупреждения Vite о внешних URL шрифтов Filament, но сборка завершается.

## Вывод

Для следующих этапов:

- тесты запускать с корректным `APP_KEY` в окружении;
- не чинить весь Pint baseline в почтовых задачах;
- UI проверять на preprod после деплоя;
- для mailcow integration tests сначала нужен staging mailcow или fake client.
