<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Cashbook;
use App\Models\ChitPayment;
use App\Models\ChitRefund;
use App\Models\JewelleryInvoice;
use App\Models\PaymentMode;
use App\Models\StaffCashHandover;
use App\Repositories\CashbookRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashflowService
{
    public const TRANSACTION_TYPES = [
        'cash_received',
        'upi_received',
        'bank_received',
        'card_received',
        'refund',
        'jewellery_adjustment',
        'staff_handover',
        'opening_balance',
        'closing_balance',
    ];

    public function __construct(
        private readonly CashbookRepository $cashbooks
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCashbookEntry(array $data): Cashbook
    {
        return DB::transaction(function () use ($data): Cashbook {
            $type = (string) $data['transaction_type'];

            if (! in_array($type, self::TRANSACTION_TYPES, true)) {
                throw ValidationException::withMessages(['transaction_type' => 'Invalid cashbook transaction type.']);
            }

            $date = Carbon::parse($data['cashbook_date'])->toDateString();
            $branchId = $data['branch_id'] ?? null;
            $debit = round((float) ($data['debit'] ?? 0), 2);
            $credit = round((float) ($data['credit'] ?? 0), 2);
            $previousBalance = $this->getLatestBalanceBeforeInsert($branchId);
            $balance = array_key_exists('balance', $data)
                ? round((float) $data['balance'], 2)
                : round($previousBalance + $credit - $debit, 2);

            $cashbook = $this->cashbooks->create([
                'branch_id' => $branchId,
                'cashbook_date' => $date,
                'transaction_type' => $type,
                'payment_mode_id' => $data['payment_mode_id'] ?? null,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $data['created_by'] ?? Auth::id(),
            ]);

            $cashbook->load(['branch', 'paymentMode', 'creator']);
            $this->logCashbookAction($cashbook, $this->eventForType($type), 'created', null, $cashbook->toArray());

            return $cashbook;
        });
    }

    public function createPaymentCashEntry(ChitPayment $payment): Cashbook
    {
        $payment->loadMissing(['paymentMode', 'branch']);

        return $this->createCashbookEntry([
            'branch_id' => $payment->branch_id,
            'cashbook_date' => $payment->payment_date,
            'transaction_type' => $this->paymentTransactionType($payment),
            'payment_mode_id' => $payment->payment_mode_id,
            'debit' => 0,
            'credit' => $payment->total_amount,
            'reference_type' => ChitPayment::class,
            'reference_id' => $payment->id,
            'remarks' => "Payment {$payment->payment_no}",
            'created_by' => Auth::id(),
        ]);
    }

    public function createRefundCashEntry(ChitRefund $refund): Cashbook
    {
        $refund->loadMissing('enrollment');

        return $this->createCashbookEntry([
            'branch_id' => $refund->enrollment?->branch_id,
            'cashbook_date' => $refund->refund_date ?? now()->toDateString(),
            'transaction_type' => 'refund',
            'payment_mode_id' => $refund->payment_mode_id,
            'debit' => $refund->amount,
            'credit' => 0,
            'reference_type' => ChitRefund::class,
            'reference_id' => $refund->id,
            'remarks' => $refund->remarks ?: "Refund {$refund->refund_no}",
            'created_by' => Auth::id(),
        ]);
    }

    public function createJewelleryAdjustmentEntry(JewelleryInvoice $invoice, bool $reverse = false): ?Cashbook
    {
        $invoice->loadMissing('enrollment');

        if (! $invoice->enrollment_id || (float) $invoice->chit_adjustment_amount <= 0) {
            return null;
        }

        $amount = (float) $invoice->chit_adjustment_amount;

        return $this->createCashbookEntry([
            'branch_id' => $invoice->enrollment?->branch_id,
            'cashbook_date' => now()->toDateString(),
            'transaction_type' => 'jewellery_adjustment',
            'payment_mode_id' => null,
            'debit' => $reverse ? 0 : $amount,
            'credit' => $reverse ? $amount : 0,
            'reference_type' => JewelleryInvoice::class,
            'reference_id' => $invoice->id,
            'remarks' => ($reverse ? 'Reversal for ' : 'Adjustment for ').$invoice->invoice_no,
            'created_by' => Auth::id(),
        ]);
    }

    public function createStaffHandoverEntry(StaffCashHandover $handover): Cashbook
    {
        return $this->createCashbookEntry([
            'branch_id' => $handover->branch_id,
            'cashbook_date' => $handover->handover_date,
            'transaction_type' => 'staff_handover',
            'payment_mode_id' => null,
            'debit' => 0,
            'credit' => $handover->total_amount,
            'reference_type' => StaffCashHandover::class,
            'reference_id' => $handover->id,
            'remarks' => "Staff handover {$handover->handover_no}",
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createOpeningBalance(array $data): Cashbook
    {
        $date = Carbon::parse($data['cashbook_date'])->toDateString();
        $branchId = $data['branch_id'] ?? null;

        if ($this->dayEntries($date, $branchId)->exists()) {
            throw ValidationException::withMessages([
                'cashbook_date' => 'Opening balance must be the first cashbook entry of the day.',
            ]);
        }

        $amount = round((float) ($data['credit'] ?? 0), 2);

        return $this->createCashbookEntry([
            'branch_id' => $branchId,
            'cashbook_date' => $date,
            'transaction_type' => 'opening_balance',
            'payment_mode_id' => $data['payment_mode_id'] ?? null,
            'debit' => 0,
            'credit' => $amount,
            'balance' => $amount,
            'remarks' => $data['remarks'] ?? 'Opening balance',
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createClosingBalance(array $data): Cashbook
    {
        $date = Carbon::parse($data['cashbook_date'])->toDateString();
        $branchId = $data['branch_id'] ?? null;

        if ($this->dayEntries($date, $branchId)->where('transaction_type', 'closing_balance')->exists()) {
            throw ValidationException::withMessages([
                'cashbook_date' => 'Closing balance is already created for this day.',
            ]);
        }

        $summary = $this->calculateDailyCashflow($date, $branchId);

        return $this->createCashbookEntry([
            'branch_id' => $branchId,
            'cashbook_date' => $date,
            'transaction_type' => 'closing_balance',
            'payment_mode_id' => $data['payment_mode_id'] ?? null,
            'debit' => 0,
            'credit' => 0,
            'balance' => $summary['closing_balance'],
            'remarks' => $data['remarks'] ?? 'Closing balance',
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * @return array<string, float|string|null>
     */
    public function calculateDailyCashflow(mixed $date, mixed $branchId = null): array
    {
        $date = Carbon::parse($date)->toDateString();

        return $this->summaryForQuery($this->dayEntries($date, $branchId), $date, $date, $branchId);
    }

    /**
     * @return array<string, float|string|null>
     */
    public function calculateDateRangeCashflow(mixed $from, mixed $to, mixed $branchId = null): array
    {
        $from = Carbon::parse($from)->toDateString();
        $to = Carbon::parse($to)->toDateString();

        return $this->summaryForQuery(
            Cashbook::query()
                ->when($branchId, fn ($query): mixed => $query->where('branch_id', $branchId))
                ->whereDate('cashbook_date', '>=', $from)
                ->whereDate('cashbook_date', '<=', $to),
            $from,
            $to,
            $branchId
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPaymentModeSummary(mixed $from, mixed $to, mixed $branchId = null): array
    {
        $from = Carbon::parse($from)->toDateString();
        $to = Carbon::parse($to)->toDateString();

        return Cashbook::query()
            ->with('paymentMode')
            ->selectRaw('payment_mode_id, SUM(debit) as debit_total, SUM(credit) as credit_total, SUM(credit - debit) as net_total')
            ->when($branchId, fn ($query): mixed => $query->where('branch_id', $branchId))
            ->whereDate('cashbook_date', '>=', $from)
            ->whereDate('cashbook_date', '<=', $to)
            ->groupBy('payment_mode_id')
            ->get()
            ->map(fn (Cashbook $row): array => [
                'payment_mode_id' => $row->payment_mode_id,
                'payment_mode' => $row->paymentMode?->name ?? 'Not specified',
                'debit_total' => round((float) $row->debit_total, 2),
                'credit_total' => round((float) $row->credit_total, 2),
                'net_total' => round((float) $row->net_total, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBranchWiseCashflow(mixed $from, mixed $to): array
    {
        $from = Carbon::parse($from)->toDateString();
        $to = Carbon::parse($to)->toDateString();

        return Cashbook::query()
            ->with('branch')
            ->selectRaw('branch_id, SUM(debit) as debit_total, SUM(credit) as credit_total, SUM(credit - debit) as net_total')
            ->whereDate('cashbook_date', '>=', $from)
            ->whereDate('cashbook_date', '<=', $to)
            ->groupBy('branch_id')
            ->get()
            ->map(fn (Cashbook $row): array => [
                'branch_id' => $row->branch_id,
                'branch' => $row->branch?->name ?? 'No branch',
                'debit_total' => round((float) $row->debit_total, 2),
                'credit_total' => round((float) $row->credit_total, 2),
                'net_total' => round((float) $row->net_total, 2),
            ])
            ->values()
            ->all();
    }

    public function getClosingBalance(mixed $date, mixed $branchId = null): float
    {
        $date = Carbon::parse($date)->toDateString();

        return (float) Cashbook::query()
            ->when($branchId, fn ($query): mixed => $query->where('branch_id', $branchId))
            ->whereDate('cashbook_date', '<=', $date)
            ->latest('cashbook_date')
            ->latest('id')
            ->value('balance');
    }

    private function paymentTransactionType(ChitPayment $payment): string
    {
        $code = $payment->paymentMode?->code ?? PaymentMode::whereKey($payment->payment_mode_id)->value('code');

        return match ($code) {
            'cash' => 'cash_received',
            'upi' => 'upi_received',
            'card' => 'card_received',
            default => 'bank_received',
        };
    }

    private function getLatestBalanceBeforeInsert(mixed $branchId): float
    {
        return (float) Cashbook::query()
            ->when($branchId, fn ($query): mixed => $query->where('branch_id', $branchId))
            ->latest('cashbook_date')
            ->latest('id')
            ->value('balance');
    }

    private function dayEntries(string $date, mixed $branchId = null): \Illuminate\Database\Eloquent\Builder
    {
        return Cashbook::query()
            ->when($branchId, fn ($query): mixed => $query->where('branch_id', $branchId))
            ->whereDate('cashbook_date', $date);
    }

    /**
     * @return array<string, float|string|null>
     */
    private function summaryForQuery(\Illuminate\Database\Eloquent\Builder $query, string $from, string $to, mixed $branchId): array
    {
        $openingBalance = (float) (clone $query)->where('transaction_type', 'opening_balance')->orderBy('id')->value('balance');
        $debitTotal = round((float) (clone $query)->where('transaction_type', '!=', 'closing_balance')->sum('debit'), 2);
        $creditTotal = round((float) (clone $query)->where('transaction_type', '!=', 'closing_balance')->sum('credit'), 2);
        $latestBalance = (float) (clone $query)->latest('cashbook_date')->latest('id')->value('balance');

        return [
            'from' => $from,
            'to' => $to,
            'branch_id' => $branchId,
            'opening_balance' => round($openingBalance, 2),
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
            'net_cashflow' => round($creditTotal - $debitTotal, 2),
            'closing_balance' => round($latestBalance ?: ($openingBalance + $creditTotal - $debitTotal), 2),
        ];
    }

    private function eventForType(string $type): string
    {
        return match ($type) {
            'opening_balance' => 'opening',
            'closing_balance' => 'closing',
            'refund' => 'refund',
            'staff_handover' => 'handover',
            'jewellery_adjustment' => 'adjustment',
            'cash_received', 'upi_received', 'bank_received', 'card_received' => 'payment',
            default => 'create',
        };
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logCashbookAction(
        Cashbook $cashbook,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'cashbooks',
            'description' => "Cashbook {$cashbook->transaction_type} entry {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => Cashbook::class,
            'auditable_id' => $cashbook->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
