<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GoldRateResource;
use App\Models\GoldRate;
use App\Services\GoldRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoldRateController extends Controller
{
    public function __construct(
        private readonly GoldRateService $goldRateService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $rates = $this->goldRateService
            ->getRateHistory($request->only(['rate_date', 'status', 'rate_locked', 'from_date', 'to_date']))
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Gold rates fetched successfully',
            'data' => [
                'rates' => GoldRateResource::collection($rates->getCollection()),
                'pagination' => [
                    'current_page' => $rates->currentPage(),
                    'last_page' => $rates->lastPage(),
                    'per_page' => $rates->perPage(),
                    'total' => $rates->total(),
                ],
            ],
        ]);
    }

    public function latest(): JsonResponse
    {
        $rate = $this->goldRateService->getLatestApprovedRate();

        return response()->json([
            'success' => true,
            'message' => $rate ? 'Latest approved gold rate fetched successfully' : 'No approved gold rate found',
            'data' => [
                'rate' => $rate ? new GoldRateResource($rate->load(['creator', 'approver'])) : null,
            ],
        ]);
    }

    public function show(GoldRate $goldRate): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Gold rate fetched successfully',
            'data' => [
                'rate' => new GoldRateResource($goldRate->load(['creator', 'approver'])),
            ],
        ]);
    }
}
