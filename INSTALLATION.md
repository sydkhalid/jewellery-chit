# Installation Guide

This guide installs the Jewellery Chit Maintenance Software for local development or staging.

## Requirements

- PHP 8.2 or newer
- Composer
- Node.js 20+ and npm
- MySQL or MariaDB
- PHP extensions commonly required by Laravel: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, `zip`
- A writable `storage/` and `bootstrap/cache/`

## Setup

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
```

On Linux or macOS, use:

```bash
cp .env.example .env
```

## Database

Update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jewellery_chit
DB_USERNAME=root
DB_PASSWORD=
```

Then run:

```bash
php artisan migrate --seed
php artisan storage:link
```

Optional demo data:

```bash
php artisan db:seed --class=DemoDataSeeder
```

## Frontend Assets

Development:

```bash
npm run dev
```

Production build:

```bash
npm run build
```

## Run Locally

```bash
php artisan serve
```

Open:

```text
http://127.0.0.1:8000/login
```

Default users:

- `admin@example.com` / `password`
- `manager@example.com` / `password`
- `staff@example.com` / `password`

## Queue Setup

Set `.env`:

```env
QUEUE_CONNECTION=database
```

Run once:

```bash
php artisan queue:table
php artisan migrate
```

Run worker:

```bash
php artisan queue:work --tries=3 --timeout=120
```

## Scheduler Setup

Local manual run:

```bash
php artisan schedule:run
```

Production cron:

```cron
* * * * * cd /path/to/jewellery-chit && php artisan schedule:run >> /dev/null 2>&1
```

## Integrations

Phase 7 providers are configured in `.env`:

```env
INTEGRATION_MODE=sandbox
INTEGRATION_SIMULATE_SANDBOX=true
WHATSAPP_PROVIDER=twilio
SMS_PROVIDER=msg91
PAYMENT_GATEWAY=razorpay
```

See `docs/integrations.md` for Twilio, Meta, MSG91, Textlocal, Razorpay, Pine Labs, PayU, and UPI QR credentials.

## Troubleshooting

- Blank page: run `php artisan optimize:clear` and check `storage/logs/laravel.log`.
- Permission denied: make `storage/` and `bootstrap/cache/` writable.
- Vite assets missing: run `npm install` and `npm run build`.
- Login fails after seeding: run `php artisan db:seed --class=DefaultUserSeeder`.
- Permission changes not applied: run `php artisan permission:cache-reset`.
- File uploads not visible: run `php artisan storage:link`.
