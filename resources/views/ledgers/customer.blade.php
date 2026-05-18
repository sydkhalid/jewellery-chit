@extends('layouts.admin')

@section('title', $customer->name.' Ledger')
@section('page-title', 'Customer Ledger')
@section('page-eyebrow', 'Chit Ledger')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $customer->name }}</h2>
            <p class="admin-section-copy">{{ $customer->customer_code }} - {{ $customer->mobile }}</p>
        </div>

        <a href="{{ route('ledgers.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>All Ledger
        </a>
    </div>

    <div class="dashboard-card-grid">
        <div class="metric-card metric-card-warning">
            <div class="metric-icon"><i class="bi bi-arrow-down-circle"></i></div>
            <div>
                <div class="metric-label">Total Debit</div>
                <div class="metric-value">Rs. {{ number_format($total_debit, 2) }}</div>
                <div class="metric-trend">Dues and charges</div>
            </div>
        </div>
        <div class="metric-card metric-card-success">
            <div class="metric-icon"><i class="bi bi-arrow-up-circle"></i></div>
            <div>
                <div class="metric-label">Total Credit</div>
                <div class="metric-value">Rs. {{ number_format($total_credit, 2) }}</div>
                <div class="metric-trend">Payments and adjustments</div>
            </div>
        </div>
        <div class="metric-card metric-card-primary">
            <div class="metric-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="metric-label">Balance</div>
                <div class="metric-value">Rs. {{ number_format($closing_balance, 2) }}</div>
                <div class="metric-trend">Running ledger balance</div>
            </div>
        </div>
        <div class="metric-card metric-card-info">
            <div class="metric-icon"><i class="bi bi-journal-check"></i></div>
            <div>
                <div class="metric-label">Chit Accounts</div>
                <div class="metric-value">{{ $enrollments->count() }}</div>
                <div class="metric-trend">Linked enrollments</div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h3>Chit Accounts</h3>
                <p>All chit accounts linked to this customer.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Chit No</th>
                        <th>Scheme</th>
                        <th>Status</th>
                        <th class="text-end">Payable</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Pending</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <td><a href="{{ route('chit-enrollments.ledger', $enrollment) }}">{{ $enrollment->chit_no }}</a></td>
                            <td>{{ $enrollment->scheme?->name }}</td>
                            <td><span class="badge text-bg-light">{{ ucfirst($enrollment->status) }}</span></td>
                            <td class="text-end">Rs. {{ number_format((float) $enrollment->total_payable, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $enrollment->total_paid, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $enrollment->total_pending, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No chit accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('ledgers.partials.table', ['entries' => $entries])
@endsection
