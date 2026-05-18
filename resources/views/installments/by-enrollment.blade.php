@extends('layouts.admin')

@section('title', $enrollment->chit_no.' Installments')
@section('page-title', 'Enrollment Installments')
@section('page-eyebrow', 'Installment Schedule')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $enrollment->chit_no }}</h2>
            <p class="admin-section-copy">{{ $enrollment->customer?->name }} - {{ $enrollment->scheme?->name }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('installments.generate')
                <button type="button" class="btn btn-warning" data-installment-action="regenerate" data-url="{{ route('chit-enrollments.installments.regenerate', $enrollment) }}">
                    <i class="bi bi-arrow-repeat me-1"></i>Regenerate
                </button>
            @endcan
            <a href="{{ route('chit-enrollments.show', $enrollment) }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Enrollment
            </a>
        </div>
    </div>

    <div class="dashboard-card-grid">
        <div class="metric-card metric-card-primary">
            <div class="metric-icon"><i class="bi bi-list-ol"></i></div>
            <div>
                <div class="metric-label">Total Months</div>
                <div class="metric-value">{{ $enrollment->total_months }}</div>
                <div class="metric-trend">{{ $installments->count() }} generated</div>
            </div>
        </div>
        <div class="metric-card metric-card-success">
            <div class="metric-icon"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="metric-label">Total Payable</div>
                <div class="metric-value">Rs. {{ number_format((float) $enrollment->total_payable, 2) }}</div>
                <div class="metric-trend">Monthly Rs. {{ number_format((float) $enrollment->monthly_amount, 2) }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-warning">
            <div class="metric-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="metric-label">Total Paid</div>
                <div class="metric-value">Rs. {{ number_format((float) $enrollment->total_paid, 2) }}</div>
                <div class="metric-trend">Pending Rs. {{ number_format((float) $enrollment->total_pending, 2) }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-info">
            <div class="metric-icon"><i class="bi bi-person-badge"></i></div>
            <div>
                <div class="metric-label">Staff</div>
                <div class="metric-value">{{ $enrollment->assignedStaff?->name ?: '-' }}</div>
                <div class="metric-trend">{{ $enrollment->branch?->name ?: 'No branch' }}</div>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h3>Month-wise Schedule</h3>
                <p>{{ $enrollment->customer?->name }} / {{ $enrollment->scheme?->name }}</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Due Date</th>
                        <th class="text-end">Due</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th class="text-end">Late Fee</th>
                        <th>Status</th>
                        <th class="text-end">Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($installments as $installment)
                        @php
                            $badgeClass = match ($installment->status) {
                                'paid' => 'success',
                                'partial', 'advance' => 'info',
                                'overdue' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td>{{ $installment->installment_no }}</td>
                            <td>{{ optional($installment->due_date)->format('d M Y') }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $installment->due_amount, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $installment->paid_amount, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $installment->balance_amount, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $installment->late_fee, 2) }}</td>
                            <td><span class="badge text-bg-{{ $badgeClass }}">{{ ucfirst($installment->status) }}</span></td>
                            <td class="text-end">
                                <a href="#" class="btn btn-sm btn-outline-primary disabled" aria-disabled="true">
                                    <i class="bi bi-wallet2 me-1"></i>Pay
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No installments generated.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
