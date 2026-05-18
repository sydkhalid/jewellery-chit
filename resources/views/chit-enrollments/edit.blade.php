@extends('layouts.admin')

@section('title', 'Edit Enrollment')
@section('page-title', 'Edit Enrollment')
@section('page-eyebrow', 'Enrollment Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $enrollment->chit_no }}</h2>
            <p class="admin-section-copy">Update enrollment assignment, dates, amount, or agreement.</p>
        </div>

        <a href="{{ route('chit-enrollments.show', $enrollment) }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('chit-enrollments.update', $enrollment) }}" method="POST" enctype="multipart/form-data" data-ajax-form="chit-enrollment">
            @csrf
            @method('PUT')
            @include('chit-enrollments.partials.form', ['enrollment' => $enrollment])
        </form>
    </div>
@endsection
