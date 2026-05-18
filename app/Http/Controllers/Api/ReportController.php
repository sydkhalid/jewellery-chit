<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports
    ) {
    }

    public function dashboardSummary(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Dashboard report summary fetched successfully',
            'data' => [
                'customers' => $this->reports->summary('customers', $request->all()),
                'active_chits' => $this->reports->summary('active-chits', $request->all()),
                'collections' => $this->reports->summary('collections', $request->all()),
                'pending' => $this->reports->summary('pending', $request->all()),
            ],
        ]);
    }

    public function collectionSummary(Request $request): JsonResponse
    {
        return $this->summaryResponse('Collection summary fetched successfully', 'collections', $request);
    }

    public function pendingSummary(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Pending summary fetched successfully',
            'data' => [
                'pending' => $this->reports->summary('pending', $request->all()),
                'overdue' => $this->reports->summary('overdue', $request->all()),
            ],
        ]);
    }

    public function staffCollectionSummary(Request $request): JsonResponse
    {
        return $this->summaryResponse('Staff collection summary fetched successfully', 'staff', $request);
    }

    public function branchCollectionSummary(Request $request): JsonResponse
    {
        return $this->summaryResponse('Branch collection summary fetched successfully', 'branches', $request);
    }

    private function summaryResponse(string $message, string $type, Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'summary' => $this->reports->summary($type, $request->all()),
                'rows' => $this->reports->rows($type, $request->all()),
            ],
        ]);
    }
}
