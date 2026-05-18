@extends('layouts.admin')

@section('title', 'Edit Staff')
@section('page-title', 'Edit Staff')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $staff->name }}</h2>
            <p class="admin-section-copy">{{ $staff->email }}</p>
        </div>

        <a href="{{ route('staff.show', $staff) }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('staff.update', $staff) }}" method="POST" data-ajax-form="staff">
            @csrf
            @method('PUT')
            @include('staff.partials.form', ['staff' => $staff, 'branches' => $branches, 'roles' => $roles])
        </form>
    </div>
@endsection
