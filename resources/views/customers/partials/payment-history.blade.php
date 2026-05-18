<div class="detail-panel">
    <div class="summary-strip mb-3">
        <div>
            <span>Total Paid</span>
            <strong>Rs. {{ number_format($paymentHistory['total_paid'], 2) }}</strong>
        </div>
        <div>
            <span>Receipts</span>
            <strong>{{ $paymentHistory['payments']->count() }}</strong>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Payment No</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paymentHistory['payments'] as $payment)
                    <tr>
                        <td>{{ optional($payment->payment_date)->format('d M Y') }}</td>
                        <td>{{ $payment->formatted_payment_no }}</td>
                        <td>{{ $payment->paymentMode?->name ?: '-' }}</td>
                        <td><span class="badge text-bg-light">{{ ucfirst($payment->status) }}</span></td>
                        <td class="text-end">Rs. {{ number_format((float) $payment->total_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
