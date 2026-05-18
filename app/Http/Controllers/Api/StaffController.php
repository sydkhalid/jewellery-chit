<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\StaffResource;
use App\Models\User;
use App\Repositories\StaffRepository;
use App\Services\StaffBranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends BaseApiController
{
    public function __construct(
        private readonly StaffRepository $staffRepository,
        private readonly StaffBranchService $staffBranchService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $staff = $this->staffRepository
            ->getForDataTable($request->only(['branch_id', 'status', 'role']))
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Staff users fetched successfully',
            'data' => [
                'staff' => StaffResource::collection($staff->getCollection()),
                'pagination' => [
                    'current_page' => $staff->currentPage(),
                    'last_page' => $staff->lastPage(),
                    'per_page' => $staff->perPage(),
                    'total' => $staff->total(),
                ],
            ],
        ]);
    }

    public function show(User $staff): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Staff user fetched successfully',
            'data' => [
                'staff' => new StaffResource($staff->load(['branch', 'roles'])->loadCount(['staffCollections', 'assignedChitEnrollments', 'staffCashHandovers'])),
            ],
        ]);
    }

    public function collectionSummary(User $staff): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Staff collection summary fetched successfully',
            'data' => [
                'summary' => $this->staffBranchService->getStaffCollectionSummary($staff),
            ],
        ]);
    }
}
