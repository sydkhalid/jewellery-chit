<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\ChitClosure;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitPayment;
use App\Models\ChitRefund;
use App\Models\JewelleryInvoice;
use App\Models\ShopSetting;
use App\Repositories\MaturityClosingRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaturityClosingService
{
    public function __construct(
        private readonly MaturityClosingRepository $closings,
        private readonly LedgerService $ledgerService,
        private readonly CashflowService $cashflowService
    ) {
    }

    public function calculateMaturityValue(ChitEnrollment $enrollment): float
    {
        return round($this->calculateTotalPaid($enrollment) + $this->calculateShopBonus($enrollment), 2);
    }

    public function calculateTotalPaid(ChitEnrollment $enrollment): float
    {
        return round((float) ChitPayment::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('status', 'success')
            ->sum('amount'), 2);
    }

    public function calculateShopBonus(ChitEnrollment $enrollment): float
    {
        $enrollment->loadMissing('scheme');
        $scheme = $enrollment->scheme;

        if (! $scheme || $scheme->shop_bonus_type === 'none') {
            return 0.0;
        }

        return match ($scheme->shop_bonus_type) {
            'fixed' => round((float) $scheme->shop_bonus_value, 2),
            'percentage' => round(($this->calculateTotalPaid($enrollment) * (float) $scheme->shop_bonus_value) / 100, 2),
            default => 0.0,
        };
    }

    public function calculateDeductions(ChitEnrollment $enrollment): float
    {
        return round((float) ChitInstallment::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('late_fee'), 2);
    }

    public function calculateFinalMaturityValue(ChitEnrollment $enrollment): float
    {
        return max(0, round(
            $this->calculateTotalPaid($enrollment)
            + $this->calculateShopBonus($enrollment)
            - $this->calculateDeductions($enrollment),
            2
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createNormalClosing(ChitEnrollment $enrollment, array $data): ChitClosure
    {
        if (! $enrollment->maturity_date || $enrollment->maturity_date->isFuture()) {
            throw ValidationException::withMessages([
                'closure_type' => 'Normal closing is allowed only after maturity date.',
            ]);
        }

        return $this->createClosing($enrollment, $data, 'normal');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEarlyClosing(ChitEnrollment $enrollment, array $data): ChitClosure
    {
        if ($enrollment->maturity_date && ! $enrollment->maturity_date->isFuture()) {
            throw ValidationException::withMessages([
                'closure_type' => 'Use normal closing for already matured chits.',
            ]);
        }

        return $this->createClosing($enrollment, $data, 'early');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDefaultedClosing(ChitEnrollment $enrollment, array $data): ChitClosure
    {
        $hasOverdue = $enrollment->installments()
            ->whereIn('status', ['overdue', 'partial', 'pending'])
            ->whereDate('due_date', '<', today())
            ->where('balance_amount', '>', 0)
            ->exists();

        if ($enrollment->status !== 'defaulted' && ! $hasOverdue) {
            throw ValidationException::withMessages([
                'closure_type' => 'Defaulted closing needs a defaulted chit or overdue unpaid installments.',
            ]);
        }

        return $this->createClosing($enrollment, $data, 'defaulted');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCancelledClosing(ChitEnrollment $enrollment, array $data): ChitClosure
    {
        return $this->createClosing($enrollment, $data, 'cancelled');
    }

    public function approveClosing(ChitClosure $closure): ChitClosure
    {
        return DB::transaction(function () use ($closure): ChitClosure {
            if ($closure->status !== 'pending') {
                throw ValidationException::withMessages([
                    'closure' => 'Only pending closings can be approved.',
                ]);
            }

            $oldValues = $closure->toArray();
            $closure = $this->closings->update($closure, [
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $this->logClosingAction($closure, 'approval', 'approved', $oldValues, $closure->toArray());

            return $closure->load(['customer', 'enrollment.scheme', 'approver']);
        });
    }

    public function completeClosing(ChitClosure $closure): ChitClosure
    {
        return DB::transaction(function () use ($closure): ChitClosure {
            if ($closure->status !== 'approved') {
                throw ValidationException::withMessages([
                    'closure' => 'Closing must be approved before completion.',
                ]);
            }

            $closure->load(['enrollment', 'customer']);
            $oldValues = $closure->toArray();
            $this->validateSettlementAmounts($closure);

            $this->ledgerService->createClosingEntry($closure);

            if ((float) $closure->refund_amount > 0) {
                $refund = $this->createRefund($closure);
                $this->ledgerService->createRefundEntry($refund);
                $this->cashflowService->createRefundCashEntry($refund);
            }

            if ((float) $closure->jewellery_adjustment_amount > 0) {
                $invoice = $this->createJewelleryAdjustment($closure);
                $this->ledgerService->createJewelleryAdjustmentEntry($invoice);
                $this->cashflowService->createJewelleryAdjustmentEntry($invoice);
            }

            $closure->enrollment?->update([
                'status' => 'closed',
                'total_paid' => $this->calculateTotalPaid($closure->enrollment),
                'total_pending' => 0,
                'updated_by' => Auth::id(),
            ]);

            $closure = $this->closings->update($closure, [
                'status' => 'completed',
                'completed_by' => Auth::id(),
                'completed_at' => now(),
            ]);

            $this->logClosingAction($closure, 'completion', 'completed', $oldValues, $closure->toArray());

            return $closure->load(['customer', 'enrollment.scheme', 'approver', 'completer']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function cancelClosing(ChitClosure $closure, array $data): ChitClosure
    {
        return DB::transaction(function () use ($closure, $data): ChitClosure {
            if ($closure->status === 'completed') {
                throw ValidationException::withMessages([
                    'closure' => 'Completed closings cannot be cancelled.',
                ]);
            }

            if ($closure->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'closure' => 'Closing is already cancelled.',
                ]);
            }

            $oldValues = $closure->toArray();
            $closure = $this->closings->update($closure, [
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $data['cancellation_reason'],
            ]);

            $this->logClosingAction($closure, 'cancellation', 'cancelled', $oldValues, $closure->toArray());

            return $closure->load(['customer', 'enrollment.scheme', 'canceller']);
        });
    }

    public function uploadCustomerSignature(ChitClosure $closure, mixed $file): ChitClosure
    {
        if (! $file instanceof UploadedFile) {
            return $closure;
        }

        $oldValues = $closure->toArray();
        $path = $file->store('maturity-signatures', 'public');
        $closure = $this->closings->update($closure, ['customer_signature' => $path]);
        $this->logClosingAction($closure, 'update', 'signature uploaded', $oldValues, $closure->toArray());

        return $closure;
    }

    /**
     * @return array<string, mixed>
     */
    public function calculationSummary(ChitEnrollment $enrollment, ?float $deductions = null): array
    {
        $enrollment->loadMissing(['customer', 'scheme', 'installments', 'payments']);
        $totalPaid = $this->calculateTotalPaid($enrollment);
        $shopBonus = $this->calculateShopBonus($enrollment);
        $deductions = round($deductions ?? $this->calculateDeductions($enrollment), 2);
        $finalMaturityValue = max(0, round($totalPaid + $shopBonus - $deductions, 2));
        $paidMonths = $enrollment->installments->where('status', 'paid')->count();

        return [
            'enrollment' => $enrollment,
            'customer' => $enrollment->customer,
            'scheme' => $enrollment->scheme,
            'total_months' => (int) $enrollment->total_months,
            'paid_months' => $paidMonths,
            'pending_months' => max(0, (int) $enrollment->total_months - $paidMonths),
            'total_paid' => $totalPaid,
            'shop_bonus' => $shopBonus,
            'deductions' => $deductions,
            'maturity_value' => round($totalPaid + $shopBonus, 2),
            'final_maturity_value' => $finalMaturityValue,
            'total_pending' => (float) $enrollment->total_pending,
            'maturity_rule' => $enrollment->scheme?->maturity_rule,
            'early_closing_rule' => $enrollment->scheme?->early_closing_rule,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createClosing(ChitEnrollment $enrollment, array $data, string $type): ChitClosure
    {
        return DB::transaction(function () use ($enrollment, $data, $type): ChitClosure {
            $enrollment = ChitEnrollment::query()
                ->with(['customer', 'scheme'])
                ->lockForUpdate()
                ->findOrFail($enrollment->id);

            if (in_array($enrollment->status, ['closed', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'enrollment_id' => 'Closed or cancelled chit enrollments cannot be closed again.',
                ]);
            }

            if ($enrollment->closure()->whereIn('status', ['pending', 'approved', 'completed'])->exists()) {
                throw ValidationException::withMessages([
                    'enrollment_id' => 'This chit already has an active maturity closing.',
                ]);
            }

            $deductions = round((float) ($data['deductions'] ?? $this->calculateDeductions($enrollment)), 2);
            $summary = $this->calculationSummary($enrollment, $deductions);
            $refundAmount = round((float) ($data['refund_amount'] ?? 0), 2);
            $jewelleryAdjustmentAmount = round((float) ($data['jewellery_adjustment_amount'] ?? 0), 2);
            $this->validateSettlementValues($summary['final_maturity_value'], $refundAmount, $jewelleryAdjustmentAmount);

            $closure = $this->closings->create([
                'closure_no' => $this->generateClosureNumber(),
                'enrollment_id' => $enrollment->id,
                'customer_id' => $enrollment->customer_id,
                'closure_type' => $type,
                'total_paid' => $summary['total_paid'],
                'shop_bonus' => $summary['shop_bonus'],
                'deductions' => $summary['deductions'],
                'final_maturity_value' => $summary['final_maturity_value'],
                'refund_amount' => $refundAmount,
                'jewellery_adjustment_amount' => $jewelleryAdjustmentAmount,
                'remarks' => $data['remarks'] ?? null,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            if (($data['customer_signature'] ?? null) instanceof UploadedFile) {
                $closure = $this->uploadCustomerSignature($closure, $data['customer_signature']);
            }

            $closure->load(['customer', 'enrollment.scheme', 'creator']);
            $this->logClosingAction($closure, 'closing', "{$type} closing created", null, $closure->toArray());

            return $closure;
        });
    }

    private function validateSettlementAmounts(ChitClosure $closure): void
    {
        $this->validateSettlementValues(
            (float) $closure->final_maturity_value,
            (float) $closure->refund_amount,
            (float) $closure->jewellery_adjustment_amount
        );
    }

    private function validateSettlementValues(float $finalMaturityValue, float $refundAmount, float $jewelleryAdjustmentAmount): void
    {
        if ($refundAmount + $jewelleryAdjustmentAmount > $finalMaturityValue) {
            throw ValidationException::withMessages([
                'refund_amount' => 'Refund and jewellery adjustment cannot exceed final maturity value.',
            ]);
        }
    }

    private function createRefund(ChitClosure $closure): ChitRefund
    {
        return ChitRefund::create([
            'refund_no' => $this->generateRefundNumber(),
            'enrollment_id' => $closure->enrollment_id,
            'customer_id' => $closure->customer_id,
            'payment_mode_id' => null,
            'refund_date' => now()->toDateString(),
            'amount' => $closure->refund_amount,
            'transaction_id' => null,
            'remarks' => "Maturity closing {$closure->closure_no}",
            'status' => 'paid',
            'created_by' => Auth::id(),
        ]);
    }

    private function createJewelleryAdjustment(ChitClosure $closure): JewelleryInvoice
    {
        return JewelleryInvoice::create([
            'invoice_no' => $this->generateInvoiceNumber(),
            'customer_id' => $closure->customer_id,
            'enrollment_id' => $closure->enrollment_id,
            'invoice_date' => now()->toDateString(),
            'gold_rate' => 0,
            'gross_weight' => 0,
            'net_weight' => 0,
            'making_charge' => 0,
            'wastage' => 0,
            'gst_amount' => 0,
            'discount' => 0,
            'chit_adjustment_amount' => $closure->jewellery_adjustment_amount,
            'total_amount' => $closure->jewellery_adjustment_amount,
            'balance_payable' => 0,
            'status' => 'final',
            'created_by' => Auth::id(),
        ]);
    }

    private function generateClosureNumber(): string
    {
        return $this->generateNumber('closure_number_prefix', 'CLS', ChitClosure::class, 'closure_no');
    }

    private function generateRefundNumber(): string
    {
        return $this->generateNumber('refund_number_prefix', 'REF', ChitRefund::class, 'refund_no');
    }

    private function generateInvoiceNumber(): string
    {
        return $this->generateNumber('invoice_number_prefix', 'INV', JewelleryInvoice::class, 'invoice_no');
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function generateNumber(string $settingKey, string $fallbackPrefix, string $modelClass, string $column): string
    {
        $prefix = (string) ShopSetting::getByKey($settingKey, $fallbackPrefix);
        $nextId = (int) $modelClass::withTrashed()->max('id') + 1;

        do {
            $number = $prefix.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while ($modelClass::withTrashed()->where($column, $number)->exists());

        return $number;
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logClosingAction(
        ChitClosure $closure,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'chit_closures',
            'description' => "Closing {$closure->closure_no} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => ChitClosure::class,
            'auditable_id' => $closure->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
