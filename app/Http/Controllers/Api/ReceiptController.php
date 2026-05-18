<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChitReceiptResource;
use App\Models\ChitReceipt;
use App\Repositories\ReceiptRepository;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReceiptController extends Controller
{
    public function __construct(
        private readonly ReceiptRepository $receipts,
        private readonly ReceiptService $receiptService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $receipts = $this->receipts->getForDataTable($request->only([
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
            'message' => 'Receipts fetched successfully',
            'data' => [
                'receipts' => ChitReceiptResource::collection($receipts->getCollection()),
                'pagination' => [
                    'current_page' => $receipts->currentPage(),
                    'last_page' => $receipts->lastPage(),
                    'per_page' => $receipts->perPage(),
                    'total' => $receipts->total(),
                ],
            ],
        ]);
    }

    public function show(ChitReceipt $receipt): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Receipt fetched successfully',
            'data' => [
                'receipt' => new ChitReceiptResource($receipt->load($this->relations())),
            ],
        ]);
    }

    public function download(ChitReceipt $receipt): BinaryFileResponse|JsonResponse
    {
        try {
            $path = $this->receiptService->generatePdf($receipt);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $exception->errors(),
                ],
            ], 422);
        }

        return response()->download(Storage::disk('public')->path($path), $receipt->receipt_no.'.pdf');
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'customer',
            'enrollment.scheme',
            'payment.paymentMode',
            'payment.staff',
            'payment.branch',
            'payment.allocations.installment',
        ];
    }
}
