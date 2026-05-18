@extends('layouts.admin')

@section('title', 'Gold Rates')
@section('page-title', 'Gold Rates')
@section('page-eyebrow', 'Rate Board')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Rate Board</h2>
            <p class="admin-section-copy">Maintain approved gold and silver rates used by jewellery billing.</p>
        </div>

        @can('gold_rates.create')
            <a href="{{ route('gold-rates.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Add Rate
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label" for="gold-rate-date-filter">Date</label>
                <input type="date" id="gold-rate-date-filter" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="gold-rate-status-filter">Status</label>
                <select id="gold-rate-status-filter" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="gold-rate-lock-filter">Lock</label>
                <select id="gold-rate-lock-filter" class="form-select">
                    <option value="">All</option>
                    <option value="1">Locked</option>
                    <option value="0">Open</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="gold-rates-table" data-source="{{ route('gold-rates.data') }}">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-end">22K Gold</th>
                        <th class="text-end">24K Gold</th>
                        <th class="text-end">Silver</th>
                        <th>Status</th>
                        <th>Lock</th>
                        <th>Created By</th>
                        <th>Approved By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
