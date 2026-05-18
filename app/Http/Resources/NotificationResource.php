<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'enrollment_id' => $this->enrollment_id,
            'notification_type' => $this->notification_type,
            'notification_type_label' => str($this->notification_type)->replace('_', ' ')->title()->toString(),
            'title' => $this->title,
            'message' => $this->message,
            'channel' => $this->channel,
            'status' => $this->status,
            'sent_at' => optional($this->sent_at)->toDateTimeString(),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'customer_code' => $this->customer->customer_code,
                'name' => $this->customer->name,
                'mobile' => $this->customer->mobile,
            ] : null),
            'enrollment' => $this->whenLoaded('enrollment', fn () => $this->enrollment ? [
                'id' => $this->enrollment->id,
                'chit_no' => $this->enrollment->chit_no,
                'status' => $this->enrollment->status,
            ] : null),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
