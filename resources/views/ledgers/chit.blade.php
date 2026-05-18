@extends('layouts.admin')

@section('title', $enrollment->chit_no.' Ledger')
@section('page-title', 'Chit Ledger')
@section('page-eyebrow', 'Chit Ledger')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $enrollment->chit_no }}</h2>
            <p class="admin-section-copy">{{ $enrollment->customer?->name }} - {{ $enrollment->scheme?->name }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @role('Admin')
                <button type="button" class="btn btn-primary" data-ledger-action="rebuild" data-url="{{ route('chit-enrollments.ledger.rebuild', $enrollment) }}">
                    <i class="bi bi-arrow-repeat me-1"></i>Rebuild
                </button>
            @endrole
            <a href="{{ route('ledgers.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>All Ledger
            </a>
        </div>
    </div>

    <div class="dashboard-card-grid">
        <div class="metric-card metric-card-warning">
            <div class="metric-icon"><i class="bi bi-arrow-down-circle"></i></div>
            <div>
                <div class="metric-label">Total Debit</div>
                <div class="metric-value">Rs. {{ number_format($total_debit, 2) }}</div>
                <div class="metric-trend">Payable: Rs. {{ number_format((float) $enrollment->total_payable, 2) }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-success">
            <div class="metric-icon"><i class="bi bi-arrow-up-circle"></i></div>
            <div>
                <div class="metric-label">Total Credit</div>
                <div class="metric-value">Rs. {{ number_format($total_credit, 2) }}</div>
                <div class="metric-trend">Paid: Rs. {{ number_format((float) $enrollment->total_paid, 2) }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-primary">
            <div class="metric-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="metric-label">Balance</div>
                <div class="metric-value">Rs. {{ number_format($closing_balance, 2) }}</div>
                <div class="metric-trend">Pending: Rs. {{ number_format((float) $enrollment->total_pending, 2) }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-info">
            <div class="metric-icon"><i class="bi bi-cash-coin"></i></div>
            <div>
                <div class="metric-label">Late Fee / Advance</div>
                <div class="metric-value">Rs. {{ number_format($late_fee, 2) }}</div>
                <div class="metric-trend">Advance Rs. {{ number_format($advance, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Customer</h4>
                    <dl>
                        <dt>Name</dt>
                        <dd>{{ $enrollment->customer?->name }}</dd>
                        <dt>Mobile</dt>
                        <dd>{{ $enrollment->customer?->mobile }}</dd>
                        <dt>Branch</dt>
                        <dd>{{ $enrollment->branch?->name ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Scheme</h4>
                    <dl>
                        <dt>Scheme</dt>
                        <dd>{{ $enrollment->scheme?->name }}</dd>
                        <dt>Duration</dt>
                        <dd>{{ $enrollment->total_months }} months</dd>
                        <dt>Staff</dt>
                        <dd>{{ $enrollment->assignedStaff?->name ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @include('ledgers.partials.table', ['entries' => $entries])
@endsection
