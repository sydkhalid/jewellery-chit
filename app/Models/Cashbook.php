<?php

namespace App\Models;

use App\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashbook extends Model
{
    use HasCommonScopes, HasFactory;

    protected $fillable = [
        'branch_id',
        'cashbook_date',
        'transaction_type',
        'payment_mode_id',
        'debit',
        'credit',
        'balance',
        'reference_type',
        'reference_id',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'cashbook_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function paymentMode(): BelongsTo
    {
        return $this->belongsTo(PaymentMode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
