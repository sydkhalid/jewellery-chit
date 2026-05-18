@extends('layouts.admin')

@section('title', 'Branch Details')
@section('page-title', 'Branch Details')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $branch->name }}</h2>
            <p class="admin-section-copy">{{ $branch->branch_code }}</p>
        </div>

        <div class="d-flex gap-2">
            @can('branch.edit')
                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('branches.index') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-card h-100">
                <h3 class="form-section-title">Branch Information</h3>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Code</dt>
                    <dd class="col-sm-8">{{ $branch->branch_code }}</dd>
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">{{ $branch->name }}</dd>
                    <dt class="col-sm-4">Mobile</dt>
                    <dd class="col-sm-8">{{ $branch->mobile ?: '-' }}</dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $branch->email ?: '-' }}</dd>
                    <dt class="col-sm-4">Address</dt>
                    <dd class="col-sm-8">{{ trim($branch->address.' '.$branch->city.' '.$branch->state.' '.$branch->pincode) ?: '-' }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge rounded-pill text-bg-{{ $branch->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($branch->status) }}</span>
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
                    <span>Staff</span>
                    <strong>{{ $summary['staff_count'] }}</strong>
                </div>
            </div>
        </div>
    </div>
@endsection
