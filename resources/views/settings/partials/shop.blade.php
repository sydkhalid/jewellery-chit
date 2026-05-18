<h3 class="form-section-title">Shop Details</h3>
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="shop_name">Shop name</label>
        <input type="text" class="form-control" id="shop_name" name="shop_name" value="{{ old('shop_name', $values['shop_name'] ?? '') }}" required>
        <div class="invalid-feedback d-block" data-error-for="shop_name"></div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="shop_logo">Shop logo</label>
        <input type="file" class="form-control" id="shop_logo" name="shop_logo" accept="image/*">
        <div class="invalid-feedback d-block" data-error-for="shop_logo"></div>
    </div>
    @if (! empty($values['shop_logo']))
        <div class="col-12">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($values['shop_logo']) }}" alt="Shop logo" class="rounded border" style="max-height: 72px;">
        </div>
    @endif
    <div class="col-12">
        <label class="form-label" for="shop_address">Shop address</label>
        <textarea class="form-control" id="shop_address" name="shop_address" rows="3">{{ old('shop_address', $values['shop_address'] ?? '') }}</textarea>
        <div class="invalid-feedback d-block" data-error-for="shop_address"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="shop_mobile">Mobile</label>
        <input type="text" class="form-control" id="shop_mobile" name="shop_mobile" value="{{ old('shop_mobile', $values['shop_mobile'] ?? '') }}">
        <div class="invalid-feedback d-block" data-error-for="shop_mobile"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="shop_email">Email</label>
        <input type="email" class="form-control" id="shop_email" name="shop_email" value="{{ old('shop_email', $values['shop_email'] ?? '') }}">
        <div class="invalid-feedback d-block" data-error-for="shop_email"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="gstin">GSTIN</label>
        <input type="text" class="form-control" id="gstin" name="gstin" value="{{ old('gstin', $values['gstin'] ?? '') }}">
        <div class="invalid-feedback d-block" data-error-for="gstin"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="financial_year">Financial year</label>
        <input type="text" class="form-control" id="financial_year" name="financial_year" value="{{ old('financial_year', $values['financial_year'] ?? '') }}" required>
        <div class="invalid-feedback d-block" data-error-for="financial_year"></div>
    </div>
</div>
