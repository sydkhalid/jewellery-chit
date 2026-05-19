<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\IntegrationTransactionResource;
use App\Services\Integrations\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationWebhookController extends BaseApiController
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGateway
    ) {
    }

    public function metaWhatsappVerify(Request $request): JsonResponse|string
    {
        $verifyToken = (string) config('integrations.whatsapp.providers.meta.verify_token');

        $mode = (string) ($request->query('hub_mode') ?? $request->query->get('hub.mode'));
        $token = (string) ($request->query('hub_verify_token') ?? $request->query->get('hub.verify_token'));
        $challenge = (string) ($request->query('hub_challenge') ?? $request->query->get('hub.challenge'));

        if ($mode === 'subscribe' && hash_equals($verifyToken, $token)) {
            return $challenge;
        }

        return $this->sendError('Meta WhatsApp webhook verification failed.', [], 403);
    }

    public function twilioWhatsapp(Request $request): JsonResponse
    {
        return $this->webhookResponse('twilio', $request, 'Twilio WhatsApp webhook processed successfully');
    }

    public function metaWhatsapp(Request $request): JsonResponse
    {
        if (! $this->paymentGateway->verifyWebhookSignature('meta', $request)) {
            return $this->sendError('Invalid Meta WhatsApp signature.', [], 403);
        }

        return $this->webhookResponse('meta', $request, 'Meta WhatsApp webhook processed successfully');
    }

    public function msg91Sms(Request $request): JsonResponse
    {
        return $this->webhookResponse('msg91', $request, 'MSG91 SMS webhook processed successfully');
    }

    public function textlocalSms(Request $request): JsonResponse
    {
        return $this->webhookResponse('textlocal', $request, 'Textlocal SMS webhook processed successfully');
    }

    public function razorpay(Request $request): JsonResponse
    {
        if (! $this->paymentGateway->verifyWebhookSignature('razorpay', $request)) {
            return $this->sendError('Invalid Razorpay signature.', [], 403);
        }

        return $this->webhookResponse('razorpay', $request, 'Razorpay webhook processed successfully');
    }

    public function pineLabs(Request $request): JsonResponse
    {
        return $this->webhookResponse('pine_labs', $request, 'Pine Labs webhook processed successfully');
    }

    public function payu(Request $request): JsonResponse
    {
        return $this->webhookResponse('payu', $request, 'PayU webhook processed successfully');
    }

    public function upiQr(Request $request): JsonResponse
    {
        return $this->webhookResponse('upi_qr', $request, 'UPI QR webhook processed successfully');
    }

    private function webhookResponse(string $provider, Request $request, string $message): JsonResponse
    {
        $transaction = $this->paymentGateway->handleWebhook($provider, $request);

        return $this->sendSuccess([
            'transaction' => new IntegrationTransactionResource($transaction),
        ], $message);
    }
}
