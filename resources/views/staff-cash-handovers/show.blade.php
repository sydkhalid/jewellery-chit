@extends('layouts.admin')

@section('title', 'Cash Handover Details')
@section('page-title', 'Cash Handover Details')
@section('page-eyebrow', 'Staff & Branch')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $handover->handover_no }}</h2>
            <p class="admin-section-copy">{{ optional($handover->handover_date)->format('d M Y') }} / {{ $handover->staff?->name }}</p>
        </div>

        <a href="{{ route('staff-cash-handovers.index') }}" class="btn btn-light">Back</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-card h-100">
                <h3 class="form-section-title">Handover Information</h3>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Handover No</dt>
                    <dd class="col-sm-8">{{ $handover->handover_no }}</dd>
                    <dt class="col-sm-4">Staff</dt>
                    <dd class="col-sm-8">{{ $handover->staff?->name ?? '-' }}</dd>
                    <dt class="col-sm-4">Branch</dt>
                    <dd class="col-sm-8">{{ $handover->branch?->name ?? '-' }}</dd>
                    <dt class="col-sm-4">Date</dt>
                    <dd class="col-sm-8">{{ optional($handover->handover_date)->format('d M Y') }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        @php
                            $statusClass = match ($handover->status) {
                                'received' => 'success',
                                'rejected' => 'danger',
                                default => 'warning',
                            };
                        @endphp
                        <span class="badge rounded-pill text-bg-{{ $statusClass }}">{{ ucfirst($handover->status) }}</span>
                    </dd>
                    <dt class="col-sm-4">Received By</dt>
                    <dd class="col-sm-8">{{ $handover->receiver?->name ?? '-' }}</dd>
                    <dt class="col-sm-4">Remarks</dt>
                    <dd class="col-sm-8">{{ $handover->remarks ?: '-' }}</dd>
                </dl>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="admin-card h-100">
                <h3 class="form-section-title">Amount Split</h3>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Cash</span>
                    <strong>Rs. {{ number_format((float) $handover->cash_amount, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>UPI</span>
                    <strong>Rs. {{ number_format((float) $handover->upi_amount, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Card</span>
                    <strong>Rs. {{ number_format((float) $handover->card_amount, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Bank</span>
                    <strong>Rs. {{ number_format((float) $handover->bank_amount, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span>Total</span>
                    <strong>Rs. {{ number_format((float) $handover->total_amount, 2) }}</strong>
                </div>
            </div>
        </div>
    </div>
@endsection
