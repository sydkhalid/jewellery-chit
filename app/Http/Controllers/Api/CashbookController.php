<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CashbookResource;
use App\Models\Cashbook;
use App\Repositories\CashbookRepository;
use App\Services\CashflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbookController extends Controller
{
    public function __construct(
        private readonly CashbookRepository $cashbooks,
        private readonly CashflowService $cashflowService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $cashbooks = $this->cashbooks
            ->getForDataTable($request->only(['branch_id', 'transaction_type', 'payment_mode_id', 'from_date', 'to_date']))
            ->latest('cashbook_date')
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Cashbook entries fetched successfully',
            'data' => [
                'cashbooks' => CashbookResource::collection($cashbooks->getCollection()),
                'pagination' => [
                    'current_page' => $cashbooks->currentPage(),
                    'last_page' => $cashbooks->lastPage(),
                    'per_page' => $cashbooks->perPage(),
                    'total' => $cashbooks->total(),
                ],
            ],
        ]);
    }

    public function show(Cashbook $cashbook): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Cashbook entry fetched successfully',
            'data' => [
                'cashbook' => new CashbookResource($cashbook->load(['branch', 'paymentMode', 'creator'])),
            ],
        ]);
    }

    public function dailySummary(Request $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());

        return response()->json([
            'success' => true,
            'message' => 'Daily cashflow summary fetched successfully',
            'data' => [
                'summary' => $this->cashflowService->calculateDailyCashflow($date, $request->input('branch_id')),
            ],
        ]);
    }

    public function dateRangeSummary(Request $request): JsonResponse
    {
        $from = $request->input('from_date', today()->toDateString());
        $to = $request->input('to_date', today()->toDateString());

        return response()->json([
            'success' => true,
            'message' => 'Date range cashflow summary fetched successfully',
            'data' => [
                'summary' => $this->cashflowService->calculateDateRangeCashflow($from, $to, $request->input('branch_id')),
                'branch_summary' => $this->cashflowService->getBranchWiseCashflow($from, $to),
            ],
        ]);
    }

    public function paymentModeSummary(Request $request): JsonResponse
    {
        $from = $request->input('from_date', today()->toDateString());
        $to = $request->input('to_date', today()->toDateString());

        return response()->json([
            'success' => true,
            'message' => 'Payment mode cashflow summary fetched successfully',
            'data' => [
                'payment_modes' => $this->cashflowService->getPaymentModeSummary($from, $to, $request->input('branch_id')),
            ],
        ]);
    }
}
