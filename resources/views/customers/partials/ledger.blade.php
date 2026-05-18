<div class="detail-panel">
    <div class="summary-strip mb-3">
        <div>
            <span>Total Debit</span>
            <strong>Rs. {{ number_format($ledger['total_debit'], 2) }}</strong>
        </div>
        <div>
            <span>Total Credit</span>
            <strong>Rs. {{ number_format($ledger['total_credit'], 2) }}</strong>
        </div>
        <div>
            <span>Closing Balance</span>
            <strong>Rs. {{ number_format($ledger['closing_balance'], 2) }}</strong>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Reference</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ledger['entries'] as $entry)
                    <tr>
                        <td>{{ optional($entry->transaction_date)->format('d M Y') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $entry->transaction_type)) }}</td>
                        <td>{{ $entry->remarks ?: '-' }}</td>
                        <td class="text-end">Rs. {{ number_format((float) $entry->debit, 2) }}</td>
                        <td class="text-end">Rs. {{ number_format((float) $entry->credit, 2) }}</td>
                        <td class="text-end">Rs. {{ number_format((float) $entry->balance, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No ledger entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
