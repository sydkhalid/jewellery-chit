<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

class BaseApiController extends Controller
{
    /**
     * @param  array<string, mixed>|array<int, mixed>  $data
     */
    protected function sendSuccess(array $data = [], string $message = 'Success', int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $errors
     */
    protected function sendError(string $message = 'Something went wrong', array $errors = [], int $status = 400): JsonResponse
    {
        return ApiResponse::error($message, $errors, $status);
    }

    /**
     * @param  class-string<JsonResource>  $resourceClass
     * @param  array<string, mixed>  $extra
     */
    protected function sendPaginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message = 'Data fetched successfully',
        array $extra = []
    ): JsonResponse {
        $items = $resourceClass::collection($paginator->getCollection())->resolve(request());

        return ApiResponse::success(
            $extra === [] ? $items : array_merge($extra, ['items' => $items]),
            $message,
            200,
            $this->paginationMeta($paginator)
        );
    }

    /**
     * @return array<string, int>
     */
    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    protected function sendValidationError(ValidationException $exception): JsonResponse
    {
        return ApiResponse::validationError($exception->errors());
    }
}
