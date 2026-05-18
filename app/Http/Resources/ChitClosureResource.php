<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ChitClosureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'closure_no' => $this->closure_no,
            'enrollment_id' => $this->enrollment_id,
            'customer_id' => $this->customer_id,
            'closure_type' => $this->closure_type,
            'total_paid' => $this->total_paid,
            'shop_bonus' => $this->shop_bonus,
            'deductions' => $this->deductions,
            'final_maturity_value' => $this->final_maturity_value,
            'refund_amount' => $this->refund_amount,
            'jewellery_adjustment_amount' => $this->jewellery_adjustment_amount,
            'customer_signature' => $this->customer_signature,
            'customer_signature_url' => $this->customer_signature ? Storage::disk('public')->url($this->customer_signature) : null,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'approved_at' => optional($this->approved_at)->toDateTimeString(),
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
            'cancelled_at' => optional($this->cancelled_at)->toDateTimeString(),
            'cancellation_reason' => $this->cancellation_reason,
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
                'start_date' => optional($this->enrollment->start_date)->toDateString(),
                'maturity_date' => optional($this->enrollment->maturity_date)->toDateString(),
                'total_months' => $this->enrollment->total_months,
                'total_payable' => $this->enrollment->total_payable,
                'total_paid' => $this->enrollment->total_paid,
                'total_pending' => $this->enrollment->total_pending,
                'scheme' => $this->enrollment->relationLoaded('scheme') && $this->enrollment->scheme ? [
                    'id' => $this->enrollment->scheme->id,
                    'scheme_code' => $this->enrollment->scheme->scheme_code,
                    'name' => $this->enrollment->scheme->name,
                    'scheme_type' => $this->enrollment->scheme->scheme_type,
                    'duration_months' => $this->enrollment->scheme->duration_months,
                ] : null,
            ]),
            'approver' => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
