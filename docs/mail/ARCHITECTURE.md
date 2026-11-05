# Корпоративная почта Niktrade: архитектура MVP

## Цель

Добавить нативную корпоративную почту в Niktrade без отдельной видимой учетной записи для сотрудника и без прямого доступа браузера к IMAP/SMTP.

## Границы системы

Niktrade отвечает за:

- карточку сотрудника;
- учетную запись сотрудника в текущей auth-системе;
- статус почтового профиля;
- очередь провижининга;
- backend API почтового клиента;
- корпоративную и личную адресную книгу;
- аудит операций управления mailbox.

Mailcow отвечает за:

- mailbox;
- app-password;
- IMAPS;
- SMTP Submission;
- антиспам/почтовую инфраструктуру;
- собственные volume/database internals, недоступные бизнес-коду Niktrade.

## Поток провижининга

1. Сотрудник создается или обновляется в Niktrade.
2. Domain service валидирует карточку и резервирует корпоративный адрес в `employee_mail_profiles`.
3. В той же DB transaction создается событие в `integration_outbox`.
4. Queue job забирает событие и запускает provisioning worker.
5. Identity adapter проверяет или создает/обновляет текущую учетную запись.
6. Mailcow adapter проверяет mailbox.
7. Если mailbox нет, worker создает его через mailcow Admin API.
8. Worker создает или ротирует app-password с IMAP/SMTP-доступом.
9. App-password шифруется и сохраняется в `mailbox_credentials`.
10. Worker выполняет техническую проверку IMAP/SMTP.
11. `employee_mail_profiles.status` становится `active`, либо `failed` с причиной ошибки.

## Runtime поток почтового клиента

1. Сотрудник входит в Niktrade через обычную web-сессию.
2. Frontend вызывает Mail API Niktrade.
3. Backend проверяет, что пользователь читает только свой mailbox.
4. Backend расшифровывает app-password только на время IMAP/SMTP-операции.
5. Backend получает папки, письма, вложения или отправляет письмо.
6. Frontend получает нормализованный JSON/multipart response.

## Таблицы

`employee_mail_profiles`:

- связь `employee_id` с `users.id`;
- корпоративный email;
- display name;
- quota;
- status;
- timestamps провижининга;
- last error metadata без секретов.

`mailbox_credentials`:

- связь с mail profile;
- encrypted app-password;
- key version;
- allowed protocols;
- rotation timestamps.

`integration_outbox`:

- event type;
- aggregate type/id;
- payload;
- status;
- attempts;
- available_at;
- locked_at;
- processed_at.

`mail_provisioning_attempts`:

- mail profile;
- outbox event;
- step;
- status;
- duration;
- sanitized error.

`personal_contacts`:

- owner user;
- name;
- email;
- phone/company/notes;
- timestamps.

## Права доступа

MVP должен разделять права:

- сотрудник читает и отправляет только свою почту;
- HR/Admin видит статус почтового профиля и может повторить provisioning;
- никто не читает чужую почту через админку;
- super admin может выполнять технические действия управления mailbox, но не получает просмотр содержимого писем.

## Security baseline

- Нет mailcow API key во frontend.
- Нет IMAP/SMTP credentials во frontend.
- Нет секретов в логах.
- Нет HTML писем без sanitize.
- Вложения проверяются по размеру и имени.
- Все операции над письмами проверяют owner mailbox.

## Наблюдаемость

Минимально нужны:

- structured logs provisioning worker;
- attempts table;
- health check mailcow API;
- health check IMAP/SMTP;
- счетчики успешных/ошибочных provisioning events;
- понятный статус в карточке сотрудника.
