<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasCommonScopes, HasFactory;

    protected $fillable = [
        'customer_id',
        'enrollment_id',
        'notification_type',
        'title',
        'message',
        'channel',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ChitEnrollment::class, 'enrollment_id');
    }
}
