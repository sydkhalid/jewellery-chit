@extends('layouts.admin')

@section('title', 'Chit Schemes')
@section('page-title', 'Chit Schemes')
@section('page-eyebrow', 'Scheme Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Scheme Register</h2>
            <p class="admin-section-copy">Configure fixed amount, flexible amount, and gold weight chit plans.</p>
        </div>

        @can('schemes.create')
            <a href="{{ route('chit-schemes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Add Scheme
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label for="scheme-type-filter" class="form-label">Scheme type</label>
                <select id="scheme-type-filter" class="form-select">
                    <option value="">All types</option>
                    <option value="fixed_amount">Fixed Amount</option>
                    <option value="flexible_amount">Flexible Amount</option>
                    <option value="gold_weight">Gold Weight</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="scheme-status-filter" class="form-label">Status</label>
                <select id="scheme-status-filter" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="chit-schemes-table" data-source="{{ route('chit-schemes.data') }}">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Amount/Weight</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Enrollments</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
