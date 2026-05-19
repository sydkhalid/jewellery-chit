# Real Integrations

Phase 7 adds provider-ready integrations for WhatsApp, SMS, online payment gateways, UPI QR, and webhook logging.

## Mode Switch

Set the integration mode in `.env`:

```env
INTEGRATION_MODE=sandbox
INTEGRATION_SIMULATE_SANDBOX=true
```

Use `sandbox` while testing. When `INTEGRATION_MODE=live` and real credentials are present, the services call the provider APIs. If credentials are missing in sandbox, the system creates simulated successful responses and logs them in `integration_transactions`.

## WhatsApp Providers

Supported providers:

- Twilio WhatsApp: `WHATSAPP_PROVIDER=twilio`
- Meta WhatsApp Business Cloud API: `WHATSAPP_PROVIDER=meta`

Important credentials:

```env
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_WHATSAPP_FROM=
META_WHATSAPP_ACCESS_TOKEN=
META_WHATSAPP_PHONE_NUMBER_ID=
META_WHATSAPP_VERIFY_TOKEN=
META_WHATSAPP_APP_SECRET=
```

Webhook routes:

- `POST /api/webhooks/whatsapp/twilio`
- `GET /api/webhooks/whatsapp/meta`
- `POST /api/webhooks/whatsapp/meta`

## SMS Providers

Supported providers:

- MSG91: `SMS_PROVIDER=msg91`
- Textlocal: `SMS_PROVIDER=textlocal`

Important credentials:

```env
MSG91_AUTH_KEY=
MSG91_TEMPLATE_ID=
MSG91_SENDER_ID=
TEXTLOCAL_API_KEY=
TEXTLOCAL_SENDER=
```

Webhook routes:

- `POST /api/webhooks/sms/msg91`
- `POST /api/webhooks/sms/textlocal`

## Payment Providers

Supported providers:

- Razorpay: `PAYMENT_GATEWAY=razorpay`
- Pine Labs: `PAYMENT_GATEWAY=pine_labs`
- PayU: `PAYMENT_GATEWAY=payu`
- UPI QR: `PAYMENT_GATEWAY=upi_qr`

Important credentials:

```env
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
PINE_LABS_MERCHANT_ID=
PINE_LABS_CLIENT_ID=
PINE_LABS_CLIENT_SECRET=
PINE_LABS_STORE_ID=
PINE_LABS_SECURITY_TOKEN=
PAYU_MERCHANT_KEY=
PAYU_MERCHANT_SALT=
PAYU_WEBHOOK_SECRET=
PAYU_SUCCESS_URL=
PAYU_FAILURE_URL=
UPI_QR_VPA=
UPI_QR_PAYEE_NAME="${APP_NAME}"
UPI_QR_MERCHANT_CODE=
```

Protected API routes:

- `POST /api/payments/gateway/order`
- `POST /api/payments/gateway/{transaction}/retry`

Webhook routes:

- `POST /api/webhooks/payments/razorpay`
- `POST /api/webhooks/payments/pine-labs`
- `POST /api/webhooks/payments/payu`
- `POST /api/webhooks/payments/upi-qr`

## Payment Order Example

```http
POST /api/payments/gateway/order
Accept: application/json
Authorization: Bearer {token}
```

```json
{
  "provider": "razorpay",
  "amount": 1250,
  "currency": "INR",
  "local_reference": "TEST-RAZORPAY-001",
  "customer": {
    "name": "Demo Customer",
    "email": "demo@example.com",
    "mobile": "9999999999"
  }
}
```

## UPI QR Example

```json
{
  "provider": "upi_qr",
  "amount": 500,
  "currency": "INR",
  "local_reference": "UPI-TEST-001",
  "description": "Chit installment payment"
}
```

Response contains `data.transaction.response_payload.upi_uri`.

## Transaction Logs

All provider requests, retries, simulated sandbox calls, and webhooks are stored in:

- `integration_transactions`

Important fields:

- `gateway_type`: `message` or `payment`
- `provider`: `twilio`, `meta`, `msg91`, `textlocal`, `razorpay`, `pine_labs`, `payu`, `upi_qr`
- `mode`: `sandbox` or `live`
- `direction`: `request`, `retry`, or `webhook`
- `status`: `pending`, `success`, or `failed`
- `request_payload`, `response_payload`, `webhook_payload`
- `external_id`, `local_reference`, `retry_count`, `last_error`

## Test Commands

```bash
php artisan migrate
php artisan test tests/Feature/PhaseSevenIntegrationTest.php
```
