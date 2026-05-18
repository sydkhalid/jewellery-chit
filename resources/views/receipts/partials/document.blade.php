@php
    $payment = $payment ?? $receipt->payment;
    $customer = $customer ?? $receipt->customer;
    $enrollment = $enrollment ?? $receipt->enrollment;
    $allocations = $allocations ?? ($payment?->allocations ?? collect());
    $shop = $shop ?? [];
    $copyLabel = $copyLabel ?? 'Original Copy';
    $printMode = $printMode ?? 'a4';
    $logoPath = filled($shop['logo'] ?? null) ? public_path('storage/'.$shop['logo']) : null;
@endphp

<section class="receipt-document receipt-document-{{ $printMode }}">
    <header class="receipt-header">
        <div class="receipt-logo">
            @if ($logoPath && file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Shop logo">
            @else
                <span>JC</span>
            @endif
        </div>
        <div class="receipt-shop">
            <h1>{{ $shop['name'] ?? config('app.name', 'Jewellery Chit') }}</h1>
            <p>{{ $shop['address'] ?? 'Shop address not configured' }}</p>
            <p>
                @if (! blank($shop['mobile'] ?? null))
                    Mobile: {{ $shop['mobile'] }}
                @endif
                @if (! blank($shop['email'] ?? null))
                    {{ ! blank($shop['mobile'] ?? null) ? ' | ' : '' }}Email: {{ $shop['email'] }}
                @endif
            </p>
            @if (! blank($shop['gstin'] ?? null))
                <p>GSTIN: {{ $shop['gstin'] }}</p>
            @endif
        </div>
        <div class="receipt-copy-label">{{ $copyLabel }}</div>
    </header>

    <div class="receipt-title-row">
        <h2>Payment Receipt</h2>
        <div>
            <strong>{{ $receipt->receipt_no }}</strong>
            <span>{{ optional($receipt->receipt_date)->format('d M Y') }}</span>
        </div>
    </div>

    <div class="receipt-grid">
        <div>
            <h3>Customer</h3>
            <dl>
                <dt>Customer Code</dt>
                <dd>{{ $customer?->customer_code }}</dd>
                <dt>Name</dt>
                <dd>{{ $customer?->name }}</dd>
                <dt>Mobile</dt>
                <dd>{{ $customer?->mobile }}</dd>
                <dt>Address</dt>
                <dd>{{ $customer?->full_address ?: $customer?->address }}</dd>
            </dl>
        </div>
        <div>
            <h3>Chit Details</h3>
            <dl>
                <dt>Chit Number</dt>
                <dd>{{ $enrollment?->chit_no }}</dd>
                <dt>Scheme</dt>
                <dd>{{ $enrollment?->scheme?->name }}</dd>
                <dt>Payment Number</dt>
                <dd>{{ $payment?->payment_no }}</dd>
                <dt>Collected By</dt>
                <dd>{{ $payment?->staff?->name ?: '-' }}</dd>
            </dl>
        </div>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>Installment</th>
                <th>Due Date</th>
                <th>Details</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Late Fee</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($allocations as $allocation)
                <tr>
                    <td>#{{ $allocation->installment?->installment_no }}</td>
                    <td>{{ optional($allocation->installment?->due_date)->format('d M Y') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $payment?->payment_type ?? 'payment')) }}</td>
                    <td class="text-right">Rs. {{ number_format((float) $allocation->amount, 2) }}</td>
                    <td class="text-right">Rs. {{ number_format((float) $allocation->late_fee_amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No installment allocation found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="receipt-summary">
        <div class="receipt-payment-meta">
            <p><strong>Payment Mode:</strong> {{ $payment?->paymentMode?->name ?: '-' }}</p>
            <p><strong>Transaction ID:</strong> {{ $payment?->transaction_id ?: '-' }}</p>
            <p><strong>Branch:</strong> {{ $payment?->branch?->name ?: '-' }}</p>
        </div>
        <table>
            <tr>
                <th>Amount Paid</th>
                <td>Rs. {{ number_format((float) $payment?->amount, 2) }}</td>
            </tr>
            <tr>
                <th>Late Fee</th>
                <td>Rs. {{ number_format((float) $payment?->late_fee_amount, 2) }}</td>
            </tr>
            <tr class="receipt-total">
                <th>Total Amount</th>
                <td>Rs. {{ number_format((float) $receipt->amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="receipt-footer">
        <div>
            <h3>Terms and Conditions</h3>
            <p>{!! nl2br(e($shop['terms'] ?? 'Please keep this receipt for future reference.')) !!}</p>
        </div>
        <div class="signature-box">
            <span>Authorized Signature</span>
        </div>
    </div>
</section>
