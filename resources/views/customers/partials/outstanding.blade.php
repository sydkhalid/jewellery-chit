<div class="detail-panel">
    <div class="summary-strip mb-3">
        <div>
            <span>Total Outstanding</span>
            <strong>Rs. {{ number_format($outstanding['total_outstanding'], 2) }}</strong>
        </div>
        <div>
            <span>Open Chits</span>
            <strong>{{ $outstanding['enrollments']->count() }}</strong>
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
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($outstanding['enrollments'] as $enrollment)
                    <tr>
                        <td>{{ $enrollment->chit_no }}</td>
                        <td>{{ $enrollment->scheme?->name ?: '-' }}</td>
                        <td><span class="badge text-bg-light">{{ ucfirst($enrollment->status) }}</span></td>
                        <td class="text-end">Rs. {{ number_format((float) $enrollment->total_payable, 2) }}</td>
                        <td class="text-end">Rs. {{ number_format((float) $enrollment->total_paid, 2) }}</td>
                        <td class="text-end">Rs. {{ number_format((float) $enrollment->balance_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No outstanding chit balances.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
