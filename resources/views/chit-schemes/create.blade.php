@extends('layouts.admin')

@section('title', 'Add Scheme')
@section('page-title', 'Add Scheme')
@section('page-eyebrow', 'Scheme Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">New Chit Scheme</h2>
            <p class="admin-section-copy">Create scheme rules, bonus settings, and late fee configuration.</p>
        </div>

        <a href="{{ route('chit-schemes.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('chit-schemes.store') }}" method="POST" data-ajax-form="chit-scheme">
            @csrf
            @include('chit-schemes.partials.form', ['scheme' => $scheme])
        </form>
    </div>
@endsection
