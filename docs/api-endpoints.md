# API Endpoints

All mobile API routes use Sanctum token authentication and return the standard response envelope.

## Headers

```http
Accept: application/json
Authorization: Bearer {token}
```

## Authentication

```http
POST /api/login
POST /api/logout
GET /api/profile
```

Login body:

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

Success response:

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "plain-text-token",
    "user": {}
  }
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

Unauthorized:

```json
{
  "success": false,
  "message": "Unauthorized",
  "errors": []
}
```

## Protected Routes

- Customers: `GET /api/customers`, `POST /api/customers`, `GET /api/customers/{customer}`, `PUT /api/customers/{customer}`, `DELETE /api/customers/{customer}`
- Schemes: `GET /api/schemes`, `GET /api/schemes/{scheme}`
- Enrollments: `GET /api/chit-enrollments`, `POST /api/chit-enrollments`, `GET /api/chit-enrollments/{enrollment}`
- Installments: `GET /api/installments`, `GET /api/chit-enrollments/{enrollment}/installments`
- Payments: `GET /api/payments`, `POST /api/payments`, `GET /api/payments/{payment}`
- Payment gateways: `POST /api/payments/gateway/order`, `POST /api/payments/gateway/{transaction}/retry`
- Receipts: `GET /api/receipts`, `GET /api/receipts/{receipt}`, `GET /api/receipts/{receipt}/download`
- Ledger: `GET /api/ledger`, `GET /api/customers/{customer}/ledger`, `GET /api/chit-enrollments/{enrollment}/ledger`
- Pending dues: `GET /api/pending-dues`, `GET /api/pending-dues/today`, `GET /api/pending-dues/weekly`, `GET /api/pending-dues/monthly`, `GET /api/pending-dues/overdue`
- Maturity: `GET /api/maturity-closings`, `GET /api/maturity-closings/{closure}`, `GET /api/chit-enrollments/{enrollment}/maturity-calculate`
- Jewellery invoices: `GET /api/jewellery-invoices`, `POST /api/jewellery-invoices`, `GET /api/jewellery-invoices/{invoice}`
- Gold rates: `GET /api/gold-rates`, `GET /api/gold-rates/latest`, `GET /api/gold-rates/{goldRate}`
- Staff and branch: `GET /api/branches`, `GET /api/branches/{branch}`, `GET /api/staff`, `GET /api/staff/{staff}`
- Cashbook: `GET /api/cashbooks`, `GET /api/cashbooks/{cashbook}`, summary endpoints under `/api/cashbooks/*`
- Reports: dashboard, collection, pending, staff collection, and branch collection summaries under `/api/reports/*`
- Messages: `POST /api/messages/whatsapp`, `POST /api/messages/sms`, log and notification lists under `/api/messages/*`
- Settings: `GET /api/settings`, `GET /api/settings/{key}`

## Public Webhooks

- WhatsApp: `POST /api/webhooks/whatsapp/twilio`, `GET|POST /api/webhooks/whatsapp/meta`
- SMS: `POST /api/webhooks/sms/msg91`, `POST /api/webhooks/sms/textlocal`
- Payments: `POST /api/webhooks/payments/razorpay`, `POST /api/webhooks/payments/pine-labs`, `POST /api/webhooks/payments/payu`, `POST /api/webhooks/payments/upi-qr`

See `docs/integrations.md` for provider credentials, sandbox/live mode, and request examples.

Paginated list response:

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
