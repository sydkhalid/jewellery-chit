# API Documentation

All mobile APIs are under `/api` and use Laravel Sanctum token authentication.

## Response Format

Success:

```json
{
  "success": true,
  "message": "Data fetched successfully",
  "data": {}
}
```

Validation error:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {}
}
```

Paginated response:

```json
{
  "success": true,
  "message": "Data fetched successfully",
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 0
  }
}
```

## Headers

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

## Authentication

Login:

```http
POST /api/login
```

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

Use `data.token` as a Bearer token.

Profile:

```http
GET /api/profile
```

Logout:

```http
POST /api/logout
```

## Customer APIs

```http
GET /api/customers
POST /api/customers
GET /api/customers/{customer}
PUT /api/customers/{customer}
DELETE /api/customers/{customer}
POST /api/customers/{customer}/documents
GET /api/customers/{customer}/ledger
GET /api/customers/{customer}/payment-history
GET /api/customers/{customer}/outstanding
```

Create example:

```json
{
  "name": "Demo Customer",
  "mobile": "9000000001",
  "email": "demo@example.com",
  "address": "Main Road",
  "city": "Chennai",
  "state": "Tamil Nadu",
  "pincode": "600001",
  "nominee": {
    "name": "Nominee",
    "relationship": "Spouse"
  }
}
```

## Scheme APIs

```http
GET /api/schemes
GET /api/schemes/{scheme}
```

Mobile clients should display active scheme details from these endpoints.

## Enrollment APIs

```http
GET /api/chit-enrollments
POST /api/chit-enrollments
GET /api/chit-enrollments/{enrollment}
```

Create example:

```json
{
  "customer_id": 1,
  "scheme_id": 1,
  "branch_id": 1,
  "assigned_staff_id": 3,
  "start_date": "2026-05-19",
  "monthly_amount": 1000
}
```

## Installment APIs

```http
GET /api/installments
GET /api/chit-enrollments/{enrollment}/installments
```

## Payment APIs

```http
GET /api/payments
POST /api/payments
GET /api/payments/{payment}
POST /api/payments/gateway/order
POST /api/payments/gateway/{transaction}/retry
```

Collection example:

```json
{
  "enrollment_id": 1,
  "customer_id": 1,
  "installment_id": 1,
  "payment_mode_id": 1,
  "payment_date": "2026-05-19",
  "amount": 1000,
  "payment_type": "full",
  "remarks": "Mobile app collection"
}
```

Gateway order example:

```json
{
  "provider": "razorpay",
  "amount": 1250,
  "currency": "INR",
  "local_reference": "PAY-APP-0001"
}
```

## Receipt APIs

```http
GET /api/receipts
GET /api/receipts/{receipt}
GET /api/receipts/{receipt}/download
```

## Ledger APIs

```http
GET /api/ledger
GET /api/customers/{customer}/ledger
GET /api/chit-enrollments/{enrollment}/ledger
```

## Pending Dues APIs

```http
GET /api/pending-dues
GET /api/pending-dues/today
GET /api/pending-dues/weekly
GET /api/pending-dues/monthly
GET /api/pending-dues/overdue
GET /api/pending-dues/staff-summary
GET /api/pending-dues/branch-summary
```

## Dashboard and Report APIs

```http
GET /api/reports/dashboard-summary
GET /api/reports/collection-summary
GET /api/reports/pending-summary
GET /api/reports/staff-collection-summary
GET /api/reports/branch-collection-summary
```

## Messaging APIs

```http
POST /api/messages/whatsapp
POST /api/messages/sms
GET /api/messages/notifications
GET /api/messages/whatsapp-logs
GET /api/messages/sms-logs
```

Send example:

```json
{
  "mobile": "919999999999",
  "message_type": "general",
  "message": "Your chit reminder message"
}
```

## Webhook Routes

Public webhooks:

```http
POST /api/webhooks/whatsapp/twilio
GET  /api/webhooks/whatsapp/meta
POST /api/webhooks/whatsapp/meta
POST /api/webhooks/sms/msg91
POST /api/webhooks/sms/textlocal
POST /api/webhooks/payments/razorpay
POST /api/webhooks/payments/pine-labs
POST /api/webhooks/payments/payu
POST /api/webhooks/payments/upi-qr
```

Webhook events are logged in `integration_transactions`.

## API Versioning

Current version is unprefixed `/api`. For future breaking changes, add version groups such as:

```php
Route::prefix('v1')->group(function () {
    // mobile API routes
});
```

Mobile apps should keep the base URL configurable.
