@extends('layouts.admin')

@section('title', $definition['title'])
@section('page-title', $definition['title'])
@section('page-eyebrow', 'Reports')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $definition['title'] }}</h2>
            <p class="admin-section-copy">Filter, review, print, and export this report.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('reports.export_excel')
                <a href="{{ route('reports.excel', $type) }}" class="btn btn-light" data-report-export="excel">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </a>
            @endcan
            @can('reports.export_pdf')
                <a href="{{ route('reports.pdf', $type) }}" class="btn btn-light" data-report-export="pdf">
                    <i class="bi bi-filetype-pdf me-1"></i>PDF
                </a>
            @endcan
            @can('reports.print')
                <a href="{{ route('reports.print', $type) }}" class="btn btn-primary" target="_blank" data-report-export="print">
                    <i class="bi bi-printer me-1"></i>Print
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-4 mb-4" data-report-summary>
        @foreach ($summary as $card)
            <div class="col-md-3">
                <div class="admin-card h-100">
                    <div class="text-muted small">{{ $card['label'] }}</div>
                    <div class="metric-value">{{ $card['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3" data-report-filters>
            <div class="col-md-2">
                <label class="form-label" for="report-from-filter">From</label>
                <input type="date" id="report-from-filter" class="form-control" data-report-filter="from_date">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="report-to-filter">To</label>
                <input type="date" id="report-to-filter" class="form-control" data-report-filter="to_date">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="report-branch-filter">Branch</label>
                <select id="report-branch-filter" class="form-select" data-report-filter="branch_id">
                    <option value="">All</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="report-staff-filter">Staff</label>
                <select id="report-staff-filter" class="form-select" data-report-filter="staff_id">
                    <option value="">All</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="report-scheme-filter">Scheme</label>
                <select id="report-scheme-filter" class="form-select" data-report-filter="scheme_id">
                    <option value="">All</option>
                    @foreach ($schemes as $scheme)
                        <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="report-customer-filter">Customer</label>
                <select id="report-customer-filter" class="form-select" data-report-filter="customer_id">
                    <option value="">All</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="report-status-filter">Status</label>
                <select id="report-status-filter" class="form-select" data-report-filter="status">
                    <option value="">All</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="report-mode-filter">Payment mode</label>
                <select id="report-mode-filter" class="form-select" data-report-filter="payment_mode_id">
                    <option value="">All</option>
                    @foreach ($paymentModes as $mode)
                        <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table
                class="table table-hover align-middle w-100"
                id="report-table"
                data-report-table
                data-source="{{ route($definition['route']) }}"
                data-columns='@json($definition['columns'])'
            >
                <thead>
                    <tr>
                        @foreach ($definition['columns'] as $column)
                            <th class="{{ $column['className'] ?? '' }}">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
