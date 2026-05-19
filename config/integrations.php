<?php

return [
    'mode' => env('INTEGRATION_MODE', 'sandbox'),
    'simulate_sandbox' => env('INTEGRATION_SIMULATE_SANDBOX', true),

    'whatsapp' => [
        'default' => env('WHATSAPP_PROVIDER', 'twilio'),
        'providers' => [
            'twilio' => [
                'sid' => env('TWILIO_ACCOUNT_SID'),
                'token' => env('TWILIO_AUTH_TOKEN'),
                'from' => env('TWILIO_WHATSAPP_FROM'),
                'base_url' => env('TWILIO_BASE_URL', 'https://api.twilio.com/2010-04-01'),
                'webhook_secret' => env('TWILIO_WEBHOOK_SECRET'),
            ],
            'meta' => [
                'access_token' => env('META_WHATSAPP_ACCESS_TOKEN'),
                'phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
                'business_account_id' => env('META_WHATSAPP_BUSINESS_ACCOUNT_ID'),
                'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN'),
                'app_secret' => env('META_WHATSAPP_APP_SECRET'),
                'api_version' => env('META_WHATSAPP_API_VERSION', 'v20.0'),
                'base_url' => env('META_GRAPH_BASE_URL', 'https://graph.facebook.com'),
            ],
        ],
    ],

    'sms' => [
        'default' => env('SMS_PROVIDER', 'msg91'),
        'providers' => [
            'msg91' => [
                'auth_key' => env('MSG91_AUTH_KEY'),
                'template_id' => env('MSG91_TEMPLATE_ID'),
                'sender_id' => env('MSG91_SENDER_ID'),
                'route' => env('MSG91_ROUTE', '4'),
                'base_url' => env('MSG91_BASE_URL', 'https://control.msg91.com'),
                'webhook_secret' => env('MSG91_WEBHOOK_SECRET'),
            ],
            'textlocal' => [
                'api_key' => env('TEXTLOCAL_API_KEY'),
                'sender' => env('TEXTLOCAL_SENDER', 'TXTLCL'),
                'base_url' => env('TEXTLOCAL_BASE_URL', 'https://api.textlocal.in'),
                'webhook_secret' => env('TEXTLOCAL_WEBHOOK_SECRET'),
            ],
        ],
    ],

    'payments' => [
        'default' => env('PAYMENT_GATEWAY', 'razorpay'),
        'providers' => [
            'razorpay' => [
                'key_id' => env('RAZORPAY_KEY_ID'),
                'key_secret' => env('RAZORPAY_KEY_SECRET'),
                'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
                'base_url' => env('RAZORPAY_BASE_URL', 'https://api.razorpay.com'),
            ],
            'pine_labs' => [
                'merchant_id' => env('PINE_LABS_MERCHANT_ID'),
                'client_id' => env('PINE_LABS_CLIENT_ID'),
                'client_secret' => env('PINE_LABS_CLIENT_SECRET'),
                'store_id' => env('PINE_LABS_STORE_ID'),
                'security_token' => env('PINE_LABS_SECURITY_TOKEN'),
                'base_url' => env('PINE_LABS_BASE_URL', 'https://api.pluralonline.com'),
            ],
            'payu' => [
                'merchant_key' => env('PAYU_MERCHANT_KEY'),
                'merchant_salt' => env('PAYU_MERCHANT_SALT'),
                'webhook_secret' => env('PAYU_WEBHOOK_SECRET'),
                'base_url' => env('PAYU_BASE_URL', 'https://secure.payu.in'),
                'success_url' => env('PAYU_SUCCESS_URL'),
                'failure_url' => env('PAYU_FAILURE_URL'),
            ],
            'upi_qr' => [
                'vpa' => env('UPI_QR_VPA'),
                'payee_name' => env('UPI_QR_PAYEE_NAME', env('APP_NAME', 'Jewellery Chit')),
                'merchant_code' => env('UPI_QR_MERCHANT_CODE'),
            ],
        ],
    ],
];
