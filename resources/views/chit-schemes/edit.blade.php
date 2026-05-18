@extends('layouts.admin')

@section('title', 'Edit Scheme')
@section('page-title', 'Edit Scheme')
@section('page-eyebrow', 'Scheme Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $scheme->scheme_code }}</h2>
            <p class="admin-section-copy">Update scheme rule and status settings.</p>
        </div>

        <a href="{{ route('chit-schemes.show', $scheme) }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('chit-schemes.update', $scheme) }}" method="POST" data-ajax-form="chit-scheme">
            @csrf
            @method('PUT')
            @include('chit-schemes.partials.form', ['scheme' => $scheme])
        </form>
    </div>
@endsection
