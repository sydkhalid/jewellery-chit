<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntegrationTransaction extends Model
{
    use HasCommonScopes, HasFactory;

    protected $fillable = [
        'gateway_type',
        'provider',
        'mode',
        'direction',
        'status',
        'reference_type',
        'reference_id',
        'local_reference',
        'external_id',
        'amount',
        'currency',
        'request_payload',
        'response_payload',
        'webhook_payload',
        'retry_count',
        'last_error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'webhook_payload' => 'array',
            'retry_count' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
