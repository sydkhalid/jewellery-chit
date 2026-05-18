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
            'children' => [
                ['title' => 'Rate Board', 'permission' => 'gold_rates.view'],
                ['title' => 'Update Rates', 'permission' => 'gold_rates.create'],
                ['title' => 'Approve Rates', 'permission' => 'gold_rates.approve'],
            ],
        ],
        [
            'title' => 'Staff & Branch',
            'icon' => 'bi-building',
            'permissions' => ['staff.view', 'staff.create', 'staff.edit', 'staff.delete', 'branch.view', 'branch.create', 'branch.edit', 'branch.delete', 'staff_cash_handover.view', 'staff_cash_handover.create', 'staff_cash_handover.receive'],
            'children' => [
                ['title' => 'Staff Users', 'permission' => 'staff.view'],
                ['title' => 'Branches', 'permission' => 'branch.view'],
                ['title' => 'Cash Handovers', 'permission' => 'staff_cash_handover.view'],
            ],
        ],
        [
            'title' => 'Cashflow',
            'icon' => 'bi-graph-up-arrow',
            'permissions' => ['cashflow.view', 'cashflow.create', 'cashbook.view'],
            'children' => [
                ['title' => 'Cashflow Entries', 'permission' => 'cashflow.view'],
                ['title' => 'Cash Book', 'permission' => 'cashbook.view'],
            ],
        ],
        [
            'title' => 'Reports',
            'icon' => 'bi-file-earmark-bar-graph',
            'permissions' => ['reports.view', 'reports.export_excel', 'reports.export_pdf', 'reports.print'],
            'children' => [
                ['title' => 'Collection Reports', 'permission' => 'reports.view'],
                ['title' => 'Excel Export', 'permission' => 'reports.export_excel'],
                ['title' => 'PDF Export', 'permission' => 'reports.export_pdf'],
            ],
        ],
        [
            'title' => 'WhatsApp/SMS',
            'icon' => 'bi-chat-dots',
            'permissions' => ['messages.view', 'messages.send', 'messages.retry', 'messages.logs'],
            'children' => [
                ['title' => 'Message Templates', 'permission' => 'messages.view'],
                ['title' => 'Send Campaign', 'permission' => 'messages.send'],
                ['title' => 'Message Logs', 'permission' => 'messages.logs'],
            ],
        ],
        [
            'title' => 'Admin Settings',
            'icon' => 'bi-gear',
            'permissions' => ['settings.view', 'settings.edit', 'settings.backup', 'backup.view', 'backup.create', 'backup.download', 'backup.delete', 'audit_logs.view', 'activity_logs.view'],
            'children' => [
                ['title' => 'System Settings', 'permission' => 'settings.view'],
                ['title' => 'Permission Setup', 'permission' => 'settings.edit'],
                ['title' => 'Backup Settings', 'permission' => 'settings.backup'],
                ['title' => 'Audit Logs', 'permission' => 'audit_logs.view'],
                ['title' => 'Backups', 'permission' => 'backup.view'],
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
