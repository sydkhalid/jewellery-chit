@extends('layouts.admin')

@section('title', 'Staff Details')
@section('page-title', 'Staff Details')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $staff->name }}</h2>
            <p class="admin-section-copy">{{ $staff->getRoleNames()->first() }} / {{ $staff->branch?->name ?? 'No branch' }}</p>
        </div>

        <div class="d-flex gap-2">
            @can('staff.edit')
                <a href="{{ route('staff.edit', $staff) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('staff.index') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-card h-100">
                <h3 class="form-section-title">Staff Information</h3>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">{{ $staff->name }}</dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $staff->email }}</dd>
                    <dt class="col-sm-4">Mobile</dt>
                    <dd class="col-sm-8">{{ $staff->mobile ?: '-' }}</dd>
                    <dt class="col-sm-4">Role</dt>
                    <dd class="col-sm-8">{{ $staff->getRoleNames()->first() }}</dd>
                    <dt class="col-sm-4">Branch</dt>
                    <dd class="col-sm-8">{{ $staff->branch?->name ?? '-' }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge rounded-pill text-bg-{{ $staff->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($staff->status) }}</span>
                    </dd>
                </dl>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="admin-card h-100">
                <h3 class="form-section-title">Collection Summary</h3>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Total Collection</span>
                    <strong>Rs. {{ number_format($summary['total_collection'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Today</span>
                    <strong>Rs. {{ number_format($summary['today_collection'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>This Month</span>
                    <strong>Rs. {{ number_format($summary['month_collection'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span>Pending Handovers</span>
                    <strong>{{ $summary['pending_handovers'] }}</strong>
                </div>
            </div>
        </div>
    </div>
@endsection
