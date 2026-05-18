@extends('layouts.admin')

@section('title', $scheme->scheme_code)
@section('page-title', 'Scheme Details')
@section('page-eyebrow', 'Scheme Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $scheme->name }}</h2>
            <p class="admin-section-copy">{{ $scheme->scheme_code }} · {{ str($scheme->scheme_type)->replace('_', ' ')->title() }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('schemes.edit')
                <a href="{{ route('chit-schemes.edit', $scheme) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('chit-schemes.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="dashboard-card-grid">
        <div class="metric-card metric-card-primary">
            <div class="metric-icon"><i class="bi bi-calendar3"></i></div>
            <div>
                <div class="metric-label">Duration</div>
                <div class="metric-value">{{ $scheme->duration_months }}</div>
                <div class="metric-trend">Months</div>
            </div>
        </div>
        <div class="metric-card metric-card-success">
            <div class="metric-icon"><i class="bi bi-people"></i></div>
            <div>
                <div class="metric-label">Enrollments</div>
                <div class="metric-value">{{ $scheme->enrollments_count }}</div>
                <div class="metric-trend">{{ $scheme->active_enrollments_count }} active</div>
            </div>
        </div>
        <div class="metric-card metric-card-warning">
            <div class="metric-icon"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="metric-label">Estimated Payable</div>
                <div class="metric-value">Rs. {{ number_format($totalPayable, 2) }}</div>
                <div class="metric-trend">Based on minimum rule</div>
            </div>
        </div>
        <div class="metric-card metric-card-info">
            <div class="metric-icon"><i class="bi bi-toggle-on"></i></div>
            <div>
                <div class="metric-label">Status</div>
                <div class="metric-value">{{ ucfirst($scheme->status) }}</div>
                <div class="metric-trend">Scheme availability</div>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Scheme Rules</h4>
                    <dl>
                        <dt>Scheme Type</dt>
                        <dd>{{ str($scheme->scheme_type)->replace('_', ' ')->title() }}</dd>
                        <dt>Monthly Amount</dt>
                        <dd>{{ $scheme->monthly_amount ? 'Rs. '.number_format((float) $scheme->monthly_amount, 2) : '-' }}</dd>
                        <dt>Flexible Range</dt>
                        <dd>{{ $scheme->min_amount ? 'Rs. '.number_format((float) $scheme->min_amount, 2).' - Rs. '.number_format((float) $scheme->max_amount, 2) : '-' }}</dd>
                        <dt>Gold Weight</dt>
                        <dd>{{ $scheme->gold_weight ? number_format((float) $scheme->gold_weight, 3).' g' : '-' }}</dd>
                        <dt>Grace Period</dt>
                        <dd>{{ $scheme->grace_period_days }} days</dd>
                    </dl>
                </div>
            </div>

            <div class="col-md-6">
                <div class="detail-panel h-100">
                    <h4>Bonus & Late Fee</h4>
                    <dl>
                        <dt>Shop Bonus</dt>
                        <dd>{{ ucfirst($scheme->shop_bonus_type) }} · {{ number_format((float) $scheme->shop_bonus_value, 2) }}</dd>
                        <dt>Late Fee</dt>
                        <dd>{{ ucfirst($scheme->late_fee_type) }} · {{ number_format((float) $scheme->late_fee_value, 2) }}</dd>
                        <dt>Maturity Rule</dt>
                        <dd>{{ $scheme->maturity_rule ?: '-' }}</dd>
                        <dt>Early Closing Rule</dt>
                        <dd>{{ $scheme->early_closing_rule ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
