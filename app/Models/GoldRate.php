<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoldRate extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'rate_date',
        'gold_22k',
        'gold_24k',
        'silver_rate',
        'status',
        'approved_by',
        'approved_at',
        'rate_locked',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rate_date' => 'date',
            'gold_22k' => 'decimal:2',
            'gold_24k' => 'decimal:2',
            'silver_rate' => 'decimal:2',
            'approved_at' => 'datetime',
            'rate_locked' => 'boolean',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
