# Jewellery Chit Maintenance Software

API-first Laravel application for jewellery shop chit scheme maintenance with a Blade + Vite admin panel and Sanctum-protected REST APIs for mobile clients.

## Stack

- Laravel, Blade, Vite, Bootstrap 5, SweetAlert2, ApexCharts
- Laravel Sanctum for API authentication
- Spatie Permission for roles and permissions
- Laravel DataTables for web listings
- Laravel Excel and DomPDF for exports and receipts
- Spatie Laravel Backup for database backups

## Main Modules

- Auth, roles, permissions, and dashboard
- Customers, documents, ledger, and outstanding history
- Chit schemes, enrollments, installment generation, payments, and receipts
- Ledger, pending dues, reminders, maturity closing, and jewellery invoice adjustment
- Gold rates, staff, branches, cashbook, reports, WhatsApp/SMS logs, settings
- Backup, audit logs, and activity logs

## Default Users

- Admin: `admin@example.com` / `password`
- Manager: `manager@example.com` / `password`
- Staff: `staff@example.com` / `password`

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Load the complete demo/test dataset:

```bash
php artisan db:seed --class=DemoDataSeeder
```

See `docs/installation.md`, `docs/api-endpoints.md`, `docs/user-roles-permissions.md`, `docs/testing-checklist.md`, and `docs/deployment.md` for production and QA details.
