<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PaymentStoreRequest;
use App\Http\Resources\ChitPaymentResource;
use App\Models\ChitPayment;
use App\Repositories\PaymentRepository;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends BaseApiController
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly PaymentService $paymentService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $payments = $this->payments->getForDataTable($request->only([
            'customer_id',
            'enrollment_id',
            'payment_mode_id',
            'staff_id',
            'branch_id',
            'from_date',
            'to_date',
            'status',
        ]))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Payments fetched successfully',
            'data' => [
                'payments' => ChitPaymentResource::collection($payments->getCollection()),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ],
            ],
        ]);
    }

    public function store(PaymentStoreRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->collectPayment($request->validated());
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $exception->errors(),
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment collected successfully',
            'data' => [
                'payment' => new ChitPaymentResource($payment),
            ],
        ], 201);
    }

    public function show(ChitPayment $payment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Payment fetched successfully',
            'data' => [
                'payment' => new ChitPaymentResource($payment->load(['customer', 'enrollment.scheme', 'paymentMode', 'branch', 'staff', 'receipt', 'allocations.installment'])),
            ],
        ]);
    }
}
