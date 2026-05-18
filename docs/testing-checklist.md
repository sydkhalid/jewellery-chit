# Testing Checklist

Use this checklist before release.

## Web Panel

- Auth login and logout for Admin, Manager, Staff
- Role-based sidebar visibility and permission blocking
- Dashboard cards and charts
- Customer CRUD, documents, profile, ledger, payment history, outstanding
- Scheme CRUD, validation, active/inactive status
- Enrollment create, update, cancel, agreement upload, installment generation
- Installment list, enrollment schedule, overdue update
- Payment create, partial/full/advance/multiple month, receipt, ledger, cashbook updates
- Receipt thermal print, A4 print, PDF, duplicate, WhatsApp placeholder, cancel
- Ledger list, customer ledger, chit ledger, rebuild guard
- Pending dues today, weekly, monthly, overdue, follow-up, reminders
- Maturity closing calculate, create, approve, complete, cancel
- Jewellery invoice draft, update, finalize, cancel, chit adjustment
- Gold rate create, approve, reject, lock, latest approved
- Branch, staff, role assignment, inactive login block
- Staff cash handover create, receive, reject
- Cashbook opening, closing, summaries, payment mode totals
- Reports, filters, Excel, PDF, print
- WhatsApp/SMS logs, notifications, retry flow
- Settings update and logo upload
- Backup create, download, delete
- Audit logs and activity logs with filters

## API

- Login, token profile, logout
- Customer list, create, show
- Scheme list and detail
- Enrollment create and show
- Installment list and by enrollment
- Payment collection and show
- Receipt view and download
- Ledger, pending dues, dashboard summary
- Validation errors, unauthenticated responses, forbidden responses

## Build

```bash
composer install
npm install
npm run build
php artisan migrate --seed
php artisan storage:link
php artisan test
```
