@extends('layouts.admin')

@section('title', 'Cashbook Entry')
@section('page-title', 'Cashbook Entry')
@section('page-eyebrow', 'Cashflow')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ str($cashbook->transaction_type)->replace('_', ' ')->title() }}</h2>
            <p class="admin-section-copy">{{ optional($cashbook->cashbook_date)->format('d M Y') }}</p>
        </div>

        <a href="{{ route('cashbooks.index') }}" class="btn btn-light">Back</a>
    </div>

    <div class="admin-card">
        <dl class="row mb-0">
            <dt class="col-sm-4">Date</dt>
            <dd class="col-sm-8">{{ optional($cashbook->cashbook_date)->format('d M Y') }}</dd>
            <dt class="col-sm-4">Branch</dt>
            <dd class="col-sm-8">{{ $cashbook->branch?->name ?? '-' }}</dd>
            <dt class="col-sm-4">Transaction Type</dt>
            <dd class="col-sm-8">{{ str($cashbook->transaction_type)->replace('_', ' ')->title() }}</dd>
            <dt class="col-sm-4">Payment Mode</dt>
            <dd class="col-sm-8">{{ $cashbook->paymentMode?->name ?? '-' }}</dd>
            <dt class="col-sm-4">Debit</dt>
            <dd class="col-sm-8">Rs. {{ number_format((float) $cashbook->debit, 2) }}</dd>
            <dt class="col-sm-4">Credit</dt>
            <dd class="col-sm-8">Rs. {{ number_format((float) $cashbook->credit, 2) }}</dd>
            <dt class="col-sm-4">Balance</dt>
            <dd class="col-sm-8">Rs. {{ number_format((float) $cashbook->balance, 2) }}</dd>
            <dt class="col-sm-4">Reference</dt>
            <dd class="col-sm-8">
                @if ($cashbook->reference_type)
                    {{ class_basename($cashbook->reference_type) }} {{ $cashbook->reference_id ? '#'.$cashbook->reference_id : '' }}
                @else
                    -
                @endif
            </dd>
            <dt class="col-sm-4">Remarks</dt>
            <dd class="col-sm-8">{{ $cashbook->remarks ?: '-' }}</dd>
            <dt class="col-sm-4">Created By</dt>
            <dd class="col-sm-8">{{ $cashbook->creator?->name ?? '-' }}</dd>
        </dl>
    </div>
@endsection
