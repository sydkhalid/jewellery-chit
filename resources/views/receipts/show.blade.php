@extends('layouts.admin')

@section('title', $receipt->receipt_no)
@section('page-title', 'Receipt Details')
@section('page-eyebrow', 'Receipt Register')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $receipt->receipt_no }}</h2>
            <p class="admin-section-copy">{{ $receipt->customer?->name }} - {{ $receipt->enrollment?->chit_no }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if ($receipt->status === 'active')
                @can('receipts.print')
                    <a href="{{ route('receipts.thermal-print', $receipt) }}" target="_blank" class="btn btn-light">
                        <i class="bi bi-printer me-1"></i>Thermal
                    </a>
                    <a href="{{ route('receipts.a4-print', $receipt) }}" target="_blank" class="btn btn-light">
                        <i class="bi bi-file-earmark-text me-1"></i>A4 Print
                    </a>
                @endcan
                @can('receipts.pdf')
                    <a href="{{ route('receipts.pdf', $receipt) }}" class="btn btn-primary">
                        <i class="bi bi-filetype-pdf me-1"></i>PDF
                    </a>
                @endcan
                @can('receipts.duplicate')
                    <a href="{{ route('receipts.duplicate', $receipt) }}" target="_blank" class="btn btn-light">
                        <i class="bi bi-copy me-1"></i>Duplicate
                    </a>
                @endcan
                @can('receipts.whatsapp')
                    <button type="button" class="btn btn-success" data-receipt-action="whatsapp" data-url="{{ route('receipts.whatsapp', $receipt) }}">
                        <i class="bi bi-whatsapp me-1"></i>WhatsApp
                    </button>
                @endcan
                @can('receipts.cancel')
                    <button type="button" class="btn btn-danger" data-receipt-action="cancel" data-url="{{ route('receipts.cancel', $receipt) }}">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                @endcan
            @endif
            <a href="{{ route('receipts.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="dashboard-card-grid">
        <div class="metric-card metric-card-success">
            <div class="metric-icon"><i class="bi bi-receipt"></i></div>
            <div>
                <div class="metric-label">Receipt Amount</div>
                <div class="metric-value">Rs. {{ number_format((float) $receipt->amount, 2) }}</div>
                <div class="metric-trend">{{ optional($receipt->receipt_date)->format('d M Y') }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-primary">
            <div class="metric-icon"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="metric-label">Payment</div>
                <div class="metric-value">{{ $receipt->payment?->payment_no }}</div>
                <div class="metric-trend">{{ $receipt->payment?->paymentMode?->name }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-warning">
            <div class="metric-icon"><i class="bi bi-printer"></i></div>
            <div>
                <div class="metric-label">Print Count</div>
                <div class="metric-value">{{ $receipt->print_count }}</div>
                <div class="metric-trend">{{ $receipt->pdf_path ?: 'PDF not cached' }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-info">
            <div class="metric-icon"><i class="bi bi-toggle-on"></i></div>
            <div>
                <div class="metric-label">Status</div>
                <div class="metric-value">{{ ucfirst($receipt->status) }}</div>
                <div class="metric-trend">{{ optional($receipt->cancelled_at)->format('d M Y h:i A') ?: 'Receipt valid' }}</div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Customer</h4>
                    <dl>
                        <dt>Code</dt>
                        <dd>{{ $receipt->customer?->customer_code }}</dd>
                        <dt>Name</dt>
                        <dd>{{ $receipt->customer?->name }}</dd>
                        <dt>Mobile</dt>
                        <dd>{{ $receipt->customer?->mobile }}</dd>
                        <dt>Address</dt>
                        <dd>{{ $receipt->customer?->full_address ?: $receipt->customer?->address }}</dd>
                    </dl>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Chit & Payment</h4>
                    <dl>
                        <dt>Chit No</dt>
                        <dd>{{ $receipt->enrollment?->chit_no }}</dd>
                        <dt>Scheme</dt>
                        <dd>{{ $receipt->enrollment?->scheme?->name }}</dd>
                        <dt>Transaction ID</dt>
                        <dd>{{ $receipt->payment?->transaction_id ?: '-' }}</dd>
                        <dt>Collected By</dt>
                        <dd>{{ $receipt->payment?->staff?->name ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h3>Installment Details</h3>
                <p>{{ $receipt->payment?->allocations->count() ?? 0 }} installment allocation records.</p>
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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($receipt->payment?->allocations ?? [] as $allocation)
                        <tr>
                            <td>#{{ $allocation->installment?->installment_no }}</td>
                            <td>{{ optional($allocation->installment?->due_date)->format('d M Y') }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $allocation->amount, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $allocation->late_fee_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total</th>
                        <th class="text-end">Rs. {{ number_format((float) $receipt->payment?->amount, 2) }}</th>
                        <th class="text-end">Rs. {{ number_format((float) $receipt->payment?->late_fee_amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
