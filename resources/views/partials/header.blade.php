@php
    $user = auth()->user();
    $roleName = $user?->getRoleNames()->first() ?? 'User';
@endphp

<header class="admin-header">
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn btn-light admin-icon-btn d-xl-none" data-sidebar-toggle aria-label="Open sidebar">
            <i class="bi bi-list"></i>
        </button>

        <div>
            <p class="admin-eyebrow mb-1">@yield('page-eyebrow', 'Jewellery Chit')</p>
            <h1 class="admin-page-title mb-0">@yield('page-title', 'Dashboard')</h1>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 gap-md-3">
        <button type="button" class="btn btn-light admin-icon-btn" data-theme-toggle aria-label="Toggle dark mode">
            <i class="bi bi-moon-stars" data-theme-icon></i>
        </button>

        <button type="button" class="btn btn-light admin-icon-btn d-none d-md-inline-flex" aria-label="Notifications">
            <i class="bi bi-bell"></i>
        </button>

        <div class="dropdown">
            <button class="btn admin-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="admin-avatar">{{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}</span>
                <span class="d-none d-sm-block text-start">
                    <span class="d-block fw-semibold lh-sm">{{ $user?->name }}</span>
                    <span class="d-block small text-muted">{{ $roleName }}</span>
                </span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li>
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <i class="bi bi-person me-2"></i>Profile
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
