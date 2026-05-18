@extends('layouts.admin')

@section('title', 'Cash Handovers')
@section('page-title', 'Cash Handovers')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Staff Cash Handovers</h2>
            <p class="admin-section-copy">Track staff cash, UPI, card, and bank handovers with receive/reject control.</p>
        </div>

        @can('staff_cash_handover.create')
            <a href="{{ route('staff-cash-handovers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>New Handover
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="handover-staff-filter">Staff</label>
                <select id="handover-staff-filter" class="form-select">
                    <option value="">All staff</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="handover-branch-filter">Branch</label>
                <select id="handover-branch-filter" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="handover-status-filter">Status</label>
                <select id="handover-status-filter" class="form-select">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="received">Received</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="handover-from-filter">From</label>
                <input type="date" id="handover-from-filter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="handover-to-filter">To</label>
                <input type="date" id="handover-to-filter" class="form-control">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="handovers-table" data-source="{{ route('staff-cash-handovers.data') }}">
                <thead>
                    <tr>
                        <th>Handover No</th>
                        <th>Date</th>
                        <th>Staff</th>
                        <th>Branch</th>
                        <th class="text-end">Cash</th>
                        <th class="text-end">UPI</th>
                        <th class="text-end">Card</th>
                        <th class="text-end">Bank</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>Received By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
