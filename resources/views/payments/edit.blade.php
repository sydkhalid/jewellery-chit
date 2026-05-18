@extends('layouts.admin')

@section('title', 'Edit Payment')
@section('page-title', 'Edit Payment')
@section('page-eyebrow', 'Payment Collection')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $payment->payment_no }}</h2>
            <p class="admin-section-copy">Completed payment edits are routed for admin approval.</p>
        </div>

        <a href="{{ route('payments.show', $payment) }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('payments.update', $payment) }}" method="POST" data-ajax-form="payment" data-payment-form>
            @csrf
            @method('PUT')
            @include('payments.partials.form', ['payment' => $payment])
        </form>
    </div>
@endsection
