# Jewellery Chit Maintenance Software

API-first Laravel application for jewellery shop chit scheme maintenance. The web panel uses Blade + Vite, while mobile clients use the same Sanctum-protected REST APIs and shared service layer.

## Stack

- Laravel 12, PHP 8.2+, MySQL
- Blade, Vite, Bootstrap 5, SweetAlert2, ApexCharts
- Laravel Sanctum for mobile/API authentication
- Spatie Permission for Admin, Manager, and Staff access control
- Laravel DataTables for web listings
- DomPDF and Laravel Excel for receipts and reports
- Spatie Laravel Backup
- Provider-ready integrations for WhatsApp, SMS, payment gateways, and UPI QR

## Main Modules

- Authentication, roles, permissions, dashboard, profile
- Customers, documents, nominees, ledgers, outstanding balances
- Chit schemes, enrollments, installment schedules, payments, receipts
- Pending dues, reminders, maturity closing, jewellery invoice adjustment
- Gold rates, staff, branches, cashbook, reports
- WhatsApp/SMS logs, settings, backups, audit logs, activity logs
- Mobile API with standard `success`, `message`, `data` response envelope

## Default Login

Created by `DatabaseSeeder`:

- Admin: `admin@example.com` / `password`
- Manager: `manager@example.com` / `password`
- Staff: `staff@example.com` / `password`

Change these immediately before production use.

## Quick Start

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Optional demo data:

```bash
php artisan db:seed --class=DemoDataSeeder
```

## Documentation

- `INSTALLATION.md` - local setup and troubleshooting
- `DEPLOYMENT.md` - production deployment checklist
- `API_DOCUMENTATION.md` - Sanctum API usage and endpoints
- `USER_MANUAL.md` - daily operational workflows
- `ADMIN_GUIDE.md` - roles, settings, permissions, integrations
- `TESTING_GUIDE.md` - automated and manual QA flow
- `BACKUP_GUIDE.md` - backup, restore, scheduler, retention

Supporting docs are also available under `docs/`.

## Core Commands

```bash
php artisan migrate --seed
php artisan queue:work --tries=3
php artisan schedule:run
npm run build
php artisan test
```

## Production Notes

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Configure queue workers and the Laravel scheduler.
- Configure backups and validate restore on a separate database.
- Use live provider credentials only after sandbox verification.
- Run `php artisan config:cache`, `route:cache`, and `view:cache` after deployment.
