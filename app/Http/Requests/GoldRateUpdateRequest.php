<?php

namespace App\Http\Requests;

class GoldRateUpdateRequest extends GoldRateStoreRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gold_rates.edit') ?? false;
    }
}
