<div class="row g-4">
    <div class="col-12">
        <h3 class="form-section-title">Scheme Details</h3>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="name">Scheme name</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $scheme->name) }}" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="scheme_type">Scheme type</label>
        <select class="form-select" id="scheme_type" name="scheme_type" data-scheme-type required>
            <option value="fixed_amount" @selected(old('scheme_type', $scheme->scheme_type) === 'fixed_amount')>Fixed Amount</option>
            <option value="flexible_amount" @selected(old('scheme_type', $scheme->scheme_type) === 'flexible_amount')>Flexible Amount</option>
            <option value="gold_weight" @selected(old('scheme_type', $scheme->scheme_type) === 'gold_weight')>Gold Weight</option>
        </select>
        <div class="invalid-feedback" data-error-for="scheme_type"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            <option value="active" @selected(old('status', $scheme->status) === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $scheme->status) === 'inactive')>Inactive</option>
        </select>
        <div class="invalid-feedback" data-error-for="status"></div>
    </div>

    <div class="col-md-4" data-scheme-field="fixed_amount">
        <label class="form-label" for="monthly_amount">Monthly amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="monthly_amount" name="monthly_amount" value="{{ old('monthly_amount', $scheme->monthly_amount) }}">
        <div class="invalid-feedback" data-error-for="monthly_amount"></div>
    </div>

    <div class="col-md-4" data-scheme-field="flexible_amount">
        <label class="form-label" for="min_amount">Minimum amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="min_amount" name="min_amount" value="{{ old('min_amount', $scheme->min_amount) }}">
        <div class="invalid-feedback" data-error-for="min_amount"></div>
    </div>

    <div class="col-md-4" data-scheme-field="flexible_amount">
        <label class="form-label" for="max_amount">Maximum amount</label>
        <input type="number" step="0.01" min="0" class="form-control" id="max_amount" name="max_amount" value="{{ old('max_amount', $scheme->max_amount) }}">
        <div class="invalid-feedback" data-error-for="max_amount"></div>
    </div>

    <div class="col-md-4" data-scheme-field="gold_weight">
        <label class="form-label" for="gold_weight">Gold weight</label>
        <input type="number" step="0.001" min="0" class="form-control" id="gold_weight" name="gold_weight" value="{{ old('gold_weight', $scheme->gold_weight) }}">
        <div class="invalid-feedback" data-error-for="gold_weight"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="duration_months">Duration months</label>
        <input type="number" min="1" class="form-control" id="duration_months" name="duration_months" value="{{ old('duration_months', $scheme->duration_months) }}" required>
        <div class="invalid-feedback" data-error-for="duration_months"></div>
    </div>

    <div class="col-12">
        <hr>
        <h3 class="form-section-title">Bonus & Fees</h3>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="shop_bonus_type">Shop bonus type</label>
        <select class="form-select" id="shop_bonus_type" name="shop_bonus_type" required>
            <option value="none" @selected(old('shop_bonus_type', $scheme->shop_bonus_type) === 'none')>None</option>
            <option value="fixed" @selected(old('shop_bonus_type', $scheme->shop_bonus_type) === 'fixed')>Fixed</option>
            <option value="percentage" @selected(old('shop_bonus_type', $scheme->shop_bonus_type) === 'percentage')>Percentage</option>
        </select>
        <div class="invalid-feedback" data-error-for="shop_bonus_type"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="shop_bonus_value">Shop bonus value</label>
        <input type="number" step="0.01" min="0" class="form-control" id="shop_bonus_value" name="shop_bonus_value" value="{{ old('shop_bonus_value', $scheme->shop_bonus_value ?? 0) }}">
        <div class="invalid-feedback" data-error-for="shop_bonus_value"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="late_fee_type">Late fee type</label>
        <select class="form-select" id="late_fee_type" name="late_fee_type" required>
            <option value="none" @selected(old('late_fee_type', $scheme->late_fee_type) === 'none')>None</option>
            <option value="fixed" @selected(old('late_fee_type', $scheme->late_fee_type) === 'fixed')>Fixed</option>
            <option value="percentage" @selected(old('late_fee_type', $scheme->late_fee_type) === 'percentage')>Percentage</option>
        </select>
        <div class="invalid-feedback" data-error-for="late_fee_type"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="late_fee_value">Late fee value</label>
        <input type="number" step="0.01" min="0" class="form-control" id="late_fee_value" name="late_fee_value" value="{{ old('late_fee_value', $scheme->late_fee_value ?? 0) }}">
        <div class="invalid-feedback" data-error-for="late_fee_value"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="grace_period_days">Grace period days</label>
        <input type="number" min="0" class="form-control" id="grace_period_days" name="grace_period_days" value="{{ old('grace_period_days', $scheme->grace_period_days ?? 0) }}">
        <div class="invalid-feedback" data-error-for="grace_period_days"></div>
    </div>

    <div class="col-12">
        <label class="form-label" for="maturity_rule">Maturity rule</label>
        <textarea class="form-control" id="maturity_rule" name="maturity_rule" rows="3">{{ old('maturity_rule', $scheme->maturity_rule) }}</textarea>
        <div class="invalid-feedback" data-error-for="maturity_rule"></div>
    </div>

    <div class="col-12">
        <label class="form-label" for="early_closing_rule">Early closing rule</label>
        <textarea class="form-control" id="early_closing_rule" name="early_closing_rule" rows="3">{{ old('early_closing_rule', $scheme->early_closing_rule) }}</textarea>
        <div class="invalid-feedback" data-error-for="early_closing_rule"></div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('chit-schemes.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Scheme
        </button>
    </div>
</div>
