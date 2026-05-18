<div class="row g-4">
    <div class="col-12">
        <h3 class="form-section-title">Installment Details</h3>
    </div>

    <div class="col-md-6">
        <label class="form-label">Enrollment</label>
        <input type="text" class="form-control" value="{{ $installment->enrollment?->chit_no }} - {{ $installment->enrollment?->customer?->name }}" readonly>
    </div>

    <div class="col-md-6">
        <label class="form-label">Installment No</label>
        <input type="text" class="form-control" value="{{ $installment->installment_no }}" readonly>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="due_date">Due date</label>
        <input type="date" class="form-control" id="due_date" name="due_date" value="{{ old('due_date', optional($installment->due_date)->toDateString()) }}" required>
        <div class="invalid-feedback" data-error-for="due_date"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="due_amount">Due amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="due_amount" name="due_amount" value="{{ old('due_amount', $installment->due_amount) }}" required>
        <div class="invalid-feedback" data-error-for="due_amount"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="late_fee">Late fee</label>
        <input type="number" step="0.01" min="0" class="form-control" id="late_fee" name="late_fee" value="{{ old('late_fee', $installment->late_fee) }}">
        <div class="invalid-feedback" data-error-for="late_fee"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label">Paid amount</label>
        <input type="text" class="form-control" value="Rs. {{ number_format((float) $installment->paid_amount, 2) }}" readonly>
    </div>

    <div class="col-md-4">
        <label class="form-label">Balance amount</label>
        <input type="text" class="form-control" value="Rs. {{ number_format((float) $installment->balance_amount, 2) }}" readonly>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            @foreach (['pending', 'partial', 'paid', 'overdue', 'advance'] as $status)
                <option value="{{ $status }}" @selected(old('status', $installment->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="status"></div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('installments.show', $installment) }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Installment
        </button>
    </div>
</div>
