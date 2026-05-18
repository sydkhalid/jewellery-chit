@extends('layouts.admin')

@section('title', 'Audit Logs')
@section('page-title', 'Audit Logs')
@section('page-eyebrow', 'Admin Settings')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Audit Logs</h2>
            <p class="admin-section-copy">Track critical create, update, delete, payment, receipt, closing, and backup events.</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-2">
                <label class="form-label" for="audit-user-filter">User</label>
                <select id="audit-user-filter" class="form-select">
                    <option value="">All users</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="audit-module-filter">Module</label>
                <select id="audit-module-filter" class="form-select">
                    <option value="">All modules</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}">{{ str(class_basename($module))->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="audit-event-filter">Event</label>
                <select id="audit-event-filter" class="form-select">
                    <option value="">All events</option>
                    @foreach ($events as $event)
                        <option value="{{ $event }}">{{ str($event)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="audit-from-filter">From</label>
                <input type="date" id="audit-from-filter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="audit-to-filter">To</label>
                <input type="date" id="audit-to-filter" class="form-control">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="audit-logs-table" data-source="{{ route('audit-logs.data') }}">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Event</th>
                        <th>IP address</th>
                        <th>User agent</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
