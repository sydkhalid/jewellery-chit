<div class="row g-4">
    <div class="col-md-4">
        <label class="form-label" for="branch_code">Branch code</label>
        <input type="text" class="form-control" id="branch_code" name="branch_code" value="{{ old('branch_code', $branch->branch_code) }}" placeholder="Auto generated if empty">
        <div class="invalid-feedback" data-error-for="branch_code"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="name">Branch name</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $branch->name) }}" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            <option value="active" @selected(old('status', $branch->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $branch->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
        <div class="invalid-feedback" data-error-for="status"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="mobile">Mobile</label>
        <input type="text" class="form-control" id="mobile" name="mobile" value="{{ old('mobile', $branch->mobile) }}">
        <div class="invalid-feedback" data-error-for="mobile"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $branch->email) }}">
        <div class="invalid-feedback" data-error-for="email"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="pincode">Pincode</label>
        <input type="text" class="form-control" id="pincode" name="pincode" value="{{ old('pincode', $branch->pincode) }}">
        <div class="invalid-feedback" data-error-for="pincode"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="city">City</label>
        <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $branch->city) }}">
        <div class="invalid-feedback" data-error-for="city"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="state">State</label>
        <input type="text" class="form-control" id="state" name="state" value="{{ old('state', $branch->state) }}">
        <div class="invalid-feedback" data-error-for="state"></div>
    </div>

    <div class="col-12">
        <label class="form-label" for="address">Address</label>
        <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $branch->address) }}</textarea>
        <div class="invalid-feedback" data-error-for="address"></div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('branches.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Branch
        </button>
    </div>
</div>
