<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffCashHandoverStoreRequest;
use App\Http\Resources\StaffCashHandoverResource;
use App\Models\StaffCashHandover;
use App\Repositories\StaffCashHandoverRepository;
use App\Services\StaffBranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffCashHandoverController extends Controller
{
    public function __construct(
        private readonly StaffCashHandoverRepository $handovers,
        private readonly StaffBranchService $staffBranchService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $handovers = $this->handovers
            ->getForDataTable($request->only(['staff_id', 'branch_id', 'status', 'from_date', 'to_date']))
            ->latest('handover_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Staff cash handovers fetched successfully',
            'data' => [
                'handovers' => StaffCashHandoverResource::collection($handovers->getCollection()),
                'pagination' => [
                    'current_page' => $handovers->currentPage(),
                    'last_page' => $handovers->lastPage(),
                    'per_page' => $handovers->perPage(),
                    'total' => $handovers->total(),
                ],
            ],
        ]);
    }

    public function store(StaffCashHandoverStoreRequest $request): JsonResponse
    {
        try {
            $handover = $this->staffBranchService->createCashHandover($request->validated());
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
            'message' => 'Cash handover created successfully',
            'data' => [
                'handover' => new StaffCashHandoverResource($handover),
            ],
        ], 201);
    }

    public function show(StaffCashHandover $handover): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Staff cash handover fetched successfully',
            'data' => [
                'handover' => new StaffCashHandoverResource($handover->load(['staff', 'branch', 'receiver'])),
            ],
        ]);
    }
}
