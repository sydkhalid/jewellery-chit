@extends('layouts.admin')

@section('title', 'New Maturity Closing')
@section('page-title', 'New Maturity Closing')
@section('page-eyebrow', 'Maturity Closing')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Create Closing</h2>
            <p class="admin-section-copy">Calculate maturity value, settlement split, and approval-ready closing record.</p>
        </div>

        <a href="{{ route('maturity-closings.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form
            action="{{ route('maturity-closings.store') }}"
            method="POST"
            enctype="multipart/form-data"
            data-ajax-form="maturity-closing"
            data-maturity-form
            data-calculate-template="{{ route('chit-enrollments.maturity-calculate', ['enrollment' => '__ID__']) }}"
        >
            @csrf
            @include('maturity-closings.partials.form', ['closure' => $closure])
        </form>
    </div>
@endsection
