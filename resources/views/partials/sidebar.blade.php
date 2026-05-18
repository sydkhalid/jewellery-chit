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
            'children' => [
                ['title' => 'Customer List', 'permission' => 'customers.view'],
                ['title' => 'Add Customer', 'permission' => 'customers.create'],
            ],
        ],
        [
            'title' => 'Chit Schemes',
            'icon' => 'bi-diagram-3',
            'permissions' => ['schemes.view', 'schemes.create', 'schemes.edit', 'schemes.delete', 'schemes.status'],
            'children' => [
                ['title' => 'Scheme List', 'permission' => 'schemes.view'],
                ['title' => 'Create Scheme', 'permission' => 'schemes.create'],
            ],
        ],
        [
            'title' => 'Chit Enrollments',
            'icon' => 'bi-person-check',
            'permissions' => ['enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete', 'enrollments.close', 'enrollments.cancel'],
            'children' => [
                ['title' => 'Enrollment List', 'permission' => 'enrollments.view'],
                ['title' => 'New Enrollment', 'permission' => 'enrollments.create'],
            ],
        ],
        [
            'title' => 'Installments',
            'icon' => 'bi-calendar2-check',
            'permissions' => ['installments.view', 'installments.generate', 'installments.edit', 'installments.status'],
            'children' => [
                ['title' => 'Installment Schedule', 'permission' => 'installments.view'],
                ['title' => 'Generate Installments', 'permission' => 'installments.generate'],
            ],
        ],
        [
            'title' => 'Payments',
            'icon' => 'bi-wallet2',
            'permissions' => ['payments.view', 'payments.create', 'payments.edit', 'payments.cancel', 'payments.approve_edit'],
            'children' => [
                ['title' => 'Payment Entries', 'permission' => 'payments.view'],
                ['title' => 'Collect Payment', 'permission' => 'payments.create'],
            ],
        ],
        [
            'title' => 'Receipts',
            'icon' => 'bi-receipt',
            'permissions' => ['receipts.view', 'receipts.print', 'receipts.pdf', 'receipts.duplicate', 'receipts.cancel', 'receipts.whatsapp'],
            'children' => [
                ['title' => 'Receipt Register', 'permission' => 'receipts.view'],
                ['title' => 'Print Receipts', 'permission' => 'receipts.print'],
                ['title' => 'Receipt PDFs', 'permission' => 'receipts.pdf'],
            ],
        ],
        [
            'title' => 'Ledger',
            'icon' => 'bi-journal-text',
            'permissions' => ['ledger.view', 'ledger.customer', 'ledger.chit'],
            'children' => [
                ['title' => 'Customer Ledger', 'permission' => 'ledger.customer'],
                ['title' => 'Chit Ledger', 'permission' => 'ledger.chit'],
            ],
        ],
        [
            'title' => 'Pending Dues',
            'icon' => 'bi-hourglass-split',
            'permissions' => ['pending_dues.view', 'pending_dues.followup', 'pending_dues.reminder'],
            'children' => [
                ['title' => 'Due List', 'permission' => 'pending_dues.view'],
                ['title' => 'Follow-up Queue', 'permission' => 'pending_dues.followup'],
                ['title' => 'Reminder Queue', 'permission' => 'pending_dues.reminder'],
            ],
        ],
        [
            'title' => 'Maturity Closing',
            'icon' => 'bi-award',
            'permissions' => ['maturity.view', 'maturity.create', 'maturity.approve', 'maturity.cancel'],
            'children' => [
                ['title' => 'Matured Chits', 'permission' => 'maturity.view'],
                ['title' => 'Create Closing', 'permission' => 'maturity.create'],
                ['title' => 'Approval Queue', 'permission' => 'maturity.approve'],
            ],
        ],
        [
            'title' => 'Jewellery Billing',
            'icon' => 'bi-gem',
            'permissions' => ['jewellery.view', 'jewellery.create', 'jewellery.edit', 'jewellery.cancel', 'jewellery.adjust_chit'],
            'children' => [
                ['title' => 'Billing Register', 'permission' => 'jewellery.view'],
                ['title' => 'New Bill', 'permission' => 'jewellery.create'],
                ['title' => 'Chit Adjustment', 'permission' => 'jewellery.adjust_chit'],
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
                $isActive = $group['active'] ?? false;
            @endphp

            @if (! $hasChildren)
                <a href="{{ $group['route'] ?? '#' }}" class="admin-nav-link {{ $isActive ? 'active' : '' }}">
                    <i class="bi {{ $group['icon'] }}"></i>
                    <span>{{ $group['title'] }}</span>
                </a>
            @else
                <div class="admin-nav-group">
                    <button class="admin-nav-link admin-nav-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                        <i class="bi {{ $group['icon'] }}"></i>
                        <span>{{ $group['title'] }}</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </button>

                    <div class="collapse" id="{{ $collapseId }}">
                        <div class="admin-subnav">
                            @foreach ($visibleChildren as $child)
                                <a href="{{ $child['route'] ?? '#' }}" class="admin-subnav-link">
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
