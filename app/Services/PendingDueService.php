<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\ChitInstallment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PendingDueService
{
    public function __construct(
        private readonly MessageService $messageService
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPendingDuesQuery(array $filters = []): Builder
    {
        return ChitInstallment::query()
            ->with(['enrollment.customer', 'enrollment.scheme', 'enrollment.branch', 'enrollment.assignedStaff'])
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->where('balance_amount', '>', 0)
            ->when(Arr::get($filters, 'due_type'), fn (Builder $query, string $type): Builder => $this->applyDueType($query, $type))
            ->when(Arr::get($filters, 'customer_id'), fn (Builder $query, mixed $customerId): Builder => $query->whereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('customer_id', $customerId)))
            ->when(Arr::get($filters, 'staff_id'), fn (Builder $query, mixed $staffId): Builder => $query->whereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('assigned_staff_id', $staffId)))
            ->when(Arr::get($filters, 'branch_id'), fn (Builder $query, mixed $branchId): Builder => $query->whereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('branch_id', $branchId)))
            ->when(Arr::get($filters, 'scheme_id'), fn (Builder $query, mixed $schemeId): Builder => $query->whereHas('enrollment', fn (Builder $enrollment): Builder => $enrollment->where('scheme_id', $schemeId)))
            ->when(Arr::get($filters, 'status'), fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(Arr::get($filters, 'followup_status'), fn (Builder $query, string $status): Builder => $query->where('followup_status', $status))
            ->when(Arr::get($filters, 'from_date'), fn (Builder $query, string $fromDate): Builder => $query->whereDate('due_date', '>=', $fromDate))
            ->when(Arr::get($filters, 'to_date'), fn (Builder $query, string $toDate): Builder => $query->whereDate('due_date', '<=', $toDate));
    }

    /**
     * @return Collection<int, ChitInstallment>
     */
    public function getTodayDues(): Collection
    {
        return $this->getPendingDuesQuery(['due_type' => 'today'])->orderBy('due_date')->get();
    }

    /**
     * @return Collection<int, ChitInstallment>
     */
    public function getWeeklyDues(): Collection
    {
        return $this->getPendingDuesQuery(['due_type' => 'weekly'])->orderBy('due_date')->get();
    }

    /**
     * @return Collection<int, ChitInstallment>
     */
    public function getMonthlyDues(): Collection
    {
        return $this->getPendingDuesQuery(['due_type' => 'monthly'])->orderBy('due_date')->get();
    }

    /**
     * @return Collection<int, ChitInstallment>
     */
    public function getOverdueDues(): Collection
    {
        return $this->getPendingDuesQuery(['due_type' => 'overdue'])->orderBy('due_date')->get();
    }

    /**
     * @return Collection<int, ChitInstallment>
     */
    public function getStaffWisePending(mixed $staffId): Collection
    {
        return $this->getPendingDuesQuery(['staff_id' => $staffId])->orderBy('due_date')->get();
    }

    /**
     * @return Collection<int, ChitInstallment>
     */
    public function getBranchWisePending(mixed $branchId): Collection
    {
        return $this->getPendingDuesQuery(['branch_id' => $branchId])->orderBy('due_date')->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function calculateDueSummary(array $filters): array
    {
        $summary = (clone $this->getPendingDuesQuery($filters))
            ->toBase()
            ->selectRaw('COUNT(*) as aggregate_count')
            ->selectRaw('COALESCE(SUM(due_amount), 0) as total_due')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as total_balance')
            ->selectRaw('COALESCE(SUM(late_fee), 0) as total_late_fee')
            ->first();

        return [
            'count' => (int) ($summary->aggregate_count ?? 0),
            'total_due' => round((float) ($summary->total_due ?? 0), 2),
            'total_paid' => round((float) ($summary->total_paid ?? 0), 2),
            'total_balance' => round((float) ($summary->total_balance ?? 0), 2),
            'total_late_fee' => round((float) ($summary->total_late_fee ?? 0), 2),
            'today_count' => $this->getPendingDuesQuery(array_replace($filters, ['due_type' => 'today']))->count(),
            'overdue_count' => $this->getPendingDuesQuery(array_replace($filters, ['due_type' => 'overdue']))->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateFollowUpStatus(ChitInstallment $installment, array $data): ChitInstallment
    {
        return DB::transaction(function () use ($installment, $data): ChitInstallment {
            $oldValues = $installment->toArray();
            $installment->update([
                'followup_status' => $data['followup_status'],
                'promise_to_pay_date' => $data['promise_to_pay_date'] ?? null,
                'followup_remarks' => $data['remarks'] ?? null,
                'last_followup_at' => now(),
            ]);

            $installment = $installment->refresh()->load(['enrollment.customer', 'enrollment.scheme']);
            $this->logPendingDueAction($installment, 'followup', 'follow-up updated', $oldValues, $installment->toArray());

            return $installment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setPromiseToPayDate(ChitInstallment $installment, array $data): ChitInstallment
    {
        return $this->updateFollowUpStatus($installment, [
            'followup_status' => $data['followup_status'] ?? 'promised',
            'promise_to_pay_date' => $data['promise_to_pay_date'] ?? null,
            'remarks' => $data['remarks'] ?? $installment->followup_remarks,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendDueReminder(ChitInstallment $installment, string $channel): array
    {
        $this->validateChannel($channel);

        return DB::transaction(function () use ($installment, $channel): array {
            $installment->loadMissing(['enrollment.customer', 'enrollment.scheme']);
            $customer = $installment->enrollment?->customer;
            $enrollment = $installment->enrollment;

            if (! $customer || ! $enrollment) {
                throw ValidationException::withMessages(['customer' => 'Customer and enrollment details are required for reminders.']);
            }

            $messageResult = $this->messageService->sendDueReminder($customer, $enrollment, $installment, $channel);

            $oldValues = $installment->toArray();
            $installment->update([
                'reminder_count' => (int) $installment->reminder_count + 1,
                'last_reminder_at' => now(),
            ]);

            $installment = $installment->refresh();
            $this->logPendingDueAction($installment, 'reminder', "{$channel} reminder sent", $oldValues, [
                'channel' => $channel,
                'message' => $messageResult['message'],
                'message_log_id' => $messageResult['log']->id ?? null,
                'reminder_count' => $installment->reminder_count,
            ]);

            return [
                'installment_id' => $installment->id,
                'channel' => $channel,
                'message' => $messageResult['message'],
                'message_log_id' => $messageResult['log']->id ?? null,
            ];
        });
    }

    /**
     * @param  array<int, mixed>  $installmentIds
     * @return array<string, mixed>
     */
    public function sendBulkDueReminder(array $installmentIds, string $channel): array
    {
        $this->validateChannel($channel);
        $sent = [];

        $installments = $this->getPendingDuesQuery()
            ->whereIn('id', $installmentIds)
            ->orderBy('due_date')
            ->get();

        foreach ($installments as $installment) {
            $sent[] = $this->sendDueReminder($installment, $channel);
        }

        return [
            'count' => count($sent),
            'channel' => $channel,
            'items' => $sent,
        ];
    }

    private function applyDueType(Builder $query, string $type): Builder
    {
        return match ($type) {
            'today' => $query->whereDate('due_date', today()),
            'weekly' => $query->whereBetween('due_date', [today()->startOfWeek()->toDateString(), today()->endOfWeek()->toDateString()]),
            'monthly' => $query->whereBetween('due_date', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()]),
            'overdue' => $query->whereDate('due_date', '<', today()),
            default => $query,
        };
    }

    private function validateChannel(string $channel): void
    {
        if (! in_array($channel, ['whatsapp', 'sms'], true)) {
            throw ValidationException::withMessages([
                'channel' => 'Reminder channel must be whatsapp or sms.',
            ]);
        }
    }

    private function reminderMessage(ChitInstallment $installment): string
    {
        $installment->loadMissing(['enrollment.customer']);
        $customerName = $installment->enrollment?->customer?->name ?? 'Customer';
        $chitNo = $installment->enrollment?->chit_no ?? '-';
        $amount = number_format((float) $installment->balance_amount, 2);

        if ($installment->due_date && $installment->due_date->lt(today())) {
            return "Dear {$customerName}, your chit installment for {$chitNo} amount ₹{$amount} is overdue. Please pay immediately to avoid extra charges.";
        }

        $dueDate = optional($installment->due_date)->format('d M Y');

        return "Dear {$customerName}, your chit installment for {$chitNo} amount ₹{$amount} is due on {$dueDate}. Please pay before due date. Thank you.";
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logPendingDueAction(
        ChitInstallment $installment,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'pending_dues',
            'description' => "Installment #{$installment->installment_no} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => ChitInstallment::class,
            'auditable_id' => $installment->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
