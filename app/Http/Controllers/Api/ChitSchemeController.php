<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ChitSchemeResource;
use App\Models\ChitScheme;
use App\Repositories\ChitSchemeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChitSchemeController extends BaseApiController
{
    public function __construct(
        private readonly ChitSchemeRepository $schemes
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $schemes = $this->schemes->query()
            ->withCount([
                'enrollments',
                'enrollments as active_enrollments_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->where('status', 'active')
            ->when($request->input('scheme_type'), fn ($query, string $type) => $query->where('scheme_type', $type))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Schemes fetched successfully',
            'data' => [
                'schemes' => ChitSchemeResource::collection($schemes->getCollection()),
                'pagination' => [
                    'current_page' => $schemes->currentPage(),
                    'last_page' => $schemes->lastPage(),
                    'per_page' => $schemes->perPage(),
                    'total' => $schemes->total(),
                ],
            ],
        ]);
    }

    public function show(ChitScheme $scheme): JsonResponse
    {
        if ($scheme->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Scheme not found',
                'data' => [],
            ], 404);
        }

        $scheme->loadCount([
            'enrollments',
            'enrollments as active_enrollments_count' => fn ($query) => $query->where('status', 'active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Scheme fetched successfully',
            'data' => [
                'scheme' => new ChitSchemeResource($scheme),
            ],
        ]);
    }
}
