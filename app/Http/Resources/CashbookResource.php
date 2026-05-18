<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashbookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'cashbook_date' => optional($this->cashbook_date)->toDateString(),
            'transaction_type' => $this->transaction_type,
            'transaction_type_label' => str($this->transaction_type)->replace('_', ' ')->title()->toString(),
            'payment_mode_id' => $this->payment_mode_id,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'balance' => $this->balance,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'remarks' => $this->remarks,
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'branch_code' => $this->branch->branch_code,
                'name' => $this->branch->name,
            ] : null),
            'payment_mode' => $this->whenLoaded('paymentMode', fn () => $this->paymentMode ? [
                'id' => $this->paymentMode->id,
                'name' => $this->paymentMode->name,
                'code' => $this->paymentMode->code,
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
