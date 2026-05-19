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
                <span>Role: <strong>{{ auth()->user()->getRoleNames()->first() ?? 'No role assigned' }}</strong></span>
                <span class="dashboard-role-divider"></span>
                <span>Updated: <strong>{{ $dashboardMeta['generated_at'] ?? now()->format('d M Y, h:i A') }}</strong></span>
            </div>
        </div>
        <div class="dashboard-hero-actions">
            @can('reports.view')
                <a href="{{ route('reports.index') }}" class="btn btn-light">
                    <i class="bi bi-download me-2"></i>Reports
                </a>
            @endcan
            @can('payments.create')
                <a href="{{ route('payments.create') }}" class="btn btn-warning">
                    <i class="bi bi-plus-lg me-2"></i>Collect Payment
                </a>
            @endcan
        </div>
    </section>

    <section class="dashboard-card-grid" aria-label="Dashboard summary">
        @foreach ($summaryCards as $card)
            <a href="{{ $card['url'] ?? '#' }}" class="metric-card metric-card-{{ $card['tone'] }}">
                <div class="metric-icon">
                    <i class="bi {{ $card['icon'] }}"></i>
                </div>
                <div>
                    <p class="metric-label">{{ $card['label'] }}</p>
                    <h3 class="metric-value">{{ $card['value'] }}</h3>
                    <p class="metric-trend mb-0">{{ $card['trend'] }}</p>
                </div>
            </a>
        @endforeach
    </section>

    <section class="dashboard-grid">
        <article class="admin-card dashboard-chart-card">
            <div class="admin-card-header">
                <div>
                    <h3>Staff-wise collection</h3>
                    <p>{{ $dashboardMeta['collection_period'] ?? 'Current month' }}</p>
                </div>
            </div>
            <div id="staffWiseCollectionChart" class="dashboard-chart" data-chart-status>
                <div class="skeleton-chart" aria-hidden="true"></div>
            </div>
        </article>

        <article class="admin-card dashboard-chart-card">
            <div class="admin-card-header">
                <div>
                    <h3>Scheme-wise collection</h3>
                    <p>{{ $dashboardMeta['collection_period'] ?? 'Current month' }}</p>
                </div>
            </div>
            <div id="schemeWiseCollectionChart" class="dashboard-chart" data-chart-status>
                <div class="skeleton-chart" aria-hidden="true"></div>
            </div>
        </article>

        <article class="admin-card dashboard-chart-card dashboard-chart-wide">
            <div class="admin-card-header">
                <div>
                    <h3>Monthly collection trend</h3>
                    <p>Last six months from successful payments</p>
                </div>
            </div>
            <div id="monthlyCollectionTrendChart" class="dashboard-chart" data-chart-status>
                <div class="skeleton-chart skeleton-chart-line" aria-hidden="true"></div>
            </div>
        </article>

        <article class="admin-card dashboard-chart-card">
            <div class="admin-card-header">
                <div>
                    <h3>Payment mode collection</h3>
                    <p>{{ $dashboardMeta['collection_period'] ?? 'Current month' }}</p>
                </div>
            </div>
            <div id="paymentModeCollectionChart" class="dashboard-chart" data-chart-status>
                <div class="skeleton-chart skeleton-chart-radial" aria-hidden="true"></div>
            </div>
        </article>
    </section>

    <section class="admin-card recent-activity-card">
        <div class="admin-card-header">
            <div>
                <h3>Recent activity</h3>
                <p>Latest operational movement</p>
            </div>
            @can('activity_logs.view')
                <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-dark">View all</a>
            @endcan
        </div>

        <div class="activity-list">
            @forelse ($recentActivities as $activity)
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
            @empty
                <div class="empty-state empty-state-inline">
                    <i class="bi bi-activity"></i>
                    <h3>No recent activity</h3>
                    <p>New operational actions will appear here automatically.</p>
                </div>
            @endforelse
        </div>
    </section>

    <script type="application/json" id="dashboard-chart-data">
        @json($charts)
    </script>
@endsection
