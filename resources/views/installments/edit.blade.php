@extends('layouts.admin')

@section('title', 'Edit Installment')
@section('page-title', 'Edit Installment')
@section('page-eyebrow', 'Installment Schedule')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $installment->enrollment?->chit_no }} / #{{ $installment->installment_no }}</h2>
            <p class="admin-section-copy">Update due date, due amount, late fee, and status.</p>
        </div>

        <a href="{{ route('installments.show', $installment) }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('installments.update', $installment) }}" method="POST" data-ajax-form="installment">
            @csrf
            @method('PUT')
            @include('installments.partials.form', ['installment' => $installment])
        </form>
    </div>
@endsection
