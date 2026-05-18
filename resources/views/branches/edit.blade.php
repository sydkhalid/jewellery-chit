@extends('layouts.admin')

@section('title', 'Edit Branch')
@section('page-title', 'Edit Branch')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $branch->name }}</h2>
            <p class="admin-section-copy">{{ $branch->branch_code }}</p>
        </div>

        <a href="{{ route('branches.show', $branch) }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('branches.update', $branch) }}" method="POST" data-ajax-form="branch">
            @csrf
            @method('PUT')
            @include('branches.partials.form', ['branch' => $branch])
        </form>
    </div>
@endsection
