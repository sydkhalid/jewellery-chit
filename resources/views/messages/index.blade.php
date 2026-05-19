@extends('layouts.admin')

@section('title', 'Message Dashboard')
@section('page-title', 'Message Dashboard')
@section('page-eyebrow', 'WhatsApp/SMS')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Messaging</h2>
            <p class="admin-section-copy">Send customer messages and review WhatsApp, SMS, and notification delivery logs.</p>
        </div>

        @can('messages.send')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                <i class="bi bi-send me-1"></i>Send Message
            </button>
        @endcan
    </div>

    <div class="row g-4 mb-4">
        @can('messages.view')
            <div class="col-md-3">
                <a href="{{ route('messages.notifications') }}" class="admin-card h-100 text-decoration-none text-reset d-block">
                    <div class="text-muted small">Notifications</div>
                    <div class="metric-value">{{ number_format($summary['notifications']) }}</div>
                </a>
            </div>
        @endcan
        @can('messages.logs')
            <div class="col-md-3">
                <a href="{{ route('messages.whatsapp-logs') }}" class="admin-card h-100 text-decoration-none text-reset d-block">
                    <div class="text-muted small">WhatsApp Logs</div>
                    <div class="metric-value">{{ number_format($summary['whatsapp']) }}</div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="{{ route('messages.sms-logs') }}" class="admin-card h-100 text-decoration-none text-reset d-block">
                    <div class="text-muted small">SMS Logs</div>
                    <div class="metric-value">{{ number_format($summary['sms']) }}</div>
                </a>
            </div>
            <div class="col-md-3">
                <div class="admin-card h-100">
                    <div class="text-muted small">Failed Messages</div>
                    <div class="metric-value">{{ number_format($summary['failed']) }}</div>
                </div>
            </div>
        @endcan
    </div>

    <div class="admin-card">
        <h3 class="form-section-title">Message Templates</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Template</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (\App\Services\MessageService::TEMPLATES as $type => $template)
                        <tr>
                            <td class="fw-semibold">{{ str($type)->replace('_', ' ')->title() }}</td>
                            <td>{{ $template }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="fw-semibold">General</td>
                        <td>Custom message entered by staff.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @include('messages.partials.send-modal')
@endsection
