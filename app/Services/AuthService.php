<?php

namespace App\Services;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * @param  array<int, string>  $allowedRoles
     *
     * @throws ValidationException
     */
    public function authenticateWebUser(LoginRequest $request, array $allowedRoles = ['Admin', 'Manager', 'Staff']): User
    {
        $request->ensureIsNotRateLimited();

        if (! Auth::guard('web')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::guard('web')->user();

        if (! $user instanceof User || $user->status !== 'active' || ! $user->hasAnyRole($allowedRoles) || ! $user->can('dashboard.view')) {
            Auth::guard('web')->logout();
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'This account is not allowed to access the admin panel.',
            ]);
        }

        RateLimiter::clear($request->throttleKey());
        $request->session()->regenerate();

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    public function issueToken(string $email, string $password, array $allowedRoles = ['Admin', 'Manager', 'Staff']): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid login credentials',
                'status' => 422,
            ];
        }

        if ($user->status !== 'active') {
            return [
                'success' => false,
                'message' => 'This account is inactive',
                'status' => 403,
            ];
        }

        if (! $user->hasAnyRole($allowedRoles)) {
            return [
                'success' => false,
                'message' => 'User does not have access to this application',
                'status' => 403,
            ];
        }

        return [
            'success' => true,
            'message' => 'Login successful',
            'status' => 200,
            'token' => $user->createToken('mobile-api')->plainTextToken,
            'user' => $user,
        ];
    }

    public function logoutWebUser(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function revokeCurrentToken(mixed $accessToken): void
    {
        $accessToken?->delete();
    }
}
