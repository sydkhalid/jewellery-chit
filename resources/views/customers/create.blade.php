@extends('layouts.admin')

@section('title', 'Add Customer')
@section('page-title', 'Add Customer')
@section('page-eyebrow', 'Customer Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">New Customer</h2>
            <p class="admin-section-copy">Create the customer profile and nominee details.</p>
        </div>

        <a href="{{ route('customers.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('customers.store') }}" method="POST" enctype="multipart/form-data" data-ajax-form="customer">
            @csrf
            @include('customers.partials.form', ['customer' => $customer])
        </form>
    </div>
@endsection
