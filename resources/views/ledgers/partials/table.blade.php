<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h3>Transaction List</h3>
            <p>Debit increases dues. Credit reduces dues through payment or adjustment.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Chit No</th>
                    <th>Type</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Balance</th>
                    <th>Reference</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>{{ optional($entry->transaction_date)->format('d M Y') }}</td>
                        <td>{{ $entry->enrollment?->chit_no }}</td>
                        <td><span class="badge text-bg-light">{{ ucfirst(str_replace('_', ' ', $entry->transaction_type)) }}</span></td>
                        <td class="text-end">Rs. {{ number_format((float) $entry->debit, 2) }}</td>
                        <td class="text-end">Rs. {{ number_format((float) $entry->credit, 2) }}</td>
                        <td class="text-end">Rs. {{ number_format((float) $entry->balance, 2) }}</td>
                        <td>{{ $entry->reference_type ? class_basename($entry->reference_type).' #'.$entry->reference_id : '-' }}</td>
                        <td>{{ $entry->remarks ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No ledger entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
