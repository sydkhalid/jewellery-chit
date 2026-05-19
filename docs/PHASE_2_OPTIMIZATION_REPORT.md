# Phase 2 Optimization Report

Date: 2026-05-19

## Security Issues Fixed

- Added named rate limits for web login, API login, authenticated API traffic, and webhooks.
- Added API login lockout handling with failed-attempt tracking and reset on successful login.
- Set Sanctum personal access token expiry through `SANCTUM_TOKEN_EXPIRATION` with issued token expiry metadata.
- Replaced weak staff password validation with Laravel password defaults and production-strength defaults.
- Added centralized upload validation for images, documents, and agreements.
- Blocked executable upload extensions including PHP, shell, JS, HTML, SVG, EXE, BAT, CMD, MSI, and PHAR.
- Kept web routes under CSRF/session middleware and API routes under Sanctum plus permission middleware.
- Verified model mass assignment uses explicit `$fillable`; no globally unguarded models were introduced.
- Preserved escaped Blade output and escaped dynamic DataTable attributes where user-controlled text is embedded.

## Performance Improvements

- Cached dashboard data per user, branch, role set, and day.
- Cached shop settings by key, group, and full list with automatic invalidation on save/delete.
- Cached latest approved and today's approved gold rates with invalidation on rate lifecycle changes.
- Replaced report-row N+1 sums for staff and branch collections with eager aggregate `withSum` queries.
- Replaced pending-dues summary collection loading with SQL aggregate totals.
- Kept DataTables backed by eager-loaded Eloquent builders for core admin grids.
- Added queued background paths for reminders, message sending, receipt PDFs, and exports.

## DB Indexes Added

- `chit_enrollments`: status/start date, branch/status/start date, staff/status/start date.
- `chit_installments`: status/balance/due date, enrollment/due/status, follow-up/due date.
- `chit_payments`: status/payment date plus status-scoped branch, staff, and payment-mode date indexes.
- `chit_receipts`: payment/status and status/customer/receipt date.
- `gold_rates`: status/rate date/rate locked.
- `notifications`: channel/status/created date.
- `whatsapp_logs` and `sms_logs`: message type/status/created date and mobile/message type/created date.
- `activity_logs`, `audit_logs`, and `integration_transactions`: operational lookup indexes.

## Cache Implemented

- Redis-ready `.env.example` defaults for `CACHE_STORE=redis` and Redis cache DB.
- `DASHBOARD_CACHE_TTL`, `SETTINGS_CACHE_TTL`, and `GOLD_RATE_CACHE_TTL` configuration.
- Dashboard, settings, and gold-rate cache code implemented with safe TTLs and invalidation where needed.

## Queue Jobs Implemented

- `SendMessageJob` for WhatsApp/SMS.
- `SendDueReminderJob` and `SendBulkDueReminderJob` for due reminders.
- `GenerateReceiptPdfJob` for receipt PDF generation after payment.
- `SendReceiptWhatsappJob` for receipt WhatsApp sharing.
- `ExportReportJob` for queued Excel/PDF report exports via `?queue=1`.

## Monitoring

- Added Laravel Telescope as a dev dependency and registered it only when `APP_ENV=local` and the package exists.
- Added Laravel Debugbar as a dev dependency and defaulted it to local debug only.
- Added daily `security` and `performance` log channels.

## Benchmark Summary

- Full feature/unit suite: 111 tests passed, 717 assertions, 43.90s.
- Production cache commands completed:
  - `php artisan optimize`
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`

## Next Phase Prompt

Implement Phase 3: production deployment hardening. Add CI checks, backup restore drills, Horizon worker supervision, scheduled task monitoring, Redis health checks, log rotation policy, deployment rollback plan, and a staging smoke-test checklist.
