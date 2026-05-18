@extends('layouts.admin')

@section('title', 'Customers')
@section('page-title', 'Customers')
@section('page-eyebrow', 'Customer Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Customer Register</h2>
            <p class="admin-section-copy">Manage customer profiles, documents, ledgers, and chit history.</p>
        </div>

        @can('customers.create')
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Add Customer
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label for="customer-status-filter" class="form-label">Status</label>
                <select id="customer-status-filter" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="customers-table" data-source="{{ route('customers.data') }}">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Chits</th>
                        <th>Documents</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
