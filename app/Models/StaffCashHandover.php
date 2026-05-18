<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffCashHandover extends Model
{
    use HasCommonScopes, HasFactory, SoftDeletes;

    protected $fillable = [
        'handover_no',
        'staff_id',
        'branch_id',
        'handover_date',
        'cash_amount',
        'upi_amount',
        'card_amount',
        'bank_amount',
        'total_amount',
        'received_by',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'handover_date' => 'date',
            'cash_amount' => 'decimal:2',
            'upi_amount' => 'decimal:2',
            'card_amount' => 'decimal:2',
            'bank_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
