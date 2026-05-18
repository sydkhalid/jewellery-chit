<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JewelleryInvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'customer_id' => $this->customer_id,
            'enrollment_id' => $this->enrollment_id,
            'invoice_date' => optional($this->invoice_date)->toDateString(),
            'gold_rate' => $this->gold_rate,
            'gross_weight' => $this->gross_weight,
            'net_weight' => $this->net_weight,
            'making_charge' => $this->making_charge,
            'wastage' => $this->wastage,
            'gst_amount' => $this->gst_amount,
            'discount' => $this->discount,
            'chit_adjustment_amount' => $this->chit_adjustment_amount,
            'total_amount' => $this->total_amount,
            'balance_payable' => $this->balance_payable,
            'status' => $this->status,
            'finalized_at' => optional($this->finalized_at)->toDateTimeString(),
            'cancelled_at' => optional($this->cancelled_at)->toDateTimeString(),
            'cancellation_reason' => $this->cancellation_reason,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'customer_code' => $this->customer->customer_code,
                'name' => $this->customer->name,
                'mobile' => $this->customer->mobile,
            ]),
            'enrollment' => $this->whenLoaded('enrollment', fn () => $this->enrollment ? [
                'id' => $this->enrollment->id,
                'chit_no' => $this->enrollment->chit_no,
                'status' => $this->enrollment->status,
                'maturity_date' => optional($this->enrollment->maturity_date)->toDateString(),
                'scheme' => $this->enrollment->relationLoaded('scheme') && $this->enrollment->scheme ? [
                    'id' => $this->enrollment->scheme->id,
                    'scheme_code' => $this->enrollment->scheme->scheme_code,
                    'name' => $this->enrollment->scheme->name,
                ] : null,
            ] : null),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'purity' => $item->purity,
                'gross_weight' => $item->gross_weight,
                'net_weight' => $item->net_weight,
                'rate' => $item->rate,
                'making_charge' => $item->making_charge,
                'wastage' => $item->wastage,
                'gst_amount' => $item->gst_amount,
                'total_amount' => $item->total_amount,
            ])->values()),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'finalizer' => $this->whenLoaded('finalizer', fn () => $this->finalizer ? [
                'id' => $this->finalizer->id,
                'name' => $this->finalizer->name,
            ] : null),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
