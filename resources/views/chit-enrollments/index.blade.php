@extends('layouts.admin')

@section('title', 'Chit Enrollments')
@section('page-title', 'Chit Enrollments')
@section('page-eyebrow', 'Enrollment Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Enrollment Register</h2>
            <p class="admin-section-copy">Create and manage customer chit accounts and installment schedules.</p>
        </div>

        @can('enrollments.create')
            <a href="{{ route('chit-enrollments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>New Enrollment
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="enrollment-customer-filter">Customer</label>
                <select id="enrollment-customer-filter" class="form-select">
                    <option value="">All customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="enrollment-scheme-filter">Scheme</label>
                <select id="enrollment-scheme-filter" class="form-select">
                    <option value="">All schemes</option>
                    @foreach ($schemes as $scheme)
                        <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="enrollment-staff-filter">Staff</label>
                <select id="enrollment-staff-filter" class="form-select">
                    <option value="">All staff</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="enrollment-branch-filter">Branch</label>
                <select id="enrollment-branch-filter" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="enrollment-status-filter">Status</label>
                <select id="enrollment-status-filter" class="form-select">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="matured">Matured</option>
                    <option value="closed">Closed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="defaulted">Defaulted</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="enrollment-from-filter">From</label>
                <input type="date" id="enrollment-from-filter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="enrollment-to-filter">To</label>
                <input type="date" id="enrollment-to-filter" class="form-control">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="chit-enrollments-table" data-source="{{ route('chit-enrollments.data') }}">
                <thead>
                    <tr>
                        <th>Chit No</th>
                        <th>Customer</th>
                        <th>Scheme</th>
                        <th>Branch</th>
                        <th>Staff</th>
                        <th>Start</th>
                        <th>Maturity</th>
                        <th class="text-end">Payable</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @include('chit-enrollments.partials.cancel-modal')
@endsection
