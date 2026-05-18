<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ChitEnrollmentStoreRequest;
use App\Http\Resources\ChitEnrollmentResource;
use App\Models\ChitEnrollment;
use App\Repositories\ChitEnrollmentRepository;
use App\Services\ChitEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChitEnrollmentController extends BaseApiController
{
    public function __construct(
        private readonly ChitEnrollmentRepository $enrollments,
        private readonly ChitEnrollmentService $enrollmentService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $enrollments = $this->enrollments->query()
            ->with(['customer', 'scheme', 'branch', 'assignedStaff'])
            ->withCount('payments')
            ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Enrollments fetched successfully',
            'data' => [
                'enrollments' => ChitEnrollmentResource::collection($enrollments->getCollection()),
                'pagination' => [
                    'current_page' => $enrollments->currentPage(),
                    'last_page' => $enrollments->lastPage(),
                    'per_page' => $enrollments->perPage(),
                    'total' => $enrollments->total(),
                ],
            ],
        ]);
    }

    public function store(ChitEnrollmentStoreRequest $request): JsonResponse
    {
        try {
            $enrollment = $this->enrollmentService->createEnrollment($request->validated());
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
            'message' => 'Enrollment created successfully',
            'data' => $this->enrollmentService->prepareApiEnrollmentData($enrollment),
        ], 201);
    }

    public function show(ChitEnrollment $enrollment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Enrollment fetched successfully',
            'data' => $this->enrollmentService->prepareApiEnrollmentData($enrollment),
        ]);
    }
}
