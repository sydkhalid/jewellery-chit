@php
    $selectedStaffId = old('staff_id', $handover->staff_id ?? auth()->id());
    $selectedBranchId = old('branch_id', $handover->branch_id ?? auth()->user()?->branch_id);
@endphp

<div class="row g-4">
    <div class="col-md-4">
        <label class="form-label" for="staff_id">Staff</label>
        <select class="form-select" id="staff_id" name="staff_id" required>
            <option value="">Select staff</option>
            @foreach ($staffUsers as $staff)
                <option value="{{ $staff->id }}" data-branch="{{ $staff->branch_id }}" @selected((int) $selectedStaffId === $staff->id)>{{ $staff->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="staff_id"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="branch_id">Branch</label>
        <select class="form-select" id="branch_id" name="branch_id">
            <option value="">Staff branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) $selectedBranchId === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="branch_id"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="handover_date">Handover date</label>
        <input type="date" class="form-control" id="handover_date" name="handover_date" value="{{ old('handover_date', optional($handover->handover_date)->toDateString() ?? today()->toDateString()) }}" required>
        <div class="invalid-feedback" data-error-for="handover_date"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="cash_amount">Cash amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="cash_amount" name="cash_amount" data-handover-amount value="{{ old('cash_amount', $handover->cash_amount ?? 0) }}">
        <div class="invalid-feedback" data-error-for="cash_amount"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="upi_amount">UPI amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="upi_amount" name="upi_amount" data-handover-amount value="{{ old('upi_amount', $handover->upi_amount ?? 0) }}">
        <div class="invalid-feedback" data-error-for="upi_amount"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="card_amount">Card amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="card_amount" name="card_amount" data-handover-amount value="{{ old('card_amount', $handover->card_amount ?? 0) }}">
        <div class="invalid-feedback" data-error-for="card_amount"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="bank_amount">Bank amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="bank_amount" name="bank_amount" data-handover-amount value="{{ old('bank_amount', $handover->bank_amount ?? 0) }}">
        <div class="invalid-feedback" data-error-for="bank_amount"></div>
    </div>

    <div class="col-md-8">
        <label class="form-label" for="remarks">Remarks</label>
        <textarea class="form-control" id="remarks" name="remarks" rows="3">{{ old('remarks', $handover->remarks) }}</textarea>
        <div class="invalid-feedback" data-error-for="remarks"></div>
    </div>

    <div class="col-md-4">
        <div class="scheme-info-panel h-100">
            <div class="text-muted small mb-2">Handover Total</div>
            <div class="fs-4 fw-semibold" data-handover-total>Rs. 0.00</div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('staff-cash-handovers.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Handover
        </button>
    </div>
</div>
