<section class="profile-section">
    <div class="admin-card-header">
        <div>
            <h3>Profile information</h3>
            <p>Update the name and email used for web panel access.</p>
        </div>
        <div class="profile-section-icon">
            <i class="bi bi-person-lines-fill"></i>
        </div>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle"></i>
            <span>Profile updated successfully.</span>
        </div>
    @endif

    <form method="post" action="{{ route('profile.update') }}" class="row g-3">
        @csrf
        @method('patch')

        <div class="col-12">
            <label class="form-label" for="name">Name</label>
            <input
                id="name"
                name="name"
                type="text"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $user->name) }}"
                autocomplete="name"
                required
                autofocus
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="email">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $user->email) }}"
                autocomplete="username"
                required
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="profile-inline-note mt-3">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        <span>Your email address is unverified.</span>
                        <button form="send-verification" class="btn btn-link p-0 align-baseline">
                            Resend verification email
                        </button>

                        @if (session('status') === 'verification-link-sent')
                            <strong>A new verification link has been sent.</strong>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle me-1"></i>Save Profile
            </button>
        </div>
    </form>
</section>
