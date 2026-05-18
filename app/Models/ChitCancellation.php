<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChitCancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'customer_id',
        'cancellation_date',
        'reason',
        'refund_amount',
        'deduction_amount',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'cancellation_date' => 'date',
            'refund_amount' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ChitEnrollment::class, 'enrollment_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
