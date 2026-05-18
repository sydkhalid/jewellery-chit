<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitPayment;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        $today = today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        return [
            'summaryCards' => $this->summaryCards($today, $monthStart, $monthEnd),
            'charts' => [
                'staffWiseCollection' => $this->staffWiseCollection($monthStart, $monthEnd),
                'schemeWiseCollection' => $this->schemeWiseCollection($monthStart, $monthEnd),
                'monthlyCollectionTrend' => $this->monthlyCollectionTrend(),
                'paymentModeCollection' => $this->paymentModeCollection($monthStart, $monthEnd),
            ],
            'recentActivities' => $this->recentActivities(),
            'dashboardMeta' => [
                'collection_period' => $monthStart->format('d M Y').' - '.$monthEnd->format('d M Y'),
                'generated_at' => now()->format('d M Y, h:i A'),
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function summaryCards(Carbon $today, Carbon $monthStart, Carbon $monthEnd): array
    {
        $totalCustomers = $this->customerQuery()->count();
        $activeCustomers = $this->customerQuery()->where('status', 'active')->count();

        $activeChits = $this->enrollmentQuery()->where('status', 'active')->count();
        $newEnrollments = $this->betweenDates(
            $this->enrollmentQuery(),
            'start_date',
            $monthStart,
            $monthEnd
        )->count();

        $todayPayments = $this->successfulPaymentQuery();
        $todayCollection = (float) $this->betweenDates($todayPayments, 'payment_date', $today, $today)->sum('total_amount');
        $todayReceiptCount = $this->betweenDates(
            $this->successfulPaymentQuery(),
            'payment_date',
            $today,
            $today
        )->count();

        $monthlyCollection = (float) $this->betweenDates(
            $this->successfulPaymentQuery(),
            'payment_date',
            $monthStart,
            $monthEnd
        )->sum('total_amount');
        $lastMonthCollection = (float) $this->betweenDates(
            $this->successfulPaymentQuery(),
            'payment_date',
            $monthStart->copy()->subMonthNoOverflow(),
            $monthStart->copy()->subDay()
        )->sum('total_amount');

        $pendingDueQuery = $this->pendingInstallmentQuery();
        $pendingDues = (float) $pendingDueQuery->sum('balance_amount');
        $pendingInstallmentCount = $this->pendingInstallmentQuery()->count();

        $maturedChits = $this->maturedEnrollmentQuery()->count();
        $closedChits = $this->enrollmentQuery()->where('status', 'closed')->count();
        $closedThisMonth = $this->betweenDates(
            $this->enrollmentQuery()->where('status', 'closed'),
            'updated_at',
            $monthStart,
            $monthEnd
        )->count();

        $overdueInstallments = $this->overdueInstallmentQuery()->with('enrollment:id,customer_id')->get();
        $overdueCustomers = $overdueInstallments
            ->pluck('enrollment.customer_id')
            ->filter()
            ->unique()
            ->count();

        return [
            [
                'label' => 'Total customers',
                'value' => $this->number($totalCustomers),
                'icon' => 'bi-people',
                'tone' => 'primary',
                'trend' => $this->number($activeCustomers).' active customers',
                'url' => route('customers.index'),
            ],
            [
                'label' => 'Active chits',
                'value' => $this->number($activeChits),
                'icon' => 'bi-journal-check',
                'tone' => 'success',
                'trend' => $this->number($newEnrollments).' new this month',
                'url' => route('chit-enrollments.index'),
            ],
            [
                'label' => 'Today collection',
                'value' => $this->money($todayCollection),
                'icon' => 'bi-cash-stack',
                'tone' => 'warning',
                'trend' => $this->number($todayReceiptCount).' payments posted today',
                'url' => route('payments.index', ['from_date' => $today->toDateString(), 'to_date' => $today->toDateString()]),
            ],
            [
                'label' => 'Monthly collection',
                'value' => $this->money($monthlyCollection),
                'icon' => 'bi-calendar3',
                'tone' => 'info',
                'trend' => $this->monthlyCollectionTrendText($monthlyCollection, $lastMonthCollection),
                'url' => route('payments.index'),
            ],
            [
                'label' => 'Pending dues',
                'value' => $this->money($pendingDues),
                'icon' => 'bi-hourglass-split',
                'tone' => 'danger',
                'trend' => $this->number($pendingInstallmentCount).' installments pending',
                'url' => route('pending-dues.index'),
            ],
            [
                'label' => 'Matured chits',
                'value' => $this->number($maturedChits),
                'icon' => 'bi-award',
                'tone' => 'purple',
                'trend' => 'Ready for closing',
                'url' => route('maturity-closings.index'),
            ],
            [
                'label' => 'Closed chits',
                'value' => $this->number($closedChits),
                'icon' => 'bi-check2-circle',
                'tone' => 'dark',
                'trend' => $this->number($closedThisMonth).' closed this month',
                'url' => route('chit-enrollments.index', ['status' => 'closed']),
            ],
            [
                'label' => 'Overdue customers',
                'value' => $this->number($overdueCustomers),
                'icon' => 'bi-exclamation-triangle',
                'tone' => 'orange',
                'trend' => $this->number($overdueInstallments->count()).' overdue installments',
                'url' => route('pending-dues.overdue'),
            ],
        ];
    }

    /**
     * @return array{labels: array<int, string>, series: array<int, float>}
     */
    private function staffWiseCollection(Carbon $from, Carbon $to): array
    {
        $payments = $this->betweenDates($this->successfulPaymentQuery(), 'payment_date', $from, $to)
            ->with('staff:id,name')
            ->get(['id', 'staff_id', 'total_amount']);

        return $this->chartFromTotals($payments
            ->groupBy(fn (ChitPayment $payment): string => $payment->staff?->name ?? 'Unassigned')
            ->map(fn (Collection $group): float => (float) $group->sum('total_amount'))
            ->sortDesc()
            ->take(8));
    }

    /**
     * @return array{labels: array<int, string>, series: array<int, float>}
     */
    private function schemeWiseCollection(Carbon $from, Carbon $to): array
    {
        $payments = $this->betweenDates($this->successfulPaymentQuery(), 'payment_date', $from, $to)
            ->with('enrollment.scheme')
            ->get(['id', 'enrollment_id', 'total_amount']);

        return $this->chartFromTotals($payments
            ->groupBy(fn (ChitPayment $payment): string => $payment->enrollment?->scheme?->name ?? 'No scheme')
            ->map(fn (Collection $group): float => (float) $group->sum('total_amount'))
            ->sortDesc()
            ->take(8));
    }

    /**
     * @return array{labels: array<int, string>, series: array<int, float>}
     */
    private function monthlyCollectionTrend(): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $offset): Carbon => today()->startOfMonth()->subMonths($offset));

        $from = $months->first();
        $to = today()->endOfMonth();

        $totals = $this->betweenDates($this->successfulPaymentQuery(), 'payment_date', $from, $to)
            ->get(['id', 'payment_date', 'total_amount'])
            ->groupBy(fn (ChitPayment $payment): string => optional($payment->payment_date)->format('Y-m') ?? '')
            ->map(fn (Collection $group): float => (float) $group->sum('total_amount'));

        return [
            'labels' => $months->map(fn (Carbon $month): string => $month->format('M y'))->values()->all(),
            'series' => $months
                ->map(fn (Carbon $month): float => round((float) ($totals[$month->format('Y-m')] ?? 0), 2))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, series: array<int, float>}
     */
    private function paymentModeCollection(Carbon $from, Carbon $to): array
    {
        $payments = $this->betweenDates($this->successfulPaymentQuery(), 'payment_date', $from, $to)
            ->with('paymentMode:id,name')
            ->get(['id', 'payment_mode_id', 'total_amount']);

        $totals = $payments
            ->groupBy(fn (ChitPayment $payment): string => $payment->paymentMode?->name ?? 'Unknown')
            ->map(fn (Collection $group): float => (float) $group->sum('total_amount'))
            ->filter(fn (float $total): bool => $total > 0)
            ->sortDesc();

        $totalCollection = (float) $totals->sum();

        if ($totalCollection <= 0) {
            return ['labels' => ['No collection'], 'series' => [0]];
        }

        return [
            'labels' => $totals->keys()->values()->all(),
            'series' => $totals
                ->map(fn (float $amount): float => round(($amount / $totalCollection) * 100, 2))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recentActivities(): array
    {
        return $this->activityQuery()
            ->with('user:id,name')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (ActivityLog $activity): array => [
                'title' => str($activity->module ?: 'System')->replace('_', ' ')->title().' '.$activity->action,
                'description' => $activity->description ?: (($activity->user?->name ?? 'System').' performed '.$activity->action),
                'time' => $activity->created_at?->diffForHumans() ?? '-',
                'type' => str($activity->module ?: 'Activity')->replace('_', ' ')->title()->toString(),
            ])
            ->all();
    }

    private function customerQuery(): Builder
    {
        $query = Customer::query();
        $user = Auth::user();

        if (! $user || $user->hasRole('Admin')) {
            return $query;
        }

        if ($user->hasRole('Manager') && $user->branch_id) {
            return $query->whereHas('enrollments', fn (Builder $query): Builder => $query->where('branch_id', $user->branch_id));
        }

        if ($user->hasRole('Staff')) {
            return $query->where(function (Builder $query) use ($user): void {
                $query->where('created_by', $user->id)
                    ->orWhereHas('enrollments', fn (Builder $query): Builder => $query->where('assigned_staff_id', $user->id));
            });
        }

        return $query;
    }

    private function enrollmentQuery(): Builder
    {
        $query = ChitEnrollment::query();
        $user = Auth::user();

        if (! $user || $user->hasRole('Admin')) {
            return $query;
        }

        if ($user->hasRole('Manager') && $user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        if ($user->hasRole('Staff')) {
            return $query->where('assigned_staff_id', $user->id);
        }

        return $query;
    }

    private function installmentQuery(): Builder
    {
        $query = ChitInstallment::query();
        $user = Auth::user();

        if (! $user || $user->hasRole('Admin')) {
            return $query;
        }

        if ($user->hasRole('Manager') && $user->branch_id) {
            return $query->whereHas('enrollment', fn (Builder $query): Builder => $query->where('branch_id', $user->branch_id));
        }

        if ($user->hasRole('Staff')) {
            return $query->whereHas('enrollment', fn (Builder $query): Builder => $query->where('assigned_staff_id', $user->id));
        }

        return $query;
    }

    private function successfulPaymentQuery(): Builder
    {
        $query = ChitPayment::query()->where('status', 'success');
        $user = Auth::user();

        if (! $user || $user->hasRole('Admin')) {
            return $query;
        }

        if ($user->hasRole('Manager') && $user->branch_id) {
            return $query->where(function (Builder $query) use ($user): void {
                $query->where('branch_id', $user->branch_id)
                    ->orWhereHas('enrollment', fn (Builder $query): Builder => $query->where('branch_id', $user->branch_id));
            });
        }

        if ($user->hasRole('Staff')) {
            return $query->where(function (Builder $query) use ($user): void {
                $query->where('staff_id', $user->id)
                    ->orWhereHas('enrollment', fn (Builder $query): Builder => $query->where('assigned_staff_id', $user->id));
            });
        }

        return $query;
    }

    private function activityQuery(): Builder
    {
        $query = ActivityLog::query();
        $user = Auth::user();

        if (! $user || $user->hasRole('Admin')) {
            return $query;
        }

        if ($user->hasRole('Manager') && $user->branch_id) {
            return $query->whereHas('user', fn (Builder $query): Builder => $query->where('branch_id', $user->branch_id));
        }

        if ($user->hasRole('Staff')) {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }

    private function pendingInstallmentQuery(): Builder
    {
        return $this->installmentQuery()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->where('balance_amount', '>', 0);
    }

    private function overdueInstallmentQuery(): Builder
    {
        return $this->installmentQuery()
            ->where('balance_amount', '>', 0)
            ->where(function (Builder $query): void {
                $query->where('status', 'overdue')
                    ->orWhereDate('due_date', '<', today());
            });
    }

    private function maturedEnrollmentQuery(): Builder
    {
        return $this->enrollmentQuery()
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->where(function (Builder $query): void {
                $query->where('status', 'matured')
                    ->orWhereDate('maturity_date', '<=', today());
            });
    }

    private function betweenDates(Builder $query, string $column, Carbon $from, Carbon $to): Builder
    {
        return $query
            ->whereDate($column, '>=', $from->toDateString())
            ->whereDate($column, '<=', $to->toDateString());
    }

    /**
     * @param  Collection<string, float>  $totals
     * @return array{labels: array<int, string>, series: array<int, float>}
     */
    private function chartFromTotals(Collection $totals): array
    {
        $totals = $totals->filter(fn (float $total): bool => $total > 0);

        if ($totals->isEmpty()) {
            return ['labels' => ['No collection'], 'series' => [0]];
        }

        return [
            'labels' => $totals->keys()->values()->all(),
            'series' => $totals->map(fn (float $total): float => round($total, 2))->values()->all(),
        ];
    }

    private function monthlyCollectionTrendText(float $currentMonth, float $lastMonth): string
    {
        if ($lastMonth <= 0) {
            return $currentMonth > 0 ? 'First collection month' : 'No collection this month';
        }

        $percentage = (($currentMonth - $lastMonth) / $lastMonth) * 100;
        $prefix = $percentage >= 0 ? '+' : '';

        return $prefix.number_format($percentage, 1).'% vs last month';
    }

    private function money(float|int $value): string
    {
        return 'Rs. '.number_format((float) $value, 2);
    }

    private function number(int|float $value): string
    {
        return number_format((float) $value);
    }
}
