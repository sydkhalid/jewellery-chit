<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ChitEnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chit_no' => $this->chit_no,
            'customer_id' => $this->customer_id,
            'scheme_id' => $this->scheme_id,
            'branch_id' => $this->branch_id,
            'assigned_staff_id' => $this->assigned_staff_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'customer_code' => $this->customer->customer_code,
                'name' => $this->customer->name,
                'mobile' => $this->customer->mobile,
            ]),
            'scheme' => $this->whenLoaded('scheme', fn () => [
                'id' => $this->scheme->id,
                'scheme_code' => $this->scheme->scheme_code,
                'name' => $this->scheme->name,
                'scheme_type' => $this->scheme->scheme_type,
                'duration_months' => $this->scheme->duration_months,
                'monthly_amount' => $this->scheme->monthly_amount,
                'min_amount' => $this->scheme->min_amount,
                'max_amount' => $this->scheme->max_amount,
                'gold_weight' => $this->scheme->gold_weight,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'branch_code' => $this->branch->branch_code,
            ] : null),
            'assigned_staff' => $this->whenLoaded('assignedStaff', fn () => $this->assignedStaff ? [
                'id' => $this->assignedStaff->id,
                'name' => $this->assignedStaff->name,
                'email' => $this->assignedStaff->email,
            ] : null),
            'start_date' => optional($this->start_date)->toDateString(),
            'monthly_due_date' => $this->monthly_due_date,
            'maturity_date' => optional($this->maturity_date)->toDateString(),
            'agreement_file' => $this->agreement_file,
            'agreement_url' => $this->agreement_file ? Storage::disk('public')->url($this->agreement_file) : null,
            'remarks' => $this->remarks,
            'total_months' => $this->total_months,
            'monthly_amount' => $this->monthly_amount,
            'total_payable' => $this->total_payable,
            'total_paid' => $this->total_paid,
            'total_pending' => $this->total_pending,
            'balance_amount' => $this->balance_amount,
            'maturity_status' => $this->maturity_status,
            'status' => $this->status,
            'installments' => $this->whenLoaded('installments', fn () => $this->installments->map(fn ($installment): array => [
                'id' => $installment->id,
                'installment_no' => $installment->installment_no,
                'due_date' => optional($installment->due_date)->toDateString(),
                'due_amount' => $installment->due_amount,
                'paid_amount' => $installment->paid_amount,
                'balance_amount' => $installment->balance_amount,
                'status' => $installment->status,
            ])->values()),
            'payments_count' => $this->whenCounted('payments'),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
