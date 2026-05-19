<?php

namespace App\Services\Integrations;

use App\Models\IntegrationTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

class MessageIntegrationService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function send(string $channel, array $data, ?Model $reference = null, bool $isRetry = false): array
    {
        $provider = $this->providerForChannel($channel);
        $requestPayload = $this->messagePayload($channel, $provider, $data);

        $transaction = IntegrationTransaction::create([
            'gateway_type' => 'message',
            'provider' => $provider,
            'mode' => $this->mode(),
            'direction' => $isRetry ? 'retry' : 'request',
            'status' => 'pending',
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'local_reference' => (string) ($data['mobile'] ?? ''),
            'request_payload' => $requestPayload,
            'retry_count' => $isRetry ? 1 : 0,
        ]);

        try {
            $responsePayload = $this->shouldSimulate($channel, $provider)
                ? $this->simulatedResponse($channel, $provider, $data)
                : $this->dispatchToProvider($channel, $provider, $requestPayload);

            $success = (bool) ($responsePayload['success'] ?? false);

            $transaction->update([
                'status' => $success ? 'success' : 'failed',
                'external_id' => $responsePayload['external_id'] ?? null,
                'response_payload' => $responsePayload,
                'last_error' => $success ? null : (string) ($responsePayload['error'] ?? 'Provider returned failure.'),
                'processed_at' => now(),
            ]);

            return $responsePayload + [
                'success' => $success,
                'provider' => $provider,
                'mode' => $this->mode(),
                'transaction_id' => $transaction->id,
            ];
        } catch (Throwable $exception) {
            $transaction->update([
                'status' => 'failed',
                'last_error' => $exception->getMessage(),
                'processed_at' => now(),
            ]);

            return [
                'success' => false,
                'provider' => $provider,
                'mode' => $this->mode(),
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function providerForChannel(string $channel): string
    {
        return (string) config($channel === 'whatsapp'
            ? 'integrations.whatsapp.default'
            : 'integrations.sms.default');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function messagePayload(string $channel, string $provider, array $data): array
    {
        return match ("{$channel}:{$provider}") {
            'whatsapp:twilio' => [
                'from' => 'whatsapp:'.ltrim((string) config('integrations.whatsapp.providers.twilio.from'), '+'),
                'to' => 'whatsapp:'.$this->normalizeInternationalMobile((string) $data['mobile']),
                'body' => $data['message'],
            ],
            'whatsapp:meta' => [
                'messaging_product' => 'whatsapp',
                'to' => $this->normalizeDigits((string) $data['mobile']),
                'type' => 'text',
                'text' => ['body' => $data['message']],
            ],
            'sms:msg91' => [
                'template_id' => config('integrations.sms.providers.msg91.template_id'),
                'short_url' => '0',
                'recipients' => [[
                    'mobiles' => $this->normalizeDigits((string) $data['mobile']),
                    'message' => $data['message'],
                ]],
            ],
            'sms:textlocal' => [
                'numbers' => $this->normalizeDigits((string) $data['mobile']),
                'message' => $data['message'],
                'sender' => config('integrations.sms.providers.textlocal.sender'),
            ],
            default => [
                'mobile' => $data['mobile'],
                'message' => $data['message'],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function dispatchToProvider(string $channel, string $provider, array $payload): array
    {
        return match ("{$channel}:{$provider}") {
            'whatsapp:twilio' => $this->sendTwilioWhatsapp($payload),
            'whatsapp:meta' => $this->sendMetaWhatsapp($payload),
            'sms:msg91' => $this->sendMsg91Sms($payload),
            'sms:textlocal' => $this->sendTextlocalSms($payload),
            default => [
                'success' => false,
                'error' => "Unsupported {$channel} provider {$provider}.",
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sendTwilioWhatsapp(array $payload): array
    {
        $sid = (string) config('integrations.whatsapp.providers.twilio.sid');
        $token = (string) config('integrations.whatsapp.providers.twilio.token');
        $url = rtrim((string) config('integrations.whatsapp.providers.twilio.base_url'), '/')."/Accounts/{$sid}/Messages.json";
        $response = Http::withBasicAuth($sid, $token)->asForm()->post($url, [
            'From' => $payload['from'],
            'To' => $payload['to'],
            'Body' => $payload['body'],
        ]);

        return [
            'success' => $response->successful(),
            'external_id' => Arr::get($response->json() ?: [], 'sid'),
            'raw' => $response->json() ?: $response->body(),
            'http_status' => $response->status(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sendMetaWhatsapp(array $payload): array
    {
        $phoneNumberId = (string) config('integrations.whatsapp.providers.meta.phone_number_id');
        $version = (string) config('integrations.whatsapp.providers.meta.api_version');
        $url = rtrim((string) config('integrations.whatsapp.providers.meta.base_url'), '/')."/{$version}/{$phoneNumberId}/messages";
        $response = Http::withToken((string) config('integrations.whatsapp.providers.meta.access_token'))->post($url, $payload);
        $json = $response->json() ?: [];

        return [
            'success' => $response->successful(),
            'external_id' => Arr::get($json, 'messages.0.id'),
            'raw' => $json ?: $response->body(),
            'http_status' => $response->status(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sendMsg91Sms(array $payload): array
    {
        $url = rtrim((string) config('integrations.sms.providers.msg91.base_url'), '/').'/api/v5/flow/';
        $response = Http::withHeaders([
            'authkey' => (string) config('integrations.sms.providers.msg91.auth_key'),
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        return [
            'success' => $response->successful(),
            'external_id' => Arr::get($response->json() ?: [], 'request_id'),
            'raw' => $response->json() ?: $response->body(),
            'http_status' => $response->status(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sendTextlocalSms(array $payload): array
    {
        $url = rtrim((string) config('integrations.sms.providers.textlocal.base_url'), '/').'/send/';
        $response = Http::asForm()->post($url, [
            'apikey' => (string) config('integrations.sms.providers.textlocal.api_key'),
            'numbers' => $payload['numbers'],
            'message' => $payload['message'],
            'sender' => $payload['sender'],
        ]);

        $json = $response->json() ?: [];

        return [
            'success' => $response->successful() && Arr::get($json, 'status') !== 'failure',
            'external_id' => Arr::get($json, 'batch_id'),
            'raw' => $json ?: $response->body(),
            'http_status' => $response->status(),
        ];
    }

    private function shouldSimulate(string $channel, string $provider): bool
    {
        if ($this->mode() === 'sandbox' && (bool) config('integrations.simulate_sandbox', true)) {
            return true;
        }

        return ! $this->hasCredentials($channel, $provider);
    }

    private function hasCredentials(string $channel, string $provider): bool
    {
        return match ("{$channel}:{$provider}") {
            'whatsapp:twilio' => filled(config('integrations.whatsapp.providers.twilio.sid'))
                && filled(config('integrations.whatsapp.providers.twilio.token'))
                && filled(config('integrations.whatsapp.providers.twilio.from')),
            'whatsapp:meta' => filled(config('integrations.whatsapp.providers.meta.access_token'))
                && filled(config('integrations.whatsapp.providers.meta.phone_number_id')),
            'sms:msg91' => filled(config('integrations.sms.providers.msg91.auth_key'))
                && filled(config('integrations.sms.providers.msg91.template_id')),
            'sms:textlocal' => filled(config('integrations.sms.providers.textlocal.api_key')),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function simulatedResponse(string $channel, string $provider, array $data): array
    {
        return [
            'success' => true,
            'provider' => $provider,
            'channel' => $channel,
            'mode' => $this->mode(),
            'simulated' => true,
            'external_id' => strtoupper($channel).'-SIM-'.now()->format('YmdHis').'-'.random_int(1000, 9999),
            'mobile' => $data['mobile'],
            'message' => "Sandbox {$provider} {$channel} message accepted.",
        ];
    }

    private function mode(): string
    {
        return (string) config('integrations.mode', 'sandbox');
    }

    private function normalizeInternationalMobile(string $mobile): string
    {
        $mobile = preg_replace('/[^\d+]/', '', $mobile) ?: '';

        return str_starts_with($mobile, '+') ? $mobile : '+'.$mobile;
    }

    private function normalizeDigits(string $mobile): string
    {
        return preg_replace('/\D+/', '', $mobile) ?: $mobile;
    }
}
