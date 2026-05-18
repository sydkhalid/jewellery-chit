@extends('layouts.admin')

@section('title', 'Staff')
@section('page-title', 'Staff')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Staff Users</h2>
            <p class="admin-section-copy">Manage staff logins, roles, branches, and collection responsibility.</p>
        </div>

        @can('staff.create')
            <a href="{{ route('staff.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Add Staff
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="staff-role-filter">Role</label>
                <select id="staff-role-filter" class="form-select">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="staff-branch-filter">Branch</label>
                <select id="staff-branch-filter" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="staff-status-filter">Status</label>
                <select id="staff-status-filter" class="form-select">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="staff-table" data-source="{{ route('staff.data') }}">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th class="text-end">Collections</th>
                        <th class="text-end">Enrollments</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
