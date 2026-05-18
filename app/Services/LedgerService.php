<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\ChitClosure;
use App\Models\ChitEnrollment;
use App\Models\ChitInstallment;
use App\Models\ChitLedger;
use App\Models\ChitPayment;
use App\Models\ChitRefund;
use App\Models\Customer;
use App\Models\JewelleryInvoice;
use App\Repositories\LedgerRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LedgerService
{
    public function __construct(
        private readonly LedgerRepository $ledgers
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createLedgerEntry(array $data): ChitLedger
    {
        return DB::transaction(function () use ($data): ChitLedger {
            $enrollment = ChitEnrollment::query()->findOrFail((int) $data['enrollment_id']);

            if ((int) $enrollment->customer_id !== (int) $data['customer_id']) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Ledger customer must match the selected chit enrollment.',
                ]);
            }

            if (($data['prevent_duplicate'] ?? false) && filled($data['reference_type'] ?? null) && filled($data['reference_id'] ?? null)) {
                $existing = ChitLedger::query()
                    ->where('enrollment_id', $enrollment->id)
                    ->where('transaction_type', $data['transaction_type'])
                    ->where('reference_type', $data['reference_type'])
                    ->where('reference_id', $data['reference_id'])
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $previousBalance = $this->ledgers->getCurrentBalance($enrollment);
            $debit = round((float) ($data['debit'] ?? 0), 2);
            $credit = round((float) ($data['credit'] ?? 0), 2);

            $ledger = $this->ledgers->create([
                'enrollment_id' => $enrollment->id,
                'customer_id' => $enrollment->customer_id,
                'transaction_date' => $data['transaction_date'],
                'transaction_type' => $data['transaction_type'],
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($previousBalance + $debit - $credit, 2),
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $data['created_by'] ?? Auth::id(),
            ]);

            $this->logLedgerAction($ledger, 'create', 'ledger entry created', null, $ledger->toArray());

            return $ledger->load(['customer', 'enrollment.scheme', 'creator']);
        });
    }

    public function createDueEntry(ChitInstallment $installment, bool $preventDuplicate = true): ChitLedger
    {
        $installment->loadMissing('enrollment');

        return $this->createLedgerEntry([
            'enrollment_id' => $installment->enrollment_id,
            'customer_id' => $installment->enrollment->customer_id,
            'transaction_date' => $installment->due_date?->toDateString() ?? now()->toDateString(),
            'transaction_type' => 'due',
            'debit' => $installment->due_amount,
            'credit' => 0,
            'reference_type' => ChitInstallment::class,
            'reference_id' => $installment->id,
            'remarks' => "Installment #{$installment->installment_no} due",
            'prevent_duplicate' => $preventDuplicate,
        ]);
    }

    public function createPaymentEntry(ChitPayment $payment, bool $preventDuplicate = false): ChitLedger
    {
        return $this->createLedgerEntry([
            'enrollment_id' => $payment->enrollment_id,
            'customer_id' => $payment->customer_id,
            'transaction_date' => $payment->payment_date?->toDateString() ?? now()->toDateString(),
            'transaction_type' => 'payment',
            'debit' => 0,
            'credit' => $payment->amount,
            'reference_type' => ChitPayment::class,
            'reference_id' => $payment->id,
            'remarks' => "Payment {$payment->payment_no}",
            'prevent_duplicate' => $preventDuplicate,
        ]);
    }

    public function createLateFeeEntry(ChitPayment $payment, bool $preventDuplicate = false): ?ChitLedger
    {
        if ((float) $payment->late_fee_amount <= 0) {
            return null;
        }

        return $this->createLedgerEntry([
            'enrollment_id' => $payment->enrollment_id,
            'customer_id' => $payment->customer_id,
            'transaction_date' => $payment->payment_date?->toDateString() ?? now()->toDateString(),
            'transaction_type' => 'late_fee',
            'debit' => $payment->late_fee_amount,
            'credit' => 0,
            'reference_type' => ChitPayment::class,
            'reference_id' => $payment->id,
            'remarks' => "Late fee for {$payment->payment_no}",
            'prevent_duplicate' => $preventDuplicate,
        ]);
    }

    public function createAdvanceEntry(ChitPayment $payment, bool $preventDuplicate = false): ChitLedger
    {
        return $this->createLedgerEntry([
            'enrollment_id' => $payment->enrollment_id,
            'customer_id' => $payment->customer_id,
            'transaction_date' => $payment->payment_date?->toDateString() ?? now()->toDateString(),
            'transaction_type' => 'advance',
            'debit' => 0,
            'credit' => $payment->amount,
            'reference_type' => ChitPayment::class,
            'reference_id' => $payment->id,
            'remarks' => "Advance payment {$payment->payment_no}",
            'prevent_duplicate' => $preventDuplicate,
        ]);
    }

    public function createClosingEntry(ChitClosure $closure): ChitLedger
    {
        return $this->createLedgerEntry([
            'enrollment_id' => $closure->enrollment_id,
            'customer_id' => $closure->customer_id,
            'transaction_date' => $closure->approved_at?->toDateString() ?? now()->toDateString(),
            'transaction_type' => 'closing',
            'debit' => 0,
            'credit' => $closure->final_maturity_value,
            'reference_type' => ChitClosure::class,
            'reference_id' => $closure->id,
            'remarks' => "Closing {$closure->closure_no}",
        ]);
    }

    public function createRefundEntry(ChitRefund $refund): ChitLedger
    {
        return $this->createLedgerEntry([
            'enrollment_id' => $refund->enrollment_id,
            'customer_id' => $refund->customer_id,
            'transaction_date' => $refund->refund_date?->toDateString() ?? now()->toDateString(),
            'transaction_type' => 'refund',
            'debit' => 0,
            'credit' => $refund->amount,
            'reference_type' => ChitRefund::class,
            'reference_id' => $refund->id,
            'remarks' => "Refund {$refund->refund_no}",
        ]);
    }

    public function createJewelleryAdjustmentEntry(JewelleryInvoice $invoice): ?ChitLedger
    {
        if (! $invoice->enrollment_id || (float) $invoice->chit_adjustment_amount <= 0) {
            return null;
        }

        return $this->createLedgerEntry([
            'enrollment_id' => $invoice->enrollment_id,
            'customer_id' => $invoice->customer_id,
            'transaction_date' => $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
            'transaction_type' => 'adjustment',
            'debit' => 0,
            'credit' => $invoice->chit_adjustment_amount,
            'reference_type' => JewelleryInvoice::class,
            'reference_id' => $invoice->id,
            'remarks' => "Jewellery adjustment {$invoice->invoice_no}",
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomerLedger(Customer $customer): array
    {
        $entries = $this->ledgers->getByCustomer($customer)->get();

        return [
            'customer' => $customer->loadMissing('enrollments.scheme'),
            'entries' => $entries,
            'enrollments' => $customer->enrollments()->with('scheme')->latest()->get(),
            'total_debit' => (float) $entries->sum('debit'),
            'total_credit' => (float) $entries->sum('credit'),
            'closing_balance' => (float) optional($entries->last())->balance,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getChitLedger(ChitEnrollment $enrollment): array
    {
        $entries = $this->ledgers->getByEnrollment($enrollment)->get();

        return [
            'enrollment' => $enrollment->loadMissing(['customer', 'scheme', 'branch', 'assignedStaff']),
            'entries' => $entries,
            'total_debit' => (float) $entries->sum('debit'),
            'total_credit' => (float) $entries->sum('credit'),
            'closing_balance' => (float) optional($entries->last())->balance,
            'late_fee' => (float) $entries->where('transaction_type', 'late_fee')->sum('debit'),
            'advance' => (float) $entries->where('transaction_type', 'advance')->sum('credit'),
        ];
    }

    /**
     * @return Collection<int, ChitLedger>
     */
    public function calculateRunningBalance(ChitEnrollment $enrollment): Collection
    {
        $balance = 0.0;
        $entries = $this->ledgers->getByEnrollment($enrollment)->get();

        foreach ($entries as $entry) {
            $balance = round($balance + (float) $entry->debit - (float) $entry->credit, 2);

            if (round((float) $entry->balance, 2) !== $balance) {
                $entry->update(['balance' => $balance]);
            }
        }

        return $this->ledgers->getByEnrollment($enrollment)->get();
    }

    /**
     * @return Collection<int, ChitLedger>
     */
    public function rebuildLedger(ChitEnrollment $enrollment): Collection
    {
        if (! (Auth::user()?->hasRole('Admin') ?? false)) {
            throw ValidationException::withMessages([
                'ledger' => 'Only Admin can rebuild chit ledgers.',
            ]);
        }

        return DB::transaction(function () use ($enrollment): Collection {
            $enrollment->load(['installments', 'payments']);
            $oldValues = [
                'entry_count' => $enrollment->ledgers()->count(),
                'balance' => $this->ledgers->getCurrentBalance($enrollment),
            ];

            foreach ($enrollment->installments()->orderBy('installment_no')->get() as $installment) {
                $this->createDueEntry($installment, true);
            }

            foreach ($enrollment->payments()->where('status', 'success')->orderBy('payment_date')->orderBy('id')->get() as $payment) {
                $this->createLateFeeEntry($payment, true);
                $payment->payment_type === 'advance'
                    ? $this->createAdvanceEntry($payment, true)
                    : $this->createPaymentEntry($payment, true);
            }

            $entries = $this->calculateRunningBalance($enrollment);
            $lastEntry = $entries->last();

            if ($lastEntry) {
                $this->logLedgerAction($lastEntry, 'rebuild', 'ledger rebuilt', $oldValues, [
                    'entry_count' => $entries->count(),
                    'balance' => (float) $lastEntry->balance,
                ]);
            }

            return $entries;
        });
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logLedgerAction(
        ChitLedger $ledger,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'chit_ledgers',
            'description' => "Ledger entry #{$ledger->id} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => ChitLedger::class,
            'auditable_id' => $ledger->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
