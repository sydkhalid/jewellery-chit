<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChitReceiptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_no' => $this->receipt_no,
            'formatted_receipt_no' => $this->formatted_receipt_no,
            'payment_id' => $this->payment_id,
            'enrollment_id' => $this->enrollment_id,
            'customer_id' => $this->customer_id,
            'receipt_date' => optional($this->receipt_date)->toDateString(),
            'amount' => $this->amount,
            'pdf_path' => $this->pdf_path,
            'print_count' => $this->print_count,
            'status' => $this->status,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'customer_code' => $this->customer->customer_code,
                'name' => $this->customer->name,
                'mobile' => $this->customer->mobile,
                'address' => $this->customer->address,
            ]),
            'enrollment' => $this->whenLoaded('enrollment', fn () => [
                'id' => $this->enrollment->id,
                'chit_no' => $this->enrollment->chit_no,
                'scheme' => $this->enrollment->relationLoaded('scheme') && $this->enrollment->scheme ? [
                    'id' => $this->enrollment->scheme->id,
                    'name' => $this->enrollment->scheme->name,
                    'scheme_code' => $this->enrollment->scheme->scheme_code,
                ] : null,
            ]),
            'payment' => $this->whenLoaded('payment', fn () => [
                'id' => $this->payment->id,
                'payment_no' => $this->payment->payment_no,
                'payment_date' => optional($this->payment->payment_date)->toDateString(),
                'amount' => $this->payment->amount,
                'late_fee_amount' => $this->payment->late_fee_amount,
                'total_amount' => $this->payment->total_amount,
                'payment_type' => $this->payment->payment_type,
                'transaction_id' => $this->payment->transaction_id,
                'payment_mode' => $this->payment->relationLoaded('paymentMode') && $this->payment->paymentMode ? [
                    'id' => $this->payment->paymentMode->id,
                    'name' => $this->payment->paymentMode->name,
                    'code' => $this->payment->paymentMode->code,
                ] : null,
                'staff' => $this->payment->relationLoaded('staff') && $this->payment->staff ? [
                    'id' => $this->payment->staff->id,
                    'name' => $this->payment->staff->name,
                ] : null,
                'allocations' => $this->payment->relationLoaded('allocations')
                    ? $this->payment->allocations->map(fn ($allocation): array => [
                        'installment_no' => $allocation->installment?->installment_no,
                        'due_date' => optional($allocation->installment?->due_date)->toDateString(),
                        'amount' => $allocation->amount,
                        'late_fee_amount' => $allocation->late_fee_amount,
                    ])->values()
                    : [],
            ]),
            'cancelled_by' => $this->cancelled_by,
            'cancelled_at' => optional($this->cancelled_at)->toDateTimeString(),
            'cancellation_reason' => $this->cancellation_reason,
            'download_url' => route('receipts.pdf', $this->resource),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
