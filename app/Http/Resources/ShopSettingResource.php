<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->typedValue(),
            'raw_value' => $this->value,
            'type' => $this->type,
            'group_name' => $this->group_name,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }

    private function typedValue(): mixed
    {
        return match ($this->type) {
            'json' => json_decode($this->value ?? 'null', true),
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            'number' => is_numeric($this->value) ? (float) $this->value : null,
            default => $this->value,
        };
    }
}
