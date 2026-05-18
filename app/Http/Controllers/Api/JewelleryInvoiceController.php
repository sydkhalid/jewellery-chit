<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\JewelleryInvoiceStoreRequest;
use App\Http\Resources\JewelleryInvoiceResource;
use App\Models\JewelleryInvoice;
use App\Repositories\JewelleryInvoiceRepository;
use App\Services\JewelleryInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JewelleryInvoiceController extends Controller
{
    public function __construct(
        private readonly JewelleryInvoiceRepository $invoices,
        private readonly JewelleryInvoiceService $jewelleryInvoiceService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $invoices = $this->invoices
            ->getForDataTable($request->only([
                'customer_id',
                'enrollment_id',
                'status',
                'from_date',
                'to_date',
            ]))
            ->latest('invoice_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Jewellery invoices fetched successfully',
            'data' => [
                'invoices' => JewelleryInvoiceResource::collection($invoices->getCollection()),
                'pagination' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total(),
                ],
            ],
        ]);
    }

    public function store(JewelleryInvoiceStoreRequest $request): JsonResponse
    {
        try {
            $invoice = $this->jewelleryInvoiceService->createInvoice($request->validated());
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
            'message' => 'Jewellery invoice created successfully',
            'data' => [
                'invoice' => new JewelleryInvoiceResource($invoice->load(['customer', 'enrollment.scheme', 'items', 'creator'])),
            ],
        ], 201);
    }

    public function show(JewelleryInvoice $invoice): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Jewellery invoice fetched successfully',
            'data' => [
                'invoice' => new JewelleryInvoiceResource($invoice->load(['customer', 'enrollment.scheme', 'items', 'creator', 'finalizer'])),
            ],
        ]);
    }
}
