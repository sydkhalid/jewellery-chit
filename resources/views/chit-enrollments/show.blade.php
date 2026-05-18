@extends('layouts.admin')

@section('title', $enrollment->chit_no)
@section('page-title', 'Enrollment Details')
@section('page-eyebrow', 'Enrollment Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $enrollment->chit_no }}</h2>
            <p class="admin-section-copy">{{ $enrollment->customer?->name }} · {{ $enrollment->scheme?->name }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('installments.view')
                <a href="{{ route('chit-enrollments.installments', $enrollment) }}" class="btn btn-light">
                    <i class="bi bi-calendar2-week me-1"></i>Installments
                </a>
            @endcan
            @can('enrollments.edit')
                @if ($enrollment->status === 'active')
                    <a href="{{ route('chit-enrollments.edit', $enrollment) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                @endif
            @endcan
            @can('enrollments.cancel')
                @if ($enrollment->status === 'active')
                    <button type="button" class="btn btn-warning" data-enrollment-action="cancel" data-url="{{ route('chit-enrollments.cancel', $enrollment) }}">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                @endif
            @endcan
            <a href="{{ route('chit-enrollments.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="dashboard-card-grid">
        <div class="metric-card metric-card-primary">
            <div class="metric-icon"><i class="bi bi-calendar-event"></i></div>
            <div>
                <div class="metric-label">Start Date</div>
                <div class="metric-value">{{ optional($enrollment->start_date)->format('d M Y') }}</div>
                <div class="metric-trend">Due day {{ $enrollment->monthly_due_date }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-success">
            <div class="metric-icon"><i class="bi bi-calendar-check"></i></div>
            <div>
                <div class="metric-label">Maturity</div>
                <div class="metric-value">{{ optional($enrollment->maturity_date)->format('d M Y') }}</div>
                <div class="metric-trend">{{ $enrollment->total_months }} months</div>
            </div>
        </div>
        <div class="metric-card metric-card-warning">
            <div class="metric-icon"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="metric-label">Total Payable</div>
                <div class="metric-value">Rs. {{ number_format((float) $enrollment->total_payable, 2) }}</div>
                <div class="metric-trend">Pending Rs. {{ number_format((float) $enrollment->total_pending, 2) }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-info">
            <div class="metric-icon"><i class="bi bi-toggle-on"></i></div>
            <div>
                <div class="metric-label">Status</div>
                <div class="metric-value">{{ ucfirst($enrollment->status) }}</div>
                <div class="metric-trend">{{ $enrollment->maturity_status }}</div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Enrollment Info</h4>
                    <dl>
                        <dt>Customer</dt>
                        <dd>{{ $enrollment->customer?->name }} - {{ $enrollment->customer?->mobile }}</dd>
                        <dt>Scheme</dt>
                        <dd>{{ $enrollment->scheme?->name }} ({{ str($enrollment->scheme?->scheme_type)->replace('_', ' ')->title() }})</dd>
                        <dt>Branch</dt>
                        <dd>{{ $enrollment->branch?->name ?: '-' }}</dd>
                        <dt>Assigned Staff</dt>
                        <dd>{{ $enrollment->assignedStaff?->name ?: '-' }}</dd>
                        <dt>Agreement</dt>
                        <dd>
                            @if ($enrollment->agreement_file)
                                <a href="{{ asset('storage/'.$enrollment->agreement_file) }}" target="_blank">Open agreement</a>
                            @else
                                -
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Remarks & Cancellation</h4>
                    <dl>
                        <dt>Remarks</dt>
                        <dd>{{ $enrollment->remarks ?: '-' }}</dd>
                        <dt>Cancelled</dt>
                        <dd>{{ $enrollment->cancellations->first()?->cancellation_date?->format('d M Y') ?: '-' }}</dd>
                        <dt>Reason</dt>
                        <dd>{{ $enrollment->cancellations->first()?->reason ?: '-' }}</dd>
                        <dt>Refund/Deduction</dt>
                        <dd>
                            Rs. {{ number_format((float) ($enrollment->cancellations->first()?->refund_amount ?? 0), 2) }}
                            /
                            Rs. {{ number_format((float) ($enrollment->cancellations->first()?->deduction_amount ?? 0), 2) }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h3>Installment Schedule</h3>
                <p>{{ $enrollment->installments->count() }} installments generated for this chit.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Due Date</th>
                        <th class="text-end">Due</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollment->installments as $installment)
                        <tr>
                            <td>{{ $installment->installment_no }}</td>
                            <td>{{ optional($installment->due_date)->format('d M Y') }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $installment->due_amount, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $installment->paid_amount, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $installment->balance_amount, 2) }}</td>
                            <td><span class="badge text-bg-light">{{ ucfirst($installment->status) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No installments generated.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('chit-enrollments.partials.cancel-modal')
@endsection
