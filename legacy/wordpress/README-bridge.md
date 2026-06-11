# Universal Application Engine Bridge for WordPress

Полная документация проекта: [`../../README.md`](../../README.md)

Этот bridge-плагин встраивает Symfony API в WordPress-сайт на Bootstrap 5.

## 1) Установка

1. Скопируйте файлы:
   - `universal-application-engine-bridge.php`
   - папку `assets/`
2. Поместите их в папку WP плагина, например:
   - `wp-content/plugins/uae-bridge/`
3. Активируйте плагин в админке WordPress.
4. Перейдите в `Настройки -> Постоянные ссылки` и нажмите `Сохранить` (для применения rewrite-правил).

## 2) Конфигурация API

Перед подключением задайте константы в `wp-config.php`:

```php
define('UAE_API_BASE', 'https://api.хануманфест.рф/api');
define('UAE_PRODUCT_SLUG', 'hanuman-fest-2026');
```

## 3) Страницы и shortcode

Создайте 3 страницы:

1. `/registration/`
   - shortcode: `[uae_registration]`
2. `/pay/`
   - shortcode: `[uae_payment]`
3. `/return/`
   - shortcode: `[uae_return]`

Плагин добавляет rewrite:
- `/pay/{token}` -> страница `pay` с query var `uae_token`

## 4) Что делает bridge

- форма регистрации на Bootstrap 5
- расчёт цены через `POST /api/calculate`
- создание заявки через `POST /api/applications`
- переход на оплату через `POST /api/payments`
- оплата второй половины через `GET/POST /api/payment-links/{token}`
- проверка результата после возврата через `GET /api/payments/{id}/status`

## 5) Важные условия

- На API должен быть валидный SSL.
- CORS на API должен разрешать основной сайт:
  - `CORS_ALLOW_ORIGIN=https://хануманфест.рф`
- В API переменная `FRONTEND_URL` должна указывать на основной сайт:
  - `FRONTEND_URL=https://хануманфест.рф`

