@extends('layouts.admin')

@section('title', 'Installment #'.$installment->installment_no)
@section('page-title', 'Installment Details')
@section('page-eyebrow', 'Installment Schedule')

@section('content')
    @php
        $badgeClass = match ($installment->status) {
            'paid' => 'success',
            'partial', 'advance' => 'info',
            'overdue' => 'danger',
            default => 'secondary',
        };
    @endphp

    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $installment->enrollment?->chit_no }} / Installment #{{ $installment->installment_no }}</h2>
            <p class="admin-section-copy">{{ $installment->enrollment?->customer?->name }} - {{ $installment->enrollment?->scheme?->name }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('installments.edit')
                <a href="{{ route('installments.edit', $installment) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('chit-enrollments.installments', $installment->enrollment) }}" class="btn btn-light">
                <i class="bi bi-calendar2-week me-1"></i>Schedule
            </a>
            <a href="{{ route('installments.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="dashboard-card-grid">
        <div class="metric-card metric-card-primary">
            <div class="metric-icon"><i class="bi bi-calendar-event"></i></div>
            <div>
                <div class="metric-label">Due Date</div>
                <div class="metric-value">{{ optional($installment->due_date)->format('d M Y') }}</div>
                <div class="metric-trend">Month {{ $installment->installment_no }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-success">
            <div class="metric-icon"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="metric-label">Due Amount</div>
                <div class="metric-value">Rs. {{ number_format((float) $installment->due_amount, 2) }}</div>
                <div class="metric-trend">Late fee Rs. {{ number_format((float) $installment->late_fee, 2) }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-warning">
            <div class="metric-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="metric-label">Paid</div>
                <div class="metric-value">Rs. {{ number_format((float) $installment->paid_amount, 2) }}</div>
                <div class="metric-trend">Balance Rs. {{ number_format((float) $installment->balance_amount, 2) }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-info">
            <div class="metric-icon"><i class="bi bi-toggle-on"></i></div>
            <div>
                <div class="metric-label">Status</div>
                <div class="metric-value">{{ ucfirst($installment->status) }}</div>
                <div class="metric-trend">{{ optional($installment->paid_date)->format('d M Y') ?: 'Not fully paid' }}</div>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Enrollment</h4>
                    <dl>
                        <dt>Chit No</dt>
                        <dd>{{ $installment->enrollment?->chit_no }}</dd>
                        <dt>Customer</dt>
                        <dd>{{ $installment->enrollment?->customer?->name }} - {{ $installment->enrollment?->customer?->mobile }}</dd>
                        <dt>Scheme</dt>
                        <dd>{{ $installment->enrollment?->scheme?->name }}</dd>
                        <dt>Branch</dt>
                        <dd>{{ $installment->enrollment?->branch?->name ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Installment</h4>
                    <dl>
                        <dt>Status</dt>
                        <dd><span class="badge text-bg-{{ $badgeClass }}">{{ ucfirst($installment->status) }}</span></dd>
                        <dt>Assigned Staff</dt>
                        <dd>{{ $installment->enrollment?->assignedStaff?->name ?: '-' }}</dd>
                        <dt>Payment Count</dt>
                        <dd>{{ $installment->payments->count() }}</dd>
                        <dt>Last Updated</dt>
                        <dd>{{ optional($installment->updated_at)->format('d M Y h:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
