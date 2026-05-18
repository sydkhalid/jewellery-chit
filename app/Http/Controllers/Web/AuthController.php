<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    public function showLogin(): View
    {
        return view('web.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $this->authService->authenticateWebUser($request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logoutWebUser($request);

        return redirect()->route('login');
    }
}
