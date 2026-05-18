<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
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
        $credentials = $request->validated();
        $result = $this->authService->issueToken(
            $credentials['email'],
            $credentials['password']
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => [],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $result['token'],
                'user' => new UserResource($result['user']),
            ],
        ]);
    }

    /**
     * Revoke the current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->revokeCurrentToken(
            $request->user()?->currentAccessToken()
        );

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
            'data' => [],
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user fetched successfully',
            'data' => [
                'user' => new UserResource($request->user()),
            ],
        ]);
    }
}
