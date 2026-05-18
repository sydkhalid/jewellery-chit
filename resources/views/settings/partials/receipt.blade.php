<h3 class="form-section-title">Receipt and Numbering Settings</h3>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="receipt_prefix">Receipt prefix</label>
        <input type="text" class="form-control" id="receipt_prefix" name="receipt_prefix" value="{{ old('receipt_prefix', $values['receipt_prefix'] ?? '') }}" required>
        <div class="invalid-feedback d-block" data-error-for="receipt_prefix"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="payment_number_prefix">Payment prefix</label>
        <input type="text" class="form-control" id="payment_number_prefix" name="payment_number_prefix" value="{{ old('payment_number_prefix', $values['payment_number_prefix'] ?? '') }}" required>
        <div class="invalid-feedback d-block" data-error-for="payment_number_prefix"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="invoice_number_prefix">Invoice prefix</label>
        <input type="text" class="form-control" id="invoice_number_prefix" name="invoice_number_prefix" value="{{ old('invoice_number_prefix', $values['invoice_number_prefix'] ?? '') }}" required>
        <div class="invalid-feedback d-block" data-error-for="invoice_number_prefix"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="closure_number_prefix">Closure prefix</label>
        <input type="text" class="form-control" id="closure_number_prefix" name="closure_number_prefix" value="{{ old('closure_number_prefix', $values['closure_number_prefix'] ?? '') }}" required>
        <div class="invalid-feedback d-block" data-error-for="closure_number_prefix"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="refund_number_prefix">Refund prefix</label>
        <input type="text" class="form-control" id="refund_number_prefix" name="refund_number_prefix" value="{{ old('refund_number_prefix', $values['refund_number_prefix'] ?? '') }}" required>
        <div class="invalid-feedback d-block" data-error-for="refund_number_prefix"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="handover_number_prefix">Handover prefix</label>
        <input type="text" class="form-control" id="handover_number_prefix" name="handover_number_prefix" value="{{ old('handover_number_prefix', $values['handover_number_prefix'] ?? '') }}" required>
        <div class="invalid-feedback d-block" data-error-for="handover_number_prefix"></div>
    </div>
    <div class="col-12">
        <label class="form-label" for="terms_and_conditions">Terms and conditions</label>
        <textarea class="form-control" id="terms_and_conditions" name="terms_and_conditions" rows="4">{{ old('terms_and_conditions', $values['terms_and_conditions'] ?? '') }}</textarea>
        <div class="invalid-feedback d-block" data-error-for="terms_and_conditions"></div>
    </div>
</div>
