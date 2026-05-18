@extends('layouts.admin')

@section('title', 'Maturity Closings')
@section('page-title', 'Maturity Closings')
@section('page-eyebrow', 'Maturity Closing')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Closing Register</h2>
            <p class="admin-section-copy">Review normal, early, defaulted, and cancelled chit closing requests.</p>
        </div>

        @can('maturity.create')
            <a href="{{ route('maturity-closings.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>New Closing
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="maturity-customer-filter">Customer</label>
                <select id="maturity-customer-filter" class="form-select">
                    <option value="">All customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="maturity-enrollment-filter">Chit number</label>
                <select id="maturity-enrollment-filter" class="form-select">
                    <option value="">All chits</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}">{{ $enrollment->chit_no }} - {{ $enrollment->customer?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="maturity-type-filter">Type</label>
                <select id="maturity-type-filter" class="form-select">
                    <option value="">All types</option>
                    @foreach ($closureTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="maturity-status-filter">Status</label>
                <select id="maturity-status-filter" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="maturity-from-filter">From</label>
                <input type="date" id="maturity-from-filter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="maturity-to-filter">To</label>
                <input type="date" id="maturity-to-filter" class="form-control">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="maturity-closings-table" data-source="{{ route('maturity-closings.data') }}">
                <thead>
                    <tr>
                        <th>Closing No</th>
                        <th>Customer</th>
                        <th>Chit No</th>
                        <th>Scheme</th>
                        <th>Type</th>
                        <th class="text-end">Total Paid</th>
                        <th class="text-end">Bonus</th>
                        <th class="text-end">Final Value</th>
                        <th class="text-end">Refund</th>
                        <th class="text-end">Jewellery Adj.</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
