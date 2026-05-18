@php
    $selectedRole = old('role', $staff->exists ? $staff->getRoleNames()->first() : 'Staff');
@endphp

<div class="row g-4">
    <div class="col-md-4">
        <label class="form-label" for="name">Name</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $staff->name) }}" required>
        <div class="invalid-feedback" data-error-for="name"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $staff->email) }}" required>
        <div class="invalid-feedback" data-error-for="email"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="mobile">Mobile</label>
        <input type="text" class="form-control" id="mobile" name="mobile" value="{{ old('mobile', $staff->mobile) }}">
        <div class="invalid-feedback" data-error-for="mobile"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="password">Password</label>
        <input type="password" class="form-control" id="password" name="password" @required(! $staff->exists)>
        <div class="form-text">{{ $staff->exists ? 'Leave blank to keep current password.' : 'Minimum 8 characters.' }}</div>
        <div class="invalid-feedback" data-error-for="password"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="branch_id">Branch</label>
        <select class="form-select" id="branch_id" name="branch_id" required>
            <option value="">Select branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $staff->branch_id) === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="branch_id"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="role">Role</label>
        <select class="form-select" id="role" name="role" required>
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="role"></div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            <option value="active" @selected(old('status', $staff->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $staff->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
        <div class="invalid-feedback" data-error-for="status"></div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('staff.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Staff
        </button>
    </div>
</div>
