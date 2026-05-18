@php
    $mode = $mode ?? 'entry';
@endphp

<div class="row g-4">
    <div class="col-md-4">
        <label class="form-label" for="cashbook_date">Date</label>
        <input type="date" class="form-control" id="cashbook_date" name="cashbook_date" value="{{ old('cashbook_date', optional($cashbook->cashbook_date)->toDateString() ?? today()->toDateString()) }}" required>
        <div class="invalid-feedback" data-error-for="cashbook_date"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="branch_id">Branch</label>
        <select class="form-select" id="branch_id" name="branch_id">
            <option value="">All / Main cash</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $cashbook->branch_id) === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="branch_id"></div>
    </div>

    @if ($mode === 'entry')
        <div class="col-md-4">
            <label class="form-label" for="transaction_type">Transaction type</label>
            <select class="form-select" id="transaction_type" name="transaction_type" required>
                @foreach ($transactionTypes as $type)
                    <option value="{{ $type }}" @selected(old('transaction_type', $cashbook->transaction_type) === $type)>{{ str($type)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback" data-error-for="transaction_type"></div>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="payment_mode_id">Payment mode</label>
            <select class="form-select" id="payment_mode_id" name="payment_mode_id">
                <option value="">Not specified</option>
                @foreach ($paymentModes as $modeOption)
                    <option value="{{ $modeOption->id }}" @selected((int) old('payment_mode_id', $cashbook->payment_mode_id) === $modeOption->id)>{{ $modeOption->name }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback" data-error-for="payment_mode_id"></div>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="debit">Debit</label>
            <input type="number" step="0.01" min="0" class="form-control" id="debit" name="debit" value="{{ old('debit', $cashbook->debit ?? 0) }}">
            <div class="invalid-feedback" data-error-for="debit"></div>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="credit">Credit</label>
            <input type="number" step="0.01" min="0" class="form-control" id="credit" name="credit" value="{{ old('credit', $cashbook->credit ?? 0) }}">
            <div class="invalid-feedback" data-error-for="credit"></div>
        </div>
    @elseif ($mode === 'opening')
        <input type="hidden" name="transaction_type" value="opening_balance">
        <div class="col-md-4">
            <label class="form-label" for="credit">Opening balance</label>
            <input type="number" step="0.01" min="0" class="form-control" id="credit" name="credit" value="{{ old('credit', $cashbook->credit ?? 0) }}" required>
            <div class="invalid-feedback" data-error-for="credit"></div>
        </div>
    @else
        <input type="hidden" name="transaction_type" value="closing_balance">
    @endif

    <div class="col-12">
        <label class="form-label" for="remarks">Remarks</label>
        <textarea class="form-control" id="remarks" name="remarks" rows="3">{{ old('remarks', $cashbook->remarks) }}</textarea>
        <div class="invalid-feedback" data-error-for="remarks"></div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('cashbooks.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Cashbook
        </button>
    </div>
</div>
