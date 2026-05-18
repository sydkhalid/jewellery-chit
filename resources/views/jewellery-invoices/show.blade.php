@extends('layouts.admin')

@section('title', $invoice->invoice_no)
@section('page-title', 'Jewellery Invoice Details')
@section('page-eyebrow', 'Jewellery Billing')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $invoice->invoice_no }}</h2>
            <p class="admin-section-copy">{{ $invoice->customer?->name }} - {{ optional($invoice->invoice_date)->format('d M Y') }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('jewellery.edit')
                @if ($invoice->status === 'draft')
                    <a href="{{ route('jewellery-invoices.edit', $invoice) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                @endif
            @endcan
            @can('jewellery.create')
                @if ($invoice->status === 'draft')
                    <button type="button" class="btn btn-success" data-jewellery-action="finalize" data-url="{{ route('jewellery-invoices.finalize', $invoice) }}">
                        <i class="bi bi-patch-check me-1"></i>Finalize
                    </button>
                @endif
            @endcan
            @can('jewellery.cancel')
                @if (in_array($invoice->status, ['draft', 'final'], true))
                    <button type="button" class="btn btn-danger" data-jewellery-action="cancel" data-url="{{ route('jewellery-invoices.cancel', $invoice) }}">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                @endif
            @endcan
            <a href="{{ route('jewellery-invoices.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="dashboard-card-grid">
        <div class="metric-card metric-card-primary">
            <div class="metric-icon"><i class="bi bi-gem"></i></div>
            <div>
                <div class="metric-label">Total Amount</div>
                <div class="metric-value">Rs. {{ number_format((float) $invoice->total_amount, 2) }}</div>
                <div class="metric-trend">{{ number_format((float) $invoice->net_weight, 3) }} gm net weight</div>
            </div>
        </div>
        <div class="metric-card metric-card-success">
            <div class="metric-icon"><i class="bi bi-award"></i></div>
            <div>
                <div class="metric-label">Chit Adjustment</div>
                <div class="metric-value">Rs. {{ number_format((float) $invoice->chit_adjustment_amount, 2) }}</div>
                <div class="metric-trend">{{ $invoice->enrollment?->chit_no ?: 'No chit linked' }}</div>
            </div>
        </div>
        <div class="metric-card metric-card-warning">
            <div class="metric-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <div class="metric-label">Balance Payable</div>
                <div class="metric-value">Rs. {{ number_format((float) $invoice->balance_payable, 2) }}</div>
                <div class="metric-trend">After discount and adjustment</div>
            </div>
        </div>
        <div class="metric-card metric-card-info">
            <div class="metric-icon"><i class="bi bi-toggle-on"></i></div>
            <div>
                <div class="metric-label">Status</div>
                <div class="metric-value">{{ ucfirst($invoice->status) }}</div>
                <div class="metric-trend">{{ $invoice->finalizer?->name ? 'Final by '.$invoice->finalizer->name : 'Draft invoice' }}</div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Customer & Chit</h4>
                    <dl>
                        <dt>Customer</dt>
                        <dd>{{ $invoice->customer?->customer_code }} - {{ $invoice->customer?->name }}</dd>
                        <dt>Mobile</dt>
                        <dd>{{ $invoice->customer?->mobile ?: '-' }}</dd>
                        <dt>Chit number</dt>
                        <dd>{{ $invoice->enrollment?->chit_no ?: '-' }}</dd>
                        <dt>Scheme</dt>
                        <dd>{{ $invoice->enrollment?->scheme?->name ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Invoice Totals</h4>
                    <dl>
                        <dt>Gold rate</dt>
                        <dd>Rs. {{ number_format((float) $invoice->gold_rate, 2) }}</dd>
                        <dt>Gross / Net weight</dt>
                        <dd>{{ number_format((float) $invoice->gross_weight, 3) }} / {{ number_format((float) $invoice->net_weight, 3) }}</dd>
                        <dt>Making / Wastage / GST</dt>
                        <dd>Rs. {{ number_format((float) $invoice->making_charge, 2) }} / Rs. {{ number_format((float) $invoice->wastage, 2) }} / Rs. {{ number_format((float) $invoice->gst_amount, 2) }}</dd>
                        <dt>Discount</dt>
                        <dd>Rs. {{ number_format((float) $invoice->discount, 2) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h3>Items</h3>
                <p>{{ $invoice->items->count() }} billed item rows.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Purity</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Net</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Making</th>
                        <th class="text-end">Wastage</th>
                        <th class="text-end">GST</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->purity ?: '-' }}</td>
                            <td class="text-end">{{ number_format((float) $item->gross_weight, 3) }}</td>
                            <td class="text-end">{{ number_format((float) $item->net_weight, 3) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $item->rate, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $item->making_charge, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $item->wastage, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $item->gst_amount, 2) }}</td>
                            <td class="text-end">Rs. {{ number_format((float) $item->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
