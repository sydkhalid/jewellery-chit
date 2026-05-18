@extends('layouts.admin')

@section('title', 'Create Cash Entry')
@section('page-title', 'Create Cash Entry')
@section('page-eyebrow', 'Cashflow')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Manual Cashbook Entry</h2>
            <p class="admin-section-copy">Create controlled cashflow entries for operational adjustments.</p>
        </div>

        <a href="{{ route('cashbooks.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('cashbooks.store') }}" method="POST" data-ajax-form="cashbook">
            @csrf
            @include('cashbooks.partials.form', ['cashbook' => $cashbook, 'mode' => 'entry'])
        </form>
    </div>
@endsection
