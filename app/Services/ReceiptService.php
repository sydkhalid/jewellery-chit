<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\ChitPayment;
use App\Models\ChitReceipt;
use App\Models\ShopSetting;
use App\Repositories\ReceiptRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReceiptService
{
    public function __construct(
        private readonly ReceiptRepository $receipts
    ) {
    }

    public function generateReceipt(ChitPayment $payment): ChitReceipt
    {
        return DB::transaction(function () use ($payment): ChitReceipt {
            $existingReceipt = $this->receipts->findByPayment($payment->id);

            if ($existingReceipt) {
                return $existingReceipt->load($this->receiptRelations());
            }

            $receipt = $this->receipts->create([
                'receipt_no' => $this->generateReceiptNumber(),
                'payment_id' => $payment->id,
                'enrollment_id' => $payment->enrollment_id,
                'customer_id' => $payment->customer_id,
                'receipt_date' => $payment->payment_date,
                'amount' => $payment->total_amount,
                'status' => 'active',
            ]);

            $receipt->load($this->receiptRelations());
            $this->logReceiptAction($receipt, 'receipt', 'generated', null, $receipt->toArray());

            return $receipt;
        });
    }

    public function generateReceiptNumber(): string
    {
        $prefix = (string) ShopSetting::getByKey('receipt_prefix', 'RCPT');
        $nextId = (int) ChitReceipt::withTrashed()->max('id') + 1;

        do {
            $number = $prefix.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while (ChitReceipt::withTrashed()->where('receipt_no', $number)->exists());

        return $number;
    }

    public function generatePdf(ChitReceipt $receipt): string
    {
        $this->ensureReceiptCanOutput($receipt);
        $receipt->load($this->receiptRelations());

        $pdf = Pdf::loadView('receipts.pdf', $this->getA4PrintData($receipt))
            ->setPaper('a4', 'portrait');

        $fileName = Str::slug($receipt->receipt_no).'.pdf';
        $path = 'receipts/'.$fileName;

        Storage::disk('public')->put($path, $pdf->output());

        $oldValues = $receipt->toArray();
        $receipt = $this->receipts->update($receipt, ['pdf_path' => $path]);
        $this->logReceiptAction($receipt, 'receipt', 'pdf downloaded', $oldValues, $receipt->toArray());

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    public function getThermalPrintData(ChitReceipt $receipt): array
    {
        $this->ensureReceiptCanOutput($receipt);

        return $this->receiptData($receipt, 'Original Copy', 'thermal');
    }

    /**
     * @return array<string, mixed>
     */
    public function getA4PrintData(ChitReceipt $receipt): array
    {
        $this->ensureReceiptCanOutput($receipt);

        return $this->receiptData($receipt, 'Original Copy', 'a4');
    }

    public function incrementPrintCount(ChitReceipt $receipt): ChitReceipt
    {
        $this->ensureReceiptCanOutput($receipt);
        $oldValues = $receipt->toArray();
        $receipt = $this->receipts->update($receipt, [
            'print_count' => (int) $receipt->print_count + 1,
        ]);

        $this->logReceiptAction($receipt, 'print', 'printed', $oldValues, $receipt->toArray());

        return $receipt;
    }

    /**
     * @return array<string, mixed>
     */
    public function duplicateReceipt(ChitReceipt $receipt): array
    {
        $this->ensureReceiptCanOutput($receipt);
        $receipt = $this->incrementPrintCount($receipt);
        $this->logReceiptAction($receipt, 'duplicate', 'duplicate copy generated', null, $receipt->toArray());

        return $this->receiptData($receipt, 'Duplicate Copy', 'a4');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function cancelReceipt(ChitReceipt $receipt, array $data): ChitReceipt
    {
        return DB::transaction(function () use ($receipt, $data): ChitReceipt {
            if ($receipt->status === 'cancelled') {
                throw ValidationException::withMessages(['receipt' => 'Receipt is already cancelled.']);
            }

            $oldValues = $receipt->toArray();
            $receipt = $this->receipts->cancel($receipt, [
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $data['cancellation_reason'] ?? null,
                'pdf_path' => null,
            ]);

            $this->logReceiptAction($receipt, 'cancellation', 'cancelled', $oldValues, $receipt->toArray());

            return $receipt->load($this->receiptRelations());
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function sendWhatsappReceipt(ChitReceipt $receipt): array
    {
        $this->ensureReceiptCanOutput($receipt);
        $receipt->load($this->receiptRelations());

        $message = sprintf(
            'Receipt %s for Rs. %s has been generated for chit %s.',
            $receipt->receipt_no,
            number_format((float) $receipt->amount, 2),
            $receipt->enrollment?->chit_no ?? '-'
        );

        $this->logReceiptAction($receipt, 'whatsapp', 'whatsapp share requested', null, [
            'mobile' => $receipt->customer?->mobile,
            'message' => $message,
            'placeholder' => true,
        ]);

        return [
            'status' => 'placeholder',
            'mobile' => $receipt->customer?->mobile,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function receiptData(ChitReceipt $receipt, string $copyLabel, string $printMode): array
    {
        $receipt->loadMissing($this->receiptRelations());

        return [
            'receipt' => $receipt,
            'payment' => $receipt->payment,
            'customer' => $receipt->customer,
            'enrollment' => $receipt->enrollment,
            'allocations' => $receipt->payment?->allocations ?? collect(),
            'shop' => $this->shopSettings(),
            'copyLabel' => $copyLabel,
            'printMode' => $printMode,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function shopSettings(): array
    {
        return [
            'name' => (string) ShopSetting::getByKey('shop_name', config('app.name', 'Jewellery Chit')),
            'logo' => ShopSetting::getByKey('shop_logo'),
            'address' => ShopSetting::getByKey('shop_address'),
            'mobile' => ShopSetting::getByKey('shop_mobile'),
            'email' => ShopSetting::getByKey('shop_email'),
            'gstin' => ShopSetting::getByKey('gstin'),
            'terms' => ShopSetting::getByKey('terms_and_conditions', 'Goods once adjusted against chit value are subject to shop policy. Please keep this receipt for future reference.'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function receiptRelations(): array
    {
        return [
            'customer',
            'enrollment.scheme',
            'payment.paymentMode',
            'payment.staff',
            'payment.branch',
            'payment.allocations.installment',
        ];
    }

    private function ensureReceiptCanOutput(ChitReceipt $receipt): void
    {
        if ($receipt->status === 'cancelled') {
            throw ValidationException::withMessages(['receipt' => 'Cancelled receipt cannot be printed, downloaded, or shared.']);
        }
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logReceiptAction(
        ChitReceipt $receipt,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $actorId = Auth::id();

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'chit_receipts',
            'description' => "Receipt {$receipt->receipt_no} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => ChitReceipt::class,
            'auditable_id' => $receipt->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
