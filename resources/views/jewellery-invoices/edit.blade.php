@extends('layouts.admin')

@section('title', 'Edit '.$invoice->invoice_no)
@section('page-title', 'Edit Jewellery Invoice')
@section('page-eyebrow', 'Jewellery Billing')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $invoice->invoice_no }}</h2>
            <p class="admin-section-copy">Draft invoices can be edited until they are finalized.</p>
        </div>

        <a href="{{ route('jewellery-invoices.show', $invoice) }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form
            action="{{ route('jewellery-invoices.update', $invoice) }}"
            method="POST"
            data-ajax-form="jewellery-invoice"
            data-jewellery-form
            data-matured-chits-template="{{ route('customers.matured-chits', ['customer' => '__ID__']) }}"
            data-calculate-url="{{ route('jewellery-invoices.calculate') }}"
        >
            @csrf
            @method('PUT')
            @include('jewellery-invoices.partials.form', ['invoice' => $invoice])
        </form>
    </div>
@endsection
