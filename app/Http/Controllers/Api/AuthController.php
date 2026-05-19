<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    /**
     * Authenticate an admin or staff user and issue a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->validated();
        $result = $this->authService->issueToken(
            $credentials['email'],
            $credentials['password']
        );

        if (! $result['success']) {
            $request->hitRateLimiter();

            return $this->sendError($result['message'], [], $result['status']);
        }

        $request->clearRateLimiter();

        return $this->sendSuccess([
                'token' => $result['token'],
                'expires_at' => $result['expires_at'] ?? null,
                'user' => new UserResource($result['user']),
        ], 'Login successful');
    }

    /**
     * Revoke the current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->revokeCurrentToken(
            $request->user()?->currentAccessToken()
        );

        return $this->sendSuccess([], 'Logout successful');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->sendSuccess([
            'user' => new UserResource($request->user()),
        ], 'Authenticated user fetched successfully');
    }

    public function user(Request $request): JsonResponse
    {
        return $this->profile($request);
    }
}
