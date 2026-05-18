@php
    $passwordErrors = $errors->updatePassword;
@endphp

<section class="profile-section">
    <div class="admin-card-header">
        <div>
            <h3>Change password</h3>
            <p>Keep your account secure with a strong password.</p>
        </div>
        <div class="profile-section-icon profile-section-icon-dark">
            <i class="bi bi-shield-lock"></i>
        </div>
    </div>

    @if (session('status') === 'password-updated')
        <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle"></i>
            <span>Password updated successfully.</span>
        </div>
    @endif

    <form method="post" action="{{ route('password.update') }}" class="row g-3">
        @csrf
        @method('put')

        <div class="col-12">
            <label class="form-label" for="update_password_current_password">Current password</label>
            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                class="form-control {{ $passwordErrors->has('current_password') ? 'is-invalid' : '' }}"
                autocomplete="current-password"
            >
            @if ($passwordErrors->has('current_password'))
                <div class="invalid-feedback">{{ $passwordErrors->first('current_password') }}</div>
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label" for="update_password_password">New password</label>
            <input
                id="update_password_password"
                name="password"
                type="password"
                class="form-control {{ $passwordErrors->has('password') ? 'is-invalid' : '' }}"
                autocomplete="new-password"
            >
            @if ($passwordErrors->has('password'))
                <div class="invalid-feedback">{{ $passwordErrors->first('password') }}</div>
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label" for="update_password_password_confirmation">Confirm password</label>
            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                class="form-control {{ $passwordErrors->has('password_confirmation') ? 'is-invalid' : '' }}"
                autocomplete="new-password"
            >
            @if ($passwordErrors->has('password_confirmation'))
                <div class="invalid-feedback">{{ $passwordErrors->first('password_confirmation') }}</div>
            @endif
        </div>

        <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-dark">
                <i class="bi bi-key me-1"></i>Update Password
            </button>
        </div>
    </form>
</section>
