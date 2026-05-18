<div class="admin-page-actions">
    <div>
        <h2 class="admin-section-title">{{ $pageTitle }}</h2>
        <p class="admin-section-copy">Track due installments, update follow-ups, and send WhatsApp/SMS reminder placeholders.</p>
    </div>

    @can('pending_dues.reminder')
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-success" data-pending-due-bulk data-channel="whatsapp">
                <i class="bi bi-whatsapp me-1"></i>Bulk WhatsApp
            </button>
            <button type="button" class="btn btn-light" data-pending-due-bulk data-channel="sms">
                <i class="bi bi-chat-dots me-1"></i>Bulk SMS
            </button>
        </div>
    @endcan
</div>

<div class="dashboard-card-grid">
    <div class="metric-card metric-card-warning">
        <div class="metric-icon"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="metric-label">Pending Count</div>
            <div class="metric-value">{{ $summary['count'] }}</div>
            <div class="metric-trend">Today {{ $summary['today_count'] }} / Overdue {{ $summary['overdue_count'] }}</div>
        </div>
    </div>
    <div class="metric-card metric-card-primary">
        <div class="metric-icon"><i class="bi bi-cash-stack"></i></div>
        <div>
            <div class="metric-label">Total Due</div>
            <div class="metric-value">Rs. {{ number_format($summary['total_due'], 2) }}</div>
            <div class="metric-trend">Scheduled amount</div>
        </div>
    </div>
    <div class="metric-card metric-card-success">
        <div class="metric-icon"><i class="bi bi-wallet2"></i></div>
        <div>
            <div class="metric-label">Paid So Far</div>
            <div class="metric-value">Rs. {{ number_format($summary['total_paid'], 2) }}</div>
            <div class="metric-trend">Partial collections included</div>
        </div>
    </div>
    <div class="metric-card metric-card-warning">
        <div class="metric-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
            <div class="metric-label">Balance</div>
            <div class="metric-value">Rs. {{ number_format($summary['total_balance'], 2) }}</div>
            <div class="metric-trend">Late fee Rs. {{ number_format($summary['total_late_fee'], 2) }}</div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="row g-3 align-items-end mb-3">
        <div class="col-md-2">
            <label class="form-label" for="pending-due-type-filter">Due type</label>
            <select id="pending-due-type-filter" class="form-select">
                <option value="">All</option>
                <option value="today" @selected($selectedDueType === 'today')>Today</option>
                <option value="weekly" @selected($selectedDueType === 'weekly')>Weekly</option>
                <option value="monthly" @selected($selectedDueType === 'monthly')>Monthly</option>
                <option value="overdue" @selected($selectedDueType === 'overdue')>Overdue</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pending-customer-filter">Customer</label>
            <select id="pending-customer-filter" class="form-select">
                <option value="">All customers</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="pending-staff-filter">Staff</label>
            <select id="pending-staff-filter" class="form-select">
                <option value="">All staff</option>
                @foreach ($staffUsers as $staff)
                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="pending-branch-filter">Branch</label>
            <select id="pending-branch-filter" class="form-select">
                <option value="">All branches</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pending-scheme-filter">Scheme</label>
            <select id="pending-scheme-filter" class="form-select">
                <option value="">All schemes</option>
                @foreach ($schemes as $scheme)
                    <option value="{{ $scheme->id }}">{{ $scheme->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="pending-status-filter">Status</label>
            <select id="pending-status-filter" class="form-select">
                <option value="">All</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="pending-followup-filter">Follow-up</label>
            <select id="pending-followup-filter" class="form-select">
                <option value="">All</option>
                @foreach ($followupStatuses as $status)
                    <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="pending-from-filter">From</label>
            <input type="date" id="pending-from-filter" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="pending-to-filter">To</label>
            <input type="date" id="pending-to-filter" class="form-control">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle w-100" id="pending-dues-table" data-source="{{ route('pending-dues.data') }}" data-bulk-url="{{ route('pending-dues.bulk-reminder') }}">
            <thead>
                <tr>
                    <th><input type="checkbox" class="form-check-input" data-pending-due-select-all></th>
                    <th>Due Date</th>
                    <th>Customer Code</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Chit Number</th>
                    <th>Scheme</th>
                    <th>Installment</th>
                    <th class="text-end">Due</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th class="text-end">Late Fee</th>
                    <th>Status</th>
                    <th>Staff</th>
                    <th>Branch</th>
                    <th>Follow-up</th>
                    <th>Promise Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('pending-dues.partials.followup-modal')
