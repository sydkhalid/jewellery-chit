@extends('layouts.admin')

@section('title', 'Edit Gold Rate')
@section('page-title', 'Edit Gold Rate')
@section('page-eyebrow', 'Rate Board')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ optional($goldRate->rate_date)->format('d M Y') }}</h2>
            <p class="admin-section-copy">Locked rates cannot be edited.</p>
        </div>

        <a href="{{ route('gold-rates.show', $goldRate) }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('gold-rates.update', $goldRate) }}" method="POST" data-ajax-form="gold-rate">
            @csrf
            @method('PUT')
            @include('gold-rates.partials.form', ['goldRate' => $goldRate])
        </form>
    </div>
@endsection
