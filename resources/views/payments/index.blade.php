@extends('layouts.admin')

@section('title', 'Payments')
@section('page-title', 'Payments')
@section('page-eyebrow', 'Payment Collection')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Payment Register</h2>
            <p class="admin-section-copy">Track collections, receipts, payment modes, staff collections, and cancellations.</p>
        </div>

        @can('payments.create')
            <a href="{{ route('payments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Collect Payment
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="payment-customer-filter">Customer</label>
                <select id="payment-customer-filter" class="form-select">
                    <option value="">All customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="payment-enrollment-filter">Chit number</label>
                <select id="payment-enrollment-filter" class="form-select">
                    <option value="">All chits</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}">{{ $enrollment->chit_no }} - {{ $enrollment->customer?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment-mode-filter">Mode</label>
                <select id="payment-mode-filter" class="form-select">
                    <option value="">All modes</option>
                    @foreach ($paymentModes as $mode)
                        <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment-staff-filter">Staff</label>
                <select id="payment-staff-filter" class="form-select">
                    <option value="">All staff</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment-branch-filter">Branch</label>
                <select id="payment-branch-filter" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment-status-filter">Status</label>
                <select id="payment-status-filter" class="form-select">
                    <option value="">All</option>
                    <option value="success">Success</option>
                    <option value="pending">Pending</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment-from-filter">From</label>
                <input type="date" id="payment-from-filter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment-to-filter">To</label>
                <input type="date" id="payment-to-filter" class="form-control">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="payments-table" data-source="{{ route('payments.data') }}">
                <thead>
                    <tr>
                        <th>Payment No</th>
                        <th>Customer</th>
                        <th>Chit No</th>
                        <th>Date</th>
                        <th>Mode</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Late Fee</th>
                        <th class="text-end">Total</th>
                        <th>Staff</th>
                        <th>Status</th>
                        <th>Receipt</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @include('payments.partials.cancel-modal')
@endsection
