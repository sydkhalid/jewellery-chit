@php
    $selectedSchemeId = old('scheme_id', $enrollment->scheme_id);
    $selectedCustomerId = old('customer_id', $enrollment->customer_id);
    $selectedBranchId = old('branch_id', $enrollment->branch_id ?? auth()->user()?->branch_id);
    $selectedStaffId = old('assigned_staff_id', $enrollment->assigned_staff_id);
    $selectedScheme = $schemes->firstWhere('id', (int) $selectedSchemeId);
@endphp

<div class="row g-4">
    <div class="col-12">
        <h3 class="form-section-title">Enrollment Details</h3>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="customer_id">Customer</label>
        <select class="form-select" id="customer_id" name="customer_id" required>
            <option value="">Select customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((int) $selectedCustomerId === $customer->id)>
                    {{ $customer->customer_code }} - {{ $customer->name }} - {{ $customer->mobile }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="customer_id"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="scheme_id">Scheme</label>
        <select class="form-select" id="scheme_id" name="scheme_id" data-enrollment-scheme required>
            <option value="">Select scheme</option>
            @foreach ($schemes as $scheme)
                @php
                    $schemePayload = [
                        'id' => $scheme->id,
                        'scheme_type' => $scheme->scheme_type,
                        'monthly_amount' => $scheme->monthly_amount,
                        'min_amount' => $scheme->min_amount,
                        'max_amount' => $scheme->max_amount,
                        'gold_weight' => $scheme->gold_weight,
                        'duration_months' => $scheme->duration_months,
                    ];
                @endphp
                <option
                    value="{{ $scheme->id }}"
                    data-scheme="{{ e(json_encode($schemePayload)) }}"
                    @selected((int) $selectedSchemeId === $scheme->id)
                >
                    {{ $scheme->scheme_code }} - {{ $scheme->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="scheme_id"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="branch_id">Branch</label>
        <select class="form-select" id="branch_id" name="branch_id">
            <option value="">Default branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) $selectedBranchId === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="branch_id"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="assigned_staff_id">Assigned staff</label>
        <select class="form-select" id="assigned_staff_id" name="assigned_staff_id">
            <option value="">Not assigned</option>
            @foreach ($staffUsers as $staff)
                <option value="{{ $staff->id }}" @selected((int) $selectedStaffId === $staff->id)>{{ $staff->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="assigned_staff_id"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="start_date">Start date</label>
        <input type="date" class="form-control" id="start_date" name="start_date" data-enrollment-start-date value="{{ old('start_date', optional($enrollment->start_date)->toDateString() ?? now()->toDateString()) }}" required>
        <div class="invalid-feedback" data-error-for="start_date"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="monthly_due_date_preview">Monthly due date</label>
        <input type="text" class="form-control" id="monthly_due_date_preview" data-enrollment-due-date value="{{ old('monthly_due_date', $enrollment->monthly_due_date) }}" readonly>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="maturity_date_preview">Maturity date</label>
        <input type="date" class="form-control" id="maturity_date_preview" data-enrollment-maturity-date value="{{ old('maturity_date', optional($enrollment->maturity_date)->toDateString()) }}" readonly>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="monthly_amount">Monthly amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="monthly_amount" name="monthly_amount" data-enrollment-monthly-amount value="{{ old('monthly_amount', $enrollment->monthly_amount ?? $selectedScheme?->monthly_amount) }}">
        <div class="form-text" data-enrollment-amount-help></div>
        <div class="invalid-feedback" data-error-for="monthly_amount"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="agreement_file">Agreement upload</label>
        <input type="file" class="form-control" id="agreement_file" name="agreement_file" accept=".pdf,image/*">
        <div class="invalid-feedback" data-error-for="agreement_file"></div>
    </div>

    <div class="col-md-4">
        <div class="scheme-info-panel" data-enrollment-scheme-info>
            Select a scheme to view amount rules.
        </div>
    </div>

    <div class="col-12">
        <label class="form-label" for="remarks">Remarks</label>
        <textarea class="form-control" id="remarks" name="remarks" rows="3">{{ old('remarks', $enrollment->remarks) }}</textarea>
        <div class="invalid-feedback" data-error-for="remarks"></div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('chit-enrollments.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Enrollment
        </button>
    </div>
</div>
