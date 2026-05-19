# Testing Guide

The project uses Laravel feature tests, Pest/PHPUnit compatibility, Laravel Dusk, Playwright, and Postman collections.

## Test Environment

Use `.env.testing` with an isolated database.

Recommended:

```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
QUEUE_CONNECTION=sync
MAIL_MAILER=array
CACHE_STORE=array
INTEGRATION_MODE=sandbox
INTEGRATION_SIMULATE_SANDBOX=true
```

## Run Feature Tests

```bash
php artisan test
```

Run a targeted file:

```bash
php artisan test tests/Feature/PhaseSevenIntegrationTest.php
```

## Browser Tests

Laravel Dusk:

```bash
php artisan dusk
```

Playwright:

```bash
npm run test:playwright
```

Install browser once:

```bash
npm run test:playwright:install
```

## Postman

Collection path:

```text
docs/postman/jewellery-chit-api.postman_collection.json
```

Set variables:

- `base_url`: `http://127.0.0.1:8000/api`
- `token`: set automatically after login or paste manually

## Manual QA Flow

1. Login as Admin.
2. Verify dashboard cards and charts load.
3. Create customer and upload document.
4. Create scheme.
5. Create enrollment and confirm installments.
6. Collect payment.
7. Confirm receipt, ledger, cashbook.
8. Check pending dues.
9. Send WhatsApp/SMS in sandbox.
10. Create maturity closing.
11. Create jewellery invoice adjustment.
12. Export reports.
13. Create backup.
14. Review audit and activity logs.
15. Test API login and protected mobile routes.

## Role Testing

Test the same flow with:

- Admin: all menus and actions.
- Manager: management actions except restricted backup/delete actions.
- Staff: customer, collection, receipt, pending dues, and message-send actions only.

Expected failures should return 403 or hidden menu items.

## Integration Testing

Sandbox integrations:

```bash
php artisan test tests/Feature/PhaseSevenIntegrationTest.php
```

Covered:

- WhatsApp sandbox send
- SMS sandbox send
- Razorpay order and webhook
- UPI QR URI generation

## Common Fixes

- Permission cache issue: `php artisan permission:cache-reset`
- Stale config: `php artisan optimize:clear`
- Failing migrations: reset only test DB, never production DB.
- Missing storage files: `php artisan storage:link`
- Frontend issue: `npm run build`
