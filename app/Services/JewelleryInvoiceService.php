<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Cashbook;
use App\Models\ChitClosure;
use App\Models\ChitEnrollment;
use App\Models\JewelleryInvoice;
use App\Models\JewelleryInvoiceItem;
use App\Models\ShopSetting;
use App\Repositories\JewelleryInvoiceRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JewelleryInvoiceService
{
    public function __construct(
        private readonly JewelleryInvoiceRepository $invoices,
        private readonly LedgerService $ledgerService,
        private readonly GoldRateService $goldRateService,
        private readonly CashflowService $cashflowService
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createInvoice(array $data): JewelleryInvoice
    {
        return DB::transaction(function () use ($data): JewelleryInvoice {
            $this->assertApprovedBillingRate((float) $data['gold_rate']);
            $totals = $this->calculateInvoiceTotals($data['items'], $data);
            $enrollment = $this->resolveAdjustmentEnrollment($data, null, $totals);

            $invoice = $this->invoices->create([
                'invoice_no' => $this->generateInvoiceNumber(),
                'customer_id' => $data['customer_id'],
                'enrollment_id' => $enrollment?->id,
                'invoice_date' => $data['invoice_date'],
                'gold_rate' => $data['gold_rate'],
                'gross_weight' => $totals['gross_weight'],
                'net_weight' => $totals['net_weight'],
                'making_charge' => $totals['making_charge'],
                'wastage' => $totals['wastage'],
                'gst_amount' => $totals['gst_amount'],
                'discount' => $totals['discount'],
                'chit_adjustment_amount' => $totals['chit_adjustment_amount'],
                'total_amount' => $totals['total_amount'],
                'balance_payable' => $totals['balance_payable'],
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $this->replaceItems($invoice, $data['items']);
            $invoice->load(['customer', 'enrollment.scheme', 'items', 'creator']);
            $this->logInvoiceAction($invoice, 'create', 'created', null, $invoice->toArray());

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateInvoice(JewelleryInvoice $invoice, array $data): JewelleryInvoice
    {
        return DB::transaction(function () use ($invoice, $data): JewelleryInvoice {
            if ($invoice->status !== 'draft') {
                throw ValidationException::withMessages([
                    'invoice' => 'Only draft invoices can be edited.',
                ]);
            }

            $this->assertApprovedBillingRate((float) $data['gold_rate']);
            $oldValues = $invoice->load('items')->toArray();
            $totals = $this->calculateInvoiceTotals($data['items'], $data);
            $enrollment = $this->resolveAdjustmentEnrollment($data, $invoice, $totals);

            $invoice = $this->invoices->update($invoice, [
                'customer_id' => $data['customer_id'],
                'enrollment_id' => $enrollment?->id,
                'invoice_date' => $data['invoice_date'],
                'gold_rate' => $data['gold_rate'],
                'gross_weight' => $totals['gross_weight'],
                'net_weight' => $totals['net_weight'],
                'making_charge' => $totals['making_charge'],
                'wastage' => $totals['wastage'],
                'gst_amount' => $totals['gst_amount'],
                'discount' => $totals['discount'],
                'chit_adjustment_amount' => $totals['chit_adjustment_amount'],
                'total_amount' => $totals['total_amount'],
                'balance_payable' => $totals['balance_payable'],
            ]);

            $this->replaceItems($invoice, $data['items']);
            $invoice->load(['customer', 'enrollment.scheme', 'items', 'creator']);
            $this->logInvoiceAction($invoice, 'update', 'updated', $oldValues, $invoice->toArray());

            return $invoice;
        });
    }

    public function finalizeInvoice(JewelleryInvoice $invoice): JewelleryInvoice
    {
        return DB::transaction(function () use ($invoice): JewelleryInvoice {
            if ($invoice->status !== 'draft') {
                throw ValidationException::withMessages([
                    'invoice' => 'Only draft invoices can be finalized.',
                ]);
            }

            $invoice->load(['items', 'enrollment.closure']);
            $oldValues = $invoice->toArray();

            if ($invoice->enrollment_id && (float) $invoice->chit_adjustment_amount > 0) {
                $this->assertAdjustmentAllowed($invoice->enrollment, (float) $invoice->chit_adjustment_amount, $invoice);
            }

            $invoice = $this->invoices->update($invoice, [
                'status' => 'final',
                'finalized_by' => Auth::id(),
                'finalized_at' => now(),
            ]);

            if ($invoice->enrollment_id && (float) $invoice->chit_adjustment_amount > 0) {
                $this->applyChitAdjustment($invoice, $invoice->enrollment);
            }

            $invoice->load(['customer', 'enrollment.scheme', 'items', 'creator', 'finalizer']);
            $this->logInvoiceAction($invoice, 'final', 'finalized', $oldValues, $invoice->toArray());

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function cancelInvoice(JewelleryInvoice $invoice, array $data): JewelleryInvoice
    {
        return DB::transaction(function () use ($invoice, $data): JewelleryInvoice {
            if ($invoice->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice is already cancelled.',
                ]);
            }

            $invoice->load(['items', 'enrollment.closure']);
            $oldValues = $invoice->toArray();
            $wasFinal = $invoice->status === 'final';
            $hadAdjustment = $wasFinal && $invoice->enrollment_id && (float) $invoice->chit_adjustment_amount > 0;

            if ($wasFinal && ! (Auth::user()?->hasRole('Admin') ?? false)) {
                throw ValidationException::withMessages([
                    'invoice' => 'Only Admin can cancel a final invoice.',
                ]);
            }

            $invoice = $this->invoices->update($invoice, [
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $data['cancellation_reason'],
            ]);

            if ($hadAdjustment) {
                $this->reverseChitAdjustment($invoice);
            }

            $invoice->load(['customer', 'enrollment.scheme', 'items', 'creator', 'canceller']);
            $this->logInvoiceAction($invoice, 'cancel', 'cancelled', $oldValues, $invoice->toArray());

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function calculateItemTotal(array $item): float
    {
        $netWeight = (float) ($item['net_weight'] ?? 0);
        $rate = (float) ($item['rate'] ?? 0);
        $makingCharge = (float) ($item['making_charge'] ?? 0);
        $wastage = (float) ($item['wastage'] ?? 0);
        $gstAmount = (float) ($item['gst_amount'] ?? 0);

        return round(($netWeight * $rate) + $makingCharge + $wastage + $gstAmount, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $data
     * @return array<string, float>
     */
    public function calculateInvoiceTotals(array $items, array $data): array
    {
        $grossWeight = 0.0;
        $netWeight = 0.0;
        $makingCharge = 0.0;
        $wastage = 0.0;
        $gstAmount = 0.0;
        $itemTotal = 0.0;

        foreach ($items as $item) {
            $grossWeight += (float) ($item['gross_weight'] ?? 0);
            $netWeight += (float) ($item['net_weight'] ?? 0);
            $makingCharge += (float) ($item['making_charge'] ?? 0);
            $wastage += (float) ($item['wastage'] ?? 0);
            $gstAmount += (float) ($item['gst_amount'] ?? 0);
            $itemTotal += $this->calculateItemTotal($item);
        }

        $discount = round((float) ($data['discount'] ?? 0), 2);
        $totalAmount = max(0, round($itemTotal - $discount, 2));
        $chitAdjustmentAmount = round((float) ($data['chit_adjustment_amount'] ?? 0), 2);

        if ($chitAdjustmentAmount > $totalAmount) {
            throw ValidationException::withMessages([
                'chit_adjustment_amount' => 'Chit adjustment cannot exceed invoice total.',
            ]);
        }

        return [
            'gross_weight' => round($grossWeight, 3),
            'net_weight' => round($netWeight, 3),
            'making_charge' => round($makingCharge, 2),
            'wastage' => round($wastage, 2),
            'gst_amount' => round($gstAmount, 2),
            'discount' => $discount,
            'total_amount' => $totalAmount,
            'chit_adjustment_amount' => $chitAdjustmentAmount,
            'balance_payable' => round($totalAmount - $chitAdjustmentAmount, 2),
        ];
    }

    public function applyChitAdjustment(JewelleryInvoice $invoice, ChitEnrollment $enrollment): void
    {
        $this->assertAdjustmentAllowed($enrollment, (float) $invoice->chit_adjustment_amount, $invoice);
        $this->createLedgerAdjustment($invoice);
        $this->createCashbookEntry($invoice);
        $this->syncClosureAdjustmentTotal($enrollment);
        $this->logInvoiceAction($invoice, 'adjustment', 'chit adjustment applied', null, [
            'enrollment_id' => $enrollment->id,
            'amount' => (float) $invoice->chit_adjustment_amount,
        ]);
    }

    public function reverseChitAdjustment(JewelleryInvoice $invoice): void
    {
        if (! $invoice->enrollment_id || (float) $invoice->chit_adjustment_amount <= 0) {
            return;
        }

        $this->ledgerService->createLedgerEntry([
            'enrollment_id' => $invoice->enrollment_id,
            'customer_id' => $invoice->customer_id,
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'adjustment',
            'debit' => $invoice->chit_adjustment_amount,
            'credit' => 0,
            'reference_type' => JewelleryInvoice::class,
            'reference_id' => $invoice->id,
            'remarks' => "Jewellery adjustment reversal {$invoice->invoice_no}",
            'created_by' => Auth::id(),
        ]);

        $this->createCashbookEntry($invoice, true);
        $invoice->loadMissing('enrollment');
        $this->syncClosureAdjustmentTotal($invoice->enrollment);
        $this->logInvoiceAction($invoice, 'adjustment', 'chit adjustment reversed', null, [
            'enrollment_id' => $invoice->enrollment_id,
            'amount' => (float) $invoice->chit_adjustment_amount,
        ]);
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = (string) ShopSetting::getByKey('invoice_number_prefix', 'INV');
        $nextId = (int) JewelleryInvoice::withTrashed()->max('id') + 1;

        do {
            $number = $prefix.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while (JewelleryInvoice::withTrashed()->where('invoice_no', $number)->exists());

        return $number;
    }

    public function createLedgerAdjustment(JewelleryInvoice $invoice): void
    {
        $this->ledgerService->createJewelleryAdjustmentEntry($invoice);
    }

    public function createCashbookEntry(JewelleryInvoice $invoice, bool $reverse = false): ?Cashbook
    {
        return $this->cashflowService->createJewelleryAdjustmentEntry($invoice, $reverse);
    }

    /**
     * @return Collection<int, ChitEnrollment>
     */
    public function getCustomerMaturedChits(int $customerId): Collection
    {
        $enrollments = ChitEnrollment::query()
            ->with(['customer', 'scheme', 'closure'])
            ->where('customer_id', $customerId)
            ->orderByDesc('id')
            ->get();

        return $enrollments
            ->filter(fn (ChitEnrollment $enrollment): bool => $this->isAdjustmentEligible($enrollment))
            ->values();
    }

    /**
     * @return array<string, float>
     */
    public function adjustmentAvailability(ChitEnrollment $enrollment, ?JewelleryInvoice $invoice = null): array
    {
        $closure = $this->eligibleClosure($enrollment);
        $finalMaturityValue = round((float) ($closure?->final_maturity_value ?? $enrollment->total_paid), 2);
        $used = round((float) JewelleryInvoice::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('status', 'final')
            ->when($invoice?->id, fn ($query): mixed => $query->where('id', '!=', $invoice->id))
            ->sum('chit_adjustment_amount'), 2);

        return [
            'final_maturity_value' => $finalMaturityValue,
            'used_adjustment' => $used,
            'available_adjustment' => max(0, round($finalMaturityValue - $used, 2)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, float>  $totals
     */
    private function resolveAdjustmentEnrollment(array $data, ?JewelleryInvoice $invoice, array $totals): ?ChitEnrollment
    {
        if (blank($data['enrollment_id'] ?? null)) {
            if ((float) $totals['chit_adjustment_amount'] > 0) {
                throw ValidationException::withMessages([
                    'enrollment_id' => 'Select a matured chit before applying chit adjustment.',
                ]);
            }

            return null;
        }

        $enrollment = ChitEnrollment::query()
            ->with(['closure', 'scheme', 'customer'])
            ->findOrFail((int) $data['enrollment_id']);

        if ((int) $enrollment->customer_id !== (int) $data['customer_id']) {
            throw ValidationException::withMessages([
                'enrollment_id' => 'Selected chit does not belong to the selected customer.',
            ]);
        }

        if ((float) $totals['chit_adjustment_amount'] > 0) {
            $this->assertAdjustmentAllowed($enrollment, (float) $totals['chit_adjustment_amount'], $invoice);
        }

        return $enrollment;
    }

    private function assertAdjustmentAllowed(ChitEnrollment $enrollment, float $amount, ?JewelleryInvoice $invoice = null): void
    {
        if ($amount <= 0) {
            return;
        }

        if (! $this->isAdjustmentEligible($enrollment)) {
            throw ValidationException::withMessages([
                'enrollment_id' => 'Only matured, closed, or approved closing chits can be adjusted.',
            ]);
        }

        $availability = $this->adjustmentAvailability($enrollment, $invoice);

        if ($amount > $availability['available_adjustment']) {
            throw ValidationException::withMessages([
                'chit_adjustment_amount' => 'Chit adjustment exceeds available maturity value.',
            ]);
        }
    }

    public function isAdjustmentEligible(ChitEnrollment $enrollment): bool
    {
        $enrollment->loadMissing('closure');

        return in_array($enrollment->status, ['matured', 'closed'], true)
            || ($enrollment->maturity_date && $enrollment->maturity_date->lte(today()))
            || in_array($enrollment->closure?->status, ['approved', 'completed'], true);
    }

    private function eligibleClosure(ChitEnrollment $enrollment): ?ChitClosure
    {
        return $enrollment->closure()
            ->whereIn('status', ['approved', 'completed'])
            ->latest('id')
            ->first();
    }

    private function syncClosureAdjustmentTotal(ChitEnrollment $enrollment): void
    {
        $closure = $this->eligibleClosure($enrollment);

        if (! $closure) {
            return;
        }

        $closure->update([
            'jewellery_adjustment_amount' => round((float) JewelleryInvoice::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('status', 'final')
                ->sum('chit_adjustment_amount'), 2),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function replaceItems(JewelleryInvoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        foreach ($items as $item) {
            JewelleryInvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_name' => $item['item_name'],
                'purity' => $item['purity'] ?? null,
                'gross_weight' => $item['gross_weight'],
                'net_weight' => $item['net_weight'],
                'rate' => $item['rate'],
                'making_charge' => $item['making_charge'] ?? 0,
                'wastage' => $item['wastage'] ?? 0,
                'gst_amount' => $item['gst_amount'] ?? 0,
                'total_amount' => $this->calculateItemTotal($item),
            ]);
        }
    }

    private function assertApprovedBillingRate(float $goldRate): void
    {
        $approvedRate = $this->goldRateService->approvedBillingRate();

        if (round($goldRate, 2) !== round((float) $approvedRate->gold_22k, 2)) {
            throw ValidationException::withMessages([
                'gold_rate' => 'Jewellery invoices must use the latest approved 22K gold rate.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logInvoiceAction(
        JewelleryInvoice $invoice,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'jewellery_invoices',
            'description' => "Invoice {$invoice->invoice_no} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => JewelleryInvoice::class,
            'auditable_id' => $invoice->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
