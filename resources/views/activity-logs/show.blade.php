@extends('layouts.admin')

@section('title', 'Activity Log Details')
@section('page-title', 'Activity Log Details')
@section('page-eyebrow', 'Admin Settings')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ str($activityLog->action)->headline() }}</h2>
            <p class="admin-section-copy">{{ $activityLog->created_at?->format('d M Y, h:i A') }}</p>
        </div>

        <a href="{{ route('activity-logs.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Activity Logs
        </a>
    </div>

    <div class="admin-card">
        <div class="detail-panel">
            <dl>
                <dt>User</dt>
                <dd>{{ $activityLog->user?->name ?? 'System' }}</dd>
                <dt>Module</dt>
                <dd>{{ str($activityLog->module)->replace('_', ' ')->headline() }}</dd>
                <dt>Action</dt>
                <dd>{{ str($activityLog->action)->headline() }}</dd>
                <dt>Description</dt>
                <dd>{{ $activityLog->description ?? '-' }}</dd>
                <dt>IP address</dt>
                <dd>{{ $activityLog->ip_address ?? '-' }}</dd>
                <dt>User agent</dt>
                <dd>{{ $activityLog->user_agent ?? '-' }}</dd>
            </dl>
        </div>
    </div>
@endsection
