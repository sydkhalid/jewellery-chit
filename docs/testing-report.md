# Phase 1 QA Testing

## Scope

This phase adds a CI-ready QA layer for the Jewellery Shop Chit Maintenance Software:

- Pest and PHPUnit feature tests for API, service, module, permission, ledger, cashbook, report, and business-flow checks.
- Laravel Dusk browser smoke tests for login, dashboard, sidebar, customer, payment, receipt, and report surfaces.
- Playwright browser tests for responsive UI, sidebar navigation, AJAX validation, SweetAlert-triggered flows, and mobile layout.
- Postman collection for API smoke testing with Sanctum token storage.

## Commands

```bash
composer test
composer test:pest
composer test:dusk
npm run test:playwright
```

Before Dusk or Playwright, make sure the local app and test data are available:

```bash
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

## Coverage Matrix

| Area | Automated Coverage |
| --- | --- |
| Authentication | Feature API/web tests, Dusk login flow, Postman login/logout |
| Customers | Feature CRUD/document tests, API tests, Playwright AJAX validation |
| Schemes | Feature CRUD/status/API tests |
| Enrollments | Feature create/cancel/installment-generation/API tests |
| Installments | Feature generation/status/API tests |
| Payments | Feature full/partial/advance/multiple/cancel/approval tests |
| Receipts | Feature generation/PDF/print/duplicate/cancel/API tests |
| Ledger | Phase 1 feature debit/credit/running-balance test |
| Pending Dues | Phase 1 feature overdue/follow-up/reminder log test |
| Maturity | Phase 1 feature normal close/refund/completion test |
| Jewellery Invoice | Phase 1 feature calculation/final/chit-adjustment test |
| Gold Rates | Feature and invoice approval-rate assertions |
| Staff/Branch | Existing module routes plus role checks |
| Cashbook | Phase 1 feature opening/credit/debit/summary test |
| Reports | Phase 1 feature summary, Excel, PDF checks |
| Roles | Feature Admin/Staff permission checks |
| API | Feature Sanctum envelope, pagination, protected routes, Postman collection |
| UI | Dusk browser smoke tests and Playwright responsive tests |

## Postman

Import:

```text
docs/postman/jewellery-chit-api.postman_collection.json
```

Set `base_url` to your running app API URL. Run `Auth - Login` first so the collection stores the Sanctum token.

## Latest Local Run

```text
php artisan test
107 passed, 690 assertions

vendor/bin/pest --filter=PhaseOneFullSystemQaTest
4 passed, 62 assertions

php artisan dusk --filter=AdminPanelBrowserTest --env=dusk.local
2 passed, 7 assertions

npm run test:playwright
6 passed across desktop and mobile Chromium

npm run build
Vite build completed with chunk-size warnings only
```
