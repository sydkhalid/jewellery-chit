<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChitPaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'installment_id',
        'amount',
        'late_fee_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'late_fee_amount' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ChitPayment::class, 'payment_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(ChitInstallment::class, 'installment_id');
    }
}
