@extends('layouts.admin')

@section('title', 'New Enrollment')
@section('page-title', 'New Enrollment')
@section('page-eyebrow', 'Enrollment Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Create Chit Enrollment</h2>
            <p class="admin-section-copy">Assign an active scheme to a customer and generate installments.</p>
        </div>

        <a href="{{ route('chit-enrollments.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('chit-enrollments.store') }}" method="POST" enctype="multipart/form-data" data-ajax-form="chit-enrollment">
            @csrf
            @include('chit-enrollments.partials.form', ['enrollment' => $enrollment])
        </form>
    </div>
@endsection
