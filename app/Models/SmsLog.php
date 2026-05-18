<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use HasCommonScopes, HasFactory;

    protected $fillable = [
        'customer_id',
        'message_type',
        'mobile',
        'message',
        'response',
        'status',
        'retry_count',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'retry_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
