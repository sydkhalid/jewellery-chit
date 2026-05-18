@extends('layouts.admin')

@section('title', 'Create Jewellery Invoice')
@section('page-title', 'Create Jewellery Invoice')
@section('page-eyebrow', 'Jewellery Billing')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">New Invoice</h2>
            <p class="admin-section-copy">Bill jewellery items and apply available chit maturity value.</p>
        </div>

        <a href="{{ route('jewellery-invoices.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form
            action="{{ route('jewellery-invoices.store') }}"
            method="POST"
            data-ajax-form="jewellery-invoice"
            data-jewellery-form
            data-matured-chits-template="{{ route('customers.matured-chits', ['customer' => '__ID__']) }}"
            data-calculate-url="{{ route('jewellery-invoices.calculate') }}"
        >
            @csrf
            @include('jewellery-invoices.partials.form', ['invoice' => $invoice])
        </form>
    </div>
@endsection
