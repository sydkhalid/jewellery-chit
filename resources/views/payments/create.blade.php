@extends('layouts.admin')

@section('title', 'Collect Payment')
@section('page-title', 'Collect Payment')
@section('page-eyebrow', 'Payment Collection')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">New Payment</h2>
            <p class="admin-section-copy">Collect customer chit payments and generate receipt, ledger, and cashbook entries.</p>
        </div>

        <a href="{{ route('payments.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('payments.store') }}" method="POST" data-ajax-form="payment" data-payment-form>
            @csrf
            @include('payments.partials.form', ['payment' => $payment])
        </form>
    </div>
@endsection
