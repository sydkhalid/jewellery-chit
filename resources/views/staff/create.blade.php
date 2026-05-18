@extends('layouts.admin')

@section('title', 'Add Staff')
@section('page-title', 'Add Staff')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">New Staff User</h2>
            <p class="admin-section-copy">Create a web/API login and assign exactly one staff role.</p>
        </div>

        <a href="{{ route('staff.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('staff.store') }}" method="POST" data-ajax-form="staff">
            @csrf
            @include('staff.partials.form', ['staff' => $staff, 'branches' => $branches, 'roles' => $roles])
        </form>
    </div>
@endsection
