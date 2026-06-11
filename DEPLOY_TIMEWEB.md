# Production Runbook (Timeweb)

## 1. Prerequisites

- Domain/subdomain: `апи.хануманфест.рф`
- PHP: `8.2`
- MySQL: `8.0` (DB: `cb15013_uae`)
- Site root: `public/`
- Repository sync: GitHub (branch `main` or release branch)

## 2. Environment Variables (`.env.local`)

Create/update `.env.local` on hosting:

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=CHANGE_ME_LONG_RANDOM

DATABASE_URL="mysql://DB_USER:DB_PASSWORD@127.0.0.1:3306/cb15013_uae?serverVersion=8.0&charset=utf8mb4"

APP_URL="https://апи.хануманфест.рф"
FRONTEND_URL="https://хануманфест.рф"
CORS_ALLOW_ORIGIN="https://хануманфест.рф"

YOOKASSA_SHOP_ID=...
YOOKASSA_SECRET_KEY=...
GOOGLE_SHEETS_WEBHOOK_URL=...

MAILER_DSN=...
MAILER_FROM="noreply@хануманфест.рф"
MAILER_FROM_NAME="Hanuman Fest"

# Admin access
ADMIN_PASSWORD_HASH='$2y$12$replace_with_your_hash'
```

Generate hash for admin password:

```bash
php -r "echo password_hash('YOUR_STRONG_PASSWORD', PASSWORD_BCRYPT), PHP_EOL;"
```

## 3. Deploy Steps (after `git pull`)

Run in project root:

```bash
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console assets:install public --no-interaction
```

If needed for permissions:

```bash
mkdir -p var/cache var/log
chmod -R 775 var
```

## 4. Web Server Checklist

- Document root points to `public/`
- PHP-FPM enabled for project
- HTTPS certificate attached to `апи.хануманфест.рф`
- Force redirect HTTP -> HTTPS

## 5. Smoke Test

After deploy:

```bash
curl -I https://апи.хануманфест.рф/admin
curl -s https://апи.хануманфест.рф/api/products/hanuman-fest-2026
```

Expected:

- `/admin` redirects to `/admin/login` (form login, not HTTP Basic)
- Product endpoint returns JSON with active period/options

## 6. Rollback (quick)

1. Checkout previous stable commit/tag
2. `composer install --no-dev --optimize-autoloader`
3. `php bin/console cache:clear --env=prod`
4. Run DB rollback only if a migration requires it

