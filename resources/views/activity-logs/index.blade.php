@extends('layouts.admin')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')
@section('page-eyebrow', 'Admin Settings')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Activity Logs</h2>
            <p class="admin-section-copy">Review user actions across the web panel and API workflows.</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-2">
                <label class="form-label" for="activity-user-filter">User</label>
                <select id="activity-user-filter" class="form-select">
                    <option value="">All users</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="activity-module-filter">Module</label>
                <select id="activity-module-filter" class="form-select">
                    <option value="">All modules</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}">{{ str($module)->replace('_', ' ')->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="activity-action-filter">Action</label>
                <select id="activity-action-filter" class="form-select">
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}">{{ str($action)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="activity-from-filter">From</label>
                <input type="date" id="activity-from-filter" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="activity-to-filter">To</label>
                <input type="date" id="activity-to-filter" class="form-control">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="activity-logs-table" data-source="{{ route('activity-logs.data') }}">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP address</th>
                        <th>User agent</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
