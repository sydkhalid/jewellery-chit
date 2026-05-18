@extends('layouts.admin')

@section('title', 'Jewellery Invoices')
@section('page-title', 'Jewellery Invoices')
@section('page-eyebrow', 'Jewellery Billing')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Invoice Register</h2>
            <p class="admin-section-copy">Create jewellery invoices and apply matured chit adjustments where eligible.</p>
        </div>

        @can('jewellery.create')
            <a href="{{ route('jewellery-invoices.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Create Invoice
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="jewellery-customer-filter">Customer</label>
                <select id="jewellery-customer-filter" class="form-select">
                    <option value="">All customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="jewellery-enrollment-filter">Chit number</label>
                <select id="jewellery-enrollment-filter" class="form-select">
                    <option value="">All chits</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}">{{ $enrollment->chit_no }} - {{ $enrollment->customer?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="jewellery-status-filter">Status</label>
                <select id="jewellery-status-filter" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="jewellery-from-filter">From</label>
                <input type="date" id="jewellery-from-filter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="jewellery-to-filter">To</label>
                <input type="date" id="jewellery-to-filter" class="form-control">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="jewellery-invoices-table" data-source="{{ route('jewellery-invoices.data') }}">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Chit No</th>
                        <th>Scheme</th>
                        <th>Date</th>
                        <th class="text-end">Gold Rate</th>
                        <th class="text-end">Net Wt.</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Chit Adj.</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
