@extends('layouts.admin')

@section('title', 'Add Branch')
@section('page-title', 'Add Branch')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">New Branch</h2>
            <p class="admin-section-copy">Create a branch for staff assignment and collection reporting.</p>
        </div>

        <a href="{{ route('branches.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('branches.store') }}" method="POST" data-ajax-form="branch">
            @csrf
            @include('branches.partials.form', ['branch' => $branch])
        </form>
    </div>
@endsection
