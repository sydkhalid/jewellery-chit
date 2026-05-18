@extends('layouts.admin')

@section('title', 'Cashbook')
@section('page-title', 'Cashbook')
@section('page-eyebrow', 'Cashflow')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Cashbook Register</h2>
            <p class="admin-section-copy">Review branch-wise debit, credit, balances, and payment mode cashflow.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('cashflow.create')
                <a href="{{ route('cashbooks.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Cash Entry
                </a>
                <a href="{{ route('cashbooks.opening-balance.create') }}" class="btn btn-light">
                    Opening Balance
                </a>
                <a href="{{ route('cashbooks.closing-balance.create') }}" class="btn btn-light">
                    Closing Balance
                </a>
            @endcan
        </div>
    </div>

    @include('cashbooks.partials.summary-cards', ['summary' => $summary, 'paymentModeSummary' => $paymentModeSummary])

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="cashbook-branch-filter">Branch</label>
                <select id="cashbook-branch-filter" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cashbook-type-filter">Transaction type</label>
                <select id="cashbook-type-filter" class="form-select">
                    <option value="">All types</option>
                    @foreach ($transactionTypes as $type)
                        <option value="{{ $type }}">{{ str($type)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="cashbook-mode-filter">Payment mode</label>
                <select id="cashbook-mode-filter" class="form-select">
                    <option value="">All modes</option>
                    @foreach ($paymentModes as $mode)
                        <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="cashbook-from-filter">From</label>
                <input type="date" id="cashbook-from-filter" class="form-control" value="{{ today()->toDateString() }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="cashbook-to-filter">To</label>
                <input type="date" id="cashbook-to-filter" class="form-control" value="{{ today()->toDateString() }}">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-3">
            <button type="button" class="btn btn-light btn-sm" disabled>
                <i class="bi bi-file-earmark-excel me-1"></i>Excel Export
            </button>
            <button type="button" class="btn btn-light btn-sm" disabled>
                <i class="bi bi-filetype-pdf me-1"></i>PDF Export
            </button>
        </div>

        <div class="table-responsive">
            <table
                class="table table-hover align-middle w-100"
                id="cashbooks-table"
                data-source="{{ route('cashbooks.data') }}"
                data-daily-summary="{{ route('cashbooks.daily-summary') }}"
                data-range-summary="{{ route('cashbooks.date-range-summary') }}"
                data-mode-summary="{{ route('cashbooks.payment-mode-summary') }}"
            >
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Type</th>
                        <th>Mode</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Balance</th>
                        <th>Reference</th>
                        <th>Created By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
