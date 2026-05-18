@extends('layouts.admin')

@section('title', 'Edit Customer')
@section('page-title', 'Edit Customer')
@section('page-eyebrow', 'Customer Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $customer->customer_code }}</h2>
            <p class="admin-section-copy">Update customer and nominee information.</p>
        </div>

        <a href="{{ route('customers.show', $customer) }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('customers.update', $customer) }}" method="POST" enctype="multipart/form-data" data-ajax-form="customer">
            @csrf
            @method('PUT')
            @include('customers.partials.form', ['customer' => $customer])
        </form>
    </div>
@endsection
