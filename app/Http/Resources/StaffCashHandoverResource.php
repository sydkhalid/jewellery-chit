<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffCashHandoverResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'handover_no' => $this->handover_no,
            'staff_id' => $this->staff_id,
            'branch_id' => $this->branch_id,
            'handover_date' => optional($this->handover_date)->toDateString(),
            'cash_amount' => $this->cash_amount,
            'upi_amount' => $this->upi_amount,
            'card_amount' => $this->card_amount,
            'bank_amount' => $this->bank_amount,
            'total_amount' => $this->total_amount,
            'received_by' => $this->received_by,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'staff' => $this->whenLoaded('staff', fn () => $this->staff ? [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
                'email' => $this->staff->email,
            ] : null),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'branch_code' => $this->branch->branch_code,
                'name' => $this->branch->name,
            ] : null),
            'receiver' => $this->whenLoaded('receiver', fn () => $this->receiver ? [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
            ] : null),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
