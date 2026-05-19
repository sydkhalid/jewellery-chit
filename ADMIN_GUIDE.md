# Admin Guide

This guide covers administrative configuration and controls.

## Roles

Default roles:

- Admin
- Manager
- Staff

Admin has all permissions. Manager has broad operational access with restricted backup/delete actions. Staff has customer, enrollment, collection, receipt, ledger, pending dues, and message-send access.

## Permission Groups

Major permission groups:

- Dashboard
- Customers
- Chit Schemes
- Chit Enrollments
- Installments
- Payments
- Receipts
- Ledger
- Pending Dues
- Maturity Closing
- Jewellery Billing
- Gold Rates
- Staff & Branch
- Cashflow
- Reports
- WhatsApp/SMS
- Admin Settings
- Audit Logs
- Backup

Run after changing permission seeders:

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan permission:cache-reset
```

## Default Users

Seeded by `DefaultUserSeeder`:

- `admin@example.com`
- `manager@example.com`
- `staff@example.com`

Change default passwords after first login.

## Branch and Staff

Use `Staff & Branch` to manage:

- Branch list and branch creation
- Staff users and roles
- Staff status
- Cash handovers

Inactive staff cannot use web or API login.

## Shop Settings

Use `Admin Settings` to manage:

- Shop name, logo, address, mobile, email, GSTIN
- Receipt and number prefixes
- Chit defaults
- WhatsApp/SMS options
- Backup settings

Settings are stored in `shop_settings`.

## Integrations

Provider settings are in `.env` and `config/integrations.php`.

Supported providers:

- WhatsApp: Twilio, Meta WhatsApp Business
- SMS: MSG91, Textlocal
- Payments: Razorpay, Pine Labs, PayU, UPI QR

Keep sandbox mode during validation:

```env
INTEGRATION_MODE=sandbox
INTEGRATION_SIMULATE_SANDBOX=true
```

Switch to live only after testing:

```env
INTEGRATION_MODE=live
INTEGRATION_SIMULATE_SANDBOX=false
```

Monitor `integration_transactions` for request, retry, webhook, and failure payloads.

## Audit and Activity Logs

Audit logs track critical model changes. Activity logs track user actions.

Use these pages for investigation:

- `Admin Settings > Audit Logs`
- `Admin Settings > Activity Logs`

Common filters:

- User
- Module
- Event/action
- Date range
- IP address

## Backup Controls

Admin users with backup permissions can create, download, and delete backups from the backup page.

Recommended:

- Keep database backups daily.
- Keep files backup if receipt PDFs and uploads are stored locally.
- Test restore monthly.

## Security Notes

- Use HTTPS in production.
- Do not expose `.env`, `storage/logs`, or database dumps.
- Keep `APP_DEBUG=false` in production.
- Rotate provider API credentials if leaked.
- Review staff permissions regularly.
