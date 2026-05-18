<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Cashbook;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitLedger;
use App\Models\ChitPayment;
use App\Models\ChitPaymentAllocation;
use App\Models\ChitReceipt;
use App\Models\PaymentMode;
use App\Models\ShopSetting;
use App\Repositories\PaymentRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly ReceiptService $receiptService,
        private readonly LedgerService $ledgerService,
        private readonly CashflowService $cashflowService
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function collectPayment(array $data): ChitPayment
    {
        return DB::transaction(function () use ($data): ChitPayment {
            $enrollment = $this->resolveEnrollment($data);
            $paymentMode = PaymentMode::findOrFail((int) $data['payment_mode_id']);
            $this->validateTransactionReference($paymentMode, $data['transaction_id'] ?? null);

            $paymentType = (string) $data['payment_type'];
            $allocationPlan = match ($paymentType) {
                'full' => $this->collectFullPayment($enrollment, $data),
                'partial' => $this->collectPartialPayment($enrollment, $data),
                'advance' => $this->collectAdvancePayment($enrollment, $data),
                'multiple_month' => $this->collectMultipleMonthPayment($enrollment, $data),
                default => throw ValidationException::withMessages(['payment_type' => 'Invalid payment type.']),
            };

            $firstInstallment = $allocationPlan[0]['installment'] ?? null;
            $lateFeeAmount = array_key_exists('late_fee_amount', $data)
                ? (float) ($data['late_fee_amount'] ?? 0)
                : ($firstInstallment instanceof ChitInstallment ? $this->calculateLateFee($firstInstallment) : 0.0);

            $payment = $this->payments->create([
                'payment_no' => $this->generatePaymentNumber(),
                'enrollment_id' => $enrollment->id,
                'customer_id' => $enrollment->customer_id,
                'installment_id' => $firstInstallment?->id,
                'payment_mode_id' => $paymentMode->id,
                'branch_id' => $data['branch_id'] ?? $enrollment->branch_id ?? Auth::user()?->branch_id,
                'staff_id' => $data['staff_id'] ?? Auth::id(),
                'payment_date' => $data['payment_date'],
                'amount' => round((float) $data['amount'], 2),
                'late_fee_amount' => round($lateFeeAmount, 2),
                'total_amount' => round((float) $data['amount'] + $lateFeeAmount, 2),
                'transaction_id' => $data['transaction_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'payment_type' => $paymentType,
                'status' => 'success',
                'created_by' => Auth::id(),
            ]);

            $this->applyAllocationPlan($payment, $allocationPlan, $lateFeeAmount);
            $this->updateEnrollmentTotals($enrollment);
            $this->createLedgerEntry($payment);
            $this->createCashbookEntry($payment);
            $this->createReceipt($payment);

            $payment->load(['customer', 'enrollment.scheme', 'paymentMode', 'branch', 'staff', 'receipt', 'allocations.installment']);
            $this->logPaymentAction($payment, 'payment', 'created', null, $payment->toArray());

            return $payment;
        });
    }

    /**
     * @return array<int, array{installment: ChitInstallment, amount: float}>
     */
    public function applyPaymentToInstallments(ChitEnrollment $enrollment, float $amount): array
    {
        return $this->buildAllocationPlan($enrollment, $amount, null, true, false);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{installment: ChitInstallment, amount: float}>
     */
    public function collectFullPayment(ChitEnrollment $enrollment, array $data): array
    {
        return $this->buildAllocationPlan($enrollment, (float) $data['amount'], $data['installment_id'] ?? null, false, true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{installment: ChitInstallment, amount: float}>
     */
    public function collectPartialPayment(ChitEnrollment $enrollment, array $data): array
    {
        return $this->buildAllocationPlan($enrollment, (float) $data['amount'], $data['installment_id'] ?? null, false, false);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{installment: ChitInstallment, amount: float}>
     */
    public function collectAdvancePayment(ChitEnrollment $enrollment, array $data): array
    {
        return $this->buildAllocationPlan($enrollment, (float) $data['amount'], $data['installment_id'] ?? null, true, false);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{installment: ChitInstallment, amount: float}>
     */
    public function collectMultipleMonthPayment(ChitEnrollment $enrollment, array $data): array
    {
        return $this->buildAllocationPlan($enrollment, (float) $data['amount'], $data['installment_id'] ?? null, true, false);
    }

    public function calculateLateFee(ChitInstallment $installment): float
    {
        $installment->loadMissing('enrollment.scheme');
        $scheme = $installment->enrollment?->scheme;

        if (! $scheme || $scheme->late_fee_type === 'none') {
            return 0.0;
        }

        $graceDays = (int) $scheme->grace_period_days;
        $chargeableDate = $installment->due_date?->copy()->addDays($graceDays);

        if (! $chargeableDate || $chargeableDate->gte(today()) || (float) $installment->balance_amount <= 0) {
            return 0.0;
        }

        return match ($scheme->late_fee_type) {
            'fixed' => round((float) $scheme->late_fee_value, 2),
            'percentage' => round(((float) $installment->balance_amount * (float) $scheme->late_fee_value) / 100, 2),
            default => 0.0,
        };
    }

    public function generatePaymentNumber(): string
    {
        $prefix = (string) ShopSetting::getByKey('payment_number_prefix', 'PAY');
        $nextId = (int) ChitPayment::withTrashed()->max('id') + 1;

        do {
            $number = $prefix.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while (ChitPayment::withTrashed()->where('payment_no', $number)->exists());

        return $number;
    }

    public function updateEnrollmentTotals(ChitEnrollment $enrollment): ChitEnrollment
    {
        $totalPaid = (float) ChitPayment::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('status', 'success')
            ->sum('amount');

        $enrollment->update([
            'total_paid' => round($totalPaid, 2),
            'total_pending' => max(0, round((float) $enrollment->total_payable - $totalPaid, 2)),
            'updated_by' => Auth::id(),
        ]);

        return $enrollment->refresh();
    }

    public function createLedgerEntry(ChitPayment $payment): ChitLedger
    {
        $this->ledgerService->createLateFeeEntry($payment);

        return $payment->payment_type === 'advance'
            ? $this->ledgerService->createAdvanceEntry($payment)
            : $this->ledgerService->createPaymentEntry($payment);
    }

    public function createCashbookEntry(ChitPayment $payment): Cashbook
    {
        return $this->cashflowService->createPaymentCashEntry($payment);
    }

    public function createReceipt(ChitPayment $payment): ChitReceipt
    {
        return $this->receiptService->generateReceipt($payment);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function cancelPayment(ChitPayment $payment, array $data): ChitPayment
    {
        return DB::transaction(function () use ($payment, $data): ChitPayment {
            if ($payment->status === 'cancelled') {
                throw ValidationException::withMessages(['payment' => 'Payment is already cancelled.']);
            }

            $payment->load(['allocations.installment', 'receipt', 'enrollment']);
            $oldValues = $payment->toArray();

            $this->reverseInstallmentPayment($payment);
            $this->reverseLedgerEntry($payment);
            $this->reverseCashbookEntry($payment);

            if ($payment->receipt) {
                $payment->receipt->update([
                    'status' => 'cancelled',
                    'cancelled_by' => Auth::id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => $data['cancellation_reason'],
                    'pdf_path' => null,
                ]);
            }

            $payment = $this->payments->cancel($payment, [
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $data['cancellation_reason'],
            ]);

            $this->updateEnrollmentTotals($payment->enrollment);
            $payment->load(['customer', 'enrollment.scheme', 'paymentMode', 'branch', 'staff', 'receipt', 'allocations.installment']);
            $this->logPaymentAction($payment, 'cancellation', 'cancelled', $oldValues, $payment->toArray());

            return $payment;
        });
    }

    public function reverseInstallmentPayment(ChitPayment $payment): void
    {
        foreach ($payment->allocations as $allocation) {
            $installment = $allocation->installment;

            if (! $installment) {
                continue;
            }

            $paidReduction = (float) $allocation->amount + (float) $allocation->late_fee_amount;
            $newLateFee = max(0, round((float) $installment->late_fee - (float) $allocation->late_fee_amount, 2));
            $newPaidAmount = max(0, round((float) $installment->paid_amount - $paidReduction, 2));
            $dueTotal = (float) $installment->due_amount + $newLateFee;
            $newBalance = max(0, round($dueTotal - $newPaidAmount, 2));

            $installment->update([
                'late_fee' => $newLateFee,
                'paid_amount' => $newPaidAmount,
                'balance_amount' => $newBalance,
                'status' => $this->resolveInstallmentStatus($installment, $newPaidAmount, $newBalance),
                'paid_date' => $newBalance > 0 ? null : $installment->paid_date,
            ]);
        }
    }

    public function reverseLedgerEntry(ChitPayment $payment): ChitLedger
    {
        return $this->ledgerService->createLedgerEntry([
            'enrollment_id' => $payment->enrollment_id,
            'customer_id' => $payment->customer_id,
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'adjustment',
            'debit' => $payment->amount,
            'credit' => 0,
            'reference_type' => ChitPayment::class,
            'reference_id' => $payment->id,
            'remarks' => "Payment cancellation {$payment->payment_no}",
            'created_by' => Auth::id(),
        ]);
    }

    public function reverseCashbookEntry(ChitPayment $payment): Cashbook
    {
        return $this->cashflowService->createCashbookEntry([
            'branch_id' => $payment->branch_id,
            'cashbook_date' => now()->toDateString(),
            'transaction_type' => 'refund',
            'payment_mode_id' => $payment->payment_mode_id,
            'debit' => $payment->total_amount,
            'credit' => 0,
            'reference_type' => ChitPayment::class,
            'reference_id' => $payment->id,
            'remarks' => "Payment cancellation {$payment->payment_no}",
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function requestPaymentEditApproval(ChitPayment $payment, array $data): ChitPayment
    {
        return DB::transaction(function () use ($payment, $data): ChitPayment {
            $oldValues = $payment->toArray();
            $payment = $this->payments->update($payment, [
                'edit_status' => 'pending',
                'edit_payload' => $data,
                'edit_requested_by' => Auth::id(),
                'edit_requested_at' => now(),
            ]);

            $this->logPaymentAction($payment, 'update', 'edit approval requested', $oldValues, $payment->toArray());

            return $payment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function approvePaymentEdit(ChitPayment $payment, array $data): ChitPayment
    {
        return DB::transaction(function () use ($payment, $data): ChitPayment {
            if ($payment->edit_status !== 'pending' || ! $payment->edit_payload) {
                throw ValidationException::withMessages(['payment' => 'No pending edit approval found.']);
            }

            $oldValues = $payment->load(['allocations.installment', 'receipt', 'enrollment'])->toArray();

            if (! (bool) ($data['approved'] ?? true)) {
                $payment = $this->payments->update($payment, [
                    'edit_status' => 'rejected',
                    'edit_approved_by' => Auth::id(),
                    'edit_approved_at' => now(),
                ]);

                $this->logPaymentAction($payment, 'update', 'edit approval rejected', $oldValues, $payment->toArray());

                return $payment;
            }

            $payload = $payment->edit_payload;
            $oldEnrollment = $payment->enrollment;

            $this->reverseInstallmentPayment($payment);
            $this->reverseLedgerEntry($payment);
            $this->reverseCashbookEntry($payment);
            $payment->allocations()->delete();

            $enrollment = $this->resolveEnrollment($payload);
            $paymentMode = PaymentMode::findOrFail((int) $payload['payment_mode_id']);
            $this->validateTransactionReference($paymentMode, $payload['transaction_id'] ?? null);

            $allocationPlan = match ((string) $payload['payment_type']) {
                'full' => $this->collectFullPayment($enrollment, $payload),
                'partial' => $this->collectPartialPayment($enrollment, $payload),
                'advance' => $this->collectAdvancePayment($enrollment, $payload),
                'multiple_month' => $this->collectMultipleMonthPayment($enrollment, $payload),
                default => throw ValidationException::withMessages(['payment_type' => 'Invalid payment type.']),
            };

            $lateFeeAmount = (float) ($payload['late_fee_amount'] ?? 0);
            $payment = $this->payments->update($payment, [
                'enrollment_id' => $enrollment->id,
                'customer_id' => $enrollment->customer_id,
                'installment_id' => $allocationPlan[0]['installment']->id ?? null,
                'payment_mode_id' => $paymentMode->id,
                'branch_id' => $payload['branch_id'] ?? $enrollment->branch_id ?? Auth::user()?->branch_id,
                'staff_id' => $payload['staff_id'] ?? Auth::id(),
                'payment_date' => $payload['payment_date'],
                'amount' => round((float) $payload['amount'], 2),
                'late_fee_amount' => round($lateFeeAmount, 2),
                'total_amount' => round((float) $payload['amount'] + $lateFeeAmount, 2),
                'transaction_id' => $payload['transaction_id'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'payment_type' => $payload['payment_type'],
                'edit_status' => 'approved',
                'edit_approved_by' => Auth::id(),
                'edit_approved_at' => now(),
            ]);

            $this->applyAllocationPlan($payment, $allocationPlan, $lateFeeAmount);
            $this->updateEnrollmentTotals($oldEnrollment);
            $this->updateEnrollmentTotals($enrollment);
            $this->createLedgerEntry($payment);
            $this->createCashbookEntry($payment);
            $payment->receipt?->update([
                'enrollment_id' => $payment->enrollment_id,
                'customer_id' => $payment->customer_id,
                'receipt_date' => $payment->payment_date,
                'amount' => $payment->total_amount,
                'pdf_path' => null,
                'status' => 'active',
                'cancelled_by' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ]);

            $payment->load(['customer', 'enrollment.scheme', 'paymentMode', 'branch', 'staff', 'receipt', 'allocations.installment']);
            $this->logPaymentAction($payment, 'update', 'edit approved', $oldValues, $payment->toArray());

            return $payment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePendingPayment(ChitPayment $payment, array $data): ChitPayment
    {
        return DB::transaction(function () use ($payment, $data): ChitPayment {
            $oldValues = $payment->toArray();
            $payment = $this->payments->update($payment, [
                'payment_mode_id' => $data['payment_mode_id'],
                'branch_id' => $data['branch_id'] ?? $payment->branch_id,
                'staff_id' => $data['staff_id'] ?? $payment->staff_id,
                'payment_date' => $data['payment_date'],
                'transaction_id' => $data['transaction_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);
            $this->logPaymentAction($payment, 'update', 'updated', $oldValues, $payment->toArray());

            return $payment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveEnrollment(array $data): ChitEnrollment
    {
        $enrollment = ChitEnrollment::query()
            ->with(['scheme', 'installments' => fn ($query) => $query->orderBy('installment_no')])
            ->lockForUpdate()
            ->findOrFail((int) $data['enrollment_id']);

        if ((int) $enrollment->customer_id !== (int) $data['customer_id']) {
            throw ValidationException::withMessages(['customer_id' => 'Selected customer does not match the enrollment.']);
        }

        if ($enrollment->status !== 'active') {
            throw ValidationException::withMessages(['enrollment_id' => 'Payments can be collected only for active enrollments.']);
        }

        return $enrollment;
    }

    /**
     * @return array<int, array{installment: ChitInstallment, amount: float}>
     */
    private function buildAllocationPlan(ChitEnrollment $enrollment, float $amount, mixed $installmentId, bool $allowMultiple, bool $fullOnly): array
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        $installments = $this->pendingInstallments($enrollment, $installmentId);
        $remaining = round($amount, 2);
        $plan = [];

        foreach ($installments as $installment) {
            $balance = (float) $installment->balance_amount;

            if ($balance <= 0) {
                continue;
            }

            if ($fullOnly && round($remaining, 2) !== round($balance, 2)) {
                throw ValidationException::withMessages(['amount' => 'Full payment amount must equal the selected installment balance.']);
            }

            $applied = $allowMultiple ? min($remaining, $balance) : min($remaining, $balance);
            $plan[] = [
                'installment' => $installment,
                'amount' => round($applied, 2),
            ];
            $remaining = round($remaining - $applied, 2);

            if (! $allowMultiple || $remaining <= 0) {
                break;
            }
        }

        if ($plan === []) {
            throw ValidationException::withMessages(['installment_id' => 'No pending installment found for payment collection.']);
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount exceeds pending installment balance.']);
        }

        return $plan;
    }

    /**
     * @return \Illuminate\Support\Collection<int, ChitInstallment>
     */
    private function pendingInstallments(ChitEnrollment $enrollment, mixed $installmentId): \Illuminate\Support\Collection
    {
        $query = ChitInstallment::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('balance_amount', '>', 0)
            ->orderBy('installment_no');

        if ($installmentId) {
            $selected = ChitInstallment::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('id', $installmentId)
                ->firstOrFail();

            return collect([$selected])->merge(
                $query->where('installment_no', '>', $selected->installment_no)->get()
            )->filter(fn (ChitInstallment $installment): bool => (float) $installment->balance_amount > 0)->values();
        }

        return $query->get();
    }

    /**
     * @param  array<int, array{installment: ChitInstallment, amount: float}>  $allocationPlan
     */
    private function applyAllocationPlan(ChitPayment $payment, array $allocationPlan, float $lateFeeAmount): void
    {
        foreach ($allocationPlan as $index => $allocation) {
            $installment = $allocation['installment']->refresh();
            $principalAmount = (float) $allocation['amount'];
            $appliedLateFee = $index === 0 ? round($lateFeeAmount, 2) : 0.0;
            $newLateFee = round((float) $installment->late_fee + $appliedLateFee, 2);
            $newPaidAmount = round((float) $installment->paid_amount + $principalAmount + $appliedLateFee, 2);
            $newBalance = max(0, round((float) $installment->due_amount + $newLateFee - $newPaidAmount, 2));

            $installment->update([
                'late_fee' => $newLateFee,
                'paid_amount' => $newPaidAmount,
                'balance_amount' => $newBalance,
                'status' => $this->resolveInstallmentStatus($installment, $newPaidAmount, $newBalance),
                'paid_date' => $newBalance <= 0 ? $payment->payment_date : $installment->paid_date,
            ]);

            ChitPaymentAllocation::create([
                'payment_id' => $payment->id,
                'installment_id' => $installment->id,
                'amount' => $principalAmount,
                'late_fee_amount' => $appliedLateFee,
            ]);
        }
    }

    private function resolveInstallmentStatus(ChitInstallment $installment, float $paidAmount, float $balanceAmount): string
    {
        if ($balanceAmount <= 0) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return $installment->due_date && $installment->due_date->lt(today()) ? 'overdue' : 'pending';
    }

    private function validateTransactionReference(PaymentMode $paymentMode, mixed $transactionId): void
    {
        if ($paymentMode->code !== 'cash' && blank($transactionId)) {
            throw ValidationException::withMessages([
                'transaction_id' => 'Transaction ID is required for '.$paymentMode->name.' payments.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logPaymentAction(
        ChitPayment $payment,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'chit_payments',
            'description' => "Payment {$payment->payment_no} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => ChitPayment::class,
            'auditable_id' => $payment->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
