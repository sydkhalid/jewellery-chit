<?php

namespace App\Services\Integrations;

use App\Models\IntegrationTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class PaymentGatewayService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createOrder(array $data): IntegrationTransaction
    {
        $provider = (string) ($data['provider'] ?? config('integrations.payments.default'));
        $amount = round((float) $data['amount'], 2);
        $currency = strtoupper((string) ($data['currency'] ?? 'INR'));
        $localReference = (string) ($data['local_reference'] ?? 'PGW-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)));
        $requestPayload = $this->paymentPayload($provider, $amount, $currency, $localReference, $data);

        $transaction = IntegrationTransaction::create([
            'gateway_type' => 'payment',
            'provider' => $provider,
            'mode' => $this->mode(),
            'direction' => 'request',
            'status' => 'pending',
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'local_reference' => $localReference,
            'amount' => $amount,
            'currency' => $currency,
            'request_payload' => $requestPayload,
        ]);

        try {
            $responsePayload = $this->shouldSimulate($provider)
                ? $this->simulatedPaymentResponse($provider, $transaction)
                : $this->dispatchPaymentProvider($provider, $requestPayload);

            $transaction->update([
                'status' => ($responsePayload['success'] ?? false) ? 'pending' : 'failed',
                'external_id' => $responsePayload['external_id'] ?? null,
                'response_payload' => $responsePayload,
                'last_error' => ($responsePayload['success'] ?? false) ? null : (string) ($responsePayload['error'] ?? 'Payment provider returned failure.'),
                'processed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $transaction->update([
                'status' => 'failed',
                'last_error' => $exception->getMessage(),
                'processed_at' => now(),
            ]);
        }

        return $transaction->refresh();
    }

    public function retry(IntegrationTransaction $transaction): IntegrationTransaction
    {
        $data = [
            'provider' => $transaction->provider,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'local_reference' => $transaction->local_reference,
            'reference_type' => $transaction->reference_type,
            'reference_id' => $transaction->reference_id,
        ];

        $retry = $this->createOrder($data);
        $retry->update([
            'direction' => 'retry',
            'retry_count' => (int) $transaction->retry_count + 1,
        ]);

        return $retry->refresh();
    }

    public function handleWebhook(string $provider, Request $request): IntegrationTransaction
    {
        $payload = $request->all();
        $externalId = $this->extractExternalId($provider, $payload);
        $status = $this->mapWebhookStatus($provider, $payload);

        $transaction = IntegrationTransaction::query()
            ->when($externalId, fn ($query) => $query->where('external_id', $externalId))
            ->where('provider', $provider)
            ->latest()
            ->first();

        if (! $transaction) {
            $transaction = IntegrationTransaction::create([
                'gateway_type' => in_array($provider, ['twilio', 'meta', 'msg91', 'textlocal'], true) ? 'message' : 'payment',
                'provider' => $provider,
                'mode' => $this->mode(),
                'direction' => 'webhook',
                'status' => 'pending',
                'external_id' => $externalId,
            ]);
        }

        $transaction->update([
            'direction' => 'webhook',
            'status' => $status,
            'webhook_payload' => $payload,
            'last_error' => $status === 'failed' ? json_encode($payload) : null,
            'processed_at' => now(),
        ]);

        return $transaction->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhookSignature(string $provider, Request $request, array $payload = []): bool
    {
        if ($this->mode() === 'sandbox') {
            return true;
        }

        return match ($provider) {
            'razorpay' => $this->verifyRazorpaySignature($request),
            'meta' => $this->verifyMetaSignature($request),
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function paymentPayload(string $provider, float $amount, string $currency, string $localReference, array $data): array
    {
        return match ($provider) {
            'razorpay' => [
                'amount' => (int) round($amount * 100),
                'currency' => $currency,
                'receipt' => $localReference,
                'notes' => [
                    'reference_type' => $data['reference_type'] ?? null,
                    'reference_id' => $data['reference_id'] ?? null,
                ],
            ],
            'pine_labs' => [
                'merchant_order_reference' => $localReference,
                'amount' => ['value' => (int) round($amount * 100), 'currency' => $currency],
                'purchase_details' => ['customer' => $data['customer'] ?? []],
            ],
            'payu' => $this->payuPayload($amount, $currency, $localReference, $data),
            'upi_qr' => $this->upiPayload($amount, $currency, $localReference, $data),
            default => [
                'amount' => $amount,
                'currency' => $currency,
                'reference' => $localReference,
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function dispatchPaymentProvider(string $provider, array $payload): array
    {
        return match ($provider) {
            'razorpay' => $this->createRazorpayOrder($payload),
            'pine_labs' => $this->createPineLabsOrder($payload),
            'payu' => $this->createPayuOrder($payload),
            'upi_qr' => $this->createUpiQrOrder($payload),
            default => [
                'success' => false,
                'error' => "Unsupported payment provider {$provider}.",
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function createRazorpayOrder(array $payload): array
    {
        $response = Http::withBasicAuth(
            (string) config('integrations.payments.providers.razorpay.key_id'),
            (string) config('integrations.payments.providers.razorpay.key_secret')
        )->post(rtrim((string) config('integrations.payments.providers.razorpay.base_url'), '/').'/v1/orders', $payload);

        $json = $response->json() ?: [];

        return [
            'success' => $response->successful(),
            'external_id' => Arr::get($json, 'id'),
            'raw' => $json ?: $response->body(),
            'http_status' => $response->status(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function createPineLabsOrder(array $payload): array
    {
        $response = Http::withHeaders([
            'client_id' => (string) config('integrations.payments.providers.pine_labs.client_id'),
            'client_secret' => (string) config('integrations.payments.providers.pine_labs.client_secret'),
            'Content-Type' => 'application/json',
        ])->post(rtrim((string) config('integrations.payments.providers.pine_labs.base_url'), '/').'/api/pay/v1/orders', $payload);

        $json = $response->json() ?: [];

        return [
            'success' => $response->successful(),
            'external_id' => Arr::get($json, 'order_id'),
            'raw' => $json ?: $response->body(),
            'http_status' => $response->status(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function createPayuOrder(array $payload): array
    {
        return [
            'success' => true,
            'external_id' => $payload['txnid'],
            'payment_url' => rtrim((string) config('integrations.payments.providers.payu.base_url'), '/').'/_payment',
            'form_payload' => $payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function createUpiQrOrder(array $payload): array
    {
        if (blank($payload['vpa'] ?? null)) {
            return [
                'success' => false,
                'error' => 'UPI VPA is not configured.',
            ];
        }

        return [
            'success' => true,
            'external_id' => $payload['reference'],
            'upi_uri' => $payload['upi_uri'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payuPayload(float $amount, string $currency, string $localReference, array $data): array
    {
        $key = (string) config('integrations.payments.providers.payu.merchant_key');
        $salt = (string) config('integrations.payments.providers.payu.merchant_salt');
        $productInfo = (string) ($data['productinfo'] ?? 'Jewellery Chit Payment');
        $firstName = (string) Arr::get($data, 'customer.name', 'Customer');
        $email = (string) Arr::get($data, 'customer.email', 'customer@example.com');
        $hashString = "{$key}|{$localReference}|{$amount}|{$productInfo}|{$firstName}|{$email}|||||||||||{$salt}";

        return [
            'key' => $key,
            'txnid' => $localReference,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'productinfo' => $productInfo,
            'firstname' => $firstName,
            'email' => $email,
            'phone' => Arr::get($data, 'customer.mobile'),
            'surl' => config('integrations.payments.providers.payu.success_url'),
            'furl' => config('integrations.payments.providers.payu.failure_url'),
            'hash' => hash('sha512', $hashString),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function upiPayload(float $amount, string $currency, string $localReference, array $data): array
    {
        $vpa = (string) config('integrations.payments.providers.upi_qr.vpa');
        $payeeName = (string) config('integrations.payments.providers.upi_qr.payee_name');
        $merchantCode = (string) config('integrations.payments.providers.upi_qr.merchant_code');
        $query = http_build_query(array_filter([
            'pa' => $vpa,
            'pn' => $payeeName,
            'mc' => $merchantCode,
            'tr' => $localReference,
            'tn' => (string) ($data['description'] ?? 'Jewellery Chit Payment'),
            'am' => number_format($amount, 2, '.', ''),
            'cu' => $currency,
        ], fn ($value) => filled($value)));

        return [
            'reference' => $localReference,
            'vpa' => $vpa,
            'payee_name' => $payeeName,
            'amount' => $amount,
            'currency' => $currency,
            'upi_uri' => "upi://pay?{$query}",
        ];
    }

    private function shouldSimulate(string $provider): bool
    {
        if ($provider === 'upi_qr') {
            return false;
        }

        if ($this->mode() === 'sandbox' && (bool) config('integrations.simulate_sandbox', true)) {
            return true;
        }

        return ! $this->hasPaymentCredentials($provider);
    }

    private function hasPaymentCredentials(string $provider): bool
    {
        return match ($provider) {
            'razorpay' => filled(config('integrations.payments.providers.razorpay.key_id'))
                && filled(config('integrations.payments.providers.razorpay.key_secret')),
            'pine_labs' => filled(config('integrations.payments.providers.pine_labs.client_id'))
                && filled(config('integrations.payments.providers.pine_labs.client_secret')),
            'payu' => filled(config('integrations.payments.providers.payu.merchant_key'))
                && filled(config('integrations.payments.providers.payu.merchant_salt')),
            'upi_qr' => filled(config('integrations.payments.providers.upi_qr.vpa')),
            default => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function simulatedPaymentResponse(string $provider, IntegrationTransaction $transaction): array
    {
        return [
            'success' => true,
            'provider' => $provider,
            'mode' => $this->mode(),
            'simulated' => true,
            'external_id' => strtoupper($provider).'-SIM-'.$transaction->id.'-'.now()->format('YmdHis'),
            'message' => "Sandbox {$provider} order created.",
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractExternalId(string $provider, array $payload): ?string
    {
        return match ($provider) {
            'razorpay' => Arr::get($payload, 'payload.payment.entity.order_id') ?? Arr::get($payload, 'payload.payment.entity.id'),
            'pine_labs' => Arr::get($payload, 'order_id') ?? Arr::get($payload, 'payment_id'),
            'payu' => Arr::get($payload, 'mihpayid') ?? Arr::get($payload, 'txnid'),
            'upi_qr' => Arr::get($payload, 'tr') ?? Arr::get($payload, 'transaction_reference'),
            default => Arr::get($payload, 'id') ?? Arr::get($payload, 'message_id'),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mapWebhookStatus(string $provider, array $payload): string
    {
        $event = strtolower((string) (Arr::get($payload, 'event') ?? Arr::get($payload, 'status') ?? Arr::get($payload, 'payment_status') ?? 'success'));

        if (str_contains($event, 'fail') || str_contains($event, 'cancel') || str_contains($event, 'bounce')) {
            return 'failed';
        }

        if (str_contains($event, 'captured') || str_contains($event, 'success') || str_contains($event, 'paid')) {
            return 'success';
        }

        return 'pending';
    }

    private function verifyRazorpaySignature(Request $request): bool
    {
        $secret = (string) config('integrations.payments.providers.razorpay.webhook_secret');

        if ($secret === '') {
            return true;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, (string) $request->header('X-Razorpay-Signature'));
    }

    private function verifyMetaSignature(Request $request): bool
    {
        $secret = (string) config('integrations.whatsapp.providers.meta.app_secret');

        if ($secret === '') {
            return true;
        }

        $header = (string) $request->header('X-Hub-Signature-256');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $header);
    }

    private function mode(): string
    {
        return (string) config('integrations.mode', 'sandbox');
    }
}
