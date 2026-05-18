@extends('layouts.admin')

@section('title', 'Receipts')
@section('page-title', 'Receipts')
@section('page-eyebrow', 'Receipt Register')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Receipt Register</h2>
            <p class="admin-section-copy">View receipts, print thermal and A4 copies, download PDFs, share placeholders, and cancel standalone receipts.</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="receipt-customer-filter">Customer</label>
                <select id="receipt-customer-filter" class="form-select">
                    <option value="">All customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="receipt-enrollment-filter">Chit number</label>
                <select id="receipt-enrollment-filter" class="form-select">
                    <option value="">All chits</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}">{{ $enrollment->chit_no }} - {{ $enrollment->customer?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="receipt-mode-filter">Mode</label>
                <select id="receipt-mode-filter" class="form-select">
                    <option value="">All modes</option>
                    @foreach ($paymentModes as $mode)
                        <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="receipt-staff-filter">Staff</label>
                <select id="receipt-staff-filter" class="form-select">
                    <option value="">All staff</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="receipt-branch-filter">Branch</label>
                <select id="receipt-branch-filter" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="receipt-status-filter">Status</label>
                <select id="receipt-status-filter" class="form-select">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="receipt-from-filter">From</label>
                <input type="date" id="receipt-from-filter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="receipt-to-filter">To</label>
                <input type="date" id="receipt-to-filter" class="form-control">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="receipts-table" data-source="{{ route('receipts.data') }}">
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Customer</th>
                        <th>Chit No</th>
                        <th>Payment No</th>
                        <th>Date</th>
                        <th>Mode</th>
                        <th class="text-end">Amount</th>
                        <th>Staff</th>
                        <th>Status</th>
                        <th class="text-end">Prints</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
