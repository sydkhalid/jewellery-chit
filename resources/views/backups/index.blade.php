@extends('layouts.admin')

@section('title', 'Backups')
@section('page-title', 'Backups')
@section('page-eyebrow', 'Admin Settings')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Backup Manager</h2>
            <p class="admin-section-copy">Create, download, and remove database backup archives.</p>
        </div>

        @can('backup.create')
            <button type="button" class="btn btn-primary" data-backup-action="create" data-url="{{ route('backups.create') }}">
                <i class="bi bi-database-add me-1"></i>Create Backup
            </button>
        @endcan
    </div>

    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <div>
                <h3>Available Backups</h3>
                <p>Stored on the configured Laravel backup disk.</p>
            </div>
        </div>

        @if (count($backups) > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Backup name</th>
                            <th>Date</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr>
                                <td class="fw-semibold">{{ $backup['name'] }}</td>
                                <td>{{ $backup['date']->format('d M Y, h:i A') }}</td>
                                <td>{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                                <td><span class="badge text-bg-success">Available</span></td>
                                <td class="text-end">
                                    @can('backup.download')
                                        <a href="{{ route('backups.download', ['backup' => $backup['name']]) }}" class="btn btn-sm btn-light" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    @endcan
                                    @can('backup.delete')
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-backup-action="delete" data-url="{{ route('backups.delete', ['backup' => $backup['name']]) }}" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-database"></i>
                <h3>No backups found</h3>
                <p>Create a backup to store the latest database archive.</p>
            </div>
        @endif
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h3>Backup Logs</h3>
                <p>Recent create, download, delete, and failure activity.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Backup</th>
                        <th>Disk</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                            <td>{{ $log->backup_name }}</td>
                            <td>{{ $log->disk ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $log->status === 'success' ? 'text-bg-success' : 'text-bg-danger' }}">
                                    {{ str($log->status)->headline() }}
                                </span>
                            </td>
                            <td>{{ $log->creator?->name ?? 'System' }}</td>
                            <td>{{ $log->message ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state empty-state-inline">
                                    <i class="bi bi-clock-history"></i>
                                    <h3>No backup logs yet</h3>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
