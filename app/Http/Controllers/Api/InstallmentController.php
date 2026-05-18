<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChitEnrollmentResource;
use App\Http\Resources\ChitInstallmentResource;
use App\Models\ChitEnrollment;
use App\Repositories\InstallmentRepository;
use App\Services\InstallmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    public function __construct(
        private readonly InstallmentRepository $installments,
        private readonly InstallmentService $installmentService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $installments = $this->installments->getForDataTable($request->only([
            'customer_id',
            'enrollment_id',
            'from_date',
            'to_date',
            'status',
            'assigned_staff_id',
            'branch_id',
        ]))
            ->orderBy('due_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Installments fetched successfully',
            'data' => [
                'installments' => ChitInstallmentResource::collection($installments->getCollection()),
                'pagination' => [
                    'current_page' => $installments->currentPage(),
                    'last_page' => $installments->lastPage(),
                    'per_page' => $installments->perPage(),
                    'total' => $installments->total(),
                ],
            ],
        ]);
    }

    public function byEnrollment(ChitEnrollment $enrollment): JsonResponse
    {
        $enrollment->load(['customer', 'scheme', 'branch', 'assignedStaff']);
        $installments = $this->installmentService->getEnrollmentInstallments($enrollment);

        return response()->json([
            'success' => true,
            'message' => 'Installments fetched successfully',
            'data' => [
                'enrollment' => new ChitEnrollmentResource($enrollment),
                'installments' => ChitInstallmentResource::collection($installments),
            ],
        ]);
    }
}
