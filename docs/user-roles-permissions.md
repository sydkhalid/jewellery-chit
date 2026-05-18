# User Roles and Permissions

Roles are managed by Spatie Permission.

## Roles

- Admin: full system access
- Manager: management access except high-risk backup deletion and restricted admin actions
- Staff: customer, enrollment, payment, receipt, ledger, pending due, and message send access

## Permission Groups

- Dashboard: `dashboard.view`
- Customers: `customers.view`, `customers.create`, `customers.edit`, `customers.delete`, `customers.deactivate`, `customers.documents`, `customers.ledger`
- Chit schemes: `schemes.view`, `schemes.create`, `schemes.edit`, `schemes.delete`, `schemes.status`
- Enrollments: `enrollments.view`, `enrollments.create`, `enrollments.edit`, `enrollments.delete`, `enrollments.close`, `enrollments.cancel`
- Installments: `installments.view`, `installments.generate`, `installments.edit`, `installments.status`
- Payments: `payments.view`, `payments.create`, `payments.edit`, `payments.cancel`, `payments.approve_edit`
- Receipts: `receipts.view`, `receipts.print`, `receipts.pdf`, `receipts.duplicate`, `receipts.cancel`, `receipts.whatsapp`
- Ledger: `ledger.view`, `ledger.customer`, `ledger.chit`
- Pending dues: `pending_dues.view`, `pending_dues.followup`, `pending_dues.reminder`
- Maturity: `maturity.view`, `maturity.create`, `maturity.approve`, `maturity.cancel`
- Jewellery: `jewellery.view`, `jewellery.create`, `jewellery.edit`, `jewellery.cancel`, `jewellery.adjust_chit`
- Gold rates: `gold_rates.view`, `gold_rates.create`, `gold_rates.edit`, `gold_rates.approve`, `gold_rates.lock`
- Staff and branch: staff, branch, and cash handover permissions
- Cashflow: `cashflow.view`, `cashflow.create`, `cashbook.view`
- Reports: `reports.view`, `reports.export_excel`, `reports.export_pdf`, `reports.print`
- Messages: `messages.view`, `messages.send`, `messages.retry`, `messages.logs`
- Settings and logs: `settings.view`, `settings.edit`, `settings.backup`, `audit_logs.view`, `activity_logs.view`
- Backup: `backup.view`, `backup.create`, `backup.download`, `backup.delete`

Run `php artisan db:seed --class=RolePermissionSeeder` to refresh roles and permissions safely.
