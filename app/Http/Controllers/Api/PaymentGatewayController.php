<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PaymentGatewayOrderRequest;
use App\Http\Resources\IntegrationTransactionResource;
use App\Models\IntegrationTransaction;
use App\Services\Integrations\PaymentGatewayService;
use Illuminate\Http\JsonResponse;

class PaymentGatewayController extends BaseApiController
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGateway
    ) {
    }

    public function createOrder(PaymentGatewayOrderRequest $request): JsonResponse
    {
        $transaction = $this->paymentGateway->createOrder($request->validated());

        return $this->sendSuccess([
            'transaction' => new IntegrationTransactionResource($transaction),
        ], 'Payment gateway order created successfully');
    }

    public function retry(IntegrationTransaction $transaction): JsonResponse
    {
        if ($transaction->gateway_type !== 'payment') {
            return $this->sendError('Only payment transactions can be retried.', [], 422);
        }

        $retry = $this->paymentGateway->retry($transaction);

        return $this->sendSuccess([
            'transaction' => new IntegrationTransactionResource($retry),
        ], 'Payment gateway order retried successfully');
    }
}
