<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChitPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_no' => $this->payment_no,
            'formatted_payment_no' => $this->formatted_payment_no,
            'enrollment_id' => $this->enrollment_id,
            'customer_id' => $this->customer_id,
            'installment_id' => $this->installment_id,
            'payment_mode_id' => $this->payment_mode_id,
            'branch_id' => $this->branch_id,
            'staff_id' => $this->staff_id,
            'payment_date' => optional($this->payment_date)->toDateString(),
            'amount' => $this->amount,
            'late_fee_amount' => $this->late_fee_amount,
            'total_amount' => $this->total_amount,
            'payment_type' => $this->payment_type,
            'transaction_id' => $this->transaction_id,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'edit_status' => $this->edit_status,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'customer_code' => $this->customer->customer_code,
                'name' => $this->customer->name,
                'mobile' => $this->customer->mobile,
            ]),
            'enrollment' => $this->whenLoaded('enrollment', fn () => [
                'id' => $this->enrollment->id,
                'chit_no' => $this->enrollment->chit_no,
                'status' => $this->enrollment->status,
                'total_payable' => $this->enrollment->total_payable,
                'total_paid' => $this->enrollment->total_paid,
                'total_pending' => $this->enrollment->total_pending,
                'scheme' => $this->enrollment->relationLoaded('scheme') && $this->enrollment->scheme ? [
                    'id' => $this->enrollment->scheme->id,
                    'name' => $this->enrollment->scheme->name,
                    'scheme_code' => $this->enrollment->scheme->scheme_code,
                ] : null,
            ]),
            'payment_mode' => $this->whenLoaded('paymentMode', fn () => [
                'id' => $this->paymentMode->id,
                'name' => $this->paymentMode->name,
                'code' => $this->paymentMode->code,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'branch_code' => $this->branch->branch_code,
            ] : null),
            'staff' => $this->whenLoaded('staff', fn () => $this->staff ? [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
            ] : null),
            'receipt' => $this->whenLoaded('receipt', fn () => $this->receipt ? [
                'id' => $this->receipt->id,
                'receipt_no' => $this->receipt->receipt_no,
                'formatted_receipt_no' => $this->receipt->formatted_receipt_no,
                'status' => $this->receipt->status,
                'amount' => $this->receipt->amount,
            ] : null),
            'allocations' => $this->whenLoaded('allocations', fn () => $this->allocations->map(fn ($allocation): array => [
                'id' => $allocation->id,
                'installment_id' => $allocation->installment_id,
                'amount' => $allocation->amount,
                'late_fee_amount' => $allocation->late_fee_amount,
                'installment' => $allocation->relationLoaded('installment') && $allocation->installment ? [
                    'id' => $allocation->installment->id,
                    'installment_no' => $allocation->installment->installment_no,
                    'due_date' => optional($allocation->installment->due_date)->toDateString(),
                    'status' => $allocation->installment->status,
                ] : null,
            ])->values()),
            'cancelled_at' => optional($this->cancelled_at)->toDateTimeString(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
