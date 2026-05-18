@php
    $nominee = $customer->nominee;
@endphp

<div class="row g-4">
    <div class="col-12">
        <h3 class="form-section-title">Customer Details</h3>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="name">Name</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="mobile">Mobile</label>
        <input type="text" class="form-control" id="mobile" name="mobile" value="{{ old('mobile', $customer->mobile) }}" required>
        <div class="invalid-feedback" data-error-for="mobile"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="alternate_mobile">Alternate mobile</label>
        <input type="text" class="form-control" id="alternate_mobile" name="alternate_mobile" value="{{ old('alternate_mobile', $customer->alternate_mobile) }}">
        <div class="invalid-feedback" data-error-for="alternate_mobile"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $customer->email) }}">
        <div class="invalid-feedback" data-error-for="email"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="photo">Photo</label>
        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
        <div class="invalid-feedback" data-error-for="photo"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="aadhaar_no">Aadhaar number</label>
        <input type="text" class="form-control" id="aadhaar_no" name="aadhaar_no" value="{{ old('aadhaar_no', $customer->aadhaar_no) }}">
        <div class="invalid-feedback" data-error-for="aadhaar_no"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="pan_no">PAN number</label>
        <input type="text" class="form-control" id="pan_no" name="pan_no" value="{{ old('pan_no', $customer->pan_no) }}">
        <div class="invalid-feedback" data-error-for="pan_no"></div>
    </div>

    <div class="col-12">
        <label class="form-label" for="address">Address</label>
        <textarea class="form-control" id="address" name="address" rows="3" required>{{ old('address', $customer->address) }}</textarea>
        <div class="invalid-feedback" data-error-for="address"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="city">City</label>
        <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $customer->city) }}">
        <div class="invalid-feedback" data-error-for="city"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="state">State</label>
        <input type="text" class="form-control" id="state" name="state" value="{{ old('state', $customer->state) }}">
        <div class="invalid-feedback" data-error-for="state"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="pincode">Pincode</label>
        <input type="text" class="form-control" id="pincode" name="pincode" value="{{ old('pincode', $customer->pincode) }}">
        <div class="invalid-feedback" data-error-for="pincode"></div>
    </div>

    <div class="col-12">
        <hr>
        <h3 class="form-section-title">Nominee Details</h3>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="nominee_name">Nominee name</label>
        <input type="text" class="form-control" id="nominee_name" name="nominee[name]" value="{{ old('nominee.name', $nominee?->name) }}">
        <div class="invalid-feedback" data-error-for="nominee.name"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="nominee_relationship">Relationship</label>
        <input type="text" class="form-control" id="nominee_relationship" name="nominee[relationship]" value="{{ old('nominee.relationship', $nominee?->relationship) }}">
        <div class="invalid-feedback" data-error-for="nominee.relationship"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="nominee_mobile">Mobile</label>
        <input type="text" class="form-control" id="nominee_mobile" name="nominee[mobile]" value="{{ old('nominee.mobile', $nominee?->mobile) }}">
        <div class="invalid-feedback" data-error-for="nominee.mobile"></div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="nominee_aadhaar_no">Aadhaar number</label>
        <input type="text" class="form-control" id="nominee_aadhaar_no" name="nominee[aadhaar_no]" value="{{ old('nominee.aadhaar_no', $nominee?->aadhaar_no) }}">
        <div class="invalid-feedback" data-error-for="nominee.aadhaar_no"></div>
    </div>

    <div class="col-12">
        <label class="form-label" for="nominee_address">Address</label>
        <textarea class="form-control" id="nominee_address" name="nominee[address]" rows="2">{{ old('nominee.address', $nominee?->address) }}</textarea>
        <div class="invalid-feedback" data-error-for="nominee.address"></div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('customers.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Customer
        </button>
    </div>
</div>
