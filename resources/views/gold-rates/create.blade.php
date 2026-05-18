@extends('layouts.admin')

@section('title', 'Add Gold Rate')
@section('page-title', 'Add Gold Rate')
@section('page-eyebrow', 'Rate Board')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">New Rate</h2>
            <p class="admin-section-copy">Create a pending daily rate for approval and billing use.</p>
        </div>

        <a href="{{ route('gold-rates.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('gold-rates.store') }}" method="POST" data-ajax-form="gold-rate">
            @csrf
            @include('gold-rates.partials.form', ['goldRate' => $goldRate])
        </form>
    </div>
@endsection
