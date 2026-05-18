@php
    $user = auth()->user();
    $menuGroups = [
        [
            'title' => 'Dashboard',
            'icon' => 'bi-speedometer2',
            'route' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'permissions' => ['dashboard.view'],
            'children' => [],
        ],
        [
            'title' => 'Customers',
            'icon' => 'bi-people',
            'permissions' => ['customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.deactivate', 'customers.documents', 'customers.ledger'],
            'active' => request()->routeIs('customers.*'),
            'children' => [
                ['title' => 'Customer List', 'permission' => 'customers.view', 'route' => route('customers.index'), 'active' => request()->routeIs('customers.index', 'customers.show', 'customers.edit', 'customers.ledger', 'customers.payment-history', 'customers.outstanding')],
                ['title' => 'Add Customer', 'permission' => 'customers.create', 'route' => route('customers.create'), 'active' => request()->routeIs('customers.create')],
            ],
        ],
        [
            'title' => 'Chit Schemes',
            'icon' => 'bi-diagram-3',
            'permissions' => ['schemes.view', 'schemes.create', 'schemes.edit', 'schemes.delete', 'schemes.status'],
            'active' => request()->routeIs('chit-schemes.*'),
            'children' => [
                ['title' => 'Scheme List', 'permission' => 'schemes.view', 'route' => route('chit-schemes.index'), 'active' => request()->routeIs('chit-schemes.index', 'chit-schemes.show', 'chit-schemes.edit')],
                ['title' => 'Add Scheme', 'permission' => 'schemes.create', 'route' => route('chit-schemes.create'), 'active' => request()->routeIs('chit-schemes.create')],
            ],
        ],
        [
            'title' => 'Chit Enrollments',
            'icon' => 'bi-person-check',
            'permissions' => ['enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete', 'enrollments.close', 'enrollments.cancel'],
            'active' => request()->routeIs('chit-enrollments.*'),
            'children' => [
                ['title' => 'Enrollment List', 'permission' => 'enrollments.view', 'route' => route('chit-enrollments.index'), 'active' => request()->routeIs('chit-enrollments.index', 'chit-enrollments.show', 'chit-enrollments.edit')],
                ['title' => 'New Enrollment', 'permission' => 'enrollments.create', 'route' => route('chit-enrollments.create'), 'active' => request()->routeIs('chit-enrollments.create')],
            ],
        ],
        [
            'title' => 'Installments',
            'icon' => 'bi-calendar2-check',
            'permissions' => ['installments.view', 'installments.generate', 'installments.edit', 'installments.status'],
            'active' => request()->routeIs('installments.*', 'chit-enrollments.installments*'),
            'children' => [
                ['title' => 'Installment List', 'permission' => 'installments.view', 'route' => route('installments.index'), 'active' => request()->routeIs('installments.*', 'chit-enrollments.installments*') && request('status') !== 'overdue'],
                ['title' => 'Overdue Installments', 'permission' => 'installments.view', 'route' => route('installments.index', ['status' => 'overdue']), 'active' => request()->routeIs('installments.index') && request('status') === 'overdue'],
            ],
        ],
        [
            'title' => 'Payments',
            'icon' => 'bi-wallet2',
            'permissions' => ['payments.view', 'payments.create', 'payments.edit', 'payments.cancel', 'payments.approve_edit'],
            'active' => request()->routeIs('payments.*'),
            'children' => [
                ['title' => 'Payment List', 'permission' => 'payments.view', 'route' => route('payments.index'), 'active' => request()->routeIs('payments.index', 'payments.show', 'payments.edit')],
                ['title' => 'Collect Payment', 'permission' => 'payments.create', 'route' => route('payments.create'), 'active' => request()->routeIs('payments.create')],
            ],
        ],
        [
            'title' => 'Receipts',
            'icon' => 'bi-receipt',
            'permissions' => ['receipts.view', 'receipts.print', 'receipts.pdf', 'receipts.duplicate', 'receipts.cancel', 'receipts.whatsapp'],
            'active' => request()->routeIs('receipts.*'),
            'children' => [
                ['title' => 'Receipt List', 'permission' => 'receipts.view', 'route' => route('receipts.index'), 'active' => request()->routeIs('receipts.*')],
            ],
        ],
        [
            'title' => 'Ledger',
            'icon' => 'bi-journal-text',
            'permissions' => ['ledger.view', 'ledger.customer', 'ledger.chit'],
            'active' => request()->routeIs('ledgers.*', 'customers.ledger', 'chit-enrollments.ledger*'),
            'children' => [
                ['title' => 'All Ledger', 'permission' => 'ledger.view', 'route' => route('ledgers.index'), 'active' => request()->routeIs('ledgers.*') && ! request('scope')],
                ['title' => 'Customer Ledger', 'permission' => 'ledger.customer', 'route' => route('ledgers.index', ['scope' => 'customer']), 'active' => request()->routeIs('customers.ledger') || request('scope') === 'customer'],
                ['title' => 'Chit Ledger', 'permission' => 'ledger.chit', 'route' => route('ledgers.index', ['scope' => 'chit']), 'active' => request()->routeIs('chit-enrollments.ledger*') || request('scope') === 'chit'],
            ],
        ],
        [
            'title' => 'Pending Dues',
            'icon' => 'bi-hourglass-split',
            'permissions' => ['pending_dues.view', 'pending_dues.followup', 'pending_dues.reminder'],
            'active' => request()->routeIs('pending-dues.*'),
            'children' => [
                ['title' => 'All Pending Dues', 'permission' => 'pending_dues.view', 'route' => route('pending-dues.index'), 'active' => request()->routeIs('pending-dues.index')],
                ['title' => 'Today Dues', 'permission' => 'pending_dues.view', 'route' => route('pending-dues.today'), 'active' => request()->routeIs('pending-dues.today')],
                ['title' => 'Weekly Dues', 'permission' => 'pending_dues.view', 'route' => route('pending-dues.weekly'), 'active' => request()->routeIs('pending-dues.weekly')],
                ['title' => 'Monthly Dues', 'permission' => 'pending_dues.view', 'route' => route('pending-dues.monthly'), 'active' => request()->routeIs('pending-dues.monthly')],
                ['title' => 'Overdue Dues', 'permission' => 'pending_dues.view', 'route' => route('pending-dues.overdue'), 'active' => request()->routeIs('pending-dues.overdue')],
            ],
        ],
        [
            'title' => 'Maturity Closing',
            'icon' => 'bi-award',
            'permissions' => ['maturity.view', 'maturity.create', 'maturity.approve', 'maturity.cancel'],
            'active' => request()->routeIs('maturity-closings.*'),
            'children' => [
                ['title' => 'Closing List', 'permission' => 'maturity.view', 'route' => route('maturity-closings.index'), 'active' => request()->routeIs('maturity-closings.index', 'maturity-closings.show')],
                ['title' => 'New Closing', 'permission' => 'maturity.create', 'route' => route('maturity-closings.create'), 'active' => request()->routeIs('maturity-closings.create')],
            ],
        ],
        [
            'title' => 'Jewellery Billing',
            'icon' => 'bi-gem',
            'permissions' => ['jewellery.view', 'jewellery.create', 'jewellery.edit', 'jewellery.cancel', 'jewellery.adjust_chit'],
            'active' => request()->routeIs('jewellery-invoices.*'),
            'children' => [
                ['title' => 'Invoice List', 'permission' => 'jewellery.view', 'route' => route('jewellery-invoices.index'), 'active' => request()->routeIs('jewellery-invoices.index', 'jewellery-invoices.show', 'jewellery-invoices.edit')],
                ['title' => 'Create Invoice', 'permission' => 'jewellery.create', 'route' => route('jewellery-invoices.create'), 'active' => request()->routeIs('jewellery-invoices.create')],
            ],
        ],
        [
            'title' => 'Gold Rates',
            'icon' => 'bi-currency-exchange',
            'permissions' => ['gold_rates.view', 'gold_rates.create', 'gold_rates.edit', 'gold_rates.approve', 'gold_rates.lock'],
            'active' => request()->routeIs('gold-rates.*'),
            'children' => [
                ['title' => 'Rate List', 'permission' => 'gold_rates.view', 'route' => route('gold-rates.index'), 'active' => request()->routeIs('gold-rates.index', 'gold-rates.show', 'gold-rates.edit')],
                ['title' => 'Add Rate', 'permission' => 'gold_rates.create', 'route' => route('gold-rates.create'), 'active' => request()->routeIs('gold-rates.create')],
            ],
        ],
        [
            'title' => 'Staff & Branch',
            'icon' => 'bi-building',
            'permissions' => ['staff.view', 'staff.create', 'staff.edit', 'staff.delete', 'branch.view', 'branch.create', 'branch.edit', 'branch.delete', 'staff_cash_handover.view', 'staff_cash_handover.create', 'staff_cash_handover.receive'],
            'active' => request()->routeIs('branches.*', 'staff.*', 'staff-cash-handovers.*'),
            'children' => [
                ['title' => 'Branch List', 'permission' => 'branch.view', 'route' => route('branches.index'), 'active' => request()->routeIs('branches.index', 'branches.show', 'branches.edit')],
                ['title' => 'Add Branch', 'permission' => 'branch.create', 'route' => route('branches.create'), 'active' => request()->routeIs('branches.create')],
                ['title' => 'Staff List', 'permission' => 'staff.view', 'route' => route('staff.index'), 'active' => request()->routeIs('staff.index', 'staff.show', 'staff.edit')],
                ['title' => 'Add Staff', 'permission' => 'staff.create', 'route' => route('staff.create'), 'active' => request()->routeIs('staff.create')],
                ['title' => 'Cash Handovers', 'permission' => 'staff_cash_handover.view', 'route' => route('staff-cash-handovers.index'), 'active' => request()->routeIs('staff-cash-handovers.*')],
            ],
        ],
        [
            'title' => 'Cashflow',
            'icon' => 'bi-graph-up-arrow',
            'permissions' => ['cashflow.view', 'cashflow.create', 'cashbook.view'],
            'active' => request()->routeIs('cashbooks.*'),
            'children' => [
                ['title' => 'Cashbook', 'permission' => 'cashbook.view', 'route' => route('cashbooks.index'), 'active' => request()->routeIs('cashbooks.index', 'cashbooks.show')],
                ['title' => 'Opening Balance', 'permission' => 'cashflow.create', 'route' => route('cashbooks.opening-balance.create'), 'active' => request()->routeIs('cashbooks.opening-balance.*')],
                ['title' => 'Closing Balance', 'permission' => 'cashflow.create', 'route' => route('cashbooks.closing-balance.create'), 'active' => request()->routeIs('cashbooks.closing-balance.*')],
                ['title' => 'Cashflow Summary', 'permission' => 'cashbook.view', 'route' => route('cashbooks.index', ['summary' => 'cashflow']), 'active' => request()->routeIs('cashbooks.index') && request('summary') === 'cashflow'],
            ],
        ],
        [
            'title' => 'Reports',
            'icon' => 'bi-file-earmark-bar-graph',
            'permissions' => ['reports.view', 'reports.export_excel', 'reports.export_pdf', 'reports.print'],
            'active' => request()->routeIs('reports.*'),
            'children' => [
                ['title' => 'Customer Report', 'permission' => 'reports.view', 'route' => route('reports.customers'), 'active' => request()->routeIs('reports.customers')],
                ['title' => 'Active Chit Report', 'permission' => 'reports.view', 'route' => route('reports.active-chits'), 'active' => request()->routeIs('reports.active-chits')],
                ['title' => 'Collection Report', 'permission' => 'reports.view', 'route' => route('reports.collections'), 'active' => request()->routeIs('reports.collections')],
                ['title' => 'Pending Report', 'permission' => 'reports.view', 'route' => route('reports.pending'), 'active' => request()->routeIs('reports.pending')],
                ['title' => 'Overdue Report', 'permission' => 'reports.view', 'route' => route('reports.overdue'), 'active' => request()->routeIs('reports.overdue')],
                ['title' => 'Matured Report', 'permission' => 'reports.view', 'route' => route('reports.matured'), 'active' => request()->routeIs('reports.matured')],
                ['title' => 'Closed Report', 'permission' => 'reports.view', 'route' => route('reports.closed'), 'active' => request()->routeIs('reports.closed')],
                ['title' => 'Cancelled Chit Report', 'permission' => 'reports.view', 'route' => route('reports.cancelled'), 'active' => request()->routeIs('reports.cancelled')],
                ['title' => 'Staff Report', 'permission' => 'reports.view', 'route' => route('reports.staff'), 'active' => request()->routeIs('reports.staff')],
                ['title' => 'Branch Report', 'permission' => 'reports.view', 'route' => route('reports.branches'), 'active' => request()->routeIs('reports.branches')],
                ['title' => 'Scheme Report', 'permission' => 'reports.view', 'route' => route('reports.schemes'), 'active' => request()->routeIs('reports.schemes')],
                ['title' => 'Receipt Report', 'permission' => 'reports.view', 'route' => route('reports.receipts'), 'active' => request()->routeIs('reports.receipts')],
                ['title' => 'Cashflow Report', 'permission' => 'reports.view', 'route' => route('reports.cashflow'), 'active' => request()->routeIs('reports.cashflow')],
            ],
        ],
        [
            'title' => 'WhatsApp/SMS',
            'icon' => 'bi-chat-dots',
            'permissions' => ['messages.view', 'messages.send', 'messages.retry', 'messages.logs'],
            'active' => request()->routeIs('messages.*', 'whatsapp-logs.*', 'sms-logs.*'),
            'children' => [
                ['title' => 'Message Dashboard', 'permission' => 'messages.view', 'route' => route('messages.index'), 'active' => request()->routeIs('messages.index')],
                ['title' => 'WhatsApp Logs', 'permission' => 'messages.logs', 'route' => route('messages.whatsapp-logs'), 'active' => request()->routeIs('messages.whatsapp-logs', 'whatsapp-logs.*')],
                ['title' => 'SMS Logs', 'permission' => 'messages.logs', 'route' => route('messages.sms-logs'), 'active' => request()->routeIs('messages.sms-logs', 'sms-logs.*')],
                ['title' => 'Notifications', 'permission' => 'messages.view', 'route' => route('messages.notifications'), 'active' => request()->routeIs('messages.notifications')],
            ],
        ],
        [
            'title' => 'Admin Settings',
            'icon' => 'bi-gear',
            'permissions' => ['settings.view', 'settings.edit', 'settings.backup', 'backup.view', 'backup.create', 'backup.download', 'backup.delete', 'audit_logs.view', 'activity_logs.view'],
            'active' => request()->routeIs('settings.*', 'backups.*', 'audit-logs.*', 'activity-logs.*'),
            'children' => [
                ['title' => 'Shop Settings', 'permission' => 'settings.view', 'route' => route('settings.shop'), 'active' => request()->routeIs('settings.index', 'settings.shop')],
                ['title' => 'Receipt Settings', 'permission' => 'settings.view', 'route' => route('settings.receipt'), 'active' => request()->routeIs('settings.receipt')],
                ['title' => 'Chit Settings', 'permission' => 'settings.view', 'route' => route('settings.chit'), 'active' => request()->routeIs('settings.chit')],
                ['title' => 'Message Settings', 'permission' => 'settings.view', 'route' => route('settings.message'), 'active' => request()->routeIs('settings.message')],
                ['title' => 'Backup Settings', 'permission' => 'settings.backup', 'route' => route('settings.backup'), 'active' => request()->routeIs('settings.backup')],
                ['title' => 'Backups', 'permission' => 'backup.view', 'route' => route('backups.index'), 'active' => request()->routeIs('backups.*')],
                ['title' => 'Audit Logs', 'permission' => 'audit_logs.view', 'route' => route('audit-logs.index'), 'active' => request()->routeIs('audit-logs.*')],
                ['title' => 'Activity Logs', 'permission' => 'activity_logs.view', 'route' => route('activity-logs.index'), 'active' => request()->routeIs('activity-logs.*')],
            ],
        ],
    ];

    $canSeeGroup = function (array $group) use ($user): bool {
        return ($user?->hasRole('Admin') ?? false)
            || collect($group['permissions'])->contains(fn (string $permission): bool => $user?->can($permission) ?? false);
    };

    $canSeeChild = function (array $child) use ($user): bool {
        return ($user?->hasRole('Admin') ?? false) || ($user?->can($child['permission']) ?? false);
    };
@endphp

<aside class="admin-sidebar" aria-label="Admin sidebar">
    <div class="admin-brand">
        <div class="admin-brand-mark">
            <i class="bi bi-gem"></i>
        </div>
        <div>
            <div class="admin-brand-name">Jewellery Chit</div>
            <div class="admin-brand-subtitle">Maintenance Suite</div>
        </div>
    </div>

    <nav class="admin-nav">
        @foreach ($menuGroups as $index => $group)
            @continue(! $canSeeGroup($group))

            @php
                $visibleChildren = collect($group['children'])->filter(fn (array $child): bool => $canSeeChild($child));
                $collapseId = 'sidebar-menu-'.$index;
                $hasChildren = $visibleChildren->isNotEmpty();
                $isActive = ($group['active'] ?? false) || $visibleChildren->contains(fn (array $child): bool => $child['active'] ?? false);
            @endphp

            @if (! $hasChildren)
                <a href="{{ $group['route'] ?? '#' }}" class="admin-nav-link {{ $isActive ? 'active' : '' }}">
                    <i class="bi {{ $group['icon'] }}"></i>
                    <span>{{ $group['title'] }}</span>
                </a>
            @else
                <div class="admin-nav-group">
                    <button class="admin-nav-link admin-nav-toggle {{ $isActive ? 'active' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $isActive ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                        <i class="bi {{ $group['icon'] }}"></i>
                        <span>{{ $group['title'] }}</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </button>

                    <div class="collapse {{ $isActive ? 'show' : '' }}" id="{{ $collapseId }}">
                        <div class="admin-subnav">
                            @foreach ($visibleChildren as $child)
                                <a href="{{ $child['route'] ?? '#' }}" class="admin-subnav-link {{ ($child['active'] ?? false) ? 'active' : '' }}">
                                    {{ $child['title'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </nav>
</aside>
