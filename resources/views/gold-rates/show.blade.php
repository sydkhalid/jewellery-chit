@extends('layouts.admin')

@section('title', 'Gold Rate')
@section('page-title', 'Gold Rate Details')
@section('page-eyebrow', 'Rate Board')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ optional($goldRate->rate_date)->format('d M Y') }}</h2>
            <p class="admin-section-copy">{{ ucfirst($goldRate->status) }} rate board entry.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('gold_rates.edit')
                @if (! $goldRate->rate_locked)
                    <a href="{{ route('gold-rates.edit', $goldRate) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                @endif
            @endcan
            @can('gold_rates.approve')
                @if (! $goldRate->rate_locked && $goldRate->status !== 'approved')
                    <button type="button" class="btn btn-success" data-gold-rate-action="approve" data-url="{{ route('gold-rates.approve', $goldRate) }}">
                        <i class="bi bi-check2-circle me-1"></i>Approve
                    </button>
                @endif
                @if (! $goldRate->rate_locked && $goldRate->status !== 'rejected')
                    <button type="button" class="btn btn-warning" data-gold-rate-action="reject" data-url="{{ route('gold-rates.reject', $goldRate) }}">
                        <i class="bi bi-x-octagon me-1"></i>Reject
                    </button>
                @endif
            @endcan
            @can('gold_rates.lock')
                @if (! $goldRate->rate_locked && $goldRate->status === 'approved')
                    <button type="button" class="btn btn-dark" data-gold-rate-action="lock" data-url="{{ route('gold-rates.lock', $goldRate) }}">
                        <i class="bi bi-lock me-1"></i>Lock
                    </button>
                @endif
            @endcan
            <a href="{{ route('gold-rates.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="dashboard-card-grid">
        <div class="metric-card metric-card-primary">
            <div class="metric-icon"><i class="bi bi-gem"></i></div>
            <div>
                <div class="metric-label">22K Gold</div>
                <div class="metric-value">Rs. {{ number_format((float) $goldRate->gold_22k, 2) }}</div>
                <div class="metric-trend">Used by billing defaults</div>
            </div>
        </div>
        <div class="metric-card metric-card-success">
            <div class="metric-icon"><i class="bi bi-gem"></i></div>
            <div>
                <div class="metric-label">24K Gold</div>
                <div class="metric-value">Rs. {{ number_format((float) $goldRate->gold_24k, 2) }}</div>
                <div class="metric-trend">Reference rate</div>
            </div>
        </div>
        <div class="metric-card metric-card-warning">
            <div class="metric-icon"><i class="bi bi-currency-rupee"></i></div>
            <div>
                <div class="metric-label">Silver</div>
                <div class="metric-value">Rs. {{ number_format((float) $goldRate->silver_rate, 2) }}</div>
                <div class="metric-trend">Optional</div>
            </div>
        </div>
        <div class="metric-card metric-card-info">
            <div class="metric-icon"><i class="bi bi-lock"></i></div>
            <div>
                <div class="metric-label">Status</div>
                <div class="metric-value">{{ ucfirst($goldRate->status) }}</div>
                <div class="metric-trend">{{ $goldRate->rate_locked ? 'Locked' : 'Open' }}</div>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="detail-panel">
            <dl>
                <dt>Created by</dt>
                <dd>{{ $goldRate->creator?->name ?: '-' }}</dd>
                <dt>Approved by</dt>
                <dd>{{ $goldRate->approver?->name ?: '-' }}</dd>
                <dt>Approved at</dt>
                <dd>{{ optional($goldRate->approved_at)->format('d M Y h:i A') ?: '-' }}</dd>
                <dt>Created at</dt>
                <dd>{{ optional($goldRate->created_at)->format('d M Y h:i A') }}</dd>
            </dl>
        </div>
    </div>
@endsection
