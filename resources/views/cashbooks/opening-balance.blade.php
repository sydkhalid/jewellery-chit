@extends('layouts.admin')

@section('title', 'Opening Balance')
@section('page-title', 'Opening Balance')
@section('page-eyebrow', 'Cashflow')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Create Opening Balance</h2>
            <p class="admin-section-copy">Opening balance must be the first cashbook entry for the selected date.</p>
        </div>

        <a href="{{ route('cashbooks.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('cashbooks.opening-balance.store') }}" method="POST" data-ajax-form="cashbook">
            @csrf
            @include('cashbooks.partials.form', ['cashbook' => $cashbook, 'mode' => 'opening'])
        </form>
    </div>
@endsection
