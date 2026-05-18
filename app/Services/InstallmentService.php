<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Repositories\InstallmentRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstallmentService
{
    public function __construct(
        private readonly InstallmentRepository $installments,
        private readonly LedgerService $ledgerService
    ) {
    }

    /**
     * @return Collection<int, ChitInstallment>
     */
    public function generateSchedule(ChitEnrollment $enrollment): Collection
    {
        return DB::transaction(function () use ($enrollment): Collection {
            if ($enrollment->installments()->exists()) {
                return $this->getEnrollmentInstallments($enrollment);
            }

            $rows = [];
            $dueAmount = $this->calculateDueAmount($enrollment);
            $timestamp = now();

            for ($installmentNumber = 1; $installmentNumber <= (int) $enrollment->total_months; $installmentNumber++) {
                $rows[] = [
                    'enrollment_id' => $enrollment->id,
                    'installment_no' => $installmentNumber,
                    'due_date' => $this->calculateDueDate($enrollment->start_date, $installmentNumber)->toDateString(),
                    'due_amount' => $dueAmount,
                    'paid_amount' => 0,
                    'balance_amount' => $dueAmount,
                    'late_fee' => 0,
                    'status' => 'pending',
                    'paid_date' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if ($rows !== []) {
                $this->installments->query()->insert($rows);
            }

            $createdInstallments = $this->getEnrollmentInstallments($enrollment);

            foreach ($createdInstallments as $installment) {
                $this->ledgerService->createDueEntry($installment);
            }

            $this->logInstallmentAction(
                $enrollment,
                'schedule_generation',
                'schedule generated',
                null,
                ['installment_count' => $createdInstallments->count()]
            );

            return $createdInstallments;
        });
    }

    /**
     * @return Collection<int, ChitInstallment>
     */
    public function regenerateSchedule(ChitEnrollment $enrollment): Collection
    {
        return DB::transaction(function () use ($enrollment): Collection {
            if ($enrollment->payments()->exists()) {
                throw ValidationException::withMessages([
                    'enrollment' => 'Installment schedule cannot be regenerated after payments exist.',
                ]);
            }

            $oldValues = [
                'installments' => $this->getEnrollmentInstallments($enrollment)->toArray(),
            ];

            $enrollment->installments()->delete();
            $installments = $this->generateSchedule($enrollment);

            $this->logInstallmentAction(
                $enrollment,
                'schedule_regeneration',
                'schedule regenerated',
                $oldValues,
                ['installment_count' => $installments->count()]
            );

            return $installments;
        });
    }

    public function calculateDueDate(mixed $startDate, int $installmentNumber): Carbon
    {
        return Carbon::parse($startDate)->addMonthsNoOverflow(max(0, $installmentNumber - 1));
    }

    public function calculateDueAmount(ChitEnrollment $enrollment): float
    {
        return round((float) ($enrollment->monthly_amount ?? 0), 2);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateInstallment(ChitInstallment $installment, array $data): ChitInstallment
    {
        return DB::transaction(function () use ($installment, $data): ChitInstallment {
            $oldValues = $installment->load('enrollment')->toArray();
            $dueAmount = (float) $data['due_amount'];
            $lateFee = (float) ($data['late_fee'] ?? 0);
            $paidAmount = (float) $installment->paid_amount;
            $balanceAmount = max(0, round($dueAmount + $lateFee - $paidAmount, 2));
            $explicitStatus = (string) $data['status'];

            $installment = $this->installments->update($installment, [
                'due_date' => $data['due_date'],
                'due_amount' => $dueAmount,
                'late_fee' => $lateFee,
                'balance_amount' => $balanceAmount,
                'status' => $explicitStatus,
            ]);

            $installment = $this->updateInstallmentStatus($installment);

            $this->logInstallmentAction(
                $installment->enrollment,
                'update',
                'installment updated',
                $oldValues,
                $installment->fresh()->toArray(),
                $installment
            );

            return $installment->load(['enrollment.customer', 'enrollment.scheme', 'enrollment.branch', 'enrollment.assignedStaff']);
        });
    }

    public function updateInstallmentStatus(ChitInstallment $installment): ChitInstallment
    {
        return DB::transaction(function () use ($installment): ChitInstallment {
            $oldValues = $installment->toArray();
            $installment = $this->applyAdvancePayment($installment);
            $status = $this->resolveStatus($installment);
            $paidDate = $status === 'paid' ? ($installment->paid_date ?? now()->toDateString()) : $installment->paid_date;

            $installment = $this->installments->update($installment, [
                'balance_amount' => max(0, round((float) $installment->due_amount + (float) $installment->late_fee - (float) $installment->paid_amount, 2)),
                'status' => $status,
                'paid_date' => $paidDate,
            ]);

            if (($oldValues['status'] ?? null) !== $installment->status) {
                $this->logInstallmentAction(
                    $installment->enrollment,
                    'status_update',
                    'installment status updated',
                    $oldValues,
                    $installment->toArray(),
                    $installment
                );
            }

            $this->refreshEnrollmentTotals($installment->enrollment);

            return $installment;
        });
    }

    public function markOverdueInstallments(): int
    {
        $installments = $this->installments->query()
            ->with('enrollment')
            ->whereDate('due_date', '<', today())
            ->where('status', 'pending')
            ->where('balance_amount', '>', 0)
            ->get();

        $installments->each(fn (ChitInstallment $installment): ChitInstallment => $this->updateInstallmentStatus($installment));

        return $installments->count();
    }

    /**
     * @return Collection<int, ChitInstallment>
     */
    public function getEnrollmentInstallments(ChitEnrollment $enrollment): Collection
    {
        return $this->installments
            ->getByEnrollment($enrollment->id)
            ->with(['payments'])
            ->get();
    }

    private function resolveStatus(ChitInstallment $installment): string
    {
        if ($installment->status === 'advance') {
            return 'advance';
        }

        $dueTotal = (float) $installment->due_amount + (float) $installment->late_fee;
        $paidAmount = (float) $installment->paid_amount;

        if ($dueTotal > 0 && $paidAmount >= $dueTotal) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        if ($installment->due_date && $installment->due_date->lt(today())) {
            return 'overdue';
        }

        return 'pending';
    }

    private function applyAdvancePayment(ChitInstallment $installment): ChitInstallment
    {
        $dueTotal = (float) $installment->due_amount + (float) $installment->late_fee;
        $paidAmount = (float) $installment->paid_amount;
        $excessAmount = round($paidAmount - $dueTotal, 2);

        if ($dueTotal <= 0 || $excessAmount <= 0) {
            return $installment;
        }

        $enrollment = $installment->enrollment()->first();

        $installment = $this->installments->update($installment, [
            'paid_amount' => $dueTotal,
            'balance_amount' => 0,
            'status' => 'paid',
            'paid_date' => $installment->paid_date ?? now()->toDateString(),
        ]);

        $futureInstallments = $this->installments->query()
            ->where('enrollment_id', $installment->enrollment_id)
            ->where('installment_no', '>', $installment->installment_no)
            ->orderBy('installment_no')
            ->get();

        foreach ($futureInstallments as $futureInstallment) {
            if ($excessAmount <= 0) {
                break;
            }

            $futureDueTotal = (float) $futureInstallment->due_amount + (float) $futureInstallment->late_fee;
            $futurePaidAmount = (float) $futureInstallment->paid_amount;
            $futureBalance = max(0, round($futureDueTotal - $futurePaidAmount, 2));

            if ($futureBalance <= 0) {
                continue;
            }

            $oldFutureValues = $futureInstallment->toArray();
            $appliedAmount = min($excessAmount, $futureBalance);
            $newPaidAmount = round($futurePaidAmount + $appliedAmount, 2);
            $newBalance = max(0, round($futureDueTotal - $newPaidAmount, 2));
            $newStatus = $newBalance <= 0 ? 'paid' : 'partial';

            $futureInstallment = $this->installments->update($futureInstallment, [
                'paid_amount' => $newPaidAmount,
                'balance_amount' => $newBalance,
                'status' => $newStatus,
                'paid_date' => $newStatus === 'paid' ? ($futureInstallment->paid_date ?? now()->toDateString()) : $futureInstallment->paid_date,
            ]);

            if ($enrollment) {
                $this->logInstallmentAction(
                    $enrollment,
                    'status_update',
                    'advance applied',
                    $oldFutureValues,
                    $futureInstallment->toArray(),
                    $futureInstallment
                );
            }

            $excessAmount = round($excessAmount - $appliedAmount, 2);
        }

        return $installment->refresh();
    }

    private function refreshEnrollmentTotals(?ChitEnrollment $enrollment): void
    {
        if (! $enrollment) {
            return;
        }

        $totalPayable = (float) $enrollment->installments()->sum('due_amount');
        $totalPaid = (float) $enrollment->installments()->sum('paid_amount');

        $enrollment->update([
            'total_payable' => round($totalPayable, 2),
            'total_paid' => round($totalPaid, 2),
            'total_pending' => max(0, round($totalPayable - $totalPaid, 2)),
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logInstallmentAction(
        ChitEnrollment $enrollment,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?ChitInstallment $installment = null
    ): void {
        $actorId = Auth::id();
        $subject = $installment
            ? "installment {$installment->installment_no}"
            : "schedule for {$enrollment->chit_no}";

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'chit_installments',
            'description' => ucfirst($subject).' '.$action.'.',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => $installment ? ChitInstallment::class : ChitEnrollment::class,
            'auditable_id' => $installment?->id ?? $enrollment->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
