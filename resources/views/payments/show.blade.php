@extends('layouts.admin')

@section('title', $payment->payment_no)
@section('page-title', 'Payment Details')
@section('page-eyebrow', 'Payment Collection')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $payment->payment_no }}</h2>
            <p class="admin-section-copy">{{ $payment->customer?->name }} - {{ $payment->enrollment?->chit_no }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('payments.edit')
                @if ($payment->status === 'success')
                    <a href="{{ route('payments.edit', $payment) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                @endif
            @endcan
            @can('payments.approve_edit')
                @if ($payment->status === 'success' && $payment->edit_status === 'pending')
                    <button type="button" class="btn btn-success" data-payment-action="approve" data-url="{{ route('payments.approve-edit', $payment) }}">
                        <i class="bi bi-check2-circle me-1"></i>Approve Edit
                    </button>
                @endif
            @endcan
            @can('payments.cancel')
                @if ($payment->status === 'success')
                    <button type="button" class="btn btn-danger" data-payment-action="cancel" data-url="{{ route('payments.cancel', $payment) }}">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                @endif
            @endcan
            <a href="{{ route('payments.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="dashboard-card-grid">
        <div class="metric-card metric-card-primary">
            <div class="metric-icon"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="metric-label">Amount</div>
                <div class="metric-value">Rs. {{ number_format((float) $payment->amount, 2) }}</div>
                <div class="metric-trend">{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-success">
            <div class="metric-icon"><i class="bi bi-receipt"></i></div>
            <div>
                <div class="metric-label">Receipt</div>
                <div class="metric-value">{{ $payment->receipt?->receipt_no ?: '-' }}</div>
                <div class="metric-trend">Rs. {{ number_format((float) $payment->total_amount, 2) }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-warning">
            <div class="metric-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="metric-label">Mode</div>
                <div class="metric-value">{{ $payment->paymentMode?->name }}</div>
                <div class="metric-trend">{{ $payment->transaction_id ?: 'No transaction ID' }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-info">
            <div class="metric-icon"><i class="bi bi-toggle-on"></i></div>
            <div>
                <div class="metric-label">Status</div>
                <div class="metric-value">{{ ucfirst($payment->status) }}</div>
                <div class="metric-trend">{{ $payment->edit_status ? 'Edit '.$payment->edit_status : optional($payment->payment_date)->format('d M Y') }}</div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Collection Info</h4>
                    <dl>
                        <dt>Customer</dt>
                        <dd>{{ $payment->customer?->name }} - {{ $payment->customer?->mobile }}</dd>
                        <dt>Chit No</dt>
                        <dd>{{ $payment->enrollment?->chit_no }} - {{ $payment->enrollment?->scheme?->name }}</dd>
                        <dt>Staff</dt>
                        <dd>{{ $payment->staff?->name ?: '-' }}</dd>
                        <dt>Branch</dt>
                        <dd>{{ $payment->branch?->name ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Amounts</h4>
                    <dl>
                        <dt>Payment Amount</dt>
                        <dd>Rs. {{ number_format((float) $payment->amount, 2) }}</dd>
                        <dt>Late Fee</dt>
                        <dd>Rs. {{ number_format((float) $payment->late_fee_amount, 2) }}</dd>
                        <dt>Total Collected</dt>
                        <dd>Rs. {{ number_format((float) $payment->total_amount, 2) }}</dd>
                        <dt>Remarks</dt>
                        <dd>{{ $payment->remarks ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-3">
        <div class="admin-card-header">
            <div>
                <h3>Installment Allocation</h3>
                <p>{{ $payment->allocations->count() }} installment allocation records.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Installment</th>
                        <th>Due Date</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Late Fee</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payment->allocations as $allocation)
                        <tr>
                            <td>#{{ $allocation->installment?->installment_no }}</td>
                            <td>{{ optional($allocation->installment?->due_date)->format('d M Y') }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $allocation->amount, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $allocation->late_fee_amount, 2) }}</td>
                            <td><span class="badge text-bg-light">{{ ucfirst($allocation->installment?->status ?? '-') }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card" id="receipt">
        <div class="admin-card-header">
            <div>
                <h3>Receipt</h3>
                <p>{{ $payment->receipt?->receipt_no ?: 'Receipt not generated' }}</p>
            </div>
            @if ($payment->receipt)
                <div class="d-flex flex-wrap gap-2">
                    @can('receipts.view')
                        <a href="{{ route('receipts.show', $payment->receipt) }}" class="btn btn-sm btn-light">
                            <i class="bi bi-eye me-1"></i>Open
                        </a>
                    @endcan
                    @if ($payment->receipt->status === 'active')
                        @can('receipts.print')
                            <a href="{{ route('receipts.thermal-print', $payment->receipt) }}" target="_blank" class="btn btn-sm btn-light">
                                <i class="bi bi-printer me-1"></i>Thermal
                            </a>
                        @endcan
                        @can('receipts.pdf')
                            <a href="{{ route('receipts.pdf', $payment->receipt) }}" class="btn btn-sm btn-light">
                                <i class="bi bi-filetype-pdf me-1"></i>PDF
                            </a>
                        @endcan
                    @endif
                </div>
            @endif
        </div>

        <div class="detail-panel">
            <dl>
                <dt>Receipt No</dt>
                <dd>{{ $payment->receipt?->receipt_no ?: '-' }}</dd>
                <dt>Receipt Date</dt>
                <dd>{{ optional($payment->receipt?->receipt_date)->format('d M Y') ?: '-' }}</dd>
                <dt>Receipt Amount</dt>
                <dd>Rs. {{ number_format((float) ($payment->receipt?->amount ?? 0), 2) }}</dd>
                <dt>Receipt Status</dt>
                <dd>{{ ucfirst($payment->receipt?->status ?? '-') }}</dd>
            </dl>
        </div>
    </div>

    @include('payments.partials.cancel-modal')
@endsection
