<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PendingDueResource;
use App\Services\PendingDueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingDueController extends BaseApiController
{
    public function __construct(
        private readonly PendingDueService $pendingDueService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->paginatedResponse($request, $request->only([
            'due_type',
            'customer_id',
            'staff_id',
            'branch_id',
            'scheme_id',
            'from_date',
            'to_date',
            'status',
            'followup_status',
        ]), 'Pending dues fetched successfully');
    }

    public function today(Request $request): JsonResponse
    {
        return $this->paginatedResponse($request, $this->filtersWithDueType($request, 'today'), 'Today dues fetched successfully');
    }

    public function weekly(Request $request): JsonResponse
    {
        return $this->paginatedResponse($request, $this->filtersWithDueType($request, 'weekly'), 'Weekly dues fetched successfully');
    }

    public function monthly(Request $request): JsonResponse
    {
        return $this->paginatedResponse($request, $this->filtersWithDueType($request, 'monthly'), 'Monthly dues fetched successfully');
    }

    public function overdue(Request $request): JsonResponse
    {
        return $this->paginatedResponse($request, $this->filtersWithDueType($request, 'overdue'), 'Overdue dues fetched successfully');
    }

    public function staffSummary(Request $request): JsonResponse
    {
        $staffId = $request->input('staff_id');

        if ($staffId) {
            $dues = $this->pendingDueService->getStaffWisePending($staffId);

            return response()->json([
                'success' => true,
                'message' => 'Staff-wise pending dues fetched successfully',
                'data' => [
                    'staff_id' => (int) $staffId,
                    'summary' => [
                        'count' => $dues->count(),
                        'total_balance' => round((float) $dues->sum('balance_amount'), 2),
                    ],
                    'dues' => PendingDueResource::collection($dues),
                ],
            ]);
        }

        $summary = $this->pendingDueService->getPendingDuesQuery()
            ->get()
            ->groupBy(fn ($installment): string => (string) ($installment->enrollment?->assigned_staff_id ?? 'unassigned'))
            ->map(fn ($items, string $staffId): array => [
                'staff_id' => $staffId === 'unassigned' ? null : (int) $staffId,
                'staff_name' => $items->first()?->enrollment?->assignedStaff?->name ?? 'Unassigned',
                'count' => $items->count(),
                'total_balance' => round((float) $items->sum('balance_amount'), 2),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Staff-wise pending summary fetched successfully',
            'data' => [
                'summary' => $summary,
            ],
        ]);
    }

    public function branchSummary(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id');

        if ($branchId) {
            $dues = $this->pendingDueService->getBranchWisePending($branchId);

            return response()->json([
                'success' => true,
                'message' => 'Branch-wise pending dues fetched successfully',
                'data' => [
                    'branch_id' => (int) $branchId,
                    'summary' => [
                        'count' => $dues->count(),
                        'total_balance' => round((float) $dues->sum('balance_amount'), 2),
                    ],
                    'dues' => PendingDueResource::collection($dues),
                ],
            ]);
        }

        $summary = $this->pendingDueService->getPendingDuesQuery()
            ->get()
            ->groupBy(fn ($installment): string => (string) ($installment->enrollment?->branch_id ?? 'unassigned'))
            ->map(fn ($items, string $branchId): array => [
                'branch_id' => $branchId === 'unassigned' ? null : (int) $branchId,
                'branch_name' => $items->first()?->enrollment?->branch?->name ?? 'Unassigned',
                'count' => $items->count(),
                'total_balance' => round((float) $items->sum('balance_amount'), 2),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Branch-wise pending summary fetched successfully',
            'data' => [
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function paginatedResponse(Request $request, array $filters, string $message): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $dues = $this->pendingDueService
            ->getPendingDuesQuery($filters)
            ->orderBy('due_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'summary' => $this->pendingDueService->calculateDueSummary($filters),
                'dues' => PendingDueResource::collection($dues->getCollection()),
                'pagination' => [
                    'current_page' => $dues->currentPage(),
                    'last_page' => $dues->lastPage(),
                    'per_page' => $dues->perPage(),
                    'total' => $dues->total(),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersWithDueType(Request $request, string $dueType): array
    {
        return array_replace($request->only([
            'customer_id',
            'staff_id',
            'branch_id',
            'scheme_id',
            'from_date',
            'to_date',
            'status',
            'followup_status',
        ]), ['due_type' => $dueType]);
    }
}
