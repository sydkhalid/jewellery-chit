@extends('layouts.admin')

@section('title', 'Audit Log Details')
@section('page-title', 'Audit Log Details')
@section('page-eyebrow', 'Admin Settings')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ str($auditLog->event)->headline() }}</h2>
            <p class="admin-section-copy">{{ $auditLog->created_at?->format('d M Y, h:i A') }}</p>
        </div>

        <a href="{{ route('audit-logs.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Audit Logs
        </a>
    </div>

    <div class="admin-card mb-4">
        <div class="detail-panel">
            <dl>
                <dt>User</dt>
                <dd>{{ $auditLog->user?->name ?? 'System' }}</dd>
                <dt>Module</dt>
                <dd>{{ str(class_basename($auditLog->auditable_type))->headline() }}</dd>
                <dt>Auditable ID</dt>
                <dd>{{ $auditLog->auditable_id }}</dd>
                <dt>IP address</dt>
                <dd>{{ $auditLog->ip_address ?? '-' }}</dd>
                <dt>User agent</dt>
                <dd>{{ $auditLog->user_agent ?? '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="admin-card h-100">
                <div class="admin-card-header">
                    <div>
                        <h3>Old Values</h3>
                        <p>Values before the event.</p>
                    </div>
                </div>
                <pre class="log-json">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="admin-card h-100">
                <div class="admin-card-header">
                    <div>
                        <h3>New Values</h3>
                        <p>Values after the event.</p>
                    </div>
                </div>
                <pre class="log-json">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}' }}</pre>
            </div>
        </div>
    </div>
@endsection
