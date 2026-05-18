@php
    $selectedCustomerId = old('customer_id', $payment->customer_id);
    $selectedEnrollmentId = old('enrollment_id', $payment->enrollment_id);
    $selectedInstallmentId = old('installment_id', $payment->installment_id);
    $selectedPaymentModeId = old('payment_mode_id', $payment->payment_mode_id);
    $selectedBranchId = old('branch_id', $payment->branch_id ?? auth()->user()?->branch_id);
    $selectedStaffId = old('staff_id', $payment->staff_id ?? auth()->id());
@endphp

<div class="row g-4">
    <div class="col-12">
        <h3 class="form-section-title">Payment Details</h3>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="customer_id">Customer</label>
        <select class="form-select" id="customer_id" name="customer_id" data-payment-customer required>
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
        <label class="form-label" for="enrollment_id">Chit enrollment</label>
        <select class="form-select" id="enrollment_id" name="enrollment_id" data-payment-enrollment required>
            <option value="">Select chit</option>
            @foreach ($enrollments as $enrollment)
                @php
                    $enrollmentPayload = [
                        'id' => $enrollment->id,
                        'customer_id' => $enrollment->customer_id,
                        'chit_no' => $enrollment->chit_no,
                        'branch_id' => $enrollment->branch_id,
                        'staff_id' => $enrollment->assigned_staff_id,
                        'total_payable' => $enrollment->total_payable,
                        'total_paid' => $enrollment->total_paid,
                        'total_pending' => $enrollment->total_pending,
                    ];
                @endphp
                <option
                    value="{{ $enrollment->id }}"
                    data-customer="{{ $enrollment->customer_id }}"
                    data-enrollment="{{ e(json_encode($enrollmentPayload)) }}"
                    @selected((int) $selectedEnrollmentId === $enrollment->id)
                >
                    {{ $enrollment->chit_no }} - {{ $enrollment->customer?->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="enrollment_id"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="installment_id">Pending installment</label>
        <select class="form-select" id="installment_id" name="installment_id" data-payment-installment>
            <option value="">Auto select first due</option>
            @foreach ($enrollments as $enrollment)
                @foreach ($enrollment->installments as $installment)
                    @php
                        $installmentPayload = [
                            'id' => $installment->id,
                            'enrollment_id' => $installment->enrollment_id,
                            'installment_no' => $installment->installment_no,
                            'due_date' => optional($installment->due_date)->toDateString(),
                            'due_amount' => $installment->due_amount,
                            'paid_amount' => $installment->paid_amount,
                            'balance_amount' => $installment->balance_amount,
                            'late_fee' => $installment->late_fee,
                            'status' => $installment->status,
                        ];
                    @endphp
                    <option
                        value="{{ $installment->id }}"
                        data-enrollment="{{ $enrollment->id }}"
                        data-installment="{{ e(json_encode($installmentPayload)) }}"
                        @selected((int) $selectedInstallmentId === $installment->id)
                    >
                        #{{ $installment->installment_no }} / {{ optional($installment->due_date)->format('d M Y') }} / Rs. {{ number_format((float) $installment->balance_amount, 2) }}
                    </option>
                @endforeach
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="installment_id"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="payment_type">Payment type</label>
        <select class="form-select" id="payment_type" name="payment_type" data-payment-type required>
            @foreach (['full' => 'Full', 'partial' => 'Partial', 'advance' => 'Advance', 'multiple_month' => 'Multiple Month'] as $value => $label)
                <option value="{{ $value }}" @selected(old('payment_type', $payment->payment_type ?? 'partial') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="payment_type"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="payment_date">Payment date</label>
        <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ old('payment_date', optional($payment->payment_date)->toDateString() ?? now()->toDateString()) }}" required>
        <div class="invalid-feedback" data-error-for="payment_date"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="amount">Amount</label>
        <input type="number" step="0.01" min="1" class="form-control" id="amount" name="amount" data-payment-amount value="{{ old('amount', $payment->amount) }}" required>
        <div class="invalid-feedback" data-error-for="amount"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="late_fee_amount">Late fee amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="late_fee_amount" name="late_fee_amount" data-payment-late-fee value="{{ old('late_fee_amount', $payment->late_fee_amount ?? 0) }}">
        <div class="invalid-feedback" data-error-for="late_fee_amount"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="payment_mode_id">Payment mode</label>
        <select class="form-select" id="payment_mode_id" name="payment_mode_id" data-payment-mode required>
            <option value="">Select mode</option>
            @foreach ($paymentModes as $mode)
                <option value="{{ $mode->id }}" data-code="{{ $mode->code }}" @selected((int) $selectedPaymentModeId === $mode->id)>{{ $mode->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="payment_mode_id"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="transaction_id">Transaction ID</label>
        <input type="text" class="form-control" id="transaction_id" name="transaction_id" data-payment-transaction value="{{ old('transaction_id', $payment->transaction_id) }}">
        <div class="form-text" data-payment-transaction-help>Required for non-cash payments.</div>
        <div class="invalid-feedback" data-error-for="transaction_id"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="staff_id">Staff</label>
        <select class="form-select" id="staff_id" name="staff_id">
            <option value="">Logged in staff</option>
            @foreach ($staffUsers as $staff)
                <option value="{{ $staff->id }}" @selected((int) $selectedStaffId === $staff->id)>{{ $staff->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="staff_id"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="branch_id">Branch</label>
        <select class="form-select" id="branch_id" name="branch_id">
            <option value="">Default branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) $selectedBranchId === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="branch_id"></div>
    </div>

    <div class="col-md-8">
        <label class="form-label" for="remarks">Remarks</label>
        <textarea class="form-control" id="remarks" name="remarks" rows="3">{{ old('remarks', $payment->remarks) }}</textarea>
        <div class="invalid-feedback" data-error-for="remarks"></div>
    </div>

    <div class="col-md-4">
        <div class="scheme-info-panel h-100" data-payment-summary>
            Select customer, chit, and installment to view payment summary.
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('payments.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Payment
        </button>
    </div>
</div>
