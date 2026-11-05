# Корпоративная почта Niktrade: план реализации

Дата аудита: 2026-07-13.

Этот документ фиксирует этап 0 из ТЗ на модуль корпоративной почты. Реализация должна идти небольшими независимыми изменениями: сначала доменная модель и конфигурация, затем провижининг, после этого backend gateway и только затем интерфейс.

## Найденная архитектура

- Backend: Laravel 13, PHP 8.4 в текущем окружении, Eloquent, Filament 4, Livewire.
- Frontend: Blade, Vite 8, Tailwind/Vite, локальные CSS-файлы Work UI.
- База: production ожидает PostgreSQL; локальная среда настроена на sqlite и не подходит для браузерной проверки авторизации.
- Пользователь и сотрудник: текущая рабочая модель `App\Models\User`. В ней уже есть ФИО по частям, email, телефон, дата рождения, отдел, должность, HR-статусы, график, обеденное время, документы и события графика.
- Покупатели отделены в `App\Models\Customer` и используют отдельный guard `customer`.
- Авторизация: session guard `web`, Filament admin panel, Spatie Permission. Keycloak в проекте не обнаружен.
- Роли и права: `database/seeders/AdminRolePermissionSeeder.php`. Почтовые права нужно добавлять туда отдельно и минимально.
- Очереди: Laravel database queue уже доступна через стандартную миграцию jobs; отдельного брокера и Redis нет.
- Transactional outbox: готовой таблицы/механизма не обнаружено.
- Почтовый модуль: IMAP/SMTP/mailcow-кода нет. Есть только стандартный `config/mail.php` для исходящей Laravel-почты и UI-заглушки "Почта будет подключена позже".
- Логирование: стандартная Laravel logging stack/single, также есть Telegram-интеграции для заказов.
- Reverse proxy на сервере: nginx активен; Apache не используется.
- Docker на сервере установлен, но `docker compose` plugin на момент аудита недоступен. Это блокер для штатного запуска mailcow dockerized до установки compose plugin или согласованной альтернативы.
- Деплой: текущий `Deploy.bat` делает `git add`, commit, push и на сервере `/opt/niktrade-platform` выполняет `git pull` и `php artisan optimize:clear`. Для UI-задач ранее дополнительно требовался `npm run build`.

## Baseline проверок

- `php artisan about --only=environment,cache,drivers`: локально приложение поднято как `local`, database `sqlite`, queue `database`, session `database`, mail `log`.
- `composer test` без `APP_KEY` падает с ошибкой `No application encryption key has been specified`.
- `php artisan test` с временным process-only `APP_KEY` проходит: 2 теста, 2 assertions.
- `vendor/bin/pint --test` падает на существующем форматировании во многих файлах. Глобально чинить Pint в рамках почтового модуля нельзя, чтобы не создать шумовой diff.
- `npm run build` проходит; Vite предупреждает о неразрешенных внешних URL шрифтов Filament, но сборка завершается успешно.
- Браузерная проверка локально для admin UI невалидна из-за локальной конфигурации. Для UI-этапов проверять только preprod после деплоя.

## Будущие изменения по этапам

### Этап 1. Domain model и миграции

Добавить конфиг `config/mail_module.php` и env flags:

- `MAIL_MODULE_ENABLED`
- `MAIL_PROVISIONING_ENABLED`
- параметры mailcow API, IMAP, SMTP, quota, лимитов вложений, page size.

Новые таблицы:

- `employee_mail_profiles`
- `mailbox_credentials`
- `integration_outbox`
- `mail_provisioning_attempts`
- `personal_contacts`

Новые модели:

- `EmployeeMailProfile`
- `MailboxCredential`
- `IntegrationOutbox`
- `MailProvisioningAttempt`
- `PersonalContact`

Связи добавлять через `User`, не дублируя данные сотрудника.

### Этап 2. Email generation и карточка сотрудника

- Сервис генерации корпоративного адреса из ФИО сотрудника.
- Резервирование адреса в `employee_mail_profiles`.
- Отображение mail status в карточке сотрудника.
- Кнопка повторной попытки провижининга при ошибке.
- Не менять пользовательский `users.email` автоматически без отдельного решения, так как сейчас он участвует в авторизации.

### Этап 3. Identity adapter

Так как Keycloak не найден, MVP должен использовать текущую модель `User`.

Интерфейс:

- `createOrGetUser(User $employee)`
- `updateUser(User $employee)`
- `suspendUser(int $employeeId)`
- `restoreUser(int $employeeId)`
- `getUserByEmployeeId(int $employeeId)`

### Этап 4. Mailcow Admin adapter

- HTTP-клиент к mailcow Admin REST API.
- API key читать только на backend из secret file/env.
- Таймауты, retries, structured logs без секретов.
- Идемпотентность: существующий mailbox/app-password не считать ошибкой.

### Этап 5. Provisioning worker

- Observer/service на изменения сотрудника создает событие в `integration_outbox` внутри DB transaction.
- Queue job забирает outbox event и выполняет provisioning steps.
- Каждая попытка пишется в `mail_provisioning_attempts`.
- Ошибки не должны ломать сохранение карточки сотрудника.

### Этап 6. Credentials

- Генерация app-password не менее 32 случайных байт до кодирования.
- Хранение только в encrypted cast/значении.
- Никаких паролей в логах, UI, exceptions.
- Ротация app-password отдельным действием.

### Этап 7. MailGateway backend

- Backend подключается к IMAPS/SMTP от имени конкретного mailbox через app-password.
- Браузер работает только через текущую HTTPS-сессию Niktrade.
- API для папок, списка писем, просмотра, отправки, вложений, черновиков и операций над письмами.
- Не хранить тела писем в БД Niktrade.

### Этап 8. Address book

- Корпоративная адресная книга строится из активных сотрудников с активным mail profile.
- Личные контакты в `personal_contacts`.
- Автодополнение получателей объединяет корпоративные и личные контакты.

### Этап 9. Frontend

- Раздел "Почта" в существующем Work UI.
- Desktop: папки, список, preview, composer.
- Mobile: единая нижняя панель, список писем, просмотр, composer как bottom sheet.
- Проверка только на preprod после деплоя.

### Этап 10. Infrastructure

- Mailcow dockerized держать отдельным upstream-проектом, не смешивать с приложением.
- Подготовить runbook, `.env.example`, secret file examples, nginx snippets.
- Не менять production DNS, MX, PTR, firewall и реальные секреты автоматически.
- Перед mailcow нужен docker compose plugin или согласованная установка compose.

### Этап 11. Acceptance

- Unit tests для генерации email, outbox, статусов, credentials.
- Integration tests с fake mailcow client.
- E2E только против staging/preprod mailcow.
- Security checks: отсутствие секретов в логах/UI, запрет доступа к чужому mailbox.

## Затрагиваемые области проекта

- `config/`
- `app/Models/`
- `app/Services/Mail/`
- `app/Jobs/`
- `app/Observers/`
- `app/Http/Controllers/`
- `app/Filament/Resources/AdminUsers/`
- `resources/views/filament/`
- `resources/css/work.css`
- `routes/web.php` или отдельный route file, если он уже будет принят в проекте
- `database/migrations/`
- `database/seeders/AdminRolePermissionSeeder.php`
- `tests/`
- `docs/mail/`

## Риски

- Нет `docker compose` plugin на сервере; mailcow dockerized нельзя запускать штатно до исправления.
- Локальная среда невалидна для браузерной проверки admin UI.
- Локальный `APP_KEY` отсутствует, поэтому тесты без временного ключа падают.
- Существующий Pint baseline грязный; форматировать весь проект нельзя.
- Нужно аккуратно разделить `users.email` как логин и будущий корпоративный mailbox address.
- Пока нет staging mailcow, поэтому интеграцию с реальным API можно делать только через контракт и fake client.
- DNS/MX/PTR/TLS/firewall остаются ручными административными шагами.

## Ближайший следующий шаг

Этап 1: добавить конфигурацию и миграции доменной модели без подключения к mailcow и без UI. Это даст безопасный фундамент, который можно протестировать локально через unit/feature tests.
