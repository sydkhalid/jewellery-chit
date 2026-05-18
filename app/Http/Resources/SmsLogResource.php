<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'message_type' => $this->message_type,
            'message_type_label' => str($this->message_type)->replace('_', ' ')->title()->toString(),
            'mobile' => $this->mobile,
            'message' => $this->message,
            'response' => $this->response,
            'status' => $this->status,
            'retry_count' => $this->retry_count,
            'sent_at' => optional($this->sent_at)->toDateTimeString(),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'customer_code' => $this->customer->customer_code,
                'name' => $this->customer->name,
                'mobile' => $this->customer->mobile,
            ] : null),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
