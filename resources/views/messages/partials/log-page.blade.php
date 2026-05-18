<div class="admin-page-actions">
    <div>
        <h2 class="admin-section-title">{{ $title }}</h2>
        <p class="admin-section-copy">{{ $copy }}</p>
    </div>

    @can('messages.send')
        <a href="{{ route('messages.index') }}" class="btn btn-primary">
            <i class="bi bi-send me-1"></i>Send Message
        </a>
    @endcan
</div>

<div class="admin-card">
    <div class="row g-3 align-items-end mb-3">
        <div class="col-md-3">
            <label class="form-label" for="{{ $tableId }}-customer-filter">Customer</label>
            <select id="{{ $tableId }}-customer-filter" class="form-select" data-message-filter="customer_id">
                <option value="">All customers</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->customer_code }} - {{ $customer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="{{ $tableId }}-mobile-filter">Mobile</label>
            <input type="text" id="{{ $tableId }}-mobile-filter" class="form-control" data-message-filter="mobile">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="{{ $tableId }}-type-filter">Message type</label>
            <select id="{{ $tableId }}-type-filter" class="form-select" data-message-filter="message_type">
                <option value="">All types</option>
                @foreach ($messageTypes as $type)
                    <option value="{{ $type }}">{{ str($type)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="{{ $tableId }}-status-filter">Status</label>
            <select id="{{ $tableId }}-status-filter" class="form-select" data-message-filter="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="{{ $tableId }}-channel-filter">Channel</label>
            <select id="{{ $tableId }}-channel-filter" class="form-select" data-message-filter="channel" @if ($channel) disabled @endif>
                @if ($channel)
                    <option value="{{ $channel }}" selected>{{ ucfirst($channel) }}</option>
                @else
                    <option value="">All channels</option>
                    @foreach ($channels as $availableChannel)
                        <option value="{{ $availableChannel }}">{{ ucfirst($availableChannel) }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="{{ $tableId }}-from-filter">From</label>
            <input type="date" id="{{ $tableId }}-from-filter" class="form-control" data-message-filter="from_date">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="{{ $tableId }}-to-filter">To</label>
            <input type="date" id="{{ $tableId }}-to-filter" class="form-control" data-message-filter="to_date">
        </div>
    </div>

    <div class="table-responsive">
        <table
            class="table table-hover align-middle w-100"
            id="{{ $tableId }}"
            data-message-log-table="{{ $tableId }}"
            data-message-log-type="{{ $tableId === 'notifications-table' ? 'notifications' : ($channel === 'sms' ? 'sms' : 'whatsapp') }}"
            data-source="{{ $source }}"
        >
            <thead>
                <tr>
                    <th>Created</th>
                    <th>Customer</th>
                    @if ($tableId === 'notifications-table')
                        <th>Chit No</th>
                    @else
                        <th>Mobile</th>
                    @endif
                    <th>Type</th>
                    <th>Channel</th>
                    <th>Message</th>
                    <th>Status</th>
                    @if ($showRetry)
                        <th>Retries</th>
                    @endif
                    <th>Sent At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
