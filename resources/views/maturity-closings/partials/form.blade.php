@php
    $selectedEnrollmentId = old('enrollment_id', $closure->enrollment_id);
@endphp

<div class="row g-4">
    <div class="col-12">
        <h3 class="form-section-title">Closing Details</h3>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="enrollment_id">Chit enrollment</label>
        <select class="form-select" id="enrollment_id" name="enrollment_id" data-maturity-enrollment required>
            <option value="">Select chit</option>
            @foreach ($enrollments as $enrollment)
                <option value="{{ $enrollment->id }}" @selected((int) $selectedEnrollmentId === $enrollment->id)>
                    {{ $enrollment->chit_no }} - {{ $enrollment->customer?->name }} - {{ $enrollment->scheme?->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="enrollment_id"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="closure_type">Closure type</label>
        <select class="form-select" id="closure_type" name="closure_type" required>
            @foreach ($closureTypes as $type)
                <option value="{{ $type }}" @selected(old('closure_type', $closure->closure_type ?? 'normal') === $type)>
                    {{ ucfirst($type) }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="closure_type"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="deductions">Deductions</label>
        <input type="number" step="0.01" min="0" class="form-control" id="deductions" name="deductions" data-maturity-deductions value="{{ old('deductions', $closure->deductions ?? 0) }}">
        <div class="invalid-feedback" data-error-for="deductions"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="refund_amount">Refund amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="refund_amount" name="refund_amount" value="{{ old('refund_amount', $closure->refund_amount ?? 0) }}">
        <div class="invalid-feedback" data-error-for="refund_amount"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="jewellery_adjustment_amount">Jewellery adjustment</label>
        <input type="number" step="0.01" min="0" class="form-control" id="jewellery_adjustment_amount" name="jewellery_adjustment_amount" value="{{ old('jewellery_adjustment_amount', $closure->jewellery_adjustment_amount ?? 0) }}">
        <div class="invalid-feedback" data-error-for="jewellery_adjustment_amount"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="customer_signature">Customer signature</label>
        <input type="file" class="form-control" id="customer_signature" name="customer_signature" accept="image/*">
        <div class="invalid-feedback" data-error-for="customer_signature"></div>
    </div>

    <div class="col-md-8">
        <label class="form-label" for="remarks">Remarks</label>
        <textarea class="form-control" id="remarks" name="remarks" rows="3">{{ old('remarks', $closure->remarks) }}</textarea>
        <div class="invalid-feedback" data-error-for="remarks"></div>
    </div>

    <div class="col-md-4">
        <div class="scheme-info-panel h-100" data-maturity-summary>
            Select a chit enrollment to calculate maturity value.
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('maturity-closings.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Closing
        </button>
    </div>
</div>
