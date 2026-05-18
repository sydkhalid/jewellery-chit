@extends('layouts.admin')

@section('title', 'Branches')
@section('page-title', 'Branches')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Branch Register</h2>
            <p class="admin-section-copy">Maintain branch details used by staff, enrollments, payments, and cashflow.</p>
        </div>

        @can('branch.create')
            <a href="{{ route('branches.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Add Branch
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="branch-status-filter">Status</label>
                <select id="branch-status-filter" class="form-select">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="branch-city-filter">City</label>
                <input type="text" id="branch-city-filter" class="form-control" placeholder="Search city">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="branches-table" data-source="{{ route('branches.data') }}">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>City</th>
                        <th>Status</th>
                        <th class="text-end">Users</th>
                        <th class="text-end">Enrollments</th>
                        <th class="text-end">Payments</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
