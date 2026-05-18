<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Cashbook;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitPayment;
use App\Models\ChitReceipt;
use App\Models\ChitScheme;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ReportService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            'customers' => [
                'title' => 'Customer Report',
                'route' => 'reports.customers',
                'date_column' => 'created_at',
                'columns' => [
                    ['data' => 'customer_code', 'label' => 'Code'],
                    ['data' => 'name', 'label' => 'Name'],
                    ['data' => 'mobile', 'label' => 'Mobile'],
                    ['data' => 'city', 'label' => 'City'],
                    ['data' => 'status', 'label' => 'Status'],
                    ['data' => 'enrollments_count', 'label' => 'Chits', 'className' => 'text-end'],
                    ['data' => 'total_paid', 'label' => 'Paid', 'className' => 'text-end', 'money' => true],
                    ['data' => 'total_pending', 'label' => 'Pending', 'className' => 'text-end', 'money' => true],
                ],
            ],
            'active-chits' => $this->enrollmentDefinition('Active Chit Report', 'reports.active-chits'),
            'matured' => $this->enrollmentDefinition('Matured Chit Report', 'reports.matured'),
            'closed' => $this->enrollmentDefinition('Closed Chit Report', 'reports.closed'),
            'cancelled' => $this->enrollmentDefinition('Cancelled Chit Report', 'reports.cancelled'),
            'collections' => [
                'title' => 'Collection Report',
                'route' => 'reports.collections',
                'date_column' => 'payment_date',
                'columns' => [
                    ['data' => 'payment_no', 'label' => 'Payment No'],
                    ['data' => 'date', 'label' => 'Date'],
                    ['data' => 'customer', 'label' => 'Customer'],
                    ['data' => 'chit_no', 'label' => 'Chit No'],
                    ['data' => 'mode', 'label' => 'Mode'],
                    ['data' => 'staff', 'label' => 'Staff'],
                    ['data' => 'branch', 'label' => 'Branch'],
                    ['data' => 'amount', 'label' => 'Amount', 'className' => 'text-end', 'money' => true],
                    ['data' => 'late_fee', 'label' => 'Late Fee', 'className' => 'text-end', 'money' => true],
                    ['data' => 'total', 'label' => 'Total', 'className' => 'text-end', 'money' => true],
                    ['data' => 'status', 'label' => 'Status'],
                ],
            ],
            'pending' => $this->installmentDefinition('Pending Report', 'reports.pending'),
            'overdue' => $this->installmentDefinition('Overdue Report', 'reports.overdue'),
            'staff' => [
                'title' => 'Staff Report',
                'route' => 'reports.staff',
                'date_column' => 'created_at',
                'columns' => [
                    ['data' => 'name', 'label' => 'Name'],
                    ['data' => 'email', 'label' => 'Email'],
                    ['data' => 'mobile', 'label' => 'Mobile'],
                    ['data' => 'role', 'label' => 'Role'],
                    ['data' => 'branch', 'label' => 'Branch'],
                    ['data' => 'status', 'label' => 'Status'],
                    ['data' => 'total_collection', 'label' => 'Collection', 'className' => 'text-end', 'money' => true],
                    ['data' => 'created_at', 'label' => 'Created'],
                ],
            ],
            'branches' => [
                'title' => 'Branch Report',
                'route' => 'reports.branches',
                'date_column' => 'created_at',
                'columns' => [
                    ['data' => 'branch_code', 'label' => 'Code'],
                    ['data' => 'name', 'label' => 'Name'],
                    ['data' => 'city', 'label' => 'City'],
                    ['data' => 'status', 'label' => 'Status'],
                    ['data' => 'users_count', 'label' => 'Staff', 'className' => 'text-end'],
                    ['data' => 'enrollments_count', 'label' => 'Chits', 'className' => 'text-end'],
                    ['data' => 'total_collection', 'label' => 'Collection', 'className' => 'text-end', 'money' => true],
                ],
            ],
            'schemes' => [
                'title' => 'Scheme Report',
                'route' => 'reports.schemes',
                'date_column' => 'created_at',
                'columns' => [
                    ['data' => 'scheme_code', 'label' => 'Code'],
                    ['data' => 'name', 'label' => 'Name'],
                    ['data' => 'scheme_type', 'label' => 'Type'],
                    ['data' => 'duration_months', 'label' => 'Months', 'className' => 'text-end'],
                    ['data' => 'monthly_amount', 'label' => 'Monthly', 'className' => 'text-end', 'money' => true],
                    ['data' => 'enrollments_count', 'label' => 'Enrollments', 'className' => 'text-end'],
                    ['data' => 'status', 'label' => 'Status'],
                ],
            ],
            'receipts' => [
                'title' => 'Receipt Report',
                'route' => 'reports.receipts',
                'date_column' => 'receipt_date',
                'columns' => [
                    ['data' => 'receipt_no', 'label' => 'Receipt No'],
                    ['data' => 'date', 'label' => 'Date'],
                    ['data' => 'customer', 'label' => 'Customer'],
                    ['data' => 'chit_no', 'label' => 'Chit No'],
                    ['data' => 'payment_no', 'label' => 'Payment No'],
                    ['data' => 'mode', 'label' => 'Mode'],
                    ['data' => 'amount', 'label' => 'Amount', 'className' => 'text-end', 'money' => true],
                    ['data' => 'status', 'label' => 'Status'],
                    ['data' => 'print_count', 'label' => 'Prints', 'className' => 'text-end'],
                ],
            ],
            'cashflow' => [
                'title' => 'Cashflow Report',
                'route' => 'reports.cashflow',
                'date_column' => 'cashbook_date',
                'columns' => [
                    ['data' => 'date', 'label' => 'Date'],
                    ['data' => 'branch', 'label' => 'Branch'],
                    ['data' => 'type', 'label' => 'Type'],
                    ['data' => 'mode', 'label' => 'Mode'],
                    ['data' => 'debit', 'label' => 'Debit', 'className' => 'text-end', 'money' => true],
                    ['data' => 'credit', 'label' => 'Credit', 'className' => 'text-end', 'money' => true],
                    ['data' => 'balance', 'label' => 'Balance', 'className' => 'text-end', 'money' => true],
                    ['data' => 'remarks', 'label' => 'Remarks'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function rows(string $type, array $filters = []): Collection
    {
        $query = $this->query($type, $filters);

        return $query->get()->map(fn (mixed $model): array => $this->mapRow($type, $model))->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{label: string, value: string, tone: string}>
     */
    public function summary(string $type, array $filters = []): array
    {
        $rows = $this->rows($type, $filters);

        $money = fn (float $value): string => 'Rs. '.number_format($value, 2);

        return match ($type) {
            'collections' => [
                ['label' => 'Payments', 'value' => (string) $rows->count(), 'tone' => 'primary'],
                ['label' => 'Amount', 'value' => $money((float) $rows->sum('amount')), 'tone' => 'success'],
                ['label' => 'Late Fee', 'value' => $money((float) $rows->sum('late_fee')), 'tone' => 'warning'],
                ['label' => 'Total', 'value' => $money((float) $rows->sum('total')), 'tone' => 'dark'],
            ],
            'pending', 'overdue' => [
                ['label' => 'Installments', 'value' => (string) $rows->count(), 'tone' => 'primary'],
                ['label' => 'Due Amount', 'value' => $money((float) $rows->sum('due_amount')), 'tone' => 'warning'],
                ['label' => 'Paid Amount', 'value' => $money((float) $rows->sum('paid_amount')), 'tone' => 'success'],
                ['label' => 'Balance', 'value' => $money((float) $rows->sum('balance_amount')), 'tone' => 'danger'],
            ],
            'cashflow' => [
                ['label' => 'Entries', 'value' => (string) $rows->count(), 'tone' => 'primary'],
                ['label' => 'Debit', 'value' => $money((float) $rows->sum('debit')), 'tone' => 'danger'],
                ['label' => 'Credit', 'value' => $money((float) $rows->sum('credit')), 'tone' => 'success'],
                ['label' => 'Balance', 'value' => $money((float) data_get($rows->last(), 'balance', 0)), 'tone' => 'dark'],
            ],
            'customers' => [
                ['label' => 'Customers', 'value' => (string) $rows->count(), 'tone' => 'primary'],
                ['label' => 'Chits', 'value' => (string) $rows->sum('enrollments_count'), 'tone' => 'success'],
                ['label' => 'Paid', 'value' => $money((float) $rows->sum('total_paid')), 'tone' => 'dark'],
                ['label' => 'Pending', 'value' => $money((float) $rows->sum('total_pending')), 'tone' => 'warning'],
            ],
            default => [
                ['label' => 'Rows', 'value' => (string) $rows->count(), 'tone' => 'primary'],
                ['label' => 'Total Payable', 'value' => $money((float) $rows->sum('total_payable')), 'tone' => 'dark'],
                ['label' => 'Total Paid', 'value' => $money((float) $rows->sum('total_paid')), 'tone' => 'success'],
                ['label' => 'Total Pending', 'value' => $money((float) $rows->sum('total_pending')), 'tone' => 'warning'],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{title: string, headings: array<int, string>, rows: array<int, array<int, mixed>>, summary: array<int, array<string, string>>}
     */
    public function exportPayload(string $type, array $filters = []): array
    {
        $definition = $this->definition($type);
        $columns = $definition['columns'];
        $rows = $this->rows($type, $filters);

        return [
            'title' => $definition['title'],
            'headings' => collect($columns)->pluck('label')->all(),
            'rows' => $rows->map(fn (array $row): array => collect($columns)->map(fn (array $column): mixed => $row[$column['data']] ?? null)->all())->all(),
            'summary' => $this->summary($type, $filters),
        ];
    }

    public function definition(string $type): array
    {
        abort_unless(array_key_exists($type, $this->definitions()), 404);

        return $this->definitions()[$type];
    }

    public function logReportAction(string $type, string $action): void
    {
        $definition = $this->definition($type);
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'reports',
            'description' => "{$definition['title']} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => self::class,
            'auditable_id' => 0,
            'event' => $action,
            'old_values' => null,
            'new_values' => ['report_type' => $type, 'title' => $definition['title']],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(string $type, array $filters): Builder
    {
        $query = match ($type) {
            'customers' => Customer::query()->withCount('enrollments')->withSum('enrollments as total_paid_sum', 'total_paid')->withSum('enrollments as total_pending_sum', 'total_pending'),
            'active-chits' => ChitEnrollment::query()->with(['customer', 'scheme', 'branch', 'assignedStaff'])->where('status', 'active'),
            'matured' => ChitEnrollment::query()->with(['customer', 'scheme', 'branch', 'assignedStaff'])->where(function (Builder $query): void {
                $query->where('status', 'matured')->orWhereDate('maturity_date', '<=', today());
            })->whereNotIn('status', ['closed', 'cancelled']),
            'closed' => ChitEnrollment::query()->with(['customer', 'scheme', 'branch', 'assignedStaff'])->where('status', 'closed'),
            'cancelled' => ChitEnrollment::query()->with(['customer', 'scheme', 'branch', 'assignedStaff'])->where('status', 'cancelled'),
            'collections' => ChitPayment::query()->with(['customer', 'enrollment', 'paymentMode', 'branch', 'staff'])->where('status', 'success'),
            'pending' => ChitInstallment::query()->with(['enrollment.customer', 'enrollment.scheme', 'enrollment.branch', 'enrollment.assignedStaff'])->whereIn('status', ['pending', 'partial']),
            'overdue' => ChitInstallment::query()->with(['enrollment.customer', 'enrollment.scheme', 'enrollment.branch', 'enrollment.assignedStaff'])->where(function (Builder $query): void {
                $query->where('status', 'overdue')->orWhere(function (Builder $query): void {
                    $query->whereDate('due_date', '<', today())->where('balance_amount', '>', 0);
                });
            }),
            'staff' => User::query()->with(['branch', 'roles'])->withCount('staffCashHandovers')->role(['Admin', 'Manager', 'Staff']),
            'branches' => Branch::query()->withCount(['users', 'enrollments']),
            'schemes' => ChitScheme::query()->withCount('enrollments'),
            'receipts' => ChitReceipt::query()->with(['customer', 'enrollment', 'payment.paymentMode', 'payment.staff', 'payment.branch']),
            'cashflow' => Cashbook::query()->with(['branch', 'paymentMode', 'creator']),
            default => abort(404),
        };

        return $this->applyCommonFilters($type, $this->applyAccessScope($type, $query), $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCommonFilters(string $type, Builder $query, array $filters): Builder
    {
        $definition = $this->definition($type);
        $dateColumn = $definition['date_column'] ?? null;

        if ($dateColumn && filled($filters['from_date'] ?? null)) {
            $query->whereDate($dateColumn, '>=', $filters['from_date']);
        }

        if ($dateColumn && filled($filters['to_date'] ?? null)) {
            $query->whereDate($dateColumn, '<=', $filters['to_date']);
        }

        if (filled($filters['customer_id'] ?? null)) {
            match ($type) {
                'customers' => $query->whereKey($filters['customer_id']),
                'active-chits', 'matured', 'closed', 'cancelled', 'collections', 'receipts' => $query->where('customer_id', $filters['customer_id']),
                'pending', 'overdue' => $query->whereHas('enrollment', fn (Builder $query): Builder => $query->where('customer_id', $filters['customer_id'])),
                default => null,
            };
        }

        if (filled($filters['scheme_id'] ?? null)) {
            match ($type) {
                'active-chits', 'matured', 'closed', 'cancelled' => $query->where('scheme_id', $filters['scheme_id']),
                'pending', 'overdue' => $query->whereHas('enrollment', fn (Builder $query): Builder => $query->where('scheme_id', $filters['scheme_id'])),
                'schemes' => $query->whereKey($filters['scheme_id']),
                default => null,
            };
        }

        if (filled($filters['branch_id'] ?? null)) {
            $this->filterByBranch($type, $query, $filters['branch_id']);
        }

        if (filled($filters['staff_id'] ?? null)) {
            $this->filterByStaff($type, $query, $filters['staff_id']);
        }

        if (filled($filters['status'] ?? null)) {
            if (in_array($type, ['customers', 'active-chits', 'matured', 'closed', 'cancelled', 'pending', 'overdue', 'staff', 'branches', 'schemes', 'receipts'], true)) {
                $query->where('status', $filters['status']);
            }
        }

        if (filled($filters['payment_mode_id'] ?? null)) {
            match ($type) {
                'collections' => $query->where('payment_mode_id', $filters['payment_mode_id']),
                'receipts' => $query->whereHas('payment', fn (Builder $query): Builder => $query->where('payment_mode_id', $filters['payment_mode_id'])),
                'cashflow' => $query->where('payment_mode_id', $filters['payment_mode_id']),
                default => null,
            };
        }

        return $query->latest($dateColumn ?: 'id');
    }

    private function applyAccessScope(string $type, Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user || $user->hasRole('Admin')) {
            return $query;
        }

        if ($user->hasRole('Manager') && $user->branch_id) {
            return $this->filterByBranch($type, $query, $user->branch_id);
        }

        if ($user->hasRole('Staff')) {
            if (in_array($type, ['branches', 'cashflow'], true) && $user->branch_id) {
                return $this->filterByBranch($type, $query, $user->branch_id);
            }

            return $this->filterByStaff($type, $query, $user->id);
        }

        return $query;
    }

    private function filterByBranch(string $type, Builder $query, mixed $branchId): Builder
    {
        return match ($type) {
            'customers' => $query->whereHas('enrollments', fn (Builder $query): Builder => $query->where('branch_id', $branchId)),
            'active-chits', 'matured', 'closed', 'cancelled', 'collections', 'staff', 'cashflow' => $query->where('branch_id', $branchId),
            'pending', 'overdue' => $query->whereHas('enrollment', fn (Builder $query): Builder => $query->where('branch_id', $branchId)),
            'branches' => $query->whereKey($branchId),
            'receipts' => $query->whereHas('payment', fn (Builder $query): Builder => $query->where('branch_id', $branchId)),
            default => $query,
        };
    }

    private function filterByStaff(string $type, Builder $query, mixed $staffId): Builder
    {
        return match ($type) {
            'customers' => $query->whereHas('enrollments', fn (Builder $query): Builder => $query->where('assigned_staff_id', $staffId)),
            'active-chits', 'matured', 'closed', 'cancelled' => $query->where('assigned_staff_id', $staffId),
            'collections' => $query->where('staff_id', $staffId),
            'pending', 'overdue' => $query->whereHas('enrollment', fn (Builder $query): Builder => $query->where('assigned_staff_id', $staffId)),
            'staff' => $query->whereKey($staffId),
            'receipts' => $query->whereHas('payment', fn (Builder $query): Builder => $query->where('staff_id', $staffId)),
            default => $query,
        };
    }

    private function mapRow(string $type, mixed $model): array
    {
        return match ($type) {
            'customers' => [
                'customer_code' => $model->customer_code,
                'name' => $model->name,
                'mobile' => $model->mobile,
                'city' => $model->city,
                'status' => $model->status,
                'enrollments_count' => (int) $model->enrollments_count,
                'total_paid' => (float) ($model->total_paid_sum ?? 0),
                'total_pending' => (float) ($model->total_pending_sum ?? 0),
            ],
            'active-chits', 'matured', 'closed', 'cancelled' => $this->mapEnrollmentRow($model),
            'collections' => [
                'payment_no' => $model->payment_no,
                'date' => optional($model->payment_date)->format('d M Y'),
                'customer' => $model->customer?->name ?? '-',
                'chit_no' => $model->enrollment?->chit_no ?? '-',
                'mode' => $model->paymentMode?->name ?? '-',
                'staff' => $model->staff?->name ?? '-',
                'branch' => $model->branch?->name ?? '-',
                'amount' => (float) $model->amount,
                'late_fee' => (float) $model->late_fee_amount,
                'total' => (float) $model->total_amount,
                'status' => $model->status,
            ],
            'pending', 'overdue' => $this->mapInstallmentRow($model),
            'staff' => [
                'name' => $model->name,
                'email' => $model->email,
                'mobile' => $model->mobile,
                'role' => $model->getRoleNames()->first() ?? '-',
                'branch' => $model->branch?->name ?? '-',
                'status' => $model->status,
                'total_collection' => (float) ChitPayment::where('staff_id', $model->id)->where('status', 'success')->sum('total_amount'),
                'created_at' => optional($model->created_at)->format('d M Y'),
            ],
            'branches' => [
                'branch_code' => $model->branch_code,
                'name' => $model->name,
                'city' => $model->city,
                'status' => $model->status,
                'users_count' => (int) $model->users_count,
                'enrollments_count' => (int) $model->enrollments_count,
                'total_collection' => (float) ChitPayment::where('branch_id', $model->id)->where('status', 'success')->sum('total_amount'),
            ],
            'schemes' => [
                'scheme_code' => $model->scheme_code,
                'name' => $model->name,
                'scheme_type' => str($model->scheme_type)->replace('_', ' ')->title()->toString(),
                'duration_months' => (int) $model->duration_months,
                'monthly_amount' => (float) $model->monthly_amount,
                'enrollments_count' => (int) $model->enrollments_count,
                'status' => $model->status,
            ],
            'receipts' => [
                'receipt_no' => $model->receipt_no,
                'date' => optional($model->receipt_date)->format('d M Y'),
                'customer' => $model->customer?->name ?? '-',
                'chit_no' => $model->enrollment?->chit_no ?? '-',
                'payment_no' => $model->payment?->payment_no ?? '-',
                'mode' => $model->payment?->paymentMode?->name ?? '-',
                'amount' => (float) $model->amount,
                'status' => $model->status,
                'print_count' => (int) $model->print_count,
            ],
            'cashflow' => [
                'date' => optional($model->cashbook_date)->format('d M Y'),
                'branch' => $model->branch?->name ?? '-',
                'type' => str($model->transaction_type)->replace('_', ' ')->title()->toString(),
                'mode' => $model->paymentMode?->name ?? '-',
                'debit' => (float) $model->debit,
                'credit' => (float) $model->credit,
                'balance' => (float) $model->balance,
                'remarks' => $model->remarks,
            ],
            default => [],
        };
    }

    private function mapEnrollmentRow(ChitEnrollment $enrollment): array
    {
        return [
            'chit_no' => $enrollment->chit_no,
            'customer' => $enrollment->customer?->name ?? '-',
            'scheme' => $enrollment->scheme?->name ?? '-',
            'branch' => $enrollment->branch?->name ?? '-',
            'staff' => $enrollment->assignedStaff?->name ?? '-',
            'start_date' => optional($enrollment->start_date)->format('d M Y'),
            'maturity_date' => optional($enrollment->maturity_date)->format('d M Y'),
            'total_payable' => (float) $enrollment->total_payable,
            'total_paid' => (float) $enrollment->total_paid,
            'total_pending' => (float) $enrollment->total_pending,
            'status' => $enrollment->status,
        ];
    }

    private function mapInstallmentRow(ChitInstallment $installment): array
    {
        return [
            'due_date' => optional($installment->due_date)->format('d M Y'),
            'customer' => $installment->enrollment?->customer?->name ?? '-',
            'chit_no' => $installment->enrollment?->chit_no ?? '-',
            'scheme' => $installment->enrollment?->scheme?->name ?? '-',
            'installment_no' => (int) $installment->installment_no,
            'due_amount' => (float) $installment->due_amount,
            'paid_amount' => (float) $installment->paid_amount,
            'balance_amount' => (float) $installment->balance_amount,
            'late_fee' => (float) $installment->late_fee,
            'status' => $installment->status,
            'staff' => $installment->enrollment?->assignedStaff?->name ?? '-',
            'branch' => $installment->enrollment?->branch?->name ?? '-',
        ];
    }

    private function enrollmentDefinition(string $title, string $route): array
    {
        return [
            'title' => $title,
            'route' => $route,
            'date_column' => 'start_date',
            'columns' => [
                ['data' => 'chit_no', 'label' => 'Chit No'],
                ['data' => 'customer', 'label' => 'Customer'],
                ['data' => 'scheme', 'label' => 'Scheme'],
                ['data' => 'branch', 'label' => 'Branch'],
                ['data' => 'staff', 'label' => 'Staff'],
                ['data' => 'start_date', 'label' => 'Start'],
                ['data' => 'maturity_date', 'label' => 'Maturity'],
                ['data' => 'total_payable', 'label' => 'Payable', 'className' => 'text-end', 'money' => true],
                ['data' => 'total_paid', 'label' => 'Paid', 'className' => 'text-end', 'money' => true],
                ['data' => 'total_pending', 'label' => 'Pending', 'className' => 'text-end', 'money' => true],
                ['data' => 'status', 'label' => 'Status'],
            ],
        ];
    }

    private function installmentDefinition(string $title, string $route): array
    {
        return [
            'title' => $title,
            'route' => $route,
            'date_column' => 'due_date',
            'columns' => [
                ['data' => 'due_date', 'label' => 'Due Date'],
                ['data' => 'customer', 'label' => 'Customer'],
                ['data' => 'chit_no', 'label' => 'Chit No'],
                ['data' => 'scheme', 'label' => 'Scheme'],
                ['data' => 'installment_no', 'label' => 'Month', 'className' => 'text-end'],
                ['data' => 'due_amount', 'label' => 'Due', 'className' => 'text-end', 'money' => true],
                ['data' => 'paid_amount', 'label' => 'Paid', 'className' => 'text-end', 'money' => true],
                ['data' => 'balance_amount', 'label' => 'Balance', 'className' => 'text-end', 'money' => true],
                ['data' => 'late_fee', 'label' => 'Late Fee', 'className' => 'text-end', 'money' => true],
                ['data' => 'status', 'label' => 'Status'],
                ['data' => 'staff', 'label' => 'Staff'],
                ['data' => 'branch', 'label' => 'Branch'],
            ],
        ];
    }
}
