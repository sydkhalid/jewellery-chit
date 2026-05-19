<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gateway_type' => $this->gateway_type,
            'provider' => $this->provider,
            'mode' => $this->mode,
            'direction' => $this->direction,
            'status' => $this->status,
            'local_reference' => $this->local_reference,
            'external_id' => $this->external_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'request_payload' => $this->request_payload,
            'response_payload' => $this->response_payload,
            'webhook_payload' => $this->webhook_payload,
            'retry_count' => $this->retry_count,
            'last_error' => $this->last_error,
            'processed_at' => optional($this->processed_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
