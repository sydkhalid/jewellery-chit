@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-eyebrow', 'Admin overview')

@section('content')
    <section class="dashboard-hero">
        <div>
            <p class="admin-eyebrow mb-2">Welcome, {{ auth()->user()->name }}</p>
            <h2 class="dashboard-hero-title">Chit operations at a glance</h2>
            <p class="dashboard-hero-copy mb-0">Track collections, dues, enrollments, staff performance, and billing movement from one place.</p>
            <div class="dashboard-role-line mt-3">
                Role: <strong>{{ auth()->user()->getRoleNames()->first() ?? 'No role assigned' }}</strong>
            </div>
        </div>
        <div class="dashboard-hero-actions">
            <button type="button" class="btn btn-light">
                <i class="bi bi-download me-2"></i>Export
            </button>
            <button type="button" class="btn btn-warning">
                <i class="bi bi-plus-lg me-2"></i>New Entry
            </button>
        </div>
    </section>

    <section class="dashboard-card-grid" aria-label="Dashboard summary">
        @foreach ($summaryCards as $card)
            <article class="metric-card metric-card-{{ $card['tone'] }}">
                <div class="metric-icon">
                    <i class="bi {{ $card['icon'] }}"></i>
                </div>
                <div>
                    <p class="metric-label">{{ $card['label'] }}</p>
                    <h3 class="metric-value">{{ $card['value'] }}</h3>
                    <p class="metric-trend mb-0">{{ $card['trend'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="dashboard-grid">
        <article class="admin-card dashboard-chart-card">
            <div class="admin-card-header">
                <div>
                    <h3>Staff-wise collection</h3>
                    <p>Collection posted by active staff</p>
                </div>
            </div>
            <div id="staffWiseCollectionChart" class="dashboard-chart" data-chart-status></div>
        </article>

        <article class="admin-card dashboard-chart-card">
            <div class="admin-card-header">
                <div>
                    <h3>Scheme-wise collection</h3>
                    <p>Contribution share by scheme</p>
                </div>
            </div>
            <div id="schemeWiseCollectionChart" class="dashboard-chart" data-chart-status></div>
        </article>

        <article class="admin-card dashboard-chart-card dashboard-chart-wide">
            <div class="admin-card-header">
                <div>
                    <h3>Monthly collection trend</h3>
                    <p>Six month collection movement</p>
                </div>
            </div>
            <div id="monthlyCollectionTrendChart" class="dashboard-chart" data-chart-status></div>
        </article>

        <article class="admin-card dashboard-chart-card">
            <div class="admin-card-header">
                <div>
                    <h3>Payment mode collection</h3>
                    <p>Cash, UPI, card, and bank split</p>
                </div>
            </div>
            <div id="paymentModeCollectionChart" class="dashboard-chart" data-chart-status></div>
        </article>
    </section>

    <section class="admin-card recent-activity-card">
        <div class="admin-card-header">
            <div>
                <h3>Recent activity</h3>
                <p>Latest operational movement</p>
            </div>
            <a href="#" class="btn btn-sm btn-outline-dark">View all</a>
        </div>

        <div class="activity-list">
            @foreach ($recentActivities as $activity)
                <div class="activity-item">
                    <div class="activity-dot"></div>
                    <div class="activity-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-1">
                            <h4>{{ $activity['title'] }}</h4>
                            <span>{{ $activity['time'] }}</span>
                        </div>
                        <p>{{ $activity['description'] }}</p>
                        <span class="activity-badge">{{ $activity['type'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <script type="application/json" id="dashboard-chart-data">
        @json($charts)
    </script>
@endsection
