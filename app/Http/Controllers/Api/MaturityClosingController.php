<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChitClosureResource;
use App\Models\ChitClosure;
use App\Models\ChitEnrollment;
use App\Repositories\MaturityClosingRepository;
use App\Services\MaturityClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaturityClosingController extends Controller
{
    public function __construct(
        private readonly MaturityClosingRepository $closings,
        private readonly MaturityClosingService $maturityClosingService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $closures = $this->closings
            ->getForDataTable($request->only([
                'customer_id',
                'enrollment_id',
                'closure_type',
                'status',
                'from_date',
                'to_date',
            ]))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Maturity closings fetched successfully',
            'data' => [
                'closures' => ChitClosureResource::collection($closures->getCollection()),
                'pagination' => [
                    'current_page' => $closures->currentPage(),
                    'last_page' => $closures->lastPage(),
                    'per_page' => $closures->perPage(),
                    'total' => $closures->total(),
                ],
            ],
        ]);
    }

    public function show(ChitClosure $closure): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Maturity closing fetched successfully',
            'data' => [
                'closure' => new ChitClosureResource($closure->load(['customer', 'enrollment.scheme', 'approver', 'creator'])),
            ],
        ]);
    }

    public function calculate(ChitEnrollment $enrollment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Maturity value calculated successfully',
            'data' => $this->maturityClosingService->calculationSummary($enrollment),
        ]);
    }
}
