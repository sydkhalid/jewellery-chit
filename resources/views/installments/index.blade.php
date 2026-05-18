@extends('layouts.admin')

@section('title', 'Installments')
@section('page-title', 'Installments')
@section('page-eyebrow', 'Installment Schedule')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Installment Register</h2>
            <p class="admin-section-copy">Track month-wise due, paid, balance, and overdue installment status.</p>
        </div>

        @can('installments.status')
            <button type="button" class="btn btn-warning" data-installment-action="mark-overdue" data-url="{{ route('installments.mark-overdue') }}">
                <i class="bi bi-exclamation-circle me-1"></i>Mark Overdue
            </button>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="installment-customer-filter">Customer</label>
                <select id="installment-customer-filter" class="form-select">
                    <option value="">All customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="installment-enrollment-filter">Enrollment</label>
                <select id="installment-enrollment-filter" class="form-select">
                    <option value="">All enrollments</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}">{{ $enrollment->chit_no }} - {{ $enrollment->customer?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="installment-staff-filter">Staff</label>
                <select id="installment-staff-filter" class="form-select">
                    <option value="">All staff</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="installment-branch-filter">Branch</label>
                <select id="installment-branch-filter" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="installment-status-filter">Status</label>
                <select id="installment-status-filter" class="form-select">
                    <option value="">All</option>
                    @foreach (['pending', 'partial', 'paid', 'overdue', 'advance'] as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="installment-from-filter">Due from</label>
                <input type="date" id="installment-from-filter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="installment-to-filter">Due to</label>
                <input type="date" id="installment-to-filter" class="form-control">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="installments-table" data-source="{{ route('installments.data') }}">
                <thead>
                    <tr>
                        <th>Chit No</th>
                        <th>Customer</th>
                        <th>Scheme</th>
                        <th>No</th>
                        <th>Due Date</th>
                        <th class="text-end">Due</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Late Fee</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
