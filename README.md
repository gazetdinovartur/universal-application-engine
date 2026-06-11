# Universal Application Engine

Backend для регистрации и оплаты участия в фестивале (Hanuman Fest 2026). Заменяет связку WordPress + Forminator + самописная YooKassa.

**Фронтенд:** WordPress-сайт + bridge-плагин (Bootstrap 5 + JavaScript). Отдельного SPA нет — формы встраиваются на сайт через shortcode.

---

## Содержание

1. [Архитектура](#архитектура)
2. [Быстрый старт (локально)](#быстрый-старт-локально)
3. [Production-деплой API](#production-деплой-api)
4. [Интеграция с WordPress с нуля](#интеграция-с-wordpress-с-нуля)
5. [API для bridge](#api-для-bridge)
6. [Google Sheets](#google-sheets)
7. [Миграция legacy-данных (CSV)](#миграция-legacy-данных-csv)
8. [CLI-команды](#cli-команды)
9. [Админка](#админка)
10. [Тесты](#тесты)
11. [Структура репозитория](#структура-репозитория)
12. [Дополнительная документация](#дополнительная-документация)

---

## Архитектура

```
┌─────────────────────────────┐         HTTPS JSON          ┌──────────────────────────────┐
│  хануманфест.рф (WordPress) │  ─────────────────────────► │  апи.хануманфест.рф (Symfony)│
│  bridge-плагин + Bootstrap  │                             │  MySQL + EasyAdmin           │
└─────────────────────────────┘                             └──────────────┬───────────────┘
                                                                           │
                    ┌──────────────────────────────────────────────────────┼──────────────────┐
                    ▼                          ▼                           ▼                  ▼
              YooKassa API              Google Apps Script              SMTP              /admin
              (оплата)                  (экспорт в таблицу)           (письма)          (CRUD)
```

**Источник правды — MySQL.** Google Sheets — зеркало для людей (экспорт, не обратный импорт в реальном времени).

### Поток регистрации

1. Пользователь заполняет форму на WordPress (`[uae_registration]`).
2. JS (`uae-bridge.js`) вызывает `POST /api/calculate` → показывает цену.
3. `POST /api/applications` → создаётся заявка, строка уходит в Google Sheets.
4. `POST /api/payments` → создаётся платёж YooKassa, редирект на оплату.
5. После оплаты YooKassa шлёт webhook → Symfony обновляет статус, экспорт в Sheets, при 50% — ссылка на вторую оплату + email.
6. Страница `/return/` (`[uae_return]`) опрашивает `GET /api/payments/{id}/status`.

---

## Быстрый старт (локально)

### Требования

- Docker + Docker Compose
- PHP 8.2+ (если без Docker)
- MySQL 8.0

### Docker

```bash
docker compose up -d
```

API: http://localhost:8080

Первичная настройка БД и данных фестиваля:

```bash
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console app:seed:hanuman-fest
```

Проверка:

```bash
curl -s http://localhost:8080/api/products/hanuman-fest-2026 | jq .
```

### Переменные окружения

Скопируйте и настройте `.env.local` (не коммитится):

```dotenv
APP_SECRET=change-me
DATABASE_URL="mysql://app:!ChangeMe!@database:3306/app?serverVersion=8.0&charset=utf8mb4"

APP_URL=http://localhost:8080
FRONTEND_URL=http://localhost:8080
CORS_ALLOW_ORIGIN=http://localhost:8080

YOOKASSA_SHOP_ID=
YOOKASSA_SECRET_KEY=
GOOGLE_SHEETS_WEBHOOK_URL=

MAILER_DSN=null://null
MAILER_FROM=noreply@example.com
MAILER_FROM_NAME="Hanuman Fest"
```

| Переменная | Назначение |
|------------|------------|
| `APP_URL` | URL Symfony API |
| `FRONTEND_URL` | URL основного сайта (WordPress) — return URL YooKassa и ссылки `/pay/{token}` в письмах |
| `CORS_ALLOW_ORIGIN` | Origin WordPress-сайта для браузерных запросов bridge |
| `GOOGLE_SHEETS_WEBHOOK_URL` | URL развёрнутого Google Apps Script (пусто = экспорт отключён) |

---

## Production-деплой API

Подробный runbook: [`DEPLOY_TIMEWEB.md`](DEPLOY_TIMEWEB.md)

Кратко после `git pull`:

```bash
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console app:seed:hanuman-fest   # один раз
```

Smoke test:

```bash
curl -I https://api.хануманфест.рф/admin
curl -s https://api.хануманфест.рф/api/products/hanuman-fest-2026
```

---

## Интеграция с WordPress с нуля

Это главный способ показать форму на сайте. Iframe не используется — форма рисуется прямо на странице WordPress.

### Шаг 1. Развернуть Symfony API

- Домен API: `https://api.хануманфест.рф`
- SSL обязателен
- В `.env.local` на хостинге:

```dotenv
APP_URL="https://api.хануманфест.рф"
FRONTEND_URL="https://хануманфест.рф"
CORS_ALLOW_ORIGIN="https://хануманфест.рф"
YOOKASSA_SHOP_ID=...
YOOKASSA_SECRET_KEY=...
GOOGLE_SHEETS_WEBHOOK_URL=...
MAILER_DSN=smtp://...
ADMIN_PASSWORD_HASH='$2y$12$...'   # php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_BCRYPT), PHP_EOL;"
```

- В личном кабинете YooKassa webhook: `POST https://api.хануманфест.рф/api/webhooks/yookassa`

### Шаг 2. Установить bridge-плагин

Скопируйте из репозитория:

```
legacy/wordpress/universal-application-engine-bridge.php
legacy/wordpress/assets/uae-bridge.js
```

В WordPress:

```
wp-content/plugins/uae-bridge/
├── universal-application-engine-bridge.php
└── assets/
    └── uae-bridge.js
```

Активируйте плагин в админке WP.

### Шаг 3. Конфигурация в `wp-config.php`

```php
define('UAE_API_BASE', 'https://api.хануманфест.рф/api');
define('UAE_PRODUCT_SLUG', 'hanuman-fest-2026');
```

### Шаг 4. Создать страницы

| Slug страницы | Shortcode | Назначение |
|---------------|-----------|------------|
| `registration` | `[uae_registration]` | Форма регистрации и первая оплата |
| `pay` | `[uae_payment]` | Оплата второй половины по ссылке |
| `return` | `[uae_return]` | Страница возврата после YooKassa |

### Шаг 5. Permalinks

`Настройки → Постоянные ссылки → Сохранить` — нужно для rewrite `/pay/{token}`.

Плагин мапит URL вида `https://хануманфест.рф/pay/abc123...` на страницу `pay` с токеном.

### Шаг 6. Bootstrap 5

Форма использует Bootstrap 5. На теме WP должны быть подключены стили Bootstrap (или совместимая тема). Скрипт `uae-bridge.js` подключается плагином автоматически на страницах с shortcode.

### Шаг 7. Проверка end-to-end

1. Открыть `/registration/` — форма загружается, варианты участия подтягиваются из API.
2. Заполнить и отправить — редирект на YooKassa.
3. После оплаты — `/return/?payment_id=...` показывает статус.
4. В админке Symfony (`/admin`) — новая заявка и платёж.
5. В Google Sheets — новая строка (если настроен webhook).

Подробнее: [`legacy/wordpress/README-bridge.md`](legacy/wordpress/README-bridge.md)

---

## API для bridge

| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/api/products/{slug}` | Продукт, варианты участия, активный период цен |
| POST | `/api/calculate` | Расчёт цены (legacy Forminator parity) |
| POST | `/api/applications` | Создание заявки |
| POST | `/api/payments` | Создание платежа YooKassa |
| GET | `/api/payments/{id}/status` | Статус после возврата |
| GET | `/api/payment-links/{token}` | Данные ссылки второй оплаты |
| POST | `/api/payment-links/{token}/pay` | Создать платёж по ссылке |
| POST | `/api/webhooks/yookassa` | Webhook YooKassa |

Пример расчёта:

```bash
curl -s -X POST https://api.хануманфест.рф/api/calculate \
  -H 'Content-Type: application/json' \
  -d '{
    "productSlug": "hanuman-fest-2026",
    "participationOptionCode": "OWN_HOUSE_NO_FOOD",
    "adultsCount": 1,
    "childrenCount": 0,
    "transferIncluded": false,
    "paymentFactor": 0.5
  }'
```

---

## Google Sheets

**Направление: Symfony → Google Sheets** (экспорт).

| Событие | Что происходит |
|---------|----------------|
| Новая заявка | POST на Apps Script (`action: application`) |
| Успешная оплата | POST (`action: payment`) — обновление колонок оплаты |

### Настройка

1. Откройте [`legacy/google-apps-script/Code.by-columns.gs`](legacy/google-apps-script/Code.by-columns.gs).
2. Задайте `SHEET_ID` и имя листа в скрипте.
3. Deploy → Web app → скопируйте URL.
4. Пропишите в Symfony: `GOOGLE_SHEETS_WEBHOOK_URL=https://script.google.com/macros/s/.../exec`

Пересинхронизация всех успешных оплат:

```bash
php bin/console app:payments:sync-google-sheets
```

Если `GOOGLE_SHEETS_WEBHOOK_URL` пуст — экспорт молча пропускается (warning в лог).

---

## Миграция legacy-данных (CSV)

Одноразовый импорт из выгрузок Forminator и Google Sheets. Подробно: [`IMPORT_LEGACY_ORDERS.md`](IMPORT_LEGACY_ORDERS.md)

```bash
php bin/console app:seed:hanuman-fest

php bin/console app:import:legacy-orders \
  --sheet-source="/path/Лист регистраций - Регистрации.csv" \
  --forminator-source="/path/forminator-....csv" \
  --product-slug=hanuman-fest-2026 \
  --dry-run

php bin/console app:applications:recalculate-statuses --product-slug=hanuman-fest-2026
php bin/console app:payment-links:generate --product-slug=hanuman-fest-2026 --dry-run
php bin/console app:payment-links:generate --product-slug=hanuman-fest-2026
```

---

## CLI-команды

| Команда | Назначение |
|---------|------------|
| `app:seed:hanuman-fest` | Продукт, периоды цен, варианты участия Hanuman Fest 2026 |
| `app:import:legacy-orders` | Импорт заявок/оплат из CSV |
| `app:applications:recalculate-statuses` | Пересчёт `paidAmount` и статусов по успешным платежам |
| `app:payment-links:generate` | Ссылки на вторую оплату для `PARTIALLY_PAID` (--send-email для рассылки) |
| `app:payments:sync-google-sheets` | Повторный экспорт всех успешных оплат в Sheets |

---

## Админка

URL: `/admin` (HTTP Basic Auth)

Локально по умолчанию: логин `admin`, пароль `TempAdmin!2026` (см. `config/packages/security.yaml`). **На production обязательно задайте `ADMIN_PASSWORD_HASH`.**

Разделы: заявки, платежи, пользователи, проекты, периоды цен (с редактором матрицы цен), варианты участия.

Операции импорта, payment links и sync Sheets — только через CLI, не из админки.

---

## Тесты

```bash
composer test
# или
php bin/phpunit
```

Покрытие:

- `PhoneNormalizer` — нормализация телефонов
- `FestivalPricingCalculator` — формула цен (legacy parity)
- `PaymentService`, `PaymentLinkService` — оплата и ссылки
- CLI: import, recalculate, generate payment links
- API: `/api/products`, `/api/calculate`
- Google Sheets export client

Тестовая БД: SQLite (`var/test.db`), создаётся автоматически.

---

## Структура репозитория

```
├── src/                    # Symfony: entities, services, API, admin, commands
├── config/                 # Конфигурация, security, services
├── migrations/             # Doctrine migrations (MySQL)
├── templates/              # Twig (админка, email)
├── public/                 # Web root (index.php)
├── legacy/
│   ├── wordpress/          # Bridge-плагин для WordPress
│   └── google-apps-script/ # Apps Script для Google Sheets
├── tests/                  # PHPUnit
├── docker/                 # nginx + PHP для локальной разработки
├── DEPLOY_TIMEWEB.md       # Production runbook
├── IMPORT_LEGACY_ORDERS.md # Legacy CSV import
└── compose.yaml            # Docker Compose
```

---

## Дополнительная документация

| Файл | Содержание |
|------|------------|
| [`DEPLOY_TIMEWEB.md`](DEPLOY_TIMEWEB.md) | Деплой на Timeweb, env, smoke tests |
| [`IMPORT_LEGACY_ORDERS.md`](IMPORT_LEGACY_ORDERS.md) | Импорт CSV, recalc, payment links |
| [`legacy/wordpress/README-bridge.md`](legacy/wordpress/README-bridge.md) | Bridge: shortcode, rewrite, конфиг |

---

## Стек

- PHP 8.2+, Symfony 7.4, Doctrine ORM 3, EasyAdmin 5
- MySQL 8.0
- YooKassa REST API
- WordPress bridge: Bootstrap 5 + vanilla JS
- Google Sheets via Apps Script webhook
