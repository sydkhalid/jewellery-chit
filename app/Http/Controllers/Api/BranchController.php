<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Repositories\BranchRepository;
use App\Services\StaffBranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchRepository $branches,
        private readonly StaffBranchService $staffBranchService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $branches = $this->branches
            ->getForDataTable($request->only(['status', 'city']))
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Branches fetched successfully',
            'data' => [
                'branches' => BranchResource::collection($branches->getCollection()),
                'pagination' => [
                    'current_page' => $branches->currentPage(),
                    'last_page' => $branches->lastPage(),
                    'per_page' => $branches->perPage(),
                    'total' => $branches->total(),
                ],
            ],
        ]);
    }

    public function show(Branch $branch): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Branch fetched successfully',
            'data' => [
                'branch' => new BranchResource($branch->loadCount(['users', 'enrollments', 'payments'])),
            ],
        ]);
    }

    public function collectionSummary(Branch $branch): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Branch collection summary fetched successfully',
            'data' => [
                'summary' => $this->staffBranchService->getBranchCollectionSummary($branch),
            ],
        ]);
    }
}
