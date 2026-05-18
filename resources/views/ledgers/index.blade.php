@extends('layouts.admin')

@section('title', 'Ledgers')
@section('page-title', 'Ledgers')
@section('page-eyebrow', 'Chit Ledger')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Ledger Register</h2>
            <p class="admin-section-copy">Read-only transaction ledger for dues, payments, late fees, advances, refunds, closings, and adjustments.</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="ledger-customer-filter">Customer</label>
                <select id="ledger-customer-filter" class="form-select">
                    <option value="">All customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="ledger-enrollment-filter">Chit number</label>
                <select id="ledger-enrollment-filter" class="form-select">
                    <option value="">All chits</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}">{{ $enrollment->chit_no }} - {{ $enrollment->customer?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="ledger-type-filter">Type</label>
                <select id="ledger-type-filter" class="form-select">
                    <option value="">All types</option>
                    @foreach ($transactionTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="ledger-branch-filter">Branch</label>
                <select id="ledger-branch-filter" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="ledger-staff-filter">Staff</label>
                <select id="ledger-staff-filter" class="form-select">
                    <option value="">All staff</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="ledger-from-filter">From</label>
                <input type="date" id="ledger-from-filter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="ledger-to-filter">To</label>
                <input type="date" id="ledger-to-filter" class="form-control">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="ledgers-table" data-source="{{ route('ledgers.data') }}">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Chit No</th>
                        <th>Transaction Type</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Balance</th>
                        <th>Reference</th>
                        <th>Remarks</th>
                        <th>Created By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
