<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin Login | {{ config('app.name', 'Jewellery Chit') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-page">
        <main class="auth-shell">
            <section class="auth-panel" aria-label="Admin login">
                <aside class="auth-visual">
                    <div class="auth-brand">
                        <div class="auth-brand-mark">
                            <i class="bi bi-gem"></i>
                        </div>
                        <div>
                            <div class="auth-brand-name">Jewellery Chit</div>
                            <div class="auth-brand-subtitle">Maintenance Suite</div>
                        </div>
                    </div>

                    <div class="auth-visual-copy">
                        <span class="auth-eyebrow">Admin panel</span>
                        <h1>Shop chit operations, secured.</h1>
                        <p>Access customer, scheme, collection, receipt, and reporting workflows from one professional console.</p>
                    </div>

                    <div class="auth-role-grid" aria-label="Allowed roles">
                        <div class="auth-role-card">
                            <i class="bi bi-shield-lock"></i>
                            <span>Admin</span>
                        </div>
                        <div class="auth-role-card">
                            <i class="bi bi-person-badge"></i>
                            <span>Manager</span>
                        </div>
                        <div class="auth-role-card">
                            <i class="bi bi-cash-coin"></i>
                            <span>Staff</span>
                        </div>
                    </div>

                    <div class="auth-visual-footer">
                        <span>REST API ready</span>
                        <span>Sanctum auth</span>
                        <span>Role based access</span>
                    </div>
                </aside>

                <div class="auth-form-panel">
                    <div class="auth-form-card">
                        <div class="auth-mobile-brand">
                            <div class="auth-brand-mark">
                                <i class="bi bi-gem"></i>
                            </div>
                            <div>
                                <div class="auth-brand-name">Jewellery Chit</div>
                                <div class="auth-brand-subtitle">Maintenance Suite</div>
                            </div>
                        </div>

                        <div class="auth-form-heading">
                            <span class="auth-eyebrow">Secure sign in</span>
                            <h2>Welcome back</h2>
                            <p>Use your active admin, manager, or staff account.</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success auth-alert" role="alert">
                                <i class="bi bi-check-circle"></i>
                                <span>{{ session('status') }}</span>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger auth-alert" role="alert">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
                            @csrf

                            <div class="auth-field">
                                <label for="email" class="form-label">Email address</label>
                                <div class="auth-input-wrap">
                                    <i class="bi bi-envelope"></i>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="name@example.com"
                                        autocomplete="username"
                                        required
                                        autofocus
                                    >
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="auth-field">
                                <label for="password" class="form-label">Password</label>
                                <div class="auth-input-wrap">
                                    <i class="bi bi-lock"></i>
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="Enter password"
                                        autocomplete="current-password"
                                        required
                                    >
                                    <button type="button" class="auth-password-toggle" data-password-toggle aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="auth-form-options">
                                <div class="form-check">
                                    <input id="remember" type="checkbox" name="remember" class="form-check-input">
                                    <label for="remember" class="form-check-label">Remember me</label>
                                </div>

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}">Forgot password?</a>
                                @endif
                            </div>

                            <button type="submit" class="btn auth-submit">
                                <span>Sign in</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <script>
            document.querySelector('[data-password-toggle]')?.addEventListener('click', function () {
                const password = document.getElementById('password');
                const icon = this.querySelector('i');
                const isHidden = password.type === 'password';

                password.type = isHidden ? 'text' : 'password';
                this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                icon.classList.toggle('bi-eye', !isHidden);
                icon.classList.toggle('bi-eye-slash', isHidden);
            });
        </script>
    </body>
</html>
