# Phase 10 Go-Live Readiness

Generated for the Jewellery Chit ERP go-live setup.

## Live Import Commands

Place real CSV files in `storage/app/imports/` using the templates in `docs/import-templates/`.

Dry-run validation:

```bash
php artisan live-data:import --settings=storage/app/imports/settings.csv --staff=storage/app/imports/staff_users.csv --gold-rates=storage/app/imports/gold_rates.csv --customers=storage/app/imports/customers.csv --chits=storage/app/imports/chit_accounts.csv --dry-run
```

Commit import:

```bash
php artisan live-data:import --settings=storage/app/imports/settings.csv --staff=storage/app/imports/staff_users.csv --gold-rates=storage/app/imports/gold_rates.csv --customers=storage/app/imports/customers.csv --chits=storage/app/imports/chit_accounts.csv
```

Readiness audit:

```bash
php artisan go-live:verify
php artisan go-live:verify --json
```

## Go-Live Checklist

- Real customer CSV validated with no duplicate mobile numbers.
- Real chit account CSV validated against existing customer codes, scheme codes, branch codes, and staff emails.
- Active schemes are configured before chit account import.
- Approved gold rates are imported and locked for the go-live date.
- Receipt settings are configured: shop name, address, mobile, GSTIN, prefix, and terms.
- Real Admin, Manager, and Staff users are created with strong passwords.
- Backup settings are enabled and `backup:run` is available.
- Reminder channel is enabled: WhatsApp or SMS.
- Queue worker is running for `default,reminders,messages,pdf,exports`.
- Scheduler cron is installed and `php artisan schedule:list` shows overdue marking, reminders, and backup.
- Reports open for customers, collections, pending dues, receipts, and cashflow.
- Payment collection creates payment, receipt, ledger, installment, and cashbook entries.
- API login, Sanctum-protected user endpoint, and production API throttles are verified.

## Daily Operations Checklist

- Confirm queue worker is running.
- Confirm scheduler ran overdue marking before collections begin.
- Verify today gold rate is approved and locked.
- Review pending dues and send reminders.
- Collect payments only through the payment module.
- Print or share receipts from the receipt module.
- Review dashboard cards for collection, pending dues, and overdue customers.
- Check failed jobs and application logs before closing.

## Backup Verification Checklist

- Run `php artisan backup:run --only-db --disable-notifications`.
- Confirm a backup row appears in the backup log screen.
- Confirm the archive exists on the configured disk.
- Download or copy one backup to a separate machine.
- Run restore validation on a staging database before go-live.
- Confirm backup retention policy and disk space.

## End-Of-Day Closing Checklist

- Verify all successful payments have receipts.
- Verify each staff cash handover is received or rejected.
- Reconcile cashbook totals against physical cash, UPI, card, and bank collections.
- Review pending failed messages or reminders.
- Export daily collection and receipt reports.
- Run or verify the scheduled database backup.
- Record unresolved dues, failed collections, and operational notes for the next day.

## Current Readiness Rules

`php artisan go-live:verify` checks:

- Confirmed live-data import marker from `live-data:import`.
- Customer and chit data presence.
- Active schemes.
- Approved gold rate.
- Receipt/shop settings.
- Active Admin, Manager, and Staff users.
- Backup command and disk configuration.
- Reminder route/channel readiness.
- Queue connection and jobs table.
- Scheduled overdue, reminder, and backup tasks.
- Core report routes.
- Payment collection setup.
- API login and Sanctum-protected routes.

## Final ERP Readiness Report Format

- Imported data summary: counts from `live-data:import`.
- Go-live readiness: status from `go-live:verify`.
- Production verification summary: build, tests, queue, scheduler, backup, reports, payment, and API checks.
- Sign-off: owner name, date, database backup reference, and first live collection receipt number.
