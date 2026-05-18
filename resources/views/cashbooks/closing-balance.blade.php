@extends('layouts.admin')

@section('title', 'Closing Balance')
@section('page-title', 'Closing Balance')
@section('page-eyebrow', 'Cashflow')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Create Closing Balance</h2>
            <p class="admin-section-copy">Closing balance is calculated from opening balance plus credits minus debits.</p>
        </div>

        <a href="{{ route('cashbooks.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('cashbooks.closing-balance.store') }}" method="POST" data-ajax-form="cashbook">
            @csrf
            @include('cashbooks.partials.form', ['cashbook' => $cashbook, 'mode' => 'closing'])
        </form>
    </div>
@endsection
