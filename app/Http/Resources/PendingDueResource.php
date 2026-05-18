<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingDueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'installment_no' => $this->installment_no,
            'due_date' => optional($this->due_date)->toDateString(),
            'due_amount' => $this->due_amount,
            'paid_amount' => $this->paid_amount,
            'balance_amount' => $this->balance_amount,
            'late_fee' => $this->late_fee,
            'status' => $this->status,
            'followup_status' => $this->followup_status,
            'promise_to_pay_date' => optional($this->promise_to_pay_date)->toDateString(),
            'followup_remarks' => $this->followup_remarks,
            'reminder_count' => $this->reminder_count,
            'last_reminder_at' => optional($this->last_reminder_at)->toDateTimeString(),
            'customer' => $this->relationLoaded('enrollment') && $this->enrollment?->customer ? [
                'id' => $this->enrollment->customer->id,
                'customer_code' => $this->enrollment->customer->customer_code,
                'name' => $this->enrollment->customer->name,
                'mobile' => $this->enrollment->customer->mobile,
            ] : null,
            'enrollment' => $this->relationLoaded('enrollment') && $this->enrollment ? [
                'id' => $this->enrollment->id,
                'chit_no' => $this->enrollment->chit_no,
                'status' => $this->enrollment->status,
                'scheme' => $this->enrollment->relationLoaded('scheme') && $this->enrollment->scheme ? [
                    'id' => $this->enrollment->scheme->id,
                    'name' => $this->enrollment->scheme->name,
                    'scheme_code' => $this->enrollment->scheme->scheme_code,
                ] : null,
                'branch' => $this->enrollment->relationLoaded('branch') && $this->enrollment->branch ? [
                    'id' => $this->enrollment->branch->id,
                    'name' => $this->enrollment->branch->name,
                ] : null,
                'assigned_staff' => $this->enrollment->relationLoaded('assignedStaff') && $this->enrollment->assignedStaff ? [
                    'id' => $this->enrollment->assignedStaff->id,
                    'name' => $this->enrollment->assignedStaff->name,
                ] : null,
            ] : null,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
