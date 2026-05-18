@extends('layouts.admin')

@section('title', $closure->closure_no)
@section('page-title', 'Maturity Closing Details')
@section('page-eyebrow', 'Maturity Closing')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $closure->closure_no }}</h2>
            <p class="admin-section-copy">{{ $closure->customer?->name }} - {{ $closure->enrollment?->chit_no }} - {{ ucfirst($closure->closure_type) }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('maturity.approve')
                @if ($closure->status === 'pending')
                    <button type="button" class="btn btn-success" data-maturity-action="approve" data-url="{{ route('maturity-closings.approve', $closure) }}">
                        <i class="bi bi-check2-circle me-1"></i>Approve
                    </button>
                @endif
                @if ($closure->status === 'approved')
                    <button type="button" class="btn btn-primary" data-maturity-action="complete" data-url="{{ route('maturity-closings.complete', $closure) }}">
                        <i class="bi bi-patch-check me-1"></i>Complete
                    </button>
                @endif
            @endcan
            @can('maturity.cancel')
                @if (in_array($closure->status, ['pending', 'approved'], true))
                    <button type="button" class="btn btn-danger" data-maturity-action="cancel" data-url="{{ route('maturity-closings.cancel', $closure) }}">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                @endif
            @endcan
            <a href="{{ route('maturity-closings.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    @include('maturity-closings.partials.summary')

    <div class="admin-card mb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Customer & Chit</h4>
                    <dl>
                        <dt>Customer</dt>
                        <dd>{{ $closure->customer?->customer_code }} - {{ $closure->customer?->name }}</dd>
                        <dt>Mobile</dt>
                        <dd>{{ $closure->customer?->mobile ?: '-' }}</dd>
                        <dt>Chit number</dt>
                        <dd>{{ $closure->enrollment?->chit_no }}</dd>
                        <dt>Scheme</dt>
                        <dd>{{ $closure->enrollment?->scheme?->name ?: '-' }}</dd>
                        <dt>Start date</dt>
                        <dd>{{ optional($closure->enrollment?->start_date)->format('d M Y') ?: '-' }}</dd>
                        <dt>Maturity date</dt>
                        <dd>{{ optional($closure->enrollment?->maturity_date)->format('d M Y') ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Closing Settlement</h4>
                    <dl>
                        <dt>Closure type</dt>
                        <dd>{{ ucfirst($closure->closure_type) }}</dd>
                        <dt>Status</dt>
                        <dd>{{ ucfirst($closure->status) }}</dd>
                        <dt>Total months</dt>
                        <dd>{{ $summary['total_months'] }}</dd>
                        <dt>Refund amount</dt>
                        <dd>Rs. {{ number_format((float) $closure->refund_amount, 2) }}</dd>
                        <dt>Jewellery adjustment</dt>
                        <dd>Rs. {{ number_format((float) $closure->jewellery_adjustment_amount, 2) }}</dd>
                        <dt>Remarks</dt>
                        <dd>{{ $closure->remarks ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Approval</h4>
                    <dl>
                        <dt>Approved by</dt>
                        <dd>{{ $closure->approver?->name ?: '-' }}</dd>
                        <dt>Approved at</dt>
                        <dd>{{ optional($closure->approved_at)->format('d M Y h:i A') ?: '-' }}</dd>
                        <dt>Completed by</dt>
                        <dd>{{ $closure->completer?->name ?: '-' }}</dd>
                        <dt>Completed at</dt>
                        <dd>{{ optional($closure->completed_at)->format('d M Y h:i A') ?: '-' }}</dd>
                        <dt>Cancelled by</dt>
                        <dd>{{ $closure->canceller?->name ?: '-' }}</dd>
                        <dt>Cancellation reason</dt>
                        <dd>{{ $closure->cancellation_reason ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Customer Signature</h4>
                    @if ($closure->customer_signature)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($closure->customer_signature) }}" alt="Customer signature" class="img-fluid rounded border">
                    @else
                        <p class="mb-0 text-muted">No signature uploaded.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h3>Settlement References</h3>
                <p>Refund and jewellery adjustment records created during completion.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($refunds as $refund)
                        <tr>
                            <td>Refund</td>
                            <td>{{ $refund->refund_no }}</td>
                            <td>{{ optional($refund->refund_date)->format('d M Y') }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $refund->amount, 2) }}</td>
                            <td>{{ ucfirst($refund->status) }}</td>
                        </tr>
                    @empty
                    @endforelse
                    @forelse ($jewelleryInvoices as $invoice)
                        <tr>
                            <td>Jewellery Adjustment</td>
                            <td>{{ $invoice->invoice_no }}</td>
                            <td>{{ optional($invoice->invoice_date)->format('d M Y') }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $invoice->chit_adjustment_amount, 2) }}</td>
                            <td>{{ ucfirst($invoice->status) }}</td>
                        </tr>
                    @empty
                    @endforelse
                    @if ($refunds->isEmpty() && $jewelleryInvoices->isEmpty())
                        <tr>
                            <td colspan="5" class="text-center text-muted">No settlement references created yet.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection
