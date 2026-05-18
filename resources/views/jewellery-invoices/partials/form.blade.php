@php
    $selectedCustomerId = old('customer_id', $invoice->customer_id);
    $selectedEnrollmentId = old('enrollment_id', $invoice->enrollment_id);
@endphp

<div class="row g-4">
    <div class="col-12">
        <h3 class="form-section-title">Invoice Details</h3>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="customer_id">Customer</label>
        <select class="form-select" id="customer_id" name="customer_id" data-jewellery-customer required>
            <option value="">Select customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((int) $selectedCustomerId === $customer->id)>
                    {{ $customer->customer_code }} - {{ $customer->name }} - {{ $customer->mobile }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="customer_id"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="enrollment_id">Matured chit adjustment</label>
        <select class="form-select" id="enrollment_id" name="enrollment_id" data-jewellery-enrollment>
            <option value="">No chit adjustment</option>
            @foreach ($enrollments as $enrollment)
                <option value="{{ $enrollment->id }}" data-customer="{{ $enrollment->customer_id }}" @selected((int) $selectedEnrollmentId === $enrollment->id)>
                    {{ $enrollment->chit_no }} - {{ $enrollment->scheme?->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="enrollment_id"></div>
    </div>

    <div class="col-md-2">
        <label class="form-label" for="invoice_date">Invoice date</label>
        <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', optional($invoice->invoice_date)->toDateString() ?? now()->toDateString()) }}" required>
        <div class="invalid-feedback" data-error-for="invoice_date"></div>
    </div>

    <div class="col-md-2">
        <label class="form-label" for="gold_rate">Gold rate</label>
        <input type="number" step="0.01" min="1" class="form-control" id="gold_rate" name="gold_rate" data-jewellery-gold-rate value="{{ old('gold_rate', $invoice->gold_rate ?? ($latestGoldRate?->gold_22k ?? 1)) }}" readonly required>
        <div class="form-text">{{ $latestGoldRate ? 'Latest approved 22K rate dated '.$latestGoldRate->rate_date->format('d M Y') : 'Approve a gold rate before creating invoices.' }}</div>
        <div class="invalid-feedback" data-error-for="gold_rate"></div>
    </div>

    <div class="col-12">
        @include('jewellery-invoices.partials.items-table', ['invoice' => $invoice])
    </div>

    <div class="col-md-3">
        <label class="form-label" for="gross_weight">Gross weight</label>
        <input type="number" step="0.001" min="0" class="form-control" id="gross_weight" name="gross_weight" data-jewellery-total="gross_weight" value="{{ old('gross_weight', $invoice->gross_weight ?? 0) }}" readonly required>
        <div class="invalid-feedback" data-error-for="gross_weight"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="net_weight">Net weight</label>
        <input type="number" step="0.001" min="0" class="form-control" id="net_weight" name="net_weight" data-jewellery-total="net_weight" value="{{ old('net_weight', $invoice->net_weight ?? 0) }}" readonly required>
        <div class="invalid-feedback" data-error-for="net_weight"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="making_charge">Making charge</label>
        <input type="number" step="0.01" min="0" class="form-control" id="making_charge" name="making_charge" data-jewellery-total="making_charge" value="{{ old('making_charge', $invoice->making_charge ?? 0) }}" readonly>
        <div class="invalid-feedback" data-error-for="making_charge"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="wastage">Wastage</label>
        <input type="number" step="0.01" min="0" class="form-control" id="wastage" name="wastage" data-jewellery-total="wastage" value="{{ old('wastage', $invoice->wastage ?? 0) }}" readonly>
        <div class="invalid-feedback" data-error-for="wastage"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="gst_amount">GST</label>
        <input type="number" step="0.01" min="0" class="form-control" id="gst_amount" name="gst_amount" data-jewellery-total="gst_amount" value="{{ old('gst_amount', $invoice->gst_amount ?? 0) }}" readonly>
        <div class="invalid-feedback" data-error-for="gst_amount"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="discount">Discount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="discount" name="discount" data-jewellery-discount value="{{ old('discount', $invoice->discount ?? 0) }}">
        <div class="invalid-feedback" data-error-for="discount"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="chit_adjustment_amount">Chit adjustment</label>
        <input type="number" step="0.01" min="0" class="form-control" id="chit_adjustment_amount" name="chit_adjustment_amount" data-jewellery-adjustment value="{{ old('chit_adjustment_amount', $invoice->chit_adjustment_amount ?? 0) }}">
        <div class="form-text" data-jewellery-adjustment-help>Select a matured chit to apply adjustment.</div>
        <div class="invalid-feedback" data-error-for="chit_adjustment_amount"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="total_amount">Total amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="total_amount" name="total_amount" data-jewellery-total="total_amount" value="{{ old('total_amount', $invoice->total_amount ?? 0) }}" readonly>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="balance_payable">Balance payable</label>
        <input type="number" step="0.01" min="0" class="form-control" id="balance_payable" name="balance_payable" data-jewellery-total="balance_payable" value="{{ old('balance_payable', $invoice->balance_payable ?? 0) }}" readonly>
    </div>

    <div class="col-md-9">
        <div class="scheme-info-panel h-100" data-jewellery-summary>
            Add item rows to calculate invoice totals.
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('jewellery-invoices.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Invoice
        </button>
    </div>
</div>
