@extends('layouts.admin')

@section('title', 'New Cash Handover')
@section('page-title', 'New Cash Handover')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Create Handover</h2>
            <p class="admin-section-copy">Record staff collection totals for admin or manager receiving.</p>
        </div>

        <a href="{{ route('staff-cash-handovers.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('staff-cash-handovers.store') }}" method="POST" data-ajax-form="handover">
            @csrf
            @include('staff-cash-handovers.partials.form', ['handover' => $handover, 'staffUsers' => $staffUsers, 'branches' => $branches])
        </form>
    </div>
@endsection
