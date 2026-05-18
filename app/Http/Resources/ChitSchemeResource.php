<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChitSchemeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scheme_code' => $this->scheme_code,
            'name' => $this->name,
            'scheme_type' => $this->scheme_type,
            'scheme_type_label' => str($this->scheme_type)->replace('_', ' ')->title()->toString(),
            'monthly_amount' => $this->monthly_amount,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
            'gold_weight' => $this->gold_weight,
            'duration_months' => $this->duration_months,
            'shop_bonus_type' => $this->shop_bonus_type,
            'shop_bonus_value' => $this->shop_bonus_value,
            'grace_period_days' => $this->grace_period_days,
            'late_fee_type' => $this->late_fee_type,
            'late_fee_value' => $this->late_fee_value,
            'maturity_rule' => $this->maturity_rule,
            'early_closing_rule' => $this->early_closing_rule,
            'status' => $this->status,
            'enrollments_count' => $this->whenCounted('enrollments'),
            'active_enrollments_count' => $this->whenCounted('active_enrollments'),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
