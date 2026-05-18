@extends('layouts.admin')

@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('page-eyebrow', 'Account')

@section('content')
    @php
        $roleNames = $user->getRoleNames();
        $initials = str($user->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn ($part) => str($part)->substr(0, 1)->upper())
            ->implode('');
    @endphp

    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Profile & Security</h2>
            <p class="admin-section-copy">Manage your panel identity and password from the same admin workspace.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-light">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
        </a>
    </div>

    <section class="profile-layout">
        <aside class="admin-card profile-overview-card">
            <div class="profile-avatar">{{ $initials ?: 'U' }}</div>
            <h3>{{ $user->name }}</h3>
            <p>{{ $user->email }}</p>

            <div class="profile-role-list">
                @forelse ($roleNames as $role)
                    <span>{{ $role }}</span>
                @empty
                    <span>No role assigned</span>
                @endforelse
            </div>

            <div class="profile-meta-list">
                <div>
                    <span>Status</span>
                    <strong class="text-capitalize">{{ $user->status ?? 'active' }}</strong>
                </div>
                <div>
                    <span>Mobile</span>
                    <strong>{{ $user->mobile ?: '-' }}</strong>
                </div>
                <div>
                    <span>Branch</span>
                    <strong>{{ $user->branch?->name ?? '-' }}</strong>
                </div>
                <div>
                    <span>Joined</span>
                    <strong>{{ $user->created_at?->format('d M Y') ?? '-' }}</strong>
                </div>
            </div>
        </aside>

        <div class="profile-form-grid">
            <article class="admin-card profile-form-card">
                @include('profile.partials.update-profile-information-form')
            </article>

            <article class="admin-card profile-form-card">
                @include('profile.partials.update-password-form')
            </article>
        </div>
    </section>
@endsection
