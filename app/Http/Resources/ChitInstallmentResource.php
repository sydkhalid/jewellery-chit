<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChitInstallmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enrollment_id' => $this->enrollment_id,
            'installment_no' => $this->installment_no,
            'due_date' => optional($this->due_date)->toDateString(),
            'due_amount' => $this->due_amount,
            'paid_amount' => $this->paid_amount,
            'balance_amount' => $this->balance_amount,
            'late_fee' => $this->late_fee,
            'status' => $this->status,
            'paid_date' => optional($this->paid_date)->toDateString(),
            'enrollment' => $this->whenLoaded('enrollment', fn () => [
                'id' => $this->enrollment->id,
                'chit_no' => $this->enrollment->chit_no,
                'status' => $this->enrollment->status,
                'customer' => $this->enrollment->relationLoaded('customer') && $this->enrollment->customer ? [
                    'id' => $this->enrollment->customer->id,
                    'customer_code' => $this->enrollment->customer->customer_code,
                    'name' => $this->enrollment->customer->name,
                    'mobile' => $this->enrollment->customer->mobile,
                ] : null,
                'scheme' => $this->enrollment->relationLoaded('scheme') && $this->enrollment->scheme ? [
                    'id' => $this->enrollment->scheme->id,
                    'scheme_code' => $this->enrollment->scheme->scheme_code,
                    'name' => $this->enrollment->scheme->name,
                ] : null,
                'branch' => $this->enrollment->relationLoaded('branch') && $this->enrollment->branch ? [
                    'id' => $this->enrollment->branch->id,
                    'name' => $this->enrollment->branch->name,
                ] : null,
                'assigned_staff' => $this->enrollment->relationLoaded('assignedStaff') && $this->enrollment->assignedStaff ? [
                    'id' => $this->enrollment->assignedStaff->id,
                    'name' => $this->enrollment->assignedStaff->name,
                ] : null,
            ]),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
