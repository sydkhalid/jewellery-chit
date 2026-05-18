<h3 class="form-section-title">Chit Settings</h3>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="chit_number_prefix">Chit number prefix</label>
        <input type="text" class="form-control" id="chit_number_prefix" name="chit_number_prefix" value="{{ old('chit_number_prefix', $values['chit_number_prefix'] ?? '') }}" required>
        <div class="invalid-feedback d-block" data-error-for="chit_number_prefix"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="default_grace_period_days">Default grace period days</label>
        <input type="number" min="0" class="form-control" id="default_grace_period_days" name="default_grace_period_days" value="{{ old('default_grace_period_days', $values['default_grace_period_days'] ?? 0) }}">
        <div class="invalid-feedback d-block" data-error-for="default_grace_period_days"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="default_late_fee_type">Default late fee type</label>
        <select class="form-select" id="default_late_fee_type" name="default_late_fee_type">
            @foreach (['none' => 'None', 'fixed' => 'Fixed', 'percentage' => 'Percentage'] as $key => $label)
                <option value="{{ $key }}" @selected(old('default_late_fee_type', $values['default_late_fee_type'] ?? 'none') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback d-block" data-error-for="default_late_fee_type"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="default_late_fee_value">Default late fee value</label>
        <input type="number" min="0" step="0.01" class="form-control" id="default_late_fee_value" name="default_late_fee_value" value="{{ old('default_late_fee_value', $values['default_late_fee_value'] ?? 0) }}">
        <div class="invalid-feedback d-block" data-error-for="default_late_fee_value"></div>
    </div>
</div>
